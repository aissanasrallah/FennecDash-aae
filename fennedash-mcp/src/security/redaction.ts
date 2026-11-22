/**
 * Defence in depth: the FennecDash API already redacts secrets, and the MCP
 * server redacts again before anything reaches a model, an audit file or a log.
 */

const SENSITIVE_KEY_PATTERNS: readonly RegExp[] = [
  /pass(word|wd|phrase)?/i,
  /secret/i,
  /authorization/i,
  /api[_-]?key/i,
  /private[_-]?key/i,
  /credential/i,
  /signature/i,
  /cookie/i,
  /token[_-]?hash/i,
  /^(access|refresh|approval|auth|api)?[_-]?token$/i,
];

const TOKEN_PATTERN = /\bfdm_(?:ap_)?[a-f0-9]{20,}\b/gi;
const BEARER_PATTERN = /\bBearer\s+[A-Za-z0-9._~+/=-]{12,}/gi;
const JWT_PATTERN = /\beyJ[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\b/g;

export const REDACTED = '[redacted]';

export function isSensitiveKey(key: string): boolean {
  return SENSITIVE_KEY_PATTERNS.some((pattern) => pattern.test(key));
}

export function redactString(value: string): string {
  return value
    .replace(TOKEN_PATTERN, 'fdm_[redacted]')
    .replace(BEARER_PATTERN, 'Bearer [redacted]')
    .replace(JWT_PATTERN, '[redacted-jwt]');
}

/**
 * Deep clone that removes secrets. Handles cycles, Buffers, Dates, Errors,
 * Maps and Sets so callers can pass API payloads and tool arguments safely.
 *
 * `preserveKeys` is deliberately narrow: it exists for capability tokens that
 * must be handed back to the caller (a single-use approval token) while every
 * other secret stays redacted, including the same token in audit logs.
 */
export interface RedactOptions {
  readonly preserveKeys?: ReadonlySet<string>;
}

export function redactValue(value: unknown, options: RedactOptions = {}): unknown {
  return redactInternal(value, options, new WeakSet<object>(), 0);
}

function redactInternal(
  value: unknown,
  options: RedactOptions,
  seen: WeakSet<object>,
  depth: number,
): unknown {
  if (value === null || value === undefined) {
    return value;
  }

  if (depth > 16) {
    return '[max-depth]';
  }

  if (typeof value === 'string') {
    return redactString(value);
  }

  if (typeof value === 'number' || typeof value === 'boolean') {
    return value;
  }

  if (typeof value === 'bigint') {
    return value.toString();
  }

  if (typeof value === 'function' || typeof value === 'symbol') {
    return '[unserializable]';
  }

  if (value instanceof Date) {
    return value.toISOString();
  }

  if (value instanceof Error) {
    return {
      name: value.name,
      message: redactString(value.message),
    };
  }

  if (Buffer.isBuffer(value)) {
    return `[buffer ${value.byteLength} bytes]`;
  }

  if (typeof value === 'object') {
    if (seen.has(value)) {
      return '[circular]';
    }

    seen.add(value);

    if (Array.isArray(value)) {
      return value.map((entry) => redactInternal(entry, options, seen, depth + 1));
    }

    if (value instanceof Map) {
      return redactInternal(Object.fromEntries(value), options, seen, depth + 1);
    }

    if (value instanceof Set) {
      return [...value].map((entry) => redactInternal(entry, options, seen, depth + 1));
    }

    const output: Record<string, unknown> = {};

    for (const [key, entry] of Object.entries(value as Record<string, unknown>)) {
      if (options.preserveKeys?.has(key)) {
        output[key] = entry;
        continue;
      }

      output[key] = isSensitiveKey(key)
        ? REDACTED
        : redactInternal(entry, options, seen, depth + 1);
    }

    return output;
  }

  return '[unserializable]';
}

/**
 * Compact representation for audit rows and logs: redacted, then capped.
 */
export function summarizeForAudit(value: unknown, maxLength = 1500): unknown {
  const redacted = redactValue(value);

  try {
    const serialized = JSON.stringify(redacted);

    if (serialized === undefined) {
      return null;
    }

    if (serialized.length <= maxLength) {
      return redacted;
    }

    return {
      truncated: true,
      bytes: serialized.length,
      preview: redactString(serialized.slice(0, maxLength)),
    };
  } catch {
    return '[unserializable]';
  }
}
