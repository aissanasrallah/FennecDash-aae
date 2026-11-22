import { readFile } from 'node:fs/promises';
import { join } from 'node:path';

import { packageRoot } from '../config/env.js';
import type {
  ResourceContext,
  ResourceDefinition,
  ResourceContent,
} from '../server/resource-registry.js';
import { McpToolError } from '../security/errors.js';

function json(value: unknown): ResourceContent {
  return {
    text: JSON.stringify(value, null, 2),
    mimeType: 'application/json',
  };
}

function markdown(value: string): ResourceContent {
  return { text: value, mimeType: 'text/markdown' };
}

async function loadBundledDoc(relativePath: string): Promise<string> {
  const absolutePath = join(packageRoot, relativePath);

  try {
    return await readFile(absolutePath, 'utf8');
  } catch {
    throw new McpToolError(
      'not_found',
      `The bundled document "${relativePath}" is not available in this installation. Reinstall the package or read the documentation from the repository.`,
      { details: { document: relativePath } },
    );
  }
}

async function readEndpointResource(
  ctx: ResourceContext,
  path: string,
  query?: Record<string, string | number>,
): Promise<ResourceContent> {
  const response = await ctx.client.get(path, query, { requestId: ctx.requestId });
  return json(response.data);
}

function numericId(variables: Record<string, string | string[]>, name: string): number {
  const raw = variables[name];
  const value = Array.isArray(raw) ? raw[0] : raw;

  if (value === undefined || !/^\d+$/.test(value)) {
    throw new McpToolError('validation_failed', `The resource URI must contain a numeric ${name}.`, {
      details: { field: name },
    });
  }

  return Number(value);
}

export const resourceDefinitions: readonly ResourceDefinition[] = [
  {
    kind: 'static',
    name: 'system_overview',
    uri: 'fennedash://system/overview',
    title: 'FennecDash platform overview',
    description:
      'Combined live status, aggregate record counts and health checks for this FennecDash installation.',
    mimeType: 'application/json',
    async read(ctx) {
      const [status, info, health] = await Promise.all([
        ctx.client.get('system_status', undefined, { requestId: ctx.requestId }),
        ctx.client.get('system_info', undefined, { requestId: ctx.requestId }),
        ctx.client.get('system_health', undefined, { requestId: ctx.requestId }),
      ]);

      return json({
        status: status.data,
        info: info.data,
        health: health.data,
        generated_at: new Date().toISOString(),
      });
    },
  },
  {
    kind: 'static',
    name: 'system_capabilities',
    uri: 'fennedash://system/capabilities',
    title: 'FennecDash Agent API capabilities',
    description:
      'What the Agent API can and cannot do on this installation: resources, write operations, critical operations, limits and unsupported capabilities.',
    mimeType: 'application/json',
    async read(ctx) {
      return readEndpointResource(ctx, 'system_capabilities');
    },
  },
  {
    kind: 'static',
    name: 'system_modules',
    uri: 'fennedash://system/modules',
    title: 'Installed FennecDash modules',
    description: 'Modules recorded in the database with version, activation and on-disk presence.',
    mimeType: 'application/json',
    async read(ctx) {
      return readEndpointResource(ctx, 'system_modules');
    },
  },
  {
    kind: 'static',
    name: 'system_permissions',
    uri: 'fennedash://system/permissions',
    title: 'Effective permissions for the current identity',
    description:
      'The staff account, administrator flag and feature/capability pairs granted to the connected token owner.',
    mimeType: 'application/json',
    async read(ctx) {
      return readEndpointResource(ctx, 'system_permissions');
    },
  },
  {
    kind: 'static',
    name: 'resource_catalog',
    uri: 'fennedash://catalog/resources',
    title: 'FennecDash API resource catalog',
    description:
      'Every Agent API resource with its id field, searchable fields, filters, sortable columns and limits.',
    mimeType: 'application/json',
    async read(ctx) {
      return readEndpointResource(ctx, 'resources');
    },
  },
  {
    kind: 'static',
    name: 'tool_catalog',
    uri: 'fennedash://docs/tool-catalog',
    title: 'FennecDash MCP tool catalog',
    description:
      'The generated catalog of MCP tools, their risk class and required FennecDash permission.',
    mimeType: 'text/markdown',
    async read() {
      return markdown(await loadBundledDoc('docs/MCP_TOOL_CATALOG.md'));
    },
  },
  {
    kind: 'static',
    name: 'mcp_architecture',
    uri: 'fennedash://docs/architecture',
    title: 'FennecDash MCP architecture',
    description:
      'How the MCP server, the Agent API and FennecDash authentication fit together, including the trust boundaries.',
    mimeType: 'text/markdown',
    async read() {
      return markdown(await loadBundledDoc('docs/MCP_ARCHITECTURE.md'));
    },
  },
  {
    kind: 'static',
    name: 'security_model',
    uri: 'fennedash://docs/security',
    title: 'FennecDash MCP security model',
    description:
      'Token handling, risk classes, approvals, audit trails, redaction and deployment hardening guidance.',
    mimeType: 'text/markdown',
    async read() {
      return markdown(await loadBundledDoc('docs/SECURITY_MODEL.md'));
    },
  },
  {
    kind: 'template',
    name: 'project',
    uriTemplate: 'fennedash://projects/{id}',
    title: 'FennecDash project',
    description:
      'One project with its tasks, members, files, invoices and activity summary. Requires the "view" permission on "projects".',
    mimeType: 'application/json',
    async list(ctx) {
      try {
        const response = await ctx.client.get(
          'projects',
          { per_page: 50, sort: 'id', order: 'desc' },
          { requestId: ctx.requestId },
        );

        const rows = Array.isArray(response.data) ? response.data : [];

        return rows
          .filter(
            (entry): entry is Record<string, unknown> =>
              typeof entry === 'object' && entry !== null && 'id' in entry,
          )
          .map((entry) => ({
            uri: `fennedash://projects/${String(entry.id)}`,
            name: `project-${String(entry.id)}`,
            title: typeof entry.name === 'string' ? entry.name : `Project ${String(entry.id)}`,
          }));
      } catch {
        // resources/list must stay usable when one template cannot be
        // enumerated (permissions, module disabled, backend hiccup). The read
        // callback still reports the real error for a specific URI.
        return [];
      }
    },
    async read(_uri, variables, ctx) {
      const id = numericId(variables, 'id');
      return readEndpointResource(ctx, `projects/${id}`);
    },
  },
  {
    kind: 'template',
    name: 'client',
    uriTemplate: 'fennedash://clients/{id}',
    title: 'FennecDash client',
    description:
      'One client record with contacts and aggregate project, invoice and estimate information where available. Requires the "view" permission on "customers".',
    mimeType: 'application/json',
    async list(ctx) {
      try {
        const response = await ctx.client.get(
          'clients',
          { per_page: 50, sort: 'userid', order: 'desc' },
          { requestId: ctx.requestId },
        );

        const rows = Array.isArray(response.data) ? response.data : [];

        return rows
          .filter(
            (entry): entry is Record<string, unknown> =>
              typeof entry === 'object' && entry !== null && 'userid' in entry,
          )
          .map((entry) => ({
            uri: `fennedash://clients/${String(entry.userid)}`,
            name: `client-${String(entry.userid)}`,
            title:
              typeof entry.company === 'string' ? entry.company : `Client ${String(entry.userid)}`,
          }));
      } catch {
        return [];
      }
    },
    async read(_uri, variables, ctx) {
      const id = numericId(variables, 'id');
      return readEndpointResource(ctx, `clients/${id}`);
    },
  },
];
