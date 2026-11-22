import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StreamableHTTPClientTransport } from '@modelcontextprotocol/sdk/client/streamableHttp.js';
import { LATEST_PROTOCOL_VERSION } from '@modelcontextprotocol/sdk/types.js';
import { afterAll, beforeAll, describe, expect, it } from 'vitest';

import { AuditLogger } from '../../src/audit/audit-logger.js';
import { AuthCache } from '../../src/auth/context.js';
import { startHttpServer, type HttpServerHandle } from '../../src/transports/streamable-http.js';
import { createNullLogger } from '../../src/utils/logger.js';
import { makeConfig } from '../helpers/config.js';
import { startFakeBackend, type FakeBackend } from '../helpers/fake-backend.js';

let backend: FakeBackend;
let server: HttpServerHandle;
let endpoint: URL;

beforeAll(async () => {
  backend = await startFakeBackend();

  const config = makeConfig({
    FENNEDASH_BASE_URL: backend.baseUrl,
    FENNEDASH_API_TOKEN: '',
    MCP_TRANSPORT: 'http',
    MCP_HTTP_PORT: '0',
    MCP_HTTP_HOST: '127.0.0.1',
    MCP_AUTH_CACHE_TTL: '0',
  });

  const logger = createNullLogger();
  server = await startHttpServer({
    config,
    logger,
    audit: new AuditLogger('', logger),
    authCache: new AuthCache(0),
  });

  endpoint = new URL(`http://127.0.0.1:${server.port}/mcp`);
});

afterAll(async () => {
  await server.close();
  await backend.close();
});

async function connect(token: string): Promise<{ client: Client; transport: StreamableHTTPClientTransport }> {
  const transport = new StreamableHTTPClientTransport(endpoint, {
    requestInit: { headers: { Authorization: `Bearer ${token}` } },
  });
  const client = new Client({ name: 'http-test', version: '1.0.0' });

  await client.connect(transport);
  return { client, transport };
}

async function health(): Promise<{ status: number; body: Record<string, unknown> }> {
  const response = await fetch(new URL('/healthz', endpoint));
  return { status: response.status, body: (await response.json()) as Record<string, unknown> };
}

describe('Streamable HTTP transport', () => {
  it('serves an unauthenticated health probe without leaking data', async () => {
    const result = await health();

    expect(result.status).toBe(200);
    expect(result.body).toMatchObject({
      status: 'ok',
      name: 'fennedash-mcp',
      transport: 'http',
    });
    expect(JSON.stringify(result.body)).not.toMatch(/token|staff|email/i);
  });

  it('rejects requests without a bearer token', async () => {
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json, text/event-stream' },
      body: JSON.stringify({
        jsonrpc: '2.0',
        id: 1,
        method: 'initialize',
        params: {
          protocolVersion: LATEST_PROTOCOL_VERSION,
          capabilities: {},
          clientInfo: { name: 'no-token', version: '1.0.0' },
        },
      }),
    });

    expect(response.status).toBe(401);
    expect(response.headers.get('www-authenticate')).toContain('Bearer');
  });

  it('rejects an invalid token', async () => {
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        Authorization: 'Bearer definitely-not-valid',
        'Content-Type': 'application/json',
        Accept: 'application/json, text/event-stream',
      },
      body: JSON.stringify({
        jsonrpc: '2.0',
        id: 1,
        method: 'initialize',
        params: {
          protocolVersion: LATEST_PROTOCOL_VERSION,
          capabilities: {},
          clientInfo: { name: 'bad-token', version: '1.0.0' },
        },
      }),
    });

    expect(response.status).toBe(401);
  });

  it('completes a session, lists tools and executes a tool', async () => {
    const { client, transport } = await connect('test-token');

    try {
      expect(transport.sessionId).toBeTruthy();
      expect(client.getServerVersion()).toMatchObject({ name: 'fennedash-mcp' });

      const tools = await client.listTools();
      expect(tools.tools.length).toBeGreaterThanOrEqual(55);

      const result = await client.callTool({
        name: 'fennedash_system_get_status',
        arguments: {},
      });

      expect(result.isError).not.toBe(true);
      expect(result.structuredContent).toMatchObject({ ok: true });
    } finally {
      await client.close();
    }
  });

  it('binds a session to its bearer token', async () => {
    const { client, transport } = await connect('test-token');
    const sessionId = transport.sessionId;

    try {
      const body = JSON.stringify({ jsonrpc: '2.0', id: 2, method: 'tools/list', params: {} });

      const foreign = await fetch(endpoint, {
        method: 'POST',
        headers: {
          Authorization: 'Bearer other-token',
          'Content-Type': 'application/json',
          Accept: 'application/json, text/event-stream',
          'Mcp-Session-Id': sessionId ?? '',
          'MCP-Protocol-Version': LATEST_PROTOCOL_VERSION,
        },
        body,
      });

      expect(foreign.status).toBe(403);

      const same = await fetch(endpoint, {
        method: 'POST',
        headers: {
          Authorization: 'Bearer test-token',
          'Content-Type': 'application/json',
          Accept: 'application/json, text/event-stream',
          'Mcp-Session-Id': sessionId ?? '',
          'MCP-Protocol-Version': LATEST_PROTOCOL_VERSION,
        },
        body,
      });

      expect(same.status).toBe(200);
      const payload = (await same.json()) as { result?: { tools?: unknown[] } };
      expect(payload.result?.tools?.length).toBeGreaterThanOrEqual(55);
    } finally {
      await client.close();
    }
  });

  it('removes a session when the client terminates it', async () => {
    const { client, transport } = await connect('test-token');
    const sessionId = transport.sessionId;
    expect(sessionId).toBeTruthy();

    await transport.terminateSession();
    await client.close();

    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        Authorization: 'Bearer test-token',
        'Content-Type': 'application/json',
        Accept: 'application/json, text/event-stream',
        'Mcp-Session-Id': sessionId ?? '',
        'MCP-Protocol-Version': LATEST_PROTOCOL_VERSION,
      },
      body: JSON.stringify({ jsonrpc: '2.0', id: 3, method: 'tools/list', params: {} }),
    });

    expect(response.status).toBe(404);
  });
});
