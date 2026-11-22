import { z } from 'zod';

import { asRecord } from '../../integrations/fennedash-api/client.js';
import type { ToolContext, ToolDefinition, ToolOutcome } from '../../server/tool-registry.js';
import { McpToolError, normalizeError } from '../../security/errors.js';
import { readEndpoint, writeEndpoint } from '../shared.js';

async function settle<T>(
  operation: Promise<T>,
): Promise<{ ok: true; value: T } | { ok: false; error: { code: string; message: string } }> {
  try {
    return { ok: true, value: await operation };
  } catch (error) {
    const normalized = normalizeError(error);
    return { ok: false, error: { code: normalized.code, message: normalized.message } };
  }
}

function section<T>(
  result: { ok: true; value: T } | { ok: false; error: { code: string; message: string } },
): unknown {
  return result.ok ? result.value : { unavailable: true, error: result.error };
}

export const systemTools: readonly ToolDefinition[] = [
  {
    name: 'fennedash_system_get_status',
    module: 'system',
    action: 'get_status',
    title: 'Get platform status',
    description:
      'Returns live platform status: application name and environment, base URL, timezone, language, database connectivity and server version, PHP version and SAPI, plus the authenticated staff id and token risk ceiling. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'system_status');
    },
  },
  {
    name: 'fennedash_system_get_info',
    module: 'system',
    action: 'get_info',
    title: 'Get platform information',
    description:
      'Returns aggregate platform information: counts of active staff, clients, open leads, projects, tasks, tickets, invoices, estimates, contracts and AAE export orders, plus how many modules are installed and active. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'system_info');
    },
  },
  {
    name: 'fennedash_system_check_health',
    module: 'system',
    action: 'check_health',
    title: 'Check system health',
    description:
      'Runs live health checks: database connection, cache/log/uploads directory existence and writability, required PHP extensions, free disk space and the presence of the Agent API tables. Returns per-check status ok/warn/fail and an overall status. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'system_health');
    },
  },
  {
    name: 'fennedash_system_get_config',
    module: 'system',
    action: 'get_config',
    title: 'Get redacted configuration',
    description:
      'Returns non-secret application settings (company name, phone, VAT, country, timezone, language, date/time format, currency and document prefixes) and PHP runtime limits. Credentials, SMTP passwords, API keys and tokens are never returned. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'system_config');
    },
  },
  {
    name: 'fennedash_system_get_version',
    module: 'system',
    action: 'get_version',
    title: 'Get version information',
    description:
      'Returns the application name, database migration version, CodeIgniter version, PHP version, database server version and Agent API version. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'system_version');
    },
  },
  {
    name: 'fennedash_system_get_capabilities',
    module: 'system',
    action: 'get_capabilities',
    title: 'Get platform capabilities',
    description:
      'Returns what the Agent API supports on this installation: available resources, write operations, critical operations, risk-level definitions, limits (per_page, SQL rows, rate limit), module status and explicitly unsupported capabilities. Use this before planning multi-step work. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'system_capabilities');
    },
  },
  {
    name: 'fennedash_system_get_permissions',
    module: 'system',
    action: 'get_permissions',
    title: 'Get effective permissions',
    description:
      'Returns the effective authorization context for the token owner: staff id, administrator flag, role and the feature/capability pairs granted to the staff account. Administrators implicitly hold every permission. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'system_permissions');
    },
  },
  {
    name: 'fennedash_system_list_modules',
    module: 'system',
    action: 'list_modules',
    title: 'List modules',
    description:
      'Lists every FennecDash module recorded in the database with installed version, active flag, presence on disk and whether its module entrypoint exists. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'system_modules');
    },
  },
  {
    name: 'fennedash_system_get_diagnostics',
    module: 'system',
    action: 'get_diagnostics',
    title: 'Get system diagnostics',
    description:
      'Returns diagnostic data: database size and table count, storage usage for uploads/cache/logs, log file statistics, Agent API error counts for the last 24 hours, active token count and PHP memory usage. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'system_diagnostics');
    },
  },
  {
    name: 'fennedash_system_get_api_catalog',
    module: 'system',
    action: 'get_api_catalog',
    title: 'Get Agent API catalog',
    description:
      'Lists the Agent API resources with their id field, searchable fields, filters, sortable columns and endpoint path. Use it to build correct queries instead of guessing field names. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'resources');
    },
  },
  {
    name: 'fennedash_discover',
    module: 'discover',
    action: 'platform',
    title: 'Discover FennecDash',
    description:
      'One-call platform discovery for agents: returns status, capabilities, modules, API resources and effective permissions in a single response. Individual sections are reported as unavailable if a call fails, so partial discovery is still usable. Start here when you know nothing about the instance. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      const [status, capabilities, modules, resources, permissions] = await Promise.all([
        settle(readEndpoint(ctx, 'system_status')),
        settle(readEndpoint(ctx, 'system_capabilities')),
        settle(readEndpoint(ctx, 'system_modules')),
        settle(readEndpoint(ctx, 'resources')),
        settle(readEndpoint(ctx, 'system_permissions')),
      ]);

      const warnings = [status, capabilities, modules, resources, permissions]
        .filter((result): result is { ok: false; error: { code: string; message: string } } => !result.ok)
        .map((result) => result.error);

      return {
        data: {
          status: section(status),
          capabilities: section(capabilities),
          modules: section(modules),
          resources: section(resources),
          permissions: section(permissions),
        },
        meta: {
          partial: warnings.length > 0,
          warnings,
          generated_at: new Date().toISOString(),
        },
        backendRequestId: undefined,
      };
    },
  },
  {
    name: 'fennedash_auth_whoami',
    module: 'auth',
    action: 'whoami',
    title: 'Who am I',
    description:
      'Returns the staff account and API token bound to the current connection, including token label, prefix, risk ceiling, scopes, expiry and request count. Use it to confirm which identity an agent is operating as. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'me');
    },
  },
  {
    name: 'fennedash_api_ping',
    module: 'api',
    action: 'ping',
    title: 'Ping the FennecDash API',
    description:
      'Calls the public ping endpoint and reports reachability plus measured latency. Use it to isolate connectivity problems before blaming a tool or permission. Read-only and does not require a valid token.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      const startedAt = Date.now();
      const response = await ctx.client.ping({ requestId: ctx.requestId });

      return {
        data: response.data,
        meta: {
          reachable: true,
          latency_ms: Date.now() - startedAt,
          base_url: ctx.config.baseUrl,
        },
        backendRequestId: response.requestId,
      };
    },
  },
  {
    name: 'fennedash_approvals_request',
    module: 'approvals',
    action: 'request',
    title: 'Request approval for a critical action',
    description:
      'Creates a single-use approval token for a critical operation. The approval is bound to the exact action and payload, expires after 300 seconds and requires a critical-risk API token. It does not execute anything: pass the returned approval_token to the critical tool with dry_run=false. Use dry_run=true first to see what would change. WRITE operation (creates an approval record).',
    risk: 'write',
    inputSchema: {
      action: z
        .enum(['cache_clear'])
        .describe('Critical action to approve. Currently only cache_clear is supported.'),
    },
    async handler(args, ctx) {
      return writeEndpoint(ctx, 'approvals_create', { action: args.action });
    },
  },
  {
    name: 'fennedash_system_clear_cache',
    module: 'system',
    action: 'clear_cache',
    title: 'Clear application cache',
    description:
      'Deletes files in application/cache (index.html and .htaccess are preserved). Dry-run by default: with dry_run=true it only reports how many files and bytes would be deleted. Execution requires dry_run=false plus a single-use approval_token from fennedash_approvals_request, and a critical-risk token. The result is verified by re-scanning the directory. CRITICAL operation.',
    risk: 'critical',
    inputSchema: {
      dry_run: z
        .boolean()
        .default(true)
        .describe('When true (default), only reports what would be deleted.'),
      approval_token: z
        .string()
        .min(10)
        .optional()
        .describe('Single-use approval token required when dry_run is false.'),
    },
    async handler(args, ctx) {
      const dryRun = args.dry_run !== false;

      if (dryRun) {
        const outcome = await writeEndpoint(ctx, 'cache_clear', { dry_run: true });

        return {
          ...outcome,
          meta: {
            ...(outcome.meta ?? {}),
            dry_run: true,
            approval_required_for_execution: true,
            next_step:
              'Review the reported files, then call fennedash_approvals_request with action=cache_clear and re-run this tool with dry_run=false and the approval token.',
          },
        };
      }

      const approvalToken = typeof args.approval_token === 'string' ? args.approval_token : '';

      if (approvalToken === '') {
        throw new McpToolError(
          'approval_required',
          'Executing cache_clear requires an approval token. Call fennedash_approvals_request with action=cache_clear first.',
          {
            details: {
              action: 'cache_clear',
              tool: 'fennedash_approvals_request',
              ttl_seconds: 300,
            },
          },
        );
      }

      const outcome = await writeEndpoint(ctx, 'cache_clear', {
        dry_run: false,
        approval_token: approvalToken,
      });

      const data = asRecord(outcome.data);
      const verified = outcome.meta?.verified === true && data.remaining === 0;

      if (!verified) {
        throw new McpToolError(
          'operation_unverified',
          'The cache clear ran but could not be verified: some cache files remain. Review filesystem permissions before reporting success.',
          { details: { remaining: data.remaining ?? null, deleted: data.deleted ?? null } },
        );
      }

      return {
        ...outcome,
        meta: {
          ...(outcome.meta ?? {}),
          dry_run: false,
          verified: true,
        },
      };
    },
  },
];

/** Exposed for tests and the catalog generator. */
export type SystemToolContext = ToolContext;
export type SystemToolOutcome = ToolOutcome;
