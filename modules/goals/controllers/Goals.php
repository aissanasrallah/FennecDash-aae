<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Goals extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('goals_model');
    }

    /* List all goals (current & historical periods) */
    public function index()
    {
        if (staff_cant('view', 'goals')) {
            access_denied('goals');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('goals', 'table'));
        }
        $this->app_scripts->add('circle-progress-js', 'assets/plugins/jquery-circle-progress/circle-progress.min.js');
        $data['title']         = _l('goals_tracking');
        $data['period_filter'] = $this->input->get('period') ?: 'all';
        $this->load->view('manage', $data);
    }

    public function goal($id = '')
    {
        if (staff_cant('view', 'goals')) {
            access_denied('goals');
        }
        if ($id !== '' && !is_admin() && staff_cant('edit', 'goals')) {
            if (!$this->goals_model->is_staff_assigned((int) $id, get_staff_user_id())) {
                access_denied('goals');
            }
        }

        if ($this->input->post()) {
            if ($id == '') {
                if (staff_cant('create', 'goals')) {
                    access_denied('goals');
                }
                $id = $this->goals_model->add($this->input->post());
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('goal')));
                    redirect(admin_url('goals/goal/' . $id));
                }
            } else {
                if (staff_cant('edit', 'goals')) {
                    access_denied('goals');
                }
                $success = $this->goals_model->update($this->input->post(), $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('goal')));
                }
                redirect(admin_url('goals/goal/' . $id));
            }
        }

        $selectedPeriodId = $this->input->get('period_id') ? (int) $this->input->get('period_id') : null;
        if ($id == '') {
            $title = _l('add_new', _l('goal_lowercase'));
        } else {
            $goal = $this->goals_model->get($id);
            if (!$goal) {
                show_404();
            }
            $data['goal']               = $goal;
            $data['periods']            = $this->goals_model->ensure_goal_periods((int) $id);
            $assignedStaff = $this->goals_model->get_goal_staff((int) $id, true);
            $canViewTeam   = is_admin() || has_permission('goals', '', 'edit') || has_permission('goals', '', 'create');
            $uid           = (int) get_staff_user_id();
            $achievement   = $this->goals_model->calculate_goal_achievement((int) $id, $selectedPeriodId, $canViewTeam ? null : $uid);
            $staffAchs     = $this->goals_model->calculate_goal_staff_achievements((int) $id, $achievement['period_id'] ?? $selectedPeriodId);
            if (!$canViewTeam) {
                $assignedStaff = array_values(array_filter($assignedStaff, function ($s) use ($uid) {
                    return (int) ($s['staff_id'] ?? 0) === $uid;
                }));
                $staffAchs = array_values(array_filter($staffAchs, function ($sa) use ($uid) {
                    return (int) ($sa['staff_id'] ?? 0) === $uid;
                }));
            }
            $data['assigned_staff']     = $assignedStaff;
            $data['achievement']        = $achievement;
            $data['staff_achievements'] = $staffAchs;
            $data['selected_period_id'] = $data['achievement']['period_id'] ?? $selectedPeriodId;

            $title = _l('edit', _l('goal_lowercase'));
        }

        $this->load->model('staff_model');
        $data['members'] = $this->staff_model->get('', ['is_not_staff' => 0, 'active' => 1]);

        $this->load->model('contracts_model');
        $data['contract_types'] = $this->contracts_model->get_contract_types();
        $data['title']          = $title;
        $this->app_scripts->add('circle-progress-js', 'assets/plugins/jquery-circle-progress/circle-progress.min.js');
        $this->load->view('goal', $data);
    }

    /**
     * Manually refresh / generate periods up to current date for a goal.
     */
    public function refresh_periods($id)
    {
        if (staff_cant('edit', 'goals') && staff_cant('create', 'goals')) {
            access_denied('goals');
        }
        $horizon = $this->input->get('horizon') ?: null;
        $this->goals_model->ensure_goal_periods((int) $id, $horizon);
        set_alert('success', _l('updated_successfully', _l('goal')));
        redirect(admin_url('goals/goal/' . (int) $id));
    }

    /**
     * AJAX JSON endpoint returning periods for a given Invoiced Amount goal (used by HR Profile salary settings).
     */
    public function periods_json($goal_id)
    {
        if (!has_permission('goals', '', 'view') && !has_permission('hrm_setting', '', 'view') && !is_admin()) {
            ajax_access_denied();
        }
        $goalId = (int) $goal_id;
        if (!is_admin() && !has_permission('hrm_setting', '', 'view') && !has_permission('goals', '', 'edit')) {
            if (!$this->goals_model->is_staff_assigned($goalId, (int) get_staff_user_id())) {
                ajax_access_denied();
            }
        }
        $goal = $this->goals_model->get($goalId);
        if (!$goal) {
            echo json_encode(['goal' => null, 'periods' => [], 'staff' => []]);
            return;
        }
        $periods = $this->goals_model->ensure_goal_periods($goalId);
        $staff   = $this->goals_model->get_goal_staff($goalId, true);
        $uid     = (int) get_staff_user_id();
        $canListAll = is_admin() || has_permission('hrm_setting', '', 'view') || has_permission('goals', '', 'edit');
        $safeStaff = [];
        foreach ($staff as $s) {
            if (!$canListAll && (int) ($s['staff_id'] ?? 0) !== $uid) {
                continue;
            }
            unset($s['email']);
            $safeStaff[] = $s;
        }
        echo json_encode([
            'goal'    => $goal,
            'periods' => $periods,
            'staff'   => $safeStaff,
        ]);
    }

    /* Delete goal from database */
    public function delete($id)
    {
        if (staff_cant('delete', 'goals')) {
            access_denied('goals');
        }
        if (!$id) {
            redirect(admin_url('goals'));
        }
        $response = $this->goals_model->delete($id);
        if ($response == true) {
            set_alert('success', _l('deleted', _l('goal')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('goal_lowercase')));
        }
        redirect(admin_url('goals'));
    }

    public function notify($id, $notify_type)
    {
        if (staff_cant('edit', 'goals') && staff_cant('create', 'goals')) {
            access_denied('goals');
        }
        if (!$id) {
            redirect(admin_url('goals'));
        }
        $success = $this->goals_model->notify_staff_members($id, $notify_type);
        if ($success) {
            set_alert('success', _l('goal_notify_staff_notified_manually_success'));
        } else {
            set_alert('warning', _l('goal_notify_staff_notified_manually_fail'));
        }
        redirect(admin_url('goals/goal/' . $id));
    }
}
