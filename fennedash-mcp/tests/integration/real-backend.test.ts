import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { InMemoryTransport } from '@modelcontextprotocol/sdk/inMemory.js';
import { describe, expect, it } from 'vitest';

import { AuditLogger } from '../../src/audit/audit-logger.js';
import { AuthCache } from '../../src/auth/context.js';
import { loadConfig } from '../../src/config/env.js';
import { FennecDashClient } from '../../src/integrations/fennedash-api/client.js';
import { buildMcpServer } from '../../src/server/mcp-server.js';
import { createNullLogger } from '../../src/utils/logger.js';

const enabled = process.env.FENNEDASH_E2E === '1';

describe.skipIf(!enabled)('real FennecDash backend (FENNEDASH_E2E=1)', () => {
  it('authenticates, discovers and executes read-only tools over MCP', async () => {
    const config = loadConfig();
    const logger = createNullLogger();
    const client = new FennecDashClient({
      baseUrl: config.baseUrl,
      token: config.apiToken,
      timeoutMs: config.requestTimeoutMs,
      userAgent: `${config.serverName}/${config.serverVersion}`,
    });

    const identity = await new AuthCache(0).resolve(client, config.apiToken);
    expect(identity.actor.staffId).toBeGreaterThan(0);

    const bundle = buildMcpServer({
      config,
      client,
      identity,
      audit: new AuditLogger('', logger),
      logger,
      transport: 'stdio',
      sessionId: 'e2e',
    });

    const [clientTransport, serverTransport] = InMemoryTransport.createLinkedPair();
    const mcp = new Client({ name: 'e2e', version: '1.0.0' });
    await Promise.all([bundle.server.connect(serverTransport), mcp.connect(clientTransport)]);

    try {
      const tools = await mcp.listTools();
      expect(tools.tools.map((tool) => tool.name)).toContain('fennedash_system_get_status');

      const ping = await mcp.callTool({ name: 'fennedash_api_ping', arguments: {} });
      expect(ping.isError).not.toBe(true);

      const status = await mcp.callTool({ name: 'fennedash_system_get_status', arguments: {} });
      expect(status.isError).not.toBe(true);

      const resources = await mcp.readResource({ uri: 'fennedash://system/capabilities' });
      expect(resources.contents).toHaveLength(1);

      const projects = await mcp.callTool({
        name: 'fennedash_projects_list',
        arguments: { per_page: 1 },
      });
      expect(projects.isError).not.toBe(true);

      const search = await mcp.callTool({
        name: 'fennedash_search_global',
        arguments: { q: 'client', limit: 2 },
      });
      expect(search.isError).not.toBe(true);
    } finally {
      await mcp.close().catch(() => undefined);
      await bundle.server.close().catch(() => undefined);
    }
  });
});
