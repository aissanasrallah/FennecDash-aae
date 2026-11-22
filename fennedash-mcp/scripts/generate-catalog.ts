/**
 * Generates docs/MCP_TOOL_CATALOG.md from the live registries so the catalog
 * can never drift from the implementation.
 *
 * Usage:
 *   npm run docs:catalog           # write the file
 *   npm run docs:catalog -- --check  # fail when the file is stale
 */
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';

import { packageRoot } from '../src/config/env.js';
import { buildRegistries } from '../src/server/mcp-server.js';
import type { ToolDefinition } from '../src/server/tool-registry.js';

const PUBLIC = 'public (no token)';
const STAFF = 'authenticated staff account';
const CRITICAL_NOTE = ' + single-use approval';

/**
 * Mirrors the `$routes` table in application/controllers/api/V1.php. The
 * generator fails when a tool is added without an entry here, which keeps this
 * document honest.
 */
const PERMISSIONS: Record<string, string> = {
  fennedash_discover: STAFF,
  fennedash_api_ping: PUBLIC,
  fennedash_auth_whoami: STAFF,

  fennedash_system_get_status: '`utilities`:view',
  fennedash_system_get_info: '`utilities`:view',
  fennedash_system_get_diagnostics: '`utilities`:view',
  fennedash_system_check_health: STAFF,
  fennedash_system_get_config: STAFF,
  fennedash_system_get_version: STAFF,
  fennedash_system_get_capabilities: STAFF,
  fennedash_system_get_permissions: STAFF,
  fennedash_system_list_modules: STAFF,
  fennedash_system_get_api_catalog: STAFF,
  fennedash_system_clear_cache: '`utilities`:edit' + CRITICAL_NOTE,

  fennedash_search_global:
    'per resource: `projects`, `customers`, `leads`, `invoices`, `estimates`, `tickets`, `tasks`',

  fennedash_dashboard_overview: '`dashboard`:view',
  fennedash_dashboard_stats: '`dashboard`:view',
  fennedash_dashboard_activity: '`dashboard`:view',
  fennedash_dashboard_widgets: '`dashboard`:view',
  fennedash_clients_get_summary: '`customers`:view',
  fennedash_reports_get_sales: '`reports`:view',

  fennedash_projects_create: '`projects`:create',
  fennedash_projects_update: '`projects`:edit',
  fennedash_projects_diagnose: '`projects`:view',
  fennedash_projects_get_activity: '`projects`:view',

  fennedash_users_list_roles: '`staff`:view',

  fennedash_database_list_tables: '`utilities`:view',
  fennedash_database_get_schema: '`utilities`:view',
  fennedash_database_get_stats: '`utilities`:view',
  fennedash_database_query: '`utilities`:view + host `MCP_ALLOW_SQL=1`',

  fennedash_storage_get_stats: '`utilities`:view',
  fennedash_integrations_list: '`utilities`:view',
  fennedash_logs_list: '`utilities`:view',
  fennedash_logs_search: '`utilities`:view',
  fennedash_audit_list: '`utilities`:view',
  fennedash_monitoring_get_summary: '`utilities`:view',

  fennedash_approvals_request: 'authenticated staff account with a `critical`-risk token',
};

const RESOURCE_PERMISSIONS: Record<string, string> = {
  fennedash_projects: '`projects`:view',
  fennedash_clients: '`customers`:view',
  fennedash_leads: '`leads`:view',
  fennedash_invoices: '`invoices`:view',
  fennedash_estimates: '`estimates`:view',
  fennedash_tasks: '`tasks`:view',
  fennedash_tickets: '`tickets`:view',
  fennedash_contracts: '`contracts`:view',
  fennedash_files: '`utilities`:view',
  fennedash_aae_orders: '`aae`:view',
  fennedash_activity: '`utilities`:view',
  fennedash_users: '`staff`:view',
};

function permissionFor(tool: ToolDefinition): string {
  const direct = PERMISSIONS[tool.name];

  if (direct) {
    return direct;
  }

  // Resource tools are named fennedash_<module>_<action>; the module maps to a
  // resource spec.
  if (tool.action === 'list' || tool.action === 'get') {
    const key = `fennedash_${tool.module}`;
    const resourcePermission = RESOURCE_PERMISSIONS[key];

    if (resourcePermission) {
      return resourcePermission;
    }
  }

  throw new Error(
    `No documented permission for tool "${tool.name}". Add it to PERMISSIONS in scripts/generate-catalog.ts.`,
  );
}

function escapeCell(value: string): string {
  return value.replace(/\|/g, '\\|').replace(/\r?\n/g, ' ');
}

function argumentSummary(tool: ToolDefinition): string {
  const entries = Object.entries(tool.inputSchema);

  if (entries.length === 0) {
    return '-';
  }

  return entries
    .map(([name, schema]) => {
      const optional = typeof schema === 'object' && 'isOptional' in schema
        ? (schema as { isOptional: () => boolean }).isOptional()
        : false;
      return `\`${name}\`${optional ? '?' : ''}`;
    })
    .join(', ');
}

function render(): string {
  const { tools, resources, prompts } = buildRegistries();
  const list = tools.list();

  const byModule = new Map<string, ToolDefinition[]>();

  for (const tool of list) {
    const current = byModule.get(tool.module) ?? [];
    current.push(tool);
    byModule.set(tool.module, current);
  }

  const modules = [...byModule.keys()].sort((left, right) => left.localeCompare(right));
  const counts = { read: 0, write: 0, critical: 0 };

  for (const tool of list) {
    counts[tool.risk] += 1;
  }

  const lines: string[] = [];

  lines.push('# FennecDash MCP Tool Catalog');
  lines.push('');
  lines.push(
    '> Generated by `npm run docs:catalog` from the live registries. Do not edit by hand.',
  );
  lines.push('');
  lines.push(
    `${list.length} tools, ${resources.size} resources and ${prompts.size} prompts. Tool names follow \`fennedash_<module>_<action>\`; \`fennedash_discover\` is the single documented top-level entry point.`,
  );
  lines.push('');
  lines.push('## Risk summary');
  lines.push('');
  lines.push('| Risk | Tools | Behaviour |');
  lines.push('| --- | ---: | --- |');
  lines.push(
    `| READ | ${counts.read} | No data changes. Executes with a valid token and the listed FennecDash permission. |`,
  );
  lines.push(
    `| WRITE | ${counts.write} | Changes data. Requires \`MCP_ALLOW_WRITE=true\` and the listed permission. |`,
  );
  lines.push(
    `| CRITICAL | ${counts.critical} | Destructive. Requires \`MCP_ALLOW_CRITICAL=true\`, a dry run and a single-use approval token. |`,
  );
  lines.push('');
  lines.push('## Permissions');
  lines.push('');
  lines.push(
    'FennecDash remains authoritative: the token is bound to a staff account, and `staff_can()` is checked by the Agent API for every request. The MCP server only adds a local gate (`MCP_ALLOW_WRITE`, `MCP_ALLOW_CRITICAL`, token risk ceiling) on top. Administrator accounts hold every permission implicitly.',
  );
  lines.push('');

  for (const moduleName of modules) {
    const moduleTools = byModule.get(moduleName) ?? [];

    lines.push(`## Module: \`${moduleName}\``);
    lines.push('');
    lines.push('| Tool | Risk | Arguments | FennecDash permission | Description |');
    lines.push('| --- | --- | --- | --- | --- |');

    for (const tool of moduleTools) {
      lines.push(
        `| \`${tool.name}\` | ${tool.risk.toUpperCase()} | ${escapeCell(argumentSummary(tool))} | ${escapeCell(permissionFor(tool))} | ${escapeCell(tool.description)} |`,
      );
    }

    lines.push('');
  }

  lines.push('## Resources');
  lines.push('');
  lines.push('| URI | Name | MIME type | Description |');
  lines.push('| --- | --- | --- | --- |');

  for (const resource of resources.list()) {
    const uri = resource.kind === 'static' ? resource.uri : resource.uriTemplate;
    lines.push(
      `| \`${uri}\` | \`${resource.name}\` | ${resource.mimeType} | ${escapeCell(resource.description)} |`,
    );
  }

  lines.push('');
  lines.push('## Prompts');
  lines.push('');
  lines.push('| Prompt | Description |');
  lines.push('| --- | --- |');

  for (const prompt of prompts.list()) {
    lines.push(`| \`${prompt.name}\` | ${escapeCell(prompt.description)} |`);
  }

  lines.push('');
  lines.push('## Not supported');
  lines.push('');
  lines.push(
    'FennecDash has no public workflow/automation engine, no outbound e-mail or WhatsApp send path exposed to the Agent API, no file upload/download through MCP, and no arbitrary PHP execution. The catalog intentionally omits those tools. `fennedash_database_query` is a read-only SELECT escape hatch and stays disabled unless the host sets `MCP_ALLOW_SQL=1`.',
  );
  lines.push('');

  return lines.join('\n');
}

function main(): void {
  const target = join(packageRoot, 'docs', 'MCP_TOOL_CATALOG.md');
  const output = render();

  if (process.argv.includes('--check')) {
    let current = '';

    try {
      current = readFileSync(target, 'utf8');
    } catch {
      process.stderr.write(`Catalog file is missing: ${target}\n`);
      process.exitCode = 1;
      return;
    }

    if (current !== output) {
      process.stderr.write(
        'docs/MCP_TOOL_CATALOG.md is stale. Run `npm run docs:catalog` and commit the result.\n',
      );
      process.exitCode = 1;
      return;
    }

    process.stdout.write('Tool catalog is up to date.\n');
    return;
  }

  mkdirSync(dirname(target), { recursive: true });
  writeFileSync(target, output, 'utf8');
  process.stdout.write(`Wrote ${target}\n`);
}

main();
