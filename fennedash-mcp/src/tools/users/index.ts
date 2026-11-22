import { z } from 'zod';

import type { ToolDefinition } from '../../server/tool-registry.js';
import { readEndpoint } from '../shared.js';

export const userTools: readonly ToolDefinition[] = [
  {
    name: 'fennedash_users_list_roles',
    module: 'users',
    action: 'list_roles',
    title: 'List staff roles',
    description:
      'Lists FennecDash staff roles with their id, name, decoded feature permissions and how many staff members hold each role. Use it to understand access policies before recommending a change. Read-only; requires the "view" permission on "staff".',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'roles_list');
    },
  },
];
