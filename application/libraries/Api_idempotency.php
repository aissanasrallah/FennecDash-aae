<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'libraries/Api_exception.php';

/**
 * Idempotency store for the Agent API.
 *
 * A client may send `Idempotency-Key` on a create operation. The first request
 * executes and stores its response; a retry with the same key and the same
 * request body replays the stored response instead of creating a duplicate.
 *
 * The key is scoped to the API token, so two independent agents can never
 * collide. A pending row that is older than the takeover window is considered
 * abandoned and may be retried.
 */
class Api_idempotency
{
    /** How long a completed result is kept for replay. */
    const RETENTION_SECONDS = 86400;

    /** A pending request older than this is treated as abandoned. */
    const TAKEOVER_SECONDS = 120;

    /** @var CI_Controller */
    private $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
    }

    /**
     * Validate and normalise an Idempotency-Key header.
     *
     * @return string|null null when the header is absent
     * @throws Api_exception
     */
    public function normalizeKey($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new Api_exception('idempotency_key_invalid', 'Idempotency-Key must be a string.', 422);
        }

        $key = trim($value);

        if (strlen($key) < 8 || strlen($key) > 128 || ! preg_match('/^[A-Za-z0-9._:-]+$/', $key)) {
            throw new Api_exception(
                'idempotency_key_invalid',
                'Idempotency-Key must be 8-128 characters using letters, digits, dot, underscore, colon or hyphen.',
                422
            );
        }

        return $key;
    }

    /**
     * Deterministic fingerprint of the operation. A replay is only valid for
     * exactly the same method, path and body.
     */
    public function requestHash($method, $path, $rawBody)
    {
        return hash('sha256', strtoupper((string) $method) . "\n" . (string) $path . "\n" . (string) $rawBody);
    }

    /**
     * Claim an idempotency key.
     *
     * @return array{state: string, id: int, response_code?: int, response_body?: string}
     * @throws Api_exception
     */
    public function begin($tokenId, $key, $requestHash)
    {
        $table = $this->CI->db->dbprefix('api_idempotency');
        $now   = date('Y-m-d H:i:s');

        $this->CI->db->query(
            'INSERT IGNORE INTO `' . $table . '` (token_id, idem_key, request_hash, status, created_at, updated_at)
             VALUES (?, ?, ?, \'pending\', ?, ?)',
            [(int) $tokenId, $key, $requestHash, $now, $now]
        );

        $inserted = $this->CI->db->affected_rows() > 0;

        $row = $this->CI->db->query(
            'SELECT * FROM `' . $table . '` WHERE token_id = ? AND idem_key = ? LIMIT 1',
            [(int) $tokenId, $key]
        )->row();

        if (! $row) {
            throw new Api_exception('internal_error', 'The idempotency record could not be created.', 500);
        }

        // This request created the record: it owns the claim immediately.
        // Without this check a fresh claim would look like somebody else's
        // in-flight request and the first attempt could never execute.
        if ($inserted) {
            return ['state' => 'acquired', 'id' => (int) $row->id];
        }

        if (! hash_equals((string) $row->request_hash, (string) $requestHash)) {
            throw new Api_exception(
                'idempotency_conflict',
                'This Idempotency-Key was already used for a different request payload.',
                409,
                ['idempotency_key' => $key]
            );
        }

        if ($row->status === 'completed') {
            return [
                'state'         => 'replay',
                'id'            => (int) $row->id,
                'response_code' => (int) $row->response_code,
                'response_body' => (string) $row->response_body,
            ];
        }

        $age = time() - strtotime((string) $row->created_at);

        if ($age > self::TAKEOVER_SECONDS) {
            $this->CI->db->query(
                'UPDATE `' . $table . '` SET request_hash = ?, created_at = ?, updated_at = ? WHERE id = ? AND status = \'pending\'',
                [$requestHash, $now, $now, (int) $row->id]
            );

            return ['state' => 'acquired', 'id' => (int) $row->id];
        }

        throw new Api_exception(
            'idempotency_in_progress',
            'A request with this Idempotency-Key is still in progress. Retry after it completes.',
            409,
            ['idempotency_key' => $key, 'retryable' => true]
        );
    }

    public function complete($id, $statusCode, $payloadJson)
    {
        $this->CI->db->where('id', (int) $id)->update($this->CI->db->dbprefix('api_idempotency'), [
            'status'        => 'completed',
            'response_code' => (int) $statusCode,
            'response_body' => (string) $payloadJson,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Release a failed attempt so the client can retry with the same key.
     */
    public function fail($id)
    {
        $this->CI->db->where('id', (int) $id)
            ->where('status', 'pending')
            ->delete($this->CI->db->dbprefix('api_idempotency'));
    }

    public function prune()
    {
        $table = $this->CI->db->dbprefix('api_idempotency');

        $this->CI->db->query(
            'DELETE FROM `' . $table . '` WHERE updated_at < ? AND status = \'completed\'',
            [date('Y-m-d H:i:s', time() - self::RETENTION_SECONDS)]
        );

        $this->CI->db->query(
            'DELETE FROM `' . $table . '` WHERE updated_at < ? AND status = \'pending\'',
            [date('Y-m-d H:i:s', time() - (self::TAKEOVER_SECONDS * 10))]
        );
    }
}
