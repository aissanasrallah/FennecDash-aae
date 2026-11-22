import { z } from 'zod';

import { McpToolError } from '../../security/errors.js';
import type { ToolDefinition } from '../../server/tool-registry.js';
import { idSchema, readEndpoint, writeEndpoint } from '../shared.js';

const dateSchema = z
  .string()
  .regex(/^\d{4}-\d{2}-\d{2}$/, 'Expected YYYY-MM-DD')
  .nullable()
  .optional();

const projectWritableFields = {
  name: z.string().trim().min(1).max(191).optional().describe('Project name.'),
  description: z.string().max(20_000).nullable().optional().describe('Project description.'),
  clientid: idSchema.optional().describe('Owning client id (tblclients.userid).'),
  billing_type: z.coerce.number().int().min(0).max(10).optional().describe('FennecDash billing type id.'),
  status: z.coerce.number().int().min(0).max(10).optional().describe('FennecDash project status id.'),
  progress: z.coerce.number().int().min(0).max(100).optional().describe('Progress percentage (0-100).'),
  project_cost: z.coerce.number().min(0).optional().describe('Project cost.'),
  estimated_hours: z.coerce.number().min(0).optional().describe('Estimated hours.'),
  start_date: dateSchema.describe('Start date (YYYY-MM-DD) or null.'),
  deadline: dateSchema.describe('Deadline (YYYY-MM-DD) or null.'),
} as const;

export const projectTools: readonly ToolDefinition[] = [
  {
    name: 'fennedash_projects_create',
    module: 'projects',
    action: 'create',
    title: 'Create project',
    description:
      'Creates a FennecDash project for an existing client. Required: name and clientid. Optional: description, status, billing_type, progress, project_cost, estimated_hours, start_date and deadline. The response is the persisted project read back from the database, with related records, so success can be verified. WRITE operation; requires the "create" permission on "projects".',
    risk: 'write',
    inputSchema: {
      name: z.string().trim().min(1).max(191).describe('Project name.'),
      clientid: idSchema.describe('Existing client id (tblclients.userid).'),
      description: z.string().max(20_000).optional().describe('Project description.'),
      status: z.coerce.number().int().min(0).max(10).optional().describe('Project status id (default 1).'),
      billing_type: z.coerce.number().int().min(0).max(10).optional().describe('Billing type id (default 1).'),
      progress: z.coerce.number().int().min(0).max(100).optional().describe('Progress percentage (default 0).'),
      project_cost: z.coerce.number().min(0).optional().describe('Project cost (default 0).'),
      estimated_hours: z.coerce.number().min(0).optional().describe('Estimated hours (default 0).'),
      start_date: dateSchema.describe('Start date (YYYY-MM-DD).'),
      deadline: dateSchema.describe('Deadline (YYYY-MM-DD).'),
      dry_run: z
        .boolean()
        .optional()
        .describe('Validate the payload against the backend without creating the project.'),
      idempotency_key: z
        .string()
        .trim()
        .min(8)
        .max(128)
        .optional()
        .describe('Stable key that deduplicates identical creates. One is generated when omitted.'),
    },
    async handler(args, ctx) {
      const { dry_run, idempotency_key, ...rest } = args as Record<string, unknown> & {
        dry_run?: boolean;
        idempotency_key?: string;
      };
      const body: Record<string, unknown> = {
        name: rest.name,
        clientid: rest.clientid,
      };

      for (const [key, value] of Object.entries(rest)) {
        if (key === 'name' || key === 'clientid') {
          continue;
        }
        if (value !== undefined) {
          body[key] = value;
        }
      }

      const outcome = await writeEndpoint(ctx, 'projects_create', body, {
        dryRun: dry_run === true,
        idempotencyKey: idempotency_key as string | undefined,
      });

      return {
        ...outcome,
        meta: { ...(outcome.meta ?? {}), created: true, verified: true },
      };
    },
  },
  {
    name: 'fennedash_projects_update',
    module: 'projects',
    action: 'update',
    title: 'Update project',
    description:
      'Updates one FennecDash project. Only the supplied fields change; at least one updatable field is required besides id. The response is the persisted project read back from the database, so success can be verified. Set deadline or start_date to null to clear it. WRITE operation; requires the "edit" permission on "projects".',
    risk: 'write',
    inputSchema: {
      id: idSchema.describe('Project id to update.'),
      ...projectWritableFields,
      date_finished: z
        .string()
        .regex(/^\d{4}-\d{2}-\d{2}$/, 'Expected YYYY-MM-DD')
        .nullable()
        .optional()
        .describe('Completion date (YYYY-MM-DD) or null.'),
      dry_run: z
        .boolean()
        .optional()
        .describe('Validate the payload against the backend without updating the project.'),
      idempotency_key: z
        .string()
        .trim()
        .min(8)
        .max(128)
        .optional()
        .describe('Stable key that deduplicates identical updates. One is generated when omitted.'),
    },
    async handler(args, ctx) {
      const { id, dry_run, idempotency_key, ...fields } = args as Record<string, unknown> & {
        id: number;
        dry_run?: boolean;
        idempotency_key?: string;
      };
      const body: Record<string, unknown> = { id };

      for (const [key, value] of Object.entries(fields)) {
        if (value !== undefined) {
          body[key] = value;
        }
      }

      if (Object.keys(body).length === 1) {
        throw new McpToolError(
          'validation_failed',
          'Provide at least one field to update besides id.',
          { details: { field: 'id' } },
        );
      }

      const outcome = await writeEndpoint(ctx, 'projects_update', body, {
        dryRun: dry_run === true,
        idempotencyKey: idempotency_key as string | undefined,
      });

      return {
        ...outcome,
        meta: { ...(outcome.meta ?? {}), updated: true, verified: true },
      };
    },
  },
  {
    name: 'fennedash_projects_diagnose',
    module: 'projects',
    action: 'diagnose',
    title: 'Diagnose project',
    description:
      'Collects evidence and returns structured findings for one project: project record, task counts (open/overdue), linked invoices and payment status, linked tickets, files, recent project activity, severity-ranked findings and recommended corrective actions. It never changes data. Use it before proposing or performing a fix, then verify any fix with fennedash_projects_get. Read-only.',
    risk: 'read',
    inputSchema: {
      project_id: idSchema.describe('Project id to diagnose.'),
    },
    async handler(args, ctx) {
      return readEndpoint(ctx, `project_diagnose/${args.project_id as number}`);
    },
  },
  {
    name: 'fennedash_projects_get_activity',
    module: 'projects',
    action: 'get_activity',
    title: 'Get project activity',
    description:
      'Returns recent project activity entries (who, description key, additional data, visibility and timestamp) for one project. Read-only.',
    risk: 'read',
    inputSchema: {
      project_id: idSchema.describe('Project id.'),
      limit: z.coerce.number().int().min(1).max(100).default(50).describe('Maximum entries (1-100).'),
    },
    async handler(args, ctx) {
      return readEndpoint(ctx, `project_activity/${args.project_id as number}`, {
        limit: args.limit as number,
      });
    },
  },
];
