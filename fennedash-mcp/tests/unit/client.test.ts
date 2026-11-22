import { describe, expect, it } from 'vitest';

import { FennecDashClient } from '../../src/integrations/fennedash-api/client.js';
import { McpToolError } from '../../src/security/errors.js';

const base = {
  baseUrl: 'https://crm.example.com',
  token: 'fdm_test',
  timeoutMs: 1000,
};

function jsonResponse(payload: unknown, status = 200, headers: Record<string, string> = {}): Response {
  return new Response(JSON.stringify(payload), {
    status,
    headers: { 'Content-Type': 'application/json', ...headers },
  });
}

describe('FennecDashClient', () => {
  it('sends the bearer token and correlation header, and parses the envelope', async () => {
    let captured: { url: string; init: RequestInit } | undefined;

    const client = new FennecDashClient({
      ...base,
      fetchImpl: (async (url: string | URL | Request, init?: RequestInit) => {
        captured = { url: String(url), init: init ?? {} };
        return jsonResponse({
          ok: true,
          data: { id: '1' },
          meta: { total: 1 },
          request_id: 'req_backend',
        });
      }) as typeof fetch,
    });

    const response = await client.get('projects/1', { q: 'alpha', filter: { status: 1 } }, {
      requestId: 'mcp_abc',
    });

    expect(response.data).toEqual({ id: '1' });
    expect(response.meta).toEqual({ total: 1 });
    expect(response.requestId).toBe('req_backend');

    const headers = new Headers(captured?.init.headers);
    expect(headers.get('authorization')).toBe('Bearer fdm_test');
    expect(headers.get('x-request-id')).toBe('mcp_abc');
    expect(captured?.url).toBe('https://crm.example.com/api/v1/projects/1?q=alpha&filter%5Bstatus%5D=1');
  });

  it('maps backend errors to structured tool errors', async () => {
    const client = new FennecDashClient({
      ...base,
      fetchImpl: (async () =>
        jsonResponse(
          {
            ok: false,
            error: {
              code: 'approval_required',
              message: 'This operation is critical.',
              details: { action: 'cache_clear' },
            },
            request_id: 'req_denied',
          },
          403,
        )) as typeof fetch,
    });

    await expect(client.post('cache_clear', { dry_run: false })).rejects.toMatchObject({
      code: 'approval_required',
      httpStatus: 403,
      backendRequestId: 'req_denied',
      details: { action: 'cache_clear' },
    });
  });

  it('reports a non-JSON response without leaking its body', async () => {
    const client = new FennecDashClient({
      ...base,
      fetchImpl: (async () =>
        new Response('<html>login</html>', {
          status: 200,
          headers: { 'Content-Type': 'text/html' },
        })) as typeof fetch,
    });

    try {
      await client.get('projects');
      throw new Error('expected a failure');
    } catch (error) {
      expect(error).toBeInstanceOf(McpToolError);
      expect((error as McpToolError).code).toBe('invalid_response');
      expect((error as McpToolError).message).not.toContain('<html>');
    }
  });

  it('distinguishes unreachable and timed-out backends', async () => {
    const unreachable = new FennecDashClient({
      ...base,
      fetchImpl: (async () => {
        throw new TypeError('fetch failed');
      }) as typeof fetch,
    });

    await expect(unreachable.get('projects')).rejects.toMatchObject({
      code: 'backend_unreachable',
      retryable: true,
    });

    const slow = new FennecDashClient({
      ...base,
      timeoutMs: 5,
      fetchImpl: ((_url: string | URL | Request, init?: RequestInit) =>
        new Promise<Response>((_resolve, reject) => {
          init?.signal?.addEventListener('abort', () => {
            const error = new Error('aborted');
            error.name = 'AbortError';
            reject(error);
          });
        })) as typeof fetch,
    });

    await expect(slow.get('projects')).rejects.toMatchObject({
      code: 'backend_timeout',
      retryable: true,
    });
  });

  it('uses the response header request id when the envelope omits it', async () => {
    const client = new FennecDashClient({
      ...base,
      fetchImpl: (async () =>
        jsonResponse({ ok: true, data: [] }, 200, { 'X-Request-Id': 'req_header' })) as typeof fetch,
    });

    await expect(client.get('projects')).resolves.toMatchObject({ requestId: 'req_header' });
  });
});
