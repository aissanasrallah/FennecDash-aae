<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/Api_exception.php';
require_once __DIR__ . '/Api.php';

/**
 * FennecDash Agent API v1.
 *
 * Every method is an RPC endpoint named exactly like the MCP tool suffix,
 * e.g. tool `fennedash_projects_list` calls GET /api/v1/projects.
 *
 * Read endpoints are backed by Api_resources (whitelisted tables/columns).
 * Write endpoints validate input and return the persisted record so a caller
 * can verify the change instead of trusting a success flag.
 */
class V1 extends Api
{
    /** Maximum rows returned by the guarded SQL endpoint. */
    const SQL_MAX_ROWS = 200;

    /** Tables that must never be readable through the guarded SQL endpoint. */
    const SQL_DENY_TABLES = ['api_tokens', 'vault', 'user_auto_login', 'api_approvals'];

    protected $routes = [
        // --- public -------------------------------------------------------
        'ping'                 => ['risk' => 'read', 'public' => true],

        // --- identity / discovery ----------------------------------------
        'me'                   => ['risk' => 'read', 'feature' => null],
        'system_status'        => ['risk' => 'read', 'feature' => 'utilities', 'capability' => 'view'],
        'system_info'          => ['risk' => 'read', 'feature' => 'utilities', 'capability' => 'view'],
        'system_health'        => ['risk' => 'read', 'feature' => null],
        'system_config'        => ['risk' => 'read', 'feature' => null],
        'system_modules'       => ['risk' => 'read', 'feature' => null],
        'system_diagnostics'   => ['risk' => 'read', 'feature' => 'utilities', 'capability' => 'view'],
        'system_version'       => ['risk' => 'read', 'feature' => null],
        'system_capabilities'  => ['risk' => 'read', 'feature' => null],
        'system_permissions'   => ['risk' => 'read', 'feature' => null],
        'resources'            => ['risk' => 'read', 'feature' => null],

        // --- read models --------------------------------------------------
        'projects'             => ['risk' => 'read', 'feature' => 'projects', 'capability' => 'view'],
        'clients'              => ['risk' => 'read', 'feature' => 'customers', 'capability' => 'view'],
        'leads'                => ['risk' => 'read', 'feature' => 'leads', 'capability' => 'view'],
        'invoices'             => ['risk' => 'read', 'feature' => 'invoices', 'capability' => 'view'],
        'estimates'            => ['risk' => 'read', 'feature' => 'estimates', 'capability' => 'view'],
        'tasks'                => ['risk' => 'read', 'feature' => 'tasks', 'capability' => 'view'],
        'tickets'              => ['risk' => 'read', 'feature' => 'tickets', 'capability' => 'view'],
        'staff'                => ['risk' => 'read', 'feature' => 'staff', 'capability' => 'view'],
        'roles_list'           => ['risk' => 'read', 'feature' => 'staff', 'capability' => 'view'],
        'contracts'            => ['risk' => 'read', 'feature' => 'contracts', 'capability' => 'view'],
        'files'                => ['risk' => 'read', 'feature' => 'utilities', 'capability' => 'view'],
        'aae_orders'           => ['risk' => 'read', 'feature' => 'aae', 'capability' => 'view'],
        'activity'             => ['risk' => 'read', 'feature' => 'utilities', 'capability' => 'view'],
        'search'               => ['risk' => 'read', 'feature' => null],
        'project_diagnose'     => ['risk' => 'read', 'feature' => 'projects', 'capability' => 'view'],
        'project_activity'     => ['risk' => 'read', 'feature' => 'projects', 'capability' => 'view'],
        'clients_summary'      => ['risk' => 'read', 'feature' => 'customers', 'capability' => 'view'],
        'reports_sales'        => ['risk' => 'read', 'feature' => 'reports', 'capability' => 'view'],

        // --- dashboard ----------------------------------------------------
        'dashboard_overview'   => ['risk' => 'read', 'feature' => 'dashboard', 'capability' => 'view'],
        'dashboard_stats'      => ['risk' => 'read', 'feature' => 'dashboard', 'capability' => 'view'],
        'dashboard_activity'   => ['risk' => 'read', 'feature' => 'dashboard', 'capability' => 'view'],
        'dashboard_widgets'    => ['risk' => 'read', 'feature' => 'dashboard', 'capability' => 'view'],

        // --- logs ---------------------------------------------------------
        'logs_list'            => ['risk' => 'read', 'feature' => 'utilities', 'capability' => 'view'],
        'logs_search'          => ['risk' => 'read', 'feature' => 'utilities', 'capability' => 'view'],
        'audit_list'           => ['risk' => 'read', 'feature' => 'utilities', 'capability' => 'view'],
        'integrations_list'    => ['risk' => 'read', 'feature' => 'utilities', 'capability' => 'view'],

        // --- database -----------------------------------------------------
        'database_tables'      => ['risk' => 'read', 'feature' => 'utilities', 'capability' => 'view'],
        'database_schema'      => ['risk' => 'read', 'feature' => 'utilities', 'capability' => 'view'],
        'database_stats'       => ['risk' => 'read', 'feature' => 'utilities', 'capability' => 'view'],
        'database_query'       => ['risk' => 'read', 'feature' => 'utilities', 'capability' => 'view'],

        // --- storage ------------------------------------------------------
        'storage_stats'        => ['risk' => 'read', 'feature' => 'utilities', 'capability' => 'view'],

        // --- writes -------------------------------------------------------
        'projects_create'      => ['risk' => 'write', 'feature' => 'projects', 'capability' => 'create', 'idempotent' => true],
        'projects_update'      => ['risk' => 'write', 'feature' => 'projects', 'capability' => 'edit'],
        'approvals_create'     => ['risk' => 'write', 'feature' => null],

        // --- critical -----------------------------------------------------
        'cache_clear'          => ['risk' => 'critical', 'feature' => 'utilities', 'capability' => 'edit'],
    ];

    public function _remap($method, $params = [])
    {
        $this->dispatch($method, $params);
    }

    // =====================================================================
    // Public
    // =====================================================================

    public function ping()
    {
        return [
            'pong'        => true,
            'api_version' => 'v1',
            'server_time' => date('c'),
        ];
    }

    // =====================================================================
    // Identity and discovery
    // =====================================================================

    public function me()
    {
        $staff = $this->currentStaff();
        $token = $this->currentToken();

        return [
            'staff' => [
                'id'         => (int) $staff->staffid,
                'email'      => $staff->email,
                'firstname'  => $staff->firstname,
                'lastname'   => $staff->lastname,
                'admin'      => (int) $staff->admin === 1,
                'role_id'    => $staff->role !== null ? (int) $staff->role : null,
                'language'   => $staff->default_language,
            ],
            'token' => [
                'id'            => (int) $token->id,
                'label'         => $token->label,
                'prefix'        => $token->token_prefix,
                'risk_max'      => $token->risk_max,
                'scopes'        => $token->scopes ? json_decode($token->scopes, true) : null,
                'created_at'    => $token->created_at,
                'expires_at'    => $token->expires_at,
                'last_used_at'  => $token->last_used_at,
                'request_count' => (int) $token->request_count,
            ],
        ];
    }

    public function system_status()
    {
        $dbVersion = $this->db->query('SELECT VERSION() AS v')->row();

        return [
            'application' => [
                'name'        => $this->option('companyname', 'FennecDash'),
                'environment' => ENVIRONMENT,
                'base_url'    => base_url(),
                'timezone'    => $this->option('default_timezone', date_default_timezone_get()),
                'language'    => $this->option('active_language', 'english'),
            ],
            'api' => [
                'version'     => 'v1',
                'authenticated' => true,
                'staff_id'    => $this->apiAuth->staffId(),
                'risk_max'    => $this->currentToken()->risk_max,
                'server_time' => date('c'),
            ],
            'database' => [
                'connected'      => true,
                'name'           => $this->db->database,
                'server_version' => $dbVersion ? $dbVersion->v : null,
                'prefix'         => db_prefix(),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'sapi'    => php_sapi_name(),
            ],
        ];
    }

    public function system_info()
    {
        $modules = $this->db->get(db_prefix() . 'modules')->result_array();
        $active  = 0;
        foreach ($modules as $module) {
            if ((int) $module['active'] === 1) {
                $active++;
            }
        }

        $lastActivity = $this->db->select('date')->order_by('id', 'desc')->limit(1)
            ->get(db_prefix() . 'activity_log')->row();

        return [
            'counts' => [
                'staff'      => $this->countRows('staff', ['active' => 1, 'is_not_staff' => 0]),
                'clients'    => $this->countRows('clients', ['active' => 1]),
                'leads'      => $this->countRows('leads', ['lost' => 0, 'junk' => 0]),
                'projects'   => $this->countRows('projects'),
                'tasks'      => $this->countRows('tasks'),
                'tickets'    => $this->countRows('tickets'),
                'invoices'   => $this->countRows('invoices'),
                'estimates'  => $this->countRows('estimates'),
                'contracts'  => $this->countRows('contracts'),
                'aae_orders' => $this->countRows('aae_orders'),
            ],
            'modules' => [
                'total'  => count($modules),
                'active' => $active,
            ],
            'last_activity_at' => $lastActivity ? $lastActivity->date : null,
            'generated_at'     => date('c'),
        ];
    }

    public function system_health()
    {
        $checks = [];

        try {
            $this->db->query('SELECT 1');
            $checks[] = $this->check('database', 'ok', 'Database connection succeeded.');
        } catch (Throwable $e) {
            $checks[] = $this->check('database', 'fail', 'Database connection failed.');
        }

        $checks[] = $this->pathCheck('cache_dir', APPPATH . 'cache', true);
        $checks[] = $this->pathCheck('log_dir', APPPATH . 'logs', true);
        $checks[] = $this->pathCheck('uploads_dir', FCPATH . 'uploads', true);
        $checks[] = $this->pathCheck('session_dir', APPPATH . 'logs', false);

        foreach (['mbstring', 'curl', 'openssl', 'json', 'mysqli', 'pdo'] as $extension) {
            $checks[] = $this->check(
                'php_extension_' . $extension,
                extension_loaded($extension) ? 'ok' : 'warn',
                extension_loaded($extension) ? 'Loaded.' : 'Not loaded.'
            );
        }

        $free = @disk_free_space(FCPATH);
        $checks[] = $this->check(
            'disk_free',
            $free !== false && $free > 500 * 1024 * 1024 ? 'ok' : 'warn',
            $free !== false ? round($free / 1048576, 1) . ' MB free.' : 'Unable to read disk space.'
        );

        $missing = [];
        foreach (['api_tokens', 'api_audit_log', 'api_rate_limits', 'api_approvals'] as $table) {
            if (! $this->tableExists($table)) {
                $missing[] = db_prefix() . $table;
            }
        }
        $checks[] = $this->check(
            'agent_api_schema',
            $missing === [] ? 'ok' : 'fail',
            $missing === [] ? 'All Agent API tables exist.' : 'Missing tables: ' . implode(', ', $missing)
        );

        $overall = 'ok';
        foreach ($checks as $check) {
            if ($check['status'] === 'fail') {
                $overall = 'fail';
                break;
            }
            if ($check['status'] === 'warn') {
                $overall = 'warn';
            }
        }

        return [
            'status'     => $overall,
            'checks'     => $checks,
            'checked_at' => date('c'),
        ];
    }

    public function system_config()
    {
        // Explicit allowlist. No SMTP, payment, OAuth or API credentials.
        $keys = [
            'companyname', 'company_phonenumber', 'company_vat', 'company_country',
            'default_timezone', 'active_language', 'date_format', 'time_format',
            'currency', 'default_currency', 'invoice_prefix', 'estimate_prefix',
            'project_prefix', 'task_prefix', 'ticket_prefix',
        ];

        $config = [];

        foreach ($keys as $key) {
            $value = get_option($key);
            $config[$key] = ($value === '' || $value === null) ? null : $value;
        }

        return [
            'application' => $config,
            'runtime'     => [
                'php_version'          => PHP_VERSION,
                'memory_limit'         => ini_get('memory_limit'),
                'max_execution_time'   => ini_get('max_execution_time'),
                'upload_max_filesize'  => ini_get('upload_max_filesize'),
                'post_max_size'        => ini_get('post_max_size'),
            ],
            'redaction_note' => 'Only non-secret settings are exposed. Credentials and secrets are never returned.',
        ];
    }

    public function system_modules()
    {
        $rows = $this->db->order_by('module_name', 'asc')->get(db_prefix() . 'modules')->result_array();

        $modules = [];
        foreach ($rows as $row) {
            $name = $row['module_name'];
            $modules[] = [
                'name'              => $name,
                'installed_version' => $row['installed_version'],
                'active'            => (int) $row['active'] === 1,
                'present_on_disk'   => is_dir(FCPATH . 'modules/' . $name),
                'init_file'         => is_file(FCPATH . 'modules/' . $name . '/' . $name . '.php'),
            ];
        }

        return [
            'total'   => count($modules),
            'active'  => count(array_filter($modules, static fn ($m) => $m['active'])),
            'modules' => $modules,
        ];
    }

    public function system_diagnostics()
    {
        $dbSize = $this->db->query(
            'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS mb,
                    COUNT(*) AS tables
             FROM information_schema.tables WHERE table_schema = DATABASE()'
        )->row();

        $errors24h = $this->db->where('status_code >=', 400)
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->count_all_results(db_prefix() . 'api_audit_log');

        $logStats = $this->logStats();

        return [
            'database' => [
                'size_mb'        => $dbSize ? (float) $dbSize->mb : null,
                'tables'         => $dbSize ? (int) $dbSize->tables : null,
                'server_version' => $this->db->query('SELECT VERSION() AS v')->row()->v,
            ],
            'storage' => [
                'uploads' => $this->dirStats(FCPATH . 'uploads'),
                'cache'   => $this->dirStats(APPPATH . 'cache'),
                'logs'    => $this->dirStats(APPPATH . 'logs'),
            ],
            'logs' => $logStats,
            'api'  => [
                'errors_last_24h' => $errors24h,
                'audit_rows'      => $this->countRows('api_audit_log'),
                'active_tokens'   => (int) $this->db->where('revoked_at IS NULL', null, false)
                    ->count_all_results(db_prefix() . 'api_tokens'),
            ],
            'runtime' => [
                'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 2),
                'peak_memory_mb'  => round(memory_get_peak_usage(true) / 1048576, 2),
                'loaded_extensions'=> count(get_loaded_extensions()),
            ],
            'generated_at' => date('c'),
        ];
    }

    public function system_version()
    {
        $migration = $this->db->select('MAX(version) AS v')->get(db_prefix() . 'migrations')->row();
        $ciVersion = defined('CI_VERSION') ? CI_VERSION : null;

        return [
            'application'      => 'FennecDash',
            'migration_version'=> $migration ? (int) $migration->v : null,
            'codeigniter'      => $ciVersion,
            'php'              => PHP_VERSION,
            'database'         => $this->db->query('SELECT VERSION() AS v')->row()->v,
            'agent_api'        => '1.0.0',
        ];
    }

    public function system_capabilities()
    {
        $resources = array_keys($this->apiResources->available());

        return [
            'api_version' => 'v1',
            'resources'   => $resources,
            'write_operations'    => ['projects_create', 'projects_update'],
            'critical_operations' => ['cache_clear'],
            'idempotent_operations' => ['projects_create'],
            'analytics_operations'  => ['clients_summary', 'reports_sales'],
            'risk_levels' => [
                'read'     => 'Read-only access to CRM records and diagnostics.',
                'write'    => 'Creates or updates records. Audited and permission checked.',
                'critical' => 'Destructive or platform-level operations. Requires a single-use approval token.',
            ],
            'limits' => [
                'per_page_max'          => 100,
                'sql_max_rows'          => self::SQL_MAX_ROWS,
                'rate_limit_per_minute' => (int) (getenv('MCP_RATE_LIMIT') ?: 120),
                'json_body_max_bytes'   => self::MAX_JSON_BYTES,
                'idempotency_retention_seconds' => 86400,
            ],
            'headers' => [
                'X-Agent-Name'    => 'Optional agent identity recorded in the audit log (max 120 chars).',
                'X-Agent-Session' => 'Optional agent session identifier recorded in the audit log (max 120 chars).',
                'X-Request-Id'    => 'Optional correlation id, echoed in the response and audit log.',
                'Idempotency-Key' => 'Optional on idempotent writes. Replaying the same key and body returns the stored response.',
            ],
            'modules'     => $this->system_modules()['modules'],
            'unsupported' => [
                'deployment'      => 'FennecDash has no deployment pipeline or orchestration API.',
                'service_restart' => 'No service manager is reachable from the application.',
                'file_upload'     => 'Uploads are session/CSRF bound; not exposed through the Agent API.',
                'arbitrary_sql_writes' => 'The SQL endpoint is read-only and disabled unless MCP_ALLOW_SQL=1.',
                'external_http_proxy'  => 'The API never proxies arbitrary external URLs (SSRF protection).',
                'invoice_create'  => 'Invoice creation, sending, refunds and payment capture are not exposed: no safe Agent API implementation exists yet.',
                'email_send'      => 'Sending e-mail or WhatsApp messages from an agent is not exposed; SMTP and provider credentials are operator-managed.',
                'marketing'       => 'No marketing/campaign tables exist in this FennecDash installation; nothing is exposed for that area.',
                'client_write'    => 'Client/contact create, update and delete are not exposed; only reads and aggregate summaries are available.',
                'task_write'      => 'Task create/update is not exposed; only reads are available.',
                'user_management' => 'Staff, role and permission mutation is not exposed through the Agent API.',
            ],
            'generated_at' => date('c'),
        ];
    }

    public function system_permissions()
    {
        $staffId = $this->apiAuth->staffId();
        $staff   = $this->currentStaff();

        $permissions = $this->db
            ->where('staff_id', $staffId)
            ->get(db_prefix() . 'staff_permissions')
            ->result_array();

        $grouped = [];
        foreach ($permissions as $permission) {
            $grouped[$permission['feature']][] = $permission['capability'];
        }

        $role = null;
        if (! empty($staff->role)) {
            $roleRow = $this->db->where('roleid', (int) $staff->role)->get(db_prefix() . 'roles')->row();
            if ($roleRow) {
                $role = ['id' => (int) $roleRow->roleid, 'name' => $roleRow->name];
            }
        }

        return [
            'staff_id'    => $staffId,
            'is_admin'    => $this->apiAuth->isAdmin(),
            'role'        => $role,
            'permissions' => $grouped,
            'note'        => $this->apiAuth->isAdmin()
                ? 'Administrators implicitly hold every permission.'
                : 'Permissions are the ones assigned to the staff account.',
        ];
    }

    public function resources()
    {
        $resources = [];

        foreach ($this->apiResources->available() as $key => $definition) {
            $resources[] = [
                'name'      => $key,
                'label'     => $definition['label'],
                'id_field'  => $definition['pk'],
                'searchable'=> $definition['search'],
                'filters'   => $definition['filters'],
                'sortable'  => $definition['sortable'],
                'endpoint'  => 'GET /api/v1/' . $key,
            ];
        }

        return ['resources' => $resources];
    }

    // =====================================================================
    // Analytics
    // =====================================================================

    /**
     * Aggregate client counts and balances. Returns aggregates only, never
     * individual client records, so a read-only token can answer "how many
     * clients are active" without dumping the customer database.
     */
    public function clients_summary()
    {
        $totals = $this->db->query(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(active = 1), 0) AS active,
                    COALESCE(SUM(active = 0), 0) AS inactive,
                    COALESCE(SUM(customer_type = \'b2b\'), 0) AS b2b,
                    COALESCE(SUM(customer_type = \'b2c\'), 0) AS b2c,
                    COALESCE(SUM(balance > 0), 0) AS with_balance,
                    COALESCE(SUM(balance), 0) AS total_balance
             FROM `' . db_prefix() . 'clients`'
        )->row_array();

        $newLast30 = $this->db->query(
            'SELECT COUNT(*) AS total FROM `' . db_prefix() . 'clients`
             WHERE datecreated >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
        )->row();

        $byCurrency = $this->db->query(
            'SELECT c.default_currency AS currency_id,
                    COALESCE(cur.name, \'Unknown\') AS currency_name,
                    cur.symbol AS currency_symbol,
                    COUNT(*) AS clients,
                    COALESCE(SUM(c.balance), 0) AS balance
             FROM `' . db_prefix() . 'clients` c
             LEFT JOIN `' . db_prefix() . 'currencies` cur ON cur.id = c.default_currency
             GROUP BY c.default_currency, cur.name, cur.symbol
             ORDER BY clients DESC'
        )->result_array();

        $byCountry = $this->db->query(
            'SELECT c.country AS country_id,
                    COALESCE(co.short_name, \'Unknown\') AS country,
                    COUNT(*) AS clients
             FROM `' . db_prefix() . 'clients` c
             LEFT JOIN `' . db_prefix() . 'countries` co ON co.country_id = c.country
             GROUP BY c.country, co.short_name
             ORDER BY clients DESC
             LIMIT 10'
        )->result_array();

        return [
            'totals' => [
                'total'         => (int) $totals['total'],
                'active'        => (int) $totals['active'],
                'inactive'      => (int) $totals['inactive'],
                'b2b'           => (int) $totals['b2b'],
                'b2c'           => (int) $totals['b2c'],
                'with_balance'  => (int) $totals['with_balance'],
                'total_balance' => (float) $totals['total_balance'],
                'new_last_30_days' => (int) $newLast30->total,
            ],
            'by_currency' => array_map(static function ($row) {
                return [
                    'currency_id'     => (int) $row['currency_id'],
                    'currency_name'   => $row['currency_name'],
                    'currency_symbol' => $row['currency_symbol'],
                    'clients'         => (int) $row['clients'],
                    'balance'         => (float) $row['balance'],
                ];
            }, $byCurrency),
            'by_country' => array_map(static function ($row) {
                return [
                    'country_id' => (int) $row['country_id'],
                    'country'    => $row['country'],
                    'clients'    => (int) $row['clients'],
                ];
            }, $byCountry),
            'note' => 'Balances are nominal amounts grouped by client default currency; no FX conversion is applied.',
            'generated_at' => date('c'),
        ];
    }

    /**
     * Sales report over a bounded date range. Mirrors the real invoice and
     * payment tables; draft (6) and cancelled (5) invoices are excluded unless
     * an explicit status filter is supplied.
     */
    public function reports_sales()
    {
        $from     = $this->reportDate('from', date('Y-01-01'));
        $to       = $this->reportDate('to', date('Y-m-d'));
        $currency = $this->input->get('currency');
        $status   = $this->input->get('status');
        $limit    = min(100, max(1, (int) ($this->input->get('limit') ?? 20)));

        if (strtotime($from) > strtotime($to)) {
            throw new Api_exception('validation_failed', 'Field "from" must not be after "to".', 422, ['field' => 'from']);
        }

        if ((strtotime($to) - strtotime($from)) > 3660 * 86400) {
            throw new Api_exception('validation_failed', 'The report range is limited to 10 years.', 422, ['field' => 'from']);
        }

        $currencyId = null;
        if ($currency !== null && $currency !== '') {
            if (! is_numeric($currency) || (int) $currency <= 0) {
                throw new Api_exception('validation_failed', 'Field "currency" must be a positive integer.', 422, ['field' => 'currency']);
            }
            $currencyId = (int) $currency;

            if (! $this->db->where('id', $currencyId)->get(db_prefix() . 'currencies')->row()) {
                throw new Api_exception('validation_failed', 'No currency exists with id ' . $currencyId . '.', 422, ['field' => 'currency']);
            }
        }

        $statusId = null;
        if ($status !== null && $status !== '') {
            if (! is_numeric($status) || (int) $status < 1 || (int) $status > 6) {
                throw new Api_exception('validation_failed', 'Field "status" must be an invoice status id between 1 and 6.', 422, ['field' => 'status']);
            }
            $statusId = (int) $status;
        }

        $where  = 'i.date >= ? AND i.date <= ?';
        $params = [$from, $to];

        if ($statusId !== null) {
            $where   .= ' AND i.status = ?';
            $params[] = $statusId;
        } else {
            $where .= ' AND i.status NOT IN (5, 6)';
        }

        if ($currencyId !== null) {
            $where   .= ' AND i.currency = ?';
            $params[] = $currencyId;
        }

        $paidJoin = 'LEFT JOIN (
                        SELECT invoiceid, SUM(amount) AS paid
                        FROM `' . db_prefix() . 'invoicepaymentrecords`
                        GROUP BY invoiceid
                     ) p ON p.invoiceid = i.id';

        $totals = $this->db->query(
            'SELECT COUNT(*) AS invoice_count,
                    COALESCE(SUM(i.total), 0) AS invoiced,
                    COALESCE(SUM(p.paid), 0) AS paid
             FROM `' . db_prefix() . 'invoices` i ' . $paidJoin . '
             WHERE ' . $where,
            $params
        )->row();

        $byCurrency = $this->db->query(
            'SELECT i.currency AS currency_id,
                    COALESCE(cur.name, \'Unknown\') AS currency_name,
                    cur.symbol AS currency_symbol,
                    COUNT(*) AS invoice_count,
                    COALESCE(SUM(i.total), 0) AS invoiced,
                    COALESCE(SUM(p.paid), 0) AS paid
             FROM `' . db_prefix() . 'invoices` i ' . $paidJoin . '
             LEFT JOIN `' . db_prefix() . 'currencies` cur ON cur.id = i.currency
             WHERE ' . $where . '
             GROUP BY i.currency, cur.name, cur.symbol
             ORDER BY invoiced DESC',
            $params
        )->result_array();

        $byStatus = $this->db->query(
            'SELECT i.status, COUNT(*) AS invoice_count, COALESCE(SUM(i.total), 0) AS invoiced
             FROM `' . db_prefix() . 'invoices` i
             WHERE ' . $where . '
             GROUP BY i.status
             ORDER BY i.status ASC',
            $params
        )->result_array();

        $monthly = $this->db->query(
            'SELECT DATE_FORMAT(i.date, \'%Y-%m\') AS period,
                    COUNT(*) AS invoice_count,
                    COALESCE(SUM(i.total), 0) AS invoiced,
                    COALESCE(SUM(p.paid), 0) AS paid
             FROM `' . db_prefix() . 'invoices` i ' . $paidJoin . '
             WHERE ' . $where . '
             GROUP BY period
             ORDER BY period ASC',
            $params
        )->result_array();

        $topClients = $this->db->query(
            'SELECT i.clientid,
                    COALESCE(c.company, NULLIF(i.deleted_customer_name, \'\'), CONCAT(\'Client #\', i.clientid)) AS company,
                    COUNT(*) AS invoice_count,
                    COALESCE(SUM(i.total), 0) AS invoiced,
                    COALESCE(SUM(p.paid), 0) AS paid
             FROM `' . db_prefix() . 'invoices` i ' . $paidJoin . '
             LEFT JOIN `' . db_prefix() . 'clients` c ON c.userid = i.clientid
             WHERE ' . $where . '
             GROUP BY i.clientid, c.company, i.deleted_customer_name
             ORDER BY invoiced DESC
             LIMIT ' . $limit,
            $params
        )->result_array();

        $labels = [
            1 => 'unpaid',
            2 => 'paid',
            3 => 'partially_paid',
            4 => 'overdue',
            5 => 'cancelled',
            6 => 'draft',
        ];

        $invoiced    = (float) $totals->invoiced;
        $paid        = (float) $totals->paid;
        $outstanding = $invoiced - $paid;

        return [
            'period' => ['from' => $from, 'to' => $to],
            'filters' => [
                'currency' => $currencyId,
                'status'   => $statusId,
                'excluded_statuses' => $statusId === null ? ['cancelled', 'draft'] : [],
            ],
            'totals' => [
                'invoice_count' => (int) $totals->invoice_count,
                'invoiced'      => $invoiced,
                'paid'          => $paid,
                'outstanding'   => $outstanding,
            ],
            'by_currency' => array_map(static function ($row) {
                $rowInvoiced = (float) $row['invoiced'];
                $rowPaid     = (float) $row['paid'];

                return [
                    'currency_id'     => (int) $row['currency_id'],
                    'currency_name'   => $row['currency_name'],
                    'currency_symbol' => $row['currency_symbol'],
                    'invoice_count'   => (int) $row['invoice_count'],
                    'invoiced'        => $rowInvoiced,
                    'paid'            => $rowPaid,
                    'outstanding'     => $rowInvoiced - $rowPaid,
                ];
            }, $byCurrency),
            'by_status' => array_map(static function ($row) use ($labels) {
                return [
                    'status'        => (int) $row['status'],
                    'status_label'  => $labels[(int) $row['status']] ?? 'unknown',
                    'invoice_count' => (int) $row['invoice_count'],
                    'invoiced'      => (float) $row['invoiced'],
                ];
            }, $byStatus),
            'monthly' => array_map(static function ($row) {
                return [
                    'period'        => $row['period'],
                    'invoice_count' => (int) $row['invoice_count'],
                    'invoiced'      => (float) $row['invoiced'],
                    'paid'          => (float) $row['paid'],
                ];
            }, $monthly),
            'top_clients' => array_map(static function ($row) {
                return [
                    'client_id'     => (int) $row['clientid'],
                    'company'       => $row['company'],
                    'invoice_count' => (int) $row['invoice_count'],
                    'invoiced'      => (float) $row['invoiced'],
                    'paid'          => (float) $row['paid'],
                ];
            }, $topClients),
            'note' => 'Amounts are nominal in each invoice currency and are never converted. Totals across currencies are the arithmetic sum of the grouped rows; use by_currency for reporting.',
            'generated_at' => date('c'),
        ];
    }

    private function reportDate($key, $default)
    {
        $value = $this->input->get($key);

        if ($value === null || $value === '') {
            return $default;
        }

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new Api_exception('validation_failed', 'Field "' . $key . '" must use YYYY-MM-DD.', 422, ['field' => $key]);
        }

        $date = date_create($value);

        if (! $date || $date->format('Y-m-d') !== $value) {
            throw new Api_exception('validation_failed', 'Field "' . $key . '" is not a valid date.', 422, ['field' => $key]);
        }

        return $value;
    }

    // =====================================================================
    // Read models
    // =====================================================================

    public function projects($id = null) { return $this->resource('projects', $id); }
    public function clients($id = null) { return $this->resource('clients', $id); }
    public function leads($id = null) { return $this->resource('leads', $id); }
    public function invoices($id = null) { return $this->resource('invoices', $id); }
    public function estimates($id = null) { return $this->resource('estimates', $id); }
    public function tasks($id = null) { return $this->resource('tasks', $id); }
    public function tickets($id = null) { return $this->resource('tickets', $id); }
    public function staff($id = null) { return $this->resource('staff', $id); }
    public function contracts($id = null) { return $this->resource('contracts', $id); }
    public function files($id = null) { return $this->resource('files', $id); }
    public function aae_orders($id = null) { return $this->resource('aae_orders', $id); }
    public function activity($id = null) { return $this->resource('activity', $id); }

    private function resource($key, $id)
    {
        if ($id !== null && $id !== '') {
            $row = $this->apiResources->find($key, (int) $id);

            return ['data' => $this->enrich($key, $row)];
        }

        return $this->apiResources->paginate($key, $this->input->get());
    }

    /**
     * Add related data to a single record. Kept small on purpose: list views
     * stay cheap, detail views give an agent enough context to act.
     */
    private function enrich($key, array $row)
    {
        switch ($key) {
            case 'projects':
                $projectId = (int) $row['id'];
                $row['tasks'] = $this->db->select('id, name, status, priority, duedate, datefinished')
                    ->where('rel_id', $projectId)->where('rel_type', 'project')
                    ->order_by('id', 'desc')->limit(50)
                    ->get(db_prefix() . 'tasks')->result_array();
                $row['members'] = $this->db->select('pm.staff_id, s.firstname, s.lastname, s.email')
                    ->from(db_prefix() . 'project_members pm')
                    ->join(db_prefix() . 'staff s', 's.staffid = pm.staff_id', 'left')
                    ->where('pm.project_id', $projectId)
                    ->get()->result_array();
                $row['files'] = $this->db->select('id, file_name, filetype, dateadded')
                    ->where('rel_id', $projectId)->where('rel_type', 'project')
                    ->get(db_prefix() . 'files')->result_array();
                $row['invoices'] = $this->db->select('id, formatted_number, total, status, date')
                    ->where('project_id', $projectId)
                    ->get(db_prefix() . 'invoices')->result_array();
                break;

            case 'leads':
                $row['activity'] = $this->db->select('id, description, date, staffid')
                    ->where('leadid', (int) $row['id'])->order_by('id', 'desc')->limit(20)
                    ->get(db_prefix() . 'lead_activity_log')->result_array();
                break;

            case 'invoices':
                $row['payments'] = $this->db->select('id, amount, paymentmode, date, transactionid')
                    ->where('invoiceid', (int) $row['id'])->order_by('id', 'asc')
                    ->get(db_prefix() . 'invoicepaymentrecords')->result_array();
                break;

            case 'estimates':
                $row['items'] = $this->db->select('id, description, qty, rate, unit')
                    ->where('rel_id', (int) $row['id'])->where('rel_type', 'estimate')
                    ->order_by('item_order', 'asc')
                    ->get(db_prefix() . 'itemable')->result_array();
                break;

            case 'tickets':
                $row['replies'] = (int) $this->db->where('ticketid', (int) $row['ticketid'])
                    ->count_all_results(db_prefix() . 'ticket_replies');
                break;

            case 'tasks':
                $row['assignees'] = $this->db->select('a.staffid, s.firstname, s.lastname')
                    ->from(db_prefix() . 'task_assigned a')
                    ->join(db_prefix() . 'staff s', 's.staffid = a.staffid', 'left')
                    ->where('a.taskid', (int) $row['id'])
                    ->get()->result_array();
                break;

            case 'aae_orders':
                $row['vehicles'] = $this->tableExists('aae_order_vehicles')
                    ? $this->db->where('order_id', (int) $row['id'])->get(db_prefix() . 'aae_order_vehicles')->result_array()
                    : [];
                $row['payments'] = $this->tableExists('aae_order_payments')
                    ? $this->db->where('order_id', (int) $row['id'])->get(db_prefix() . 'aae_order_payments')->result_array()
                    : [];
                break;
        }

        return $row;
    }

    public function search()
    {
        $term  = (string) $this->input->get('q');
        $limit = (int) ($this->input->get('limit') ?? 5);

        // Global search spans several features, so filter the targets by the
        // token owner's effective permissions instead of trusting the route.
        $features = [
            'projects'  => 'projects',
            'clients'   => 'customers',
            'leads'     => 'leads',
            'invoices'  => 'invoices',
            'estimates' => 'estimates',
            'tickets'   => 'tickets',
            'tasks'     => 'tasks',
        ];

        $allowed = [];

        foreach ($features as $resource => $feature) {
            if ($this->apiAuth->canFeature($feature)) {
                $allowed[] = $resource;
            }
        }

        $result = $this->apiResources->search($term, $limit, $allowed);
        $result['meta']['searched_resources'] = $allowed;

        return $result;
    }

    /**
     * Composite diagnostic used by the `fennedash_project_diagnose` tool.
     * It collects evidence first and only then derives findings.
     */
    public function project_diagnose($id = null)
    {
        if ($id === null) {
            throw new Api_exception('validation_failed', 'A project id is required.', 422, ['field' => 'id']);
        }

        $project = $this->apiResources->find('projects', (int) $id);
        $projectId = (int) $project['id'];

        $tasks = $this->db->select('id, name, status, priority, duedate, datefinished, startdate')
            ->where('rel_id', $projectId)->where('rel_type', 'project')
            ->get(db_prefix() . 'tasks')->result_array();

        $openTasks = array_values(array_filter($tasks, static fn ($task) => empty($task['datefinished'])));
        $overdue   = array_values(array_filter($openTasks, static function ($task) {
            return ! empty($task['duedate']) && strtotime($task['duedate']) < time();
        }));

        $invoices = $this->db->select('id, formatted_number, total, status, duedate, date')
            ->where('project_id', $projectId)
            ->get(db_prefix() . 'invoices')->result_array();

        $tickets = $this->db->select('ticketid, subject, status, priority, date, lastreply')
            ->where('project_id', $projectId)
            ->get(db_prefix() . 'tickets')->result_array();

        $files = $this->db->select('id, file_name, filetype, dateadded')
            ->where('rel_id', $projectId)->where('rel_type', 'project')
            ->get(db_prefix() . 'files')->result_array();

        $projectActivity = $this->tableExists('project_activity')
            ? $this->db->select('id, staff_id, contact_id, fullname, description_key, additional_data, dateadded')
                ->where('project_id', $projectId)->order_by('id', 'desc')->limit(20)
                ->get(db_prefix() . 'project_activity')->result_array()
            : [];

        $lastActivityAt = $projectActivity[0]['dateadded'] ?? null;

        $findings   = [];
        $recommended = [];

        if ($overdue !== []) {
            $findings[] = [
                'id'       => 'overdue_tasks',
                'severity' => 'high',
                'title'    => count($overdue) . ' overdue open task(s)',
                'evidence' => array_slice(array_column($overdue, 'name'), 0, 5),
            ];
            $recommended[] = 'Review overdue tasks and update their due dates or close them.';
        }

        if (! empty($project['deadline']) && strtotime($project['deadline']) < time() && (int) $project['progress'] < 100) {
            $findings[] = [
                'id'       => 'deadline_passed',
                'severity' => 'high',
                'title'    => 'Deadline passed while the project is still incomplete.',
                'evidence' => ['deadline' => $project['deadline'], 'progress' => (int) $project['progress']],
            ];
            $recommended[] = 'Re-plan the deadline or record why the project is late.';
        }

        if ($lastActivityAt === null && $projectActivity === []) {
            $findings[] = [
                'id'       => 'no_project_activity',
                'severity' => 'medium',
                'title'    => 'No project activity has been recorded.',
                'evidence' => [],
            ];
            $recommended[] = 'Confirm the project is actively tracked in FennecDash.';
        } elseif ($lastActivityAt !== null && strtotime($lastActivityAt) < strtotime('-30 days')) {
            $findings[] = [
                'id'       => 'stale_activity',
                'severity' => 'medium',
                'title'    => 'No project activity for more than 30 days.',
                'evidence' => ['last_activity_at' => $lastActivityAt],
            ];
            $recommended[] = 'Check whether the project is dormant or missing updates.';
        }

        $unpaid = array_values(array_filter($invoices, static fn ($invoice) => (int) $invoice['status'] !== 2));
        if ($unpaid !== []) {
            $findings[] = [
                'id'       => 'unpaid_invoices',
                'severity' => 'medium',
                'title'    => count($unpaid) . ' invoice(s) are not marked as paid.',
                'evidence' => array_slice(array_column($unpaid, 'formatted_number'), 0, 5),
            ];
            $recommended[] = 'Verify payment status before closing the project.';
        }

        $openTickets = array_values(array_filter($tickets, static fn ($ticket) => ! in_array((int) $ticket['status'], [4, 5], true)));
        if ($openTickets !== []) {
            $findings[] = [
                'id'       => 'open_tickets',
                'severity' => 'low',
                'title'    => count($openTickets) . ' open ticket(s) linked to this project.',
                'evidence' => array_slice(array_column($openTickets, 'subject'), 0, 5),
            ];
            $recommended[] = 'Resolve open tickets before closing the project.';
        }

        if ($findings === []) {
            $recommended[] = 'No structural issue detected. A human should still confirm the business status.';
        }

        return [
            'data' => [
                'project'   => $project,
                'evidence'  => [
                    'tasks'            => ['count' => count($tasks), 'open' => count($openTasks), 'overdue' => count($overdue)],
                    'invoices'         => $invoices,
                    'tickets'          => $tickets,
                    'files'            => $files,
                    'project_activity' => $projectActivity,
                ],
                'findings'        => $findings,
                'recommended_actions' => $recommended,
                'verified'        => true,
                'note'            => 'Findings are derived from live data. No change was made by this diagnostic.',
            ],
            'meta' => ['project_id' => $projectId, 'generated_at' => date('c')],
        ];
    }

    // =====================================================================
    // Project activity
    // =====================================================================

    public function project_activity($id = null)
    {
        if ($id === null) {
            throw new Api_exception('validation_failed', 'A project id is required.', 422, ['field' => 'id']);
        }

        $projectId = (int) $id;
        $exists    = $this->db->select('id')->where('id', $projectId)
            ->get(db_prefix() . 'projects')->row();

        if (! $exists) {
            throw new Api_exception('not_found', 'No project with id ' . $projectId . '.', 404);
        }

        if (! $this->tableExists('project_activity')) {
            return ['data' => [], 'meta' => ['project_id' => $projectId, 'count' => 0, 'supported' => false]];
        }

        $limit = min(100, max(1, (int) ($this->input->get('limit') ?? 50)));

        $rows = $this->db
            ->select('pa.id, pa.staff_id, pa.contact_id, pa.fullname, pa.visible_to_customer, pa.description_key, pa.additional_data, pa.dateadded')
            ->from(db_prefix() . 'project_activity pa')
            ->where('pa.project_id', $projectId)
            ->order_by('pa.id', 'desc')
            ->limit($limit)
            ->get()->result_array();

        return [
            'data' => $rows,
            'meta' => [
                'project_id' => $projectId,
                'count'      => count($rows),
                'limit'      => $limit,
                'supported'  => true,
            ],
        ];
    }

    // =====================================================================
    // Roles
    // =====================================================================

    public function roles_list()
    {
        $rows = $this->db->select('roleid, name, permissions')
            ->order_by('roleid', 'asc')
            ->get(db_prefix() . 'roles')->result_array();

        $roles = [];

        foreach ($rows as $row) {
            $permissions = [];
            if (! empty($row['permissions'])) {
                $decoded = @unserialize($row['permissions'], ['allowed_classes' => false]);
                if (is_array($decoded)) {
                    $permissions = $decoded;
                }
            }

            $roles[] = [
                'id'            => (int) $row['roleid'],
                'name'          => $row['name'],
                'features'      => array_keys($permissions),
                'permissions'   => $permissions,
                'staff_count'   => (int) $this->db->where('role', (int) $row['roleid'])
                    ->count_all_results(db_prefix() . 'staff'),
            ];
        }

        return ['total' => count($roles), 'data' => $roles];
    }

    // =====================================================================
    // API audit trail
    // =====================================================================

    public function audit_list()
    {
        $limit  = min(200, max(1, (int) ($this->input->get('limit') ?? 50)));
        $offset = max(0, (int) ($this->input->get('offset') ?? 0));
        $action = trim((string) ($this->input->get('action') ?? ''));
        $status = $this->input->get('status');
        $since  = trim((string) ($this->input->get('since') ?? ''));
        $scope  = (string) ($this->input->get('scope') ?? 'token');
        $agent  = trim((string) ($this->input->get('agent') ?? ''));

        if ($scope === 'all' && ! $this->apiAuth->isAdmin()) {
            throw new Api_exception('forbidden', 'Only an administrator can read the whole audit trail.', 403);
        }

        $apply = function () use ($action, $status, $since, $scope, $agent) {
            if ($scope !== 'all') {
                $this->db->where('token_id', (int) $this->currentToken()->id);
            }

            if ($agent !== '') {
                $this->db->where('agent', $agent);
            }

            if ($action !== '') {
                $this->db->like('action', $action, 'both');
            }

            if ($status !== null && $status !== '') {
                $this->db->where('status_code', (int) $status);
            }

            if ($since !== '') {
                $sinceTs = strtotime($since);
                if ($sinceTs === false) {
                    throw new Api_exception('validation_failed', 'Field "since" must be a valid date.', 422, ['field' => 'since']);
                }
                $this->db->where('created_at >=', date('Y-m-d H:i:s', $sinceTs));
            }
        };

        $apply();
        $total = (int) $this->db->count_all_results(db_prefix() . 'api_audit_log');

        $apply();
        $rows = $this->db->order_by('id', 'desc')
            ->limit($limit, $offset)
            ->get(db_prefix() . 'api_audit_log')->result_array();

        $entries = array_map(function ($row) {
            return [
                'id'              => (int) $row['id'],
                'token_id'        => $row['token_id'] !== null ? (int) $row['token_id'] : null,
                'staff_id'        => $row['staff_id'] !== null ? (int) $row['staff_id'] : null,
                'action'          => $row['action'],
                'risk'            => $row['risk'],
                'http_method'     => $row['http_method'],
                'path'            => $row['path'],
                'status_code'     => (int) $row['status_code'],
                'ip'              => $row['ip'],
                'agent'           => $row['agent'] ?? null,
                'agent_session'   => $row['agent_session'] ?? null,
                'request_id'      => $row['request_id'] ?? null,
                'request_summary' => $row['request_summary'] ? json_decode($row['request_summary'], true) : null,
                'response_ms'     => (int) $row['response_ms'],
                'created_at'      => $row['created_at'],
            ];
        }, $rows);

        return [
            'data' => $entries,
            'meta' => [
                'total'  => $total,
                'count'  => count($entries),
                'limit'  => $limit,
                'offset' => $offset,
                'scope'  => $scope === 'all' ? 'all' : 'token',
                'agent'  => $agent !== '' ? $agent : null,
            ],
        ];
    }

    // =====================================================================
    // Integrations
    // =====================================================================

    public function integrations_list()
    {
        $modules = $this->system_modules()['modules'];

        $emailConfigured = (string) get_option('email_protocol') === 'smtp'
            && trim((string) get_option('smtp_host')) !== '';

        $paymentModes = [];
        if ($this->tableExists('payment_modes')) {
            $paymentModes = $this->db->select('id, name, active')
                ->order_by('id', 'asc')
                ->get(db_prefix() . 'payment_modes')->result_array();
        }

        return [
            'modules'        => $modules,
            'email'          => [
                'protocol'   => get_option('email_protocol') ?: null,
                'configured' => $emailConfigured,
                'from_email_set' => trim((string) get_option('smtp_email')) !== '',
            ],
            'ai'             => [
                'provider'    => get_option('ai_provider') ?: null,
                'key_present' => trim((string) get_option('openai_api_key')) !== '',
            ],
            'payment_modes'  => $paymentModes,
            'notes'          => [
                'Secrets are never returned. Only presence and status flags are exposed.',
                'Automation/workflow execution is not implemented by FennecDash; no trigger tool is provided.',
            ],
            'generated_at'   => date('c'),
        ];
    }

    // =====================================================================
    // Dashboard
    // =====================================================================

    public function dashboard_overview()
    {
        $paid = $this->db->select('SUM(amount) AS total')->get(db_prefix() . 'invoicepaymentrecords')->row();
        $invoiced = $this->db->select('SUM(total) AS total')->where('status !=', 4)->get(db_prefix() . 'invoices')->row();

        $projectsByStatus = $this->db->select('status, COUNT(*) AS total')
            ->group_by('status')->get(db_prefix() . 'projects')->result_array();

        $ticketsOpen = $this->db->where('status !=', 5)->count_all_results(db_prefix() . 'tickets');
        $tasksOpen   = $this->db->where('datefinished IS NULL', null, false)->count_all_results(db_prefix() . 'tasks');

        $aae = null;
        if ($this->tableExists('aae_orders')) {
            $aae = $this->db->select('stage, COUNT(*) AS total, SUM(amount_client) AS amount_client, SUM(margin) AS margin')
                ->group_by('stage')->get(db_prefix() . 'aae_orders')->result_array();
        }

        $currency = $this->db->select('name')->where('isdefault', 1)->limit(1)
            ->get(db_prefix() . 'currencies')->row();

        return [
            'finance' => [
                'invoiced_total' => (float) ($invoiced->total ?? 0),
                'paid_total'     => (float) ($paid->total ?? 0),
                'outstanding'    => round((float) ($invoiced->total ?? 0) - (float) ($paid->total ?? 0), 2),
                'currency'       => $currency ? $currency->name : (get_option('currency') ?: null),
            ],
            'projects' => [
                'by_status' => $projectsByStatus,
                'total'     => array_sum(array_map(static fn ($row) => (int) $row['total'], $projectsByStatus)),
            ],
            'operations' => [
                'open_tasks'   => $tasksOpen,
                'open_tickets' => $ticketsOpen,
            ],
            'aae' => $aae,
            'generated_at' => date('c'),
        ];
    }

    public function dashboard_stats()
    {
        $months = [];

        for ($i = 5; $i >= 0; $i--) {
            $start = date('Y-m-01', strtotime('-' . $i . ' months'));
            $end   = date('Y-m-t 23:59:59', strtotime('-' . $i . ' months'));

            $row = $this->db->select('SUM(amount) AS total')
                ->where('date >=', $start)->where('date <=', $end)
                ->get(db_prefix() . 'invoicepaymentrecords')->row();

            $months[] = ['month' => date('Y-m', strtotime($start)), 'revenue' => (float) ($row->total ?? 0)];
        }

        return [
            'revenue_by_month' => $months,
            'counts' => [
                'leads_by_status'    => $this->db->select('status, COUNT(*) AS total')->group_by('status')->get(db_prefix() . 'leads')->result_array(),
                'tasks_by_status'    => $this->db->select('status, COUNT(*) AS total')->group_by('status')->get(db_prefix() . 'tasks')->result_array(),
                'tickets_by_status'  => $this->db->select('status, COUNT(*) AS total')->group_by('status')->get(db_prefix() . 'tickets')->result_array(),
                'projects_by_status' => $this->db->select('status, COUNT(*) AS total')->group_by('status')->get(db_prefix() . 'projects')->result_array(),
            ],
            'generated_at' => date('c'),
        ];
    }

    public function dashboard_activity()
    {
        $limit = min(100, max(1, (int) ($this->input->get('limit') ?? 25)));

        $rows = $this->db->select('a.id, a.description, a.date, a.staffid, s.firstname, s.lastname')
            ->from(db_prefix() . 'activity_log a')
            ->join(db_prefix() . 'staff s', 's.staffid = a.staffid', 'left')
            ->order_by('a.id', 'desc')->limit($limit)
            ->get()->result_array();

        return ['data' => $rows, 'meta' => ['limit' => $limit, 'count' => count($rows)]];
    }

    public function dashboard_widgets()
    {
        $widgets = get_dashboard_widgets();

        $list = [];
        foreach ($widgets as $widget) {
            $path = (string) ($widget['path'] ?? '');
            $id   = $path !== '' ? basename($path) : null;

            $list[] = [
                'id'        => $id,
                'name'      => $id !== null ? ucwords(str_replace('_', ' ', $id)) : null,
                'path'      => $path,
                'container' => $widget['container'] ?? null,
            ];
        }

        return ['total' => count($list), 'widgets' => $list];
    }

    // =====================================================================
    // Logs
    // =====================================================================

    public function logs_list()
    {
        return $this->collectLogs(false);
    }

    public function logs_search()
    {
        return $this->collectLogs(true);
    }

    private function collectLogs($requireTerm)
    {
        $level    = strtoupper((string) ($this->input->get('level') ?? ''));
        $term     = trim((string) ($this->input->get('q') ?? ''));
        $limit    = min(500, max(1, (int) ($this->input->get('limit') ?? 100)));
        $since    = (string) ($this->input->get('since') ?? '');
        $file     = (string) ($this->input->get('file') ?? '');

        if ($requireTerm && mb_strlen($term) < 2) {
            throw new Api_exception('validation_failed', 'Provide a search term of at least 2 characters.', 422, ['field' => 'q']);
        }

        $files = glob(APPPATH . 'logs/log-*.php') ?: [];
        sort($files);
        $files = array_reverse($files);

        if ($file !== '') {
            $files = array_values(array_filter($files, static fn ($path) => basename($path) === basename($file)));
        }

        $entries = [];
        $scanned = 0;

        foreach ($files as $path) {
            if (count($entries) >= $limit) {
                break;
            }

            $scanned++;
            $lines = $this->readLogTail($path, 2 * 1024 * 1024);

            foreach ($lines as $line) {
                if (count($entries) >= $limit) {
                    break;
                }

                if (! preg_match('/^(DEBUG|INFO|ERROR|ALL)\s*-\s*(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s*-->\s*(.*)$/u', $line, $matches)) {
                    continue;
                }

                if ($level !== '' && $matches[1] !== $level) {
                    continue;
                }

                if ($since !== '' && strtotime($matches[2]) < strtotime($since)) {
                    continue;
                }

                if ($term !== '' && mb_stripos($matches[3], $term) === false) {
                    continue;
                }

                $entries[] = [
                    'level'     => $matches[1],
                    'timestamp' => $matches[2],
                    'message'   => $matches[3],
                    'file'      => basename($path),
                ];
            }
        }

        $entries = array_reverse($entries);

        return [
            'data' => $entries,
            'meta' => [
                'count'         => count($entries),
                'files_scanned' => $scanned,
                'limit'         => $limit,
                'level'         => $level !== '' ? $level : null,
                'query'         => $term !== '' ? $term : null,
            ],
        ];
    }

    /**
     * Read at most $maxBytes from the end of a log file.
     *
     * @return string[]
     */
    private function readLogTail($path, $maxBytes)
    {
        $size = @filesize($path);

        if ($size === false) {
            return [];
        }

        $handle = @fopen($path, 'r');

        if (! $handle) {
            return [];
        }

        if ($size > $maxBytes) {
            fseek($handle, $size - $maxBytes);
            fgets($handle); // drop the first partial line
        }

        $lines = [];
        while (($line = fgets($handle)) !== false) {
            $lines[] = rtrim($line, "\r\n");
        }

        fclose($handle);

        return $lines;
    }

    private function logStats()
    {
        $files = glob(APPPATH . 'logs/log-*.php') ?: [];
        $errors = 0;
        $entries = 0;

        foreach ($files as $path) {
            if (filemtime($path) < strtotime('-7 days')) {
                continue;
            }

            foreach ($this->readLogTail($path, 512 * 1024) as $line) {
                if (preg_match('/^ERROR\s*-/', $line)) {
                    $errors++;
                }
                if (preg_match('/^(DEBUG|INFO|ERROR|ALL)\s*-/', $line)) {
                    $entries++;
                }
            }
        }

        return [
            'files'          => count($files),
            'entries_7d'     => $entries,
            'errors_7d'      => $errors,
            'log_threshold'  => config_item('log_threshold'),
        ];
    }

    // =====================================================================
    // Database inspection
    // =====================================================================

    public function database_tables()
    {
        $rows = $this->db->query(
            'SELECT table_name AS name, table_rows AS approx_rows,
                    ROUND((data_length + index_length) / 1024, 1) AS size_kb,
                    engine, table_collation AS collation
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
             ORDER BY (data_length + index_length) DESC'
        )->result_array();

        return ['total' => count($rows), 'data' => $rows];
    }

    public function database_schema($table = null)
    {
        if ($table === null || $table === '') {
            throw new Api_exception('validation_failed', 'A table name is required.', 422, ['field' => 'table']);
        }

        $table = preg_replace('/[^A-Za-z0-9_]/', '', (string) $table);

        if (strpos($table, db_prefix()) !== 0) {
            $table = db_prefix() . $table;
        }

        if (! $this->tableExists(substr($table, strlen(db_prefix())))) {
            throw new Api_exception('not_found', 'Unknown table: ' . $table, 404);
        }

        $columns = $this->db->query(
            'SELECT column_name AS name, column_type AS type, is_nullable AS nullable,
                    column_default AS `default`, column_key AS `key`, extra
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ?
             ORDER BY ordinal_position',
            [$table]
        )->result_array();

        $indexes = $this->db->query(
            'SELECT index_name AS name, non_unique AS non_unique, column_name AS column_name
             FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ?
             ORDER BY index_name, seq_in_index',
            [$table]
        )->result_array();

        return [
            'table'   => $table,
            'columns' => $columns,
            'indexes' => $indexes,
        ];
    }

    public function database_stats()
    {
        $db = $this->db->query(
            'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                    COUNT(*) AS tables
             FROM information_schema.tables WHERE table_schema = DATABASE()'
        )->row();

        $uptime = $this->db->query("SHOW GLOBAL STATUS LIKE 'Uptime'")->row();
        $top = $this->db->query(
            'SELECT table_name AS name, table_rows AS approx_rows,
                    ROUND((data_length + index_length) / 1024, 1) AS size_kb
             FROM information_schema.tables WHERE table_schema = DATABASE()
             ORDER BY (data_length + index_length) DESC LIMIT 10'
        )->result_array();

        return [
            'database'       => $this->db->database,
            'server_version' => $this->db->query('SELECT VERSION() AS v')->row()->v,
            'size_mb'        => $db ? (float) $db->size_mb : null,
            'tables'         => $db ? (int) $db->tables : null,
            'uptime_seconds' => $uptime ? (int) $uptime->Value : null,
            'largest_tables' => $top,
        ];
    }

    /**
     * Guarded, read-only SQL. Disabled unless MCP_ALLOW_SQL=1.
     */
    public function database_query()
    {
        if ((string) (getenv('MCP_ALLOW_SQL') ?: '0') !== '1') {
            throw new Api_exception(
                'sql_disabled',
                'Raw SQL is disabled. Set MCP_ALLOW_SQL=1 on the FennecDash host to enable read-only queries.',
                403
            );
        }

        $input = $this->input->method(true) === 'POST' ? $this->jsonInput() : $this->input->get();
        $sql   = trim((string) ($input['sql'] ?? ''));

        $this->assertReadOnlySql($sql);

        $limitApplied = false;

        if (! preg_match('/\blimit\s+\d+/i', $sql)) {
            $sql .= ' LIMIT ' . self::SQL_MAX_ROWS;
            $limitApplied = true;
        }

        $started = microtime(true);

        try {
            $this->db->query('SET SESSION MAX_EXECUTION_TIME=5000');
        } catch (Throwable $e) {
            // MariaDB uses max_statement_time (seconds) instead.
            try {
                $this->db->query('SET SESSION max_statement_time=5');
            } catch (Throwable $ignored) {
                // Older servers: rely on the PHP request timeout instead.
            }
        }

        try {
            $query = $this->db->query($sql);
        } catch (Throwable $e) {
            throw new Api_exception('sql_error', 'The query failed: ' . $e->getMessage(), 400);
        }

        if ($query === false) {
            throw new Api_exception('sql_error', 'The query failed.', 400);
        }

        $rows = is_object($query) && method_exists($query, 'result_array') ? $query->result_array() : [];

        if (count($rows) > self::SQL_MAX_ROWS) {
            $rows = array_slice($rows, 0, self::SQL_MAX_ROWS);
        }

        return [
            'data' => [
                'columns'       => $rows === [] ? [] : array_keys($rows[0]),
                'rows'          => $rows,
                'row_count'     => count($rows),
                'limit_applied' => $limitApplied,
                'duration_ms'   => (int) round((microtime(true) - $started) * 1000),
            ],
            'meta' => ['read_only' => true, 'max_rows' => self::SQL_MAX_ROWS],
        ];
    }

    private function assertReadOnlySql($sql)
    {
        if ($sql === '') {
            throw new Api_exception('validation_failed', 'Provide a SQL statement.', 422, ['field' => 'sql']);
        }

        $normalized = preg_replace('#/\*.*?\*/#s', ' ', $sql);
        $normalized = preg_replace('/(--|#)[^\n]*/', ' ', $normalized);
        $normalized = trim($normalized);

        if (! preg_match('/^(SELECT|WITH)\b/i', $normalized)) {
            throw new Api_exception('sql_not_allowed', 'Only SELECT (or WITH ... SELECT) statements are allowed.', 403);
        }

        $forbidden = [
            'insert', 'update', 'delete', 'replace', 'drop', 'alter', 'create', 'truncate',
            'grant', 'revoke', 'call', 'execute', 'prepare', 'handler', 'load_file',
            'outfile', 'dumpfile', 'sleep', 'benchmark', 'into',
        ];

        foreach ($forbidden as $keyword) {
            if (preg_match('/\b' . $keyword . '\b/i', $normalized)) {
                throw new Api_exception('sql_not_allowed', 'The statement contains a forbidden keyword: ' . $keyword, 403);
            }
        }

        foreach (self::SQL_DENY_TABLES as $table) {
            if (preg_match('/\b' . preg_quote(db_prefix() . $table, '/') . '\b/i', $normalized)
                || preg_match('/\b' . preg_quote($table, '/') . '\b/i', $normalized)) {
                throw new Api_exception('sql_not_allowed', 'Queries against ' . $table . ' are not permitted.', 403);
            }
        }

        if (preg_match('/\b(password|google_auth_secret|two_factor_auth_code|token_hash)\b/i', $normalized)) {
            throw new Api_exception('sql_not_allowed', 'The query references a sensitive column.', 403);
        }

        if (substr_count(rtrim($normalized, ';'), ';') > 0) {
            throw new Api_exception('sql_not_allowed', 'Only a single statement is allowed.', 403);
        }
    }

    // =====================================================================
    // Storage
    // =====================================================================

    public function storage_stats()
    {
        $uploads = $this->dirStats(FCPATH . 'uploads');
        $media   = $this->dirStats(FCPATH . 'media');
        $cache   = $this->dirStats(APPPATH . 'cache');
        $logs    = $this->dirStats(APPPATH . 'logs');

        $byExtension = [];
        foreach (['uploads', 'media'] as $dir) {
            $path = FCPATH . $dir;
            if (! is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $ext = strtolower($file->getExtension() ?: 'none');
                if (! isset($byExtension[$ext])) {
                    $byExtension[$ext] = ['files' => 0, 'bytes' => 0];
                }
                $byExtension[$ext]['files']++;
                $byExtension[$ext]['bytes'] += $file->getSize();
            }
        }

        arsort($byExtension);

        return [
            'directories' => [
                'uploads' => $uploads,
                'media'   => $media,
                'cache'   => $cache,
                'logs'    => $logs,
            ],
            'by_extension' => array_slice($byExtension, 0, 15, true),
            'generated_at' => date('c'),
        ];
    }

    private function dirStats($path)
    {
        if (! is_dir($path)) {
            return ['exists' => false, 'files' => 0, 'size_mb' => 0, 'writable' => false];
        }

        $files = 0;
        $bytes = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files++;
                $bytes += $file->getSize();
            }
        }

        return [
            'exists'   => true,
            'files'    => $files,
            'size_mb'  => round($bytes / 1048576, 2),
            'writable' => is_writable($path),
        ];
    }

    // =====================================================================
    // Write endpoints
    // =====================================================================

    public function projects_create()
    {
        $input = $this->jsonInput();

        $this->assertKnownFields($input, [
            'name', 'clientid', 'description', 'billing_type', 'status', 'progress',
            'project_cost', 'estimated_hours', 'start_date', 'deadline', 'dry_run',
        ]);

        $name     = $this->requireString($input, 'name', 191);
        $clientId = $this->requireInt($input, 'clientid');
        $dryRun   = $this->boolInput($input, 'dry_run', false);

        $client = $this->db->select('userid')->where('userid', $clientId)->get(db_prefix() . 'clients')->row();

        if (! $client) {
            throw new Api_exception('validation_failed', 'No client exists with id ' . $clientId . '.', 422, ['field' => 'clientid']);
        }

        $description = $this->inputValue($input, 'description', '');

        if ($description !== null && ! is_string($description)) {
            throw new Api_exception('validation_failed', 'Field "description" must be a string.', 422, ['field' => 'description']);
        }

        $description = (string) $description;

        if (mb_strlen($description) > 20000) {
            throw new Api_exception('validation_failed', 'Field "description" exceeds 20000 characters.', 422, ['field' => 'description']);
        }

        $startDate = $this->dateOrNull($input, 'start_date');
        $deadline  = $this->dateOrNull($input, 'deadline');

        if ($startDate !== null && $deadline !== null && strtotime($deadline) < strtotime($startDate)) {
            throw new Api_exception('validation_failed', 'Field "deadline" must not be before "start_date".', 422, ['field' => 'deadline']);
        }

        $data = [
            'name'             => $name,
            'description'      => $description,
            'clientid'         => $clientId,
            'billing_type'     => $this->optionalInt($input, 'billing_type', 1, 0, 10),
            'status'           => $this->optionalInt($input, 'status', 1, 0, 10),
            'progress'         => $this->optionalInt($input, 'progress', 0, 0, 100),
            'project_cost'     => $this->requireFloat($input, 'project_cost', 0),
            'estimated_hours'  => $this->requireFloat($input, 'estimated_hours', 0),
            'start_date'       => $startDate,
            'deadline'         => $deadline,
            'project_created'  => date('Y-m-d'),
            'addedfrom'        => $this->apiAuth->staffId(),
        ];

        if ($dryRun) {
            return [
                'data' => [
                    'dry_run'       => true,
                    'would_create'  => $data,
                    'client'        => ['id' => $clientId],
                ],
                'meta' => [
                    'dry_run'   => true,
                    'validated' => true,
                    'wrote'     => false,
                    'message'   => 'Dry run only. Re-submit with dry_run=false (or omit it) to create the project.',
                ],
            ];
        }

        $this->db->insert(db_prefix() . 'projects', $data);
        $id = (int) $this->db->insert_id();

        if (! $id) {
            throw new Api_exception('write_failed', 'The project could not be created.', 500);
        }

        $created = $this->apiResources->find('projects', $id);

        log_activity('Project Created from Agent API [ID: ' . $id . ']');

        return [
            'data' => $this->enrich('projects', $created),
            'meta' => ['created' => true, 'verified' => true, 'id' => $id],
        ];
    }

    public function projects_update()
    {
        $input = $this->jsonInput();

        $this->assertKnownFields($input, [
            'id', 'name', 'description', 'clientid', 'billing_type', 'status', 'progress',
            'project_cost', 'estimated_hours', 'start_date', 'deadline', 'date_finished', 'dry_run',
        ]);

        $id     = $this->requireInt($input, 'id');
        $dryRun = $this->boolInput($input, 'dry_run', false);

        $exists = $this->db->select('id')->where('id', $id)->get(db_prefix() . 'projects')->row();

        if (! $exists) {
            throw new Api_exception('not_found', 'No project with id ' . $id . '.', 404);
        }

        $data = [];

        if (array_key_exists('name', $input)) {
            $data['name'] = $this->requireString($input, 'name', 191);
        }

        if (array_key_exists('description', $input)) {
            $description = $this->inputValue($input, 'description', '');
            if ($description !== null && ! is_string($description)) {
                throw new Api_exception('validation_failed', 'Field "description" must be a string.', 422, ['field' => 'description']);
            }
            $description = (string) $description;
            if (mb_strlen($description) > 20000) {
                throw new Api_exception('validation_failed', 'Field "description" exceeds 20000 characters.', 422, ['field' => 'description']);
            }
            $data['description'] = $description;
        }

        if (array_key_exists('clientid', $input)) {
            $clientId = $this->requireInt($input, 'clientid');

            if (! $this->db->select('userid')->where('userid', $clientId)->get(db_prefix() . 'clients')->row()) {
                throw new Api_exception('validation_failed', 'No client exists with id ' . $clientId . '.', 422, ['field' => 'clientid']);
            }

            $data['clientid'] = $clientId;
        }

        foreach (['billing_type' => 10, 'status' => 10] as $field => $max) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $this->optionalInt($input, $field, 0, 0, $max);
            }
        }

        if (array_key_exists('progress', $input)) {
            $data['progress'] = $this->optionalInt($input, 'progress', 0, 0, 100);
        }

        foreach (['project_cost', 'estimated_hours'] as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $this->requireFloat($input, $field, 0);
            }
        }

        foreach (['start_date', 'deadline', 'date_finished'] as $dateField) {
            if (array_key_exists($dateField, $input)) {
                $data[$dateField] = $this->dateOrNull($input, $dateField);
            }
        }

        if ($data === []) {
            throw new Api_exception('validation_failed', 'No updatable field was provided.', 422);
        }

        if (
            array_key_exists('start_date', $data) && array_key_exists('deadline', $data)
            && $data['start_date'] !== null && $data['deadline'] !== null
            && strtotime($data['deadline']) < strtotime($data['start_date'])
        ) {
            throw new Api_exception('validation_failed', 'Field "deadline" must not be before "start_date".', 422, ['field' => 'deadline']);
        }

        if ($dryRun) {
            return [
                'data' => [
                    'dry_run'     => true,
                    'id'          => $id,
                    'would_update'=> $data,
                    'before'      => $this->enrich('projects', $this->apiResources->find('projects', $id)),
                ],
                'meta' => [
                    'dry_run'   => true,
                    'validated' => true,
                    'wrote'     => false,
                    'fields'    => array_keys($data),
                    'message'   => 'Dry run only. Re-submit with dry_run=false (or omit it) to persist these changes.',
                ],
            ];
        }

        $this->db->where('id', $id)->update(db_prefix() . 'projects', $data);

        $updated = $this->apiResources->find('projects', $id);

        log_activity('Project Updated from Agent API [ID: ' . $id . ']');

        return [
            'data' => $this->enrich('projects', $updated),
            'meta' => ['updated' => true, 'fields' => array_keys($data), 'verified' => true],
        ];
    }

    private function dateOrNull(array $input, $key)
    {
        $value = $this->inputValue($input, $key, null);

        if ($value === null || $value === '') {
            return null;
        }

        $date = date_create((string) $value);

        if (! $date) {
            throw new Api_exception('validation_failed', 'Field "' . $key . '" must be a valid date (YYYY-MM-DD).', 422, ['field' => $key]);
        }

        return $date->format('Y-m-d');
    }

    // =====================================================================
    // Approvals and critical operations
    // =====================================================================

    public function approvals_create()
    {
        $input  = $this->jsonInput();
        $action = $this->requireString($input, 'action', 120);

        if (! in_array($action, ['cache_clear'], true)) {
            throw new Api_exception('unknown_action', 'No approval flow exists for "' . $action . '".', 422);
        }

        // Bind the approval to the exact destructive payload. A dry-run is
        // never approval-gated, so only the real execution can consume it.
        $payload = ['dry_run' => false];

        $approval = $this->apiAuth->createApproval($action, $payload);

        return [
            'data' => $approval,
            'meta' => ['single_use' => true, 'ttl_seconds' => 300],
        ];
    }

    public function cache_clear()
    {
        $input    = $this->jsonInput();
        $dryRun   = filter_var($input['dry_run'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $dryRun   = $dryRun === null ? true : $dryRun;
        $approval = (string) ($input['approval_token'] ?? '');

        $cachePath = APPPATH . 'cache';

        if (! is_dir($cachePath)) {
            throw new Api_exception('not_found', 'The cache directory does not exist.', 404);
        }

        $targets = [];
        foreach (new DirectoryIterator($cachePath) as $file) {
            if ($file->isDot() || ! $file->isFile()) {
                continue;
            }

            if (in_array($file->getFilename(), ['index.html', '.htaccess'], true)) {
                continue;
            }

            $targets[] = $file->getPathname();
        }

        $bytes = array_sum(array_map(static fn ($path) => (int) @filesize($path), $targets));

        if ($dryRun) {
            return [
                'data' => [
                    'dry_run'        => true,
                    'would_delete'   => count($targets),
                    'bytes'          => $bytes,
                    'approval_token' => null,
                ],
                'meta' => [
                    'message' => 'Dry run only. Re-submit with dry_run=false and an approval token to delete these files.',
                ],
            ];
        }

        $this->apiAuth->consumeApproval($approval, 'cache_clear', ['dry_run' => false]);

        $deleted = 0;
        foreach ($targets as $path) {
            if (@unlink($path)) {
                $deleted++;
            }
        }

        $remaining = 0;
        foreach (new DirectoryIterator($cachePath) as $file) {
            if ($file->isDot() || ! $file->isFile()) {
                continue;
            }
            if (! in_array($file->getFilename(), ['index.html', '.htaccess'], true)) {
                $remaining++;
            }
        }

        return [
            'data' => [
                'dry_run'   => false,
                'deleted'   => $deleted,
                'remaining' => $remaining,
                'bytes'     => $bytes,
            ],
            'meta' => [
                'verified' => $remaining === 0,
                'message'  => $remaining === 0
                    ? 'Cache cleared and verified: no cache files remain.'
                    : 'Some cache files could not be deleted; review filesystem permissions.',
            ],
        ];
    }

    // =====================================================================
    // Small helpers
    // =====================================================================

    private function check($name, $status, $detail)
    {
        return ['name' => $name, 'status' => $status, 'detail' => $detail];
    }

    private function pathCheck($name, $path, $mustBeWritable)
    {
        if (! is_dir($path)) {
            return $this->check($name, 'fail', 'Directory does not exist: ' . $path);
        }

        if ($mustBeWritable && ! is_writable($path)) {
            return $this->check($name, 'fail', 'Directory is not writable: ' . $path);
        }

        return $this->check($name, 'ok', $path);
    }
}
