# ChatGPT

ChatGPT has two relevant surfaces:

- **ChatGPT web** connects to remote MCP servers through plugins/custom
  connectors. It requires a public HTTPS endpoint and uses OAuth 2.1 (or no
  authentication) for authenticated servers.
- **The ChatGPT desktop app** uses the Codex MCP host and shares its
  configuration, so it can start a local stdio server.

`fennedash-mcp` uses a static FennecDash bearer token and intentionally does not
implement OAuth. That means:

| Surface | Direct connection | Recommended path |
| --- | --- | --- |
| ChatGPT web | No, unless you add an OAuth gateway | Gateway in front of `fennedash-mcp` |
| ChatGPT desktop app | Yes, through the Codex MCP config | Follow [codex.md](codex.md) and use stdio |
| Codex CLI / IDE | Yes | Follow [codex.md](codex.md) |

Example gateway descriptor: [`examples/chatgpt.connector.json`](examples/chatgpt.connector.json)

## Option A - ChatGPT desktop app (stdio)

The ChatGPT desktop app, Codex CLI and the Codex IDE extension share MCP
configuration for the same Codex host. If your desktop build exposes local MCP
servers, add FennecDash to `~/.codex/config.toml`:

```toml
[mcp_servers.fennedash]
command = "node"
args = ["C:/path/to/fennedash-mcp/dist/src/index.js", "--transport", "stdio"]
env_vars = ["FENNEDASH_BASE_URL", "FENNEDASH_API_TOKEN"]
```

Restart the desktop app and confirm that `fennedash` appears in its MCP or tools
settings. See [codex.md](codex.md) for the full configuration and verification
steps.

## Option B - ChatGPT web through an OAuth gateway

ChatGPT web cannot send a static `Authorization: Bearer fdm_...` header. The
authenticated connector flow is OAuth 2.1: ChatGPT discovers protected resource
metadata, registers or identifies itself, runs authorization-code + PKCE, and
sends the resulting access token to your MCP server. ChatGPT does not support
custom API keys or client-credentials grants for this flow.

To connect without weakening the server:

```
ChatGPT web
  -> HTTPS + OAuth 2.1
     -> gateway (OAuth resource server + authorization server integration)
        -> HTTPS + FennecDash bearer token
           -> fennedash-mcp Streamable HTTP
              -> FennecDash Agent API
```

The gateway must:

1. Serve `/.well-known/oauth-protected-resource` with the canonical `resource`
   and the authorization server URL.
2. Publish OAuth metadata (or use an identity provider that does) with an
   authorization endpoint, token endpoint, PKCE `S256`, and the client
   registration method ChatGPT supports (CIMD or DCR).
3. Accept the ChatGPT redirect URI shown in the connector settings.
4. Validate the access token on every request and enforce its scopes.
5. Inject the FennecDash bearer token upstream to `fennedash-mcp`. Never send
   the ChatGPT token to FennecDash.
6. Keep `fennedash-mcp` on a private network; only the gateway is public.

Do not disable authentication on a public `/mcp` listener to make ChatGPT work.

### Add the connector

1. Build and start the MCP HTTP listener behind the gateway:

   ```bash
   cd fennedash-mcp
   MCP_TRANSPORT=http MCP_HTTP_HOST=127.0.0.1 npm run start:http
   ```

2. Verify the gateway and server with MCP Inspector first:

   ```bash
   npx @modelcontextprotocol/inspector@latest
   ```

   Connect to the gateway URL, list tools, call `fennedash_auth_whoami`, and
   check that an unauthenticated request is rejected.

3. In ChatGPT, open [ChatGPT Plugins](https://chatgpt.com/plugins), select the
   plus button, then **Add custom MCP server**.
4. Enter a name and description.
5. Under **Connection**, choose the public HTTPS endpoint, including `/mcp`.
   **Secure MCP Tunnel** is an alternative for a private server; it does not
   remove the authentication requirement.
6. Configure authentication, review the risk warning and continue.
7. Select **Create as a plugin**, then review the discovered tools.
8. Install the plugin and start a new conversation. Type `@` and select the
   plugin before asking a question.

Refresh the connector metadata after changing tools, schemas or annotations.

## Tool exposure

ChatGPT sees every tool the server advertises. If 59 tools is too many for your
workspace:

- Run a separate `fennedash-mcp` instance with a read-only token and
  `MCP_ALLOW_WRITE=false`, `MCP_ALLOW_CRITICAL=false`.
- Use the gateway to filter the tool list, or expose only read tools.
- Keep `fennedash_system_clear_cache` unavailable unless a human approval process
  exists outside ChatGPT.

The server's local policy flags and the FennecDash token risk ceiling both apply
regardless of what the client displays.

## Verify

Ask:

```text
Use fennedash_auth_whoami and report the staff account and token metadata.
```

Then `fennedash_discover` for the live catalog and
`fennedash_system_check_health` for backend health.

If ChatGPT cannot connect, test the same URL in MCP Inspector. Resolve transport,
initialization, schema and authentication errors there before changing the
connector.

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| Connector cannot reach the server | Public HTTPS URL, valid certificate, `/mcp` path, gateway health |
| OAuth loop or `invalid_client` | Check protected resource metadata, redirect URI and client registration method |
| Tools missing | Refresh connector metadata; check the gateway's tool filter |
| `401` from FennecDash | The gateway is not injecting the upstream bearer token |
| Desktop app has no local MCP option | Use the ChatGPT web gateway path, or Codex CLI |
| Tool call rejected by workspace policy | Ask the workspace admin about plugin and MCP permissions |

See [Troubleshooting](../TROUBLESHOOTING.md) and
[Security model](../docs/SECURITY_MODEL.md) before exposing the listener.
