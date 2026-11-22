import type { CallToolResult } from '@modelcontextprotocol/sdk/types.js';

import { redactValue } from './redaction.js';

export type ErrorCode =
  | 'validation_failed'
  | 'not_found'
  | 'unauthenticated'
  | 'invalid_token'
  | 'token_revoked'
  | 'token_expired'
  | 'inactive_staff'
  | 'forbidden'
  | 'risk_not_permitted'
  | 'feature_not_in_token_scope'
  | 'rate_limited'
  | 'approval_required'
  | 'approval_invalid'
  | 'approval_not_pending'
  | 'approval_expired'
  | 'approval_wrong_token'
  | 'approval_action_mismatch'
  | 'approval_payload_mismatch'
  | 'write_disabled'
  | 'critical_disabled'
  | 'session_limit_reached'
  | 'backend_unreachable'
  | 'backend_timeout'
  | 'invalid_response'
  | 'operation_unverified'
  | 'unsupported'
  | 'internal_error'
  | (string & {});

export interface ErrorDetails {
  readonly [key: string]: unknown;
}

/**
 * A safe, structured error. `message` is always safe to show to a model.
 */
export class McpToolError extends Error {
  readonly code: ErrorCode;
  readonly details?: ErrorDetails;
  readonly httpStatus?: number;
  readonly retryable: boolean;
  readonly backendRequestId?: string;

  constructor(
    code: ErrorCode,
    message: string,
    options: {
      details?: ErrorDetails;
      httpStatus?: number;
      retryable?: boolean;
      backendRequestId?: string;
      cause?: unknown;
    } = {},
  ) {
    super(message, options.cause === undefined ? undefined : { cause: options.cause });
    this.name = 'McpToolError';
    this.code = code;
    this.details = options.details;
    this.httpStatus = options.httpStatus;
    this.retryable = options.retryable ?? false;
    this.backendRequestId = options.backendRequestId;
  }
}

export class FennecApiError extends McpToolError {
  constructor(
    code: ErrorCode,
    message: string,
    options: {
      details?: ErrorDetails;
      httpStatus?: number;
      retryable?: boolean;
      backendRequestId?: string;
      cause?: unknown;
    } = {},
  ) {
    super(code, message, options);
    this.name = 'FennecApiError';
  }
}

export function normalizeError(error: unknown): McpToolError {
  if (error instanceof McpToolError) {
    return error;
  }

  if (error instanceof Error) {
    if (error.name === 'AbortError' || error.name === 'TimeoutError') {
      return new McpToolError('backend_timeout', 'The FennecDash API did not respond in time.', {
        retryable: true,
        cause: error,
      });
    }

    return new McpToolError(
      'internal_error',
      'An unexpected error occurred inside the FennecDash MCP server.',
      { cause: error },
    );
  }

  return new McpToolError('internal_error', 'An unexpected error occurred.');
}

export function errorPayload(error: McpToolError, requestId: string): Record<string, unknown> {
  return {
    ok: false,
    error: redactValue({
      code: error.code,
      message: error.message,
      ...(error.details ? { details: error.details } : {}),
      ...(error.httpStatus ? { http_status: error.httpStatus } : {}),
      ...(error.backendRequestId ? { backend_request_id: error.backendRequestId } : {}),
      retryable: error.retryable,
    }),
    request_id: error.backendRequestId ?? requestId,
  };
}

/**
 * MCP error result. `isError` lets clients distinguish a failed tool call from
 * a successful one without parsing the text.
 */
export function toErrorResult(error: McpToolError, requestId: string): CallToolResult {
  const payload = errorPayload(error, requestId);

  return {
    isError: true,
    content: [
      {
        type: 'text',
        text: JSON.stringify(payload, null, 2),
      },
    ],
    structuredContent: payload,
  };
}
