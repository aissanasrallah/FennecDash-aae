# FennecDash Architecture

This document describes the FennecDash application that the MCP server talks to,
and the additive Agent API layer built for it. It documents the code that exists
in this repository, not a target design.

## 1. Platform summary

| Layer | Implementation |
| --- | --- |
| Language | PHP 8.1+ (this installation runs PHP 8.3 under Apache and PHP 8.4 on the CLI) |
| Framework | CodeIgniter 3.1.11 with the MX (HMVC) extension |
| Database | MySQL 5.7+ / MariaDB 10.3+ (this installation runs MariaDB 11.4) |
| Web server | Apache with `mod_rewrite`; Nginx works with an equivalent front controller rule |
| Admin UI | Bootstrap 3 markup, Font Awesome, jQuery, Vue 3 mounted at `#vueApp` |
| Styling | Three legacy theme stylesheets plus Tailwind 3 scoped behind the `tw-` prefix |
| Entry point | `index.php`, environment `production`, migrations disabled by default |
| Table prefix | `tbl` (configurable through `APP_DB_PREFIX`) |

FennecDash is a CRM/ERP: clients, leads, projects, tasks, tickets, invoices,
estimates, contracts, staff, files, activity, plus optional modules for vehicle
export (AAE), accounting, HR, logistics, reports, goals, surveys and backups.

## 2. Request lifecycle (browser)

```
Browser
  -> Apache / index.php
     -> application/config/app-config.php  (loads .env, defines DB/encryption constants)
     -> CodeIgniter bootstrap
        -> application/hooks/App_Autoloader.php (app\ and modules\ namespaces)
        -> hooks: InitModules -> modules/<name>/<name>.php init files
        -> router (application/config/routes.php, optional my_routes.php)
           -> App_Controller
              -> AdminController | ClientsController | Authentication
                 -> feature controller
                    -> App_Model / feature model
                       -> MySQL (tbl_*)
                    -> application/views/... (init_head / init_tail or client theme shell)
```

- Admin requests are session authenticated and CSRF protected.
- Client-area requests are session authenticated and rendered through the
  `themes/fennec` shell.
- Views can be overridden without touching core code by creating a sibling
  `my_<filename>.php`; `App_Loader` prefers it automatically.
- Module initialization is skipped only for administrators who explicitly pass
  `?skip_modules_load=1`.

## 3. Authorization model

FennecDash owns authorization. The Agent API never invents a parallel permission
system.

| Concept | Storage | Meaning |
| --- | --- | --- |
| Staff account | `tblstaff` | Real person. `active = 1` and `is_not_staff = 0` are required for API use. |
| Administrator | `tblstaff.admin = 1` | Holds every permission implicitly. |
| Role | `tblroles.permissions` (PHP serialized) | Feature/capability map for non-admin staff. |
| Permission check | `staff_can($capability, $feature, $staffId)` | Authoritative check used by both the UI and the Agent API. |
| API token | `tblapi_tokens` | Bound to one staff account, one risk ceiling and optional feature scopes. |

A fresh installation can legitimately have no `tblstaff_permissions` rows and a
single administrator. In that case every authenticated API call is allowed by
the administrator flag, and non-admin tokens have no feature access until roles
or per-staff permissions are configured in the UI. This is expected behavior,
not a bypass.

## 4. Agent API layer

FennecDash has no pre-existing REST API. The Agent API is an additive layer; it
does not modify the browser controllers or models.

```
application/
  controllers/api/
    Api.php           JSON envelope, dispatch, auth, rate limiting, audit
    V1.php            all /api/v1 endpoints and the route/risk table
    Setup.php         one-shot installer and token lifecycle
  libraries/
    Api_auth.php      bearer auth, risk ceiling, permissions, approvals, audit
    Api_exception.php structured error type
    Api_resources.php whitelisted resource definitions, pagination, search
    Api_schema.php    idempotent schema installer
  migrations/
    345_version_345.php  wraps Api_schema for migration-based installs
```

### 4.1 Routing and envelope

`/api/v1/<action>` maps directly to a method on `V1`. Examples:

```
GET  /api/v1/ping
GET  /api/v1/projects?page=1&per_page=25
GET  /api/v1/projects/7
GET  /api/v1/project_diagnose/7
POST /api/v1/projects_create
POST /api/v1/approvals_create
POST /api/v1/cache_clear
```

Every response uses one envelope:

```json
{ "ok": true, "data": {}, "meta": {}, "request_id": "req_..." }
{ "ok": false, "error": { "code": "", "message": "", "details": {} }, "request_id": "req_..." }
```

`X-Request-Id` is accepted when it matches `[A-Za-z0-9._-]{6,64}` and echoed back;
otherwise the API generates `req_<16 hex>`. The MCP server sends its own
correlated id and reports the backend id in `request_id`.

### 4.2 Dispatch pipeline

For every non-public endpoint `Api::dispatch()` performs, in order:

1. Parse the route from the `$routes` table. Unknown actions return 404.
2. Authenticate the bearer token (SHA-256 hash lookup, revocation, expiry).
3. Load the bound staff row and reject inactive or non-staff accounts.
4. Apply the per-token rate limit (`MCP_RATE_LIMIT`, default 120/min).
5. Enforce the token risk ceiling (`read` < `write` < `critical`).
6. Enforce the token feature scope (when `scopes` is not null).
7. Enforce `staff_can($capability, $feature, $staffId)`.
8. Execute the endpoint.
9. Write one audit row, success or failure, with query/body summaries redacted.
10. Return the JSON envelope with the matching HTTP status.

The Agent API path is excluded from CSRF in `application/config/config.php`
(`api/.+`). That exemption is safe only because authentication is a bearer token
that browsers do not attach automatically. Session cookies are never accepted by
the Agent API.

### 4.3 Schema

Four tables are added by `Api_schema` / migration 345, using the configured
prefix:

| Table | Purpose | Notable columns |
| --- | --- | --- |
| `api_tokens` | API credentials | `staff_id`, `label`, `token_prefix`, `token_hash` (unique), `risk_max`, `scopes`, `client`, `expires_at`, `revoked_at`, `last_used_at`, `request_count` |
| `api_audit_log` | One row per API call | `token_id`, `staff_id`, `action`, `risk`, `http_method`, `path`, `status_code`, `ip`, `user_agent`, `request_summary`, `response_ms`, `created_at` |
| `api_rate_limits` | Fixed one-minute windows | primary key `(token_id, window_start)`, `hits` |
| `api_approvals` | Single-use critical approvals | `token_id`, `staff_id`, `action`, `approval_hash` (unique), `payload_hash`, `status`, `created_at`, `expires_at`, `used_at` |

Only SHA-256 hashes of tokens and approval tokens are stored. Plain values are
returned once at creation and never persisted.

### 4.4 Resource reads

`Api_resources` defines a whitelist per resource: table, alias, primary key,
selected columns, joins, searchable columns, allowed filters and sortable
columns. Client input never becomes a table name, column name or raw SQL
fragment.

Pagination is capped at 100 rows per page. `meta` contains `page`, `per_page`,
`total`, `pages`, `sort` and `order`. A single-record read returns the row plus
supported enrichment (for projects: tasks, members, files, invoices). Unknown
or unavailable resources return `unknown_resource`.

Global search accepts at least two characters and searches projects, clients,
leads, invoices, estimates, tickets and tasks. The backend checks the token
owner's view permission per resource and silently omits resources that are not
allowed; `meta.searched_resources` lists what was actually searched.

### 4.5 Writes

Exactly two business writes are exposed:

- `projects_create` requires `name` and an existing `clientid`, validates dates,
  clamps `progress` to 0-100, inserts the row and reads it back.
- `projects_update` accepts a fixed allowlist of project fields, requires at
  least one field, updates and reads the row back.

Both log a FennecDash activity entry and return `meta.verified: true` only after
the read-back. There is no generic table-write endpoint.

### 4.6 Critical operations and approvals

`cache_clear` is the only critical operation. It is dry-run by default. The real
execution requires a single-use approval token:

1. `POST /api/v1/cache_clear` with `{ "dry_run": true }` lists the files and
   bytes that would be deleted.
2. `POST /api/v1/approvals_create` with `{ "action": "cache_clear" }` creates an
   approval bound to `sha256({"dry_run":false})`, the token id, the action and a
   300-second expiry. Only a `critical`-risk token may request one.
3. `POST /api/v1/cache_clear` with `{ "dry_run": false, "approval_token": "..." }`
   consumes the approval, deletes the files, re-scans the directory and reports
   `remaining`. `meta.verified` is true only when `remaining === 0`.

Approvals are single use: reuse returns `409 approval_not_pending`; expiry,
wrong token, wrong action and payload mismatch each have distinct error codes.

### 4.7 Read-only SQL escape hatch

`POST /api/v1/database_query` exists for diagnostics but is disabled unless the
host sets `MCP_ALLOW_SQL=1`. When enabled it:

- accepts only a single `SELECT` or `WITH ... SELECT` statement;
- strips comments before inspection;
- rejects write/DDL/procedural keywords, `INTO`, `LOAD_FILE`, `OUTFILE`,
  `DUMPFILE`, `SLEEP`, `BENCHMARK`;
- rejects the `api_tokens`, `api_approvals`, `vault` and `user_auto_login`
  tables and sensitive columns such as password and token hashes;
- appends `LIMIT 200` when no limit is present and truncates to 200 rows;
- sets a five-second statement timeout where the server supports it.

The MCP `fennedash_database_query` tool forwards to this endpoint. It is not a
general database client.

## 5. Module system

Modules live under `modules/<name>/` and follow HMVC: their own controllers,
models, views, language files, migrations and an entry file
`modules/<name>/<name>.php`. At boot, `InitModules` reads the active modules
from `tblmodules`, requires each entry file and fires the `modules_loaded`
action.

This installation has 18 registered modules, 13 active and 5 inactive. See
[FENNECDASH_MODULES.md](FENNECDASH_MODULES.md) for the inventory and the MCP
exposure per module.

## 6. Deployment topology

```
Local agent (Claude Code, Codex, Cursor, ...)
  -> stdio
     -> fennedash-mcp process
        -> HTTPS + bearer token
           -> Apache / FennecDash /api/v1
              -> MariaDB

Remote agent (ChatGPT, hosted clients)
  -> HTTPS + bearer token
     -> fennedash-mcp Streamable HTTP (Node)
        -> HTTPS + bearer token
           -> Apache / FennecDash /api/v1
              -> MariaDB
```

The MCP server is a separate Node.js process. It never connects to MySQL, never
loads FennecDash code and never holds the application encryption key. The Agent
API is the only integration surface.

## 7. Design constraints

- No browser session or CSRF token is reused by the Agent API.
- No generic SQL write endpoint, no arbitrary PHP execution, no file upload.
- No passwords, token hashes or encryption keys are returned by any endpoint.
- Error detail is generic in production; the exception detail is written to the
  PHP error log and only returned when `ENVIRONMENT !== 'production'`.
- The API is additive: removing `application/controllers/api/`, the four
  libraries and the migration removes the integration without touching CRM
  behavior.
