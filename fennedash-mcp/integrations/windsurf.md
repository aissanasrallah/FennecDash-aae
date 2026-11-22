# Windsurf

Windsurf's Cascade agent supports stdio, Streamable HTTP and SSE MCP servers in
`mcp_config.json`.

Examples:

- [`examples/windsurf.stdio.json`](examples/windsurf.stdio.json)
- [`examples/windsurf.http.json`](examples/windsurf.http.json)

## Where the configuration lives

Windsurf is transitioning its agent configuration to the Devin directory. Use
**Open MCP config file** in Cascade's MCP menu and edit the file it opens; that
is always the authoritative path for your build.

Current documented locations:

| Context | Path |
| --- | --- |
| Cascade / Cortex-managed (Windows) | `%AppData%\devin\mcp_config.json` |
| Cascade / Cortex-managed (macOS, Linux) | `~/.config/devin/mcp_config.json` (or `$XDG_CONFIG_HOME/devin/mcp_config.json`) |
| Legacy editor discovery (stable) | `~/.codeium/windsurf/mcp_config.json` |
| Legacy editor discovery (Next) | `~/.codeium/windsurf-next/mcp_config.json` |

The editor's `chat.mcp.discovery.enabled` setting can discover servers from the
legacy `windsurf` file. That discovery setting does not change the file opened by
Cascade's **Open MCP config file** action. If a server does not appear, confirm
which file your installation actually reads.

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
        "FENNEDASH_API_TOKEN": "${env:FENNEDASH_API_TOKEN}"
      }
    }
  }
}
```

Windsurf expands `${env:NAME}` in `command`, `args`, `env`, `serverUrl`, `url`
and `headers`. It also supports `${file:/path/to/file}` for reading a value from
a file.

## Option B - Streamable HTTP

```json
{
  "mcpServers": {
    "fennedash": {
      "serverUrl": "https://mcp.example.com/mcp",
      "headers": {
        "Authorization": "Bearer ${env:FENNEDASH_API_TOKEN}"
      }
    }
  }
}
```

`url` is accepted as an alias for `serverUrl`. For HTTP servers the URL should
point at the MCP endpoint (`/mcp`), not the health endpoint.

Start the listener first:

```bash
cd fennedash-mcp
MCP_TRANSPORT=http npm run start:http
```

Windsurf supports OAuth for remote servers, but `fennedash-mcp` does not
implement OAuth. Use the static bearer header shown above, or put an OAuth
gateway in front of the server.

## Apply the change

1. Save `mcp_config.json`.
2. Open the MCP menu in Cascade.
3. Press **Refresh**.
4. Open the server and click **+ Add Server** if it is listed but not exposed to
   Cascade.

## Verify

Ask Cascade:

```text
Use fennedash_auth_whoami and report the staff account and token metadata.
```

Then run `fennedash_discover` for the live catalog.

## Team and Enterprise controls

Windsurf admins can allowlist MCP servers by server ID and configuration. Once
any allowlist entry exists, all non-allowlisted servers are blocked for the
team. Matching is case-sensitive and anchored; arguments are matched
individually and the array length must match. `env` values are not regex-matched,
so each user can supply their own token.

For FennecDash, allowlist the exact command, the absolute `dist/src/index.js`
path and the `--transport stdio` arguments, or the exact HTTPS URL for HTTP.

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| Server missing after save | Use **Open MCP config file** to confirm the path, then Refresh |
| Tools missing | Check the Cascade MCP panel and the server status |
| `401` on HTTP | Confirm the token variable is visible to the GUI process |
| `ENOENT` for `node` | Use the full path to `node.exe` |
| Server works in another client but not Windsurf | The two clients may read different config files; copy the entry to the file Windsurf opens |
| Team blocks the server | Ask an admin to allowlist it; once an allowlist exists, everything else is denied |

See [Troubleshooting](../TROUBLESHOOTING.md) for backend errors.
