# FennecDash Modules

FennecDash is a CodeIgniter 3 application with core CRM features in
`application/` and optional HMVC modules in `modules/`. This page lists what is
installed in this repository, what is active, and which parts the Agent API and
MCP server actually expose.

## 1. Installed modules

The module registry is the `tblmodules` table; the `active` flag and the
presence of `modules/<name>/<name>.php` decide whether the module boots.

| Module | Installed version | Active | MCP / Agent API exposure |
| --- | --- | --- | --- |
| `aae` | 1.1.0 | yes | `fennedash_aae_orders_list`, `fennedash_aae_orders_get` read `tblaae_orders`. |
| `accounting` | 1.4.3 | yes | Invoices and estimates are exposed as core read resources. No accounting write tools. |
| `backup` | 2.3.0 | yes | Not exposed. Backups are operator-only; the Agent API has no restore or download path. |
| `einvoice` | 1.0.0 | yes | Not exposed. |
| `exports` | 1.0.0 | yes | Not exposed. |
| `goals` | 2.3.0 | yes | Aggregates may appear in `fennedash_dashboard_*`; no goal-specific tool. |
| `hr_profile` | 1.1.2 | yes | Staff records are exposed through `fennedash_users_*`; HR profile tables are not. |
| `logistic` | 1.0.0 | yes | Not exposed directly; AAE order data covers the export workflow records. |
| `manufacturing` | 1.0.5 | no | Not exposed. |
| `menu_setup` | 2.3.0 | yes | Not exposed. |
| `openai` | 1.0.0 | no | Not exposed. The MCP server is vendor-independent and does not call this module. |
| `purchase` | 1.5.0 | no | Not exposed. |
| `quick_share` | 1.1.0 | yes | Not exposed. |
| `report_builder` | 1.0.0 | yes | Custom report definitions are not exposed. Dashboard and log reads are. |
| `surveys` | 2.3.0 | yes | Not exposed. |
| `theme_style` | 2.3.0 | yes | Not exposed. |
| `warehouse` | 1.5.1 | no | Not exposed. |
| `warranty_management` | 1.0.0 | no | Not exposed. |

`modules/einvoice_disabled/` is a separate front-end asset source used by
`npm run build`; it is not a registered module and has no entry file. The module
registry above matches the live database at the time of writing. Use
`fennedash_system_list_modules` to read the current list from the installation
instead of trusting a snapshot.

## 2. Core CRM data exposed

These resources are defined in `application/libraries/Api_resources.php` with an
explicit column allowlist.

| Resource | Table | Primary key | Read tool | Write tool |
| --- | --- | --- | --- | --- |
| `projects` | `tblprojects` | `id` | `fennedash_projects_list` / `_get` | `_create`, `_update` |
| `clients` | `tblclients` | `userid` | `fennedash_clients_list` / `_get` | none |
| `leads` | `tblleads` | `id` | `fennedash_leads_list` / `_get` | none |
| `invoices` | `tblinvoices` | `id` | `fennedash_invoices_list` / `_get` | none |
| `estimates` | `tblestimates` | `id` | `fennedash_estimates_list` / `_get` | none |
| `tasks` | `tbltasks` | `id` | `fennedash_tasks_list` / `_get` | none |
| `tickets` | `tbltickets` | `ticketid` | `fennedash_tickets_list` / `_get` | none |
| `staff` | `tblstaff` | `staffid` | `fennedash_users_list` / `_get` | none |
| `contracts` | `tblcontracts` | `id` | `fennedash_contracts_list` / `_get` | none |
| `files` | `tblfiles` | `id` | `fennedash_files_list` / `_get` | none |
| `aae_orders` | `tblaae_orders` | `id` | `fennedash_aae_orders_list` / `_get` | none |
| `activity` | `tblactivity_log` | `id` | `fennedash_activity_list` / `_get` | none |

`fennedash_system_get_api_catalog` and the `fennedash://catalog/resources`
resource return the live catalog for the current installation, including fields,
filters and sort order. Tables from inactive modules are therefore absent from
the catalog and from `fennedash_system_get_capabilities`.

## 3. Read-only operational surface

| Area | Tools | Source |
| --- | --- | --- |
| Discovery | `fennedash_discover`, `fennedash_system_get_capabilities`, `_get_api_catalog` | API route table and live resource catalog |
| Identity | `fennedash_auth_whoami`, `_get_permissions` | Token and `staff_can()` state |
| Platform | `fennedash_system_get_status`, `_get_info`, `_check_health`, `_get_config`, `_get_version`, `_list_modules`, `_get_diagnostics` | FennecDash configuration, DB and filesystem checks |
| Dashboard | `fennedash_dashboard_overview`, `_stats`, `_activity`, `_widgets` | Dashboard models |
| Projects | `fennedash_projects_diagnose`, `_get_activity` | Project tables and activity log |
| Search | `fennedash_search_global` | Projects, clients, leads, invoices, estimates, tickets, tasks |
| Monitoring | `fennedash_logs_list`, `_search`, `fennedash_audit_list`, `_get_summary`, `fennedash_integrations_list`, `fennedash_storage_get_stats` | Application logs, API audit table, settings, filesystem |
| Database | `fennedash_database_list_tables`, `_get_schema`, `_get_stats`, `_query` | `information_schema`, guarded SELECT only |

## 4. Writes and critical operations

| Operation | Class | Requirements |
| --- | --- | --- |
| `fennedash_projects_create` | WRITE | `MCP_ALLOW_WRITE=true`, `projects:create`, valid `name` and existing `clientid` |
| `fennedash_projects_update` | WRITE | `MCP_ALLOW_WRITE=true`, `projects:edit`, at least one allowlisted field |
| `fennedash_approvals_request` | WRITE | `critical`-risk token; creates a single-use approval for `cache_clear` |
| `fennedash_system_clear_cache` | CRITICAL | `MCP_ALLOW_CRITICAL=true`, `utilities:edit`, dry run, approval token |

There are no generic CRUD tools. Adding a write requires an explicit endpoint,
an allowlist, a permission, a risk class, a read-back and tests.

## 5. Not exposed

The following are deliberately outside the Agent API and MCP server:

- Backup creation, restore or download.
- File upload, download or deletion. `tblfiles` metadata is readable only.
- Outbound e-mail, SMS, WhatsApp or any messaging send path.
- Workflow or automation execution.
- Invoice, estimate, task, ticket or client writes.
- User, role or permission management.
- Module activation or installation.
- Theme, menu, survey or report-builder changes.
- Arbitrary SQL writes, DDL or multiple statements.
- Deployment, service restart or PHP execution.

`fennedash_database_query` is the only escape hatch and stays disabled unless
the FennecDash host sets `MCP_ALLOW_SQL=1`. It is read-only and capped at 200
rows.

## 6. Adding a module to the Agent API

1. Add the table and its column allowlist to
   `application/libraries/Api_resources.php`; never pass user input as an
   identifier.
2. Add a read route in `application/controllers/api/V1.php` with
   `risk => read`, the correct feature and the `view` capability.
3. Add a `ResourceToolSpec` in `src/tools/resource-tools.ts` so list/get tools
   are generated consistently.
4. Add the permission mapping in `scripts/generate-catalog.ts`.
5. Add unit and integration coverage, then run `npm test`,
   `npm run docs:catalog` and `npm run mcp:doctor`.
6. Update this file if the exposure changes.

Writes need a dedicated endpoint with validation, permission, audit and
read-back. Do not add a generic write tool.
