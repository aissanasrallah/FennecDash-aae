import { z } from 'zod';

import type { ToolDefinition } from '../../server/tool-registry.js';
import { readEndpoint } from '../shared.js';

export const searchTools: readonly ToolDefinition[] = [
  {
    name: 'fennedash_search_global',
    module: 'search',
    action: 'global',
    title: 'Search across FennecDash',
    description:
      'Searches projects, clients, leads, invoices, estimates, tickets and tasks by name, company or document number in one call. The backend silently skips resources the token owner cannot view. Returns resource, id and title for each hit; follow up with the matching get tool for detail. Read-only.',
    risk: 'read',
    inputSchema: {
      q: z.string().trim().min(2).max(200).describe('Search term (at least 2 characters).'),
      limit: z.coerce
        .number()
        .int()
        .min(1)
        .max(20)
        .default(5)
        .describe('Maximum hits per resource (1-20).'),
    },
    async handler(args, ctx) {
      return readEndpoint(ctx, 'search', {
        q: String(args.q),
        limit: Number(args.limit),
      });
    },
  },
];
