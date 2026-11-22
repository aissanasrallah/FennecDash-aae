# FennecDash MCP

A production-grade [Model Context Protocol](https://modelcontextprotocol.io)
server that exposes a live FennecDash CRM/ERP to AI agents over **stdio** and
**Streamable HTTP**.

The server is vendor-independent, written in strict TypeScript on the official
`@modelcontextprotocol/sdk`, and talks to FennecDash only through the additive
Agent API in `application/controllers/api/`. It does not read MySQL directly,
load FennecDash PHP code or bypass the CRM permission system.

## What it provides

- **61 tools** for discovery, identity, system health, dashboard, projects,
  clients, leads, invoices, estimates, tasks, tickets, staff, roles, contracts,
  files, AAE orders, activity, logs, audit, storage, integrations, database
  diagnostics, projects writes and critical cache clearing.
- **8 static resources + 2 URI templates** for live system state, catalogs,
  bundled documentation, projects and clients.
- **5 prompts** for project diagnosis, platform health, error investigation,
  project reporting and safe write workflows.
- **Three risk classes** with explicit local and backend gates:
  - READ (55): no data changes.
  - WRITE (3): `MCP_ALLOW_WRITE=true` plus the matching FennecDash permission.
  - CRITICAL (1): `MCP_ALLOW_CRITICAL=true`, a dry run and a single-use
    approval token.
- Structured success and error payloads, correlated request ids, two independent
  audit trails and secret redaction.

The authoritative list is generated from the registries:
[`docs/MCP_TOOL_CATALOG.md`](docs/MCP_TOOL_CATALOG.md).

## Requirements

- Node.js **20+** (developed and verified on Node 24)
- npm 10+
- A reachable FennecDash installation with the Agent API installed
  (`/api/v1/ping` returns `ok: true`)
- A FennecDash API token bound to a staff account

## Quick start

```bash
cd fennedash-mcp
npm install
cp .env.example .env
```

Edit `.env`:

```dotenv
FENNEDASH_BASE_URL=https://crm.example.com
FENNEDASH_API_TOKEN=fdm_...
MCP_TRANSPORT=stdio
MCP_ALLOW_WRITE=false
MCP_ALLOW_CRITICAL=false
```

Build and verify against the live backend:

```bash
npm run build
npm run mcp:doctor
```

Run locally:

```bash
npm run dev            # stdio, via tsx
npm run dev:http       # Streamable HTTP on 127.0.0.1:8765
npm start              # compiled stdio
npm start:http         # compiled HTTP
npm run inspector      # MCP Inspector against the compiled stdio server
```

## Client configuration

Ready-to-copy examples live in [`integrations/`](integrations/):

- [Claude Code](integrations/claude-code.md)
- [Claude Desktop](integrations/claude-desktop.md)
- [Codex](integrations/codex.md)
- [ChatGPT](integrations/chatgpt.md)
- [Antigravity](integrations/antigravity.md)
- [Cursor](integrations/cursor.md)
- [Windsurf](integrations/windsurf.md)

Generic stdio entry:

```json
{
  "mcpServers": {
    "fennedash": {
      "command": "node",
      "args": ["C:/path/to/fennedash-mcp/dist/src/index.js", "--transport", "stdio"],
      "env": {
        "FENNEDASH_BASE_URL": "https://crm.example.com",
        "FENNEDASH_API_TOKEN": "fdm_..."
      }
    }
  }
}
```

## Commands

| Command | What it does |
| --- | --- |
| `npm install` | Install dependencies |
| `npm run build` | Compile TypeScript to `dist/` |
| `npm run dev` | Run the stdio server from source |
| `npm run dev:http` | Run the HTTP server from source |
| `npm start` / `npm run start:http` | Run the compiled server |
| `npm test` | Build, then run unit and integration tests |
| `npm run test:watch` | Vitest watch mode |
| `npm run test:integration` | Integration tests only |
| `FENNEDASH_E2E=1 npx vitest run tests/integration/real-backend.test.ts` | Opt-in live backend test |
| `npm run inspector` | Open MCP Inspector against the compiled server |
| `npm run mcp:doctor` | Validate config, backend, auth, MCP handshake and one tool call |
| `npm run docs:catalog` | Regenerate the tool catalog |
| `npm run docs:catalog -- --check` | Fail if the catalog is stale |

On Windows PowerShell, set the opt-in variable with
`$env:FENNEDASH_E2E='1'` before the Vitest command.

## Security posture

- FennecDash remains authoritative: every request is checked by the Agent API
  with `staff_can()` and the token risk ceiling.
- Tokens are stored as SHA-256 hashes, can expire, can be revoked and can be
  narrowed to feature scopes.
- Critical work is dry-run first and single-use approval bound.
- `MCP_ALLOW_WRITE`, `MCP_ALLOW_CRITICAL` and the token risk ceiling are local
  gates in front of the backend checks.
- Audit logs redact secrets; approval tokens are returned only to the caller that
  must use them.
- The HTTP transport binds sessions to the bearer token, validates host/origin
  allowlists, limits body size and rate-limits failed authentication.
- The SQL tool is disabled unless `MCP_ALLOW_SQL=1` on the FennecDash host and
  is read-only even then.
- No OAuth authorization server or dynamic client registration: clients send a
  FennecDash bearer token, or an OAuth-capable gateway sits in front.

Read [`docs/SECURITY_MODEL.md`](docs/SECURITY_MODEL.md) before exposing the HTTP
transport beyond loopback.

## Documentation

- [Installation](INSTALLATION.md)
- [Configuration](CONFIGURATION.md)
- [Security](SECURITY.md)
- [Troubleshooting](TROUBLESHOOTING.md)
- [Changelog](CHANGELOG.md)
- [OpenCode Guide](docs/OPENCODE_GUIDE.md)
- [Production Deployment](DEPLOYMENT.md)
- [MCP architecture](docs/MCP_ARCHITECTURE.md)
- [FennecDash architecture](docs/FENNECDASH_ARCHITECTURE.md)
- [FennecDash modules](docs/FENNECDASH_MODULES.md)
- [Security model](docs/SECURITY_MODEL.md)
- [Tool catalog](docs/MCP_TOOL_CATALOG.md)

## Limitations

FennecDash has no Agent API for e-mail/WhatsApp sending, file upload/download,
workflow execution, invoice or ticket writes, user management, module
activation, backup/restore or arbitrary PHP. Those operations are intentionally
absent. `fennedash_discover` reports the live capabilities of the connected
installation, including anything unavailable there.

## License

UNLICENSED. This package is part of a private FennecDash deployment.
