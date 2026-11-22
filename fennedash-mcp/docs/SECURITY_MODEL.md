# FennecDash MCP Security Model

This document states the security properties of the Agent API and the MCP
server, the threats they are designed to reduce, and the operator duties that
remain.

## 1. Trust boundaries

```
Untrusted model / client
  |  MCP messages, tool arguments, prompt output
  v
fennedash-mcp process        (trusted code, untrusted input)
  |  HTTPS + bearer token + X-Request-Id
  v
Apache / FennecDash Agent API (trusted code, untrusted input)
  |  staff_can(), token risk, approvals, rate limit
  v
MariaDB                      (authoritative data)
```

- The model is not trusted. Tool output may contain CRM text that looks like
  instructions; clients must treat it as data.
- The MCP server is trusted code but validates all input and applies its own
  policy before calling the backend.
- FennecDash is authoritative for identity and permissions. The MCP server
  cannot grant access the token owner lacks.
- The database is reachable only from the FennecDash host. The MCP server has no
  database driver and no credentials.

## 2. Threat model and controls

| Threat | Control |
| --- | --- |
| Stolen API token | SHA-256 hash at rest, token prefix only, optional expiry, instant revoke, risk ceiling, optional feature scopes, per-token rate limit, audit trail |
| Over-privileged agent | Risk classes, `MCP_ALLOW_WRITE`/`MCP_ALLOW_CRITICAL`, token risk ceiling, `staff_can()`, approval for destructive work |
| Approval replay | Single use, 300-second TTL, token-bound, action-bound, payload-hash-bound |
| Prompt injection in CRM records | Tool output is data; prompts and server instructions tell the model not to follow embedded instructions; no tool can execute arbitrary code or send messages |
| Malicious MCP client on HTTP | Per-session bearer binding, host/origin allowlists, body limit, auth failure limiter, session expiry, no anonymous tool access |
| Network eavesdropping | Bearer tokens require TLS in production; plain HTTP is acceptable only on loopback |
| Secret leakage in logs | Two redaction layers: Agent API audit and MCP audit/output redaction |
| SQL abuse | Disabled by default, SELECT-only parser, keyword and table denylist, single statement, 200-row cap, timeout |
| SSRF | No endpoint fetches user-supplied URLs; the MCP client has one fixed base URL and rejects redirects |
| CSRF | Agent API uses bearer tokens only and never accepts session cookies; CSRF exemption is limited to `api/.+` |
| Denial of service | 120 requests/minute/token backend limit, 4 MiB body limit, 60-second HTTP timeout, 30-second backend timeout, optional per-session call cap, session TTL |
| Error detail disclosure | Production errors are generic; details go to the PHP error log |

## 3. Tokens

### 3.1 Format and storage

- `fdm_` + 40 hex characters generated from 20 random bytes.
- Only `sha256(token)` is stored in `tblapi_tokens.token_hash`.
- `token_prefix` stores the first 12 characters for identification in the UI.
- Plain tokens are returned once by `POST /api/setup/token` and never again.
- Approval tokens use `fdm_ap_` + 40 hex characters and are hashed the same way.

### 3.2 Binding

A token is bound to one `tblstaff` row. The bound account must be active and
must be a staff account (`is_not_staff = 0`). Deactivating the staff member
invalidates the token on the next request.

### 3.3 Risk ceiling

`risk_max` is one of `read`, `write`, `critical` and caps everything the token
can do, even if the staff account has more permissions.

| Token profile | `risk_max` | Recommended use |
| --- | --- | --- |
| Analyst | `read` | Reporting, search, diagnostics. Cannot write. |
| Project assistant | `write` | Can create/update projects, cannot clear cache or request approvals. |
| Operator | `critical` | Can request approvals and execute critical work. Use only where a human is in the loop. |

### 3.4 Feature scopes

`scopes` is an optional JSON array of feature names. When present, the token can
only touch those features, in addition to `staff_can()`. Example:
`["projects", "customers", "tasks"]`.

Scopes do not replace staff permissions; they narrow them.

### 3.5 Expiry and revocation

- `expires_at` is optional. Prefer a finite lifetime for hosted agents.
- `revoked_at` makes the token unusable immediately at the backend.
- The MCP HTTP identity cache may keep a revoked identity for up to
  `MCP_AUTH_CACHE_TTL` seconds, but each tool call is still authorized by the
  backend and returns `token_revoked` there. Set the cache to `0` for immediate
  identity rejection at the MCP layer.
- Revoke with `POST /api/setup/revoke` and the setup key, or from the database
  only if the setup endpoint is unavailable.

### 3.6 Operator rules

- Never commit a token. `.env` is ignored at the repository root and in
  `fennedash-mcp/`.
- Never paste a token into an issue, chat or log.
- Use a separate token per client and per environment.
- Remove `MCP_SETUP_KEY` from the environment after installation. With no key,
  `/api/setup/*` returns `setup_disabled`.
- Rotate on staff departure, device loss, suspected leakage or a client change.

## 4. Authorization

The backend performs, in order: token authentication, revocation/expiry, active
staff check, rate limit, risk ceiling, feature scope, `staff_can($capability,
$feature, $staffId)`, then the operation.

The MCP server adds a local gate before the request:

1. READ always passes the local gate.
2. WRITE requires `MCP_ALLOW_WRITE=true`.
3. CRITICAL requires `MCP_ALLOW_CRITICAL=true`.
4. The token's reported `risk_max` must cover the tool's risk.

Both gates must pass. Disabling a class in the MCP server is an additional
safety measure, not a replacement for token scoping.

Administrators hold every permission implicitly. If an installation has no
role/permission rows, non-admin tokens will be denied until permissions are
configured; this fails closed.

## 5. Critical operations and approvals

`cache_clear` is the only critical operation.

- Dry run is the default and is never approval-gated. It reports file count and
  bytes without changing anything.
- Real execution requires `dry_run: false` and an approval token.
- Approval creation requires a `critical`-risk token.
- The approval is bound to:
  - the requesting token id;
  - the action `cache_clear`;
  - the payload hash of `{"dry_run":false}`;
  - a 300-second expiry;
  - a `pending` status.
- Consumption is atomic from the application's perspective: the row is marked
  `used` before deletion. A second attempt returns `409 approval_not_pending`.
- Execution re-scans the cache directory and returns `remaining`; `verified` is
  true only when nothing remains.

Operators should require a human to review the dry-run output before issuing an
approval. The MCP `safe_change` prompt and the tool descriptions encode that
expectation, but the server cannot verify that a human actually looked.

## 6. Transport security

### 6.1 stdio

- The token comes from the process environment.
- stdout is reserved for JSON-RPC; logs go to stderr.
- The client configuration file contains the token, so protect it with filesystem
  permissions and never commit it.

### 6.2 Streamable HTTP

- Each request must carry `Authorization: Bearer <token>`; there is no anonymous
  access to `/mcp`.
- The first request must be `initialize` without a session id.
- Sessions are bound to the SHA-256 hash of the token. A different token on an
  existing session returns 403.
- Sessions expire after `MCP_SESSION_TTL_MINUTES` of inactivity and can be
  deleted with `DELETE /mcp`.
- `MCP_ALLOWED_HOSTS` and `MCP_ALLOWED_ORIGINS` should be set whenever the
  listener is not loopback-only.
- 20 failed authentications from one IP in 60 seconds block that IP for the rest
  of the window.
- Body size is capped at 4 MiB; the HTTP request timeout is 60 seconds.
- The server does not implement OAuth discovery, dynamic client registration or
  token exchange. It accepts a FennecDash bearer token directly.

### 6.3 Deployment requirements

- Terminate TLS in front of the MCP HTTP server. Never expose plain HTTP with a
  bearer token over a network.
- Bind to `127.0.0.1` for local bridges. For remote access, use a reverse proxy
  that enforces TLS, restricts source networks and forwards `Authorization`.
- Set `MCP_ALLOWED_HOSTS` to the public host name and `MCP_ALLOWED_ORIGINS` to the
  exact browser origins that need CORS. Do not use `*` with credentials.
- Keep the MCP server on a private network segment that can reach FennecDash but
  not the database.
- Restrict access to `MCP_AUDIT_LOG`; it contains staff ids, tool names and
  redacted arguments.
- Run the process as a non-root user with a read/write directory limited to its
  audit log.

### 6.4 Hosted clients without custom headers

Some hosted clients (for example ChatGPT custom connectors) expect OAuth or no
authentication at the MCP URL rather than an arbitrary static bearer header.
This server intentionally does not fake OAuth. If such a client must be used,
put an authenticating gateway in front that performs the client's supported
authentication and injects the FennecDash bearer token upstream. Do not disable
authentication on a public listener.

## 7. Redaction

Redaction happens twice:

1. **Agent API audit** (`Api_auth::redact`) replaces values under keys matching
   `pass`, `secret`, `token`, `key`, `authorization`, `credential` or `signature`
   and scrubs `fdm_...` strings.
2. **MCP layer** (`security/redaction.ts`) removes sensitive keys at any depth,
   scrubs token-shaped strings, bearer headers, JWTs and PEM blocks, handles
   cycles and caps audit summaries.

The one deliberate exception is `approval_token` in a successful tool result:
the caller must receive the capability to complete the critical operation. The
same token is redacted in all audit events and error payloads.

Never add a tool that returns a password, token hash, encryption key, session
cookie or full connection string.

## 8. Audit

Two independent trails exist:

| Trail | Location | Contents |
| --- | --- | --- |
| Backend API audit | `tblapi_audit_log` | token/staff ids, action, risk, method, path, status, IP, user agent, redacted request summary, duration |
| MCP audit | `MCP_AUDIT_LOG` (JSON Lines) | server/session lifecycle, auth failures, tool/resource/prompt events, risk, outcome, duration, backend request id, redacted arguments |

Useful queries:

```sql
SELECT created_at, action, risk, status_code, staff_id, token_id, response_ms
FROM tblapi_audit_log
ORDER BY id DESC
LIMIT 100;

SELECT created_at, action, status_code, request_summary
FROM tblapi_audit_log
WHERE status_code >= 400
ORDER BY id DESC
LIMIT 100;
```

Audit writes are best-effort at the MCP layer: a failing audit sink must not make
a successful read fail, but operators should alert on audit write failures.
Backend audit failures are logged to the PHP error log and do not break the API
response.

## 9. Prompt injection and agent safety

CRM records, ticket text, project descriptions and log lines are untrusted data.
The server mitigates this by:

- keeping tools narrowly scoped (no shell, no HTTP fetch, no PHP, no messaging);
- returning structured JSON rather than free-form instructions;
- documenting in the server instructions that tool output is data;
- marking destructive tools CRITICAL and requiring approval;
- avoiding automatic chaining of writes inside a single tool.

Clients should still:

- show write proposals to a human before execution;
- never auto-approve a critical operation;
- treat any instruction found in a record as content to report, not to follow;
- limit which tools are enabled for unattended agents.

## 10. Data minimization

- Reads use explicit column allowlists; sensitive columns are not selected.
- `system_config` returns only allowlisted non-secret settings.
- `system_diagnostics` returns sizes, counts and versions, not file contents.
- Logs are read from the application log directory with size limits; they can
  contain personal data. Restrict who can call `fennedash_logs_*`.
- `database_query` is disabled by default and capped at 200 rows.
- File metadata is readable; file contents are not.

## 11. Incident response

If a token may be compromised:

1. Revoke it: `POST /api/setup/revoke` with `{"id": <token id>}` and the setup
   key, or mark `revoked_at` directly if setup is disabled.
2. Stop the affected MCP process and remove the token from its environment.
3. Inspect `tblapi_audit_log` for the token id, especially non-read actions and
   4xx/5xx responses.
4. Inspect `MCP_AUDIT_LOG` for `tool_call` and `auth_failure` events.
5. Check FennecDash activity for unexpected project writes.
6. Rotate the FennecDash application encryption key only if it may have leaked;
   this has broad impact and is not part of normal token rotation.
7. Review approvals in `tblapi_approvals` for unexpected `used` rows.
8. Issue a new token with the minimum risk and scopes, then update the client.

If `MCP_SETUP_KEY` leaked, remove it from the environment immediately. Setup
endpoints are disabled when it is empty.

## 12. Security checklist

- [ ] `FENNEDASH_BASE_URL` uses HTTPS outside loopback.
- [ ] `MCP_SETUP_KEY` is set only during installation and removed afterwards.
- [ ] Tokens are per-client, minimum risk, optionally scoped and expiring.
- [ ] `MCP_ALLOW_WRITE` and `MCP_ALLOW_CRITICAL` are false unless required.
- [ ] `MCP_ALLOW_SQL` is 0 unless a specific diagnostic need exists.
- [ ] HTTP listener is behind TLS and an allowlisted host/origin set.
- [ ] `MCP_AUTH_CACHE_TTL` matches the revocation tolerance.
- [ ] Audit files and database backups are access controlled and retained.
- [ ] `.env` files are untracked and have restrictive filesystem permissions.
- [ ] Dependency updates are reviewed; `npm audit` is run before release.
- [ ] Critical operations have a human approval step outside the model.

## 13. Known limitations

- No OAuth authorization server, consent screen or per-end-user token exchange.
- Bearer tokens are long-lived unless an expiry is set; there is no refresh
  flow.
- Rate limiting is per token per minute; it is not a global quota or billing
  control.
- The backend audit is a database table without tamper-evident signing.
- The MCP audit is a local file; it is not shipped to a SIEM by itself.
- Redaction is pattern- and key-based; novel secret formats may not be caught.
  Treat logs as sensitive regardless.
- The approval flow proves that a valid approval token was presented, not that a
  human reviewed the dry run.
