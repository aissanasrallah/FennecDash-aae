import { parseConfig, type AppConfig } from '../../src/config/env.js';

export const TEST_BASE_URL = 'http://127.0.0.1:1';

export function makeConfig(overrides: Record<string, string | undefined> = {}): AppConfig {
  const env: Record<string, string | undefined> = {
    FENNEDASH_BASE_URL: TEST_BASE_URL,
    FENNEDASH_API_TOKEN: 'test-token-0123456789abcdef0123456789abcdef',
    MCP_TRANSPORT: 'stdio',
    MCP_ALLOW_WRITE: 'true',
    MCP_ALLOW_CRITICAL: 'true',
    MCP_AUDIT_LOG: '',
    MCP_LOG_LEVEL: 'error',
    ...overrides,
  };

  for (const key of Object.keys(env)) {
    if (env[key] === undefined) {
      delete env[key];
    }
  }

  return parseConfig(env as NodeJS.ProcessEnv);
}
