import { mkdtemp, readFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StdioClientTransport, getDefaultEnvironment } from '@modelcontextprotocol/sdk/client/stdio.js';
import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest';

import { packageRoot } from '../../src/config/env.js';
import { startFakeBackend, type FakeBackend } from '../helpers/fake-backend.js';

interface SpawnedClient {
  readonly client: Client;
  readonly auditPath: string;
  close(): Promise<void>;
}

let backend: FakeBackend;
let temporaryDirectory: string;

beforeAll(async () => {
  backend = await startFakeBackend();
  temporaryDirectory = await mkdtemp(join(tmpdir(), 'fennedash-mcp-stdio-'));
});

afterAll(async () => {
  await backend.close();
  await rm(temporaryDirectory, { recursive: true, force: true });
});

const openClients: SpawnedClient[] = [];

afterEach(async () => {
  while (openClients.length > 0) {
    await openClients.pop()?.close();
  }
});

async function connect(
  overrides: Record<string, string> = {},
  token = 'test-token',
): Promise<SpawnedClient> {
  const auditPath = join(
    temporaryDirectory,
    `audit-${Date.now()}-${Math.random().toString(16).slice(2)}.jsonl`,
  );

  const transport = new StdioClientTransport({
    command: process.execPath,
    args: [join(packageRoot, 'dist', 'src', 'index.js'), '--transport', 'stdio'],
    cwd: packageRoot,
    env: {
      ...getDefaultEnvironment(),
      FENNEDASH_BASE_URL: backend.baseUrl,
      FENNEDASH_API_TOKEN: token,
      MCP_TRANSPORT: 'stdio',
      MCP_LOG_LEVEL: 'error',
      MCP_AUDIT_LOG: auditPath,
      MCP_ALLOW_WRITE: 'true',
      MCP_ALLOW_CRITICAL: 'true',
      ...overrides,
    },
    stderr: 'ignore',
  });

  const client = new Client({ name: 'stdio-test', version: '1.0.0' });
  await client.connect(transport);

  const spawned: SpawnedClient = {
    client,
    auditPath,
    async close() {
      await client.close().catch(() => undefined);
    },
  };

  openClients.push(spawned);
  return spawned;
}

async function waitForAudit(
  path: string,
  predicate: (lines: Array<Record<string, unknown>>) => boolean,
  timeoutMs = 5000,
): Promise<Array<Record<string, unknown>>> {
  const deadline = Date.now() + timeoutMs;
  let lines: Array<Record<string, unknown>> = [];

  while (Date.now() < deadline) {
    try {
      lines = (await readFile(path, 'utf8'))
        .trim()
        .split('\n')
        .filter((line) => line !== '')
        .map((line) => JSON.parse(line) as Record<string, unknown>);
    } catch {
      lines = [];
    }

    if (predicate(lines)) {
      return lines;
    }

    await new Promise((resolve) => setTimeout(resolve, 50));
  }

  return lines;
}

describe('stdio transport', () => {
  it('completes the MCP handshake and exposes tools, resources and prompts', async () => {
    const { client } = await connect();

    expect(client.getServerVersion()).toMatchObject({ name: 'fennedash-mcp' });

    const tools = await client.listTools();
    expect(tools.tools.length).toBeGreaterThanOrEqual(55);
    expect(tools.tools.map((tool) => tool.name)).toContain('fennedash_discover');

    const resources = await client.listResources();
    expect(resources.resources.map((resource) => resource.uri)).toContain(
      'fennedash://system/capabilities',
    );

    const prompts = await client.listPrompts();
    expect(prompts.prompts.map((prompt) => prompt.name)).toContain('safe_change');
  });

  it('executes read tools against the backend with the configured token', async () => {
    const { client } = await connect();

    const result = await client.callTool({
      name: 'fennedash_projects_list',
      arguments: { per_page: 2, sort: 'id' },
    });

    expect(result.isError).not.toBe(true);
    expect(result.structuredContent).toMatchObject({ ok: true, request_id: expect.any(String) });

    const data = (result.structuredContent as { data: unknown[] }).data;
    expect(data).toHaveLength(2);

    const request = backend.requests.findLast((entry) => entry.action === 'projects');
    expect(request?.authorization).toBe('Bearer test-token');
    expect(request?.requestId).toMatch(/^tool_/);
  });

  it('reports validation failures and backend not-found errors as isError results', async () => {
    const { client } = await connect();

    const invalid = await client.callTool({
      name: 'fennedash_projects_get',
      arguments: { id: 'not-a-number' },
    });
    expect(invalid.isError).toBe(true);

    const missing = await client.callTool({
      name: 'fennedash_projects_get',
      arguments: { id: 999999 },
    });

    expect(missing.isError).toBe(true);
    expect(missing.structuredContent).toMatchObject({
      ok: false,
      error: { code: 'not_found', http_status: 404 },
    });
  });

  it('rejects unknown tools at the protocol layer', async () => {
    const { client } = await connect();

    const result = await client.callTool({ name: 'fennedash_does_not_exist', arguments: {} });

    expect(result.isError).toBe(true);
  });

  it('blocks writes when MCP_ALLOW_WRITE is false and never reaches the backend', async () => {
    const { client } = await connect({ MCP_ALLOW_WRITE: 'false' });
    const before = backend.requests.filter((entry) => entry.action === 'projects_create').length;

    const result = await client.callTool({
      name: 'fennedash_projects_create',
      arguments: { name: 'Blocked project', clientid: 1 },
    });

    expect(result.isError).toBe(true);
    expect(result.structuredContent).toMatchObject({
      ok: false,
      error: { code: 'write_disabled' },
    });
    expect(backend.requests.filter((entry) => entry.action === 'projects_create')).toHaveLength(before);
  });

  it('executes writes when enabled and returns the persisted record', async () => {
    const { client } = await connect();

    const result = await client.callTool({
      name: 'fennedash_projects_create',
      arguments: { name: 'Agent project', clientid: 1, progress: 10 },
    });

    expect(result.isError).not.toBe(true);
    expect(result.structuredContent).toMatchObject({
      ok: true,
      data: { id: '99', name: 'Agent project' },
      meta: { created: true, verified: true },
    });

    const request = backend.requests.findLast((entry) => entry.action === 'projects_create');
    expect(request?.body).toMatchObject({ name: 'Agent project', clientid: 1, progress: 10 });
  });

  it('blocks critical tools entirely when MCP_ALLOW_CRITICAL is false', async () => {
    const { client } = await connect({ MCP_ALLOW_CRITICAL: 'false' });

    const result = await client.callTool({
      name: 'fennedash_system_clear_cache',
      arguments: { dry_run: true },
    });

    expect(result.isError).toBe(true);
    expect(result.structuredContent).toMatchObject({
      ok: false,
      error: { code: 'critical_disabled' },
    });
    expect(backend.requests.some((entry) => entry.action === 'cache_clear')).toBe(false);
  });

  it('requires a single-use approval for cache clearing and verifies the result', async () => {
    const { client, auditPath } = await connect();

    const dryRun = await client.callTool({
      name: 'fennedash_system_clear_cache',
      arguments: { dry_run: true },
    });

    expect(dryRun.isError).not.toBe(true);
    expect(dryRun.structuredContent).toMatchObject({
      ok: true,
      data: { dry_run: true, would_delete: 3 },
      meta: { approval_required_for_execution: true },
    });

    const denied = await client.callTool({
      name: 'fennedash_system_clear_cache',
      arguments: { dry_run: false },
    });

    expect(denied.isError).toBe(true);
    expect(denied.structuredContent).toMatchObject({
      ok: false,
      error: { code: 'approval_required' },
    });
    expect(backend.requests.filter((entry) => entry.action === 'cache_clear')).toHaveLength(1);

    const approval = await client.callTool({
      name: 'fennedash_approvals_request',
      arguments: { action: 'cache_clear' },
    });

    expect(approval.isError).not.toBe(true);
    const approvalToken = (approval.structuredContent as { data: { approval_token: string } }).data
      .approval_token;
    expect(approvalToken).toMatch(/^fdm_ap_/);

    const executed = await client.callTool({
      name: 'fennedash_system_clear_cache',
      arguments: { dry_run: false, approval_token: approvalToken },
    });

    expect(executed.isError).not.toBe(true);
    expect(executed.structuredContent).toMatchObject({
      ok: true,
      data: { dry_run: false, deleted: 3, remaining: 0 },
      meta: { verified: true },
    });

    const reused = await client.callTool({
      name: 'fennedash_system_clear_cache',
      arguments: { dry_run: false, approval_token: approvalToken },
    });

    expect(reused.isError).toBe(true);
    expect(reused.structuredContent).toMatchObject({
      ok: false,
      error: { code: 'approval_not_pending' },
    });

    await client.close().catch(() => undefined);

    const auditLines = await waitForAudit(
      auditPath,
      (lines) =>
        lines.filter(
          (line) =>
            line.type === 'tool_call' && line.tool === 'fennedash_system_clear_cache',
        ).length >= 3,
    );

    expect(auditLines.some((line) => line.type === 'server_start')).toBe(true);

    const cacheCalls = auditLines.filter(
      (line) => line.type === 'tool_call' && line.tool === 'fennedash_system_clear_cache',
    );
    expect(cacheCalls.length).toBeGreaterThanOrEqual(3);
    expect(cacheCalls.some((line) => line.outcome === 'denied')).toBe(true);

    const serialized = JSON.stringify(auditLines);
    expect(serialized).not.toContain(approvalToken);
  });
});
