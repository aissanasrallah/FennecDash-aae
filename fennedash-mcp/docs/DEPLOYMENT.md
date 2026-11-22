# Production Deployment Guide

This document covers deploying FennecDash MCP in production with the Streamable HTTP transport.

## Prerequisites

- Node.js 20+ (LTS recommended)
- A FennecDash instance accessible via HTTPS
- A reverse proxy (nginx, Caddy, Traefik) for TLS termination
- A process manager (systemd, PM2, Docker) for the MCP server

## Architecture Overview

```
┌──────────────┐     HTTPS      ┌──────────────────┐     HTTP      ┌────────────────┐
│  OpenCode /  │ ──────────────▶ │  Reverse Proxy   │ ─────────────▶ │  FennecDash    │
│  Claude Code │                 │  (TLS + CORS)    │   (loopback)   │  MCP Server    │
└──────────────┘                 └──────────────────┘                └────────────────┘
                                                                  │        ▲
                                                                  │        │
                                                                  ▼        │
                                                           ┌────────────────┐
                                                           │  FennecDash    │
                                                           │  Agent API     │
                                                           │  (HTTPS)       │
                                                           └────────────────┘
```

The MCP server runs on **loopback only** (`127.0.0.1`). The reverse proxy handles:
- TLS termination
- CORS headers (if needed)
- Rate limiting (optional, in addition to MCP's built-in)
- Health/readiness probes

## Environment Variables

Create a `.env` file next to `package.json`:

```bash
# FennecDash connection (required)
FENNEDASH_BASE_URL=https://crm.example.com
FENNEDASH_API_TOKEN=fdm_...  # only for stdio; HTTP clients send their own

# Transport
MCP_TRANSPORT=http
MCP_HTTP_HOST=127.0.0.1
MCP_HTTP_PORT=8765

# Production hardening (required when MCP_ENV=production)
MCP_ENV=production
MCP_PUBLIC_BASE_URL=https://mcp.example.com
MCP_ALLOWED_ORIGINS=https://opencode.example.com,https://claude.example.com
MCP_ALLOWED_HOSTS=mcp.example.com
MCP_TRUST_PROXY=true

# Authorization ceilings
MCP_ALLOW_WRITE=true
MCP_ALLOW_CRITICAL=false  # set true only if cache_clear is needed

# Limits
MCP_MAX_BODY_BYTES=4194304
MCP_MAX_SESSIONS=100
MCP_READY_CACHE_MS=5000
MCP_SHUTDOWN_TIMEOUT_MS=10000
MCP_REQUEST_TIMEOUT_MS=30000
MCP_MAX_CALLS_PER_SESSION=0
MCP_SESSION_TTL_MINUTES=120

# Observability
MCP_AUDIT_LOG=/var/log/fennedash-mcp/audit.jsonl
MCP_AUDIT_MAX_BYTES=10485760
MCP_LOG_LEVEL=info
MCP_AUTH_CACHE_TTL=60
```

## Reverse Proxy Configuration

### nginx

```nginx
upstream fennedash_mcp {
    server 127.0.0.1:8765;
    keepalive 32;
}

server {
    listen 443 ssl http2;
    server_name mcp.example.com;

    ssl_certificate /etc/letsencrypt/live/mcp.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/mcp.example.com/privkey.pem;

    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header Referrer-Policy no-referrer;

    location /mcp {
        proxy_pass http://fennedash_mcp;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_cache off;
        proxy_read_timeout 65s;
        proxy_send_timeout 60s;
    }

    location /healthz {
        proxy_pass http://fennedash_mcp;
        access_log off;
    }

    location /readyz {
        proxy_pass http://fennedash_mcp;
        access_log off;
    }
}
```

### Caddy

```caddy
mcp.example.com {
    reverse_proxy 127.0.0.1:8765 {
        header_up X-Forwarded-For {http.request.header.X-Forwarded-For}
        header_up X-Forwarded-Proto {http.request.scheme}
        header_up X-Forwarded-Host {http.request.host}
    }
    header {
        X-Content-Type-Options nosniff
        X-Frame-Options DENY
        Referrer-Policy no-referrer
    }
}
```

## Process Management

### systemd

```ini
# /etc/systemd/system/fennedash-mcp.service
[Unit]
Description=FennecDash MCP Server
After=network.target

[Service]
Type=simple
User=mcp
WorkingDirectory=/opt/fennedash-mcp
EnvironmentFile=/opt/fennedash-mcp/.env
ExecStart=/usr/bin/node /opt/fennedash-mcp/dist/src/index.js
Restart=on-failure
RestartSec=5
StandardOutput=journal
StandardError=journal
SyslogIdentifier=fennedash-mcp

# Hardening
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/var/log/fennedash-mcp

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now fennedash-mcp
sudo journalctl -u fennedash-mcp -f
```

### PM2

```bash
cd /opt/fennedash-mcp
pm2 start dist/src/index.js --name fennedash-mcp --env production
pm2 save
pm2 startup
```

### Docker

```dockerfile
FROM node:20-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci --omit=dev
COPY dist ./dist
USER node
ENV NODE_ENV=production
CMD ["node", "dist/src/index.js"]
```

```bash
docker build -t fennedash-mcp .
docker run -d \
  --name fennedash-mcp \
  --restart unless-stopped \
  --env-file .env \
  -v /var/log/fennedash-mcp:/app/logs \
  -p 127.0.0.1:8765:8765 \
  fennedash-mcp
```

## Health & Readiness Probes

| Endpoint | Purpose | Expected Response |
|----------|---------|-------------------|
| `GET /healthz` | Liveness — is the process alive? | `200 { "status": "ok", ... }` |
| `GET /readyz` | Readiness — can it serve traffic? | `200 { "status": "ok", "backend": { "ok": true } }` or `200 { "status": "degraded", ... }` |

Configure your orchestrator:
- **Liveness**: `/healthz` — restart container/pod on failure
- **Readiness**: `/readyz` — remove from load balancer on failure

## Rollback Procedure

1. Keep the previous `dist/` directory (or Docker image tag).
2. Swap the deployed version:
   ```bash
   # systemd
   sudo systemctl stop fennedash-mcp
   rsync -a /opt/fennedash-mcp/prev-dist/ /opt/fennedash-mcp/dist/
   sudo systemctl start fennedash-mcp
   
   # Docker
   docker tag fennedash-mcp:latest fennedash-mcp:rollback
   docker tag fennedash-mcp:v1.2.3 fennedash-mcp:latest
   docker restart fennedash-mcp
   ```
3. Verify `/healthz` and `/readyz` return healthy.
4. Check audit log for clean startup.

## Token Management in Production

- **Never** commit tokens to git or store in CI variables.
- Use your secret manager (HashiCorp Vault, AWS Secrets Manager, 1Password CLI, etc.).
- Issue one token per agent/developer with the minimum required `risk_max` and `scopes`.
- Rotate tokens quarterly or on personnel changes using `POST /api/setup/rotate`.
- Revoke immediately on suspected compromise using `POST /api/setup/revoke`.

## Monitoring Checklist

- [ ] Audit log written to persistent storage with rotation
- [ ] `/healthz` monitored (liveness)
- [ ] `/readyz` monitored (readiness)
- [ ] Alert on `auth_failure` audit events
- [ ] Alert on `session_cap_evicted` (indicates capacity pressure)
- [ ] Alert on `backend.unreachable` in `/readyz` payload
- [ ] Disk space for audit logs and Node.js heap

## Troubleshooting

| Symptom | Check |
|---------|-------|
| 502 from reverse proxy | MCP process dead? `systemctl status fennedash-mcp` |
| `/readyz` returns `degraded` | Backend unreachable? Check `FENNEDASH_BASE_URL`, network, TLS |
| CORS errors in browser | `MCP_ALLOWED_ORIGINS` matches client origin exactly |
| 401 on valid token | Token revoked? Clock skew? Check audit log for `auth_failure` |
| High memory | Audit log rotation disabled? `MCP_AUDIT_MAX_BYTES > 0` |
| Sessions dropped | `MCP_MAX_SESSIONS` too low? TTL too short? |

## Security Checklist

- [ ] `MCP_ENV=production`
- [ ] `MCP_PUBLIC_BASE_URL` uses HTTPS
- [ ] `MCP_ALLOWED_ORIGINS` lists exact client origins (no `*`)
- [ ] `MCP_ALLOWED_HOSTS` lists public hostname
- [ ] `MCP_TRUST_PROXY=true` behind reverse proxy
- [ ] Reverse proxy enforces TLS 1.2+
- [ ] Tokens issued with minimal `risk_max` and `scopes`
- [ ] Audit log rotation enabled
- [ ] File permissions: `.env` readable only by `mcp` user
- [ ] Process runs as non-root user
- [ ] Network: MCP server only on loopback; Agent API only on HTTPS