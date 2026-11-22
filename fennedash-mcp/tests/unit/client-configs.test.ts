import { existsSync, readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import { describe, expect, it } from 'vitest';

const packageRoot = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const integrationsDir = join(packageRoot, 'integrations');
const examplesDir = join(integrationsDir, 'examples');

/** A real issued token must never appear in a documented example. */
const REAL_TOKEN = /fdm_[0-9a-f]{40}/i;

interface ServerEntry {
  readonly command?: string;
  readonly args?: readonly string[];
  readonly env?: Record<string, string>;
  readonly type?: string;
  readonly url?: string;
  readonly serverUrl?: string;
  readonly headers?: Record<string, string>;
}

interface ClientConfig {
  readonly mcpServers?: Record<string, ServerEntry>;
}

function read(name: string): string {
  return readFileSync(join(examplesDir, name), 'utf8');
}

function readJson(name: string): ClientConfig {
  const content = read(name);
  expect(content, `${name} must not contain a real token`).not.toMatch(REAL_TOKEN);
  return JSON.parse(content) as ClientConfig;
}

function fennedashEntry(name: string): ServerEntry {
  const config = readJson(name);
  expect(config.mcpServers, `${name} must have mcpServers`).toBeTypeOf('object');
  const entry = config.mcpServers?.fennedash;

  expect(entry, `${name} must define mcpServers.fennedash`).toBeDefined();
  return entry as ServerEntry;
}

const stdioExamples = [
  'claude-code.stdio.json',
  'claude-desktop.stdio.json',
  'cursor.stdio.json',
  'windsurf.stdio.json',
  'antigravity.stdio.json',
] as const;

const httpExamples = [
  'claude-code.http.json',
  'cursor.http.json',
  'windsurf.http.json',
  'antigravity.http.json',
] as const;

describe('client configuration examples', () => {
  it.each(stdioExamples)('%s describes a valid stdio server', (name) => {
    const entry = fennedashEntry(name);

    expect(entry.command).toBe('node');
    expect(entry.args).toBeInstanceOf(Array);
    expect(entry.args).toContain('--transport');
    expect(entry.args).toContain('stdio');
    expect(entry.args?.some((arg) => arg.endsWith('dist/src/index.js'))).toBe(true);

    expect(entry.env).toBeTypeOf('object');
    expect(entry.env?.FENNEDASH_BASE_URL).toMatch(/^https:\/\//);
    expect(entry.env?.FENNEDASH_API_TOKEN).toBeTruthy();
  });

  it.each(httpExamples)('%s describes a valid Streamable HTTP server', (name) => {
    const entry = fennedashEntry(name);
    const url = entry.url ?? entry.serverUrl;

    expect(url, `${name} must define url or serverUrl`).toMatch(/^https:\/\/.+\/mcp$/);
    expect(entry.headers?.Authorization).toMatch(/^Bearer /);
  });

  it('marks the Claude Code HTTP entry with an explicit transport type', () => {
    expect(fennedashEntry('claude-code.http.json').type).toBe('http');
  });

  it('uses the documented remote key names for Windsurf and Antigravity', () => {
    expect(fennedashEntry('windsurf.http.json').serverUrl).toBeTruthy();
    expect(fennedashEntry('antigravity.http.json').serverUrl).toBeTruthy();
  });

  it('describes a valid Codex stdio table', () => {
    const content = read('codex.stdio.toml');

    expect(content).not.toMatch(REAL_TOKEN);
    expect(content).toContain('[mcp_servers.fennedash]');
    expect(content).toContain('command = "node"');
    expect(content).toContain('"--transport"');
    expect(content).toContain('"stdio"');
    expect(content).toContain('"FENNEDASH_BASE_URL"');
    expect(content).toContain('"FENNEDASH_API_TOKEN"');
  });

  it('describes a valid Codex HTTP table without a literal token', () => {
    const content = read('codex.http.toml');

    expect(content).not.toMatch(REAL_TOKEN);
    expect(content).toContain('[mcp_servers.fennedash]');
    expect(content).toContain('url = "https://');
    expect(content).toContain('bearer_token_env_var = "FENNEDASH_API_TOKEN"');
  });

  it('describes the ChatGPT gateway connector honestly', () => {
    const descriptor = JSON.parse(read('chatgpt.connector.json')) as Record<string, unknown>;

    expect(descriptor.name).toBe('FennecDash');
    expect(descriptor.url).toMatch(/^https:\/\/.+\/mcp$/);
    expect(descriptor.transport).toBe('streamable-http');
    expect(descriptor.authentication).toBe('oauth2');
    expect(String(descriptor.note)).toMatch(/gateway/i);
  });

  it('keeps every example free of real tokens', () => {
    for (const name of [...stdioExamples, ...httpExamples, 'codex.stdio.toml', 'codex.http.toml', 'chatgpt.connector.json']) {
      expect(read(name), name).not.toMatch(REAL_TOKEN);
    }
  });
});

describe('client guides', () => {
  const guides: ReadonlyArray<readonly [string, readonly string[]]> = [
    ['claude-code.md', ['claude-code.stdio.json', 'claude-code.http.json']],
    ['claude-desktop.md', ['claude-desktop.stdio.json']],
    ['codex.md', ['codex.stdio.toml', 'codex.http.toml']],
    ['chatgpt.md', ['chatgpt.connector.json']],
    ['antigravity.md', ['antigravity.stdio.json', 'antigravity.http.json']],
    ['cursor.md', ['cursor.stdio.json', 'cursor.http.json']],
    ['windsurf.md', ['windsurf.stdio.json', 'windsurf.http.json']],
  ];

  it.each(guides)('%s exists and points at its example files', (guide, exampleFiles) => {
    const path = join(integrationsDir, guide);

    expect(existsSync(path), `${guide} is missing`).toBe(true);
    const content = readFileSync(path, 'utf8');

    for (const example of exampleFiles) {
      expect(existsSync(join(examplesDir, example)), `${example} is missing`).toBe(true);
      expect(content, `${guide} should reference ${example}`).toContain(example);
    }
  });

  it('documents all seven supported clients from the package README', () => {
    const readme = readFileSync(join(packageRoot, 'README.md'), 'utf8');

    for (const guide of ['claude-code', 'claude-desktop', 'codex', 'chatgpt', 'antigravity', 'cursor', 'windsurf']) {
      expect(readme).toContain(`integrations/${guide}.md`);
    }
  });

  it('does not claim that this server implements OAuth', () => {
    const readme = readFileSync(join(packageRoot, 'README.md'), 'utf8');
    const security = readFileSync(join(packageRoot, 'docs', 'SECURITY_MODEL.md'), 'utf8');

    expect(readme).toMatch(/does not implement OAuth|no OAuth/i);
    expect(security).toMatch(/does not implement OAuth|no OAuth/i);
  });
});
