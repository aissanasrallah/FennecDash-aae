<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_113 extends App_module_migration
{
    public function up()
    {
        $CI              = &get_instance();
        $hrSalaryFormTbl = db_prefix() . 'hr_salary_form';
        $hrCommTbl       = db_prefix() . 'hr_salary_commission_entries';

        if ($CI->db->table_exists($hrSalaryFormTbl)) {
            if (!$CI->db->field_exists('salary_calc_mode', $hrSalaryFormTbl)) {
                $CI->db->query('ALTER TABLE `' . $hrSalaryFormTbl . "` ADD COLUMN `salary_calc_mode` VARCHAR(20) NOT NULL DEFAULT 'fixed' AFTER `tax`;");
            }
            if (!$CI->db->field_exists('percentage_rate', $hrSalaryFormTbl)) {
                $CI->db->query('ALTER TABLE `' . $hrSalaryFormTbl . '` ADD COLUMN `percentage_rate` DECIMAL(7,4) NOT NULL DEFAULT 0.0000 AFTER `salary_calc_mode`;');
            }
            if (!$CI->db->field_exists('goal_id', $hrSalaryFormTbl)) {
                $CI->db->query('ALTER TABLE `' . $hrSalaryFormTbl . '` ADD COLUMN `goal_id` INT(11) DEFAULT NULL AFTER `percentage_rate`;');
            }
            if (!$CI->db->field_exists('revenue_attribution', $hrSalaryFormTbl)) {
                $CI->db->query('ALTER TABLE `' . $hrSalaryFormTbl . "` ADD COLUMN `revenue_attribution` VARCHAR(20) NOT NULL DEFAULT 'individual' AFTER `goal_id`;");
            }
            if (!$CI->db->field_exists('min_achievement_pct', $hrSalaryFormTbl)) {
                $CI->db->query('ALTER TABLE `' . $hrSalaryFormTbl . '` ADD COLUMN `min_achievement_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `revenue_attribution`;');
            }
        }

        if (!$CI->db->table_exists($hrCommTbl)) {
            $CI->db->query('CREATE TABLE `' . $hrCommTbl . '` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `salary_form_id` int(11) unsigned NOT NULL,
  `goal_id` int(11) NOT NULL,
  `goal_period_id` int(11) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `attribution_mode` varchar(20) NOT NULL DEFAULT \'individual\',
  `gross_invoiced_ht` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit_notes_ht` decimal(15,2) NOT NULL DEFAULT 0.00,
  `eligible_invoiced_ht` decimal(15,2) NOT NULL DEFAULT 0.00,
  `invoice_count` int(11) NOT NULL DEFAULT 0,
  `credit_note_count` int(11) NOT NULL DEFAULT 0,
  `goal_target_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `achievement_percent` decimal(7,2) NOT NULL DEFAULT 0.00,
  `percentage_rate` decimal(7,4) NOT NULL DEFAULT 0.0000,
  `commission_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT \'calculated\',
  `breakdown_json` longtext DEFAULT NULL,
  `calculated_at` datetime NOT NULL,
  `calculated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_staff_goal_period_salary` (`staff_id`, `salary_form_id`, `goal_id`, `goal_period_id`),
  KEY `idx_comm_staff_period` (`staff_id`, `period_start`, `period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
        }
    }
}
