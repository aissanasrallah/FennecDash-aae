# OpenCode Configuration for FennecDash MCP

This guide shows how to connect [OpenCode](https://opencode.ai) to a FennecDash MCP server.

## Quick Start

### 1. Local (stdio) — Recommended for Desktop

Add to your `~/.opencode/config.json`:

```json
{
  "mcp": {
    "servers": {
      "fennedash-mcp": {
        "command": "node",
        "args": ["/absolute/path/to/fennedash-mcp/dist/src/index.js"],
        "env": {
          "FENNEDASH_BASE_URL": "https://crm.example.com",
          "FENNEDASH_API_TOKEN": "fdm_...",
          "MCP_TRANSPORT": "stdio",
          "MCP_ALLOW_WRITE": "true",
          "MCP_AUDIT_LOG": "~/.opencode/logs/fennedash-mcp-audit.jsonl"
        }
      }
    }
  }
}
```

**Key points:**
- Use absolute paths — OpenCode does not expand `~` in `args`.
- `FENNEDASH_API_TOKEN` must be a token issued by your FennecDash instance via `POST /api/setup/token` (requires `MCP_SETUP_KEY`).
- `MCP_ALLOW_WRITE=true` enables project create/update; omit or set `false` for read-only.
- The audit log path is relative to your home directory.

### 2. Remote (Streamable HTTP) — For Shared/Agent Clusters

```json
{
  "mcp": {
    "servers": {
      "fennedash-mcp-remote": {
        "url": "https://mcp.example.com/mcp",
        "headers": {
          "Authorization": "Bearer fdm_..."
        },
        "oauth": false
      }
    }
  }
}
```

**Key points:**
- OpenCode sends the `Authorization` header on every request.
- `oauth: false` is required — FennecDash MCP does not implement OAuth; it uses static bearer tokens.
- The server must be deployed with `MCP_TRANSPORT=http`, `MCP_ALLOWED_ORIGINS=https://opencode.example.com`, `MCP_ALLOWED_HOSTS=mcp.example.com`, and `MCP_PUBLIC_BASE_URL=https://mcp.example.com`.
- Each agent/developer gets their own token; never share tokens.

## Token Management

1. **Issue a token** (one-time, requires server admin):
   ```bash
   curl -X POST https://crm.example.com/api/setup/token \
     -H "X-Setup-Key: $MCP_SETUP_KEY" \
     -H "Content-Type: application/json" \
     -d '{"label":"opencode-alice","risk_max":"write","scopes":["projects","clients","dashboard"]}'
   ```

2. **Use the returned `fdm_...` token** in your OpenCode config.

3. **Rotate periodically** (revokes old, issues new):
   ```bash
   curl -X POST https://crm.example.com/api/setup/rotate \
     -H "Authorization: Bearer fdm_old_..." \
     -H "Content-Type: application/json" \
     -d '{"label":"opencode-alice-rotated"}'
   ```

4. **Revoke when done**:
   ```bash
   curl -X POST https://crm.example.com/api/setup/revoke \
     -H "Authorization: Bearer fdm_..." \
     -H "Content-Type: application/json" \
     -d '{"id":123}'
   ```

## Example Workflows

### Discover what's available
```
fennedash_discover
```

### List projects (read)
```
fennedash_projects_list
```

### Create a project (write — needs MCP_ALLOW_WRITE=true)
```
fennedash_projects_create {
  "name": "New Website",
  "clientid": 42,
  "description": "Corporate site redesign",
  "start_date": "2026-01-15",
  "deadline": "2026-03-31"
}
```

### Dry-run a project update (validates without saving)
```
fennedash_projects_update {
  "id": 99,
  "progress": 50,
  "dry_run": true
}
```

### Get client summary (read)
```
fennedash_clients_get_summary { "page": 1, "per_page": 25 }
```

### Get sales report (read)
```
fennedash_reports_get_sales { "from": "2026-01-01", "to": "2026-01-31", "top": 5 }
```

## Common Issues

| Symptom | Cause | Fix |
|---------|-------|-----|
| `401 invalid_token` | Token expired/revoked or wrong CRM | Re-issue token; check `FENNEDASH_BASE_URL` |
| `403 write_disabled` | `MCP_ALLOW_WRITE=false` | Set `MCP_ALLOW_WRITE=true` in config |
| `403 risk_not_permitted` | Token risk is `read` but tool needs `write` | Issue token with `risk_max: "write"` |
| `422 validation_failed` | Missing/invalid field | Read the error `details.field` and fix |
| `429 Too Many Requests` | Rate limit hit | Wait; check `Retry-After` header |
| Connection refused | Server not running / wrong port | Verify server logs; check `MCP_HTTP_PORT` |

## Production Checklist

- [ ] `MCP_ENV=production` on the server
- [ ] `MCP_PUBLIC_BASE_URL=https://mcp.example.com`
- [ ] `MCP_ALLOWED_ORIGINS=https://opencode.example.com` (your OpenCode web URL)
- [ ] `MCP_ALLOWED_HOSTS=mcp.example.com`
- [ ] HTTPS everywhere (TLS terminated at load balancer or in Node)
- [ ] Unique token per agent/developer
- [ ] Audit log rotation enabled (`MCP_AUDIT_MAX_BYTES`)
- [ ] Readiness probe (`/readyz`) wired to your orchestrator
- [ ] Health probe (`/healthz`) monitored

## Further Reading

- [MCP Architecture](MCP_ARCHITECTURE.md)
- [Tool Catalog](MCP_TOOL_CATALOG.md)
- [Security Model](SECURITY_MODEL.md)
- [Production Deployment](DEPLOYMENT.md)