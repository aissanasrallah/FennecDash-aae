<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_Catalogue extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        $pfx = db_prefix();

        // Catalogue table - pure vehicle model/pricing reference data
        if (!$CI->db->table_exists($pfx . 'aae_catalogue')) {
            $CI->db->query('CREATE TABLE `' . $pfx . 'aae_catalogue` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `brand` varchar(100) NOT NULL,
                `model` varchar(191) NOT NULL,
                `engine` varchar(100) DEFAULT NULL,
                `model_year` smallint(4) NOT NULL DEFAULT 0,
                `color` varchar(150) DEFAULT NULL,
                `doors` varchar(20) DEFAULT NULL,
                `transmission` varchar(20) DEFAULT NULL,
                `fuel` varchar(20) DEFAULT NULL,
                -- Cost structure (USD)
                `fob_price` decimal(15,2) NOT NULL DEFAULT 0.00,
                `doc_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
                `vehicle_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
                `delivery_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
                `total_usd` decimal(15,2) NOT NULL DEFAULT 0.00,
                -- Selling prices (EUR/DZD)
                `price_eur` decimal(15,2) NOT NULL DEFAULT 0.00,
                `commission` decimal(15,2) NOT NULL DEFAULT 0.00,
                `total_ttc` decimal(15,2) NOT NULL DEFAULT 0.00,
                `price_dzd` decimal(15,2) NOT NULL DEFAULT 0.00,
                `price_millions` decimal(15,3) NOT NULL DEFAULT 0.000,
                -- Customs
                `customs_estimate` decimal(15,2) NOT NULL DEFAULT 0.00,
                -- Metadata
                `link` varchar(500) DEFAULT NULL,
                `notes` text DEFAULT NULL,
                `import_key` char(40) DEFAULT NULL,
                `source` varchar(20) NOT NULL DEFAULT \'manual\',
                `addedfrom` int(11) NOT NULL DEFAULT 0,
                `updatedby` int(11) NOT NULL DEFAULT 0,
                `datecreated` datetime NOT NULL,
                `dateupdated` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_model` (`brand`, `model`, `model_year`, `engine`),
                KEY `brand` (`brand`),
                KEY `model` (`model`),
                KEY `import_key` (`import_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        }

        // Add catalogue_id to orders table if not exists
        if ($CI->db->table_exists($pfx . 'aae_orders')) {
            $fields = $CI->db->list_fields($pfx . 'aae_orders');
            if (!in_array('catalogue_id', $fields)) {
                $CI->db->query('ALTER TABLE `' . $pfx . 'aae_orders` 
                    ADD COLUMN `catalogue_id` int(11) NOT NULL DEFAULT 0 AFTER `vehicle_id`,
                    ADD KEY `catalogue_id` (`catalogue_id`)');
            }
        }

        // Add catalogue_id to order_vehicles table if not exists
        if ($CI->db->table_exists($pfx . 'aae_order_vehicles')) {
            $fields = $CI->db->list_fields($pfx . 'aae_order_vehicles');
            if (!in_array('catalogue_id', $fields)) {
                $CI->db->query('ALTER TABLE `' . $pfx . 'aae_order_vehicles` 
                    ADD COLUMN `catalogue_id` int(11) NOT NULL DEFAULT 0 AFTER `vehicle_id`,
                    ADD KEY `catalogue_id` (`catalogue_id`)');
            }
        }

        // Migrate unique catalogue entries from tblaae_cars to tblaae_catalogue
        // Only migrate one per unique brand/model/year/engine combination
        $CI->db->query('
            INSERT IGNORE INTO `' . $pfx . 'aae_catalogue` (
                `brand`, `model`, `engine`, `model_year`, `color`,
                `fob_price`, `doc_fee`, `vehicle_fee`, `delivery_fee`, `total_usd`,
                `price_eur`, `commission`, `total_ttc`, `price_dzd`, `price_millions`,
                `customs_estimate`, `link`, `notes`, `import_key`, `source`,
                `addedfrom`, `updatedby`, `datecreated`, `dateupdated`
            )
            SELECT DISTINCT
                `brand`, `model`, `engine`, `model_year`, `color`,
                `fob_price`, `doc_fee`, `vehicle_fee`, `delivery_fee`, `total_usd`,
                `price_eur`, `commission`, `total_ttc`, `price_dzd`, `price_millions`,
                `customs_estimate`, `link`, `notes`, `import_key`, `source`,
                `addedfrom`, `updatedby`, `datecreated`, `dateupdated
            FROM `' . $pfx . 'aae_cars`
            WHERE `brand` != \'\' AND `model` != \'\'
        ');
    }

    public function down()
    {
        $CI = &get_instance();
        $pfx = db_prefix();

        // Remove catalogue_id columns
        if ($CI->db->table_exists($pfx . 'aae_orders')) {
            $fields = $CI->db->list_fields($pfx . 'aae_orders');
            if (in_array('catalogue_id', $fields)) {
                $CI->db->query('ALTER TABLE `' . $pfx . 'aae_orders` DROP COLUMN `catalogue_id`');
            }
        }

        if ($CI->db->table_exists($pfx . 'aae_order_vehicles')) {
            $fields = $CI->db->list_fields($pfx . 'aae_order_vehicles');
            if (in_array('catalogue_id', $fields)) {
                $CI->db->query('ALTER TABLE `' . $pfx . 'aae_order_vehicles` DROP COLUMN `catalogue_id`');
            }
        }

        // Drop catalogue table
        if ($CI->db->table_exists($pfx . 'aae_catalogue')) {
            $CI->db->query('DROP TABLE `' . $pfx . 'aae_catalogue`');
        }
    }
}