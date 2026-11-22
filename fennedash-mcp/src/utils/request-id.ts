import { randomUUID } from 'node:crypto';

/** Correlation id shared by the MCP audit log and the FennecDash API audit log. */
export function newRequestId(prefix = 'mcp'): string {
  return `${prefix}_${randomUUID().replace(/-/g, '').slice(0, 20)}`;
}
