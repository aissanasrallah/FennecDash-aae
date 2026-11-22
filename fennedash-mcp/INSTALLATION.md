# Installation

This guide covers installing and verifying `fennedash-mcp` from source or a release artifact.

## Prerequisites

| Requirement | Version | Notes |
|-------------|---------|-------|
| Node.js | 20+ (LTS) | Developed on Node 24; earlier LTS versions may work but are not tested |
| npm | 10+ | Bundled with Node |
| FennecDash | 3.4+ | With Agent API installed (`/api/v1/ping` returns `ok: true`) |

## From Source (Recommended)

```bash
# Clone and enter
cd fennedash-mcp

# Install dependencies
npm install

# Copy the configuration template
cp .env.example .env

# Edit .env with your FennecDash URL and a valid token
# FENNEDASH_BASE_URL=https://crm.example.com
# FENNEDASH_API_TOKEN=fdm_...

# Build TypeScript
npm run build

# Verify against the live backend
npm run mcp:doctor
```

## Verification Checklist

`npm run mcp:doctor` performs these checks:

1. **Configuration** — all required env vars present and valid
2. **Transport security** — stdio or loopback HTTP; production requires HTTPS
3. **Backend reachable** — `FENNEDASH_BASE_URL` responds within 5s
4. **Agent API version** — `/api/v1/ping` returns `api_version: "v1"`
5. **Authentication** — token is valid, not expired, bound to a staff account
6. **Discovery** — resources, tools and prompts load without error
7. **MCP handshake** — `initialize` / `initialized` completes
8. **Tool call** — `fennedash_system_get_status` returns live data
9. **Data access** — `fennedash_projects_list` returns at least one row
10. **Audit log** — file is writable

All 14 checks must pass. Any failure blocks the server from starting in production mode.

## Running the Server

### Development (source, auto-reload)

```bash
# stdio transport (for local agents like Claude Code, Cursor, OpenCode)
npm run dev

# HTTP transport (for remote agents)
npm run dev:http
```

### Production (compiled)

```bash
# stdio
npm start

# HTTP on 127.0.0.1:8765
npm start:http
```

The server process handles `SIGINT` and `SIGTERM` for graceful shutdown (see [DEPLOYMENT.md](DEPLOYMENT.md) for systemd/PM2/Docker examples).

## MCP Inspector

```bash
npm run inspector
```

Opens the [MCP Inspector](https://github.com/modelcontextprotocol/inspector) against the compiled stdio server for interactive tool testing.

## Optional: Global Install

```bash
npm pack
npm install -g fennedash-mcp-1.0.0.tgz
fennedash-mcp --help
```

The binary reads the same `.env` / environment variables. Use a config directory with `MCP_CONFIG_DIR` if you need multiple instances.

## Common Installation Issues

| Issue | Fix |
|-------|-----|
| `tsc` not found | `npm install` was skipped or failed |
| `FENNEDASH_BASE_URL` invalid | Must be `http://` or `https://` without trailing `/api` |
| `FENNEDASH_API_TOKEN` empty | Required for stdio; issue one via `POST /api/setup/token` |
| `mcp:doctor` fails on auth | Token expired, revoked, or bound to a different CRM |
| `ECONNREFUSED` on HTTP | `MCP_HTTP_PORT` already in use or firewall blocking |
| Build warnings | Ignore; only errors fail the build |

## Next Steps

- [Configuration](CONFIGURATION.md) — all environment variables
- [Security](SECURITY.md) — hardening for production
- [OpenCode Guide](docs/OPENCODE_GUIDE.md) — client-specific setup
- [Deployment](DEPLOYMENT.md) — production deployment with reverse proxy