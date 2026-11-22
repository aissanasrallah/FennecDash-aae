import { readFileSync } from 'node:fs';
import { join } from 'node:path';

import { loadConfig, packageRoot, type AppConfig, type TransportName } from './config/env.js';
import { AuditLogger } from './audit/audit-logger.js';
import { AuthCache } from './auth/context.js';
import { FennecDashClient } from './integrations/fennedash-api/client.js';
import { startHttpServer } from './transports/streamable-http.js';
import { runStdioServer } from './transports/stdio.js';
import { createLogger } from './utils/logger.js';

interface CliOptions {
  readonly help: boolean;
  readonly version: boolean;
  readonly transport?: TransportName;
}

function usage(): string {
  return [
    'FennecDash MCP server',
    '',
    'Usage:',
    '  fennedash-mcp [--transport stdio|http]',
    '  fennedash-mcp --help',
    '  fennedash-mcp --version',
    '',
    'Transports:',
    '  stdio   JSON-RPC over stdin/stdout. Default for desktop MCP clients.',
    '  http    Streamable HTTP at POST/GET/DELETE /mcp plus GET /healthz.',
    '',
    'Configuration is read from the process environment and from .env next to',
    'package.json. Required:',
    '  FENNEDASH_BASE_URL     e.g. https://crm.example.com',
    '  FENNEDASH_API_TOKEN    fdm_... (stdio; HTTP clients send their own bearer token)',
    '',
    'Common options:',
    '  MCP_ALLOW_WRITE=true       enable WRITE tools',
    '  MCP_ALLOW_CRITICAL=true    enable CRITICAL tools (still needs approval)',
    '  MCP_HTTP_HOST, MCP_HTTP_PORT, MCP_ALLOWED_HOSTS, MCP_ALLOWED_ORIGINS',
    '  MCP_AUDIT_LOG, MCP_LOG_LEVEL',
    '',
    'Run `npm run mcp:doctor` to validate the installation against a live FennecDash.',
  ].join('\n');
}

function parseArgs(argv: readonly string[]): CliOptions {
  let help = false;
  let version = false;
  let transport: TransportName | undefined;

  for (let index = 0; index < argv.length; index += 1) {
    const argument = argv[index];

    if (argument === '--help' || argument === '-h') {
      help = true;
      continue;
    }

    if (argument === '--version' || argument === '-v') {
      version = true;
      continue;
    }

    if (argument === '--transport') {
      const value = argv[index + 1];

      if (value !== 'stdio' && value !== 'http') {
        throw new Error('--transport expects "stdio" or "http".');
      }

      transport = value;
      index += 1;
      continue;
    }

    if (argument?.startsWith('--transport=')) {
      const value = argument.slice('--transport='.length);

      if (value !== 'stdio' && value !== 'http') {
        throw new Error('--transport expects "stdio" or "http".');
      }

      transport = value;
      continue;
    }

    throw new Error(`Unknown argument: ${String(argument)}`);
  }

  return { help, version, ...(transport ? { transport } : {}) };
}

function packageVersion(): string {
  try {
    const metadata = JSON.parse(readFileSync(join(packageRoot, 'package.json'), 'utf8')) as {
      version?: string;
    };
    return metadata.version ?? '0.0.0';
  } catch {
    return '0.0.0';
  }
}

async function main(): Promise<void> {
  const options = parseArgs(process.argv.slice(2));

  if (options.help) {
    process.stdout.write(`${usage()}\n`);
    return;
  }

  if (options.version) {
    process.stdout.write(`${packageVersion()}\n`);
    return;
  }

  const loaded = loadConfig();
  const config: AppConfig = options.transport
    ? { ...loaded, transport: options.transport }
    : loaded;

  const logger = createLogger(config.logLevel);
  const audit = new AuditLogger(config.auditLogPath, logger.child('audit'), {
    maxBytes: config.auditMaxBytes,
  });
  const authCache = new AuthCache(config.authCacheTtlSeconds);

  process.on('uncaughtException', (error) => {
    logger.error('Uncaught exception', {
      error: error.message,
      stack: error.stack,
    });
    process.exit(1);
  });

  process.on('unhandledRejection', (reason) => {
    logger.error('Unhandled rejection', {
      error: reason instanceof Error ? reason.message : String(reason),
    });
    process.exit(1);
  });

  if (config.transport === 'stdio') {
    if (config.apiToken === '') {
      throw new Error(
        'FENNEDASH_API_TOKEN is required for the stdio transport. Issue a token with POST /api/setup/token.',
      );
    }

    const client = new FennecDashClient({
      baseUrl: config.baseUrl,
      token: config.apiToken,
      timeoutMs: config.requestTimeoutMs,
      userAgent: `${config.serverName}/${config.serverVersion}`,
      agentName: config.serverName,
    });

    await runStdioServer({ config, logger, audit, client, authCache });
    return;
  }

  await startHttpServer({ config, logger, audit, authCache });
}

main().catch((error: unknown) => {
  // Configuration errors happen before the logger exists.
  const message = error instanceof Error ? error.message : String(error);
  process.stderr.write(`fennedash-mcp: ${message}\n`);
  process.exit(1);
});
