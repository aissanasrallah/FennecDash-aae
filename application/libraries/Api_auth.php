<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/Api_exception.php';

/**
 * Token authentication, authorization, rate limiting, audit trail and
 * approval workflow for the FennecDash Agent API.
 *
 * Design notes
 * ------------
 * - Tokens are random 20-byte values; only a SHA-256 hash is stored.
 * - A token is bound to a real staff account and can never exceed that
 *   account's existing FennecDash permissions (`staff_can()`).
 * - The token additionally carries a maximum risk level so a read-only
 *   integration cannot perform writes even if the staff account could.
 */
class Api_auth
{
    /** @var CI_Controller */
    private $CI;

    /** @var object|null */
    private $token;

    /** @var object|null */
    private $staff;

    /** @var array */
    private $permissions = [];

    /** @var int */
    private $startedAt;

    /** @var string|null Agent/integration identity supplied by the MCP server. */
    private $agent;

    /** @var string|null Opaque agent session identifier supplied by the MCP server. */
    private $agentSession;

    /** @var string|null */
    private $requestId;

    /** @var string[]|null Cached api_audit_log column list for backwards compatibility. */
    private $auditColumns;

    public function __construct()
    {
        $this->CI        = &get_instance();
        $this->startedAt = microtime(true);

        $this->CI->load->database();
        $this->CI->load->helper(['admin', 'settings']);
        $this->CI->load->library('app_object_cache');
    }

    // ---------------------------------------------------------------------
    // Authentication
    // ---------------------------------------------------------------------

    /**
     * Record the calling agent identity for the audit trail.
     *
     * These values are descriptive only: authorization always comes from the
     * token and the bound staff account. They are sanitised and length-capped
     * so a caller cannot inject control characters or blow up the audit row.
     */
    public function setClientContext($agent = null, $session = null)
    {
        $this->agent        = $this->cleanLabel($agent, 120);
        $this->agentSession = $this->cleanLabel($session, 120);
    }

    public function setRequestId($requestId)
    {
        $this->requestId = $this->cleanLabel($requestId, 64);
    }

    private function cleanLabel($value, $max)
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = preg_replace('/[\x00-\x1F\x7F]+/', '', $value);

        return $value === null ? null : substr(trim($value), 0, $max);
    }

    /**
     * @return object staff row
     * @throws Api_exception
     */
    public function authenticate()
    {
        $plain = $this->bearerToken();

        if ($plain === '') {
            throw new Api_exception('unauthenticated', 'Missing API token. Send an Authorization: Bearer <token> header.', 401);
        }

        $hash = hash('sha256', $plain);

        $token = $this->CI->db
            ->where('token_hash', $hash)
            ->get($this->CI->db->dbprefix('api_tokens'))
            ->row();

        if (! $token) {
            throw new Api_exception('invalid_token', 'The API token is not recognised.', 401);
        }

        if (! empty($token->revoked_at)) {
            throw new Api_exception('token_revoked', 'This API token has been revoked.', 401);
        }

        if (! empty($token->expires_at) && strtotime($token->expires_at) < time()) {
            throw new Api_exception('token_expired', 'This API token has expired.', 401);
        }

        $staff = $this->CI->db
            ->select('staffid, email, firstname, lastname, admin, role, active, is_not_staff, default_language')
            ->where('staffid', (int) $token->staff_id)
            ->get($this->CI->db->dbprefix('staff'))
            ->row();

        if (! $staff || (int) $staff->active !== 1 || (int) $staff->is_not_staff === 1) {
            throw new Api_exception('inactive_staff', 'The staff account bound to this token is not active.', 403);
        }

        $this->token = $token;
        $this->staff = $staff;

        $this->CI->db
            ->where('id', (int) $token->id)
            ->update($this->CI->db->dbprefix('api_tokens'), [
                'last_used_at'  => date('Y-m-d H:i:s'),
                'request_count' => (int) $token->request_count + 1,
            ]);

        return $staff;
    }

    /**
     * Read the bearer token from the request headers.
     *
     * @return string
     */
    private function bearerToken()
    {
        $header = '';

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            foreach (apache_request_headers() as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    $header = $value;
                    break;
                }
            }
        }

        if (preg_match('/^\s*Bearer\s+(.+)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        $apiKey = $this->CI->input->get_request_header('X-API-Key', true);

        return is_string($apiKey) ? trim($apiKey) : '';
    }

    // ---------------------------------------------------------------------
    // Authorization
    // ---------------------------------------------------------------------

    public function token()
    {
        return $this->token;
    }

    public function staff()
    {
        return $this->staff;
    }

    public function staffId()
    {
        return $this->staff ? (int) $this->staff->staffid : 0;
    }

    public function isAdmin()
    {
        return $this->staff && (int) $this->staff->admin === 1;
    }

    /**
     * Ensure the token's declared maximum risk covers the operation.
     *
     * @throws Api_exception
     */
    public function requireRisk($risk)
    {
        $levels = ['read' => 0, 'write' => 1, 'critical' => 2];
        $required = $levels[$risk] ?? 0;
        $granted  = $levels[$this->token->risk_max] ?? 0;

        if ($granted < $required) {
            throw new Api_exception(
                'risk_not_permitted',
                sprintf('This token is limited to "%s" operations and cannot perform "%s".', $this->token->risk_max, $risk),
                403,
                ['required_risk' => $risk, 'token_risk' => $this->token->risk_max]
            );
        }
    }

    /**
     * Check the underlying FennecDash permission of the token owner.
     *
     * @throws Api_exception
     */
    public function requirePermission($feature, $capability)
    {
        if ($feature === null) {
            return;
        }

        if (! $this->tokenAllowsFeature($feature)) {
            throw new Api_exception('feature_not_in_token_scope', 'This token is not scoped for the "' . $feature . '" feature.', 403);
        }

        if (! staff_can($capability, $feature, $this->staffId())) {
            throw new Api_exception(
                'forbidden',
                sprintf('The token owner lacks the "%s" permission on "%s".', $capability, $feature),
                403,
                ['feature' => $feature, 'capability' => $capability]
            );
        }
    }

    /**
     * Non-throwing permission check used when building composite results that
     * span several features (for example global search).
     */
    public function canFeature($feature, $capability = 'view')
    {
        if ($feature === null) {
            return true;
        }

        return $this->tokenAllowsFeature($feature)
            && staff_can($capability, $feature, $this->staffId());
    }

    private function tokenAllowsFeature($feature)
    {
        if (empty($this->token->scopes)) {
            return true;
        }

        $scopes = json_decode($this->token->scopes, true);

        if (! is_array($scopes) || $scopes === []) {
            return true;
        }

        return in_array($feature, $scopes, true);
    }

    // ---------------------------------------------------------------------
    // Rate limiting
    // ---------------------------------------------------------------------

    /**
     * @throws Api_exception
     */
    public function rateLimit($limit = null)
    {
        $limit = $limit ?: (int) (getenv('MCP_RATE_LIMIT') ?: 120);
        if ($limit <= 0) {
            return;
        }

        $window = (int) (floor(time() / 60) * 60);
        $table  = $this->CI->db->dbprefix('api_rate_limits');

        // Opportunistic pruning: rate-limit rows are disposable and must not
        // grow without bound on a long-running installation.
        if (random_int(1, 50) === 1) {
            $this->pruneRateLimits();
        }

        $this->CI->db->query(
            'INSERT INTO `' . $table . '` (token_id, window_start, hits) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE hits = hits + 1',
            [(int) $this->token->id, $window]
        );

        $row = $this->CI->db->query(
            'SELECT hits FROM `' . $table . '` WHERE token_id = ? AND window_start = ?',
            [(int) $this->token->id, $window]
        )->row();

        if ($row && (int) $row->hits > $limit) {
            throw new Api_exception(
                'rate_limited',
                'Rate limit exceeded for this token.',
                429,
                ['limit_per_minute' => $limit, 'retry_after_seconds' => 60 - (time() - $window)]
            );
        }
    }

    /**
     * Delete rate-limit windows older than one hour. Kept out of the hot path
     * except for an occasional probabilistic call.
     */
    public function pruneRateLimits()
    {
        $table = $this->CI->db->dbprefix('api_rate_limits');

        $this->CI->db->query(
            'DELETE FROM `' . $table . '` WHERE window_start < ?',
            [time() - 3600]
        );
    }

    // ---------------------------------------------------------------------
    // Approvals
    // ---------------------------------------------------------------------

    /**
     * Create a single-use approval token for a critical action.
     *
     * @return array{approval_token: string, expires_at: string, action: string}
     */
    public function createApproval($action, array $payload, $ttlSeconds = 300)
    {
        if ($this->token->risk_max !== 'critical') {
            throw new Api_exception('approval_requires_critical_token', 'Only a critical-risk token can request approvals.', 403);
        }

        $plain  = 'fdm_ap_' . bin2hex(random_bytes(20));
        $now    = time();
        $expiry = date('Y-m-d H:i:s', $now + $ttlSeconds);

        $this->CI->db->insert($this->CI->db->dbprefix('api_approvals'), [
            'token_id'      => (int) $this->token->id,
            'staff_id'      => $this->staffId(),
            'action'        => $action,
            'approval_hash' => hash('sha256', $plain),
            'payload_hash'  => $this->payloadHash($payload),
            'status'        => 'pending',
            'created_at'    => date('Y-m-d H:i:s'),
            'expires_at'    => $expiry,
        ]);

        return [
            'approval_token' => $plain,
            'expires_at'     => $expiry,
            'action'         => $action,
        ];
    }

    /**
     * Consume an approval. Throws when it does not match the action or payload.
     */
    public function consumeApproval($approvalToken, $action, array $payload)
    {
        if (! is_string($approvalToken) || $approvalToken === '') {
            throw new Api_exception(
                'approval_required',
                'This operation is critical and requires an approval token. Call the approval endpoint first.',
                403,
                ['action' => $action]
            );
        }

        $row = $this->CI->db
            ->where('approval_hash', hash('sha256', $approvalToken))
            ->get($this->CI->db->dbprefix('api_approvals'))
            ->row();

        if (! $row) {
            throw new Api_exception('approval_invalid', 'The approval token is not recognised.', 403);
        }

        if ($row->status !== 'pending') {
            throw new Api_exception('approval_not_pending', 'This approval is no longer usable.', 409, ['status' => $row->status]);
        }

        if (strtotime($row->expires_at) < time()) {
            $this->CI->db->where('id', (int) $row->id)->update($this->CI->db->dbprefix('api_approvals'), ['status' => 'expired']);
            throw new Api_exception('approval_expired', 'The approval token has expired.', 403);
        }

        if ((int) $row->token_id !== (int) $this->token->id) {
            throw new Api_exception('approval_wrong_token', 'This approval belongs to a different API token.', 403);
        }

        if ($row->action !== $action) {
            throw new Api_exception('approval_action_mismatch', 'The approval was issued for a different action.', 403);
        }

        if ($row->payload_hash !== $this->payloadHash($payload)) {
            throw new Api_exception('approval_payload_mismatch', 'The approval was issued for a different payload.', 403);
        }

        $this->CI->db->where('id', (int) $row->id)->update($this->CI->db->dbprefix('api_approvals'), [
            'status'  => 'used',
            'used_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Deterministic hash of an associative array (key order independent).
     */
    public function payloadHash(array $payload)
    {
        $this->ksortRecursive($payload);

        return hash('sha256', json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        ));
    }

    private function ksortRecursive(array &$array)
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }

    // ---------------------------------------------------------------------
    // Audit
    // ---------------------------------------------------------------------

    public function audit($action, $risk, $status, array $summary = [], $responseMs = null)
    {
        $payload = [
            'token_id'        => $this->token ? (int) $this->token->id : null,
            'staff_id'        => $this->staff ? (int) $this->staff->staffid : null,
            'action'          => substr((string) $action, 0, 120),
            'risk'            => in_array($risk, ['read', 'write', 'critical'], true) ? $risk : 'read',
            'http_method'     => $this->CI->input->method(true),
            'path'            => substr((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), 0, 255),
            'status_code'     => (int) $status,
            'ip'              => substr((string) $this->CI->input->ip_address(), 0, 64),
            'user_agent'      => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'agent'           => $this->agent,
            'agent_session'   => $this->agentSession,
            'request_id'      => $this->requestId,
            'request_summary' => json_encode(
                $this->redact($summary),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            ),
            'response_ms'     => (int) ($responseMs ?? round((microtime(true) - $this->startedAt) * 1000)),
            'created_at'      => date('Y-m-d H:i:s'),
        ];

        $this->CI->db->insert(
            $this->CI->db->dbprefix('api_audit_log'),
            $this->filterAuditColumns($payload)
        );
    }

    /**
     * The audit table gained columns over time. Keep writes working during a
     * rolling upgrade by writing only columns that exist on this installation.
     *
     * @return string[]
     */
    private function auditColumnNames()
    {
        if ($this->auditColumns !== null) {
            return $this->auditColumns;
        }

        $query = $this->CI->db->query(
            'SELECT COLUMN_NAME FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ?',
            [$this->CI->db->dbprefix('api_audit_log')]
        );

        $columns = [];
        if ($query) {
            foreach ($query->result() as $row) {
                $columns[] = $row->COLUMN_NAME;
            }
        }

        // If introspection fails, fall back to the columns of the original
        // Agent API release instead of guessing.
        $this->auditColumns = $columns === []
            ? ['token_id', 'staff_id', 'action', 'risk', 'http_method', 'path', 'status_code',
               'ip', 'user_agent', 'request_summary', 'response_ms', 'created_at']
            : $columns;

        return $this->auditColumns;
    }

    private function filterAuditColumns(array $payload)
    {
        return array_intersect_key($payload, array_flip($this->auditColumnNames()));
    }

    /**
     * Remove anything that must never be written to an audit log.
     */
    public function redact($value)
    {
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $key => $item) {
                if (is_string($key) && preg_match('/pass|secret|token|key|authorization|credential|signature/i', $key)) {
                    $clean[$key] = '[redacted]';
                } else {
                    $clean[$key] = $this->redact($item);
                }
            }

            return $clean;
        }

        if (is_string($value)) {
            // Never persist a value that looks like a bearer token.
            return preg_replace('/fdm_[A-Za-z0-9_]{20,}/', '[redacted-token]', $value);
        }

        return $value;
    }

    // ---------------------------------------------------------------------
    // Token lifecycle helpers (used by setup / CLI)
    // ---------------------------------------------------------------------

    /**
     * @return array{token: string, id: int, prefix: string}
     */
    public function issueToken(
        $staffId,
        $label,
        $riskMax = 'read',
        array $scopes = [],
        $expiresAt = null,
        $client = null,
        $rotatedFromId = null
    ) {
        $plain = 'fdm_' . bin2hex(random_bytes(20));

        $scopes = array_values(array_filter(array_map(
            static fn ($scope) => is_string($scope) ? trim($scope) : '',
            $scopes
        ), static fn ($scope) => $scope !== ''));

        $this->CI->db->insert($this->CI->db->dbprefix('api_tokens'), [
            'staff_id'        => (int) $staffId,
            'label'           => substr(trim((string) $label), 0, 120),
            'token_prefix'    => substr($plain, 0, 12),
            'token_hash'      => hash('sha256', $plain),
            'risk_max'        => in_array($riskMax, ['read', 'write', 'critical'], true) ? $riskMax : 'read',
            'scopes'          => $scopes === [] ? null : json_encode($scopes),
            'client'          => $client === null ? null : substr(trim((string) $client), 0, 60),
            'created_at'      => date('Y-m-d H:i:s'),
            'expires_at'      => $expiresAt,
            'rotated_from_id' => $rotatedFromId === null ? null : (int) $rotatedFromId,
        ]);

        return [
            'token'  => $plain,
            'id'     => (int) $this->CI->db->insert_id(),
            'prefix' => substr($plain, 0, 12),
        ];
    }

    /**
     * Issue a replacement token and revoke the old one atomically.
     *
     * @return array{token: string, id: int, prefix: string, rotated_from: int}
     * @throws Api_exception
     */
    public function rotateToken($id, $label = null, $expiresAt = null, $client = null)
    {
        $old = $this->CI->db
            ->where('id', (int) $id)
            ->get($this->CI->db->dbprefix('api_tokens'))
            ->row();

        if (! $old) {
            throw new Api_exception('not_found', 'No token with id ' . (int) $id . '.', 404);
        }

        if (! empty($old->revoked_at)) {
            throw new Api_exception('token_revoked', 'This token is already revoked and cannot be rotated.', 409);
        }

        $scopes = $old->scopes ? json_decode($old->scopes, true) : [];
        $scopes = is_array($scopes) ? $scopes : [];

        $this->CI->db->trans_start();

        $issued = $this->issueToken(
            (int) $old->staff_id,
            $label !== null && trim((string) $label) !== '' ? $label : $old->label,
            $old->risk_max,
            $scopes,
            $expiresAt !== null ? $expiresAt : $old->expires_at,
            $client !== null ? $client : $old->client,
            (int) $old->id
        );

        $this->CI->db->where('id', (int) $old->id)->update($this->CI->db->dbprefix('api_tokens'), [
            'revoked_at' => date('Y-m-d H:i:s'),
        ]);

        $this->CI->db->trans_complete();

        if ($this->CI->db->trans_status() === false) {
            throw new Api_exception('internal_error', 'The token could not be rotated; the old token was not changed.', 500);
        }

        return $issued + ['rotated_from' => (int) $old->id];
    }

    /**
     * Metadata only: hashes are never returned.
     *
     * @return array<int, array>
     */
    public function listTokens($staffId = null)
    {
        $this->CI->db->select('id, staff_id, label, token_prefix, risk_max, scopes, client, created_at,
            last_used_at, expires_at, revoked_at, rotated_from_id, request_count');

        if ($staffId !== null) {
            $this->CI->db->where('staff_id', (int) $staffId);
        }

        $rows = $this->CI->db
            ->order_by('id', 'desc')
            ->limit(500)
            ->get($this->CI->db->dbprefix('api_tokens'))
            ->result_array();

        return array_map(static function ($row) {
            return [
                'id'              => (int) $row['id'],
                'staff_id'        => (int) $row['staff_id'],
                'label'           => $row['label'],
                'prefix'          => $row['token_prefix'],
                'risk_max'        => $row['risk_max'],
                'scopes'          => $row['scopes'] ? json_decode($row['scopes'], true) : null,
                'client'          => $row['client'],
                'created_at'      => $row['created_at'],
                'last_used_at'    => $row['last_used_at'],
                'expires_at'      => $row['expires_at'],
                'revoked_at'      => $row['revoked_at'],
                'rotated_from_id' => $row['rotated_from_id'] !== null ? (int) $row['rotated_from_id'] : null,
                'request_count'   => (int) $row['request_count'],
                'active'          => empty($row['revoked_at'])
                    && (empty($row['expires_at']) || strtotime($row['expires_at']) >= time()),
            ];
        }, $rows);
    }
}
