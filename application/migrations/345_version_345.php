<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Agent API tables (tokens, audit trail, rate limiting, approvals).
 *
 * Additive only: no existing FennecDash table is modified.
 */
class Migration_Version_345 extends CI_Migration
{
    public function up(): void
    {
        $CI = &get_instance();
        $CI->load->library('api_schema');
        $CI->api_schema->install();
    }

    public function down(): void
    {
        foreach (['api_approvals', 'api_rate_limits', 'api_audit_log', 'api_tokens'] as $table) {
            $this->dbforge->drop_table($table, true);
        }
    }
}
