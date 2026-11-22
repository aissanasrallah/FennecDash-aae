<?php

defined('BASEPATH') or exit('No direct script access allowed');

class AdminController extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        if ($this->app->is_db_upgrade_required($this->current_db_version)) {
            if ($this->input->post('upgrade_database')) {
                hooks()->do_action('pre_upgrade_database');

                $this->app->upgrade_database();
            }

            die(include_once(VIEWPATH . 'admin/includes/db_update_required.php'));
        }

        hooks()->do_action('pre_admin_init');

        if (!is_staff_logged_in()) {
            if (strpos(current_full_url(), get_admin_uri() . '/authentication') === false) {
                redirect_after_login_to_current_url();
            }

            redirect(admin_url('authentication'));
        }

        if ($this->uri->segment(3) != 'notifications_check') {
            // In case staff have setup logged in as client - This is important don't change it
            foreach (['client_user_id', 'contact_user_id', 'client_logged_in', 'logged_in_as_client'] as $sk) {
                if ($this->session->has_userdata($sk)) {
                    $this->session->unset_userdata($sk);
                }
            }
        }

        // Update staff last activity (throttled to once per 60s per session to eliminate per-request write locks)
        $_nowTs  = time();
        $_lastTs = (int) $this->session->userdata('last_activity_update_ts');
        if (($_nowTs - $_lastTs) >= 60) {
            $this->db->where('staffid', get_staff_user_id());
            $this->db->update('staff', ['last_activity' => date('Y-m-d H:i:s', $_nowTs)]);
            $this->session->set_userdata('last_activity_update_ts', $_nowTs);
        }

        $this->load->model('staff_model');

        // Do not check on ajax requests
        if (!$this->input->is_ajax_request()) {
            if (ENVIRONMENT == 'production' && is_admin()) {
                if ($this->config->item('encryption_key') === '') {
                    die('<h1>Encryption key not sent in application/config/app-config.php</h1>For more info visit <a href="https://fennec-vision.com/encryption-key-explained/">encryption key explained</a>');
                } elseif (strlen($this->config->item('encryption_key')) != 32) {
                    die('<h1>Encryption key length should be 32 charachters</h1>For more info visit <a href="https://fennec-vision.com/encryption-key-explained/">encryption key explained</a>');
                }
            }

            _maybe_system_setup_warnings();

            $this->init_quick_actions_links();
        }

        $currentUser = $this->staff_model->get(get_staff_user_id());

        // Deleted or inactive but have session
        if (!$currentUser || $currentUser->active == 0) {
            $this->authentication_model->logout();
            redirect(admin_url('authentication'));
        }

        $GLOBALS['current_user'] = $currentUser;

        // Demo mode — block non-AJAX mutations (form submissions), allow AJAX (read-only data fetching)
        if (defined('DEMO_MODE') && DEMO_MODE) {
            $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
            if ($method !== 'GET' && !$this->input->is_ajax_request()) {
                $msg = _l('demo_mode_action_not_allowed');
                set_alert('warning', $msg);
                redirect(admin_url());
            }
        }

        init_admin_assets();

        hooks()->do_action('admin_init');

        $vars = [
            'current_user'    => $currentUser,
            'current_version' => $this->current_db_version,
            'task_statuses'   => $this->tasks_model->get_statuses(),
        ];

        if (!$this->input->is_ajax_request()) {
            $vars['sidebar_menu'] = $this->app_menu->get_sidebar_menu_items();
            $vars['setup_menu']   = $this->app_menu->get_setup_menu_items();
        }

        /**
        * Autoloaded view variables
        * @var array
        */
        $vars = hooks()->apply_filters('admin_area_auto_loaded_vars', $vars);
        $this->load->vars($vars);
    }

    private function init_quick_actions_links()
    {
        $this->app->add_quick_actions_link([
            'name'       => _l('invoice'),
            'permission' => 'invoices',
            'url'        => 'invoices/invoice',
            'position'   => 5,
            'icon'       => 'fa-solid fa-file-invoice',
        ]);

        $this->app->add_quick_actions_link([
            'name'       => _l('estimate'),
            'permission' => 'estimates',
            'url'        => 'estimates/estimate',
            'position'   => 10,
            'icon'       => 'fa-regular fa-file',
        ]);

        $this->app->add_quick_actions_link([
            'name'       => _l('proposal'),
            'permission' => 'proposals',
            'url'        => 'proposals/proposal',
            'position'   => 15,
            'icon'       => 'fa-regular fa-file-powerpoint',
        ]);

        $this->app->add_quick_actions_link([
            'name'       => _l('credit_note'),
            'permission' => 'credit_notes',
            'url'        => 'credit_notes/credit_note',
            'position'   => 20,
            'icon'       => 'fa-regular fa-file-lines',
        ]);

        $this->app->add_quick_actions_link([
            'name'       => _l('client'),
            'permission' => 'customers',
            'url'        => 'clients/client',
            'position'   => 25,
            'icon'       => 'fa-regular fa-building',
        ]);

        $this->app->add_quick_actions_link([
            'name'       => _l('subscription'),
            'permission' => 'subscriptions',
            'url'        => 'subscriptions/create',
            'position'   => 30,
            'icon'       => 'fa-solid fa-repeat',
        ]);


        $this->app->add_quick_actions_link([
            'name'       => _l('project'),
            'url'        => 'projects/project',
            'permission' => 'projects',
            'position'   => 35,
            'icon'       => 'fa-solid fa-chart-gantt',
        ]);


        $this->app->add_quick_actions_link([
            'name'            => _l('task'),
            'url'             => '#',
            'custom_url'      => true,
            'href_attributes' => [
                'onclick' => 'new_task();return false;',
            ],
            'permission' => 'tasks',
            'position'   => 40,
            'icon'       => 'fa-regular fa-circle-check',
        ]);

        $this->app->add_quick_actions_link([
            'name'            => _l('lead'),
            'url'             => '#',
            'custom_url'      => true,
            'permission'      => 'is_staff_member',
            'href_attributes' => [
                'onclick' => 'init_lead(); return false;',
            ],
            'position' => 45,
            'icon'     => 'fa-solid fa-crosshairs',
        ]);

        $this->app->add_quick_actions_link([
            'name'       => _l('expense'),
            'permission' => 'expenses',
            'url'        => 'expenses/expense',
            'position'   => 50,
            'icon'       => 'fa-solid fa-file-lines',
        ]);


        $this->app->add_quick_actions_link([
            'name'       => _l('contract'),
            'permission' => 'contracts',
            'url'        => 'contracts/contract',
            'position'   => 55,
            'icon'       => 'fa-solid fa-file-contract',
        ]);


        $this->app->add_quick_actions_link([
            'name'       => _l('kb_article'),
            'permission' => 'knowledge_base',
            'url'        => 'knowledge_base/article',
            'position'   => 60,
            'icon'       => 'fa-solid fa-circle-info',
        ]);

        $tickets = [
            'name'     => _l('ticket'),
            'url'      => 'tickets/add',
            'position' => 65,
            'icon'     => 'fa-solid fa-life-ring',
        ];

        if (get_option('access_tickets_to_none_staff_members') == 0 && !is_staff_member()) {
            $tickets['permission'] = 'is_staff_member';
        }

        $this->app->add_quick_actions_link($tickets);

        $this->app->add_quick_actions_link([
            'name'       => _l('staff_member'),
            'url'        => 'staff/member',
            'permission' => 'staff',
            'position'   => 70,
            'icon'       => 'fa-regular fa-user',
        ]);

        $this->app->add_quick_actions_link([
            'name'       => _l('calendar_event'),
            'url'        => 'utilities/calendar?new_event=true&date=' . _d(date('Y-m-d')),
            'permission' => '',
            'position'   => 75,
            'icon'       => 'fa-regular fa-calendar',
        ]);
    }

    /**
     * Fail-closed controller action RBAC dispatcher for business modules.
     */
    protected function enforce_action_rbac($module, array $exactMap, array $patternRules, array $moduleFeatures = [], array $safeLookupAllow = [])
    {
        if (is_admin()) {
            return;
        }
        $method = strtolower((string) $this->router->fetch_method());

        $hasAnyModuleCap = false;
        foreach ($moduleFeatures as $feat) {
            foreach (['view', 'view_own', 'create', 'edit', 'delete'] as $c) {
                if (has_permission($feat, '', $c)) {
                    $hasAnyModuleCap = true;
                    break 2;
                }
            }
        }
        if (in_array($method, $safeLookupAllow, true) && $hasAnyModuleCap) {
            return;
        }

        $spec = $exactMap[$method] ?? null;
        if ($spec === null) {
            foreach ($patternRules as $pattern => $ruleSpec) {
                if (preg_match($pattern, $method)) {
                    $spec = $ruleSpec;
                    break;
                }
            }
        }

        if ($spec === null) {
            $this->rbac_deny($module);
        }

        if (isset($spec[0]) && is_array($spec[0])) {
            foreach ($spec as $pair) {
                $cap = $pair[1] === 'auto' ? $this->infer_capability_for_action($method) : $pair[1];
                if ($this->check_feature_capability($pair[0], $cap)) {
                    return;
                }
            }
            $this->rbac_deny($module);
        }

        $feat = $spec[0];
        $cap  = $spec[1] === 'auto' ? $this->infer_capability_for_action($method) : $spec[1];
        if (!$this->check_feature_capability($feat, $cap)) {
            $this->rbac_deny($module);
        }
    }

    protected function infer_capability_for_action($method)
    {
        if (preg_match('/(^delete_|_delete$|_delete_|remove|void|bulk_action|reset_data|clear_)/', $method)) {
            return 'delete';
        }
        if (preg_match('/^(new_|add_|create_|save_|edit_|update_|import_|assign_|approve|revert|adjust|send_|convert|match_|unmatch_|leave_it_|finish_|make_|reconcile_bank|change_|update|upload_|sign_|stock_take|coppy_|copy_|pay_)/', $method)) {
            if (strpos($method, 'add_') === 0 || strpos($method, 'new_') === 0 || strpos($method, 'create_') === 0) {
                return ($this->input->post('id') || $this->uri->segment(4)) ? 'edit' : 'create';
            }
            return 'edit';
        }
        if ($this->input->post() && !preg_match('/(_table$|^table_|^get_|^rp_|^view_report$|^check_|_exists$)/', $method)) {
            return ($this->uri->segment(4) || $this->input->post('id')) ? 'edit' : 'create';
        }
        return 'view';
    }

    protected function check_feature_capability($feature, $cap)
    {
        if ($cap === 'view') {
            return has_permission($feature, '', 'view') || has_permission($feature, '', 'view_own');
        }
        if ($cap === 'create_or_edit') {
            return has_permission($feature, '', 'create') || has_permission($feature, '', 'edit');
        }
        return has_permission($feature, '', $cap);
    }

    protected function rbac_deny($module)
    {
        if ($this->input->is_ajax_request()) {
            ajax_access_denied();
        }
        access_denied($module);
    }
}