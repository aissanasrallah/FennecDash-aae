# Configuration Reference

All settings are read from environment variables and `.env` (next to `package.json`). Process environment takes precedence over `.env`.

## Required

| Variable | Description |
|----------|-------------|
| `FENNEDASH_BASE_URL` | Base URL of your FennecDash CRM (e.g. `https://crm.example.com`). Trailing `/api` or `/api/v1` is accepted and stripped. |
| `FENNEDASH_API_TOKEN` | Bearer token issued by `POST /api/setup/token`. Required for **stdio transport**; ignored for HTTP transport (clients send their own). |

## Transport

| Variable | Default | Description |
|----------|---------|-------------|
| `MCP_TRANSPORT` | `stdio` | `stdio` or `http` |
| `MCP_HTTP_HOST` | `127.0.0.1` | Bind address for HTTP transport. Use `127.0.0.1` behind a reverse proxy. |
| `MCP_HTTP_PORT` | `8765` | Port for HTTP transport. |
| `MCP_ALLOWED_ORIGINS` | *(empty)* | Comma-separated `Origin` headers allowed from browser clients. Empty = no CORS headers added. Production must list exact origins. |
| `MCP_ALLOWED_HOSTS` | *(empty)* | Comma-separated `Host` headers allowed. Empty = any host when bound beyond loopback, or loopback names when host is `127.0.0.1`, `::1`, `localhost`. Production must list the public hostname. |

## Authorization Ceilings

| Variable | Default | Description |
|----------|---------|-------------|
| `MCP_ALLOW_WRITE` | `false` | Enable WRITE tools (project create/update). Still requires the token's `risk_max` ≥ `write` and FennecDash `create`/`edit` permission. |
| `MCP_ALLOW_CRITICAL` | `false` | Enable CRITICAL tools (`fennedash_system_clear_cache`). Requires `MCP_ALLOW_WRITE=true`, a dry run, and a single-use approval token. |

## Limits

| Variable | Default | Description |
|----------|---------|-------------|
| `MCP_REQUEST_TIMEOUT_MS` | `30000` | Per-request timeout to the Agent API (1s–5min). |
| `MCP_MAX_CALLS_PER_SESSION` | `0` | Max tool calls per MCP session (0 = unlimited). |
| `MCP_SESSION_TTL_MINUTES` | `120` | Idle HTTP session lifetime (1–10080 min). |
| `MCP_MAX_BODY_BYTES` | `4194304` (4 MiB) | Max request body size for HTTP transport (64 KiB–16 MiB). |
| `MCP_MAX_SESSIONS` | `100` | Max concurrent HTTP sessions (1–10000). Excess evicts LRU session. |
| `MCP_READY_CACHE_MS` | `5000` | `/readyz` response cache lifetime (0–60000 ms). |
| `MCP_SHUTDOWN_TIMEOUT_MS` | `10000` | Graceful shutdown deadline (1s–60s). |

## Observability

| Variable | Default | Description |
|----------|---------|-------------|
| `MCP_AUDIT_LOG` | `./logs/audit.jsonl` | JSON Lines audit trail path. Empty = disabled. |
| `MCP_AUDIT_MAX_BYTES` | `10485760` (10 MiB) | Rotate audit log when it exceeds this size (0 = no rotation). |
| `MCP_LOG_LEVEL` | `info` | `debug`, `info`, `warn`, `error`. |
| `MCP_AUTH_CACHE_TTL` | `60` | Bearer token validation cache TTL in seconds (0 = disabled, max 3600). |

## Production Hardening

| Variable | Default | Description |
|----------|---------|-------------|
| `MCP_ENV` | `development` | `development` or `production`. Production enables config validation (fail-closed). |
| `MCP_PUBLIC_BASE_URL` | *(empty)* | Public HTTPS URL of the MCP endpoint (e.g. `https://mcp.example.com`). Required in production with HTTP transport. |
| `MCP_TRUST_PROXY` | `false` | Trust `X-Forwarded-For` / `X-Forwarded-Host` from a reverse proxy for client IP and host checks. |

## Quick Reference by Transport

### stdio (local agents)

```dotenv
FENNEDASH_BASE_URL=https://crm.example.com
FENNEDASH_API_TOKEN=fdm_...
MCP_TRANSPORT=stdio
MCP_ALLOW_WRITE=true
MCP_AUDIT_LOG=./logs/audit.jsonl
```

### HTTP (remote agents)

```dotenv
FENNEDASH_BASE_URL=https://crm.example.com
FENNEDASH_API_TOKEN=
MCP_TRANSPORT=http
MCP_HTTP_HOST=127.0.0.1
MCP_HTTP_PORT=8765
MCP_ALLOWED_ORIGINS=https://opencode.example.com,https://claude.example.com
MCP_ALLOWED_HOSTS=mcp.example.com
MCP_PUBLIC_BASE_URL=https://mcp.example.com
MCP_TRUST_PROXY=true
MCP_ALLOW_WRITE=true
MCP_ENV=production
MCP_AUDIT_LOG=/var/log/fennedash-mcp/audit.jsonl
MCP_AUDIT_MAX_BYTES=10485760
```

## Validation

Run `npm run mcp:doctor` or start the server — it parses and validates all settings at startup. In production mode, invalid config throws before the server listens.