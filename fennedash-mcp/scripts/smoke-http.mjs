import { readFileSync } from 'node:fs';

import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StreamableHTTPClientTransport } from '@modelcontextprotocol/sdk/client/streamableHttp.js';
import { LATEST_PROTOCOL_VERSION } from '@modelcontextprotocol/sdk/types.js';

const env = readFileSync('.env', 'utf8');
const token = /^FENNEDASH_API_TOKEN=(.+)$/m.exec(env)?.[1]?.trim() ?? '';
const url = new URL('http://127.0.0.1:8765/mcp');

const health = await fetch('http://127.0.0.1:8765/healthz');
console.log('HEALTH', health.status, JSON.stringify(await health.json()));

const unauthorized = await fetch(url, {
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
console.log('NO_TOKEN', unauthorized.status, unauthorized.headers.get('www-authenticate'));

const transport = new StreamableHTTPClientTransport(url, {
  requestInit: { headers: { Authorization: `Bearer ${token}` } },
});

const client = new Client({ name: 'http-smoke', version: '1.0.0' });

try {
  await client.connect(transport);
  console.log('CONNECTED', client.getServerVersion()?.name, transport.sessionId);

  console.log('TOOLS', (await client.listTools()).tools.length);

  const status = await client.callTool({ name: 'fennedash_system_get_status', arguments: {} });
  console.log('STATUS_OK', status.isError !== true);

  const who = await client.callTool({ name: 'fennedash_auth_whoami', arguments: {} });
  console.log('WHOAMI_OK', who.isError !== true, JSON.stringify(who.structuredContent).slice(0, 140));
} finally {
  await client.close();
}

const afterClose = await fetch(url, {
  method: 'POST',
  headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json', Accept: 'application/json, text/event-stream' },
  body: JSON.stringify({ jsonrpc: '2.0', id: 2, method: 'tools/list', params: {} }),
});
console.log('AFTER_CLOSE', afterClose.status);
