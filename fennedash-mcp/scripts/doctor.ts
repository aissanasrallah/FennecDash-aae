/**
 * FennecDash MCP doctor.
 *
 * Validates configuration, the live FennecDash Agent API, token authentication,
 * discovery, the in-memory MCP handshake and one real tool call. Read-only
 * against FennecDash; it never performs a WRITE or CRITICAL operation.
 */
import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { InMemoryTransport } from '@modelcontextprotocol/sdk/inMemory.js';

import { AuditLogger } from '../src/audit/audit-logger.js';
import { AuthCache } from '../src/auth/context.js';
import { loadConfig, type AppConfig } from '../src/config/env.js';
import { FennecDashClient } from '../src/integrations/fennedash-api/client.js';
import { buildMcpServer } from '../src/server/mcp-server.js';
import { createLogger } from '../src/utils/logger.js';
import { newRequestId } from '../src/utils/request-id.js';

type CheckStatus = 'ok' | 'warn' | 'fail';

interface CheckResult {
  readonly name: string;
  readonly status: CheckStatus;
  readonly detail: string;
}

const results: CheckResult[] = [];

function record(name: string, status: CheckStatus, detail: string): void {
  results.push({ name, status, detail });
  const label = status === 'ok' ? '[ok]  ' : status === 'warn' ? '[warn]' : '[fail]';
  process.stdout.write(`${label} ${name}${detail === '' ? '' : ` - ${detail}`}\n`);
}

function errorMessage(error: unknown): string {
  return error instanceof Error ? error.message : String(error);
}

async function main(): Promise<void> {
  process.stdout.write('FennecDash MCP doctor\n');
  process.stdout.write('=====================\n\n');

  let config: AppConfig;

  try {
    config = loadConfig();
    record('Configuration', 'ok', 'environment variables parsed successfully');
  } catch (error) {
    record('Configuration', 'fail', errorMessage(error));
    finish();
    return;
  }

  const logger = createLogger('error');
  const audit = new AuditLogger(config.auditLogPath, logger);

  process.stdout.write(`Base URL       ${config.baseUrl}\n`);
  process.stdout.write(`Transport      ${config.transport}\n`);
  process.stdout.write(`Write enabled  ${config.allowWrite}\n`);
  process.stdout.write(`Critical       ${config.allowCritical}\n`);
  process.stdout.write(`Audit log      ${config.auditLogPath}\n`);
  process.stdout.write(`Token          ${config.apiToken === '' ? '(none)' : `${config.apiToken.slice(0, 8)}...`}\n\n`);

  if (config.baseUrl.startsWith('http://') && !/^http:\/\/(localhost|127\.0\.0\.1|\[::1\])/i.test(config.baseUrl)) {
    record(
      'Transport security',
      'warn',
      'FENNEDASH_BASE_URL uses plain HTTP to a non-loopback host; the bearer token is sent unencrypted.',
    );
  } else {
    record('Transport security', 'ok', 'base URL protocol is acceptable');
  }

  if (config.transport === 'http' && !['127.0.0.1', '::1', 'localhost'].includes(config.http.host)) {
    if (config.http.allowedHosts.length === 0 || config.http.allowedOrigins.length === 0) {
      record(
        'HTTP exposure',
        'warn',
        'the HTTP transport is bound beyond loopback without MCP_ALLOWED_HOSTS and MCP_ALLOWED_ORIGINS.',
      );
    } else {
      record('HTTP exposure', 'ok', 'host and origin allowlists are configured');
    }
  } else {
    record('HTTP exposure', 'ok', 'HTTP transport is loopback-only or unused');
  }

  if (config.transport === 'stdio' && config.apiToken === '') {
    record('Token', 'fail', 'FENNEDASH_API_TOKEN is required for stdio');
    finish();
    return;
  }

  const client = new FennecDashClient({
    baseUrl: config.baseUrl,
    token: config.apiToken,
    timeoutMs: config.requestTimeoutMs,
    userAgent: `${config.serverName}/${config.serverVersion}`,
  });

  const startedAt = Date.now();

  try {
    const ping = await client.ping();
    record('Backend reachable', 'ok', `${config.baseUrl} answered in ${Date.now() - startedAt} ms`);
    record('Agent API version', 'ok', String(ping.data.api_version));
  } catch (error) {
    record('Backend reachable', 'fail', errorMessage(error));
    finish();
    return;
  }

  let staffLabel = '';

  try {
    const me = await client.me();
    staffLabel = `${me.data.staff.firstname} ${me.data.staff.lastname} <${me.data.staff.email}>`.trim();
    record(
      'Authentication',
      'ok',
      `token "${me.data.token.label}" bound to staff ${me.data.staff.id} (${staffLabel}), risk ${me.data.token.risk_max}`,
    );
  } catch (error) {
    record('Authentication', 'fail', errorMessage(error));
    finish();
    return;
  }

  try {
    const capabilities = await client.get<Record<string, unknown>>('system_capabilities');
    const resources = Array.isArray(capabilities.data.resources)
      ? capabilities.data.resources.length
      : 0;
    record('Discovery', 'ok', `${resources} documented resources`);
  } catch (error) {
    record('Discovery', 'fail', errorMessage(error));
  }

  const authCache = new AuthCache(config.authCacheTtlSeconds);

  try {
    const identity = await authCache.resolve(client, config.apiToken);
    const sessionId = newRequestId('doctor');
    const bundle = buildMcpServer({
      config,
      client,
      identity,
      audit,
      logger,
      transport: 'stdio',
      sessionId,
    });

    const [clientTransport, serverTransport] = InMemoryTransport.createLinkedPair();
    const mcpClient = new Client({ name: 'fennedash-mcp-doctor', version: '1.0.0' });

    await Promise.all([
      bundle.server.connect(serverTransport),
      mcpClient.connect(clientTransport),
    ]);

    try {
      const serverVersion = mcpClient.getServerVersion();
      record(
        'MCP handshake',
        'ok',
        `connected to ${serverVersion?.name ?? 'unknown'} ${serverVersion?.version ?? ''}`.trim(),
      );

      const tools = await mcpClient.listTools();
      const invalidNames = tools.tools
        .map((tool) => tool.name)
        .filter((name) => name !== 'fennedash_discover' && !/^fennedash_[a-z0-9]+(?:_[a-z0-9]+)+$/.test(name));

      if (invalidNames.length > 0) {
        record('Tool discovery', 'fail', `invalid tool names: ${invalidNames.join(', ')}`);
      } else {
        record(
          'Tool discovery',
          'ok',
          `${tools.tools.length} tools, all names follow fennedash_<module>_<action>`,
        );
      }

      const resources = await mcpClient.listResources();
      const prompts = await mcpClient.listPrompts();
      record(
        'Resource discovery',
        'ok',
        `${resources.resources.length} resources (including template instances)`,
      );
      record('Prompt discovery', 'ok', `${prompts.prompts.length} prompts`);

      const status = await mcpClient.callTool({
        name: 'fennedash_system_get_status',
        arguments: {},
      });

      if (status.isError === true) {
        record('MCP tool call', 'fail', 'fennedash_system_get_status returned an error');
      } else {
        record('MCP tool call', 'ok', 'fennedash_system_get_status returned live data');
      }

      const projects = await mcpClient.callTool({
        name: 'fennedash_projects_list',
        arguments: { per_page: 1 },
      });

      if (projects.isError === true) {
        const payload = projects.structuredContent as { error?: { message?: string } } | undefined;
        record('Data access', 'fail', payload?.error?.message ?? 'fennedash_projects_list failed');
      } else {
        const payload = projects.structuredContent as { data?: unknown[] } | undefined;
        record('Data access', 'ok', `projects list returned ${payload?.data?.length ?? 0} row(s)`);
      }
    } finally {
      await mcpClient.close().catch(() => undefined);
      await bundle.server.close().catch(() => undefined);
    }
  } catch (error) {
    record('MCP handshake', 'fail', errorMessage(error));
  }

  try {
    await audit.log({
      type: 'server_start',
      requestId: newRequestId('doctor'),
      sessionId: 'doctor',
      transport: config.transport,
      outcome: 'ok',
      details: { check: 'audit_write' },
    });
    await audit.close();

    if (audit.enabled) {
      record('Audit log', 'ok', `writable at ${config.auditLogPath}`);
    } else {
      record('Audit log', 'warn', 'disabled (MCP_AUDIT_LOG is empty)');
    }
  } catch (error) {
    record('Audit log', 'fail', errorMessage(error));
  }

  finish();
}

function finish(): void {
  const failures = results.filter((entry) => entry.status === 'fail').length;
  const warnings = results.filter((entry) => entry.status === 'warn').length;

  process.stdout.write(
    `\n${results.length - failures - warnings} passed, ${warnings} warning(s), ${failures} failure(s)\n`,
  );

  if (failures > 0) {
    process.stdout.write('Doctor result: FAIL\n');
    process.exitCode = 1;
    return;
  }

  process.stdout.write('Doctor result: PASS\n');
}

main().catch((error: unknown) => {
  process.stderr.write(`doctor: ${errorMessage(error)}\n`);
  process.exitCode = 1;
});
