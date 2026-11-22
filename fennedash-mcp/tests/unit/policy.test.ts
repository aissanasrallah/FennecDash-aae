import { describe, expect, it } from 'vitest';

import { assertToolAllowed, describePolicy } from '../../src/permissions/policy.js';
import { McpToolError } from '../../src/security/errors.js';

const off = { allowWrite: false, allowCritical: false };
const writes = { allowWrite: true, allowCritical: false };
const all = { allowWrite: true, allowCritical: true };

function expectCode(action: () => void, code: string): void {
  try {
    action();
    throw new Error('expected the policy check to throw');
  } catch (error) {
    expect(error).toBeInstanceOf(McpToolError);
    expect((error as McpToolError).code).toBe(code);
  }
}

describe('assertToolAllowed', () => {
  it('always allows read tools', () => {
    expect(() => assertToolAllowed('read', off)).not.toThrow();
    expect(() => assertToolAllowed('read', all)).not.toThrow();
  });

  it('denies writes when MCP_ALLOW_WRITE is false', () => {
    expectCode(() => assertToolAllowed('write', off), 'write_disabled');
  });

  it('denies critical tools when MCP_ALLOW_CRITICAL is false', () => {
    expectCode(() => assertToolAllowed('critical', writes), 'critical_disabled');
  });

  it('enforces the token risk ceiling after local flags', () => {
    expectCode(
      () => assertToolAllowed('write', { allowWrite: true, allowCritical: false, tokenRisk: 'read' }),
      'risk_not_permitted',
    );
    expectCode(
      () =>
        assertToolAllowed('critical', {
          allowWrite: true,
          allowCritical: true,
          tokenRisk: 'write',
        }),
      'risk_not_permitted',
    );
  });

  it('allows writes and critical tools when everything is enabled', () => {
    expect(() => assertToolAllowed('write', writes)).not.toThrow();
    expect(() => assertToolAllowed('critical', all)).not.toThrow();
  });

  it('describes the active policy for diagnostics', () => {
    expect(describePolicy({ ...all, tokenRisk: 'critical' })).toEqual([
      'WRITE operations enabled.',
      'CRITICAL operations enabled.',
      'Token risk ceiling: critical.',
    ]);
  });
});
