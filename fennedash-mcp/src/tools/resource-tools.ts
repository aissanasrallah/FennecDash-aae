import type { ToolDefinition } from '../server/tool-registry.js';
import {
  idSchema,
  listQuery,
  paginationShape,
  permissionNote,
} from './shared.js';

export interface ResourceToolSpec {
  /** API resource key used by /api/v1/<resource>. */
  readonly resource: string;
  /** MCP module segment, e.g. `users` for the staff resource. */
  readonly module: string;
  readonly label: string;
  readonly singular: string;
  readonly idField: string;
  readonly feature: string;
  readonly searchFields: readonly string[];
  readonly filters: readonly string[];
  readonly sortable: readonly string[];
  readonly extraNotes?: string;
  readonly includeGet?: boolean;
  readonly includeList?: boolean;
}

function listDescription(spec: ResourceToolSpec): string {
  const lines = [
    `Returns a paginated list of ${spec.label} from FennecDash.`,
    spec.searchFields.length > 0
      ? `Search (q) across: ${spec.searchFields.join(', ')}.`
      : 'This resource does not support free-text search.',
    spec.filters.length > 0
      ? `Exact-match filters: ${spec.filters.join(', ')}.`
      : 'No exact-match filters are available.',
    `Sortable columns: ${spec.sortable.join(', ')}.`,
    'Use page and per_page (maximum 100) for pagination.',
    permissionNote(spec.feature),
  ];

  if (spec.extraNotes) {
    lines.push(spec.extraNotes);
  }

  return lines.join(' ');
}

function getDescription(spec: ResourceToolSpec): string {
  return [
    `Returns one ${spec.singular} from FennecDash by its numeric ${spec.idField}.`,
    'The response may include related records where the platform supports them.',
    permissionNote(spec.feature),
    spec.extraNotes ?? '',
  ]
    .filter((entry) => entry !== '')
    .join(' ');
}

export function resourceTools(spec: ResourceToolSpec): ToolDefinition[] {
  const tools: ToolDefinition[] = [];
  const includeList = spec.includeList ?? true;
  const includeGet = spec.includeGet ?? true;

  if (includeList) {
    tools.push({
      name: `fennedash_${spec.module}_list`,
      module: spec.module,
      action: 'list',
      title: `List ${spec.label}`,
      description: listDescription(spec),
      risk: 'read',
      inputSchema: { ...paginationShape },
      async handler(args, ctx) {
        const response = await ctx.client.get(spec.resource, listQuery(args), {
          requestId: ctx.requestId,
        });

        return {
          data: response.data,
          meta: response.meta,
          backendRequestId: response.requestId,
        };
      },
    });
  }

  if (includeGet) {
    tools.push({
      name: `fennedash_${spec.module}_get`,
      module: spec.module,
      action: 'get',
      title: `Get ${spec.singular}`,
      description: getDescription(spec),
      risk: 'read',
      inputSchema: {
        id: idSchema,
      },
      async handler(args, ctx) {
        const id = args.id as number;
        const response = await ctx.client.get(`${spec.resource}/${id}`, undefined, {
          requestId: ctx.requestId,
        });

        return {
          data: response.data,
          meta: { resource: spec.resource, id },
          backendRequestId: response.requestId,
        };
      },
    });
  }

  return tools;
}

/**
 * The list of read models exposed to agents. Every entry maps to a real
 * `/api/v1/<resource>` endpoint backed by a whitelisted table definition.
 */
export const RESOURCE_SPECS: readonly ResourceToolSpec[] = [
  {
    resource: 'projects',
    module: 'projects',
    label: 'projects',
    singular: 'project',
    idField: 'id',
    feature: 'projects',
    searchFields: ['name', 'description', 'client company'],
    filters: ['status', 'clientid', 'billing_type', 'addedfrom'],
    sortable: ['id', 'name', 'status', 'start_date', 'deadline', 'project_created', 'progress'],
    extraNotes:
      'Detail responses include the project tasks, members, files and invoices. Use fennedash_projects_create or fennedash_projects_update for WRITE operations.',
  },
  {
    resource: 'clients',
    module: 'clients',
    label: 'clients',
    singular: 'client',
    idField: 'userid',
    feature: 'customers',
    searchFields: ['company', 'vat', 'phonenumber', 'city', 'website'],
    filters: ['active', 'customer_type', 'country', 'city'],
    sortable: ['userid', 'company', 'datecreated', 'balance'],
  },
  {
    resource: 'leads',
    module: 'leads',
    label: 'leads',
    singular: 'lead',
    idField: 'id',
    feature: 'leads',
    searchFields: ['name', 'company', 'email', 'phonenumber', 'city'],
    filters: ['status', 'assigned', 'source', 'lost', 'junk'],
    sortable: ['id', 'name', 'dateadded', 'lastcontact', 'lead_value'],
    extraNotes: 'Detail responses include the recent lead activity log.',
  },
  {
    resource: 'invoices',
    module: 'invoices',
    label: 'invoices',
    singular: 'invoice',
    idField: 'id',
    feature: 'invoices',
    searchFields: ['formatted_number', 'number', 'client company'],
    filters: ['status', 'clientid', 'project_id', 'currency'],
    sortable: ['id', 'number', 'date', 'duedate', 'total', 'status'],
    extraNotes: 'Detail responses include the recorded payments.',
  },
  {
    resource: 'estimates',
    module: 'estimates',
    label: 'estimates',
    singular: 'estimate',
    idField: 'id',
    feature: 'estimates',
    searchFields: ['formatted_number', 'number', 'client company'],
    filters: ['status', 'clientid', 'project_id', 'currency'],
    sortable: ['id', 'number', 'date', 'expirydate', 'total', 'status'],
    extraNotes: 'Detail responses include the estimate line items.',
  },
  {
    resource: 'tasks',
    module: 'tasks',
    label: 'tasks',
    singular: 'task',
    idField: 'id',
    feature: 'tasks',
    searchFields: ['name', 'description'],
    filters: ['status', 'priority', 'rel_id', 'rel_type', 'billable'],
    sortable: ['id', 'name', 'duedate', 'dateadded', 'priority', 'status'],
    extraNotes: 'Detail responses include the assignees.',
  },
  {
    resource: 'tickets',
    module: 'tickets',
    label: 'tickets',
    singular: 'ticket',
    idField: 'ticketid',
    feature: 'tickets',
    searchFields: ['subject', 'email', 'name'],
    filters: ['status', 'priority', 'department', 'assigned', 'userid', 'project_id'],
    sortable: ['ticketid', 'subject', 'date', 'lastreply', 'priority', 'status'],
    extraNotes: 'Detail responses include the reply count.',
  },
  {
    resource: 'contracts',
    module: 'contracts',
    label: 'contracts',
    singular: 'contract',
    idField: 'id',
    feature: 'contracts',
    searchFields: ['subject', 'description', 'client company'],
    filters: ['client', 'contract_type', 'signed', 'trash', 'project_id'],
    sortable: ['id', 'subject', 'datestart', 'dateend', 'contract_value'],
  },
  {
    resource: 'files',
    module: 'files',
    label: 'files',
    singular: 'file',
    idField: 'id',
    feature: 'utilities',
    searchFields: ['file_name', 'rel_type'],
    filters: ['rel_id', 'rel_type', 'staffid', 'visible_to_customer'],
    sortable: ['id', 'file_name', 'dateadded'],
    extraNotes:
      'Metadata only: the Agent API does not stream file contents or accept uploads. Use fennedash_storage_get_stats for storage totals.',
  },
  {
    resource: 'aae_orders',
    module: 'aae_orders',
    label: 'AAE export orders',
    singular: 'AAE export order',
    idField: 'id',
    feature: 'aae',
    searchFields: ['supplier_name', 'container_number', 'bl_number', 'client company'],
    filters: ['stage', 'client_id', 'estimate_id', 'invoice_id'],
    sortable: ['id', 'stage', 'amount_client', 'datecreated', 'dateupdated'],
    extraNotes: 'Detail responses include linked vehicles and payments.',
  },
  {
    resource: 'activity',
    module: 'activity',
    label: 'staff activity log entries',
    singular: 'activity entry',
    idField: 'id',
    feature: 'utilities',
    searchFields: ['description'],
    filters: ['staffid'],
    sortable: ['id', 'date'],
  },
  {
    resource: 'staff',
    module: 'users',
    label: 'staff users',
    singular: 'staff user',
    idField: 'staffid',
    feature: 'staff',
    searchFields: ['email', 'firstname', 'lastname'],
    filters: ['active', 'admin', 'role'],
    sortable: ['staffid', 'firstname', 'lastname', 'datecreated', 'last_login'],
    extraNotes:
      'Passwords, two-factor secrets and credentials are never selected. Role and permission inspection is available through fennedash_users_list_roles and fennedash_system_get_permissions.',
  },
];
