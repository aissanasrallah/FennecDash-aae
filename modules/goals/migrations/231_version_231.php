<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_231 extends App_module_migration
{
    public function up()
    {
        $CI       = &get_instance();
        $goalsTbl = db_prefix() . 'goals';
        $staffTbl = db_prefix() . 'goal_staff';
        $perTbl   = db_prefix() . 'goal_periods';

        if ($CI->db->table_exists($goalsTbl)) {
            $CI->db->query('ALTER TABLE `' . $goalsTbl . '` MODIFY COLUMN `achievement` DECIMAL(15,2) NOT NULL DEFAULT 0.00');

            if (!$CI->db->field_exists('recurrence_type', $goalsTbl)) {
                $CI->db->query('ALTER TABLE `' . $goalsTbl . "` ADD COLUMN `recurrence_type` VARCHAR(20) NOT NULL DEFAULT 'one_time' AFTER `goal_type`");
            }
            if (!$CI->db->field_exists('recurrence_interval', $goalsTbl)) {
                $CI->db->query('ALTER TABLE `' . $goalsTbl . '` ADD COLUMN `recurrence_interval` INT(11) NOT NULL DEFAULT 1 AFTER `recurrence_type`');
            }
            if (!$CI->db->field_exists('custom_period_days', $goalsTbl)) {
                $CI->db->query('ALTER TABLE `' . $goalsTbl . '` ADD COLUMN `custom_period_days` INT(11) DEFAULT NULL AFTER `recurrence_interval`');
            }
            if (!$CI->db->field_exists('attribution_mode', $goalsTbl)) {
                $CI->db->query('ALTER TABLE `' . $goalsTbl . "` ADD COLUMN `attribution_mode` VARCHAR(20) NOT NULL DEFAULT 'individual' AFTER `custom_period_days`");
            }
            if (!$CI->db->field_exists('series_active', $goalsTbl)) {
                $CI->db->query('ALTER TABLE `' . $goalsTbl . '` ADD COLUMN `series_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `attribution_mode`');
            }
        }

        if (!$CI->db->table_exists($staffTbl)) {
            $CI->db->query('CREATE TABLE `' . $staffTbl . '` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goal_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `assigned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `removed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_goal_staff` (`goal_id`, `staff_id`),
  KEY `idx_goal_staff_lookup` (`staff_id`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
        }

        if (!$CI->db->table_exists($perTbl)) {
            $CI->db->query('CREATE TABLE `' . $perTbl . '` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goal_id` int(11) NOT NULL,
  `period_key` varchar(32) NOT NULL,
  `period_label` varchar(120) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `target_achievement` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_historical` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_goal_period` (`goal_id`, `period_key`),
  KEY `idx_goal_period_dates` (`goal_id`, `period_start`, `period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
        }

        // Clear CI cached table list so newly created tables are visible for backfill in same request.
        unset($CI->db->data_cache['table_names']);

        if ($CI->db->table_exists($goalsTbl) && $CI->db->table_exists($staffTbl)) {
            $CI->db->query("INSERT IGNORE INTO `{$staffTbl}` (`goal_id`, `staff_id`, `active`)
                SELECT `id`, `staff_id`, 1 FROM `{$goalsTbl}` WHERE `staff_id` > 0");
        }
        if ($CI->db->table_exists($goalsTbl) && $CI->db->table_exists($perTbl)) {
            $CI->db->query("INSERT IGNORE INTO `{$perTbl}` (`goal_id`, `period_key`, `period_label`, `period_start`, `period_end`, `target_achievement`, `is_historical`)
                SELECT `id`, 'once', CONCAT(DATE_FORMAT(`start_date`, '%Y-%m-%d'), ' → ', DATE_FORMAT(`end_date`, '%Y-%m-%d')),
                       `start_date`, `end_date`, `achievement`, IF(`end_date` < CURDATE(), 1, 0)
                FROM `{$goalsTbl}`");
        }
    }
}
