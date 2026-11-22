# Cursor

Cursor supports stdio, SSE and Streamable HTTP MCP servers. Configure FennecDash
in `mcp.json`; both project and global scopes are available.

Examples:

- [`examples/cursor.stdio.json`](examples/cursor.stdio.json)
- [`examples/cursor.http.json`](examples/cursor.http.json)

## Configuration locations

| Scope | Path |
| --- | --- |
| Project | `.cursor/mcp.json` in the project root |
| Global | `~/.cursor/mcp.json` (`%USERPROFILE%\.cursor\mcp.json` on Windows) |

Cursor also exposes the same entries through **Customize → MCP**.

## Option A - stdio

```json
{
  "mcpServers": {
    "fennedash": {
      "type": "stdio",
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

Cursor expands `${env:NAME}` in `command`, `args`, `env`, `url` and `headers`, so
the token stays in your environment. Other useful variables are
`${workspaceFolder}`, `${userHome}` and `${pathSeparator}`.

`envFile` is available for stdio entries if you prefer a file:

```json
{
  "mcpServers": {
    "fennedash": {
      "type": "stdio",
      "command": "node",
      "args": ["C:/path/to/fennedash-mcp/dist/src/index.js", "--transport", "stdio"],
      "envFile": "C:/path/to/fennedash-mcp/.env"
    }
  }
}
```

## Option B - Streamable HTTP

```json
{
  "mcpServers": {
    "fennedash": {
      "url": "https://mcp.example.com/mcp",
      "headers": {
        "Authorization": "Bearer ${env:FENNEDASH_API_TOKEN}"
      }
    }
  }
}
```

Cursor reads the remote transport from `url`. A remote entry cannot use
`envFile`; use `${env:...}` interpolation or a literal header value.

Start the listener before opening Cursor:

```bash
cd fennedash-mcp
MCP_TRANSPORT=http npm run start:http
```

## Verify

1. Save `mcp.json`.
2. Open **Customize → MCP** and confirm that `fennedash` is enabled and shows
   tools.
3. Ask Cursor to call `fennedash_auth_whoami`.
4. If tools are missing, open the Output panel (`Ctrl+Shift+U` / `Cmd+Shift+U`)
   and select **MCP Logs**.

Cursor asks for approval before using MCP tools by default. The arrow next to a
tool call shows its arguments. This is useful for WRITE operations; review the
arguments before approving.

## Tool access control

Cursor's enterprise MCP allowlist can approve local command patterns and remote
URLs, and restrict which tools run automatically. If your organization uses it:

- Approve the exact `node` command and the absolute path to `dist/src/index.js`.
- Approve the MCP HTTP URL, not only the host.
- Keep `fennedash_system_clear_cache` out of auto-run lists.

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| `fennedash` missing | Validate JSON, check the scope, restart Cursor |
| `MCP error -32000: Connection closed` | Run the stdio command manually and read stderr |
| `401` on HTTP | Check `${env:FENNEDASH_API_TOKEN}` is set in Cursor's environment |
| Tools never appear | Check MCP Logs; the backend may be unreachable |
| Works in terminal but not in Cursor | GUI apps often do not inherit shell variables; set them at the OS level or use `envFile` for stdio |

See [Troubleshooting](../TROUBLESHOOTING.md) for server-side errors.
