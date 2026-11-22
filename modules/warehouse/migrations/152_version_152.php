<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_152 extends App_module_migration
{
    public function up()
    {
        $CI    = &get_instance();
        $table = db_prefix() . 'ware_unit_type';

        if ($CI->db->table_exists($table)) {
            if (!$CI->db->field_exists('base_unit_code', $table)) {
                $CI->db->query('ALTER TABLE `' . $table . '` ADD COLUMN `base_unit_code` VARCHAR(32) NULL AFTER `unit_symbol`;');
            }
            if (!$CI->db->field_exists('conversion_qty', $table)) {
                $CI->db->query('ALTER TABLE `' . $table . '` ADD COLUMN `conversion_qty` DECIMAL(12,4) NULL AFTER `base_unit_code`;');
            }
            if (!$CI->db->field_exists('legacy_unit_code', $table)) {
                $CI->db->query('ALTER TABLE `' . $table . '` ADD COLUMN `legacy_unit_code` VARCHAR(50) NULL AFTER `conversion_qty`;');
            }
            if (!$CI->db->field_exists('is_ambiguous', $table)) {
                $CI->db->query('ALTER TABLE `' . $table . '` ADD COLUMN `is_ambiguous` TINYINT(1) NOT NULL DEFAULT 0 AFTER `legacy_unit_code`;');
            }
        }
    }
}
