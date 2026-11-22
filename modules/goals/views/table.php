<?php

defined('BASEPATH') or exit('No direct script access allowed');

$hasGoalStaff  = $this->ci->db->table_exists(db_prefix() . 'goal_staff');
$hasRecurrence = $this->ci->db->field_exists('recurrence_type', db_prefix() . 'goals');

if ($hasGoalStaff) {
    $staffExpr = '(SELECT GROUP_CONCAT(CONCAT(s2.firstname, " ", s2.lastname) SEPARATOR ", ") FROM '
        . db_prefix() . 'goal_staff gs2 JOIN ' . db_prefix() . 'staff s2 ON s2.staffid = gs2.staff_id WHERE gs2.goal_id = '
        . db_prefix() . 'goals.id AND gs2.active = 1)';
} else {
    $staffExpr = '(SELECT CONCAT(firstname, " ", lastname) FROM ' . db_prefix() . 'staff WHERE ' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'goals.staff_id)';
}

$recurrenceCol = $hasRecurrence ? (db_prefix() . 'goals.recurrence_type') : (db_prefix() . 'goals.goal_type');

$aColumns = [
    db_prefix() . 'goals.subject',
    $staffExpr,
    db_prefix() . 'goals.achievement',
    db_prefix() . 'goals.start_date',
    $recurrenceCol,
    db_prefix() . 'goals.goal_type',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'goals';
$join         = [];

$where        = [];
$periodFilter = $this->ci->input->get('period') ?: 'all';
$todayEsc     = $this->ci->db->escape(date('Y-m-d'));
if ($periodFilter === 'current') {
    $where[] = 'AND ' . db_prefix() . "goals.end_date >= {$todayEsc}";
} elseif ($periodFilter === 'historical') {
    $where[] = 'AND ' . db_prefix() . "goals.end_date < {$todayEsc}";
}
if (!is_admin() && staff_cant('edit', 'goals')) {
    $me = (int) get_staff_user_id();
    if ($hasGoalStaff) {
        $where[] = 'AND (' . db_prefix() . 'goals.staff_id = ' . $me
            . ' OR EXISTS (SELECT 1 FROM ' . db_prefix() . 'goal_staff gs3 WHERE gs3.goal_id = ' . db_prefix() . 'goals.id AND gs3.staff_id = ' . $me . ' AND gs3.active = 1)'
            . ' OR (' . db_prefix() . 'goals.staff_id = 0 AND NOT EXISTS (SELECT 1 FROM ' . db_prefix() . 'goal_staff gs4 WHERE gs4.goal_id = ' . db_prefix() . 'goals.id AND gs4.active = 1)))';
    } else {
        $where[] = 'AND (' . db_prefix() . 'goals.staff_id = ' . $me . ' OR ' . db_prefix() . 'goals.staff_id = 0)';
    }
}

$additionalSelect = [
    db_prefix() . 'goals.id',
    db_prefix() . 'goals.end_date',
];
if ($hasRecurrence) {
    $additionalSelect[] = db_prefix() . 'goals.attribution_mode';
}

$searchAs = [
    db_prefix() . 'goals.subject',
    db_prefix() . 'goals.subject',
    db_prefix() . 'goals.achievement',
    db_prefix() . 'goals.start_date',
    $recurrenceCol,
    db_prefix() . 'goals.goal_type',
];

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalSelect, '', $searchAs);
$output  = $result['output'];
$rResult = $result['rResult'];

$recLabels = [
    'one_time'  => _l('goal_recurrence_one_time'),
    'daily'     => _l('goal_recurrence_daily'),
    'weekly'    => _l('goal_recurrence_weekly'),
    'monthly'   => _l('goal_recurrence_monthly'),
    'quarterly' => _l('goal_recurrence_quarterly'),
    'yearly'    => _l('goal_recurrence_yearly'),
    'custom'    => _l('goal_recurrence_custom'),
];

foreach ($rResult as $aRow) {
    $achievement = $this->ci->goals_model->calculate_goal_achievement($aRow['id']);
    $row         = [];

    $subj = '<a href="' . admin_url('goals/goal/' . $aRow['id']) . '" class="tw-font-medium">' . e($aRow[db_prefix() . 'goals.subject']) . '</a>';
    $subj .= '<div class="row-options">';
    $subj .= '<a href="' . admin_url('goals/goal/' . $aRow['id']) . '">' . _l('view') . '</a>';
    if (staff_can('delete', 'goals')) {
        $subj .= ' | <a href="' . admin_url('goals/delete/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
    }
    $subj .= '</div>';
    $row[] = $subj;

    $staffText = trim((string) ($aRow[$staffExpr] ?? ''));
    $row[]     = $staffText !== '' ? e($staffText) : '<span class="label label-default">' . _l('goal_all_employees_shared') . '</span>';

    $row[] = number_format((float) $aRow[db_prefix() . 'goals.achievement'], 2, '.', ' ');

    $periodDisplay = e($achievement['period_label'] ?? (_d($aRow[db_prefix() . 'goals.start_date']) . ' - ' . _d($aRow['end_date'])));
    $row[]         = $periodDisplay;

    $recKey = $aRow[db_prefix() . 'goals.recurrence_type'] ?? 'one_time';
    $row[]  = '<span class="label label-info">' . e($recLabels[$recKey] ?? $recKey) . '</span>';

    $row[] = e(format_goal_type($aRow[db_prefix() . 'goals.goal_type']));

    $percent              = $achievement['percent'];
    $progress_bar_percent = $achievement['progress_bar_percent'];
    ob_start(); ?>
    <input type="hidden" value="<?php echo e($progress_bar_percent); ?>" name="percent">
    <div class="goal-progress" data-reverse="true">
       <strong class="goal-percent"><?php echo e($percent); ?>%</strong>
    </div>
    <?php
    $row[]              = ob_get_clean();
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
