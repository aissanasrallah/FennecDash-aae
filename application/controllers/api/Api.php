<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/Api_exception.php';

/**
 * Base controller for the FennecDash Agent API.
 *
 * Responsibilities kept out of the endpoints:
 *   - JSON envelope and HTTP status handling
 *   - bearer authentication, risk ceiling, permissions and rate limiting
 *   - audit trail (one row per call, success or failure)
 *   - request parsing and validation helpers
 *
 * Endpoints only contain business logic and return arrays.
 */
class Api extends CI_Controller
{
    /** Maximum accepted JSON request body (2 MiB). */
    const MAX_JSON_BYTES = 2097152;

    /** @var Api_auth */
    protected $apiAuth;

    /** @var Api_resources */
    protected $apiResources;

    /** @var Api_idempotency */
    protected $apiIdempotency;

    /** @var array<string, array> */
    protected $routes = [];

    /** @var float */
    protected $startedAt;

    /** @var string */
    protected $requestId;

    public function __construct()
    {
        parent::__construct();

        $this->startedAt = microtime(true);
        $this->requestId = $this->requestId();

        $this->load->database();
        $this->load->helper(['admin', 'settings', 'general', 'file']);
        $this->load->library(['api_auth', 'api_resources', 'app_object_cache', 'api_idempotency']);

        $this->apiAuth        = $this->api_auth;
        $this->apiResources   = $this->api_resources;
        $this->apiIdempotency = $this->api_idempotency;

        $this->apiAuth->setClientContext(
            $this->input->get_request_header('X-Agent-Name', true),
            $this->input->get_request_header('X-Agent-Session', true)
        );
        $this->apiAuth->setRequestId($this->requestId);

        $this->output->set_content_type('application/json', 'utf-8');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate');
        $this->output->set_header('X-Content-Type-Options: nosniff');
        $this->output->set_header('X-Request-Id: ' . $this->requestId);
    }

    // ---------------------------------------------------------------------
    // Dispatch
    // ---------------------------------------------------------------------

    /**
     * Wrap every endpoint with authorization, error handling and auditing.
     *
     * @param string $method
     * @param array  $params
     */
    protected function dispatch($method, array $params = [])
    {
        $spec   = $this->routes[$method] ?? null;
        $risk   = $spec['risk'] ?? 'read';
        $status = 200;
        $result = null;
        $payload = null;

        $idempotencyId  = null;
        $idempotencyKey = null;
        $replayed       = false;
        $auditExtra     = [];

        try {
            if ($spec === null) {
                throw new Api_exception('unknown_endpoint', 'Unknown API endpoint: ' . $method, 404);
            }

            if (empty($spec['public'])) {
                $this->apiAuth->authenticate();
                $this->apiAuth->rateLimit();
                $this->apiAuth->requireRisk($risk);
                $this->apiAuth->requirePermission($spec['feature'] ?? null, $spec['capability'] ?? 'view');
            }

            // Idempotent writes: the key is optional for raw API clients but
            // used automatically by the MCP server. Replays return the exact
            // stored response and never execute the endpoint twice.
            if (! empty($spec['idempotent'])) {
                $idempotencyKey = $this->apiIdempotency->normalizeKey(
                    $this->input->get_request_header('Idempotency-Key', true)
                );

                if ($idempotencyKey !== null) {
                    $state = $this->apiIdempotency->begin(
                        (int) $this->currentToken()->id,
                        $idempotencyKey,
                        $this->apiIdempotency->requestHash(
                            $_SERVER['REQUEST_METHOD'] ?? 'POST',
                            (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'),
                            (string) $this->input->raw_input_stream
                        )
                    );

                    if ($state['state'] === 'replay') {
                        $replayed = true;
                        $status   = (int) ($state['response_code'] ?? 200);
                        $payload  = json_decode((string) ($state['response_body'] ?? ''), true);

                        if (! is_array($payload)) {
                            throw new Api_exception('internal_error', 'The stored idempotent response is unreadable.', 500);
                        }

                        $auditExtra = [
                            'idempotent_replay' => true,
                            'idempotency_key'   => $idempotencyKey,
                        ];
                    } else {
                        $idempotencyId = (int) $state['id'];
                    }
                }
            }

            if (! $replayed) {
                $result = call_user_func_array([$this, $method], $params);
                $status = 200;

                $payload = ['ok' => true, 'data' => $result['data'] ?? $result];

                if (isset($result['meta'])) {
                    $payload['meta'] = $result['meta'];
                }

                $payload['request_id'] = $this->requestId;

                if ($idempotencyId !== null) {
                    try {
                        $this->apiIdempotency->complete(
                            $idempotencyId,
                            $status,
                            json_encode(
                                $payload,
                                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                            )
                        );
                    } catch (Throwable $e) {
                        // The business operation already succeeded. Never turn
                        // that into a client-visible failure: log instead.
                        error_log('[fennedash-api] idempotency store failed: ' . $e->getMessage());
                    }
                }
            }
        } catch (Api_exception $e) {
            if ($idempotencyId !== null) {
                $this->apiIdempotency->fail($idempotencyId);
            }

            $status  = $e->getStatus();
            $payload = $this->errorPayload($e->getErrorCode(), $e->getMessage(), $e->getDetails());
        } catch (Throwable $e) {
            if ($idempotencyId !== null) {
                $this->apiIdempotency->fail($idempotencyId);
            }

            $status = 500;

            // Keep the client response generic in production; persist the detail for operators.
            error_log('[fennedash-api] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            $message = ENVIRONMENT === 'production'
                ? 'An internal error occurred while processing the request.'
                : get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();

            $payload = $this->errorPayload('internal_error', $message, []);
        }

        if ($idempotencyKey !== null) {
            $this->output->set_header('Idempotency-Key: ' . $idempotencyKey);

            if ($replayed) {
                $this->output->set_header('Idempotent-Replayed: true');
            }
        }

        if ($status === 429 && isset($payload['error']['details']['retry_after_seconds'])) {
            $this->output->set_header('Retry-After: ' . max(1, (int) $payload['error']['details']['retry_after_seconds']));
        }

        if ($status === 409 && ($payload['error']['code'] ?? '') === 'idempotency_in_progress') {
            $this->output->set_header('Retry-After: 2');
        }

        $this->auditSafe($method, $risk, $status, $spec, $params, $auditExtra);

        $this->output
            ->set_status_header($status)
            ->set_output(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function errorPayload($code, $message, $details = [])
    {
        $error = ['code' => $code, 'message' => $message];

        if (! empty($details)) {
            $error['details'] = $details;
        }

        return [
            'ok'         => false,
            'error'      => $error,
            'request_id' => $this->requestId,
        ];
    }

    private function auditSafe($action, $risk, $status, $spec, array $params, array $extra = [])
    {
        try {
            if (empty($spec['public'])) {
                $summary = [
                    'params' => $params,
                    'query'  => $this->input->get() ?: [],
                    'body'   => $this->safeBodyForAudit(),
                ];

                if ($extra !== []) {
                    $summary = array_merge($summary, $extra);
                }

                $this->apiAuth->audit($action, $risk, $status, $summary);
            }
        } catch (Throwable $e) {
            error_log('[fennedash-api] audit failed: ' . $e->getMessage());
        }
    }

    private function safeBodyForAudit()
    {
        $raw = $this->input->raw_input_stream;

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : ['raw' => '[unparsed]'];
    }

    private function requestId()
    {
        $incoming = $this->input->get_request_header('X-Request-Id', true);

        if (is_string($incoming) && preg_match('/^[A-Za-z0-9._-]{6,64}$/', $incoming)) {
            return $incoming;
        }

        return 'req_' . bin2hex(random_bytes(8));
    }

    // ---------------------------------------------------------------------
    // Request helpers
    // ---------------------------------------------------------------------

    /**
     * @return array
     * @throws Api_exception
     */
    protected function jsonInput()
    {
        $raw = $this->input->raw_input_stream;

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        if (strlen($raw) > self::MAX_JSON_BYTES) {
            throw new Api_exception(
                'payload_too_large',
                'The JSON request body exceeds the ' . (self::MAX_JSON_BYTES / 1024) . ' KiB limit.',
                413
            );
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            throw new Api_exception('invalid_json', 'The request body must be a valid JSON object.', 400);
        }

        return $decoded;
    }

    /**
     * @param array  $input
     * @param string $key
     * @param mixed  $default
     * @param bool   $required
     * @return mixed
     * @throws Api_exception
     */
    protected function inputValue(array $input, $key, $default = null, $required = false)
    {
        if (array_key_exists($key, $input) && $input[$key] !== '') {
            return $input[$key];
        }

        if ($required) {
            throw new Api_exception('validation_failed', 'Missing required field: ' . $key, 422, ['field' => $key]);
        }

        return $default;
    }

    protected function requireString(array $input, $key, $maxLength = 255)
    {
        $value = $this->inputValue($input, $key, null, true);

        if (! is_string($value) || trim($value) === '') {
            throw new Api_exception('validation_failed', 'Field "' . $key . '" must be a non-empty string.', 422, ['field' => $key]);
        }

        if (mb_strlen($value) > $maxLength) {
            throw new Api_exception('validation_failed', 'Field "' . $key . '" exceeds ' . $maxLength . ' characters.', 422, ['field' => $key]);
        }

        return trim($value);
    }

    protected function requireInt(array $input, $key)
    {
        $value = $this->inputValue($input, $key, null, true);

        if (! is_numeric($value) || (int) $value <= 0) {
            throw new Api_exception('validation_failed', 'Field "' . $key . '" must be a positive integer.', 422, ['field' => $key]);
        }

        return (int) $value;
    }

    /**
     * Strict boolean input. Accepts real booleans, 0/1 and the usual
     * true/false strings; anything else is a validation error.
     */
    protected function boolInput(array $input, $key, $default = false)
    {
        if (! array_key_exists($key, $input) || $input[$key] === null || $input[$key] === '') {
            return $default;
        }

        $value = filter_var($input[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($value === null) {
            throw new Api_exception(
                'validation_failed',
                'Field "' . $key . '" must be a boolean.',
                422,
                ['field' => $key]
            );
        }

        return $value;
    }

    /**
     * Reject unknown fields so a typo cannot silently be ignored on a write.
     *
     * @param string[] $allowed
     */
    protected function assertKnownFields(array $input, array $allowed)
    {
        $unknown = array_values(array_diff(array_keys($input), $allowed));

        if ($unknown !== []) {
            throw new Api_exception(
                'validation_failed',
                'Unknown field(s): ' . implode(', ', $unknown) . '.',
                422,
                ['fields' => $unknown]
            );
        }
    }

    protected function requireFloat(array $input, $key, $default = null, $min = 0.0)
    {
        $value = $this->inputValue($input, $key, $default);

        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            throw new Api_exception('validation_failed', 'Field "' . $key . '" must be a number.', 422, ['field' => $key]);
        }

        $number = (float) $value;

        if (! is_finite($number) || $number < $min) {
            throw new Api_exception(
                'validation_failed',
                'Field "' . $key . '" must be a finite number greater than or equal to ' . $min . '.',
                422,
                ['field' => $key]
            );
        }

        return $number;
    }

    protected function optionalInt(array $input, $key, $default, $min, $max)
    {
        $value = $this->inputValue($input, $key, $default);

        if (is_bool($value) || ! is_numeric($value) || (float) $value !== (float) (int) $value) {
            throw new Api_exception('validation_failed', 'Field "' . $key . '" must be an integer.', 422, ['field' => $key]);
        }

        $number = (int) $value;

        if ($number < $min || $number > $max) {
            throw new Api_exception(
                'validation_failed',
                'Field "' . $key . '" must be between ' . $min . ' and ' . $max . '.',
                422,
                ['field' => $key]
            );
        }

        return $number;
    }

    // ---------------------------------------------------------------------
    // Shared helpers used by endpoints
    // ---------------------------------------------------------------------

    protected function currentStaff()
    {
        return $this->apiAuth->staff();
    }

    protected function currentToken()
    {
        return $this->apiAuth->token();
    }

    /**
     * Safe read of a runtime option. Only an allowlist of keys is reachable
     * from the API; this helper never returns secrets by itself.
     */
    protected function option($key, $default = null)
    {
        $value = get_option($key);

        return ($value === '' || $value === null) ? $default : $value;
    }

    protected function appVersion()
    {
        if (defined('APP_VERSION')) {
            return APP_VERSION;
        }

        $row = $this->db->select('version')
            ->order_by('version', 'desc')
            ->limit(1)
            ->get(db_prefix() . 'migrations')
            ->row();

        return $row ? (int) $row->version : null;
    }

    protected function tableExists($table)
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [db_prefix() . $table]
        )->row();

        return $row && (int) $row->total > 0;
    }

    protected function countRows($table, $where = [])
    {
        if (! $this->tableExists($table)) {
            return 0;
        }

        if ($where !== []) {
            $this->db->where($where);
        }

        return (int) $this->db->count_all_results(db_prefix() . $table);
    }
}
