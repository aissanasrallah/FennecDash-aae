import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { CallToolResult, ToolAnnotations } from '@modelcontextprotocol/sdk/types.js';
import { z, type ZodRawShape } from 'zod';

import type { AuditLogger } from '../audit/audit-logger.js';
import type { AuthIdentity } from '../auth/context.js';
import type { AppConfig } from '../config/env.js';
import type { FennecDashApi } from '../integrations/fennedash-api/client.js';
import { assertToolAllowed } from '../permissions/policy.js';
import type { Risk } from '../permissions/risk.js';
import { McpToolError, normalizeError, toErrorResult } from '../security/errors.js';
import { redactValue, summarizeForAudit } from '../security/redaction.js';
import { newRequestId } from '../utils/request-id.js';
import type { McpClientInfoProvider } from './client-info.js';

export interface ToolMeters {
  calls: number;
}

export interface ToolContext {
  readonly client: FennecDashApi;
  readonly config: AppConfig;
  readonly audit: AuditLogger;
  readonly identity: AuthIdentity;
  readonly sessionId: string;
  readonly transport: 'stdio' | 'http';
  readonly meters: ToolMeters;
  readonly requestId: string;
  readonly getClientInfo?: McpClientInfoProvider;
}

export interface ToolOutcome {
  readonly data: unknown;
  readonly meta?: Record<string, unknown>;
  readonly backendRequestId?: string;
}

export interface ToolDefinition {
  readonly name: string;
  readonly module: string;
  readonly action: string;
  readonly title: string;
  readonly description: string;
  readonly risk: Risk;
  readonly inputSchema: ZodRawShape;
  readonly outputSchema?: ZodRawShape;
  readonly annotations?: ToolAnnotations;
  handler(args: Record<string, unknown>, ctx: ToolContext): Promise<ToolOutcome>;
}

export const RESPONSE_OUTPUT_SHAPE: ZodRawShape = {
  ok: z.boolean(),
  data: z.unknown().optional(),
  meta: z.record(z.string(), z.unknown()).optional(),
  error: z.record(z.string(), z.unknown()).optional(),
  request_id: z.string(),
};

/**
 * Single-use approval tokens are capabilities the caller must receive to
 * complete a critical operation. Audit logs and all other outputs still
 * redact them.
 */
const TOOL_OUTPUT_PRESERVE_KEYS: ReadonlySet<string> = new Set(['approval_token']);

const NAME_PATTERN = /^fennedash_[a-z0-9]+(?:_[a-z0-9]+)+$/;

/** Documented top-level entry point; the only tool without a module segment. */
export const TOP_LEVEL_DISCOVER_TOOL = 'fennedash_discover';

export function annotationsFor(risk: Risk): ToolAnnotations {
  if (risk === 'read') {
    return {
      readOnlyHint: true,
      destructiveHint: false,
      idempotentHint: true,
      openWorldHint: false,
    };
  }

  if (risk === 'write') {
    return {
      readOnlyHint: false,
      destructiveHint: false,
      idempotentHint: false,
      openWorldHint: false,
    };
  }

  return {
    readOnlyHint: false,
    destructiveHint: true,
    idempotentHint: false,
    openWorldHint: false,
  };
}

function validateDefinition(definition: ToolDefinition): void {
  if (definition.name === TOP_LEVEL_DISCOVER_TOOL) {
    if (definition.title.trim() === '' || definition.description.trim() === '') {
      throw new Error(`Tool "${definition.name}" must have a title and a description.`);
    }
    return;
  }

  if (!NAME_PATTERN.test(definition.name)) {
    throw new Error(
      `Invalid tool name "${definition.name}". Expected fennedash_<module>_<action> in lower snake case.`,
    );
  }

  const expected = `fennedash_${definition.module}_${definition.action}`;

  if (definition.name !== expected) {
    throw new Error(
      `Tool "${definition.name}" does not match module "${definition.module}" and action "${definition.action}" (expected "${expected}").`,
    );
  }

  if (definition.title.trim() === '' || definition.description.trim() === '') {
    throw new Error(`Tool "${definition.name}" must have a title and a description.`);
  }
}

/** Client attribution fields shared by every audit event of one call. */
function clientAuditFields(provider: McpClientInfoProvider | undefined): {
  clientName?: string;
  clientVersion?: string;
} {
  const info = provider?.();

  if (!info) {
    return {};
  }

  return {
    clientName: info.name,
    ...(info.version ? { clientVersion: info.version } : {}),
  };
}

export class ToolRegistry {
  private readonly tools = new Map<string, ToolDefinition>();

  add(definition: ToolDefinition): this {
    validateDefinition(definition);

    if (this.tools.has(definition.name)) {
      throw new Error(`Duplicate tool name: ${definition.name}`);
    }

    this.tools.set(definition.name, definition);
    return this;
  }

  addAll(definitions: readonly ToolDefinition[]): this {
    for (const definition of definitions) {
      this.add(definition);
    }
    return this;
  }

  get(name: string): ToolDefinition | undefined {
    return this.tools.get(name);
  }

  list(): readonly ToolDefinition[] {
    return [...this.tools.values()].sort((left, right) => left.name.localeCompare(right.name));
  }

  get size(): number {
    return this.tools.size;
  }

  registerWith(server: McpServer, baseContext: Omit<ToolContext, 'requestId'>): void {
    for (const definition of this.list()) {
      server.registerTool(
        definition.name,
        {
          title: definition.title,
          description: definition.description,
          inputSchema: definition.inputSchema,
          outputSchema: definition.outputSchema ?? RESPONSE_OUTPUT_SHAPE,
          annotations: definition.annotations ?? annotationsFor(definition.risk),
        },
        async (args): Promise<CallToolResult> => {
          const requestId = newRequestId('tool');
          const startedAt = Date.now();
          const context: ToolContext = {
            ...baseContext,
            requestId,
          };
          const clientFields = clientAuditFields(baseContext.getClientInfo);
          const clientInfo = baseContext.getClientInfo?.();

          if (clientInfo) {
            baseContext.client.setClientInfo?.(clientInfo.name, clientInfo.version);
          }

          try {
            assertToolAllowed(definition.risk, {
              allowWrite: baseContext.config.allowWrite,
              allowCritical: baseContext.config.allowCritical,
              tokenRisk: baseContext.identity.token.riskMax,
            });

            if (
              baseContext.config.maxCallsPerSession > 0 &&
              baseContext.meters.calls >= baseContext.config.maxCallsPerSession
            ) {
              throw new McpToolError(
                'session_limit_reached',
                `This session reached its limit of ${baseContext.config.maxCallsPerSession} tool calls.`,
                { details: { limit: baseContext.config.maxCallsPerSession } },
              );
            }

            baseContext.meters.calls += 1;

            const outcome = await definition.handler(args as Record<string, unknown>, context);

            const payload = redactValue(
              {
                ok: true,
                data: outcome.data,
                ...(outcome.meta ? { meta: outcome.meta } : {}),
                request_id: outcome.backendRequestId ?? requestId,
              },
              { preserveKeys: TOOL_OUTPUT_PRESERVE_KEYS },
            ) as Record<string, unknown>;

            await baseContext.audit.log({
              type: 'tool_call',
              requestId,
              sessionId: baseContext.sessionId,
              transport: baseContext.transport,
              tool: definition.name,
              risk: definition.risk,
              actor: {
                staffId: baseContext.identity.actor.staffId,
                tokenId: baseContext.identity.token.id,
                label: baseContext.identity.token.label,
              },
              outcome: 'ok',
              durationMs: Date.now() - startedAt,
              backendRequestId: outcome.backendRequestId,
              ...clientFields,
              args: summarizeForAudit(args),
            });

            return {
              content: [
                {
                  type: 'text',
                  text: JSON.stringify(payload, null, 2),
                },
              ],
              structuredContent: payload,
            };
          } catch (error) {
            const normalized = normalizeError(error);
            const denied = [
              'write_disabled',
              'critical_disabled',
              'risk_not_permitted',
              'forbidden',
              'feature_not_in_token_scope',
              'approval_required',
            ].includes(normalized.code);

            await baseContext.audit.log({
              type: 'tool_call',
              requestId,
              sessionId: baseContext.sessionId,
              transport: baseContext.transport,
              tool: definition.name,
              risk: definition.risk,
              actor: {
                staffId: baseContext.identity.actor.staffId,
                tokenId: baseContext.identity.token.id,
                label: baseContext.identity.token.label,
              },
              outcome: denied ? 'denied' : 'error',
              durationMs: Date.now() - startedAt,
              errorCode: normalized.code,
              backendRequestId: normalized.backendRequestId,
              ...clientFields,
              args: summarizeForAudit(args),
              details: normalized.details,
            });

            return toErrorResult(normalized, requestId);
          }
        },
      );
    }
  }
}
