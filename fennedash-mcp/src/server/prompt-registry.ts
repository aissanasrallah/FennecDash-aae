import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { GetPromptResult } from '@modelcontextprotocol/sdk/types.js';
import type { ZodRawShape } from 'zod';

import type { AuditLogger } from '../audit/audit-logger.js';
import type { AuthIdentity } from '../auth/context.js';
import type { AppConfig } from '../config/env.js';
import { newRequestId } from '../utils/request-id.js';
import type { McpClientInfoProvider } from './client-info.js';

export interface PromptContext {
  readonly config: AppConfig;
  readonly identity: AuthIdentity;
  readonly audit: AuditLogger;
  readonly sessionId: string;
  readonly transport: 'stdio' | 'http';
  readonly requestId: string;
  readonly getClientInfo?: McpClientInfoProvider;
}

export interface PromptDefinition {
  readonly name: string;
  readonly title: string;
  readonly description: string;
  readonly argsSchema?: ZodRawShape;
  build(args: Record<string, unknown>, ctx: PromptContext): GetPromptResult;
}

export class PromptRegistry {
  private readonly prompts = new Map<string, PromptDefinition>();

  add(definition: PromptDefinition): this {
    if (this.prompts.has(definition.name)) {
      throw new Error(`Duplicate prompt name: ${definition.name}`);
    }

    this.prompts.set(definition.name, definition);
    return this;
  }

  addAll(definitions: readonly PromptDefinition[]): this {
    for (const definition of definitions) {
      this.add(definition);
    }
    return this;
  }

  list(): readonly PromptDefinition[] {
    return [...this.prompts.values()].sort((left, right) => left.name.localeCompare(right.name));
  }

  get size(): number {
    return this.prompts.size;
  }

  registerWith(server: McpServer, baseContext: Omit<PromptContext, 'requestId'>): void {
    for (const definition of this.list()) {
      server.registerPrompt(
        definition.name,
        {
          title: definition.title,
          description: definition.description,
          ...(definition.argsSchema ? { argsSchema: definition.argsSchema } : {}),
        },
        async (args): Promise<GetPromptResult> => {
          const requestId = newRequestId('prompt');
          const startedAt = Date.now();
          const clientInfo = baseContext.getClientInfo?.();
          const clientFields = clientInfo
            ? {
                clientName: clientInfo.name,
                ...(clientInfo.version ? { clientVersion: clientInfo.version } : {}),
              }
            : {};

          try {
            const result = definition.build((args ?? {}) as Record<string, unknown>, {
              ...baseContext,
              requestId,
            });

            await baseContext.audit.log({
              type: 'prompt_get',
              requestId,
              sessionId: baseContext.sessionId,
              transport: baseContext.transport,
              prompt: definition.name,
              actor: {
                staffId: baseContext.identity.actor.staffId,
                tokenId: baseContext.identity.token.id,
                label: baseContext.identity.token.label,
              },
              outcome: 'ok',
              durationMs: Date.now() - startedAt,
              ...clientFields,
            });

            return result;
          } catch (error) {
            await baseContext.audit.log({
              type: 'prompt_get',
              requestId,
              sessionId: baseContext.sessionId,
              transport: baseContext.transport,
              prompt: definition.name,
              actor: {
                staffId: baseContext.identity.actor.staffId,
                tokenId: baseContext.identity.token.id,
                label: baseContext.identity.token.label,
              },
              outcome: 'error',
              durationMs: Date.now() - startedAt,
              errorCode: error instanceof Error ? error.name : 'unknown',
              ...clientFields,
            });
            throw error;
          }
        },
      );
    }
  }
}
