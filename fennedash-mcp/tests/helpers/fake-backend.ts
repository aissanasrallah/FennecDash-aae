import { randomBytes } from 'node:crypto';
import { createServer, type IncomingMessage, type ServerResponse } from 'node:http';

export interface RecordedRequest {
  readonly action: string;
  readonly method: string;
  readonly authorization: string | undefined;
  readonly requestId: string | undefined;
  readonly body: unknown;
}

export interface FakeBackend {
  readonly baseUrl: string;
  readonly requests: RecordedRequest[];
  readonly approvals: Map<string, 'pending' | 'used'>;
  close(): Promise<void>;
}

interface StaffProfile {
  readonly id: number;
  readonly email: string;
  readonly firstname: string;
  readonly lastname: string;
  readonly admin: boolean;
}

const STAFF: Record<string, StaffProfile> = {
  'test-token': {
    id: 15,
    email: 'admin@fennec.test',
    firstname: 'Demo',
    lastname: 'User',
    admin: true,
  },
  'other-token': {
    id: 16,
    email: 'agent@fennec.test',
    firstname: 'Other',
    lastname: 'Agent',
    admin: false,
  },
};

function readBody(req: IncomingMessage): Promise<unknown> {
  return new Promise((resolve, reject) => {
    const chunks: Buffer[] = [];
    req.on('data', (chunk: Buffer) => chunks.push(chunk));
    req.on('error', reject);
    req.on('end', () => {
      const raw = Buffer.concat(chunks).toString('utf8').trim();
      if (raw === '') {
        resolve(undefined);
        return;
      }
      try {
        resolve(JSON.parse(raw));
      } catch {
        reject(new Error('invalid json'));
      }
    });
  });
}

function send(
  res: ServerResponse,
  status: number,
  payload: Record<string, unknown>,
  requestId: string,
): void {
  const body = JSON.stringify({ ...payload, request_id: requestId });
  res.writeHead(status, {
    'Content-Type': 'application/json; charset=utf-8',
    'X-Request-Id': requestId,
    'Content-Length': Buffer.byteLength(body),
  });
  res.end(body);
}

function fail(
  res: ServerResponse,
  status: number,
  code: string,
  message: string,
  requestId: string,
  details: Record<string, unknown> = {},
): void {
  send(res, status, { ok: false, error: { code, message, details } }, requestId);
}

export async function startFakeBackend(): Promise<FakeBackend> {
  const requests: RecordedRequest[] = [];
  const approvals = new Map<string, 'pending' | 'used'>();

  const server = createServer((req, res) => {
    void (async () => {
      const requestId = String(req.headers['x-request-id'] ?? 'fake-request');
      const authorization = req.headers.authorization;
      const token = /^Bearer\s+(.+)$/i.exec(authorization ?? '')?.[1];
      const staff = token ? STAFF[token] : undefined;

      const url = new URL(req.url ?? '/', 'http://localhost');
      const action = url.pathname.replace(/^\/api\/v1\/?/, '').replace(/\/+$/, '');

      let body: unknown;
      try {
        body = await readBody(req);
      } catch {
        fail(res, 400, 'invalid_json', 'Request body is not valid JSON.', requestId);
        return;
      }

      requests.push({
        action,
        method: req.method ?? 'GET',
        authorization,
        requestId: req.headers['x-request-id'] as string | undefined,
        body,
      });

      if (action === 'ping') {
        send(
          res,
          200,
          { ok: true, data: { pong: true, api_version: 'v1', server_time: new Date().toISOString() } },
          requestId,
        );
        return;
      }

      if (!staff) {
        fail(res, 401, 'invalid_token', 'The API token is invalid.', requestId);
        return;
      }

      const tokenInfo = {
        id: token === 'other-token' ? 2 : 1,
        label: token === 'other-token' ? 'other' : 'test',
        prefix: (token ?? '').slice(0, 8),
        risk_max: 'critical',
        scopes: null,
        created_at: '2026-01-01 00:00:00',
        expires_at: null,
        last_used_at: null,
        request_count: requests.length,
      };

      if (action === 'me') {
        send(
          res,
          200,
          {
            ok: true,
            data: {
              staff: {
                id: staff.id,
                email: staff.email,
                firstname: staff.firstname,
                lastname: staff.lastname,
                admin: staff.admin,
                role_id: null,
                language: 'english',
              },
              token: tokenInfo,
            },
          },
          requestId,
        );
        return;
      }

      if (action === 'system_status') {
        send(
          res,
          200,
          {
            ok: true,
            data: {
              application: { name: 'Fennec-Dash', environment: 'testing' },
              api: { version: 'v1', authenticated: true },
              staff_id: staff.id,
              token_risk: 'critical',
            },
          },
          requestId,
        );
        return;
      }

      if (action === 'system_capabilities') {
        send(
          res,
          200,
          {
            ok: true,
            data: {
              api_version: 'v1',
              resources: ['projects', 'clients'],
              write_operations: ['projects_create', 'projects_update'],
              critical_operations: ['cache_clear'],
            },
          },
          requestId,
        );
        return;
      }

      if (action === 'system_permissions') {
        send(
          res,
          200,
          {
            ok: true,
            data: {
              staff_id: staff.id,
              admin: staff.admin,
              role: null,
              permissions: staff.admin ? { all: true } : { projects: ['view'] },
            },
          },
          requestId,
        );
        return;
      }

      if (action === 'system_info') {
        send(res, 200, { ok: true, data: { counts: { projects: 1, clients: 1 } } }, requestId);
        return;
      }

      if (action === 'system_health') {
        send(
          res,
          200,
          { ok: true, data: { status: 'ok', checks: [{ name: 'database', status: 'ok' }] } },
          requestId,
        );
        return;
      }

      if (action === 'system_modules') {
        send(
          res,
          200,
          { ok: true, data: { modules: [{ name: 'aae', active: true, installed_version: '1.1.0' }] } },
          requestId,
        );
        return;
      }

      if (action === 'resources') {
        send(
          res,
          200,
          {
            ok: true,
            data: [
              { resource: 'projects', id_field: 'id' },
              { resource: 'clients', id_field: 'userid' },
            ],
            meta: { count: 2 },
          },
          requestId,
        );
        return;
      }

      if (action === 'search') {
        const term = url.searchParams.get('q') ?? '';
        send(
          res,
          200,
          {
            ok: true,
            data: term.length >= 2 ? [{ resource: 'projects', id: '1', title: 'Alpha' }] : [],
            meta: { query: term, count: term.length >= 2 ? 1 : 0 },
          },
          requestId,
        );
        return;
      }

      if (action === 'projects' && req.method === 'GET') {
        send(
          res,
          200,
          {
            ok: true,
            data: [
              { id: '1', name: 'Alpha', status: '1', clientid: '1' },
              { id: '2', name: 'Beta', status: '2', clientid: '1' },
            ],
            meta: { total: 2, page: 1, per_page: 25 },
          },
          requestId,
        );
        return;
      }

      if (action === 'projects/1') {
        send(
          res,
          200,
          {
            ok: true,
            data: {
              id: '1',
              name: 'Alpha',
              status: '1',
              clientid: '1',
              tasks: [],
              members: [],
              files: [],
              invoices: [],
            },
          },
          requestId,
        );
        return;
      }

      if (action === 'projects/999999') {
        fail(res, 404, 'not_found', 'No projects record with id 999999.', requestId);
        return;
      }

      if (action === 'clients' && req.method === 'GET') {
        send(
          res,
          200,
          {
            ok: true,
            data: [{ userid: '1', company: 'Acme', active: '1' }],
            meta: { total: 1, page: 1, per_page: 25 },
          },
          requestId,
        );
        return;
      }

      if (action === 'clients/1') {
        send(res, 200, { ok: true, data: { userid: '1', company: 'Acme' } }, requestId);
        return;
      }

      if (action === 'projects_create') {
        const input = (body ?? {}) as Record<string, unknown>;

        if (typeof input.name !== 'string' || input.name === '') {
          fail(res, 422, 'validation_failed', 'Field "name" is required.', requestId, {
            field: 'name',
          });
          return;
        }

        send(
          res,
          200,
          {
            ok: true,
            data: {
              id: '99',
              name: input.name,
              clientid: String(input.clientid ?? ''),
              status: '1',
              progress: '0',
            },
            meta: { created: true, verified: true },
          },
          requestId,
        );
        return;
      }

      if (action === 'projects_update') {
        send(
          res,
          200,
          { ok: true, data: { ...(body as object) }, meta: { updated: true, verified: true } },
          requestId,
        );
        return;
      }

      if (action === 'logs_list') {
        send(
          res,
          200,
          {
            ok: true,
            data: [{ timestamp: '2026-01-01 00:00:00', level: 'ERROR', message: 'boom' }],
            meta: { count: 1 },
          },
          requestId,
        );
        return;
      }

      if (action === 'dashboard_overview') {
        send(res, 200, { ok: true, data: { invoiced: 100, paid: 40 } }, requestId);
        return;
      }

      if (action === 'clients_summary') {
        const page = parseInt(url.searchParams.get('page') ?? '1', 10);
        const perPage = Math.min(parseInt(url.searchParams.get('per_page') ?? '25', 10), 100);

        send(
          res,
          200,
          {
            ok: true,
            data: [
              { userid: '1', company: 'Acme', email: 'acme@example.com', status: '1', owner: '15', currency: 'USD', balance: '100.00' },
            ],
            meta: { total: 1, page, per_page: perPage },
          },
          requestId,
        );
        return;
      }

      if (action === 'reports_sales') {
        const from = url.searchParams.get('from') ?? '2026-01-01';
        const to = url.searchParams.get('to') ?? '2026-01-31';
        const currency = url.searchParams.get('currency') ?? 'USD';
        const top = parseInt(url.searchParams.get('top') ?? '10', 10);

        send(
          res,
          200,
          {
            ok: true,
            data: {
              period: { from, to, currency },
              invoiced: 10000,
              paid: 8000,
              outstanding: 2000,
              invoice_counts: { draft: 2, sent: 5, paid: 8, overdue: 1, cancelled: 0 },
              top_clients: [
                { userid: '1', company: 'Acme', invoiced: 5000 },
                { userid: '2', company: 'Beta Corp', invoiced: 3000 },
              ].slice(0, top),
            },
          },
          requestId,
        );
        return;
      }

      if (action === 'approvals_create') {
        const tokenValue = `fdm_ap_${randomBytes(20).toString('hex')}`;
        approvals.set(tokenValue, 'pending');

        send(
          res,
          200,
          {
            ok: true,
            data: {
              approval_token: tokenValue,
              expires_at: '2026-01-01 00:05:00',
              action: 'cache_clear',
            },
            meta: { single_use: true, ttl_seconds: 300 },
          },
          requestId,
        );
        return;
      }

      if (action === 'cache_clear') {
        const input = (body ?? {}) as Record<string, unknown>;
        const dryRun = input.dry_run !== false;

        if (dryRun) {
          send(
            res,
            200,
            {
              ok: true,
              data: { dry_run: true, would_delete: 3, bytes: 12, approval_token: null },
              meta: { message: 'Dry run only.' },
            },
            requestId,
          );
          return;
        }

        const approvalToken = typeof input.approval_token === 'string' ? input.approval_token : '';
        const state = approvals.get(approvalToken);

        if (!approvalToken || state === undefined) {
          fail(res, 403, 'approval_invalid', 'The approval token is invalid.', requestId);
          return;
        }

        if (state === 'used') {
          fail(res, 409, 'approval_not_pending', 'This approval is no longer usable.', requestId, {
            status: 'used',
          });
          return;
        }

        approvals.set(approvalToken, 'used');
        send(
          res,
          200,
          {
            ok: true,
            data: { dry_run: false, deleted: 3, remaining: 0, bytes: 12 },
            meta: { verified: true, message: 'Cache cleared and verified.' },
          },
          requestId,
        );
        return;
      }

      fail(res, 404, 'unknown_endpoint', `No fake endpoint for "${action}".`, requestId);
    })().catch((error: unknown) => {
      if (!res.headersSent) {
        res.writeHead(500, { 'Content-Type': 'application/json' });
        res.end(
          JSON.stringify({
            ok: false,
            error: { code: 'internal_error', message: error instanceof Error ? error.message : 'error' },
            request_id: 'fake',
          }),
        );
      }
    });
  });

  await new Promise<void>((resolve) => {
    server.listen(0, '127.0.0.1', () => resolve());
  });

  const address = server.address();
  const port = typeof address === 'object' && address !== null ? address.port : 0;

  return {
    baseUrl: `http://127.0.0.1:${port}`,
    requests,
    approvals,
    async close() {
      await new Promise<void>((resolve) => server.close(() => resolve()));
    },
  };
}
