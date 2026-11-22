import type { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { ResourceTemplate } from '@modelcontextprotocol/sdk/server/mcp.js';
import type { ReadResourceResult } from '@modelcontextprotocol/sdk/types.js';

import type { AuditLogger } from '../audit/audit-logger.js';
import type { AuthIdentity } from '../auth/context.js';
import type { AppConfig } from '../config/env.js';
import type { FennecDashApi } from '../integrations/fennedash-api/client.js';
import { normalizeError } from '../security/errors.js';
import { newRequestId } from '../utils/request-id.js';
import type { McpClientInfoProvider } from './client-info.js';

export interface ResourceContext {
  readonly client: FennecDashApi;
  readonly config: AppConfig;
  readonly audit: AuditLogger;
  readonly identity: AuthIdentity;
  readonly sessionId: string;
  readonly transport: 'stdio' | 'http';
  readonly requestId: string;
  readonly getClientInfo?: McpClientInfoProvider;
}

export interface ResourceContent {
  readonly text: string;
  readonly mimeType?: string;
}

export interface StaticResourceDefinition {
  readonly kind: 'static';
  readonly name: string;
  readonly uri: string;
  readonly title: string;
  readonly description: string;
  readonly mimeType: string;
  read(ctx: ResourceContext): Promise<ResourceContent>;
}

export interface TemplateResourceDefinition {
  readonly kind: 'template';
  readonly name: string;
  readonly uriTemplate: string;
  readonly title: string;
  readonly description: string;
  readonly mimeType: string;
  list?(ctx: ResourceContext): Promise<Array<{ uri: string; name: string; title?: string }>>;
  read(
    uri: URL,
    variables: Record<string, string | string[]>,
    ctx: ResourceContext,
  ): Promise<ResourceContent>;
}

export type ResourceDefinition = StaticResourceDefinition | TemplateResourceDefinition;

export class ResourceRegistry {
  private readonly resources = new Map<string, ResourceDefinition>();

  add(definition: ResourceDefinition): this {
    if (this.resources.has(definition.name)) {
      throw new Error(`Duplicate resource name: ${definition.name}`);
    }

    this.resources.set(definition.name, definition);
    return this;
  }

  addAll(definitions: readonly ResourceDefinition[]): this {
    for (const definition of definitions) {
      this.add(definition);
    }
    return this;
  }

  list(): readonly ResourceDefinition[] {
    return [...this.resources.values()].sort((left, right) => left.name.localeCompare(right.name));
  }

  get size(): number {
    return this.resources.size;
  }

  registerWith(server: McpServer, baseContext: Omit<ResourceContext, 'requestId'>): void {
    for (const definition of this.list()) {
      if (definition.kind === 'static') {
        server.registerResource(
          definition.name,
          definition.uri,
          {
            title: definition.title,
            description: definition.description,
            mimeType: definition.mimeType,
          },
          async (uri): Promise<ReadResourceResult> => {
            const requestId = newRequestId('res');
            const startedAt = Date.now();

            try {
              const content = await definition.read({ ...baseContext, requestId });

              await baseContext.audit.log({
                type: 'resource_read',
                requestId,
                sessionId: baseContext.sessionId,
                transport: baseContext.transport,
                ...this.clientFields(baseContext),
                resource: definition.uri,
                actor: this.actor(baseContext),
                outcome: 'ok',
                durationMs: Date.now() - startedAt,
              });

              return {
                contents: [
                  {
                    uri: uri.href,
                    mimeType: content.mimeType ?? definition.mimeType,
                    text: content.text,
                  },
                ],
              };
            } catch (error) {
              const normalized = normalizeError(error);
              await baseContext.audit.log({
                type: 'resource_read',
                requestId,
                sessionId: baseContext.sessionId,
                transport: baseContext.transport,
                ...this.clientFields(baseContext),
                resource: definition.uri,
                actor: this.actor(baseContext),
                outcome: 'error',
                durationMs: Date.now() - startedAt,
                errorCode: normalized.code,
              });
              throw normalized;
            }
          },
        );
        continue;
      }

      const template = new ResourceTemplate(definition.uriTemplate, {
        list: definition.list
          ? async () => ({
              resources: await definition.list!({ ...baseContext, requestId: newRequestId('res') }),
            })
          : undefined,
      });

      server.registerResource(
        definition.name,
        template,
        {
          title: definition.title,
          description: definition.description,
          mimeType: definition.mimeType,
        },
        async (uri, variables): Promise<ReadResourceResult> => {
          const requestId = newRequestId('res');
          const startedAt = Date.now();

          try {
            const content = await definition.read(uri, variables, { ...baseContext, requestId });

            await baseContext.audit.log({
              type: 'resource_read',
              requestId,
              sessionId: baseContext.sessionId,
              transport: baseContext.transport,
              ...this.clientFields(baseContext),
              resource: uri.href,
              actor: this.actor(baseContext),
              outcome: 'ok',
              durationMs: Date.now() - startedAt,
            });

            return {
              contents: [
                {
                  uri: uri.href,
                  mimeType: content.mimeType ?? definition.mimeType,
                  text: content.text,
                },
              ],
            };
          } catch (error) {
            const normalized = normalizeError(error);
            await baseContext.audit.log({
              type: 'resource_read',
              requestId,
              sessionId: baseContext.sessionId,
              transport: baseContext.transport,
              ...this.clientFields(baseContext),
              resource: uri.href,
              actor: this.actor(baseContext),
              outcome: 'error',
              durationMs: Date.now() - startedAt,
              errorCode: normalized.code,
            });
            throw normalized;
          }
        },
      );
    }
  }

  private clientFields(context: Omit<ResourceContext, 'requestId'>) {
    const info = context.getClientInfo?.();

    return info
      ? { clientName: info.name, ...(info.version ? { clientVersion: info.version } : {}) }
      : {};
  }

  private actor(context: Omit<ResourceContext, 'requestId'>) {
    return {
      staffId: context.identity.actor.staffId,
      tokenId: context.identity.token.id,
      label: context.identity.token.label,
    };
  }
}
