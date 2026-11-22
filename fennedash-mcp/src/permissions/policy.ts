import { McpToolError } from '../security/errors.js';
import { RISK_RANK, type Risk } from './risk.js';

export interface PolicyContext {
  readonly allowWrite: boolean;
  readonly allowCritical: boolean;
  /** Risk ceiling reported by `/api/v1/me` for the current token, if known. */
  readonly tokenRisk?: Risk;
}

/**
 * Local authorization gate. The FennecDash API enforces the authoritative
 * checks (staff permissions + token risk + approvals); this gate prevents a
 * misconfigured agent from even attempting operations the operator disabled.
 */
export function assertToolAllowed(risk: Risk, policy: PolicyContext): void {
  if (risk === 'read') {
    return;
  }

  if (risk === 'write' && !policy.allowWrite) {
    throw new McpToolError(
      'write_disabled',
      'Write operations are disabled on this MCP server. Set MCP_ALLOW_WRITE=true to enable them.',
      { details: { required_risk: 'write', mcp_allow_write: false } },
    );
  }

  if (risk === 'critical' && !policy.allowCritical) {
    throw new McpToolError(
      'critical_disabled',
      'Critical operations are disabled on this MCP server. Set MCP_ALLOW_CRITICAL=true to enable them.',
      { details: { required_risk: 'critical', mcp_allow_critical: false } },
    );
  }

  if (policy.tokenRisk && RISK_RANK[policy.tokenRisk] < RISK_RANK[risk]) {
    throw new McpToolError(
      'risk_not_permitted',
      `The FennecDash API token is limited to "${policy.tokenRisk}" operations and cannot perform "${risk}".`,
      {
        details: {
          required_risk: risk,
          token_risk: policy.tokenRisk,
        },
      },
    );
  }
}

export function describePolicy(policy: PolicyContext): string[] {
  const notes: string[] = [];

  notes.push(policy.allowWrite ? 'WRITE operations enabled.' : 'WRITE operations disabled.');
  notes.push(policy.allowCritical ? 'CRITICAL operations enabled.' : 'CRITICAL operations disabled.');

  if (policy.tokenRisk) {
    notes.push(`Token risk ceiling: ${policy.tokenRisk}.`);
  }

  return notes;
}
