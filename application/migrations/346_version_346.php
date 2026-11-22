<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Agent API hardening: idempotency store, token rotation lineage and
 * agent-aware audit columns.
 *
 * Additive only. Existing Agent API tables keep their data; new columns are
 * nullable and new indexes are added only when missing.
 */
class Migration_Version_346 extends CI_Migration
{
    public function up(): void
    {
        $CI = &get_instance();
        $CI->load->library('api_schema');
        $CI->api_schema->install();
    }

    public function down(): void
    {
        $CI = &get_instance();
        $CI->load->library('api_schema');

        $this->dbforge->drop_table('api_idempotency', true);

        $columns = [
            'api_audit_log' => ['request_id', 'agent_session', 'agent'],
            'api_tokens'    => ['rotated_from_id'],
        ];

        foreach ($columns as $table => $names) {
            $prefixed = $this->db->dbprefix($table);

            foreach ($names as $column) {
                if ($CI->api_schema->columnExists($prefixed, $column)) {
                    $this->db->query('ALTER TABLE `' . $prefixed . '` DROP COLUMN `' . $column . '`');
                }
            }
        }
    }
}
