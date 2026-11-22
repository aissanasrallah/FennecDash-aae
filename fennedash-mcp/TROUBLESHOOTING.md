# Troubleshooting

Common issues, diagnostics and fixes for `fennedash-mcp`.

## Quick Diagnostics

Run the doctor — it validates config, backend, auth, MCP handshake and a live tool call:

```bash
npm run mcp:doctor
```

Exit code `0` = all 14 checks pass. Non-zero = first failure printed.

## Server-Side Errors

| Error / Symptom | Cause | Fix |
|-----------------|-------|-----|
| `Invalid FENNEDASH_BASE_URL` | Protocol not http/https, or URL malformed | Fix `.env`; must be `https://crm.example.com` (no trailing `/api`) |
| `FENNEDASH_API_TOKEN is required` | Empty token for stdio transport | Issue token via `POST /api/setup/token` or use HTTP transport |
| `Production configuration is invalid` | `MCP_ENV=production` but required prod vars missing | Set `MCP_PUBLIC_BASE_URL`, `MCP_ALLOWED_ORIGINS`, `MCP_ALLOWED_HOSTS`; ensure HTTPS |
| `MCP_ALLOWED_ORIGINS must not contain "*"` | Wildcard origin in production | List exact client origins (e.g. `https://opencode.example.com`) |
| `EADDRINUSE` on start | Port already in use | Change `MCP_HTTP_PORT` or stop conflicting process |
| `ECONNREFUSED` to backend | FennecDash unreachable | Check `FENNEDASH_BASE_URL`, network, firewall, TLS cert |
| `backend_unreachable` / `backend_timeout` | Agent API not responding | Check FennecDash logs, PHP-FPM, MariaDB, web server |
| `invalid_token` / `401` | Token expired, revoked, or wrong CRM | Re-issue token; verify `FENNEDASH_BASE_URL` matches token's CRM |
| `risk_not_permitted` | Token risk < tool risk | Issue token with higher `risk_max` (`write` or `critical`) |
| `write_disabled` | `MCP_ALLOW_WRITE=false` | Set `MCP_ALLOW_WRITE=true` in server config |
| `critical_disabled` | `MCP_ALLOW_CRITICAL=false` | Set `MCP_ALLOW_CRITICAL=true` (also needs `MCP_ALLOW_WRITE=true`) |
| `approval_required` | CRITICAL tool called without approval | Run dry run → request approval → execute with `approval_token` |
| `approval_invalid` | Approval token wrong/expired/used | Request new approval; tokens are single-use, 5 min TTL |
| `idempotency_conflict` | Same key, different payload | Use a new idempotency key; keys must match the exact request body |
| `validation_failed` | Input validation error | Read `details.field` and fix the input |
| `payload_too_large` | Request body > `MCP_MAX_BODY_BYTES` | Reduce payload or increase limit (max 16 MiB) |
| `Too many failed authentication attempts` | Rate limit hit | Wait 60s; check for credential stuffing in audit log |
| `Session not found or expired` | Session TTL exceeded or evicted | Start a new MCP session (re-initialize) |
| `This session belongs to a different API token` | Token changed mid-session | Start a new session with the new token |
| `Session cap evicted` | `MCP_MAX_SESSIONS` exceeded | Increase limit or investigate connection leak |
| Audit log rotation failed | Permission / disk full | Check `MCP_AUDIT_LOG` path writable; disk space |
| High memory usage | Audit log not rotating | Set `MCP_AUDIT_MAX_BYTES > 0` (default 10 MiB) |

## Client-Side Errors

### OpenCode

| Symptom | Fix |
|---------|-----|
| `Failed to connect` | Check absolute path to `dist/src/index.js` and `node` availability |
| `MCP server has "url" but no "type"` | Add `"type": "http"` to HTTP entry |
| `401` on HTTP | Check `Authorization` header, token, and listener reachability |
| Tools missing | Run `opencode mcp list`; server may have failed during discovery |
| Token in `ps` output | Use `env` block in config instead of `--env` on CLI |

### Claude Code

| Symptom | Fix |
|---------|-----|
| `Failed to connect` | Check absolute path and `node`; use `--` separator |
| `MCP server "fennedash" has a "url" but no "type"` | Add `"type": "http"` |
| `401` on HTTP | Check header, token, listener reachability |
| Tools missing | `claude mcp get fennedash`; server may have failed discovery |

### Antigravity

| Symptom | Fix |
|---------|-----|
| Server not listed | Use **Copy Config File Path**, confirm entry, reconnect |
| `timeoutSeconds` too low | Default 180s/call, 300s connect; raise only if needed |
| `401` on HTTP | Check header and token; confirm listener reachable |
| Tools missing | Check `enabledTools` / `disabledTools` and server status |
| OAuth prompt appears | This server has no OAuth; use bearer header |

## Debugging Steps

### 1. Enable Debug Logging

```bash
MCP_LOG_LEVEL=debug npm run dev
# or
MCP_LOG_LEVEL=debug node dist/src/index.js
```

### 2. Inspect Audit Log

```bash
tail -f logs/audit.jsonl | jq .
```

Each line is a JSON object with `type`, `requestId`, `sessionId`, `tool`, `outcome`, `durationMs`, `errorCode`, `backendRequestId`, `args` (redacted).

### 3. Test Agent API Directly

```bash
# Ping
curl -H "Authorization: Bearer fdm_..." https://crm.example.com/api/v1/ping

# Whoami
curl -H "Authorization: Bearer fdm_..." https://crm.example.com/api/v1/me

# List tools via MCP
echo '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' | \
  node dist/src/index.js --transport stdio
```

### 4. Check HTTP Transport

```bash
# Health
curl http://127.0.0.1:8765/healthz

# Readiness
curl http://127.0.0.1:8765/readyz

# Initialize (requires token)
curl -X POST http://127.0.0.1:8765/mcp \
  -H "Authorization: Bearer fdm_..." \
  -H "Content-Type: application/json" \
  -H "Accept: application/json, text/event-stream" \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0.0"}}}'
```

### 5. Run Integration Tests

```bash
# Unit + integration (fake backend)
npm test

# Live backend (opt-in)
FENNEDASH_E2E=1 npx vitest run tests/integration/real-backend.test.ts
```

## Logs Location

| Transport | Logs |
|-----------|------|
| stdio (dev) | Terminal stderr |
| stdio (prod) | systemd journal / PM2 logs / Docker stdout |
| HTTP | systemd journal / PM2 logs / Docker stdout + `MCP_AUDIT_LOG` |

## Getting Help

1. Run `npm run mcp:doctor` and share the output
2. Share relevant audit log lines (redacted)
3. Share server config (redact tokens)
4. Specify: client (OpenCode/Claude Code/...), transport, FennecDash version