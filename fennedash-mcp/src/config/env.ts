import { existsSync, readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import { config as loadDotenv } from 'dotenv';
import { z } from 'zod';

/**
 * Package root discovery works both for `tsx src/index.ts` and for the
 * compiled `dist/src/index.js`, without relying on the current directory.
 */
function findPackageRoot(start: string): string {
  let directory = start;

  for (let depth = 0; depth < 8; depth += 1) {
    if (existsSync(join(directory, 'package.json'))) {
      return directory;
    }

    const parent = dirname(directory);
    if (parent === directory) {
      break;
    }

    directory = parent;
  }

  return start;
}

export const packageRoot = findPackageRoot(dirname(fileURLToPath(import.meta.url)));

/** Loads .env next to package.json. Existing process env wins. */
export function loadDotEnv(): void {
  loadDotenv({ path: join(packageRoot, '.env'), quiet: true });
}

const booleanish = (fallback: boolean) =>
  z
    .string()
    .optional()
    .transform((value, ctx) => {
      if (value === undefined || value.trim() === '') {
        return fallback;
      }

      const normalized = value.trim().toLowerCase();

      if (['1', 'true', 'yes', 'on'].includes(normalized)) {
        return true;
      }

      if (['0', 'false', 'no', 'off'].includes(normalized)) {
        return false;
      }

      ctx.addIssue({ code: 'custom', message: `expected a boolean, received "${value}"` });
      return z.NEVER;
    });

const integer = (fallback: number, min: number, max: number) =>
  z
    .string()
    .optional()
    .transform((value, ctx) => {
      if (value === undefined || value.trim() === '') {
        return fallback;
      }

      const parsed = Number(value);

      if (!Number.isInteger(parsed) || parsed < min || parsed > max) {
        ctx.addIssue({ code: 'custom', message: `expected an integer between ${min} and ${max}` });
        return z.NEVER;
      }

      return parsed;
    });

const csv = z
  .string()
  .optional()
  .transform((value) =>
    (value ?? '')
      .split(',')
      .map((entry) => entry.trim())
      .filter((entry) => entry !== ''),
  );

export const EnvSchema = z.object({
  FENNEDASH_BASE_URL: z.string().trim().min(1, 'FENNEDASH_BASE_URL is required'),
  FENNEDASH_API_TOKEN: z.string().trim().default(''),

  MCP_ENV: z.enum(['development', 'production']).default('development'),
  MCP_PUBLIC_BASE_URL: z.string().trim().default(''),
  MCP_TRUST_PROXY: booleanish(false),

  MCP_TRANSPORT: z.enum(['stdio', 'http']).default('stdio'),
  MCP_HTTP_HOST: z.string().trim().default('127.0.0.1'),
  MCP_HTTP_PORT: integer(8765, 0, 65535),
  MCP_ALLOWED_ORIGINS: csv,
  MCP_ALLOWED_HOSTS: csv,
  MCP_MAX_BODY_BYTES: integer(4 * 1024 * 1024, 65_536, 16 * 1024 * 1024),
  MCP_MAX_SESSIONS: integer(100, 1, 10_000),
  MCP_READY_CACHE_MS: integer(5_000, 0, 60_000),
  MCP_SHUTDOWN_TIMEOUT_MS: integer(10_000, 1_000, 60_000),

  MCP_ALLOW_WRITE: booleanish(false),
  MCP_ALLOW_CRITICAL: booleanish(false),

  MCP_REQUEST_TIMEOUT_MS: integer(30_000, 1_000, 300_000),
  MCP_MAX_CALLS_PER_SESSION: integer(0, 0, 1_000_000),
  MCP_SESSION_TTL_MINUTES: integer(120, 1, 10_080),

  MCP_AUDIT_LOG: z.string().trim().default('./logs/audit.jsonl'),
  MCP_AUDIT_MAX_BYTES: integer(10 * 1024 * 1024, 0, 1024 * 1024 * 1024),
  MCP_LOG_LEVEL: z.enum(['debug', 'info', 'warn', 'error']).default('info'),
  MCP_AUTH_CACHE_TTL: integer(60, 0, 3_600),
});

export type LogLevel = 'debug' | 'info' | 'warn' | 'error';
export type TransportName = 'stdio' | 'http';
export type EnvironmentName = 'development' | 'production';

export interface AppConfig {
  readonly baseUrl: string;
  readonly apiToken: string;
  readonly environment: EnvironmentName;
  readonly publicBaseUrl: string | null;
  readonly trustProxy: boolean;
  readonly transport: TransportName;
  readonly http: {
    readonly host: string;
    readonly port: number;
    readonly allowedOrigins: readonly string[];
    readonly allowedHosts: readonly string[];
    readonly maxBodyBytes: number;
    readonly maxSessions: number;
    readonly readyCacheMs: number;
    readonly shutdownTimeoutMs: number;
  };
  readonly allowWrite: boolean;
  readonly allowCritical: boolean;
  readonly requestTimeoutMs: number;
  readonly maxCallsPerSession: number;
  readonly sessionTtlMinutes: number;
  readonly auditLogPath: string;
  readonly auditMaxBytes: number;
  readonly logLevel: LogLevel;
  readonly authCacheTtlSeconds: number;
  readonly serverName: string;
  readonly serverVersion: string;
}

interface PackageMetadata {
  name?: string;
  version?: string;
}

function packageMetadata(): PackageMetadata {
  try {
    return JSON.parse(readFileSync(join(packageRoot, 'package.json'), 'utf8')) as PackageMetadata;
  } catch {
    return {};
  }
}

function normalizeBaseUrl(value: string): string {
  const trimmed = value.replace(/\/+$/, '');

  // Strip a trailing `/api` or `/api/v1` so both forms are accepted.
  return trimmed.replace(/\/api(\/v1)?$/i, '');
}

/**
 * Pure parser used by the server, the doctor and the tests.
 */
export function parseConfig(source: NodeJS.ProcessEnv): AppConfig {
  const result = EnvSchema.safeParse(source);

  if (!result.success) {
    const details = result.error.issues
      .map((issue) => `${issue.path.join('.') || '(root)'}: ${issue.message}`)
      .join('; ');

    throw new Error(`Invalid FennecDash MCP configuration: ${details}`);
  }

  const env = result.data;
  const meta = packageMetadata();

  let baseUrl: string;
  try {
    const parsed = new URL(env.FENNEDASH_BASE_URL);
    if (!['http:', 'https:'].includes(parsed.protocol)) {
      throw new Error('protocol must be http or https');
    }
    baseUrl = normalizeBaseUrl(parsed.toString());
  } catch (error) {
    throw new Error(
      `Invalid FENNEDASH_BASE_URL: ${error instanceof Error ? error.message : String(error)}`,
    );
  }

  if (env.MCP_TRANSPORT === 'stdio' && env.FENNEDASH_API_TOKEN === '') {
    throw new Error(
      'FENNEDASH_API_TOKEN is required for the stdio transport. Issue one with POST /api/setup/token.',
    );
  }

  let publicBaseUrl: string | null = null;

  if (env.MCP_PUBLIC_BASE_URL !== '') {
    try {
      const parsed = new URL(env.MCP_PUBLIC_BASE_URL);
      if (!['http:', 'https:'].includes(parsed.protocol)) {
        throw new Error('protocol must be http or https');
      }
      publicBaseUrl = parsed.toString().replace(/\/+$/, '');
    } catch (error) {
      throw new Error(
        `Invalid MCP_PUBLIC_BASE_URL: ${error instanceof Error ? error.message : String(error)}`,
      );
    }
  }

  const config: AppConfig = Object.freeze({
    baseUrl,
    apiToken: env.FENNEDASH_API_TOKEN,
    environment: env.MCP_ENV,
    publicBaseUrl,
    trustProxy: env.MCP_TRUST_PROXY,
    transport: env.MCP_TRANSPORT,
    http: Object.freeze({
      host: env.MCP_HTTP_HOST,
      port: env.MCP_HTTP_PORT,
      allowedOrigins: Object.freeze([...env.MCP_ALLOWED_ORIGINS]),
      allowedHosts: Object.freeze([...env.MCP_ALLOWED_HOSTS]),
      maxBodyBytes: env.MCP_MAX_BODY_BYTES,
      maxSessions: env.MCP_MAX_SESSIONS,
      readyCacheMs: env.MCP_READY_CACHE_MS,
      shutdownTimeoutMs: env.MCP_SHUTDOWN_TIMEOUT_MS,
    }),
    allowWrite: env.MCP_ALLOW_WRITE,
    allowCritical: env.MCP_ALLOW_CRITICAL,
    requestTimeoutMs: env.MCP_REQUEST_TIMEOUT_MS,
    maxCallsPerSession: env.MCP_MAX_CALLS_PER_SESSION,
    sessionTtlMinutes: env.MCP_SESSION_TTL_MINUTES,
    auditLogPath: env.MCP_AUDIT_LOG,
    auditMaxBytes: env.MCP_AUDIT_MAX_BYTES,
    logLevel: env.MCP_LOG_LEVEL,
    authCacheTtlSeconds: env.MCP_AUTH_CACHE_TTL,
    serverName: 'fennedash-mcp',
    serverVersion: meta.version ?? '1.0.0',
  });

  if (config.environment === 'production') {
    validateProductionConfig(config);
  }

  return config;
}

/**
 * Fail closed before the server starts listening when a production deployment
 * is misconfigured. Development stays permissive but keeps the same checks
 * available through the doctor.
 */
function validateProductionConfig(config: AppConfig): void {
  const errors: string[] = [];

  if (new URL(config.baseUrl).protocol !== 'https:') {
    errors.push('FENNEDASH_BASE_URL must use https in production.');
  }

  if (config.allowCritical && !config.allowWrite) {
    errors.push('MCP_ALLOW_CRITICAL=true also requires MCP_ALLOW_WRITE=true.');
  }

  if (config.auditLogPath.trim() === '') {
    errors.push('MCP_AUDIT_LOG must point to a writable file in production.');
  }

  if (config.transport === 'http') {
    if (!config.publicBaseUrl) {
      errors.push('MCP_PUBLIC_BASE_URL is required for the production HTTP transport (https://...).');
    } else if (new URL(config.publicBaseUrl).protocol !== 'https:') {
      errors.push('MCP_PUBLIC_BASE_URL must use https in production.');
    }

    if (config.http.allowedHosts.length === 0) {
      errors.push('MCP_ALLOWED_HOSTS must list the public host name in production.');
    }

    if (config.http.allowedOrigins.includes('*')) {
      errors.push('MCP_ALLOWED_ORIGINS must not contain "*" in production.');
    }

    if (config.http.allowedOrigins.length === 0) {
      errors.push('MCP_ALLOWED_ORIGINS must list the trusted browser origins in production.');
    }
  }

  if (errors.length > 0) {
    throw new Error(`Production configuration is invalid: ${errors.join(' ')}`);
  }
}

export function loadConfig(): AppConfig {
  loadDotEnv();
  return parseConfig(process.env);
}
