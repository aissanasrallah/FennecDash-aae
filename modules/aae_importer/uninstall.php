<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Desinstallation du module AAE Import Manager
 * Supprime les tables du module et les options associees.
 * Les devis Perfex crees depuis le catalogue ne sont PAS supprimes.
 */

function aae_importer_run_uninstall()
{
    $CI     = &get_instance();
    $CI->load->dbforge();
    $prefix = db_prefix();

    // NOTE: nom SANS prefixe pour dbforge (CI prepend deja db_prefix()).
    foreach (['aae_operation_status', 'aae_operations', 'aae_vehicles'] as $table) {
        if ($CI->db->table_exists($prefix . $table)) {
            $CI->dbforge->drop_table($table, true);
        }
    }

    delete_option('aae_importer_eur_rate');
    delete_option('aae_importer_eur_fee_percent');
    delete_option('aae_importer_dzd_rate');
    delete_option('aae_importer_default_commission');
    delete_option('aae_importer_quote_validity_days');
    delete_option('aae_importer_client_portal');
    delete_option('aae_importer_version');

    log_activity('AAE Import Manager désinstallé');
}
