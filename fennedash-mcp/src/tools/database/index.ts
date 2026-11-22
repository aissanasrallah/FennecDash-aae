import { z } from 'zod';

import type { ToolDefinition } from '../../server/tool-registry.js';
import { readEndpoint, writeEndpoint } from '../shared.js';

export const databaseTools: readonly ToolDefinition[] = [
  {
    name: 'fennedash_database_list_tables',
    module: 'database',
    action: 'list_tables',
    title: 'List database tables',
    description:
      'Lists every table in the FennecDash database with approximate row count, size, engine and collation, largest first. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'database_tables');
    },
  },
  {
    name: 'fennedash_database_get_schema',
    module: 'database',
    action: 'get_schema',
    title: 'Get table schema',
    description:
      'Returns the columns and indexes of one database table: name, type, nullability, default, key and extra attributes. Table names are validated against the live schema. Read-only.',
    risk: 'read',
    inputSchema: {
      table: z
        .string()
        .trim()
        .regex(/^[A-Za-z0-9_]+$/, 'Use letters, digits and underscores only')
        .describe('Table name, with or without the configured tbl prefix.'),
    },
    async handler(args, ctx) {
      return readEndpoint(ctx, `database_schema/${encodeURIComponent(String(args.table))}`);
    },
  },
  {
    name: 'fennedash_database_get_stats',
    module: 'database',
    action: 'get_stats',
    title: 'Get database statistics',
    description:
      'Returns database name, server version, total size, table count, server uptime and the ten largest tables. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'database_stats');
    },
  },
  {
    name: 'fennedash_database_query',
    module: 'database',
    action: 'query',
    title: 'Run read-only SQL',
    description:
      'Runs a single read-only SELECT (or WITH ... SELECT) statement. Disabled unless the FennecDash host sets MCP_ALLOW_SQL=1. The backend rejects writes, multiple statements, dangerous keywords, sensitive tables (api_tokens, api_approvals, vault, user_auto_login) and sensitive columns, and caps results at 200 rows. Use it for analysis that the resource endpoints cannot express. Read-only.',
    risk: 'read',
    inputSchema: {
      sql: z
        .string()
        .trim()
        .min(8)
        .max(10_000)
        .describe('A single SELECT statement. Do not include a trailing semicolon with multiple statements.'),
    },
    async handler(args, ctx) {
      return writeEndpoint(ctx, 'database_query', { sql: args.sql });
    },
  },
];
