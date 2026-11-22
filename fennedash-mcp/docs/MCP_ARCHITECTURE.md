# FennecDash MCP Architecture

`fennedash-mcp` is a Node.js 20+ TypeScript server built on the official
`@modelcontextprotocol/sdk`. It exposes FennecDash over MCP stdio and
Streamable HTTP, and talks to FennecDash only through the Agent API described in
[FENNECDASH_ARCHITECTURE.md](FENNECDASH_ARCHITECTURE.md).

## 1. Goals and non-goals

Goals:

- One MCP server for both local desktop agents and shared remote agents.
- Typed, discoverable tools with stable names and structured output.
- Backend-authoritative authorization with an additional local policy gate.
- A complete audit trail with secret redaction.
- Failures that a model can understand and act on.

Non-goals:

- Replacing the FennecDash UI or its permission model.
- A generic SQL client, file transfer service or PHP execution bridge.
- OAuth authorization-server functionality. The server uses FennecDash bearer
  tokens and does not publish OAuth metadata.
- Vendor-specific prompt logic. Any MCP-capable client can use the same tools.

## 2. Components

```
src/
  index.ts                     CLI, config load, transport selection, signal handling
  config/env.ts                zod env parsing, package root discovery, defaults
  server/
    mcp-server.ts              builds one McpServer, wires the registries
    tool-registry.ts           naming/risk validation, MCP registration, audit wrapper
    resource-registry.ts       static resources and URI templates
    prompt-registry.ts         prompt registration and audit
  tools/
    shared.ts                  endpoint helpers and response shaping
    resource-tools.ts          list/get tools generated from RESOURCE_SPECS
    system/ dashboard/ projects/ users/ database/ files/ integrations/
    monitoring/ search/        feature tool modules
  resources/index.ts           system, docs, project and client resources
  prompts/index.ts             diagnose, health, error, report and safe-change prompts
  integrations/fennedash-api/client.ts   HTTP client for /api/v1
  auth/context.ts              /me identity cache and auth failure limiter
  permissions/{risk,policy}.ts risk ranks and the local gate
  security/{errors,redaction}.ts
  audit/audit-logger.ts        JSON Lines audit sink
  transports/stdio.ts          stdio lifecycle
  transports/streamable-http.ts  HTTP server, sessions, CORS and auth
  utils/{logger,request-id}.ts
```

Registries are separated from transports. `buildMcpServer()` is called once for
stdio and once per HTTP session; it does not open a socket or read stdin.

## 3. Startup

`src/index.ts`:

1. Parses `--transport stdio|http`, `--help` and `--version`.
2. Loads `.env` from the package root, then validates the environment with zod.
3. Creates the logger and the audit writer.
4. Creates an `AuthCache`.
5. For stdio: requires `FENNEDASH_API_TOKEN`, validates it with `/api/v1/me`,
   builds the server, connects `StdioServerTransport` and waits for
   `SIGINT`/`SIGTERM` or transport close.
6. For HTTP: starts the listener, authenticates each session with its own
   bearer token and builds one server per session.

Invalid configuration exits with code 1 and a specific message before any
network activity.

## 4. Tool model

### 4.1 Naming

Every tool follows `fennedash_<module>_<action>` in lower snake case.
`fennedash_discover` is the single documented exception: it is the top-level
entry point and is special-cased by the registry validator. Startup fails if a
tool name is malformed, duplicated or inconsistent with its `module`/`action`.

### 4.2 Risk classes

| Class | Annotation hints | Local gate | Backend gate |
| --- | --- | --- | --- |
| READ | `readOnlyHint: true`, `destructiveHint: false`, idempotent | none | token risk >= read + `staff_can(view)` |
| WRITE | not read-only, not destructive, not idempotent | `MCP_ALLOW_WRITE=true` | token risk >= write + `staff_can(create/edit)` |
| CRITICAL | destructive, not idempotent | `MCP_ALLOW_CRITICAL=true` | token risk = critical + `staff_can(edit)` + single-use approval |

Current surface: 55 READ, 3 WRITE (`projects_create`, `projects_update`,
`approvals_request`) and 1 CRITICAL (`system_clear_cache`). The generated
[MCP_TOOL_CATALOG.md](MCP_TOOL_CATALOG.md) is authoritative.

### 4.3 Execution pipeline

For every `tools/call`, the SDK validates the zod input shape, then the registry
wrapper:

1. Creates a `tool_<12 hex>` request id and a timer.
2. Applies `assertToolAllowed()` (local flags plus token risk ceiling).
3. Enforces `MCP_MAX_CALLS_PER_SESSION` when non-zero.
4. Calls the handler, which forwards to the Agent API with the same request id.
5. Redacts the success payload, preserving only `approval_token` because the
   caller needs that capability to finish a critical operation.
6. Writes an audit event with tool, risk, outcome, duration, backend request id
   and a truncated/redacted argument summary.
7. Returns `content[0].text` (pretty JSON) and `structuredContent` with the same
   object.

On failure, the wrapper classifies policy and authorization failures as
`denied`, other failures as `error`, audits both, and returns an MCP result with
`isError: true` and the structured error payload. Models can branch on
`error.code` without parsing prose.

### 4.4 Response contract

Success:

```json
{
  "ok": true,
  "data": {},
  "meta": {},
  "request_id": "tool_ab12cd34ef56"
}
```

Failure:

```json
{
  "ok": false,
  "error": {
    "code": "forbidden",
    "message": "The token owner lacks the \"view\" permission on \"projects\".",
    "details": {},
    "http_status": 403,
    "backend_request_id": "req_...",
    "retryable": false
  },
  "request_id": "req_..."
}
```

`request_id` is the backend id when one exists, otherwise the MCP request id.
The output schema declares `data`, `meta` and `error` optional so both shapes
validate.

### 4.5 Common error codes

| Code | Meaning |
| --- | --- |
| `validation_failed` | Input or backend field validation failed |
| `not_found` | Record or endpoint does not exist |
| `unauthenticated` / `invalid_token` / `token_revoked` / `token_expired` | Token problem |
| `inactive_staff` | Token owner is disabled or not a staff account |
| `forbidden` | `staff_can()` denied the capability |
| `feature_not_in_token_scope` | Token feature scopes exclude the resource |
| `risk_not_permitted` | Token risk ceiling is too low |
| `rate_limited` | Backend per-token minute limit exceeded |
| `approval_required` | Critical execution without an approval token |
| `approval_invalid` / `_expired` / `_not_pending` / `_wrong_token` / `_action_mismatch` / `_payload_mismatch` | Approval validation failure |
| `write_disabled` / `critical_disabled` | MCP local policy disabled the class |
| `session_limit_reached` | `MCP_MAX_CALLS_PER_SESSION` exhausted |
| `backend_unreachable` / `backend_timeout` | Transport failure, retryable |
| `invalid_response` | Backend returned a non-JSON or malformed body |
| `sql_disabled` / `sql_not_allowed` | Guarded SQL is off or rejected |
| `internal_error` | Unexpected server error |

## 5. Resources

Resources are read-only MCP context. Audit events of type `resource_read` are
written for every read.

Static resources (8):

- `fennedash://system/overview` - live status, info and health combined.
- `fennedash://system/capabilities` - supported operations, limits, unsupported
  features.
- `fennedash://system/modules` - module registry.
- `fennedash://system/permissions` - effective identity and permissions.
- `fennedash://catalog/resources` - live API resource catalog.
- `fennedash://docs/tool-catalog` - this package's generated tool catalog.
- `fennedash://docs/architecture` - this document.
- `fennedash://docs/security` - the security model.

Templates (2):

- `fennedash://projects/{id}`
- `fennedash://clients/{id}`

`resources/list` includes up to 50 instances of each template for discovery. If
enumeration fails because of permissions or a backend hiccup, the template still
appears and the concrete `resources/read` returns the real error. Template URIs
must contain a numeric id.

## 6. Prompts

| Prompt | Purpose |
| --- | --- |
| `diagnose_project` | Evidence-first project diagnosis with billing and next actions |
| `platform_health` | Health, diagnostics, errors and module review |
| `investigate_errors` | Root-cause workflow for a symptom |
| `project_report` | Client/internal/executive report grounded in live data |
| `safe_change` | Verify, plan, authorize, execute, verify for writes |

Prompts never call tools themselves. They produce instructions and require the
client model to call the named tools.

## 7. Transports

### 7.1 stdio

- JSON-RPC over stdin/stdout; logs go to stderr only.
- One process, one identity from `FENNEDASH_API_TOKEN`.
- The token is validated at startup and each tool call is authorized again by
  the backend.
- `SIGINT`/`SIGTERM` close the server and write a `server_stop` audit event.

### 7.2 Streamable HTTP

- Endpoint: `POST|GET|DELETE /mcp`. Health probe: `GET /healthz`.
- Each client sends `Authorization: Bearer <FennecDash token>`.
- The first POST must be `initialize` and must not carry a session id. The server
  authenticates before parsing the body, creates a session and returns
  `Mcp-Session-Id`.
- Later requests must send that header and the same token. A different token for
  the same session returns 403.
- Sessions are stateful, bound to the SHA-256 hash of the bearer token, and
  expire after `MCP_SESSION_TTL_MINUTES` of inactivity (default 120). A cleanup
  pass runs every minute.
- `DELETE /mcp` terminates the session; later use returns 404 and the client must
  re-initialize.
- A client that changes its token must open a new session.
- Body limit is 4 MiB. Requests time out at 60 seconds at the HTTP layer.
- `MCP_ALLOWED_HOSTS` validates `Host`; `MCP_ALLOWED_ORIGINS` validates `Origin`
  and emits CORS response headers. When bound to loopback with no allowlists,
  only `localhost`, `127.0.0.1` and `::1` host names are accepted.
- A per-process limiter blocks an IP after 20 failed authentications in 60
  seconds.
- Bearer validation results are cached for `MCP_AUTH_CACHE_TTL` seconds
  (default 60). Revocation therefore takes effect within that window for the
  identity lookup, while every tool call still hits the backend, which checks the
  token again immediately.
- Responses use JSON mode (`enableJsonResponse: true`); SSE is still accepted by
  the SDK for GET streams.
- The HTTP transport does not implement OAuth 2.1 discovery or dynamic client
  registration. See [SECURITY_MODEL.md](SECURITY_MODEL.md) before exposing it.

## 8. FennecDash client

`integrations/fennedash-api/client.ts`:

- Joins `FENNEDASH_BASE_URL` + `/api/v1/<action>`.
- Sends `Authorization: Bearer <token>`, `Accept: application/json`,
  `X-Request-Id` and a `fennedash-mcp/<version>` user agent.
- Encodes query parameters, including nested filter objects.
- Applies `MCP_REQUEST_TIMEOUT_MS` with `AbortSignal`.
- Rejects HTTP redirects (`redirect: 'error'`) so a token is never forwarded to
  an unexpected host.
- Maps non-2xx envelopes into `McpToolError` with code, message, details, HTTP
  status and backend request id.
- Distinguishes unreachable, timeout, invalid JSON and invalid envelope.
- Redacts tokens from error messages before they reach a model.
- Exposes a narrow `FennecDashApi` interface so tests can inject a fake client.

The client never follows redirects. Deploy FennecDash on the exact base URL you
configure; a redirect is treated as an unreachable backend.

## 9. Audit and redaction

The MCP audit file is JSON Lines (`MCP_AUDIT_LOG`, default
`./logs/audit.jsonl`). Events include `server_start`, `server_stop`,
`session_open`, `session_close`, `auth_failure`, `tool_call`, `resource_read`
and `prompt_get`. Sensitive keys are replaced with `[redacted]`; token-shaped
strings, bearer headers and JWTs are scrubbed. Writes are serialized to avoid
interleaved lines.

The FennecDash backend writes a second, independent audit row in
`tblapi_audit_log` for every Agent API call. Either trail can be used to
reconstruct activity, and both redact secrets.

## 10. Failure behavior

- Startup fails closed: no token for stdio, invalid URL, invalid boolean or
  integer, or an unreachable backend stops the process.
- Tool failures never crash the session; they return `isError`.
- HTTP authentication failures are audited and rate limited; backend outages
  return 502 instead of an invalid-token 401.
- A malformed HTTP body returns JSON-RPC `-32700`; a non-initialize first request
  returns `-32000`; an unknown session returns 404.
- If a tool handler throws a non-`McpToolError`, the model sees a generic
  `internal_error` while the real cause is kept server-side.

## 11. Extension points

- Add a read tool: extend the feature module, add the matching Agent API route,
  add the permission mapping in `scripts/generate-catalog.ts`, add tests.
- Add a resource: add a `ResourceDefinition` in `src/resources/index.ts`.
- Add a prompt: add a `PromptDefinition` in `src/prompts/index.ts`.
- Add a transport: implement an adapter that connects the same `McpServer`; do
  not put business logic in the transport.
- Add a write: define validation, risk, permission, audit and read-back. Add a
  dry-run when the operation is destructive.

## 12. Verification

```bash
npm run build
npm test                       # unit + fake-backend integration
FENNEDASH_E2E=1 npx vitest run tests/integration/real-backend.test.ts
npm run mcp:doctor             # live backend, auth, MCP handshake, tool call
npm run docs:catalog -- --check
```

The test suite covers startup, both transports, handshake, discovery, execution,
validation, authentication, authorization, errors, pagination, audit, redaction,
critical approval, backend connectivity, client configuration examples and the
opt-in live backend path.
