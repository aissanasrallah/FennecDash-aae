<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Schema installer for the FennecDash Agent API.
 *
 * The tables are additive: nothing in the existing CRM schema is altered.
 * The same installer is used by the migration and by the one-shot setup
 * controller so both paths always produce an identical schema.
 */
class Api_schema
{
    /** @var CI_Controller */
    private $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Create every Agent API table that does not exist yet and add columns
     * introduced by later Agent API releases.
     *
     * @return array{created: string[], existing: string[], columns_added: string[]}
     */
    public function install()
    {
        $created  = [];
        $existing = [];

        foreach ($this->tables() as $name => $sql) {
            $table = $this->CI->db->dbprefix($name);
            if ($this->tableExists($table)) {
                $existing[] = $table;
                continue;
            }

            $this->CI->db->query(sprintf($sql, $table));
            $created[] = $table;
        }

        $columnsAdded = $this->installColumns();
        $this->installIndexes();

        $this->CI->load->library('api_auth');

        return [
            'created'        => $created,
            'existing'       => $existing,
            'columns_added'  => $columnsAdded,
        ];
    }

    /**
     * Additive column upgrades. Every statement is guarded by an
     * information_schema lookup, so running install() repeatedly is safe.
     *
     * @return string[]
     */
    public function installColumns()
    {
        $added = [];

        $definitions = [
            'api_tokens' => [
                'rotated_from_id' => 'INT UNSIGNED NULL AFTER `revoked_at`',
            ],
            'api_audit_log' => [
                'agent'         => 'VARCHAR(120) NULL AFTER `user_agent`',
                'agent_session' => 'VARCHAR(120) NULL AFTER `agent`',
                'request_id'    => 'VARCHAR(64) NULL AFTER `agent_session`',
            ],
        ];

        foreach ($definitions as $name => $columns) {
            $table = $this->CI->db->dbprefix($name);

            if (! $this->tableExists($table)) {
                continue;
            }

            foreach ($columns as $column => $definition) {
                if ($this->columnExists($table, $column)) {
                    continue;
                }

                $this->CI->db->query(
                    'ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition
                );

                $added[] = $table . '.' . $column;
            }
        }

        return $added;
    }

    /**
     * Secondary indexes used by pruning and filtering. Guarded like columns.
     */
    public function installIndexes()
    {
        $indexes = [
            'api_rate_limits'  => ['window_start' => 'KEY `window_start` (`window_start`)'],
            'api_idempotency'  => ['updated_at' => 'KEY `updated_at` (`updated_at`)'],
            'api_audit_log'    => ['agent' => 'KEY `agent` (`agent`)'],
        ];

        foreach ($indexes as $name => $definitions) {
            $table = $this->CI->db->dbprefix($name);

            if (! $this->tableExists($table)) {
                continue;
            }

            foreach ($definitions as $index => $definition) {
                if ($this->indexExists($table, $index)) {
                    continue;
                }

                $this->CI->db->query('ALTER TABLE `' . $table . '` ADD ' . $definition);
            }
        }
    }

    public function tableExists($table)
    {
        $query = $this->CI->db->query(
            'SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$table]
        );

        return $query && (int) $query->row()->total > 0;
    }

    public function columnExists($table, $column)
    {
        $query = $this->CI->db->query(
            'SELECT COUNT(*) AS total FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$table, $column]
        );

        return $query && (int) $query->row()->total > 0;
    }

    public function indexExists($table, $index)
    {
        $query = $this->CI->db->query(
            'SELECT COUNT(*) AS total FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );

        return $query && (int) $query->row()->total > 0;
    }

    /**
     * @return array<string, string> table name (unprefixed) => CREATE TABLE template
     */
    public function tables()
    {
        return [
            'api_tokens' => 'CREATE TABLE IF NOT EXISTS `%s` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `staff_id` INT NOT NULL,
                `label` VARCHAR(120) NOT NULL,
                `token_prefix` VARCHAR(24) NOT NULL,
                `token_hash` CHAR(64) NOT NULL,
                `risk_max` ENUM(\'read\',\'write\',\'critical\') NOT NULL DEFAULT \'read\',
                `scopes` TEXT NULL,
                `client` VARCHAR(60) NULL,
                `created_at` DATETIME NOT NULL,
                `last_used_at` DATETIME NULL,
                `expires_at` DATETIME NULL,
                `revoked_at` DATETIME NULL,
                `rotated_from_id` INT UNSIGNED NULL,
                `request_count` INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `token_hash` (`token_hash`),
                KEY `staff_id` (`staff_id`),
                KEY `revoked_at` (`revoked_at`),
                KEY `rotated_from_id` (`rotated_from_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

            'api_audit_log' => 'CREATE TABLE IF NOT EXISTS `%s` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `token_id` INT UNSIGNED NULL,
                `staff_id` INT NULL,
                `action` VARCHAR(120) NOT NULL,
                `risk` ENUM(\'read\',\'write\',\'critical\') NOT NULL DEFAULT \'read\',
                `http_method` VARCHAR(10) NOT NULL,
                `path` VARCHAR(255) NOT NULL,
                `status_code` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                `ip` VARCHAR(64) NULL,
                `user_agent` VARCHAR(255) NULL,
                `agent` VARCHAR(120) NULL,
                `agent_session` VARCHAR(120) NULL,
                `request_id` VARCHAR(64) NULL,
                `request_summary` TEXT NULL,
                `response_ms` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `token_id` (`token_id`),
                KEY `created_at` (`created_at`),
                KEY `action` (`action`),
                KEY `status_code` (`status_code`),
                KEY `agent` (`agent`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

            'api_rate_limits' => 'CREATE TABLE IF NOT EXISTS `%s` (
                `token_id` INT UNSIGNED NOT NULL,
                `window_start` INT UNSIGNED NOT NULL,
                `hits` INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`token_id`, `window_start`),
                KEY `window_start` (`window_start`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

            'api_approvals' => 'CREATE TABLE IF NOT EXISTS `%s` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `token_id` INT UNSIGNED NOT NULL,
                `staff_id` INT NOT NULL,
                `action` VARCHAR(120) NOT NULL,
                `approval_hash` CHAR(64) NOT NULL,
                `payload_hash` CHAR(64) NOT NULL,
                `status` ENUM(\'pending\',\'used\',\'revoked\',\'expired\') NOT NULL DEFAULT \'pending\',
                `created_at` DATETIME NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `used_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `approval_hash` (`approval_hash`),
                KEY `token_id` (`token_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

            'api_idempotency' => 'CREATE TABLE IF NOT EXISTS `%s` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `token_id` INT UNSIGNED NOT NULL,
                `idem_key` VARCHAR(128) NOT NULL,
                `request_hash` CHAR(64) NOT NULL,
                `status` ENUM(\'pending\',\'completed\') NOT NULL DEFAULT \'pending\',
                `response_code` SMALLINT UNSIGNED NULL,
                `response_body` MEDIUMTEXT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `token_key` (`token_id`, `idem_key`),
                KEY `status` (`status`),
                KEY `updated_at` (`updated_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        ];
    }
}
