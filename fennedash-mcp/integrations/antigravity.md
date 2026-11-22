# Antigravity

Google Antigravity supports stdio, Streamable HTTP and SSE MCP servers through
`mcp_config.json`. The App, CLI and IDE share the same server entry shape.

Examples:

- [`examples/antigravity.stdio.json`](examples/antigravity.stdio.json)
- [`examples/antigravity.http.json`](examples/antigravity.http.json)

## Open the configuration

In the Antigravity App:

1. Open **Customizations** in the sidebar.
2. Select **Installed** and scroll to **MCP Servers**.
3. Use **Add MCP Server** (`+`) to add from the catalog, or **More actions →
   Copy Config File Path** to find and edit the raw `mcp_config.json`.

For a workspace-scoped SDK setup, Antigravity discovers
`.agents/mcp_config.json` in the workspace. The App's **Copy Config File Path**
action is authoritative for the desktop installation.

## Option A - stdio

```json
{
  "mcpServers": {
    "fennedash": {
      "command": "node",
      "args": [
        "C:/path/to/fennedash-mcp/dist/src/index.js",
        "--transport",
        "stdio"
      ],
      "env": {
        "FENNEDASH_BASE_URL": "https://crm.example.com",
        "FENNEDASH_API_TOKEN": "fdm_REPLACE_WITH_TOKEN"
      },
      "timeoutSeconds": 120
    }
  }
}
```

`cwd` is optional. Use it if the server needs a specific working directory; the
compiled entry point itself resolves its package root from its own location.

## Option B - Streamable HTTP

```json
{
  "mcpServers": {
    "fennedash": {
      "serverUrl": "https://mcp.example.com/mcp",
      "headers": {
        "Authorization": "Bearer fdm_REPLACE_WITH_TOKEN"
      },
      "timeoutSeconds": 120
    }
  }
}
```

`url` is accepted as an alias for `serverUrl`. Antigravity also supports OAuth
for remote servers, but `fennedash-mcp` does not publish OAuth metadata. Use the
static bearer header, or an OAuth gateway in front of the server.

Start the listener first:

```bash
cd fennedash-mcp
MCP_TRANSPORT=http npm run start:http
```

## Secrets

Antigravity's configuration reference does not document `${env:...}`
interpolation for `mcp_config.json`. The examples therefore use a literal
placeholder. Treat the file as a secret, or generate it from a template in your
provisioning process. Do not commit it.

If your Antigravity build supports environment interpolation, `${env:NAME}` is
the natural choice, but verify it before relying on it.

## Tool access control

Antigravity permissions accept MCP matchers:

```text
mcp(fennedash/fennedash_projects_list)
mcp(fennedash/*)
mcp(*)
```

Unconfigured MCP tools run in **Ask** mode. Keep the CRITICAL tool
`fennedash_system_clear_cache` in Ask mode and require a human to review the dry
run.

Per-server allowlists are also available:

```json
{
  "mcpServers": {
    "fennedash": {
      "serverUrl": "https://mcp.example.com/mcp",
      "headers": {
        "Authorization": "Bearer fdm_REPLACE_WITH_TOKEN"
      },
      "enabledTools": [
        "fennedash_discover",
        "fennedash_auth_whoami",
        "fennedash_projects_list",
        "fennedash_projects_get"
      ]
    }
  }
}
```

`disabledTools` is applied after `enabledTools`.

## Verify

After saving, restart or reconnect the server from the MCP Servers section, then
ask:

```text
Use fennedash_auth_whoami and report the staff account and token metadata.
```

`fennedash_discover` returns the live tool catalog.

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| Server not listed | Use **Copy Config File Path**, confirm the entry, reconnect |
| `timeoutSeconds` too low | The default is 180 s per call and 300 s to connect; raise it only if needed |
| `401` on HTTP | Check the header and token; confirm the listener is reachable |
| Tools missing | Check `enabledTools` / `disabledTools` and the server status |
| OAuth prompt appears | This server has no OAuth discovery; use the bearer header instead |

See [Troubleshooting](../TROUBLESHOOTING.md) for server-side errors.
