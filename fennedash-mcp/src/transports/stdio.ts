import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';

import type { AuditLogger } from '../audit/audit-logger.js';
import { AuthCache, type AuthIdentity } from '../auth/context.js';
import type { AppConfig } from '../config/env.js';
import { FennecDashClient } from '../integrations/fennedash-api/client.js';
import { buildMcpServer } from '../server/mcp-server.js';
import type { Logger } from '../utils/logger.js';
import { newRequestId } from '../utils/request-id.js';

export interface StdioServerOptions {
  readonly config: AppConfig;
  readonly logger: Logger;
  readonly audit: AuditLogger;
  readonly client: FennecDashClient;
  readonly authCache: AuthCache;
}

export async function runStdioServer(options: StdioServerOptions): Promise<void> {
  const { config, logger, audit } = options;
  const sessionId = newRequestId('stdio');

  const identity: AuthIdentity = await options.authCache.resolve(
    options.client,
    config.apiToken,
  );

  options.client.setAgentSession?.(sessionId);

  const bundle = buildMcpServer({
    config,
    client: options.client,
    identity,
    audit,
    logger,
    transport: 'stdio',
    sessionId,
  });

  const transport = new StdioServerTransport();

  await audit.log({
    type: 'server_start',
    requestId: sessionId,
    sessionId,
    transport: 'stdio',
    actor: {
      staffId: identity.actor.staffId,
      tokenId: identity.token.id,
      label: identity.token.label,
    },
    details: {
      server: `${config.serverName}/${config.serverVersion}`,
      base_url: config.baseUrl,
      allow_write: config.allowWrite,
      allow_critical: config.allowCritical,
    },
  });

  logger.info('FennecDash MCP server starting on stdio', {
    version: config.serverVersion,
    base_url: config.baseUrl,
    staff: identity.actor.email,
    staff_id: identity.actor.staffId,
    token_risk: identity.token.riskMax,
    tools: bundle.tools.size,
    resources: bundle.resources.size,
    prompts: bundle.prompts.size,
    allow_write: config.allowWrite,
    allow_critical: config.allowCritical,
  });

  await new Promise<void>((resolve, reject) => {
    let settled = false;

    const finish = (error?: unknown): void => {
      if (settled) {
        return;
      }
      settled = true;

      process.off('SIGINT', onSignal);
      process.off('SIGTERM', onSignal);

      if (error) {
        reject(error);
      } else {
        resolve();
      }
    };

    const onSignal = (signal: NodeJS.Signals): void => {
      logger.info('Shutting down after signal', { signal });
      void bundle.server
        .close()
        .then(() => finish())
        .catch((error: unknown) => finish(error));
    };

    transport.onclose = () => finish();
    transport.onerror = (error) => {
      logger.error('Stdio transport error', { error: error.message });
    };

    process.on('SIGINT', onSignal);
    process.on('SIGTERM', onSignal);

    bundle.server.connect(transport).catch((error: unknown) => finish(error));
  });

  await audit.log({
    type: 'server_stop',
    requestId: sessionId,
    sessionId,
    transport: 'stdio',
    actor: {
      staffId: identity.actor.staffId,
      tokenId: identity.token.id,
      label: identity.token.label,
    },
    details: { tool_calls: bundle.meters.calls },
  });

  await audit.close();
  logger.info('FennecDash MCP server stopped', { tool_calls: bundle.meters.calls });
}
