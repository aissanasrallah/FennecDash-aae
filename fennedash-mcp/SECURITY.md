# Security Model

This document describes the security architecture of `fennedash-mcp`. Read it before exposing the HTTP transport beyond loopback.

## Threat Model

| Asset | Threat | Mitigation |
|-------|--------|------------|
| FennecDash data | Token theft | SHA-256 token hashes, expiry, revocation, per-token scopes |
| FennecDash data | Over-privileged agent | Token `risk_max` ceiling, `MCP_ALLOW_WRITE`/`CRITICAL` gates, FennecDash `staff_can()` |
| FennecDash data | Agent prompt injection | All tool output treated as data; redaction; no code execution |
| Audit integrity | Log tampering | Append-only JSON Lines; rotation preserves prior file |
| MCP server | DoS / resource exhaustion | Body limit, session cap, rate-limited auth, TTL cleanup |
| MCP server | CORS misconfiguration | Same-origin default; explicit allowlist in production |
| MCP server | Host header injection | `MCP_ALLOWED_HOSTS` validation |
| Credentials | Leak via logs/ps | Tokens never logged; `FENNEDASH_API_TOKEN` only in stdio `.env` |

## Authentication Flow

```
Client (OpenCode, Claude Code, etc.)
         │
         │ Bearer token (fdm_...)
         ▼
┌─────────────────────────────────────┐
│  MCP Server (HTTP transport)        │
│  1. Validate Host header            │
│  2. Validate Origin header          │
│  3. Extract Bearer token            │
│  4. Rate-limit failed auth by IP    │
│  5. Check cache (TTL)               │
│  6. Call Agent API /me              │
│  7. Bind session to token hash      │
└─────────────────────────────────────┘
         │
         │ Session ID + token hash
         ▼
┌─────────────────────────────────────┐
│  Subsequent requests                │
│  - Must present same token hash     │
│  - Session TTL enforced             │
│  - Cap: MCP_MAX_SESSIONS (evicts LRU)│
└─────────────────────────────────────┘
```

## Authorization

Three independent gates must all pass for a write/critical tool:

1. **MCP config** — `MCP_ALLOW_WRITE` / `MCP_ALLOW_CRITICAL`
2. **Token risk ceiling** — token's `risk_max` ≥ tool's risk class
3. **FennecDash `staff_can()`** — token owner's staff account has the required feature capability

```
┌─────────────┐   ┌─────────────┐   ┌────────────────────┐
│ MCP_ALLOW_  │   │ Token risk  │   │ staff_can(feature, │
│ WRITE/CRIT  │ & │ ≥ tool risk │ & │       capability)  │
└─────────────┘   └─────────────┘   └────────────────────┘
        │               │                    │
        └───────────────┴────────────────────┘
                            │
                            ▼
                    ┌───────────────┐
                    │   ALLOW       │
                    └───────────────┘
```

**Read tools** only need gate 3 (and a valid token).

## Token Management

- **Issue**: `POST /api/setup/token` (requires `X-Setup-Key`)
  - Fields: `label`, `risk_max` (`read`|`write`|`critical`), `scopes` (optional feature allowlist), `expires_at` (optional)
- **Rotate**: `POST /api/setup/rotate` (revokes old, issues new with `rotated_from_id` link)
- **List**: `GET /api/setup/tokens` (metadata only, no token secrets)
- **Revoke**: `POST /api/setup/revoke` (idempotent; `already_revoked` flag on repeat)
- **Storage**: Only SHA-256 hashes stored in `tblapi_tokens.token_hash`

## Critical Operations

Only `fennedash_system_clear_cache` is CRITICAL. It requires:

1. `MCP_ALLOW_CRITICAL=true` (server config)
2. Token `risk_max = critical`
3. **Dry run first**: call with `dry_run=true` → shows `would_delete`, `bytes`, no approval token
4. **Request approval**: `fennedash_approvals_request` with exact action/payload → returns single-use `approval_token` (5 min TTL)
5. **Execute**: call with `dry_run=false` + `approval_token` → approval consumed, action executed
6. **Audit**: every step logged with `actor`, `approval_token` (redacted in logs)

## Audit Trail

Two independent trails:

| Trail | Location | Content |
|-------|----------|---------|
| MCP server | `MCP_AUDIT_LOG` (JSON Lines) | Tool calls, resource reads, prompt gets, auth failures, session open/close, server start/stop. Includes: `requestId`, `sessionId`, `tool/resource/prompt`, `risk`, `actor` (staffId, tokenId, label), `outcome`, `durationMs`, `backendRequestId`, `clientName`, `clientVersion`, redacted `args`. |
| Agent API | `tblapi_audit_log` (MySQL) | Every API request: method, path, status, duration, IP, staff, token, request_id, `agent`, `agent_session`, `payloadHash`. |

**Redaction**: Secrets (tokens, passwords, approval tokens, API keys) are replaced with `[REDACTED]` before writing. Only the caller sees the raw approval token.

## HTTP Transport Hardening

| Control | Setting | Production Requirement |
|---------|---------|------------------------|
| Body limit | `MCP_MAX_BODY_BYTES` | ≤ 4 MiB (default) |
| Session cap | `MCP_MAX_SESSIONS` | ≤ 100 (default) |
| Auth rate limit | 20 failures / 60s per IP | Built-in, non-configurable |
| Host validation | `MCP_ALLOWED_HOSTS` | Must list public hostname |
| Origin validation | `MCP_ALLOWED_ORIGINS` | Must list exact client origins (no `*`) |
| Proxy trust | `MCP_TRUST_PROXY` | `true` behind reverse proxy |
| Security headers | Built-in | `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer`, `Cache-Control: no-store` |
| CORS | Built-in | Only reflects allowed origins; `Vary: Origin` |
| Readiness | `/readyz` | Probes backend; `degraded` if unreachable |

## Client Compatibility

| Client | Transport | Auth | Notes |
|--------|-----------|------|-------|
| OpenCode | stdio, HTTP | Bearer header / env | `${env:VAR}` interpolation; `oauth: false` for HTTP |
| Claude Code | stdio, HTTP | Bearer header / env | `${VAR}` interpolation in `.mcp.json` |
| Google Antigravity | stdio, HTTP | Bearer header | No env interpolation in `mcp_config.json` |
| Cursor | stdio, HTTP | Bearer header / env | Same as OpenCode |
| Windsurf | stdio, HTTP | Bearer header / env | Same as OpenCode |
| Codex | stdio, HTTP | Bearer header / env | Same as OpenCode |
| ChatGPT (web) | HTTP + OAuth gateway | OAuth | Requires external OAuth server; this MCP has no OAuth |

## Production Checklist

- [ ] `MCP_ENV=production`
- [ ] `MCP_PUBLIC_BASE_URL=https://...`
- [ ] `MCP_ALLOWED_ORIGINS` lists exact client origins (no `*`)
- [ ] `MCP_ALLOWED_HOSTS` lists public hostname
- [ ] `MCP_TRUST_PROXY=true` behind reverse proxy
- [ ] Reverse proxy terminates TLS 1.2+
- [ ] Tokens issued with minimal `risk_max` and `scopes`
- [ ] One token per agent/developer
- [ ] Audit log rotation enabled (`MCP_AUDIT_MAX_BYTES > 0`)
- [ ] Audit log on persistent storage with backup
- [ ] `/healthz` and `/readyz` monitored
- [ ] Alert on `auth_failure` audit events
- [ ] Alert on `session_cap_evicted`
- [ ] Process runs as non-root user
- [ ] `.env` readable only by MCP user
- [ ] Network: MCP server loopback only; Agent API HTTPS only

## Unsupported Operations

FennecDash has no Agent API for (and this server intentionally does not implement):
- E-mail / WhatsApp sending
- File upload/download
- Workflow execution
- Invoice/quote/ticket writes
- User/staff management
- Module activation/deactivation
- Backup/restore
- Arbitrary PHP/SQL execution (SELECT only with `MCP_ALLOW_SQL=1`)