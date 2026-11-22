import { z } from 'zod';

import type { ToolDefinition } from '../../server/tool-registry.js';
import type { QueryParams } from '../../integrations/fennedash-api/client.js';
import { readEndpoint } from '../shared.js';

export const dashboardTools: readonly ToolDefinition[] = [
  {
    name: 'fennedash_dashboard_overview',
    module: 'dashboard',
    action: 'overview',
    title: 'Get dashboard overview',
    description:
      'Returns live dashboard metrics: invoiced and paid totals, outstanding balance and default currency, projects grouped by status, open task and ticket counts, and AAE export order totals grouped by stage when the module is installed. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'dashboard_overview');
    },
  },
  {
    name: 'fennedash_dashboard_stats',
    module: 'dashboard',
    action: 'stats',
    title: 'Get dashboard statistics',
    description:
      'Returns six months of recorded revenue plus counts grouped by status for leads, tasks, tickets and projects. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'dashboard_stats');
    },
  },
  {
    name: 'fennedash_dashboard_activity',
    module: 'dashboard',
    action: 'activity',
    title: 'Get recent activity',
    description:
      'Returns the most recent staff activity log entries with author name, description and timestamp. Read-only.',
    risk: 'read',
    inputSchema: {
      limit: z.coerce
        .number()
        .int()
        .min(1)
        .max(100)
        .default(25)
        .describe('Number of recent entries (1-100).'),
    },
    async handler(args, ctx) {
      return readEndpoint(ctx, 'dashboard_activity', {
        limit: args.limit as number,
      });
    },
  },
  {
    name: 'fennedash_dashboard_widgets',
    module: 'dashboard',
    action: 'widgets',
    title: 'List dashboard widgets',
    description:
      'Lists the dashboard widgets registered by the platform (id, human name, view path and container). Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'dashboard_widgets');
    },
  },
  {
    name: 'fennedash_clients_get_summary',
    module: 'clients',
    action: 'get_summary',
    title: 'Get client summary',
    description:
      'Returns a paginated summary of clients: id, company name, email, status, owner, currency and open balance. Useful for listing or searching clients without loading full records. Read-only; requires the "view" permission on "clients".',
    risk: 'read',
    inputSchema: {
      page: z.coerce.number().int().min(1).optional().describe('Page number, starting at 1.'),
      per_page: z.coerce
        .number()
        .int()
        .min(1)
        .max(100)
        .optional()
        .describe('Rows per page (1-100). Defaults to 25.'),
      q: z
        .string()
        .trim()
        .min(1)
        .max(200)
        .optional()
        .describe('Free-text search term applied to company name and email.'),
      status: z.coerce.number().int().optional().describe('Filter by client status id.'),
      owner: z.coerce.number().int().optional().describe('Filter by owner staff id.'),
    },
    async handler(args, ctx) {
      const query: QueryParams = {};
      if (args.page !== undefined && args.page !== null) {
        query.page = args.page as number;
      }
      if (args.per_page !== undefined && args.per_page !== null) {
        query.per_page = args.per_page as number;
      }
      if (args.q !== undefined && args.q !== null) {
        query.q = args.q as string;
      }
      if (args.status !== undefined && args.status !== null) {
        query.status = args.status as number;
      }
      if (args.owner !== undefined && args.owner !== null) {
        query.owner = args.owner as number;
      }

      return readEndpoint(ctx, 'clients_summary', query);
    },
  },
  {
    name: 'fennedash_reports_get_sales',
    module: 'reports',
    action: 'get_sales',
    title: 'Get sales report',
    description:
      'Returns a sales report for the requested period: invoiced and paid totals, outstanding balance, counts of invoices by status and top clients by invoiced amount. Read-only; requires the "view" permission on "reports".',
    risk: 'read',
    inputSchema: {
      from: z
        .string()
        .regex(/^\d{4}-\d{2}-\d{2}$/, 'Expected YYYY-MM-DD')
        .optional()
        .describe('Report start date (inclusive). Defaults to the start of the current month.'),
      to: z
        .string()
        .regex(/^\d{4}-\d{2}-\d{2}$/, 'Expected YYYY-MM-DD')
        .optional()
        .describe('Report end date (inclusive). Defaults to today.'),
      currency: z.string().trim().max(8).optional().describe('Filter by currency code, e.g. USD.'),
      top: z.coerce.number().int().min(1).max(50).default(10).describe('Number of top clients to include.'),
    },
    async handler(args, ctx) {
      const query: QueryParams = {};
      if (args.from !== undefined && args.from !== null) {
        query.from = args.from as string;
      }
      if (args.to !== undefined && args.to !== null) {
        query.to = args.to as string;
      }
      if (args.currency !== undefined && args.currency !== null) {
        query.currency = args.currency as string;
      }
      query.top = args.top as number;

      return readEndpoint(ctx, 'reports_sales', query);
    },
  },
];
