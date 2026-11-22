<?php

defined('BASEPATH') or exit('No direct script access allowed');

$charset   = $CI->db->char_set;
$collation = $CI->db->dbcollat;

if (! $CI->db->table_exists(db_prefix() . 'aae_cars')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'aae_cars` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `brand` varchar(100) NOT NULL,
        `model` varchar(191) NOT NULL,
        `engine` varchar(100) DEFAULT NULL,
        `model_year` smallint(4) NOT NULL DEFAULT 0,
        `color` varchar(150) DEFAULT NULL,
        `fob_price` decimal(15,2) NOT NULL DEFAULT 0.00,
        `doc_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
        `vehicle_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
        `delivery_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
        `total_usd` decimal(15,2) NOT NULL DEFAULT 0.00,
        `price_eur` decimal(15,2) NOT NULL DEFAULT 0.00,
        `commission` decimal(15,2) NOT NULL DEFAULT 0.00,
        `total_ttc` decimal(15,2) NOT NULL DEFAULT 0.00,
        `price_dzd` decimal(15,2) NOT NULL DEFAULT 0.00,
        `price_millions` decimal(15,3) NOT NULL DEFAULT 0.000,
        `customs_estimate` decimal(15,2) NOT NULL DEFAULT 0.00,
        `link` varchar(500) DEFAULT NULL,
        `quantity` int(11) NOT NULL DEFAULT 0,
        `status` varchar(20) NOT NULL DEFAULT \'available\',
        `destination` varchar(20) NOT NULL DEFAULT \'algeria\',
        `client_id` int(11) NOT NULL DEFAULT 0,
        `invoice_id` int(11) NOT NULL DEFAULT 0,
        `notes` text DEFAULT NULL,
        `import_key` char(40) DEFAULT NULL,
        `source` varchar(20) NOT NULL DEFAULT \'manual\',
        `addedfrom` int(11) NOT NULL DEFAULT 0,
        `updatedby` int(11) NOT NULL DEFAULT 0,
        `datecreated` datetime NOT NULL,
        `dateupdated` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `import_key` (`import_key`),
        KEY `client_id` (`client_id`),
        KEY `invoice_id` (`invoice_id`),
        KEY `status` (`status`),
        KEY `destination` (`destination`),
        KEY `brand` (`brand`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ' COLLATE=' . $collation . ';');
}

if (! $CI->db->table_exists(db_prefix() . 'aae_imports')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'aae_imports` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `filename` varchar(255) NOT NULL,
        `total_rows` int(11) NOT NULL DEFAULT 0,
        `inserted` int(11) NOT NULL DEFAULT 0,
        `updated` int(11) NOT NULL DEFAULT 0,
        `skipped` int(11) NOT NULL DEFAULT 0,
        `failed` int(11) NOT NULL DEFAULT 0,
        `status` varchar(20) NOT NULL DEFAULT \'completed\',
        `messages` longtext DEFAULT NULL,
        `staff_id` int(11) NOT NULL DEFAULT 0,
        `datecreated` datetime NOT NULL,
        PRIMARY KEY (`id`),
        KEY `staff_id` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $charset . ' COLLATE=' . $collation . ';');
}

/**
 * Upgrade the orders table for installs created before the catalogue
 * became the default vehicle source.
 */
if ($CI->db->table_exists(db_prefix() . 'aae_orders') && ! $CI->db->field_exists('vehicle_source', db_prefix() . 'aae_orders')) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'aae_orders` ADD `vehicle_source` varchar(20) NOT NULL DEFAULT \'cars\' AFTER `vehicle_id`;');
}

/**
 * Default options.
 */
add_option('aae_company_name', 'Algerie Auto Export');
add_option('aae_eur_rate', '0.892');
add_option('aae_eur_markup', '0.8');
add_option('aae_dzd_rate', '282');
add_option('aae_default_doc_fee', '100');
add_option('aae_default_vehicle_fee', '0');
add_option('aae_default_delivery_fee', '1400');
add_option('aae_default_commission', '850');
