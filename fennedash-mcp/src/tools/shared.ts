import { randomUUID } from 'node:crypto';

import { z } from 'zod';

import type { QueryParams } from '../integrations/fennedash-api/client.js';
import type { ToolContext, ToolOutcome } from '../server/tool-registry.js';

/** Accepts both 12 and "12"; the FennecDash API compares numerically. */
export const idSchema = z.coerce
  .number()
  .int()
  .positive()
  .describe('Numeric FennecDash record id.');

export const paginationShape = {
  page: z.coerce.number().int().min(1).optional().describe('Page number, starting at 1.'),
  per_page: z.coerce
    .number()
    .int()
    .min(1)
    .max(100)
    .optional()
    .describe('Rows per page (1-100). Defaults to 25.'),
  q: z
    .string()
    .trim()
    .min(1)
    .max(200)
    .optional()
    .describe('Free-text search term applied to the documented search fields.'),
  sort: z.string().trim().max(50).optional().describe('Column to sort by; invalid values fall back to the default.'),
  order: z.enum(['asc', 'desc']).optional().describe('Sort direction.'),
  filter: z
    .record(z.string(), z.union([z.string(), z.number(), z.boolean()]))
    .optional()
    .describe('Exact-match filters. Only the documented filter keys are applied.'),
} as const;

export const limitShape = {
  limit: z.coerce.number().int().min(1).max(500).optional().describe('Maximum number of rows to return.'),
} as const;

export function listQuery(args: Record<string, unknown>): QueryParams {
  const query: QueryParams = {};

  for (const key of ['page', 'per_page', 'q', 'sort', 'order'] as const) {
    const value = args[key];
    if (value !== undefined && value !== null && value !== '') {
      query[key] = value as string | number;
    }
  }

  const filter = args.filter;
  if (filter && typeof filter === 'object' && !Array.isArray(filter)) {
    query.filter = filter as Record<string, string | number | boolean>;
  }

  return query;
}

export function stringArg(args: Record<string, unknown>, key: string): string | undefined {
  const value = args[key];
  return typeof value === 'string' && value.trim() !== '' ? value.trim() : undefined;
}

export function numberArg(args: Record<string, unknown>, key: string): number | undefined {
  const value = args[key];
  return typeof value === 'number' && Number.isFinite(value) ? value : undefined;
}

export function boolArg(args: Record<string, unknown>, key: string): boolean | undefined {
  const value = args[key];
  return typeof value === 'boolean' ? value : undefined;
}

export function permissionNote(feature: string, capability = 'view'): string {
  return `Requires the FennecDash "${capability}" permission on "${feature}".`;
}

export async function readEndpoint(
  ctx: ToolContext,
  path: string,
  query?: QueryParams,
): Promise<ToolOutcome> {
  const response = await ctx.client.get(path, query, { requestId: ctx.requestId });

  return {
    data: response.data,
    meta: response.meta,
    backendRequestId: response.requestId,
  };
}

export interface WriteEndpointOptions {
  /** When true, the backend validates the payload without persisting it. */
  readonly dryRun?: boolean;
  /** Client-supplied idempotency key; one is generated when omitted. */
  readonly idempotencyKey?: string;
}

export async function writeEndpoint(
  ctx: ToolContext,
  path: string,
  body: unknown,
  options: WriteEndpointOptions = {},
): Promise<ToolOutcome> {
  const payload: Record<string, unknown> = isRecord(body) ? { ...body } : { value: body };

  if (options.dryRun) {
    payload.dry_run = true;
  }

  const idempotencyKey =
    options.idempotencyKey !== undefined && options.idempotencyKey !== ''
      ? options.idempotencyKey
      : `mcp_${randomUUID().replace(/-/g, '').slice(0, 24)}`;

  const response = await ctx.client.post(path, payload, {
    requestId: ctx.requestId,
    idempotencyKey,
  });

  return {
    data: response.data,
    meta: {
      ...(response.meta ?? {}),
      ...(response.idempotentReplayed ? { idempotent_replayed: true } : {}),
      idempotency_key: idempotencyKey,
      dry_run: options.dryRun === true,
    },
    backendRequestId: response.requestId,
  };
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}