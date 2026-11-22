# Claude Code

Claude Code supports both a local stdio server and a remote Streamable HTTP
server. Use stdio when the MCP process runs on the same machine as Claude Code;
use HTTP when several clients share one MCP listener.

Examples:

- [`examples/claude-code.stdio.json`](examples/claude-code.stdio.json)
- [`examples/claude-code.http.json`](examples/claude-code.http.json)

## Prerequisites

1. `fennedash-mcp` is installed and built:

   ```bash
   cd fennedash-mcp
   npm install
   npm run build
   npm run mcp:doctor
   ```

2. You have a FennecDash token (`fdm_...`) bound to a staff account.
3. Claude Code is installed and can run `node`.

## Option A - stdio (recommended for one machine)

Add the server to your user scope:

```bash
claude mcp add --transport stdio \
  --env FENNEDASH_BASE_URL=https://crm.example.com \
  --env FENNEDASH_API_TOKEN=fdm_REPLACE_WITH_TOKEN \
  fennedash \
  -- node /absolute/path/to/fennedash-mcp/dist/src/index.js --transport stdio
```

On Windows, use a Windows path and, if `node` is not on `PATH`, the full path to
`node.exe`:

```powershell
claude mcp add --transport stdio `
  --env FENNEDASH_BASE_URL=https://crm.example.com `
  --env FENNEDASH_API_TOKEN=fdm_REPLACE_WITH_TOKEN `
  fennedash `
  -- "C:\Program Files\nodejs\node.exe" "C:\path\to\fennedash-mcp\dist\src\index.js" --transport stdio
```

The `--` separator is required: everything after it is passed to the server, not
parsed by Claude Code. Keep at least one option (such as `--transport stdio`)
between `--env` and the server name, otherwise the name is read as another
`KEY=value` pair.

To share the definition with a repository, create `.mcp.json` at the project
root:

```json
{
  "mcpServers": {
    "fennedash": {
      "command": "node",
      "args": [
        "/absolute/path/to/fennedash-mcp/dist/src/index.js",
        "--transport",
        "stdio"
      ],
      "env": {
        "FENNEDASH_BASE_URL": "https://crm.example.com",
        "FENNEDASH_API_TOKEN": "${FENNEDASH_API_TOKEN}"
      }
    }
  }
}
```

Claude Code expands `${VAR}` and `${VAR:-default}` in `command`, `args`, `env`,
`url` and `headers`. Do not commit a literal token in `.mcp.json`.

## Option B - Streamable HTTP

Start the MCP HTTP listener somewhere Claude Code can reach:

```bash
cd fennedash-mcp
MCP_TRANSPORT=http npm run start:http
```

Register it with a header:

```bash
claude mcp add --transport http fennedash https://mcp.example.com/mcp \
  --header "Authorization: Bearer fdm_REPLACE_WITH_TOKEN"
```

Or add it to `.mcp.json`:

```json
{
  "mcpServers": {
    "fennedash": {
      "type": "http",
      "url": "https://mcp.example.com/mcp",
      "headers": {
        "Authorization": "Bearer ${FENNEDASH_API_TOKEN}"
      }
    }
  }
}
```

`type` is required for a `url` entry. Claude Code also accepts
`"type": "streamable-http"` as an alias for `"http"`.

`claude mcp add-json` expects the object **inside** `mcpServers`, not the
wrapper:

```bash
claude mcp add-json fennedash \
  '{"type":"http","url":"https://mcp.example.com/mcp","headers":{"Authorization":"Bearer '"$FENNEDASH_API_TOKEN"'"}}'
```

## Verify

```bash
claude mcp list
claude mcp get fennedash
```

Inside Claude Code, `/mcp` shows the server, its tools and the connection status.
Then ask:

```text
Use fennedash_auth_whoami and tell me which staff account and token the server is using.
```

If the first call succeeds, try `fennedash_discover` for the live tool list and
`fennedash_system_check_health` for backend health.

## Scope and secrets

- `local` (default) stores the server in your user configuration for the current
  project. `--scope user` makes it available everywhere. `--scope project`
  writes `.mcp.json`.
- Project-scoped servers require approval in an interactive session before
  Claude Code uses them.
- With stdio, the token is in the `env` block of the client configuration or in
  `fennedash-mcp/.env`. Protect both files.
- With HTTP, each client sends its own bearer token; `FENNEDASH_API_TOKEN` in the
  server's `.env` is not used.
- A token change on an HTTP session requires a new MCP session. Delete the old
  one from `/mcp` or restart Claude Code.

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| `Failed to connect` | Run the same command manually and read stderr; check the absolute path and `node` availability |
| `MCP server "fennedash" has a "url" but no "type"` | Add `"type": "http"` to the entry |
| `401` on HTTP | Check the header, the token, and that the listener is reachable |
| Tools are missing | Run `claude mcp get fennedash`; the server may have failed during discovery |
| Token appears in `ps` output | Use the JSON `env` block instead of `--env` on the command line |

See [Troubleshooting](../TROUBLESHOOTING.md) for server-side errors.
