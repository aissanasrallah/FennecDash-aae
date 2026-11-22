import { z } from 'zod';

import type { QueryParams } from '../../integrations/fennedash-api/client.js';
import type { ToolDefinition } from '../../server/tool-registry.js';
import { normalizeError } from '../../security/errors.js';
import { readEndpoint } from '../shared.js';

const levelSchema = z.enum(['DEBUG', 'INFO', 'ERROR', 'ALL']).optional();

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

export const monitoringTools: readonly ToolDefinition[] = [
  {
    name: 'fennedash_logs_list',
    module: 'logs',
    action: 'list',
    title: 'List application logs',
    description:
      'Returns recent FennecDash application log entries parsed from the daily log files, newest first. Filter by severity (DEBUG, INFO, ERROR, ALL), since date, or a specific log file name. The optional q term is matched case-insensitively. Read-only.',
    risk: 'read',
    inputSchema: {
      level: levelSchema.describe('Only return entries with this severity.'),
      q: z.string().trim().min(2).max(200).optional().describe('Optional case-insensitive message filter.'),
      since: z.string().trim().max(40).optional().describe('Only entries at or after this date/time.'),
      file: z.string().trim().max(120).optional().describe('Restrict to one log file, e.g. log-2026-11-04.php.'),
      limit: z.coerce.number().int().min(1).max(500).default(100).describe('Maximum entries (1-500).'),
    },
    async handler(args, ctx) {
      const query: QueryParams = { limit: Number(args.limit) };

      for (const key of ['level', 'q', 'since', 'file'] as const) {
        const value = args[key];
        if (typeof value === 'string' && value !== '') {
          query[key] = value;
        }
      }

      return readEndpoint(ctx, 'logs_list', query);
    },
  },
  {
    name: 'fennedash_logs_search',
    module: 'logs',
    action: 'search',
    title: 'Search application logs',
    description:
      'Searches FennecDash application logs for a term of at least two characters, optionally restricted by severity, date and file. Returns matching entries with timestamp, severity and source file. Read-only.',
    risk: 'read',
    inputSchema: {
      q: z.string().trim().min(2).max(200).describe('Search term.'),
      level: levelSchema.describe('Only return entries with this severity.'),
      since: z.string().trim().max(40).optional().describe('Only entries at or after this date/time.'),
      limit: z.coerce.number().int().min(1).max(500).default(100).describe('Maximum entries (1-500).'),
    },
    async handler(args, ctx) {
      const query: QueryParams = { q: String(args.q), limit: Number(args.limit) };

      for (const key of ['level', 'since'] as const) {
        const value = args[key];
        if (typeof value === 'string' && value !== '') {
          query[key] = value;
        }
      }

      return readEndpoint(ctx, 'logs_search', query);
    },
  },
  {
    name: 'fennedash_audit_list',
    module: 'audit',
    action: 'list',
    title: 'List API audit entries',
    description:
      'Returns the Agent API audit trail with action, risk, method, path, status code, duration, IP and redacted request summary. By default only entries for the current token are returned; administrators may pass scope="all". Filter by action text, HTTP status and since date. Read-only.',
    risk: 'read',
    inputSchema: {
      limit: z.coerce.number().int().min(1).max(200).default(50).describe('Rows per page (1-200).'),
      offset: z.coerce.number().int().min(0).default(0).describe('Rows to skip.'),
      action: z.string().trim().max(120).optional().describe('Match action names containing this text.'),
      status: z.coerce.number().int().min(100).max(599).optional().describe('Exact HTTP status code.'),
      since: z.string().trim().max(40).optional().describe('Only entries at or after this date/time.'),
      scope: z.enum(['token', 'all']).default('token').describe('token = current token only; all = administrator only.'),
    },
    async handler(args, ctx) {
      const query: QueryParams = {
        limit: Number(args.limit),
        offset: Number(args.offset),
        scope: typeof args.scope === 'string' ? args.scope : 'token',
      };

      for (const key of ['action', 'since'] as const) {
        const value = args[key];
        if (typeof value === 'string' && value !== '') {
          query[key] = value;
        }
      }

      if (typeof args.status === 'number') {
        query.status = args.status;
      }

      return readEndpoint(ctx, 'audit_list', query);
    },
  },
  {
    name: 'fennedash_monitoring_get_summary',
    module: 'monitoring',
    action: 'get_summary',
    title: 'Get monitoring summary',
    description:
      'Combines live health checks, system diagnostics and the latest ERROR log entries into one monitoring report. Individual sections are reported as unavailable if a call fails. Use it for a quick operational assessment, then drill into specific logs. Read-only.',
    risk: 'read',
    inputSchema: {
      error_limit: z.coerce
        .number()
        .int()
        .min(1)
        .max(100)
        .default(20)
        .describe('Number of recent ERROR entries to include.'),
    },
    async handler(args, ctx) {
      const [health, diagnostics, errors] = await Promise.all([
        settle(readEndpoint(ctx, 'system_health')),
        settle(readEndpoint(ctx, 'system_diagnostics')),
        settle(
          readEndpoint(ctx, 'logs_list', {
            level: 'ERROR',
            limit: Number(args.error_limit),
          }),
        ),
      ]);

      const warnings = [health, diagnostics, errors]
        .filter((result): result is { ok: false; error: { code: string; message: string } } => !result.ok)
        .map((result) => result.error);

      return {
        data: {
          health: health.ok ? health.value.data : { unavailable: true, error: health.error },
          diagnostics: diagnostics.ok
            ? diagnostics.value.data
            : { unavailable: true, error: diagnostics.error },
          recent_errors: errors.ok ? errors.value.data : { unavailable: true, error: errors.error },
        },
        meta: {
          partial: warnings.length > 0,
          warnings,
          generated_at: new Date().toISOString(),
        },
      };
    },
  },
];
