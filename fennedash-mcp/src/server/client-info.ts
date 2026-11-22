/**
 * Identity of the MCP client that connected to this server, as reported during
 * the protocol initialize handshake. Older clients may omit the version.
 */
export interface McpClientInfo {
  readonly name: string;
  readonly version?: string;
}

export type McpClientInfoProvider = () => McpClientInfo | undefined;
