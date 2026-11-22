# Claude Desktop

Claude Desktop starts local MCP servers from `claude_desktop_config.json`. This
is the supported way to connect it to FennecDash.

Example: [`examples/claude-desktop.stdio.json`](examples/claude-desktop.stdio.json)

## Why stdio

Claude Desktop's custom connectors are remote MCP endpoints added through
**Settings → Connectors**. That dialog handles OAuth or unauthenticated
connectors; it does not provide a field for this server's static FennecDash
bearer token. Because `fennedash-mcp` requires a bearer token and does not
implement OAuth, the reliable path is a local stdio server.

If you must use a remote connector, put an authenticating gateway in front of
`fennedash-mcp`, complete the connector's OAuth (or no-auth) flow at the gateway,
and have the gateway inject the FennecDash bearer token upstream. Do not expose
an unauthenticated `/mcp` listener.

## 1. Build the server

```bash
cd fennedash-mcp
npm install
npm run build
npm run mcp:doctor
```

## 2. Open the configuration file

| Platform | Path |
| --- | --- |
| Windows | `%APPDATA%\Claude\claude_desktop_config.json` |
| macOS | `~/Library/Application Support/Claude/claude_desktop_config.json` |
| Linux | `~/.config/Claude/claude_desktop_config.json` |

In Claude Desktop, **Settings → Developer → Edit Config** opens the same file.

If the file already contains other servers, merge the `fennedash` entry into the
existing `mcpServers` object. Do not replace the whole file.

## 3. Add the server

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
        "FENNEDASH_API_TOKEN": "fdm_REPLACE_WITH_TOKEN",
        "MCP_ALLOW_WRITE": "false",
        "MCP_ALLOW_CRITICAL": "false"
      }
    }
  }
}
```

Notes:

- Use the **absolute** path to `dist/src/index.js`. Claude Desktop starts the
  process from an arbitrary working directory.
- On Windows, JSON paths may use forward slashes (`C:/...`) or escaped
  backslashes (`C:\\...`).
- If `node` is not on Claude Desktop's `PATH`, set `command` to the full path of
  `node.exe`.
- Claude Desktop does not document `${VAR}` expansion for this file, so the token
  is literal. Restrict the file's permissions.
- `fennedash-mcp/.env` is also read by the server. Values in the client `env`
  block win. You can keep the token in `.env` and omit it here, but the client
  file still needs `FENNEDASH_BASE_URL` unless `.env` provides it.

## 4. Restart Claude Desktop

Quit Claude Desktop completely, including the tray or menu-bar process, then
start it again. Opening a new window is not enough; the configuration is read at
startup.

## 5. Verify

Click the tools/connectors indicator in the message box and confirm that
`fennedash` appears with tools. Then ask:

```text
Use fennedash_auth_whoami and tell me which staff account this server is using.
```

If that works, `fennedash_discover` returns the live tool, resource and prompt
list.

## Logs

| Platform | Directory |
| --- | --- |
| Windows | `%APPDATA%\Claude\logs` |
| macOS | `~/Library/Logs/Claude` |
| Linux | `~/.config/Claude/logs` |

`mcp.log` contains connection events. `mcp-server-fennedash.log` contains the
server's stderr output, including startup errors and tool failures.

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| No `fennedash` entry | Validate the JSON, confirm the file path, restart completely |
| Entry exists but tools are missing | Read `mcp-server-fennedash.log`; run the same command manually |
| `Cannot find module ...index.js` | Run `npm run build` and fix the absolute path |
| `FENNEDASH_BASE_URL is required` | Add it to `env` or `fennedash-mcp/.env` |
| `401` / `invalid_token` | Reissue the token and update the file |
| Server exits immediately | Check stderr; the token is validated at startup |
| `EACCES` on Windows | Run Claude Desktop with access to the Node installation, or use the full `node.exe` path |

See [Troubleshooting](../TROUBLESHOOTING.md) for backend and authorization
errors.
