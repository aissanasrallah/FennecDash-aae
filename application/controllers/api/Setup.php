<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/Api_exception.php';

/**
 * One-shot installer for the Agent API.
 *
 * The CRM ships with migrations disabled, so the tables and the first token
 * can also be created through this controller. It is protected by
 * MCP_SETUP_KEY and disabled as soon as the key is removed from the
 * environment. CLI calls are trusted because they already require shell
 * access to the server.
 */
class Setup extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->database();
        $this->load->helper(['settings']);
        $this->output->set_content_type('application/json', 'utf-8');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate');
        $this->output->set_header('X-Content-Type-Options: nosniff');
    }

    public function _remap($method, $params = [])
    {
        $status  = 200;
        $payload = null;

        try {
            switch ($method) {
                case 'index':
                case 'status':
                    $payload = $this->status();
                    break;
                case 'install':
                    $payload = $this->install();
                    break;
                case 'token':
                    $payload = $this->token();
                    break;
                case 'rotate':
                    $payload = $this->rotate();
                    break;
                case 'tokens':
                    $payload = $this->tokens();
                    break;
                case 'revoke':
                    $payload = $this->revoke();
                    break;
                default:
                    throw new Api_exception('unknown_endpoint', 'Unknown setup endpoint: ' . $method, 404);
            }

            $payload = ['ok' => true, 'data' => $payload];
        } catch (Api_exception $e) {
            $status  = $e->getStatus();
            $payload = ['ok' => false, 'error' => ['code' => $e->getErrorCode(), 'message' => $e->getMessage(), 'details' => $e->getDetails()]];
        } catch (Throwable $e) {
            $status  = 500;
            error_log('[fennedash-api:setup] ' . $e->getMessage());
            $payload = ['ok' => false, 'error' => ['code' => 'internal_error', 'message' => 'Setup failed. Check the server error log.']];
        }

        $this->output->set_status_header($status)
            ->set_output(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    // ---------------------------------------------------------------------

    private function status()
    {
        $this->guard();
        $this->load->library('api_schema');

        $tables = [];
        foreach (['api_tokens', 'api_audit_log', 'api_rate_limits', 'api_approvals', 'api_idempotency'] as $table) {
            $tables[$table] = $this->api_schema->tableExists($this->db->dbprefix($table));
        }

        $tokenCount = 0;
        if ($tables['api_tokens']) {
            $tokenCount = (int) $this->db->where('revoked_at IS NULL', null, false)
                ->count_all_results($this->db->dbprefix('api_tokens'));
        }

        return [
            'installed'      => ! in_array(false, $tables, true),
            'tables'         => $tables,
            'active_tokens'  => $tokenCount,
            'setup_key_set'  => (bool) getenv('MCP_SETUP_KEY'),
            'agent_api'      => '1.0.0',
            'php_version'    => PHP_VERSION,
            'application'    => get_option('companyname'),
            'capabilities'   => [
                'token_rotation' => $tables['api_tokens'],
                'idempotency'    => $tables['api_idempotency'],
                'agent_audit'    => $tables['api_audit_log'],
            ],
        ];
    }

    private function install()
    {
        $this->guard();
        $this->assertPostOrCli();

        $this->load->library('api_schema');
        $result = $this->api_schema->install();

        return [
            'installed'     => true,
            'created'       => $result['created'],
            'existing'      => $result['existing'],
            'columns_added' => $result['columns_added'] ?? [],
        ];
    }

    private function token()
    {
        $this->guard();
        $this->assertPostOrCli();

        $input = $this->jsonInput();

        $staffId = (int) ($input['staff_id'] ?? 0);
        $label   = trim((string) ($input['label'] ?? ''));
        $riskMax = (string) ($input['risk_max'] ?? 'read');
        $scopes  = $input['scopes'] ?? [];
        $days    = $input['expires_in_days'] ?? 0;
        $client  = isset($input['client']) ? trim((string) $input['client']) : null;

        if ($staffId <= 0) {
            throw new Api_exception('validation_failed', 'staff_id is required.', 422, ['field' => 'staff_id']);
        }

        if ($label === '' || mb_strlen($label) > 120) {
            throw new Api_exception('validation_failed', 'label is required and must be at most 120 characters.', 422, ['field' => 'label']);
        }

        if (! in_array($riskMax, ['read', 'write', 'critical'], true)) {
            throw new Api_exception('validation_failed', 'risk_max must be read, write or critical.', 422, ['field' => 'risk_max']);
        }

        if (! is_array($scopes)) {
            throw new Api_exception('validation_failed', 'scopes must be an array of feature names.', 422, ['field' => 'scopes']);
        }

        if (count($scopes) > 60) {
            throw new Api_exception('validation_failed', 'scopes may contain at most 60 entries.', 422, ['field' => 'scopes']);
        }

        $normalizedScopes = [];
        foreach ($scopes as $scope) {
            if (! is_string($scope) || trim($scope) === '' || mb_strlen(trim($scope)) > 60) {
                throw new Api_exception('validation_failed', 'Each scope must be a non-empty string of at most 60 characters.', 422, ['field' => 'scopes']);
            }
            $normalizedScopes[] = trim($scope);
        }
        $normalizedScopes = array_values(array_unique($normalizedScopes));

        if (! is_numeric($days) || (int) $days < 0 || (int) $days > 3650) {
            throw new Api_exception('validation_failed', 'expires_in_days must be an integer between 0 and 3650.', 422, ['field' => 'expires_in_days']);
        }
        $days = (int) $days;

        if ($client !== null && mb_strlen($client) > 60) {
            throw new Api_exception('validation_failed', 'client must be at most 60 characters.', 422, ['field' => 'client']);
        }

        $staff = $this->db->select('staffid, firstname, lastname, admin, active, is_not_staff')
            ->where('staffid', $staffId)
            ->get($this->db->dbprefix('staff'))
            ->row();

        if (! $staff || (int) $staff->active !== 1 || (int) $staff->is_not_staff === 1) {
            throw new Api_exception('validation_failed', 'No active staff member with id ' . $staffId . '.', 422);
        }

        $this->load->library('api_auth');

        $expiresAt = $days > 0 ? date('Y-m-d H:i:s', strtotime('+' . $days . ' days')) : null;

        $issued = $this->api_auth->issueToken($staffId, $label, $riskMax, $normalizedScopes, $expiresAt, $client);

        return [
            'token'      => $issued['token'],
            'id'         => $issued['id'],
            'prefix'     => $issued['prefix'],
            'staff'      => ['id' => (int) $staff->staffid, 'name' => trim($staff->firstname . ' ' . $staff->lastname)],
            'risk_max'   => $riskMax,
            'scopes'     => $normalizedScopes === [] ? null : $normalizedScopes,
            'client'     => $client,
            'expires_at' => $expiresAt,
            'warning'    => 'This token is shown only once. Store it in the MCP server environment, never in source control. Give every agent its own token.',
        ];
    }

    private function rotate()
    {
        $this->guard();
        $this->assertPostOrCli();

        $input  = $this->jsonInput();
        $id     = (int) ($input['id'] ?? 0);
        $label  = isset($input['label']) ? trim((string) $input['label']) : null;
        $client = isset($input['client']) ? trim((string) $input['client']) : null;

        if ($id <= 0) {
            throw new Api_exception('validation_failed', 'id is required.', 422, ['field' => 'id']);
        }

        if ($label !== null && ($label === '' || mb_strlen($label) > 120)) {
            throw new Api_exception('validation_failed', 'label must be 1-120 characters when provided.', 422, ['field' => 'label']);
        }

        if ($client !== null && mb_strlen($client) > 60) {
            throw new Api_exception('validation_failed', 'client must be at most 60 characters.', 422, ['field' => 'client']);
        }

        $hasExpiry = array_key_exists('expires_in_days', $input);
        $expiresAt = null;

        if ($hasExpiry) {
            $days = $input['expires_in_days'];

            if (! is_numeric($days) || (int) $days < 0 || (int) $days > 3650) {
                throw new Api_exception('validation_failed', 'expires_in_days must be an integer between 0 and 3650.', 422, ['field' => 'expires_in_days']);
            }

            $expiresAt = (int) $days > 0
                ? date('Y-m-d H:i:s', strtotime('+' . (int) $days . ' days'))
                : null;
        }

        $this->load->library('api_auth');

        // When expires_in_days is omitted the old expiry is inherited.
        $issued = $this->api_auth->rotateToken($id, $label, $hasExpiry ? $expiresAt : null, $client);

        return [
            'token'        => $issued['token'],
            'id'           => $issued['id'],
            'prefix'       => $issued['prefix'],
            'rotated_from' => $issued['rotated_from'],
            'expires_at'   => $hasExpiry ? $expiresAt : null,
            'warning'      => 'The old token is revoked. This replacement is shown only once. Update the agent configuration now.',
        ];
    }

    private function tokens()
    {
        $this->guard();

        if (! is_cli() && ! in_array($this->input->method(true), ['GET', 'POST'], true)) {
            throw new Api_exception('method_not_allowed', 'Use GET or POST for this endpoint.', 405);
        }

        $staffId = $this->input->get('staff_id');
        $staffId = ($staffId === null || $staffId === '') ? null : (int) $staffId;

        $this->load->library('api_auth');

        return [
            'tokens' => $this->api_auth->listTokens($staffId),
            'note'   => 'Token hashes and plaintext values are never returned. Use rotate to replace a token.',
        ];
    }

    private function revoke()
    {
        $this->guard();
        $this->assertPostOrCli();

        $input = $this->jsonInput();
        $id    = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            throw new Api_exception('validation_failed', 'id is required.', 422, ['field' => 'id']);
        }

        $token = $this->db->select('id, revoked_at')->where('id', $id)->get($this->db->dbprefix('api_tokens'))->row();

        if (! $token) {
            throw new Api_exception('not_found', 'No token with id ' . $id . '.', 404);
        }

        if (! empty($token->revoked_at)) {
            return [
                'revoked'         => true,
                'already_revoked' => true,
                'id'              => $id,
                'revoked_at'      => $token->revoked_at,
            ];
        }

        $revokedAt = date('Y-m-d H:i:s');

        $this->db->where('id', $id)->update($this->db->dbprefix('api_tokens'), [
            'revoked_at' => $revokedAt,
        ]);

        return ['revoked' => true, 'already_revoked' => false, 'id' => $id, 'revoked_at' => $revokedAt];
    }

    // ---------------------------------------------------------------------

    private function guard()
    {
        if (is_cli()) {
            return;
        }

        $expected = (string) getenv('MCP_SETUP_KEY');

        if ($expected === '') {
            throw new Api_exception(
                'setup_disabled',
                'Setup is disabled. Set MCP_SETUP_KEY in the FennecDash environment to enable it.',
                403
            );
        }

        $provided = (string) $this->input->get_request_header('X-Setup-Key', true);

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            throw new Api_exception('setup_unauthorized', 'Invalid setup key.', 401);
        }
    }

    private function assertPostOrCli()
    {
        if (is_cli()) {
            return;
        }

        if ($this->input->method(true) !== 'POST') {
            throw new Api_exception('method_not_allowed', 'Use POST for this endpoint.', 405);
        }
    }

    private function jsonInput()
    {
        $raw = $this->input->raw_input_stream;

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            throw new Api_exception('invalid_json', 'The request body must be valid JSON.', 400);
        }

        return $decoded;
    }
}
