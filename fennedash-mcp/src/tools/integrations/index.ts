import type { ToolDefinition } from '../../server/tool-registry.js';
import { readEndpoint } from '../shared.js';

export const integrationTools: readonly ToolDefinition[] = [
  {
    name: 'fennedash_integrations_list',
    module: 'integrations',
    action: 'list',
    title: 'List integrations',
    description:
      'Returns real integration status from the installation: every module with version and active flag, whether SMTP e-mail is configured (host present, no credentials), the configured AI provider and whether a key is present, and the available payment modes. Secrets are never returned. FennecDash has no workflow engine, so no automation trigger tool exists. Read-only.',
    risk: 'read',
    inputSchema: {},
    async handler(_args, ctx) {
      return readEndpoint(ctx, 'integrations_list');
    },
  },
];
