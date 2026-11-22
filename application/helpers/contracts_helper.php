<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Get contract short_url
 * @since  Version 2.7.3
 * @param  object $contract
 * @return string Url
 */
function get_contract_shortlink($contract)
{
    $long_url = site_url("contract/{$contract->id}/{$contract->hash}");
    if (!get_option('bitly_access_token')) {
        return $long_url;
    }

    // Check if contract has short link, if yes return short link
    if (!empty($contract->short_link)) {
        return $contract->short_link;
    }

    // Create short link and return the newly created short link
    $short_link = app_generate_short_link([
        'long_url' => $long_url,
        'title'    => 'Contract #' . $contract->id,
    ]);

    if ($short_link) {
        $CI = &get_instance();
        $CI->db->where('id', $contract->id);
        $CI->db->update(db_prefix() . 'contracts', [
            'short_link' => $short_link,
        ]);

        return $short_link;
    }

    return $long_url;
}

/**
 * Check the contract view restrictions
 *
 * @param  int $id
 * @param  string $hash
 *
 * @return void
 */
function check_contract_restrictions($id, $hash = '')
{
    $CI = &get_instance();
    $CI->load->model('contracts_model');

    if (!$id) {
        show_404();
    }

    $contract = $CI->contracts_model->get($id);

    if (!$contract) {
        show_404();
    }

    // Staff can view any contract without hash
    if (is_staff_logged_in()) {
        return;
    }

    // Authenticated client viewing own contract — allow without hash
    if (is_client_logged_in() && $contract->client == get_client_user_id()) {
        return;
    }

    // Public access — hash is required
    if (!$hash) {
        show_404();
    }

    if ($contract->hash != $hash) {
        show_404();
    }

    if (!is_client_logged_in() && !is_staff_logged_in()) {
        if (get_option('view_contract_only_logged_in') == 1) {
            redirect_after_login_to_current_url();
            redirect(site_url('authentication/login'));
        }
    }

    // Do one more check
    if (!is_staff_logged_in()) {
        if (get_option('view_contract_only_logged_in') == 1) {
            if ($contract->client != get_client_user_id()) {
                show_404();
            }
        }
    }
}

/**
 * Function that will search possible contracts templates in applicaion/views/admin/contracts/templates
 * Will return any found files and user will be able to add new template
 *
 * @return array
 */
function get_contract_templates()
{
    $dir      = VIEWPATH . 'admin/contracts/templates';
    $cache    = class_exists('App_persistent_cache', false) ? App_persistent_cache::instance() : null;
    $cacheKey = 'contract_templates_' . (int) @filemtime($dir);
    $found    = false;

    if ($cache) {
        $cached = $cache->get($cacheKey, $found);
        if ($found && is_array($cached)) {
            return $cached;
        }
    }

    $contract_templates = [];
    if (is_dir($dir)) {
        foreach (list_files($dir) as $template) {
            $contract_templates[] = $template;
        }
    }

    if ($cache) {
        $cache->set($cacheKey, $contract_templates, 86400);
    }

    return $contract_templates;
}

/**
 * Send contract signed notification to staff members
 *
 * @param  int $contract_id
 *
 * @return void
 */
function send_contract_signed_notification_to_staff($contract_id)
{
    $CI = &get_instance();
    $CI->db->where('id', $contract_id);
    $contract = $CI->db->get(db_prefix() . 'contracts')->row();

    if (!$contract) {
        return false;
    }

    // Get creator
    $CI->db->select('staffid, email');
    $CI->db->where('staffid', $contract->addedfrom);
    $staff_contract = $CI->db->get(db_prefix() . 'staff')->result_array();

    $notifiedUsers = [];

    foreach ($staff_contract as $member) {
        $notified = add_notification([
            'description'     => 'not_contract_signed',
            'touserid'        => $member['staffid'],
            'fromcompany'     => 1,
            'fromuserid'      => 0,
            'link'            => 'contracts/contract/' . $contract->id,
            'additional_data' => serialize([
                '<b>' . $contract->subject . '</b>',
            ]),
        ]);

        if ($notified) {
            array_push($notifiedUsers, $member['staffid']);
        }

        send_mail_template('contract_signed_to_staff', $contract, $member);
    }

    pusher_trigger_notification($notifiedUsers);
}

/**
 * Get the recently created contracts in the given days
 *
 * @param  integer $days
 * @param  integer|null $staffId
 *
 * @return integer
 */
function count_recently_created_contracts($days = 7, $staffId = null)
{
    $diff1     = date('Y-m-d', strtotime('-' . $days . ' days'));
    $diff2     = date('Y-m-d', strtotime('+' . $days . ' days'));
    $staffId   = is_null($staffId) ? get_staff_user_id() : $staffId;
    $where_own = [];

    if (staff_cant('view', 'contracts')) {
        $where_own = ['addedfrom' => $staffId];
    }

    return total_rows(db_prefix() . 'contracts', 'dateadded BETWEEN "' . $diff1 . '" AND "' . $diff2 . '" AND trash=0' . (count($where_own) > 0 ? ' AND addedfrom=' . $staffId : ''));
}

/**
 * Get total number of active contracts
 *
 * @param integer|null $staffId
 *
 * @return integer
 */
function count_active_contracts($staffId = null)
{
    $where_own = [];
    $staffId   = is_null($staffId) ? get_staff_user_id() : $staffId;

    if (staff_cant('view', 'contracts')) {
        $where_own = ['addedfrom' => $staffId];
    }

    return total_rows(db_prefix() . 'contracts', '(DATE(dateend) >"' . date('Y-m-d') . '" AND trash=0' . (count($where_own) > 0 ? ' AND addedfrom=' . $staffId : '') . ') OR (DATE(dateend) IS NULL AND trash=0' . (count($where_own) > 0 ? ' AND addedfrom=' . $staffId : '') . ')');
}

/**
 * Get total number of expired contracts
 *
 * @param integer|null $staffId
 *
 * @return integer
 */
function count_expired_contracts($staffId = null)
{
    $where_own = [];
    $staffId   = is_null($staffId) ? get_staff_user_id() : $staffId;

    if (staff_cant('view', 'contracts')) {
        $where_own = ['addedfrom' => $staffId];
    }

    return total_rows(db_prefix() . 'contracts', array_merge(['DATE(dateend) <' => date('Y-m-d'), 'trash' => 0], $where_own));
}

/**
 * Get total number of trash contracts
 *
 * @param integer|null $staffId
 *
 * @return integer
 */
function count_trash_contracts($staffId = null)
{
    $where_own = [];
    $staffId   = is_null($staffId) ? get_staff_user_id() : $staffId;

    if (staff_cant('view', 'contracts')) {
        $where_own = ['addedfrom' => $staffId];
    }

    return total_rows(db_prefix() . 'contracts', array_merge(['trash' => 1], $where_own));
}
