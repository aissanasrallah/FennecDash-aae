<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_110 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        $pfx = db_prefix();

        // Orders dossier (one per client order / one vehicle order)
        if (!$CI->db->table_exists($pfx . 'aae_orders')) {
            $CI->db->query('CREATE TABLE `' . $pfx . 'aae_orders` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `client_id` int(11) NOT NULL DEFAULT 0,
                `vehicle_id` int(11) NOT NULL DEFAULT 0,
                `vehicle_source` varchar(20) NOT NULL DEFAULT \'cars\',
                `supplier_name` varchar(200) DEFAULT NULL,
                `supplier_reference` varchar(100) DEFAULT NULL,
                `stage` varchar(40) NOT NULL DEFAULT \'nouveau_contact\',
                `amount_client` decimal(15,2) NOT NULL DEFAULT 0.00,
                `amount_paid_client` decimal(15,2) NOT NULL DEFAULT 0.00,
                `amount_net_received` decimal(15,2) NOT NULL DEFAULT 0.00,
                `amount_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
                `amount_supplier` decimal(15,2) NOT NULL DEFAULT 0.00,
                `amount_paid_supplier` decimal(15,2) NOT NULL DEFAULT 0.00,
                `amount_supplier_due` decimal(15,2) NOT NULL DEFAULT 0.00,
                `margin` decimal(15,2) NOT NULL DEFAULT 0.00,
                `fx_rate_usd_eur` decimal(10,4) DEFAULT NULL,
                `fx_rate_eur_dzd` decimal(10,4) DEFAULT NULL,
                `estimate_id` int(11) NOT NULL DEFAULT 0,
                `invoice_id` int(11) NOT NULL DEFAULT 0,
                `contract_id` int(11) NOT NULL DEFAULT 0,
                `shipping_form_id` int(11) NOT NULL DEFAULT 0,
                `customs_form_id` int(11) NOT NULL DEFAULT 0,
                `transitaire_notice_id` int(11) NOT NULL DEFAULT 0,
                `delivery_note_id` int(11) NOT NULL DEFAULT 0,
                `temporary_doc_id` int(11) NOT NULL DEFAULT 0,
                `container_number` varchar(60) DEFAULT NULL,
                `bl_number` varchar(60) DEFAULT NULL,
                `tracking_link` varchar(500) DEFAULT NULL,
                `delivery_method` varchar(20) DEFAULT NULL,
                `extras` text DEFAULT NULL,
                `extras_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
                `final_papers_received` tinyint(1) NOT NULL DEFAULT 0,
                `final_invoice_id` int(11) NOT NULL DEFAULT 0,
                `final_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
                `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                `notes` text DEFAULT NULL,
                `source` varchar(20) NOT NULL DEFAULT \'manual\',
                `addedfrom` int(11) NOT NULL DEFAULT 0,
                `updatedby` int(11) NOT NULL DEFAULT 0,
                `datecreated` datetime NOT NULL,
                `dateupdated` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `client_id` (`client_id`),
                KEY `vehicle_id` (`vehicle_id`),
                KEY `stage` (`stage`),
                KEY `estimate_id` (`estimate_id`),
                KEY `invoice_id` (`invoice_id`),
                KEY `contract_id` (`contract_id`),
                KEY `datecreated` (`datecreated`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        }

        // Order vehicles (1..n per order, chassis/VIN tracked)
        if (!$CI->db->table_exists($pfx . 'aae_order_vehicles')) {
            $CI->db->query('CREATE TABLE `' . $pfx . 'aae_order_vehicles` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL DEFAULT 0,
                `vehicle_id` int(11) NOT NULL DEFAULT 0,
                `chassis_vin` varchar(100) DEFAULT NULL,
                `color` varchar(100) DEFAULT NULL,
                `cost_usd` decimal(15,2) NOT NULL DEFAULT 0.00,
                `cost_eur` decimal(15,2) NOT NULL DEFAULT 0.00,
                `price_client_eur` decimal(15,2) NOT NULL DEFAULT 0.00,
                `price_client_dzd` decimal(15,2) NOT NULL DEFAULT 0.00,
                `quantity` int(11) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`),
                KEY `order_id` (`order_id`),
                KEY `vehicle_id` (`vehicle_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        }

        // Typed document checklist per order
        if (!$CI->db->table_exists($pfx . 'aae_order_documents')) {
            $CI->db->query('CREATE TABLE `' . $pfx . 'aae_order_documents` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL DEFAULT 0,
                `doc_type` varchar(60) NOT NULL DEFAULT \'contract\',
                `label` varchar(200) DEFAULT NULL,
                `file_id` int(11) NOT NULL DEFAULT 0,
                `is_required` tinyint(1) NOT NULL DEFAULT 1,
                `received_at` datetime DEFAULT NULL,
                `sent_at` datetime DEFAULT NULL,
                `notes` text DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `order_id` (`order_id`),
                KEY `doc_type` (`doc_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        }

        // Payments (client in / supplier out / transitaire / carrier / refund)
        if (!$CI->db->table_exists($pfx . 'aae_order_payments')) {
            $CI->db->query('CREATE TABLE `' . $pfx . 'aae_order_payments` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL DEFAULT 0,
                `payment_type` enum(\'client_in\',\'supplier_out\',\'transitaire\',\'carrier\',\'refund\') NOT NULL DEFAULT \'client_in\',
                `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                `currency` varchar(8) NOT NULL DEFAULT \'EUR\',
                `proof_file_id` int(11) NOT NULL DEFAULT 0,
                `reference` varchar(100) DEFAULT NULL,
                `related_invoice_id` int(11) NOT NULL DEFAULT 0,
                `datepaid` datetime NOT NULL,
                `addedfrom` int(11) NOT NULL DEFAULT 0,
                `datecreated` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `order_id` (`order_id`),
                KEY `payment_type` (`payment_type`),
                KEY `related_invoice_id` (`related_invoice_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        }

        // Timeline events
        if (!$CI->db->table_exists($pfx . 'aae_order_events')) {
            $CI->db->query('CREATE TABLE `' . $pfx . 'aae_order_events` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL DEFAULT 0,
                `event_type` varchar(60) NOT NULL DEFAULT \'stage_change\',
                `event_title` varchar(200) DEFAULT NULL,
                `event_description` text DEFAULT NULL,
                `metadata` longtext DEFAULT NULL,
                `addedfrom` int(11) NOT NULL DEFAULT 0,
                `datecreated` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `order_id` (`order_id`),
                KEY `event_type` (`event_type`),
                KEY `datecreated` (`datecreated`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        }
    }
}
