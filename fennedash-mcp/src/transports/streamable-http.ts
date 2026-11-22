import { randomUUID } from 'node:crypto';
import { createServer, type IncomingMessage, type Server, type ServerResponse } from 'node:http';
import { isIP } from 'node:net';

import { StreamableHTTPServerTransport } from '@modelcontextprotocol/sdk/server/streamableHttp.js';
import { isInitializeRequest } from '@modelcontextprotocol/sdk/types.js';

import type { AuditLogger } from '../audit/audit-logger.js';
import { AttemptLimiter, AuthCache, hashToken, type AuthIdentity } from '../auth/context.js';
import type { AppConfig } from '../config/env.js';
import { FennecDashClient } from '../integrations/fennedash-api/client.js';
import type { McpServerBundle } from '../server/mcp-server.js';
import { buildMcpServer } from '../server/mcp-server.js';
import { normalizeError } from '../security/errors.js';
import type { Logger } from '../utils/logger.js';
import { newRequestId } from '../utils/request-id.js';

const SESSION_HEADER = 'mcp-session-id';

interface HttpSession {
  id: string;
  readonly tokenHash: string;
  readonly identity: AuthIdentity;
  readonly bundle: McpServerBundle;
  readonly transport: StreamableHTTPServerTransport;
  readonly createdAt: number;
  lastSeenAt: number;
}

interface HttpRequestWithAuth extends IncomingMessage {
  auth?: { token: string; clientId: string; scopes: string[] };
}

class HttpError extends Error {
  constructor(
    readonly status: number,
    readonly rpcCode: number,
    message: string,
    readonly headers: Record<string, string> = {},
  ) {
    super(message);
    this.name = 'HttpError';
  }
}

export interface HttpServerOptions {
  readonly config: AppConfig;
  readonly logger: Logger;
  readonly audit: AuditLogger;
  readonly authCache: AuthCache;
}

export interface HttpServerHandle {
  readonly port: number;
  readonly host: string;
  close(): Promise<void>;
}

function stripPort(host: string): string {
  if (host.startsWith('[')) {
    const end = host.indexOf(']');
    return end === -1 ? host : host.slice(0, end + 1);
  }

  const colon = host.lastIndexOf(':');
  return colon === -1 ? host : host.slice(0, colon);
}

function isLoopbackHost(host: string): boolean {
  return ['127.0.0.1', '::1', 'localhost'].includes(host);
}

function hostHeaderAllowed(host: string | undefined, config: AppConfig): boolean {
  if (!host) {
    return true;
  }

  const allowed = config.http.allowedHosts;

  if (allowed.length > 0) {
    return allowed.some((entry) => entry === host || entry === stripPort(host));
  }

  if (!isLoopbackHost(config.http.host)) {
    return true;
  }

  return ['localhost', '127.0.0.1', '::1', '[::1]'].includes(stripPort(host));
}

function originAllowed(req: IncomingMessage, config: AppConfig): boolean {
  const origin = req.headers.origin;

  if (!origin) {
    return true;
  }

  const allowed = config.http.allowedOrigins;

  if (allowed.includes('*') || allowed.includes(origin)) {
    return true;
  }

  if (allowed.length > 0) {
    return false;
  }

  // Nothing configured: accept only same-origin browser requests. Comparing
  // against the Host header also covers reverse proxies that rewrite it.
  try {
    const parsed = new URL(origin);
    return req.headers.host !== undefined && parsed.host === req.headers.host;
  } catch {
    return false;
  }
}

function setCommonHeaders(res: ServerResponse): void {
  res.setHeader('X-Content-Type-Options', 'nosniff');
  res.setHeader('X-Frame-Options', 'DENY');
  res.setHeader('Referrer-Policy', 'no-referrer');
  res.setHeader('Cache-Control', 'no-store');
}

function setCorsHeaders(req: IncomingMessage, res: ServerResponse, config: AppConfig): void {
  const origin = req.headers.origin;

  if (!origin) {
    return;
  }

  if (config.http.allowedOrigins.includes('*')) {
    res.setHeader('Access-Control-Allow-Origin', '*');
  } else if (config.http.allowedOrigins.includes(origin)) {
    res.setHeader('Access-Control-Allow-Origin', origin);
    res.setHeader('Vary', 'Origin');
  }

  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS');
  res.setHeader(
    'Access-Control-Allow-Headers',
    'Authorization, Content-Type, Mcp-Session-Id, MCP-Protocol-Version, Last-Event-ID',
  );
  res.setHeader('Access-Control-Expose-Headers', 'Mcp-Session-Id');
  res.setHeader('Access-Control-Max-Age', '600');
}

function sendJson(res: ServerResponse, status: number, payload: unknown): void {
  const body = JSON.stringify(payload);
  res.writeHead(status, {
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Length': Buffer.byteLength(body),
  });
  res.end(body);
}

function sendRpcError(
  res: ServerResponse,
  status: number,
  code: number,
  message: string,
  headers: Record<string, string> = {},
): void {
  if (res.headersSent) {
    res.destroy();
    return;
  }

  for (const [key, value] of Object.entries(headers)) {
    res.setHeader(key, value);
  }

  sendJson(res, status, {
    jsonrpc: '2.0',
    error: { code, message },
    id: null,
  });
}

function readJsonBody(req: IncomingMessage, maxBytes: number): Promise<unknown> {
  return new Promise((resolve, reject) => {
    const chunks: Buffer[] = [];
    let size = 0;

    const fail = (error: HttpError): void => {
      req.destroy();
      reject(error);
    };

    req.on('data', (chunk: Buffer) => {
      size += chunk.length;

      if (size > maxBytes) {
        fail(new HttpError(413, -32600, `Request body exceeds the ${maxBytes} byte limit.`));
        return;
      }

      chunks.push(chunk);
    });

    req.on('error', (error) => {
      reject(new HttpError(400, -32700, `Could not read the request body: ${error.message}`));
    });

    req.on('end', () => {
      const raw = Buffer.concat(chunks).toString('utf8').trim();

      if (raw === '') {
        reject(new HttpError(400, -32700, 'Request body is empty.'));
        return;
      }

      try {
        resolve(JSON.parse(raw));
      } catch {
        reject(new HttpError(400, -32700, 'Request body is not valid JSON.'));
      }
    });
  });
}

function extractBearerToken(req: IncomingMessage): string | null {
  const header = req.headers.authorization;

  if (!header) {
    return null;
  }

  const match = /^Bearer\s+(.+)$/i.exec(header.trim());
  return match?.[1]?.trim() || null;
}

function clientAddress(req: IncomingMessage, trustProxy: boolean): string {
  if (trustProxy) {
    const forwardedFor = req.headers['x-forwarded-for'];

    if (typeof forwardedFor === 'string' && forwardedFor.trim() !== '') {
      const first = forwardedFor.split(',')[0]?.trim();

      if (first && isIP(first)) {
        return first;
      }
    }

    const forwarded = req.headers['x-forwarded-host'];

    if (typeof forwarded === 'string' && forwarded.trim() !== '') {
      return forwarded.split(',')[0]?.trim() ?? 'unknown';
    }
  }

  return req.socket.remoteAddress ?? 'unknown';
}

export async function startHttpServer(options: HttpServerOptions): Promise<HttpServerHandle> {
  const { config, logger, audit } = options;
  const sessions = new Map<string, HttpSession>();
  const authFailures = new AttemptLimiter(20, 60_000);
  const startedAt = Date.now();

  const createClient = (token: string): FennecDashClient =>
    new FennecDashClient({
      baseUrl: config.baseUrl,
      token,
      timeoutMs: config.requestTimeoutMs,
      userAgent: `${config.serverName}/${config.serverVersion}`,
      agentName: config.serverName,
    });

  let readyzCache: { expiresAt: number; payload: unknown } | null = null;
  let shuttingDown = false;

  async function getReadyzPayload(): Promise<unknown> {
    const now = Date.now();

    if (readyzCache && now < readyzCache.expiresAt) {
      return readyzCache.payload;
    }

    const payload = await buildReadyzPayload();
    readyzCache = { expiresAt: now + config.http.readyCacheMs, payload };
    return payload;
  }

  async function buildReadyzPayload(): Promise<unknown> {
    const readinessClient = createClient(config.apiToken);

    try {
      const ping = await readinessClient.ping({ requestId: newRequestId('readyz') });

      return {
        status: 'ok',
        name: config.serverName,
        version: config.serverVersion,
        transport: 'http',
        sessions: sessions.size,
        max_sessions: config.http.maxSessions,
        uptime_seconds: Math.floor((Date.now() - startedAt) / 1000),
        backend: {
          ok: true,
          api_version: ping.data.api_version,
          server_time: ping.data.server_time,
        },
      };
    } catch (error) {
      return {
        status: 'degraded',
        name: config.serverName,
        version: config.serverVersion,
        transport: 'http',
        sessions: sessions.size,
        max_sessions: config.http.maxSessions,
        uptime_seconds: Math.floor((Date.now() - startedAt) / 1000),
        backend: {
          ok: false,
          error: normalizeError(error).code,
        },
      };
    }
  }

  function enforceSessionCap(): void {
    if (config.http.maxSessions <= 0 || sessions.size < config.http.maxSessions) {
      return;
    }

    // Evict the least recently active session so the oldest client is dropped
    // instead of refusing every new connection.
    let oldest: HttpSession | undefined;

    for (const session of sessions.values()) {
      if (!oldest || session.lastSeenAt < oldest.lastSeenAt) {
        oldest = session;
      }
    }

    if (oldest) {
      void closeSession(oldest, 'session_cap_evicted');
    }
  }

  async function authenticate(
    req: IncomingMessage,
  ): Promise<{ token: string; tokenHash: string; identity: AuthIdentity; client: FennecDashClient }> {
    const token = extractBearerToken(req);

    if (!token) {
      throw new HttpError(401, -32001, 'Missing bearer token.', {
        'WWW-Authenticate': 'Bearer realm="fennedash-mcp"',
      });
    }

    if (authFailures.isBlocked(clientAddress(req, config.trustProxy))) {
      throw new HttpError(429, -32000, 'Too many failed authentication attempts. Retry later.');
    }

    const client = createClient(token);

    try {
      const identity = await options.authCache.resolve(client, token);
      authFailures.reset(clientAddress(req, config.trustProxy));
      return { token, tokenHash: hashToken(token), identity, client };
    } catch (error) {
      const normalized = normalizeError(error);
      authFailures.recordFailure(clientAddress(req, config.trustProxy));

      await audit.log({
        type: 'auth_failure',
        requestId: newRequestId('http'),
        sessionId: 'none',
        transport: 'http',
        outcome: 'denied',
        errorCode: normalized.code,
        details: { ip: clientAddress(req, config.trustProxy) },
      });

      if (normalized.code === 'backend_unreachable' || normalized.code === 'backend_timeout') {
        throw new HttpError(502, -32000, normalized.message);
      }

      throw new HttpError(401, -32001, 'Invalid, expired or revoked FennecDash API token.', {
        'WWW-Authenticate': 'Bearer realm="fennedash-mcp", error="invalid_token"',
      });
    }
  }

  async function closeSession(session: HttpSession, reason: string): Promise<void> {
    sessions.delete(session.id);

    await audit.log({
      type: 'session_close',
      requestId: newRequestId('http'),
      sessionId: session.id,
      transport: 'http',
      actor: {
        staffId: session.identity.actor.staffId,
        tokenId: session.identity.token.id,
        label: session.identity.token.label,
      },
      details: { reason, tool_calls: session.bundle.meters.calls },
    });

    try {
      await session.bundle.server.close();
    } catch (error) {
      logger.warn('Failed to close HTTP session cleanly', {
        session_id: session.id,
        error: error instanceof Error ? error.message : String(error),
      });
    }
  }

  async function handleInitialize(
    req: HttpRequestWithAuth,
    res: ServerResponse,
    body: unknown,
    auth: { tokenHash: string; identity: AuthIdentity; client: FennecDashClient },
  ): Promise<void> {
    const sessionId = randomUUID();
    const createdAt = Date.now();

    enforceSessionCap();

    const transport = new StreamableHTTPServerTransport({
      sessionIdGenerator: () => randomUUID(),
      enableJsonResponse: true,
      onsessioninitialized: (initializedId) => {
        session.id = initializedId;
        sessions.set(initializedId, session);
      },
      onsessionclosed: (closedId) => {
        const closed = sessions.get(closedId);

        if (!closed) {
          return;
        }

        sessions.delete(closedId);

        // Defer so the DELETE response can finish before the server and its
        // transport are torn down.
        setImmediate(() => {
          void audit.log({
            type: 'session_close',
            requestId: newRequestId('http'),
            sessionId: closedId,
            transport: 'http',
            actor: {
              staffId: closed.identity.actor.staffId,
              tokenId: closed.identity.token.id,
              label: closed.identity.token.label,
            },
            details: { reason: 'client_delete', tool_calls: closed.bundle.meters.calls },
          });

          void closed.bundle.server.close().catch((error: unknown) => {
            logger.warn('Failed to close deleted HTTP session cleanly', {
              session_id: closedId,
              error: error instanceof Error ? error.message : String(error),
            });
          });
        });
      },
    });

    const bundle = buildMcpServer({
      config,
      client: auth.client,
      identity: auth.identity,
      audit,
      logger,
      transport: 'http',
      sessionId,
    });

    const session: HttpSession = {
      id: sessionId,
      tokenHash: auth.tokenHash,
      identity: auth.identity,
      bundle,
      transport,
      createdAt,
      lastSeenAt: createdAt,
    };

    await bundle.server.connect(transport);

    await audit.log({
      type: 'session_open',
      requestId: sessionId,
      sessionId,
      transport: 'http',
      actor: {
        staffId: auth.identity.actor.staffId,
        tokenId: auth.identity.token.id,
        label: auth.identity.token.label,
      },
      details: { ip: clientAddress(req, config.trustProxy) },
    });

    await transport.handleRequest(req, res, body);

    // A client may disconnect before initialization completes; never keep an
    // unreachable session in memory.
    if (!sessions.has(session.id)) {
      await bundle.server.close().catch(() => undefined);
    }

    logger.info('HTTP session opened', {
      session_id: session.id,
      staff_id: auth.identity.actor.staffId,
      token_risk: auth.identity.token.riskMax,
    });
  }

  async function handleRequest(req: HttpRequestWithAuth, res: ServerResponse): Promise<void> {
    setCommonHeaders(res);
    setCorsHeaders(req, res, config);

    if (!hostHeaderAllowed(req.headers.host, config)) {
      sendRpcError(res, 421, -32000, 'Host header is not allowed by MCP_ALLOWED_HOSTS.');
      return;
    }

    if (!originAllowed(req, config)) {
      sendRpcError(res, 403, -32000, 'Origin is not allowed by MCP_ALLOWED_ORIGINS.');
      return;
    }

    const url = new URL(req.url ?? '/', `http://${req.headers.host ?? 'localhost'}`);
    const path = url.pathname.replace(/\/+$/, '') || '/';

    if (path === '/healthz') {
      if (req.method !== 'GET' && req.method !== 'HEAD') {
        sendJson(res, 405, { error: 'method_not_allowed' });
        return;
      }

      sendJson(res, 200, {
        status: 'ok',
        name: config.serverName,
        version: config.serverVersion,
        transport: 'http',
        sessions: sessions.size,
        uptime_seconds: Math.floor((Date.now() - startedAt) / 1000),
      });
      return;
    }

    if (path === '/readyz') {
      if (req.method !== 'GET' && req.method !== 'HEAD') {
        sendJson(res, 405, { error: 'method_not_allowed' });
        return;
      }

      sendJson(res, 200, getReadyzPayload());
      return;
    }

    if (path !== '/mcp') {
      sendJson(res, 404, {
        error: 'not_found',
        message: 'Use the MCP endpoint at /mcp, the health probe at /healthz or the readiness probe at /readyz.',
      });
      return;
    }

    if (req.method === 'OPTIONS') {
      res.writeHead(204);
      res.end();
      return;
    }

    if (req.method !== 'POST' && req.method !== 'GET' && req.method !== 'DELETE') {
      sendRpcError(res, 405, -32000, 'Method not allowed. Use POST, GET, DELETE or OPTIONS.', {
        Allow: 'GET, POST, DELETE, OPTIONS',
      });
      return;
    }

    const sessionIdHeader = req.headers[SESSION_HEADER];
    const sessionId = Array.isArray(sessionIdHeader) ? sessionIdHeader[0] : sessionIdHeader;

    if (req.method === 'POST' && !sessionId) {
      // Authenticate before parsing the payload so unauthenticated callers
      // cannot probe protocol handling, and so a missing token is a clean 401.
      const auth = await authenticate(req);
      const body = await readJsonBody(req, config.http.maxBodyBytes);
      const isInitialize = Array.isArray(body)
        ? body.some((entry) => isInitializeRequest(entry))
        : isInitializeRequest(body);

      if (!isInitialize) {
        sendRpcError(
          res,
          400,
          -32000,
          'Bad Request: the first request must be an initialize request without a session id.',
        );
        return;
      }

      await handleInitialize(req, res, body, auth);
      return;
    }

    if (!sessionId) {
      sendRpcError(res, 400, -32000, `Missing ${SESSION_HEADER} header.`);
      return;
    }

    const session = sessions.get(sessionId);

    if (!session) {
      sendRpcError(res, 404, -32001, 'Session not found or expired. Send a new initialize request.', {
        [SESSION_HEADER]: sessionId,
      });
      return;
    }

    const auth = await authenticate(req);

    if (auth.tokenHash !== session.tokenHash) {
      sendRpcError(res, 403, -32000, 'This session belongs to a different API token.', {
        [SESSION_HEADER]: sessionId,
      });
      return;
    }

    session.lastSeenAt = Date.now();

    if (req.method === 'POST') {
      const body = await readJsonBody(req, config.http.maxBodyBytes);
      await session.transport.handleRequest(req, res, body);
      return;
    }

    await session.transport.handleRequest(req, res);
  }

  const httpServer: Server = createServer((req, res) => {
    const request = req as HttpRequestWithAuth;

    // Be permissive with clients that omit Accept while still letting the SDK
    // validate the media types it actually needs.
    if (!request.headers.accept) {
      request.headers.accept = 'application/json, text/event-stream';
    } else if (request.method === 'GET' && !request.headers.accept.includes('text/event-stream')) {
      request.headers.accept = `${request.headers.accept}, text/event-stream`;
    }

    handleRequest(request, res).catch((error: unknown) => {
      const normalized =
        error instanceof HttpError
          ? error
          : new HttpError(
              500,
              -32603,
              error instanceof Error ? error.message : 'Internal server error.',
            );

      const log = normalized.status >= 500 ? logger.error.bind(logger) : logger.warn.bind(logger);

      log('HTTP request failed', {
        method: request.method,
        path: request.url,
        status: normalized.status,
        error: error instanceof Error ? error.message : String(error),
      });

      if (normalized instanceof HttpError) {
        sendRpcError(res, normalized.status, normalized.rpcCode, normalized.message, normalized.headers);
      }
    });
  });

  httpServer.headersTimeout = 30_000;
  httpServer.requestTimeout = 60_000;
  httpServer.keepAliveTimeout = 65_000;

  const cleanupTimer = setInterval(() => {
    const now = Date.now();
    const ttlMs = config.sessionTtlMinutes * 60_000;

    for (const session of [...sessions.values()]) {
      if (now - session.lastSeenAt > ttlMs) {
        void closeSession(session, 'ttl_expired');
      }
    }

    authFailures.prune();
    options.authCache.prune();
  }, 60_000);

  cleanupTimer.unref();

  await new Promise<void>((resolve, reject) => {
    httpServer.once('error', reject);
    httpServer.listen(config.http.port, config.http.host, () => {
      httpServer.off('error', reject);
      resolve();
    });
  });

  const address = httpServer.address();
  const port = typeof address === 'object' && address !== null ? address.port : config.http.port;

  logger.info('FennecDash MCP server listening', {
    transport: 'http',
    host: config.http.host,
    port,
    endpoint: '/mcp',
    health: '/healthz',
    allow_write: config.allowWrite,
    allow_critical: config.allowCritical,
  });

  const shutdownReason = new Promise<string>((resolve) => {
    const onSignal = (signal: NodeJS.Signals): void => resolve(signal);
    process.once('SIGINT', onSignal);
    process.once('SIGTERM', onSignal);
  });

  let closed = false;

  const close = async (): Promise<void> => {
    if (closed) {
      return;
    }
    closed = true;
    clearInterval(cleanupTimer);
    shuttingDown = true;

    const sessionClose = (async () => {
      for (const session of [...sessions.values()]) {
        await closeSession(session, 'server_shutdown');
      }
    })();

    await Promise.race([
      sessionClose,
      new Promise<void>((resolve) => setTimeout(resolve, config.http.shutdownTimeoutMs)),
    ]);

    await new Promise<void>((resolve) => {
      httpServer.close(() => resolve());
    });

    await audit.close();
  };

  void shutdownReason.then((reason) => {
    logger.info('Shutting down HTTP server', { reason });
    void close().then(() => {
      process.exitCode = 0;
    });
  });

  return { port, host: config.http.host, close };
}
