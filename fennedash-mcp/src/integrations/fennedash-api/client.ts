import { randomUUID } from 'node:crypto';

import { FennecApiError, McpToolError, type ErrorCode } from '../../security/errors.js';
import { redactString } from '../../security/redaction.js';

export type QueryPrimitive = string | number | boolean | null | undefined;
export type QueryValue = QueryPrimitive | readonly QueryPrimitive[] | Record<string, QueryPrimitive>;
export type QueryParams = Record<string, QueryValue>;

export interface ApiCallOptions {
  readonly signal?: AbortSignal;
  readonly requestId?: string;
  readonly idempotencyKey?: string;
}

export interface ApiResponse<T> {
  readonly data: T;
  readonly meta?: Record<string, unknown>;
  readonly requestId?: string;
  readonly idempotentReplayed?: boolean;
}

export interface PingPayload {
  readonly pong: true;
  readonly api_version: string;
  readonly server_time: string;
}

export type TokenRisk = 'read' | 'write' | 'critical';

export interface MePayload {
  readonly staff: {
    readonly id: number;
    readonly email: string;
    readonly firstname: string;
    readonly lastname: string;
    readonly admin: boolean;
    readonly role_id: number | null;
    readonly language: string | null;
  };
  readonly token: {
    readonly id: number;
    readonly label: string;
    readonly prefix: string;
    readonly risk_max: TokenRisk;
    readonly scopes: string[] | null;
    readonly created_at: string;
    readonly expires_at: string | null;
    readonly last_used_at: string | null;
    readonly request_count: number;
  };
}

/**
 * The tools only depend on this interface, which keeps them testable without
 * a live FennecDash instance while production always uses the real client.
 */
export interface FennecDashApi {
  get<T = unknown>(
    path: string,
    query?: QueryParams,
    options?: ApiCallOptions,
  ): Promise<ApiResponse<T>>;

  post<T = unknown>(
    path: string,
    body?: unknown,
    options?: ApiCallOptions,
  ): Promise<ApiResponse<T>>;

  ping(options?: ApiCallOptions): Promise<ApiResponse<PingPayload>>;

  me(options?: ApiCallOptions): Promise<ApiResponse<MePayload>>;

  /** Bind the MCP session id so the Agent API audit trail can correlate calls. */
  setAgentSession?(sessionId: string): void;

  /** Record the MCP client name/version for audit and backend attribution. */
  setClientInfo?(name: string, version?: string): void;
}

export interface FennecDashClientOptions {
  readonly baseUrl: string;
  readonly token: string;
  readonly timeoutMs: number;
  readonly userAgent?: string;
  readonly agentName?: string;
  readonly agentSession?: string;
  readonly fetchImpl?: typeof fetch;
}

interface ApiEnvelope {
  ok?: unknown;
  data?: unknown;
  meta?: unknown;
  request_id?: unknown;
  error?: {
    code?: unknown;
    message?: unknown;
    details?: unknown;
  };
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function appendQuery(url: URL, query: QueryParams | undefined): void {
  if (!query) {
    return;
  }

  for (const [key, value] of Object.entries(query)) {
    if (value === undefined || value === null || value === '') {
      continue;
    }

    if (Array.isArray(value)) {
      for (const entry of value) {
        if (entry !== undefined && entry !== null && entry !== '') {
          url.searchParams.append(key, String(entry));
        }
      }
      continue;
    }

    if (isRecord(value)) {
      for (const [nestedKey, nestedValue] of Object.entries(value)) {
        if (nestedValue !== undefined && nestedValue !== null && nestedValue !== '') {
          url.searchParams.append(`${key}[${nestedKey}]`, String(nestedValue));
        }
      }
      continue;
    }

    url.searchParams.append(key, String(value));
  }
}

function errorCodeFrom(value: unknown): ErrorCode {
  return typeof value === 'string' && value !== '' ? value : 'internal_error';
}

/** Header values must never carry CR/LF or unbounded length. */
function headerValue(value: string, maxLength: number): string {
  return value.replace(/[\r\n\u0000]/g, ' ').trim().slice(0, maxLength);
}

const IDEMPOTENCY_KEY_PATTERN = /^[A-Za-z0-9._:-]{8,128}$/;

export class FennecDashClient implements FennecDashApi {
  private readonly baseUrl: string;
  private readonly token: string;
  private readonly timeoutMs: number;
  private readonly userAgent: string;
  private readonly fetchImpl: typeof fetch;
  private readonly agentName: string;
  private agentSession: string | null;
  private clientName: string | null = null;
  private clientVersion: string | null = null;

  constructor(options: FennecDashClientOptions) {
    this.baseUrl = options.baseUrl.replace(/\/+$/, '');
    this.token = options.token;
    this.timeoutMs = options.timeoutMs;
    this.userAgent = options.userAgent ?? 'fennedash-mcp/1.0.0';
    this.fetchImpl = options.fetchImpl ?? fetch;
    this.agentName = headerValue(options.agentName ?? 'fennedash-mcp', 120);
    this.agentSession = options.agentSession ? headerValue(options.agentSession, 120) : null;
  }

  setAgentSession(sessionId: string): void {
    this.agentSession = headerValue(sessionId, 120);
  }

  setClientInfo(name: string, version?: string): void {
    this.clientName = headerValue(name, 80);
    this.clientVersion = version ? headerValue(version, 40) : null;
  }

  async get<T = unknown>(
    path: string,
    query?: QueryParams,
    options: ApiCallOptions = {},
  ): Promise<ApiResponse<T>> {
    return this.request<T>('GET', path, { query, ...options });
  }

  async post<T = unknown>(
    path: string,
    body?: unknown,
    options: ApiCallOptions = {},
  ): Promise<ApiResponse<T>> {
    return this.request<T>('POST', path, { body, ...options });
  }

  async ping(options: ApiCallOptions = {}): Promise<ApiResponse<PingPayload>> {
    return this.get<PingPayload>('ping', undefined, options);
  }

  async me(options: ApiCallOptions = {}): Promise<ApiResponse<MePayload>> {
    return this.get<MePayload>('me', undefined, options);
  }

  private async request<T>(
    method: 'GET' | 'POST',
    path: string,
    options: {
      query?: QueryParams;
      body?: unknown;
      signal?: AbortSignal;
      requestId?: string;
      idempotencyKey?: string;
    },
  ): Promise<ApiResponse<T>> {
    const normalizedPath = path.replace(/^\/+/, '');
    const url = new URL(`${this.baseUrl}/api/v1/${normalizedPath}`);
    appendQuery(url, options.query);

    const timeoutSignal = AbortSignal.timeout(this.timeoutMs);
    const signal = options.signal
      ? AbortSignal.any([options.signal, timeoutSignal])
      : timeoutSignal;

    const headers: Record<string, string> = {
      Accept: 'application/json',
      'User-Agent': this.userAgent,
      'X-Request-Id': options.requestId ?? `mcp_${randomUUID().replace(/-/g, '').slice(0, 20)}`,
    };

    if (this.token !== '') {
      headers.Authorization = `Bearer ${this.token}`;
    }

    if (this.agentName !== '') {
      headers['X-Agent-Name'] = this.agentName;
    }

    if (this.agentSession !== null && this.agentSession !== '') {
      headers['X-Agent-Session'] = this.agentSession;
    }

    if (this.clientName !== null && this.clientName !== '') {
      headers['X-Mcp-Client'] = this.clientVersion
        ? `${this.clientName}/${this.clientVersion}`
        : this.clientName;
    }

    if (options.idempotencyKey !== undefined) {
      if (!IDEMPOTENCY_KEY_PATTERN.test(options.idempotencyKey)) {
        throw new FennecApiError(
          'validation_error',
          'The idempotency key must be 8-128 characters of letters, digits, dot, colon, underscore or dash.',
          { retryable: false },
        );
      }

      headers['Idempotency-Key'] = options.idempotencyKey;
    }

    if (method === 'POST') {
      headers['Content-Type'] = 'application/json';
    }

    let response: Response;

    try {
      response = await this.fetchImpl(url, {
        method,
        headers,
        signal,
        // Never follow a redirect: a misconfigured base URL must fail instead
        // of silently sending the bearer token to a different endpoint.
        redirect: 'error',
        ...(method === 'POST' ? { body: JSON.stringify(options.body ?? {}) } : {}),
      });
    } catch (error) {
      if (timeoutSignal.aborted) {
        throw new FennecApiError(
          'backend_timeout',
          `The FennecDash API did not respond within ${this.timeoutMs} ms.`,
          { retryable: true, cause: error },
        );
      }

      throw new FennecApiError(
        'backend_unreachable',
        `Could not reach the FennecDash API at ${this.baseUrl}. Check FENNEDASH_BASE_URL and network access.`,
        { retryable: true, cause: error },
      );
    }

    const text = await response.text();
    const headerRequestId = response.headers.get('x-request-id') ?? undefined;

    let envelope: ApiEnvelope;

    try {
      envelope = text.trim() === '' ? {} : (JSON.parse(text) as ApiEnvelope);
    } catch {
      throw new FennecApiError(
        'invalid_response',
        `The FennecDash API returned a non-JSON response (HTTP ${response.status}).`,
        { httpStatus: response.status, retryable: false },
      );
    }

    const requestId =
      (typeof envelope.request_id === 'string' ? envelope.request_id : undefined) ??
      headerRequestId;

    if (!response.ok || envelope.ok !== true) {
      const backendCode = errorCodeFrom(envelope.error?.code);
      const backendMessage =
        typeof envelope.error?.message === 'string' && envelope.error.message !== ''
          ? redactString(envelope.error.message)
          : `FennecDash API request failed with HTTP ${response.status}.`;

      throw new FennecApiError(backendCode, backendMessage, {
        httpStatus: response.status,
        details: isRecord(envelope.error?.details)
          ? (envelope.error.details as Record<string, unknown>)
          : undefined,
        retryable: response.status === 429 || response.status >= 500,
        backendRequestId: requestId,
      });
    }

    return {
      data: (envelope.data ?? null) as T,
      ...(isRecord(envelope.meta) ? { meta: envelope.meta } : {}),
      ...(requestId ? { requestId } : {}),
      ...(response.headers.get('idempotent-replayed') === 'true'
        ? { idempotentReplayed: true }
        : {}),
    };
  }
}

/** Narrow helper used by tools that receive loosely typed data. */
export function asRecord(value: unknown): Record<string, unknown> {
  if (!isRecord(value)) {
    throw new McpToolError('invalid_response', 'The FennecDash API returned an unexpected payload shape.');
  }

  return value;
}
