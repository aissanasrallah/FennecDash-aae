import { describe, expect, it } from 'vitest';

import { REDACTED, redactString, redactValue, summarizeForAudit } from '../../src/security/redaction.js';

describe('redactString', () => {
  it('removes FennecDash API and approval tokens', () => {
    const input = 'token fdm_0123456789abcdef0123456789abcdef01234567 and fdm_ap_0123456789abcdef0123456789abcdef01234567';
    const output = redactString(input);

    expect(output).not.toContain('fdm_0123456789abcdef');
    expect(output).toContain('fdm_[redacted]');
  });

  it('removes bearer credentials and JWTs', () => {
    expect(redactString('Authorization: Bearer abcdefghijklmnopqrstuvwxyz')).toContain(
      'Bearer [redacted]',
    );
    expect(redactString('eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.c2lnbmF0dXJlMTIzNDU2')).toContain(
      '[redacted-jwt]',
    );
  });
});

describe('redactValue', () => {
  it('redacts sensitive keys at every depth', () => {
    const output = redactValue({
      staff: { email: 'a@b.c', password: 'hunter2' },
      nested: [{ api_key: 'sk-live', note: 'ok' }],
      approval_token: 'fdm_ap_abc',
    }) as Record<string, unknown>;

    expect(output.staff).toEqual({ email: 'a@b.c', password: REDACTED });
    expect(output.nested).toEqual([{ api_key: REDACTED, note: 'ok' }]);
    expect(output.approval_token).toBe(REDACTED);
  });

  it('handles cycles, dates, errors and buffers', () => {
    const cyclic: Record<string, unknown> = { name: 'loop' };
    cyclic.self = cyclic;

    const output = redactValue({
      cyclic,
      when: new Date('2026-01-01T00:00:00.000Z'),
      error: new Error('failed with fdm_0123456789abcdef0123456789abcdef01234567'),
      buffer: Buffer.from('secret'),
    }) as Record<string, unknown>;

    expect(output.cyclic).toEqual({ name: 'loop', self: '[circular]' });
    expect(output.when).toBe('2026-01-01T00:00:00.000Z');
    expect(output.error).toEqual({
      name: 'Error',
      message: 'failed with fdm_[redacted]',
    });
    expect(output.buffer).toBe('[buffer 6 bytes]');
  });

  it('caps large audit summaries', () => {
    const summary = summarizeForAudit({ text: 'x'.repeat(4000) }, 100) as Record<string, unknown>;

    expect(summary.truncated).toBe(true);
    expect(String(summary.preview).length).toBeLessThanOrEqual(100);
  });
});
