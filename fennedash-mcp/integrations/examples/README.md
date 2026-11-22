# Client configuration examples

These files show the exact shape each client expects. They are templates, not a
working deployment.

## Placeholders

| Placeholder | Replace with |
| --- | --- |
| `C:/path/to/fennedash-mcp/dist/src/index.js` | Absolute path to the compiled entry point on the client machine |
| `https://crm.example.com` | Your FennecDash base URL, without `/api` or `/api/v1` |
| `https://mcp.example.com/mcp` | The public HTTPS URL of the MCP Streamable HTTP listener |
| `fdm_REPLACE_WITH_TOKEN` | A real `fdm_...` token, or delete it and use the client's environment expansion |

## Secret handling

- Prefer the client's environment expansion (`${VAR}`, `${env:VAR}`) or
  `bearer_token_env_var` over a literal token.
- Claude Desktop and Antigravity do not document environment expansion for their
  MCP config, so their examples use `fdm_REPLACE_WITH_TOKEN`. Protect those files
  with filesystem permissions.
- Never commit a file that contains a real token.

## Files

| Client | Files |
| --- | --- |
| Claude Code | `claude-code.stdio.json`, `claude-code.http.json` |
| Claude Desktop | `claude-desktop.stdio.json` |
| Codex | `codex.stdio.toml`, `codex.http.toml` |
| Cursor | `cursor.stdio.json`, `cursor.http.json` |
| Windsurf | `windsurf.stdio.json`, `windsurf.http.json` |
| Antigravity | `antigravity.stdio.json`, `antigravity.http.json` |
| ChatGPT web | `chatgpt.connector.json` (gateway descriptor; ChatGPT has no local config file) |
