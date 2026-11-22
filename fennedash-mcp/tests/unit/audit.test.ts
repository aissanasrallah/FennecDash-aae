import { mkdtemp, readFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

import { afterEach, describe, expect, it } from 'vitest';

import { AuditLogger } from '../../src/audit/audit-logger.js';
import { createNullLogger } from '../../src/utils/logger.js';

const temporaryDirectories: string[] = [];

async function temporaryAuditPath(): Promise<string> {
  const directory = await mkdtemp(join(tmpdir(), 'fennedash-mcp-audit-'));
  temporaryDirectories.push(directory);
  return join(directory, 'audit.jsonl');
}

afterEach(async () => {
  await Promise.all(
    temporaryDirectories.splice(0).map((directory) => rm(directory, { recursive: true, force: true })),
  );
});

describe('AuditLogger', () => {
  it('writes one redacted JSON line per event and serializes concurrent writes', async () => {
    const path = await temporaryAuditPath();
    const audit = new AuditLogger(path, createNullLogger());

    await Promise.all(
      Array.from({ length: 12 }, (_, index) =>
        audit.log({
          type: 'tool_call',
          requestId: `req_${index}`,
          sessionId: 'session',
          transport: 'stdio',
          tool: 'fennedash_projects_create',
          risk: 'write',
          outcome: 'ok',
          args: {
            name: 'Secret project',
            password: 'hunter2',
            token: 'fdm_0123456789abcdef0123456789abcdef01234567',
          },
        }),
      ),
    );

    await audit.close();

    const lines = (await readFile(path, 'utf8')).trim().split('\n');
    expect(lines).toHaveLength(12);

    const first = JSON.parse(lines[0] ?? '{}') as Record<string, unknown>;
    expect(first.timestamp).toMatch(/^\d{4}-\d{2}-\d{2}T/);
    expect(first.type).toBe('tool_call');
    expect(first.requestId).toBe('req_0');
    expect(first.args).toMatchObject({
      name: 'Secret project',
      password: '[redacted]',
      token: '[redacted]',
    });
  });

  it('can be disabled with an empty path', async () => {
    const audit = new AuditLogger('', createNullLogger());

    expect(audit.enabled).toBe(false);
    await expect(
      audit.log({
        type: 'server_start',
        requestId: 'req',
        sessionId: 'session',
        transport: 'http',
      }),
    ).resolves.toBeUndefined();
    await audit.close();
  });
});
