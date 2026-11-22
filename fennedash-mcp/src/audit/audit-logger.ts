import { appendFile, mkdir, rename, stat } from 'node:fs/promises';
import { dirname, isAbsolute, resolve } from 'node:path';

import { packageRoot } from '../config/env.js';
import type { Logger } from '../utils/logger.js';
import type { Risk } from '../permissions/risk.js';
import { redactValue } from '../security/redaction.js';

export type AuditEventType =
  | 'server_start'
  | 'server_stop'
  | 'session_open'
  | 'session_close'
  | 'auth_failure'
  | 'tool_call'
  | 'resource_read'
  | 'prompt_get';

export interface AuditActor {
  readonly staffId: number;
  readonly tokenId: number;
  readonly label: string;
  readonly client?: string;
}

export interface AuditEvent {
  readonly type: AuditEventType;
  readonly requestId: string;
  readonly transport: 'stdio' | 'http';
  readonly sessionId?: string;
  readonly tool?: string;
  readonly resource?: string;
  readonly prompt?: string;
  readonly risk?: Risk;
  readonly actor?: AuditActor;
  readonly outcome?: 'ok' | 'error' | 'denied';
  readonly durationMs?: number;
  readonly errorCode?: string;
  readonly backendRequestId?: string;
  readonly clientName?: string;
  readonly clientVersion?: string;
  readonly args?: unknown;
  readonly details?: unknown;
}

export interface AuditLoggerOptions {
  /**
   * Rotate the audit file once it grows past this many bytes. The previous
   * content is moved to `<path>.1`. Zero or a negative value disables rotation.
   */
  readonly maxBytes?: number;
}

const DEFAULT_MAX_BYTES = 0;

/**
 * Append-only JSON Lines audit trail. One line per event, secrets redacted,
 * writes serialized so concurrent tool calls cannot interleave.
 */
export class AuditLogger {
  private readonly filePath: string | null;
  private readonly maxBytes: number;
  private queue: Promise<void> = Promise.resolve();

  constructor(
    auditLogPath: string,
    private readonly logger: Logger,
    options: AuditLoggerOptions = {},
  ) {
    const trimmed = auditLogPath.trim();
    this.filePath =
      trimmed === ''
        ? null
        : isAbsolute(trimmed)
          ? trimmed
          : resolve(packageRoot, trimmed);
    this.maxBytes = options.maxBytes ?? DEFAULT_MAX_BYTES;
  }

  get enabled(): boolean {
    return this.filePath !== null;
  }

  log(event: AuditEvent): Promise<void> {
    if (this.filePath === null) {
      return Promise.resolve();
    }

    const record = {
      timestamp: new Date().toISOString(),
      ...(redactValue(event) as Record<string, unknown>),
    };

    this.queue = this.queue
      .then(async () => {
        const path = this.filePath as string;
        await mkdir(dirname(path), { recursive: true });
        await this.rotateIfNeeded(path);
        await appendFile(path, `${JSON.stringify(record)}\n`, 'utf8');
      })
      .catch((error: unknown) => {
        this.logger.error('Failed to write audit log', {
          error: error instanceof Error ? error.message : String(error),
        });
      });

    return this.queue;
  }

  async close(): Promise<void> {
    await this.queue;
  }

  private async rotateIfNeeded(path: string): Promise<void> {
    if (this.maxBytes <= 0) {
      return;
    }

    try {
      const info = await stat(path);

      if (info.size < this.maxBytes) {
        return;
      }

      await rename(path, `${path}.1`);
      this.logger.info('Rotated audit log', { maxBytes: this.maxBytes });
    } catch (error) {
      // A missing file is the normal first-write case. Anything else is worth
      // surfacing, but it must not stop the event from being written.
      if ((error as NodeJS.ErrnoException).code !== 'ENOENT') {
        this.logger.warn('Could not rotate audit log', {
          error: error instanceof Error ? error.message : String(error),
        });
      }
    }
  }
}
