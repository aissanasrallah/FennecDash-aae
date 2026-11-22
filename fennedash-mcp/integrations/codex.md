# Codex

Codex CLI, the Codex IDE extension and the ChatGPT desktop app share MCP
configuration for the same Codex host. Configure FennecDash once and it is
available to all three.

Examples:

- [`examples/codex.stdio.toml`](examples/codex.stdio.toml)
- [`examples/codex.http.toml`](examples/codex.http.toml)

## Configuration file

Codex reads `~/.codex/config.toml` by default. In a trusted project you can use
`.codex/config.toml` instead. Each server is a `[mcp_servers.<name>]` table.

## Option A - stdio

### With the CLI

```bash
codex mcp add fennedash \
  --env FENNEDASH_BASE_URL=https://crm.example.com \
  --env FENNEDASH_API_TOKEN=fdm_REPLACE_WITH_TOKEN \
  -- node /absolute/path/to/fennedash-mcp/dist/src/index.js --transport stdio
```

### With `config.toml`

```toml
[mcp_servers.fennedash]
command = "node"
args = ["C:/path/to/fennedash-mcp/dist/src/index.js", "--transport", "stdio"]
env_vars = ["FENNEDASH_BASE_URL", "FENNEDASH_API_TOKEN"]
startup_timeout_sec = 20
tool_timeout_sec = 60

[mcp_servers.fennedash.env]
MCP_ALLOW_WRITE = "false"
MCP_ALLOW_CRITICAL = "false"
```

`env_vars` forwards the named variables from the environment Codex runs in, so
the token does not have to be written into `config.toml`. If you prefer an
explicit map, use:

```toml
[mcp_servers.fennedash.env]
FENNEDASH_BASE_URL = "https://crm.example.com"
FENNEDASH_API_TOKEN = "fdm_REPLACE_WITH_TOKEN"
```

Do not commit a real token in either form.

## Option B - Streamable HTTP

```toml
[mcp_servers.fennedash]
url = "https://mcp.example.com/mcp"
bearer_token_env_var = "FENNEDASH_API_TOKEN"
startup_timeout_sec = 20
tool_timeout_sec = 60
```

Codex reads the token from the named environment variable and sends it as
`Authorization: Bearer ...`. This is the cleanest option for a shared listener:
no secret in `config.toml`.

Start the listener with:

```bash
cd fennedash-mcp
MCP_TRANSPORT=http npm run start:http
```

Do not use `codex mcp login` for this server. That command starts an OAuth flow;
`fennedash-mcp` uses a static FennecDash bearer token and does not publish OAuth
metadata.

## Verify

```bash
codex mcp list
```

In the Codex TUI, `/mcp` shows the connected servers and their tools. Ask:

```text
Use fennedash_auth_whoami and report the staff account and token metadata.
```

Then call `fennedash_discover` to confirm the full tool, resource and prompt
catalog.

## Useful options

| Option | Effect |
| --- | --- |
| `enabled = false` | Keeps the entry but does not connect it |
| `enabled_tools = [...]` | Exposes only the listed FennecDash tools |
| `disabled_tools = [...]` | Hides specific tools |
| `default_tools_approval_mode = "prompt"` | Asks before tool execution |
| `tool_timeout_sec = 60` | Per-tool timeout; the MCP server's own backend timeout defaults to 30 s |

For an unattended agent, an allowlist is safer than exposing all 59 tools:

```toml
[mcp_servers.fennedash]
url = "https://mcp.example.com/mcp"
bearer_token_env_var = "FENNEDASH_API_TOKEN"
enabled_tools = [
  "fennedash_discover",
  "fennedash_auth_whoami",
  "fennedash_projects_list",
  "fennedash_projects_get",
  "fennedash_search_global"
]
```

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| Server not listed | Run `codex mcp list`; check the TOML table name and restart the client |
| `env_vars` value empty | Start Codex from a shell where the variable is set, or use an explicit `env` map |
| `401` on HTTP | Confirm `bearer_token_env_var` names the right variable and the listener is reachable |
| Timeout during startup | Raise `startup_timeout_sec`; check the backend with `npm run mcp:doctor` |
| Tools missing | Check `enabled_tools` / `disabled_tools` and the server's startup logs |

See [Troubleshooting](../TROUBLESHOOTING.md) for FennecDash error codes.
