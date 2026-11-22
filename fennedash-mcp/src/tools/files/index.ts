import type { ToolDefinition } from '../../server/tool-registry.js';
import { readEndpoint } from '../shared.js';

export const fileTools: readonly ToolDefinition[] = [
  {
    name: 'fennedash_storage_get_stats',
    module: 'storage',
    action: 'get_stats',
    title: 'Get storage statistics',
    description:
      'Returns storage usage for uploads, media, cache and logs directories (file count, size, writability) and a breakdown by file extension. Metadata only; file contents are never returned. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'storage_stats');
    },
  },
];
