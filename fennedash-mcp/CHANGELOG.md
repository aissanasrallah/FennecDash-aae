# Changelog

All notable changes to `fennedash-mcp` are documented here.

## [Unreleased]

### Added
- **Idempotency support** for write tools (`projects_create`, `projects_update`): auto-generated `Idempotency-Key` header, replay detection, `idempotent_replayed` response metadata.
- **Dry-run mode** for write tools: `dry_run: true` validates payload against backend without persisting.
- **Token rotation** via `POST /api/setup/rotate` (revokes old, issues new with `rotated_from_id` link).
- **Token listing** via `GET /api/setup/tokens` (metadata only, no secrets).
- **Agent identity propagation**: MCP client name/version sent as `X-Mcp-Client` header; `X-Agent-Name`, `X-Agent-Session` for audit correlation.
- **Readiness probe** at `/readyz` (probes backend; returns `degraded` if unreachable).
- **Session cap** (`MCP_MAX_SESSIONS`, default 100) with LRU eviction.
- **Audit log rotation** (`MCP_AUDIT_MAX_BYTES`, default 10 MiB) — rotates to `.1` on size exceed.
- **Configurable body limit** (`MCP_MAX_BODY_BYTES`, default 4 MiB).
- **Graceful shutdown** with timeout (`MCP_SHUTDOWN_TIMEOUT_MS`, default 10s).
- **Proxy-aware client IP** (`MCP_TRUST_PROXY`) for rate limiting and audit.
- **Same-origin CORS default** — no wildcard; production requires explicit `MCP_ALLOWED_ORIGINS`.
- **Production config validation** (`MCP_ENV=production`) — fail-closed on missing HTTPS, allowlists, audit path.
- **New read tools**:
  - `fennedash_clients_get_summary` — paginated client list with balance, status, owner.
  - `fennedash_reports_get_sales` — invoiced/paid/outstanding, invoice status counts, top clients.
- **OpenCode integration guide** (`docs/OPENCODE_GUIDE.md`).
- **Production deployment guide** (`docs/DEPLOYMENT.md`).
- **Installation, Configuration, Security, Troubleshooting** docs.

### Changed
- **Audit log** now includes `clientName`, `clientVersion` from MCP client handshake.
- **Audit log** includes `agent`, `agent_session`, `request_id` from Agent API (backend schema 346).
- **Origin validation** tightened: empty allowlist = same-origin only (was allow-all).
- **Token revocation** is now idempotent (returns `already_revoked: true` on repeat).
- **FennecDashClient** sends `X-Agent-Name`, `X-Agent-Session`, `X-Mcp-Client`, `Idempotency-Key` headers.
- **Tool registry** propagates MCP client info to audit and backend client.
- **Resource/Prompt registries** include client identity in audit events.

### Fixed
- **Idempotency claim bug** — fixed own-insert race using `affected_rows()` instead of `lastInsertId()`.
- **Rate-limit rows** now pruned periodically (was unbounded growth).
- **Setup::status** endpoint now requires `X-Setup-Key` (was public).

### Security
- Removed wildcard CORS default.
- Added security headers: `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer`.
- Production config validation fails closed.
- Token hashes only stored (SHA-256).
- Approval tokens single-use, 5 min TTL, returned only to requesting caller.

### Backend (FennecDash Agent API)
- Migration 346: `api_idempotency` table, `api_tokens.rotated_from_id`, `api_audit_log.agent`, `agent_session`, `request_id`.
- `projects_create`/`projects_update` accept `dry_run` and `idempotency_key`.
- New endpoints: `clients_summary`, `reports_sales`.
- `Setup::rotate`, `Setup::tokens`, stricter token validation.

## [1.0.0] — 2026-11-04

### Initial Release
- Model Context Protocol server for FennecDash CRM/ERP.
- stdio and Streamable HTTP transports.
- 59 tools, 19 resources (8 static + 2 templates × instances), 5 prompts.
- Three risk classes: READ (55), WRITE (3), CRITICAL (1).
- Token-based auth with SHA-256 storage, expiry, revocation, scopes.
- Approval workflow for CRITICAL operations (dry run + single-use token).
- Dual audit trails: MCP server (JSON Lines) + Agent API (MySQL).
- Secret redaction in all outputs and logs.
- Rate limiting, session management, body size limits.
- MCP Inspector integration.
- Client configs for OpenCode, Claude Code, Cursor, Windsurf, Codex, ChatGPT, Antigravity.
- Comprehensive test suite (unit + integration with fake backend).
- Doctor command for live validation.
- Auto-generated tool catalog from live registries.