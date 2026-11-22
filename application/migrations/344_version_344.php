<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_344 extends CI_Migration
{
    public function up(): void
    {
        if (! $this->db->field_exists('customer_type', db_prefix() . 'clients')) {
            $this->dbforge->add_column('clients', [
                'customer_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                    'default'    => 'b2b',
                    'null'       => false,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->field_exists('customer_type', db_prefix() . 'clients')) {
            $this->dbforge->drop_column('clients', 'customer_type');
        }
    }
}
