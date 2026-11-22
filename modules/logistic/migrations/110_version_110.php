<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_110 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // V110: Hybrid Fleet (tbllg_vehicles), External Carriers extensions, Transport Costs, & 17 lifecycle statuses
        if (!$CI->db->table_exists(db_prefix() . 'lg_vehicles')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "lg_vehicles` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `vehicle_code` varchar(50) NOT NULL,
          `registration_plate` varchar(100) DEFAULT NULL,
          `vehicle_type` varchar(80) NOT NULL DEFAULT 'Van',
          `brand_model` varchar(100) DEFAULT NULL,
          `capacity_kg` decimal(12,2) DEFAULT NULL,
          `fuel_type` varchar(40) DEFAULT NULL,
          `status` varchar(30) NOT NULL DEFAULT 'available',
          `office_group_id` int(11) DEFAULT NULL,
          `assigned_driver_id` int(11) DEFAULT NULL,
          `insurance_expiry` date DEFAULT NULL,
          `technical_inspection_expiry` date DEFAULT NULL,
          `last_maintenance_date` date DEFAULT NULL,
          `maintenance_notes` text DEFAULT NULL,
          `created_at` datetime DEFAULT NULL,
          `created_by` int(11) DEFAULT NULL,
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_lg_vehicles_code` (`vehicle_code`),
          KEY `idx_lg_vehicles_status` (`status`),
          KEY `idx_lg_vehicles_office` (`office_group_id`),
          KEY `idx_lg_vehicles_driver` (`assigned_driver_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
        }

        if (!$CI->db->table_exists(db_prefix() . 'lg_transport_costs')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "lg_transport_costs` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `shipping_id` int(11) DEFAULT NULL,
          `transport_type` varchar(20) NOT NULL DEFAULT 'internal',
          `cost_category` varchar(40) NOT NULL,
          `driver_id` int(11) DEFAULT NULL,
          `vehicle_id` int(11) DEFAULT NULL,
          `carrier_id` int(11) DEFAULT NULL,
          `office_group_id` int(11) DEFAULT NULL,
          `cost_date` date NOT NULL,
          `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
          `reference_no` varchar(120) DEFAULT NULL,
          `payment_status` varchar(30) NOT NULL DEFAULT 'unpaid',
          `notes` text DEFAULT NULL,
          `created_at` datetime DEFAULT NULL,
          `created_by` int(11) DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_lg_costs_type` (`transport_type`, `cost_category`),
          KEY `idx_lg_costs_shipping` (`shipping_id`),
          KEY `idx_lg_costs_driver` (`driver_id`),
          KEY `idx_lg_costs_vehicle` (`vehicle_id`),
          KEY `idx_lg_costs_carrier` (`carrier_id`),
          KEY `idx_lg_costs_date` (`cost_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
        }

        $lgCompaniesTbl = db_prefix() . 'lg_shipping_companies';
        if ($CI->db->table_exists($lgCompaniesTbl)) {
            $companyCols = [
                'contact_name'       => 'VARCHAR(150) DEFAULT NULL',
                'email'              => 'VARCHAR(150) DEFAULT NULL',
                'service_areas'      => 'TEXT DEFAULT NULL',
                'pricing_model'      => "VARCHAR(40) NOT NULL DEFAULT 'fixed'",
                'default_price'      => 'DECIMAL(15,2) DEFAULT NULL',
                'payment_terms'      => 'VARCHAR(150) DEFAULT NULL',
                'invoice_ref_prefix' => 'VARCHAR(80) DEFAULT NULL',
                'active'             => 'TINYINT(1) NOT NULL DEFAULT 1',
                'notes'              => 'TEXT DEFAULT NULL',
            ];
            foreach ($companyCols as $col => $def) {
                if (!$CI->db->field_exists($col, $lgCompaniesTbl)) {
                    $CI->db->query('ALTER TABLE `' . $lgCompaniesTbl . '` ADD COLUMN `' . $col . '` ' . $def . ';');
                }
            }
        }

        $lgShipTbl = db_prefix() . 'lg_shippings';
        if ($CI->db->table_exists($lgShipTbl)) {
            $shipCols = [
                'transport_type'          => "VARCHAR(20) NOT NULL DEFAULT 'internal'",
                'vehicle_id'              => 'INT(11) DEFAULT NULL',
                'planned_delivery_date'   => 'DATETIME DEFAULT NULL',
                'actual_pickup_at'        => 'DATETIME DEFAULT NULL',
                'actual_delivery_at'      => 'DATETIME DEFAULT NULL',
                'external_carrier_ref'    => 'VARCHAR(120) DEFAULT NULL',
                'external_agreed_price'   => 'DECIMAL(15,2) DEFAULT NULL',
                'external_driver_name'    => 'VARCHAR(150) DEFAULT NULL',
                'external_driver_phone'   => 'VARCHAR(50) DEFAULT NULL',
                'external_vehicle_plate'  => 'VARCHAR(80) DEFAULT NULL',
                'external_invoice_ref'    => 'VARCHAR(120) DEFAULT NULL',
                'external_payment_status' => "VARCHAR(30) NOT NULL DEFAULT 'unpaid'",
                'delivery_notes'          => 'TEXT DEFAULT NULL',
            ];
            foreach ($shipCols as $col => $def) {
                if (!$CI->db->field_exists($col, $lgShipTbl)) {
                    $CI->db->query('ALTER TABLE `' . $lgShipTbl . '` ADD COLUMN `' . $col . '` ' . $def . ';');
                }
            }
        }

        if (!function_exists('lg_create_default_style_and_states')) {
            $CI->load->helper('logistic/logistic');
        }
        if (function_exists('lg_create_default_style_and_states')) {
            lg_create_default_style_and_states();
        }
    }
}
