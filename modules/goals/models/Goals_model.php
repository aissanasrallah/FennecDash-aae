<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Goals_model extends App_Model
{
    private const RECURRENCE_TYPES = ['one_time', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'];
    private const ATTRIBUTION_MODES = ['individual', 'shared'];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get single goal or list of goals.
     */
    public function get($id = '', $exclude_notified = false)
    {
        if (is_numeric($id)) {
            $this->db->where('id', (int) $id);

            return $this->db->get(db_prefix() . 'goals')->row();
        }

        if ($exclude_notified == true) {
            $this->db->where('notified', 0);
        }

        return $this->db->get(db_prefix() . 'goals')->result_array();
    }

    /**
     * Get all Invoiced Amount goals (goal_type = 8) for HR percentage salary linking.
     */
    public function get_invoiced_amount_goals()
    {
        $this->db->where('goal_type', 8);
        $this->db->order_by('id', 'DESC');
        $goals = $this->db->get(db_prefix() . 'goals')->result_array();
        foreach ($goals as &$g) {
            $g['assigned_staff'] = $this->get_goal_staff((int) $g['id'], true);
            $g['periods']        = $this->get_goal_periods((int) $g['id']);
        }
        unset($g);

        return $goals;
    }

    public function get_all_goals($exclude_notified = true)
    {
        if ($exclude_notified) {
            $this->db->where('notified', 0);
        }

        $this->db->order_by('end_date', 'asc');
        $goals = $this->db->get(db_prefix() . 'goals')->result_array();

        foreach ($goals as $key => $val) {
            $goal = get_goal_type($val['goal_type']);

            if (!$goal || ($goal && isset($goal['dashboard']) && $goal['dashboard'] === false)) {
                unset($goals[$key]);
                continue;
            }

            $goals[$key]['achievement']    = $this->calculate_goal_achievement($val['id']);
            $goals[$key]['goal_type_name'] = format_goal_type($val['goal_type']);
        }

        return array_values($goals);
    }

    public function get_staff_goals($staff_id, $exclude_notified = true)
    {
        $staff_id    = (int) $staff_id;
        $hasStaffTbl = $this->db->table_exists(db_prefix() . 'goal_staff');

        $this->db->distinct();
        $this->db->select(db_prefix() . 'goals.*');
        $this->db->from(db_prefix() . 'goals');
        if ($hasStaffTbl) {
            $this->db->join(
                db_prefix() . 'goal_staff',
                db_prefix() . 'goal_staff.goal_id = ' . db_prefix() . 'goals.id AND ' . db_prefix() . 'goal_staff.active = 1',
                'left'
            );
            $this->db->group_start();
            $this->db->where(db_prefix() . 'goals.staff_id', $staff_id);
            $this->db->or_where(db_prefix() . 'goal_staff.staff_id', $staff_id);
            $this->db->group_end();
        } else {
            $this->db->where(db_prefix() . 'goals.staff_id', $staff_id);
        }

        if ($exclude_notified) {
            $this->db->where(db_prefix() . 'goals.notified', 0);
        }

        $this->db->order_by(db_prefix() . 'goals.end_date', 'asc');
        $goals = $this->db->get()->result_array();

        foreach ($goals as $key => $val) {
            $goals[$key]['achievement']    = $this->calculate_goal_achievement($val['id'], null, $staff_id);
            $goals[$key]['goal_type_name'] = format_goal_type($val['goal_type']);
        }

        return $goals;
    }

    /**
     * Check whether a staff member is assigned to a goal (or the goal is company-wide).
     */
    public function is_staff_assigned($goal_id, $staff_id)
    {
        $goal = $this->get((int) $goal_id);
        if (!$goal) {
            return false;
        }
        $staff_id = (int) $staff_id;
        $assigned = $this->get_goal_staff((int) $goal_id, true);
        if (empty($assigned)) {
            return ((int) $goal->staff_id === 0 || (int) $goal->staff_id === $staff_id);
        }
        foreach ($assigned as $row) {
            if ((int) $row['staff_id'] === $staff_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return assigned staff members from tblgoal_staff (with legacy fallback).
     */
    public function get_goal_staff($goal_id, $active_only = true)
    {
        $goal_id = (int) $goal_id;
        $rows    = [];
        if ($this->db->table_exists(db_prefix() . 'goal_staff')) {
            $this->db->select('gs.*, s.firstname, s.lastname, s.email, s.active AS staff_active');
            $this->db->from(db_prefix() . 'goal_staff gs');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = gs.staff_id', 'left');
            $this->db->where('gs.goal_id', $goal_id);
            if ($active_only) {
                $this->db->where('gs.active', 1);
            }
            $this->db->order_by('s.firstname', 'ASC');
            $rows = $this->db->get()->result_array();
        }

        if (empty($rows) && $active_only) {
            $goal = $this->get($goal_id);
            if ($goal && (int) $goal->staff_id > 0) {
                $this->db->select('staffid AS staff_id, firstname, lastname, email, active AS staff_active');
                $this->db->where('staffid', (int) $goal->staff_id);
                $st = $this->db->get(db_prefix() . 'staff')->row_array();
                if ($st) {
                    $st['goal_id'] = $goal_id;
                    $st['active']  = 1;
                    return [$st];
                }
            }
        }

        return $rows;
    }

    /**
     * Sync active staff assignments without deleting historical rows.
     */
    public function sync_goal_staff($goal_id, array $staff_ids)
    {
        if (!$this->db->table_exists(db_prefix() . 'goal_staff')) {
            return;
        }
        $goal_id   = (int) $goal_id;
        $cleanIds  = array_values(array_unique(array_filter(array_map('intval', $staff_ids), function ($x) {
            return $x > 0;
        })));

        $existingRows = $this->db->where('goal_id', $goal_id)->get(db_prefix() . 'goal_staff')->result_array();
        $existingMap  = [];
        foreach ($existingRows as $er) {
            $existingMap[(int) $er['staff_id']] = $er;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($cleanIds as $sid) {
            if (isset($existingMap[$sid])) {
                if ((int) $existingMap[$sid]['active'] !== 1) {
                    $this->db->where('goal_id', $goal_id)->where('staff_id', $sid)
                        ->update(db_prefix() . 'goal_staff', ['active' => 1, 'removed_at' => null]);
                }
            } else {
                $this->db->insert(db_prefix() . 'goal_staff', [
                    'goal_id'     => $goal_id,
                    'staff_id'    => $sid,
                    'active'      => 1,
                    'assigned_at' => $now,
                ]);
            }
        }

        foreach ($existingMap as $sid => $er) {
            if (!in_array($sid, $cleanIds, true) && (int) $er['active'] === 1) {
                $this->db->where('goal_id', $goal_id)->where('staff_id', $sid)
                    ->update(db_prefix() . 'goal_staff', ['active' => 0, 'removed_at' => $now]);
            }
        }
    }

    /**
     * Build clean French/ISO period definitions for any recurrence mode.
     */
    public function generate_period_definitions($recurrenceType, $startDate, $endDate, $customDays = null, $horizonDate = null)
    {
        $start = new DateTimeImmutable($startDate);
        $end   = new DateTimeImmutable($endDate);
        if ($end < $start) {
            $end = $start;
        }

        if ($recurrenceType === 'one_time') {
            return [[
                'period_key'    => 'once',
                'period_label'  => $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y'),
                'period_start'  => $start->format('Y-m-d'),
                'period_end'    => $end->format('Y-m-d'),
                'is_historical' => $end->format('Y-m-d') < date('Y-m-d') ? 1 : 0,
            ]];
        }

        $today   = new DateTimeImmutable($horizonDate ?: date('Y-m-d'));
        $capDate = $end < $today ? $end : $today;
        if ($capDate < $start) {
            $capDate = $start;
        }

        $monthsFr = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        $periods   = [];
        $cursor    = $start;
        $maxSafety = 400; // prevents runaway loops on extreme ranges
        $idx       = 0;

        while ($cursor <= $capDate && $idx < $maxSafety) {
            $idx++;
            switch ($recurrenceType) {
                case 'daily':
                    $pStart = $cursor;
                    $pEnd   = $cursor;
                    $pKey   = $pStart->format('Y-m-d');
                    $pLabel = $pStart->format('d/m/Y');
                    $next   = $cursor->modify('+1 day');
                    break;

                case 'weekly':
                    $pStart   = $cursor;
                    $dow      = (int) $cursor->format('N'); // 1=Mon..7=Sun
                    $pEndFull = $cursor->modify('+' . (7 - $dow) . ' days');
                    $pEnd     = $pEndFull > $end ? $end : $pEndFull;
                    $pKey     = $pStart->format('o-\WW');
                    if (isset($periods[$pKey])) {
                        $pKey .= '_' . $idx;
                    }
                    $pLabel = 'Semaine ' . $pStart->format('W/o') . ' (' . $pStart->format('d/m/Y') . ' - ' . $pEnd->format('d/m/Y') . ')';
                    $next   = $pEndFull->modify('+1 day');
                    break;

                case 'monthly':
                    $pStart   = $cursor;
                    $pEndFull = new DateTimeImmutable($cursor->format('Y-m-t'));
                    $pEnd     = $pEndFull > $end ? $end : $pEndFull;
                    $pKey     = $cursor->format('Y-m');
                    $mNum     = (int) $cursor->format('n');
                    $pLabel   = $monthsFr[$mNum] . ' ' . $cursor->format('Y') . ' (' . $pStart->format('d/m') . ' - ' . $pEnd->format('d/m/Y') . ')';
                    $next     = (new DateTimeImmutable($cursor->format('Y-m-01')))->modify('+1 month');
                    break;

                case 'quarterly':
                    $pStart   = $cursor;
                    $yr       = (int) $cursor->format('Y');
                    $q        = (int) ceil(((int) $cursor->format('n')) / 3);
                    $endMonth = $q * 3;
                    $pEndFull = new DateTimeImmutable(sprintf('%04d-%02d-01', $yr, $endMonth));
                    $pEndFull = new DateTimeImmutable($pEndFull->format('Y-m-t'));
                    $pEnd     = $pEndFull > $end ? $end : $pEndFull;
                    $pKey     = sprintf('%04d-Q%d', $yr, $q);
                    $pLabel   = sprintf('T%d %04d (%s - %s)', $q, $yr, $pStart->format('d/m/Y'), $pEnd->format('d/m/Y'));
                    $next     = $pEndFull->modify('+1 day');
                    break;

                case 'yearly':
                    $pStart   = $cursor;
                    $yr       = (int) $cursor->format('Y');
                    $pEndFull = new DateTimeImmutable(sprintf('%04d-12-31', $yr));
                    $pEnd     = $pEndFull > $end ? $end : $pEndFull;
                    $pKey     = sprintf('%04d', $yr);
                    $pLabel   = sprintf('Année %04d (%s - %s)', $yr, $pStart->format('d/m/Y'), $pEnd->format('d/m/Y'));
                    $next     = new DateTimeImmutable(sprintf('%04d-01-01', $yr + 1));
                    break;

                case 'custom':
                default:
                    $days     = max(1, (int) ($customDays ?: 30));
                    $pStart   = $cursor;
                    $pEndFull = $cursor->modify('+' . ($days - 1) . ' days');
                    $pEnd     = $pEndFull > $end ? $end : $pEndFull;
                    $pKey     = sprintf('C%03d_%s', $idx, $pStart->format('Y-m-d'));
                    $pLabel   = sprintf('Période #%d (%s - %s)', $idx, $pStart->format('d/m/Y'), $pEnd->format('d/m/Y'));
                    $next     = $pEndFull->modify('+1 day');
                    break;
            }

            $periods[$pKey] = [
                'period_key'    => $pKey,
                'period_label'  => $pLabel,
                'period_start'  => $pStart->format('Y-m-d'),
                'period_end'    => $pEnd->format('Y-m-d'),
                'is_historical' => $pEnd->format('Y-m-d') < date('Y-m-d') ? 1 : 0,
            ];

            if ($next > $end) {
                break;
            }
            $cursor = $next;
        }

        return array_values($periods);
    }

    /**
     * Synchronize recurring or one-time periods into tblgoal_periods without creating duplicates.
     */
    public function ensure_goal_periods($goal_id, $horizonDate = null)
    {
        $goal = $this->get((int) $goal_id);
        if (!$goal) {
            return [];
        }

        $recType = in_array($goal->recurrence_type ?? 'one_time', self::RECURRENCE_TYPES, true)
            ? $goal->recurrence_type
            : 'one_time';

        $defs = $this->generate_period_definitions(
            $recType,
            $goal->start_date,
            $goal->end_date,
            $goal->custom_period_days ?? null,
            $horizonDate
        );

        if (!$this->db->table_exists(db_prefix() . 'goal_periods')) {
            $fallback = [];
            foreach (array_reverse($defs) as $def) {
                $fallback[] = [
                    'id'                 => 0,
                    'goal_id'            => (int) $goal_id,
                    'period_key'         => $def['period_key'],
                    'period_label'       => $def['period_label'],
                    'period_start'       => $def['period_start'],
                    'period_end'         => $def['period_end'],
                    'target_achievement' => (float) $goal->achievement,
                    'is_historical'      => $def['is_historical'],
                ];
            }
            return $fallback;
        }

        $existingRows = $this->db->where('goal_id', (int) $goal_id)->get(db_prefix() . 'goal_periods')->result_array();
        $existingMap  = [];
        foreach ($existingRows as $er) {
            $existingMap[$er['period_key']] = $er;
        }

        $targetVal = (float) $goal->achievement;
        $todayStr  = date('Y-m-d');

        foreach ($defs as $def) {
            $isHist = ($def['period_end'] < $todayStr) ? 1 : 0;
            if (isset($existingMap[$def['period_key']])) {
                $row = $existingMap[$def['period_key']];
                if ((int) $row['is_historical'] !== $isHist
                    || ($recType === 'one_time' && ($row['period_start'] !== $def['period_start'] || $row['period_end'] !== $def['period_end']))
                    || abs((float) $row['target_achievement'] - $targetVal) > 0.009) {
                    $this->db->where('id', (int) $row['id'])->update(db_prefix() . 'goal_periods', [
                        'period_label'       => $def['period_label'],
                        'period_start'       => $def['period_start'],
                        'period_end'         => $def['period_end'],
                        'target_achievement' => $targetVal,
                        'is_historical'      => $isHist,
                    ]);
                }
            } else {
                $this->db->insert(db_prefix() . 'goal_periods', [
                    'goal_id'            => (int) $goal_id,
                    'period_key'         => $def['period_key'],
                    'period_label'       => $def['period_label'],
                    'period_start'       => $def['period_start'],
                    'period_end'         => $def['period_end'],
                    'target_achievement' => $targetVal,
                    'is_historical'      => $isHist,
                ]);
            }
        }

        return $this->get_goal_periods((int) $goal_id);
    }

    public function get_goal_periods($goal_id)
    {
        if (!$this->db->table_exists(db_prefix() . 'goal_periods')) {
            return [];
        }
        $this->db->where('goal_id', (int) $goal_id);
        $this->db->order_by('period_start', 'DESC');

        return $this->db->get(db_prefix() . 'goal_periods')->result_array();
    }

    public function get_period($period_id)
    {
        if (!$this->db->table_exists(db_prefix() . 'goal_periods')) {
            return null;
        }
        $this->db->where('id', (int) $period_id);

        return $this->db->get(db_prefix() . 'goal_periods')->row();
    }

    private function prepare_goal_payload(array $data)
    {
        $staffIds = [];
        if (isset($data['staff_ids']) && is_array($data['staff_ids'])) {
            $staffIds = $data['staff_ids'];
        } elseif (isset($data['staff_id']) && is_array($data['staff_id'])) {
            $staffIds = $data['staff_id'];
        } elseif (!empty($data['staff_id'])) {
            $staffIds = [(int) $data['staff_id']];
        }
        $staffIds = array_values(array_unique(array_filter(array_map('intval', $staffIds), function ($x) {
            return $x > 0;
        })));

        $recType = $data['recurrence_type'] ?? 'one_time';
        if (!in_array($recType, self::RECURRENCE_TYPES, true)) {
            $recType = 'one_time';
        }

        $attrMode = $data['attribution_mode'] ?? 'individual';
        if (!in_array($attrMode, self::ATTRIBUTION_MODES, true)) {
            $attrMode = 'individual';
        }

        $customDays = null;
        if ($recType === 'custom') {
            $customDays = max(1, (int) ($data['custom_period_days'] ?? 30));
        }

        $startDate = to_sql_date($data['start_date']);
        $endDate   = to_sql_date($data['end_date']);
        if ($endDate < $startDate) {
            $endDate = $startDate;
        }

        $payload = [
            'subject'             => trim((string) ($data['subject'] ?? '')),
            'description'         => trim((string) ($data['description'] ?? '')),
            'goal_type'           => (int) ($data['goal_type'] ?? 1),
            'contract_type'       => empty($data['contract_type']) ? 0 : (int) $data['contract_type'],
            'achievement'         => max(0, (float) ($data['achievement'] ?? 0)),
            'start_date'          => $startDate,
            'end_date'            => $endDate,
            'notify_when_fail'    => isset($data['notify_when_fail']) ? 1 : 0,
            'notify_when_achieve' => isset($data['notify_when_achieve']) ? 1 : 0,
            'staff_id'            => count($staffIds) === 1 ? $staffIds[0] : 0,
        ];

        if ($this->db->field_exists('recurrence_type', db_prefix() . 'goals')) {
            $payload['recurrence_type']     = $recType;
            $payload['recurrence_interval'] = max(1, (int) ($data['recurrence_interval'] ?? 1));
            $payload['custom_period_days']  = $customDays;
            $payload['attribution_mode']    = $attrMode;
            $payload['series_active']       = isset($data['series_active']) ? (int) (bool) $data['series_active'] : 1;
        }

        return [$payload, $staffIds];
    }

    /**
     * Add new goal
     */
    public function add($data)
    {
        [$payload, $staffIds] = $this->prepare_goal_payload($data);

        $this->db->insert(db_prefix() . 'goals', $payload);
        $insert_id = (int) $this->db->insert_id();
        if ($insert_id) {
            $this->sync_goal_staff($insert_id, $staffIds);
            $this->ensure_goal_periods($insert_id);
            log_activity('New Goal Added [ID:' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * Update goal
     */
    public function update($data, $id)
    {
        $id   = (int) $id;
        $goal = $this->get($id);
        if (!$goal) {
            return false;
        }

        [$payload, $staffIds] = $this->prepare_goal_payload($data);
        if ($goal->notified == 1 && date('Y-m-d') < $payload['end_date']) {
            $payload['notified'] = 0;
        }

        // Preserve historical periods if recurrence changed? Keep previously recorded periods in tblgoal_periods,
        // and replace/regenerate only for 'one_time' or append missing periods cleanly.
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'goals', $payload);
        $affected = $this->db->affected_rows();

        $this->sync_goal_staff($id, $staffIds);
        $this->ensure_goal_periods($id);

        if ($affected >= 0) {
            log_activity('Goal Updated [ID:' . $id . ']');
            return true;
        }

        return false;
    }

    /**
     * Delete goal
     */
    public function delete($id)
    {
        $id = (int) $id;
        if ($this->db->table_exists(db_prefix() . 'goal_staff')) {
            $this->db->where('goal_id', $id)->delete(db_prefix() . 'goal_staff');
        }
        if ($this->db->table_exists(db_prefix() . 'goal_periods')) {
            $this->db->where('goal_id', $id)->delete(db_prefix() . 'goal_periods');
        }
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'goals');
        if ($this->db->affected_rows() > 0) {
            log_activity('Goal Deleted [ID:' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Notify staff members about goal result
     */
    public function notify_staff_members($id, $notify_type, $achievement = '')
    {
        $goal = $this->get($id);
        if (!$goal) {
            return false;
        }
        if ($achievement == '') {
            $achievement = $this->calculate_goal_achievement($id);
        }
        $goal_desc = $notify_type == 'success' ? 'not_goal_message_success' : 'not_goal_message_failed';

        $assigned = $this->get_goal_staff($goal->id, true);
        if (empty($assigned)) {
            $this->load->model('staff_model');
            $staff = $this->staff_model->get('', ['active' => 1]);
        } else {
            $ids   = array_column($assigned, 'staff_id');
            $staff = $this->db->where('active', 1)->where_in('staffid', $ids)->get(db_prefix() . 'staff')->result_array();
        }

        $notifiedUsers = [];
        foreach ($staff as $member) {
            if (is_staff_member($member['staffid'])) {
                $notified = add_notification([
                    'fromcompany'     => 1,
                    'touserid'        => $member['staffid'],
                    'description'     => $goal_desc,
                    'additional_data' => serialize([
                        format_goal_type($goal->goal_type),
                        $goal->achievement,
                        $achievement['total'],
                        _d($achievement['period_start'] ?? $goal->start_date),
                        _d($achievement['period_end'] ?? $goal->end_date),
                    ]),
                ]);
                if ($notified) {
                    $notifiedUsers[] = $member['staffid'];
                }
            }
        }

        pusher_trigger_notification($notifiedUsers);
        $this->mark_as_notified($goal->id);

        return count($staff) > 0;
    }

    /**
     * Resolve effective period row for a goal (explicit period_id -> current period -> latest period -> fallback).
     */
    public function resolve_period($goal, $periodId = null)
    {
        if (!$goal) {
            return null;
        }
        $periods = $this->ensure_goal_periods((int) $goal->id);
        if ($periodId !== null && (int) $periodId > 0) {
            foreach ($periods as $p) {
                if ((int) $p['id'] === (int) $periodId) {
                    return $p;
                }
            }
        }

        $today = date('Y-m-d');
        foreach ($periods as $p) {
            if ($p['period_start'] <= $today && $p['period_end'] >= $today) {
                return $p;
            }
        }
        if (!empty($periods)) {
            return $periods[0]; // most recent period (ordered DESC)
        }

        return [
            'id'                 => 0,
            'goal_id'            => (int) $goal->id,
            'period_key'         => 'once',
            'period_label'       => _d($goal->start_date) . ' - ' . _d($goal->end_date),
            'period_start'       => $goal->start_date,
            'period_end'         => $goal->end_date,
            'target_achievement' => (float) $goal->achievement,
            'is_historical'      => $goal->end_date < $today ? 1 : 0,
        ];
    }

    /**
     * Shared Net Invoiced HT calculation helper (Invoices HT - non-void Credit Notes HT).
     * Guarantees no invoice or credit note is counted more than once.
     */
    public function compute_net_invoiced_revenue($startDate, $endDate, array $staffFilterIds = [])
    {
        $startEsc = $this->db->escape($startDate);
        $endEsc   = $this->db->escape($endDate);

        $invWhere = db_prefix() . "invoices.date BETWEEN {$startEsc} AND {$endEsc} AND " . db_prefix() . 'invoices.status NOT IN (5, 6)';
        if (!empty($staffFilterIds)) {
            $inIds = implode(',', array_map('intval', $staffFilterIds));
            $invWhere .= ' AND ' . db_prefix() . "invoices.sale_agent IN ({$inIds})";
        }

        $invRow = $this->db->query(
            'SELECT COUNT(DISTINCT id) AS cnt, COALESCE(SUM(subtotal), 0) AS total_ht FROM ' . db_prefix() . 'invoices WHERE ' . $invWhere
        )->row();

        $cnTable = db_prefix() . 'creditnotes';
        $crTable = db_prefix() . 'credits';
        $invTbl  = db_prefix() . 'invoices';
        $cnWhere = "cn.date BETWEEN {$startEsc} AND {$endEsc} AND (cn.status IS NULL OR cn.status != 3)";
        if (!empty($staffFilterIds)) {
            $inIds = implode(',', array_map('intval', $staffFilterIds));
            $cnWhere .= " AND (
                EXISTS (
                    SELECT 1 FROM {$crTable} cr
                    JOIN {$invTbl} inv ON inv.id = cr.invoice_id
                    WHERE cr.credit_id = cn.id AND inv.sale_agent IN ({$inIds})
                )
                OR (
                    NOT EXISTS (SELECT 1 FROM {$crTable} cr2 WHERE cr2.credit_id = cn.id)
                    AND cn.addedfrom IN ({$inIds})
                )
            )";
        }

        $cnRow = $this->db->query(
            "SELECT COUNT(DISTINCT cn.id) AS cnt, COALESCE(SUM(cn.subtotal), 0) AS total_ht FROM {$cnTable} cn WHERE {$cnWhere}"
        )->row();

        $grossHt  = round((float) ($invRow->total_ht ?? 0), 2);
        $creditHt = round((float) ($cnRow->total_ht ?? 0), 2);
        $netHt    = round($grossHt - $creditHt, 2);

        return [
            'gross_ht'      => $grossHt,
            'credit_ht'     => $creditHt,
            'net_ht'        => $netHt,
            'invoice_count' => (int) ($invRow->cnt ?? 0),
            'credit_count'  => (int) ($cnRow->cnt ?? 0),
        ];
    }

    /**
     * Calculate goal achievement for a period (and optionally a specific employee).
     */
    public function calculate_goal_achievement($id, $periodId = null, $staffId = null)
    {
        $goal = $this->get((int) $id);
        if (!$goal) {
            return [
                'total'                => 0,
                'percent'              => 0,
                'progress_bar_percent' => 0,
            ];
        }

        $period     = $this->resolve_period($goal, $periodId);
        $start_date = $period['period_start'];
        $end_date   = $period['period_end'];
        $targetVal  = (float) ($period['target_achievement'] ?? $goal->achievement);
        $type       = (int) $goal->goal_type;
        $attrMode   = $goal->attribution_mode ?? 'individual';

        $staffFilterIds = [];
        if ($staffId !== null && (int) $staffId > 0) {
            if ($attrMode !== 'shared') {
                $staffFilterIds = [(int) $staffId];
            }
        } else {
            $assigned = $this->get_goal_staff((int) $goal->id, true);
            if ($attrMode !== 'shared' && !empty($assigned)) {
                $staffFilterIds = array_map('intval', array_column($assigned, 'staff_id'));
            }
        }

        $extra = [
            'gross_ht'         => 0.0,
            'credit_ht'        => 0.0,
            'invoice_count'    => 0,
            'credit_count'     => 0,
            'period_id'        => (int) ($period['id'] ?? 0),
            'period_key'       => $period['period_key'] ?? 'once',
            'period_label'     => $period['period_label'] ?? '',
            'period_start'     => $start_date,
            'period_end'       => $end_date,
            'target'           => $targetVal,
            'attribution_mode' => $attrMode,
        ];

        $total    = 0.0;
        $startEsc = $this->db->escape($start_date);
        $endEsc   = $this->db->escape($end_date);
        $inStaff  = !empty($staffFilterIds) ? implode(',', $staffFilterIds) : '';

        if ($type == 1) {
            $sql = 'SELECT COALESCE(SUM(' . db_prefix() . 'invoicepaymentrecords.amount), 0) as total FROM ' . db_prefix() . 'invoicepaymentrecords';
            if ($inStaff !== '') {
                $sql .= ' JOIN ' . db_prefix() . 'invoices ON ' . db_prefix() . 'invoices.id = ' . db_prefix() . 'invoicepaymentrecords.invoiceid';
            }
            $sql .= ' WHERE ' . db_prefix() . "invoicepaymentrecords.date BETWEEN {$startEsc} AND {$endEsc}";
            if ($inStaff !== '') {
                $sql .= ' AND ' . db_prefix() . "invoices.sale_agent IN ({$inStaff})";
            }
            $total = floatval($this->db->query($sql)->row()->total ?? 0);
        } elseif ($type == 8) {
            $rev   = $this->compute_net_invoiced_revenue($start_date, $end_date, $staffFilterIds);
            $total = $rev['net_ht'];
            $extra['gross_ht']      = $rev['gross_ht'];
            $extra['credit_ht']     = $rev['credit_ht'];
            $extra['invoice_count'] = $rev['invoice_count'];
            $extra['credit_count']  = $rev['credit_count'];
        } elseif ($type == 2) {
            $sql = 'SELECT COUNT(' . db_prefix() . 'leads.id) as total FROM ' . db_prefix() . "leads WHERE DATE(date_converted) BETWEEN {$startEsc} AND {$endEsc} AND status = 1 AND " . db_prefix() . 'leads.id IN (SELECT leadid FROM ' . db_prefix() . 'clients WHERE leadid=' . db_prefix() . 'leads.id)';
            if ($inStaff !== '') {
                $sql .= " AND CASE WHEN assigned=0 THEN addedfrom IN ({$inStaff}) ELSE assigned IN ({$inStaff}) END";
            }
            $total = floatval($this->db->query($sql)->row()->total ?? 0);
        } elseif ($type == 3) {
            $sql = 'SELECT COUNT(' . db_prefix() . 'clients.userid) as total FROM ' . db_prefix() . "clients WHERE DATE(datecreated) BETWEEN {$startEsc} AND {$endEsc} AND leadid IS NULL";
            if ($inStaff !== '') {
                $sql .= " AND addedfrom IN ({$inStaff})";
            }
            $total = floatval($this->db->query($sql)->row()->total ?? 0);
        } elseif ($type == 4) {
            $sql = 'SELECT COUNT(' . db_prefix() . 'clients.userid) as total FROM ' . db_prefix() . "clients WHERE DATE(datecreated) BETWEEN {$startEsc} AND {$endEsc}";
            if ($inStaff !== '') {
                $sql .= " AND addedfrom IN ({$inStaff})";
            }
            $total = floatval($this->db->query($sql)->row()->total ?? 0);
        } elseif ($type == 5 || $type == 7) {
            $column = $type == 7 ? 'datestart' : 'dateadded';
            $sql    = 'SELECT count(id) as total FROM ' . db_prefix() . 'contracts WHERE ' . $column . " BETWEEN {$startEsc} AND {$endEsc} AND contract_type = " . (int) $goal->contract_type . ' AND trash = 0';
            if ($inStaff !== '') {
                $sql .= " AND addedfrom IN ({$inStaff})";
            }
            $total = floatval($this->db->query($sql)->row()->total ?? 0);
        } elseif ($type == 6) {
            $sql = 'SELECT count(id) as total FROM ' . db_prefix() . "estimates WHERE DATE(invoiced_date) BETWEEN {$startEsc} AND {$endEsc} AND invoiceid IS NOT NULL AND invoiceid NOT IN (SELECT id FROM " . db_prefix() . 'invoices WHERE status=5)';
            if ($inStaff !== '') {
                $sql .= " AND (addedfrom IN ({$inStaff}) OR sale_agent IN ({$inStaff}))";
            }
            $total = floatval($this->db->query($sql)->row()->total ?? 0);
        } else {
            $sql = hooks()->apply_filters('calculate_goal_achievement_sql', '', $goal);
            if ($sql === '') {
                return array_merge(['total' => 0, 'percent' => 0, 'progress_bar_percent' => 0], $extra);
            }
            $total = floatval($this->db->query($sql)->row()->total ?? 0);
        }

        $percent = 0;
        if ($targetVal > 0) {
            if ($total >= $targetVal) {
                $percent = 100;
            } elseif ($total > 0) {
                $percent = round(($total * 100) / $targetVal, 2);
            }
        }
        $progress_bar_percent = $percent / 100;

        return array_merge([
            'total'                => $total,
            'percent'              => $percent,
            'progress_bar_percent' => $progress_bar_percent,
        ], $extra);
    }

    /**
     * Independent progress breakdown per assigned employee for a goal & period.
     */
    public function calculate_goal_staff_achievements($id, $periodId = null)
    {
        $assigned = $this->get_goal_staff((int) $id, true);
        $out      = [];
        foreach ($assigned as $member) {
            $sid          = (int) $member['staff_id'];
            $ach          = $this->calculate_goal_achievement((int) $id, $periodId, $sid);
            $out[]        = array_merge($member, $ach);
        }

        return $out;
    }

    public function mark_as_notified($id)
    {
        $this->db->where('id', (int) $id);
        $this->db->update(db_prefix() . 'goals', [
            'notified' => 1,
        ]);
    }
}
