import type { LogLevel } from '../config/env.js';
import { redactValue } from '../security/redaction.js';

const RANK: Record<LogLevel, number> = {
  debug: 10,
  info: 20,
  warn: 30,
  error: 40,
};

export interface Logger {
  readonly level: LogLevel;
  debug(message: string, fields?: Record<string, unknown>): void;
  info(message: string, fields?: Record<string, unknown>): void;
  warn(message: string, fields?: Record<string, unknown>): void;
  error(message: string, fields?: Record<string, unknown>): void;
  child(scope: string): Logger;
}

function serialize(fields?: Record<string, unknown>): string {
  if (!fields || Object.keys(fields).length === 0) {
    return '';
  }

  try {
    return ` ${JSON.stringify(redactValue(fields))}`;
  } catch {
    return ' {"fields":"[unserializable]"}';
  }
}

class StderrLogger implements Logger {
  constructor(
    public readonly level: LogLevel,
    private readonly scope: string,
  ) {}

  child(scope: string): Logger {
    return new StderrLogger(this.level, `${this.scope}:${scope}`);
  }

  debug(message: string, fields?: Record<string, unknown>): void {
    this.write('debug', message, fields);
  }

  info(message: string, fields?: Record<string, unknown>): void {
    this.write('info', message, fields);
  }

  warn(message: string, fields?: Record<string, unknown>): void {
    this.write('warn', message, fields);
  }

  error(message: string, fields?: Record<string, unknown>): void {
    this.write('error', message, fields);
  }

  private write(level: LogLevel, message: string, fields?: Record<string, unknown>): void {
    if (RANK[level] < RANK[this.level]) {
      return;
    }

    // stdout is reserved for the MCP stdio protocol.
    process.stderr.write(
      `${new Date().toISOString()} ${level.toUpperCase().padEnd(5)} [${this.scope}] ${message}${serialize(fields)}\n`,
    );
  }
}

export function createLogger(level: LogLevel, scope = 'fennedash-mcp'): Logger {
  return new StderrLogger(level, scope);
}

/** Silent logger for unit tests. */
export function createNullLogger(): Logger {
  const noop = (): void => undefined;
  return {
    level: 'error',
    debug: noop,
    info: noop,
    warn: noop,
    error: noop,
    child: () => createNullLogger(),
  };
}
