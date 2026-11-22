import { describe, expect, it } from 'vitest';

import { parseConfig } from '../../src/config/env.js';

const minimal = {
  FENNEDASH_BASE_URL: 'https://crm.example.com',
  FENNEDASH_API_TOKEN: 'fdm_0123456789abcdef0123456789abcdef01234567',
} as const;

describe('parseConfig', () => {
  it('applies safe defaults', () => {
    const config = parseConfig({ ...minimal } as NodeJS.ProcessEnv);

    expect(config.baseUrl).toBe('https://crm.example.com');
    expect(config.transport).toBe('stdio');
    expect(config.allowWrite).toBe(false);
    expect(config.allowCritical).toBe(false);
    expect(config.http.host).toBe('127.0.0.1');
    expect(config.http.port).toBe(8765);
    expect(config.requestTimeoutMs).toBe(30_000);
    expect(config.authCacheTtlSeconds).toBe(60);
    expect(config.serverName).toBe('fennedash-mcp');
  });

  it('normalizes a base URL that includes the API path', () => {
    expect(
      parseConfig({ ...minimal, FENNEDASH_BASE_URL: 'https://crm.example.com/api/v1/' } as NodeJS.ProcessEnv)
        .baseUrl,
    ).toBe('https://crm.example.com');
  });

  it('supports http transport without a static token', () => {
    const config = parseConfig({
      FENNEDASH_BASE_URL: 'http://127.0.0.1:8080',
      MCP_TRANSPORT: 'http',
      MCP_HTTP_PORT: '0',
    } as NodeJS.ProcessEnv);

    expect(config.transport).toBe('http');
    expect(config.apiToken).toBe('');
    expect(config.http.port).toBe(0);
  });

  it('requires FENNEDASH_BASE_URL', () => {
    expect(() => parseConfig({ FENNEDASH_API_TOKEN: 'x' } as NodeJS.ProcessEnv)).toThrow(
      /FENNEDASH_BASE_URL/,
    );
  });

  it('rejects an invalid URL', () => {
    expect(() =>
      parseConfig({ ...minimal, FENNEDASH_BASE_URL: 'not-a-url' } as NodeJS.ProcessEnv),
    ).toThrow(/FENNEDASH_BASE_URL/);
  });

  it('requires a token for stdio and explains how to get one', () => {
    expect(() =>
      parseConfig({ FENNEDASH_BASE_URL: 'https://crm.example.com' } as NodeJS.ProcessEnv),
    ).toThrow(/FENNEDASH_API_TOKEN is required for the stdio transport/);
  });

  it('rejects invalid booleans and integers', () => {
    expect(() =>
      parseConfig({ ...minimal, MCP_ALLOW_WRITE: 'maybe' } as NodeJS.ProcessEnv),
    ).toThrow(/expected a boolean/);

    expect(() =>
      parseConfig({ ...minimal, MCP_HTTP_PORT: '70000' } as NodeJS.ProcessEnv),
    ).toThrow(/expected an integer between 0 and 65535/);
  });

  it('parses allowlists and log level', () => {
    const config = parseConfig({
      ...minimal,
      MCP_ALLOWED_HOSTS: 'crm.example.com, mcp.example.com',
      MCP_ALLOWED_ORIGINS: 'https://app.example.com',
      MCP_LOG_LEVEL: 'debug',
    } as NodeJS.ProcessEnv);

    expect(config.http.allowedHosts).toEqual(['crm.example.com', 'mcp.example.com']);
    expect(config.http.allowedOrigins).toEqual(['https://app.example.com']);
    expect(config.logLevel).toBe('debug');
  });
});
