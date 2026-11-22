<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/Api_exception.php';

/**
 * Whitelisted read model for the Agent API.
 *
 * Every table, column, filter and sort key is declared here. User input can
 * never influence the SQL structure: only bound values are accepted, which
 * removes the SQL-injection surface entirely.
 */
class Api_resources
{
    /** @var CI_Controller */
    private $CI;

    /** @var array<string, array>|null */
    private $definitions;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
    }

    /**
     * Resource definitions. `select` is an explicit list: sensitive columns
     * (passwords, 2FA secrets, vault entries) are never reachable.
     *
     * @return array<string, array>
     */
    public function definitions()
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        $this->definitions = [
            'projects' => [
                'label'      => 'Projects',
                'table'      => 'projects',
                'alias'      => 'p',
                'pk'         => 'id',
                'feature'    => 'projects',
                'select'     => [
                    'p.id', 'p.name', 'p.description', 'p.status', 'p.clientid',
                    'p.billing_type', 'p.start_date', 'p.deadline', 'p.project_created',
                    'p.date_finished', 'p.progress', 'p.project_cost', 'p.estimated_hours',
                    'p.addedfrom', 'c.company AS client_name',
                ],
                'joins'      => [
                    ['table' => 'clients', 'alias' => 'c', 'on' => 'c.userid = p.clientid', 'type' => 'left'],
                ],
                'search'     => ['p.name', 'p.description', 'c.company'],
                'filters'    => ['status', 'clientid', 'billing_type', 'addedfrom'],
                'sortable'   => ['id', 'name', 'status', 'start_date', 'deadline', 'project_created', 'progress'],
                'default'    => ['sort' => 'id', 'order' => 'desc'],
            ],

            'clients' => [
                'label'      => 'Clients',
                'table'      => 'clients',
                'alias'      => 'c',
                'pk'         => 'userid',
                'feature'    => 'clients',
                'select'     => [
                    'c.userid', 'c.company', 'c.customer_type', 'c.vat', 'c.phonenumber',
                    'c.country', 'c.city', 'c.state', 'c.address', 'c.website',
                    'c.datecreated', 'c.active', 'c.leadid', 'c.default_language',
                    'c.default_currency', 'c.balance',
                ],
                'joins'      => [],
                'search'     => ['c.company', 'c.vat', 'c.phonenumber', 'c.city', 'c.website'],
                'filters'    => ['active', 'customer_type', 'country', 'city'],
                'sortable'   => ['userid', 'company', 'datecreated', 'balance'],
                'default'    => ['sort' => 'userid', 'order' => 'desc'],
            ],

            'leads' => [
                'label'      => 'Leads',
                'table'      => 'leads',
                'alias'      => 'l',
                'pk'         => 'id',
                'feature'    => 'leads',
                'select'     => [
                    'l.id', 'l.name', 'l.title', 'l.company', 'l.description', 'l.country',
                    'l.city', 'l.state', 'l.address', 'l.assigned', 'l.dateadded',
                    'l.status', 'l.source', 'l.lastcontact', 'l.email', 'l.website',
                    'l.phonenumber', 'l.lead_value', 'l.client_id', 'l.date_converted',
                    'l.lost', 'l.junk', 's.name AS status_name', 'st.firstname AS assigned_firstname',
                    'st.lastname AS assigned_lastname',
                ],
                'joins'      => [
                    ['table' => 'leads_status', 'alias' => 's', 'on' => 's.id = l.status', 'type' => 'left'],
                    ['table' => 'staff', 'alias' => 'st', 'on' => 'st.staffid = l.assigned', 'type' => 'left'],
                ],
                'search'     => ['l.name', 'l.company', 'l.email', 'l.phonenumber', 'l.city'],
                'filters'    => ['status', 'assigned', 'source', 'lost', 'junk'],
                'sortable'   => ['id', 'name', 'dateadded', 'lastcontact', 'lead_value'],
                'default'    => ['sort' => 'id', 'order' => 'desc'],
            ],

            'invoices' => [
                'label'      => 'Invoices',
                'table'      => 'invoices',
                'alias'      => 'i',
                'pk'         => 'id',
                'feature'    => 'invoices',
                'select'     => [
                    'i.id', 'i.number', 'i.formatted_number', 'i.clientid', 'i.date',
                    'i.duedate', 'i.currency', 'i.subtotal', 'i.total_tax', 'i.total',
                    'i.adjustment', 'i.status', 'i.project_id', 'i.datecreated',
                    'i.datesend', 'i.sent', 'c.company AS client_name', 'p.name AS project_name',
                ],
                'joins'      => [
                    ['table' => 'clients', 'alias' => 'c', 'on' => 'c.userid = i.clientid', 'type' => 'left'],
                    ['table' => 'projects', 'alias' => 'p', 'on' => 'p.id = i.project_id', 'type' => 'left'],
                ],
                'search'     => ['i.formatted_number', 'i.number', 'c.company'],
                'filters'    => ['status', 'clientid', 'project_id', 'currency'],
                'sortable'   => ['id', 'number', 'date', 'duedate', 'total', 'status'],
                'default'    => ['sort' => 'id', 'order' => 'desc'],
            ],

            'estimates' => [
                'label'      => 'Estimates',
                'table'      => 'estimates',
                'alias'      => 'e',
                'pk'         => 'id',
                'feature'    => 'estimates',
                'select'     => [
                    'e.id', 'e.number', 'e.formatted_number', 'e.clientid', 'e.date',
                    'e.expirydate', 'e.currency', 'e.subtotal', 'e.total_tax', 'e.total',
                    'e.adjustment', 'e.status', 'e.project_id', 'e.datecreated',
                    'e.invoiceid', 'e.sent', 'e.datesend', 'c.company AS client_name',
                    'p.name AS project_name',
                ],
                'joins'      => [
                    ['table' => 'clients', 'alias' => 'c', 'on' => 'c.userid = e.clientid', 'type' => 'left'],
                    ['table' => 'projects', 'alias' => 'p', 'on' => 'p.id = e.project_id', 'type' => 'left'],
                ],
                'search'     => ['e.formatted_number', 'e.number', 'c.company'],
                'filters'    => ['status', 'clientid', 'project_id', 'currency'],
                'sortable'   => ['id', 'number', 'date', 'expirydate', 'total', 'status'],
                'default'    => ['sort' => 'id', 'order' => 'desc'],
            ],

            'tasks' => [
                'label'      => 'Tasks',
                'table'      => 'tasks',
                'alias'      => 't',
                'pk'         => 'id',
                'feature'    => 'tasks',
                'select'     => [
                    't.id', 't.name', 't.description', 't.priority', 't.dateadded',
                    't.startdate', 't.duedate', 't.datefinished', 't.status', 't.rel_id',
                    't.rel_type', 't.billable', 't.billed', 't.invoice_id', 't.hourly_rate',
                    't.milestone', 't.visible_to_client',
                ],
                'joins'      => [],
                'search'     => ['t.name', 't.description'],
                'filters'    => ['status', 'priority', 'rel_id', 'rel_type', 'billable'],
                'sortable'   => ['id', 'name', 'duedate', 'dateadded', 'priority', 'status'],
                'default'    => ['sort' => 'id', 'order' => 'desc'],
            ],

            'tickets' => [
                'label'      => 'Tickets',
                'table'      => 'tickets',
                'alias'      => 'tk',
                'pk'         => 'ticketid',
                'feature'    => 'tickets',
                'select'     => [
                    'tk.ticketid', 'tk.userid', 'tk.email', 'tk.name', 'tk.department',
                    'tk.priority', 'tk.status', 'tk.service', 'tk.subject', 'tk.date',
                    'tk.lastreply', 'tk.project_id', 'tk.assigned', 'tk.clientread',
                    'tk.adminread',
                ],
                'joins'      => [],
                'search'     => ['tk.subject', 'tk.email', 'tk.name'],
                'filters'    => ['status', 'priority', 'department', 'assigned', 'userid', 'project_id'],
                'sortable'   => ['ticketid', 'subject', 'date', 'lastreply', 'priority', 'status'],
                'default'    => ['sort' => 'ticketid', 'order' => 'desc'],
            ],

            'staff' => [
                'label'      => 'Staff',
                'table'      => 'staff',
                'alias'      => 's',
                'pk'         => 'staffid',
                'feature'    => 'staff',
                'select'     => [
                    's.staffid', 's.email', 's.firstname', 's.lastname', 's.admin',
                    's.role', 's.active', 's.phonenumber', 's.datecreated', 's.last_login',
                    's.last_activity', 's.default_language', 's.hourly_rate',
                ],
                'joins'      => [],
                'search'     => ['s.email', 's.firstname', 's.lastname'],
                'filters'    => ['active', 'admin', 'role'],
                'sortable'   => ['staffid', 'firstname', 'lastname', 'datecreated', 'last_login'],
                'default'    => ['sort' => 'staffid', 'order' => 'asc'],
            ],

            'contracts' => [
                'label'      => 'Contracts',
                'table'      => 'contracts',
                'alias'      => 'ct',
                'pk'         => 'id',
                'feature'    => 'contracts',
                'select'     => [
                    'ct.id', 'ct.subject', 'ct.description', 'ct.client', 'ct.datestart',
                    'ct.dateend', 'ct.contract_type', 'ct.project_id', 'ct.dateadded',
                    'ct.contract_value', 'ct.signed', 'ct.trash', 'c.company AS client_name',
                ],
                'joins'      => [
                    ['table' => 'clients', 'alias' => 'c', 'on' => 'c.userid = ct.client', 'type' => 'left'],
                ],
                'search'     => ['ct.subject', 'ct.description', 'c.company'],
                'filters'    => ['client', 'contract_type', 'signed', 'trash', 'project_id'],
                'sortable'   => ['id', 'subject', 'datestart', 'dateend', 'contract_value'],
                'default'    => ['sort' => 'id', 'order' => 'desc'],
            ],

            'files' => [
                'label'      => 'Files',
                'table'      => 'files',
                'alias'      => 'f',
                'pk'         => 'id',
                'feature'    => 'utilities',
                'select'     => [
                    'f.id', 'f.rel_id', 'f.rel_type', 'f.file_name', 'f.filetype',
                    'f.visible_to_customer', 'f.staffid', 'f.dateadded', 'f.external',
                ],
                'joins'      => [],
                'search'     => ['f.file_name', 'f.rel_type'],
                'filters'    => ['rel_id', 'rel_type', 'staffid', 'visible_to_customer'],
                'sortable'   => ['id', 'file_name', 'dateadded'],
                'default'    => ['sort' => 'id', 'order' => 'desc'],
            ],

            'aae_orders' => [
                'label'      => 'AAE export orders',
                'table'      => 'aae_orders',
                'alias'      => 'ao',
                'pk'         => 'id',
                'feature'    => 'aae',
                'select'     => [
                    'ao.id', 'ao.client_id', 'ao.vehicle_id', 'ao.vehicle_source',
                    'ao.supplier_name', 'ao.supplier_reference', 'ao.stage',
                    'ao.amount_client', 'ao.amount_paid_client', 'ao.amount_net_received',
                    'ao.amount_balance', 'ao.amount_supplier', 'ao.amount_paid_supplier',
                    'ao.amount_supplier_due', 'ao.margin', 'ao.estimate_id', 'ao.invoice_id',
                    'ao.contract_id', 'ao.container_number', 'ao.bl_number', 'ao.tracking_link',
                    'ao.final_invoice_id', 'ao.final_balance', 'ao.refund_amount',
                    'ao.datecreated', 'ao.dateupdated', 'c.company AS client_name',
                ],
                'joins'      => [
                    ['table' => 'clients', 'alias' => 'c', 'on' => 'c.userid = ao.client_id', 'type' => 'left'],
                ],
                'search'     => ['ao.supplier_name', 'ao.container_number', 'ao.bl_number', 'c.company'],
                'filters'    => ['stage', 'client_id', 'estimate_id', 'invoice_id'],
                'sortable'   => ['id', 'stage', 'amount_client', 'datecreated', 'dateupdated'],
                'default'    => ['sort' => 'id', 'order' => 'desc'],
            ],

            'activity' => [
                'label'      => 'Activity log',
                'table'      => 'activity_log',
                'alias'      => 'al',
                'pk'         => 'id',
                'feature'    => 'utilities',
                'select'     => ['al.id', 'al.description', 'al.date', 'al.staffid'],
                'joins'      => [],
                'search'     => ['al.description'],
                'filters'    => ['staffid'],
                'sortable'   => ['id', 'date'],
                'default'    => ['sort' => 'id', 'order' => 'desc'],
            ],
        ];

        return $this->definitions;
    }

    /**
     * Definitions whose backing table actually exists on this installation.
     *
     * @return array<string, array>
     */
    public function available()
    {
        $available = [];

        foreach ($this->definitions() as $key => $definition) {
            if ($this->tableExists($definition['table'])) {
                $available[$key] = $definition;
            }
        }

        return $available;
    }

    public function get($key)
    {
        $available = $this->available();

        if (! isset($available[$key])) {
            throw new Api_exception('unknown_resource', 'Unknown or unavailable resource: ' . $key, 404);
        }

        return $available[$key];
    }

    public function tableExists($table)
    {
        $prefixed = $this->CI->db->dbprefix($table);

        $query = $this->CI->db->query(
            'SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$prefixed]
        );

        return $query && (int) $query->row()->total > 0;
    }

    // ---------------------------------------------------------------------
    // Read operations
    // ---------------------------------------------------------------------

    /**
     * @return array{data: array, meta: array}
     */
    public function paginate($key, array $params)
    {
        $definition = $this->get($key);

        $page    = max(1, (int) ($params['page'] ?? 1));
        $perPage = (int) ($params['per_page'] ?? 25);
        $perPage = max(1, min(100, $perPage));

        $sort  = (string) ($params['sort'] ?? $definition['default']['sort']);
        $order = strtoupper((string) ($params['order'] ?? $definition['default']['order']));

        if (! in_array($sort, $definition['sortable'], true)) {
            $sort = $definition['default']['sort'];
        }

        $order = $order === 'ASC' ? 'ASC' : 'DESC';

        $this->applyFrom($definition, true);
        $this->applyFilters($definition, $params);
        $this->applySearch($definition, $params);

        $countRow = $this->CI->db->get()->row();
        $total    = $countRow ? (int) $countRow->total : 0;

        $this->applyFrom($definition);
        $this->applyFilters($definition, $params);
        $this->applySearch($definition, $params);

        $this->CI->db->order_by($definition['alias'] . '.' . $sort, $order, false);
        $this->CI->db->limit($perPage, ($page - 1) * $perPage);

        $rows = $this->CI->db->get()->result_array();

        return [
            'data' => $rows,
            'meta' => [
                'resource' => $key,
                'page'     => $page,
                'per_page' => $perPage,
                'total'    => $total,
                'pages'    => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
                'sort'     => $sort,
                'order'    => $order,
            ],
        ];
    }

    /**
     * @return array
     */
    public function find($key, $id)
    {
        $definition = $this->get($key);

        $this->applyFrom($definition);
        $this->CI->db->where($definition['alias'] . '.' . $definition['pk'], (int) $id);

        $row = $this->CI->db->get()->row_array();

        if (! $row) {
            throw new Api_exception('not_found', sprintf('No %s record with id %d.', $key, (int) $id), 404);
        }

        return $row;
    }

    /**
     * Cross-resource search used by the global search tool.
     *
     * @return array{data: array, meta: array}
     */
    public function search($term, $limit = 5, array $allowed = [])
    {
        $term  = trim((string) $term);
        $limit = max(1, min(20, (int) $limit));

        if (mb_strlen($term) < 2) {
            throw new Api_exception('invalid_search', 'Search terms must contain at least 2 characters.', 422);
        }

        $targets = [
            'projects'  => 'name',
            'clients'   => 'company',
            'leads'     => 'name',
            'invoices'  => 'formatted_number',
            'estimates' => 'formatted_number',
            'tickets'   => 'subject',
            'tasks'     => 'name',
        ];

        $results = [];
        $available = $this->available();

        foreach ($targets as $key => $column) {
            if ($allowed !== [] && ! in_array($key, $allowed, true)) {
                continue;
            }

            if (! isset($available[$key])) {
                continue;
            }

            $definition = $available[$key];
            $this->applyFrom($definition);
            $this->CI->db->like($definition['alias'] . '.' . $column, $term, 'both');
            $this->CI->db->limit($limit);
            $rows = $this->CI->db->get()->result_array();

            foreach ($rows as $row) {
                $results[] = [
                    'resource' => $key,
                    'id'       => $row[$definition['pk']],
                    'title'    => $this->titleFor($key, $row),
                ];
            }
        }

        return [
            'data' => $results,
            'meta' => ['query' => $term, 'count' => count($results)],
        ];
    }

    private function titleFor($key, array $row)
    {
        switch ($key) {
            case 'clients':
                return $row['company'] ?? ('Client #' . $row['userid']);
            case 'invoices':
            case 'estimates':
                return $row['formatted_number'] ?? ('#' . $row['id']);
            case 'tickets':
                return $row['subject'] ?? ('Ticket #' . $row['ticketid']);
            default:
                return $row['name'] ?? ('#' . ($row['id'] ?? ''));
        }
    }

    // ---------------------------------------------------------------------
    // Query building
    // ---------------------------------------------------------------------

    private function applyFrom(array $definition, $countOnly = false)
    {
        $this->CI->db->reset_query();
        $this->CI->db->select($countOnly ? 'COUNT(*) AS total' : implode(', ', $definition['select']), false);
        $this->CI->db->from($this->CI->db->dbprefix($definition['table']) . ' ' . $definition['alias']);

        foreach ($definition['joins'] as $join) {
            $this->CI->db->join(
                $this->CI->db->dbprefix($join['table']) . ' ' . $join['alias'],
                $join['on'],
                $join['type']
            );
        }
    }

    private function applyFilters(array $definition, array $params)
    {
        $filters = $params['filter'] ?? [];

        if (! is_array($filters)) {
            return;
        }

        foreach ($filters as $column => $value) {
            if (! in_array($column, $definition['filters'], true)) {
                continue;
            }

            if ($value === '' || $value === null) {
                continue;
            }

            $this->CI->db->where($definition['alias'] . '.' . $column, $value);
        }
    }

    private function applySearch(array $definition, array $params)
    {
        $term = trim((string) ($params['q'] ?? ''));

        if ($term === '') {
            return;
        }

        $this->CI->db->group_start();
        foreach ($definition['search'] as $index => $column) {
            if ($index === 0) {
                $this->CI->db->like($column, $term, 'both');
            } else {
                $this->CI->db->or_like($column, $term, 'both');
            }
        }
        $this->CI->db->group_end();
    }
}
