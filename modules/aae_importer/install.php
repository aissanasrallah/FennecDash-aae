<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Installation / mise a jour schema du module AAE Import Manager
 */

function aae_importer_ensure_version()
{
    if (get_option('aae_importer_version') != AAE_IMPORTER_VERSION) {
        update_option('aae_importer_version', AAE_IMPORTER_VERSION);
    }
}

function aae_importer_run_install($fresh_install = false)
{
    $CI = &get_instance();
    $CI->load->dbforge();
    $prefix = db_prefix();

    /**
     * Catalogue des vehicules (données du fichier CATALOGUE VOITURE - CHINOISES)
     */
    if (!$CI->db->table_exists($prefix . 'aae_vehicles')) {
        $CI->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'marque' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'modele' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'moteur' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'annee' => [
                'type'    => 'INT',
                'null'    => true,
            ],
            'couleur' => [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => true,
            ],
            'fob_usd' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'doc_translation' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'vehicle_translation' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'shipping_usd' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'total_usd' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'eur_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
                'null' => true,
            ],
            'eur_fee_percent' => [
                'type' => 'DECIMAL',
                'constraint' => '8,3',
                'null' => true,
            ],
            'price_eur' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'commission_eur' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'total_ttc_eur' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'null' => true,
            ],
            'dzd_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'ttc_dzd' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
            ],
            'customs_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '6,2',
                'null' => true,
            ],
            'lien' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
                'default'    => 'disponible',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'INT',
                'null' => true,
            ],
            'date_created' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'date_updated' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $CI->dbforge->add_key('id', true);
        // NOTE: passer les noms SANS prefixe a dbforge : CI prepends deja db_prefix()
        // ('dbprefix' => db_prefix() dans application/config/database.php).
        // Aussi, brancher un create_table(db_prefix().'x') produirait 'tbltblx'.
        $CI->dbforge->create_table('aae_vehicles', true, ['ENGINE' => 'InnoDB']);
    } else {
        // Upgrade path : garantit les colonnes manquantes si une version anterieure existe
        $columns = [
            'lien'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'status'  => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false, 'default' => 'disponible'],
            'notes'   => ['type' => 'TEXT', 'null' => true],
            'eur_rate' => ['type' => 'DECIMAL', 'constraint' => '10,4', 'null' => true],
            'eur_fee_percent' => ['type' => 'DECIMAL', 'constraint' => '8,3', 'null' => true],
            'customs_rate' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
        ];
        foreach ($columns as $name => $field) {
            if (!$CI->db->field_exists($name, $prefix . 'aae_vehicles')) {
                $CI->dbforge->add_column('aae_vehicles', [$name => $field]);
            }
        }
    }

    /**
     * Operations d'import (une ligne = un vehicule reel / une commande)
     */
    if (!$CI->db->table_exists($prefix . 'aae_operations')) {
        $CI->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ref' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
            ],
            'vehicle_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'clientid' => [
                'type' => 'INT',
                'null' => true,
            ],
            'staff_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
                'default'    => 'demande',
            ],
            'qty' => [
                'type'  => 'INT',
                'null'  => false,
                'default' => 1,
            ],
            'order_date' => ['type' => 'DATE', 'null' => true],
            'production_date' => ['type' => 'DATE', 'null' => true],
            'departure_date' => ['type' => 'DATE', 'null' => true],
            'arrival_date' => ['type' => 'DATE', 'null' => true],
            'customs_date' => ['type' => 'DATE', 'null' => true],
            'delivery_date' => ['type' => 'DATE', 'null' => true],
            'eta_date' => ['type' => 'DATE', 'null' => true],
            'fob_paid' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'shipping_paid' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'translation_paid' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'commission_paid' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'customs_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'other_costs' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'total_cost' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'sale_price' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'sale_currency' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'margin' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'estimate_id' => ['type' => 'INT', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'date_created' => ['type' => 'DATETIME', 'null' => true],
            'date_updated' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $CI->dbforge->add_key('id', true);
        $CI->dbforge->create_table('aae_operations', true, ['ENGINE' => 'InnoDB']);
    } else {
        $columns = [
            'sale_currency' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'margin' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'estimate_id' => ['type' => 'INT', 'null' => true],
        ];
        foreach ($columns as $name => $field) {
            if (!$CI->db->field_exists($name, $prefix . 'aae_operations')) {
                $CI->dbforge->add_column('aae_operations', [$name => $field]);
            }
        }
    }

    /**
     * Historique des statuts des operations
     */
    if (!$CI->db->table_exists($prefix . 'aae_operation_status')) {
        $CI->dbforge->add_field([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'operation_id' => [
                'type' => 'INT',
                'null' => false,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
            ],
            'note' => ['type' => 'TEXT', 'null' => true],
            'staff_id' => ['type' => 'INT', 'null' => true],
            'date_added' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $CI->dbforge->add_key('id', true);
        $CI->dbforge->create_table('aae_operation_status', true, ['ENGINE' => 'InnoDB']);
    }

    // Options par defaut
    add_option('aae_importer_eur_rate', '0.892');          // 1 USD -> EUR
    add_option('aae_importer_eur_fee_percent', '0.8');     // majoration % avant EUR ("+0,8 %")
    add_option('aae_importer_dzd_rate', '282');            // taux DZD par defaut (282 ou 275)
    add_option('aae_importer_default_commission', '850');  // commission EUR par defaut
    add_option('aae_importer_quote_validity_days', '30');  // duree de validite devis
    add_option('aae_importer_client_portal', '1');         // afficher le catalogue aux clients

    aae_importer_ensure_version();

    if ($fresh_install) {
        log_activity('AAE Import Manager installé (version ' . AAE_IMPORTER_VERSION . ')');
    }
}
