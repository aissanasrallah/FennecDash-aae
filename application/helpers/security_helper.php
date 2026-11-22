<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * GLOBAL SECURITY HELPER
 * ───────────────────────────────────────────────────────────────────────────────
 * Injection prevention, XSS filtering, input validation, output encoding,
 * CSRF hardening, and secure type coercion for the application.
 * ═══════════════════════════════════════════════════════════════════════════════
 */

// ─────────────────────────────────────────────────────────────────────────────
// 1. INPUT SANITIZATION (Injection Prevention)
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('sanitize_input')) {
    /**
     * Sanitize a scalar input value — strip null bytes, trim,
     * remove superfluous whitespace, and optionally strip tags.
     */
    function sanitize_input($value, bool $strip_tags = false): string
    {
        if (! is_scalar($value) && ! is_null($value)) {
            return '';
        }
        $value = (string) $value;
        $value = str_replace(["\0", '%00', "\r"], '', $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = trim($value);
        $value = $strip_tags ? strip_tags($value) : $value;

        return $value;
    }
}

if (! function_exists('sanitize_input_deep')) {
    /**
     * Recursively sanitize an entire input array/tree.
     * Kills null bytes, trims, and optionally strips tags on every leaf.
     */
    function sanitize_input_deep($data, bool $strip_tags = false)
    {
        if (is_array($data)) {
            $clean = [];
            foreach ($data as $key => $value) {
                $clean[sanitize_input((string) $key, $strip_tags)] = sanitize_input_deep($value, $strip_tags);
            }

            return $clean;
        }

        return sanitize_input($data, $strip_tags);
    }
}

if (! function_exists('sanitize_input_http')) {
    /**
     * Sanitize the full $_POST / $_GET / $_REQUEST super-global against
     * common web attacks.  Uses CI's built-in XSS filter when available.
     */
    function sanitize_input_http(string $source = 'post'): array
    {
        $CI = &get_instance();

        switch ($source) {
            case 'get':
                $input = $CI->input->get(null, true);
                break;
            case 'request':
                $input = array_merge(
                    (array) $CI->input->get(null, true),
                    (array) $CI->input->post(null, true)
                );
                break;
            case 'post':
            default:
                $input = $CI->input->post(null, true);
                break;
        }

        return sanitize_input_deep($input ?: []);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. XSS / OUTPUT ENCODING
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('sanitize_html')) {
    /**
     * Sanitize a string intended for HTML output (double-encode safe).
     */
    function sanitize_html($value, bool $double_encode = false): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
            $double_encode
        );
    }
}

if (! function_exists('sanitize_js')) {
    /**
     * Encode a value for safe embedding inside a <script> block or JSON.
     * Uses json_encode which handles Unicode and special chars properly.
     *
     * @param mixed $value
     */
    function sanitize_js($value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    }
}

if (! function_exists('sanitize_url')) {
    /**
     * Encode a string for safe use in a URL query parameter value.
     */
    function sanitize_url(string $value): string
    {
        return rawurlencode($value);
    }
}

if (! function_exists('sanitize_attr')) {
    /**
     * Encode a string for safe embedding inside an HTML attribute value.
     */
    function sanitize_attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
    }
}

if (! function_exists('sanitize_css')) {
    /**
     * Encode a string for safe embedding inside a <style> block or inline style.
     */
    function sanitize_css(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-#\s.,:;()%]/', '', $value);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. STRICT TYPE VALIDATION (SQLi / IDOR prevention)
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('validate_int')) {
    /**
     * Strictly validate that a value is an integer within an optional range.
     * Returns the integer or null on failure.
     */
    function validate_int($value, ?int $min = null, ?int $max = null): ?int
    {
        if (! is_numeric($value) && ! is_int($value)) {
            return null;
        }
        $val = (int) $value;
        if ((string) $val !== (string) $value && (string) $val !== $value) {
            return null;
        }
        if ($min !== null && $val < $min) {
            return null;
        }
        if ($max !== null && $val > $max) {
            return null;
        }

        return $val;
    }
}

if (! function_exists('validate_float')) {
    /**
     * Strictly validate a float value within an optional range.
     */
    function validate_float($value, ?float $min = null, ?float $max = null): ?float
    {
        if (! is_numeric($value) && ! is_float($value)) {
            return null;
        }
        $val = (float) $value;
        if ($min !== null && $val < $min) {
            return null;
        }
        if ($max !== null && $val > $max) {
            return null;
        }

        return $val;
    }
}

if (! function_exists('validate_email')) {
    /**
     * Validate an email address using filter_var.
     */
    function validate_email(string $email): ?string
    {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            return $email;
        }

        return null;
    }
}

if (! function_exists('validate_url')) {
    /**
     * Validate a URL. Returns the URL or null.
     */
    function validate_url(string $url, array $allowed_schemes = ['http', 'https']): ?string
    {
        $url = trim($url);
        $parsed = parse_url($url);
        if ($parsed === false || ! isset($parsed['scheme'], $parsed['host'])) {
            return null;
        }
        if (! in_array(strtolower($parsed['scheme']), $allowed_schemes, true)) {
            return null;
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $url;
    }
}

if (! function_exists('validate_alpha')) {
    /**
     * Validate a string contains only alphabetic characters.
     */
    function validate_alpha(string $value, bool $allow_spaces = false, bool $allow_dashes = false): ?string
    {
        $pattern = '/^[a-zA-Z' . ($allow_spaces ? '\s' : '') . ($allow_dashes ? '\-' : '') . ']+$/u';

        return preg_match($pattern, $value) ? $value : null;
    }
}

if (! function_exists('validate_alphanum')) {
    /**
     * Validate a string is alphanumeric with optional extras.
     */
    function validate_alphanum(string $value, bool $allow_spaces = false, bool $allow_dashes = false): ?string
    {
        $pattern = '/^[a-zA-Z0-9' . ($allow_spaces ? '\s' : '') . ($allow_dashes ? '\-' : '') . ']+$/u';

        return preg_match($pattern, $value) ? $value : null;
    }
}

if (! function_exists('validate_bool')) {
    /**
     * Coerce a value to a strict boolean.  Returns null on failure.
     */
    function validate_bool($value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value === 1 ? true : ($value === 0 ? false : null);
        }
        if (is_string($value)) {
            $val = strtolower(trim($value));
            if (in_array($val, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($val, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return null;
    }
}

if (! function_exists('validate_in_list')) {
    /**
     * Validate a value is one of a known whitelist.
     *
     * @param mixed $value
     *
     * @return mixed|null
     */
    function validate_in_list($value, array $allowed)
    {
        return in_array($value, $allowed, true) ? $value : null;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. FILE / PATH SANITIZATION
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('sanitize_filename')) {
    /**
     * Sanitize a filename — strip path separators, null bytes, and
     * directory traversal attempts.
     */
    function sanitize_filename(string $filename, bool $preserve_extension = true): string
    {
        $filename = preg_replace('/[^\w\-.]/', '_', $filename);
        $filename = str_replace(['..', './', '.\\', "\0"], '', $filename);
        if (! $preserve_extension) {
            $filename = pathinfo($filename, PATHINFO_FILENAME);
        }
        $filename = trim($filename, " \t\n\r\0\x0B._-");

        return $filename ?: 'untitled';
    }
}

if (! function_exists('sanitize_filepath')) {
    /**
     * Sanitize a file system path — remove null bytes, directory traversal.
     */
    function sanitize_filepath(string $path): string
    {
        $path = str_replace(['..', "\0", '%00'], '', $path);
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $path = preg_replace('/[<>:"|?*]/', '', $path);

        return $path;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. SQL INJECTION GUARD (supplement for Query Builder)
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('sanitize_sql_like')) {
    /**
     * Escape a string for safe use in a SQL LIKE clause.
     */
    function sanitize_sql_like(string $value): string
    {
        return str_replace(['%', '_'], ['\%', '\_'], $value);
    }
}

if (! function_exists('sanitize_sql_identifier')) {
    /**
     * Sanitize a database identifier (column/table name) — only allow
     * alphanumeric and underscores.  NEVER pass raw user input as identifiers.
     */
    function sanitize_sql_identifier(string $identifier): ?string
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier) ? $identifier : null;
    }
}

if (! function_exists('sanitize_sql_order')) {
    /**
     * Sanitize an ORDER BY direction — only 'ASC' or 'DESC'.
     */
    function sanitize_sql_order(string $direction): string
    {
        $direction = strtoupper(trim($direction));

        return in_array($direction, ['ASC', 'DESC'], true) ? $direction : 'ASC';
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 6. CSRF HARDENING
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('verify_csrf_token')) {
    /**
     * Manually verify a CSRF token against the current session.
     * Returns true if the token is valid.
     */
    function verify_csrf_token(?string $token = null): bool
    {
        $CI = &get_instance();

        if ($token === null) {
            $token_name = config_item('csrf_token_name') ?: 'csrf_token_name';
            $token = $CI->input->post($token_name);
        }

        if (empty($token) || empty($CI->security)) {
            return false;
        }

        $stored = $CI->security->get_csrf_hash();

        return hash_equals((string) $stored, (string) $token);
    }
}

if (! function_exists('ensure_csrf')) {
    /**
     * CSRF gate — if the token is missing or invalid, halt with 403.
     */
    function ensure_csrf(): void
    {
        if (! verify_csrf_token()) {
            show_error('CSRF token validation failed. Please refresh the page and try again.', 403);
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 7. AUTHORIZATION GATE (IDOR prevention)
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('authorize_resource')) {
    /**
     * Verify the current user has ownership of or permission to a resource.
     * $resource_check is a callable that returns bool.
     * On failure, halts with 403.
     */
    function authorize_resource(callable $resource_check, string $error_message = 'Access denied'): void
    {
        if (! $resource_check()) {
            show_error($error_message, 403);
        }
    }
}

if (! function_exists('authorize_ownership')) {
    /**
     * Verify a resource ID belongs to the current user.
     * $user_id_getter returns the user id from a row/model.
     */
    function authorize_ownership(int $resource_user_id, string $error_message = 'Access denied'): void
    {
        $current_user_id = (int) get_staff_user_id();
        if ($current_user_id === 0) {
            $current_user_id = (int) get_client_user_id();
        }
        if ($current_user_id === 0 || $resource_user_id !== $current_user_id) {
            show_error($error_message, 403);
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 8. RATE LIMITING (Brute-force protection)
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('check_rate_limit')) {
    /**
     * Simple rate-limit check using the session or cache.
     * $max_attempts in $window_seconds.
     * Returns true if allowed, false if blocked.
     */
    function check_rate_limit(string $action_key, int $max_attempts = 5, int $window_seconds = 300): bool
    {
        $CI = &get_instance();
        $ip = $CI->input->ip_address();
        $cache_key = 'rate_limit_' . md5($action_key . '_' . $ip);

        $attempts = $CI->app_object_cache->get($cache_key);
        if ($attempts === false) {
            $attempts = ['count' => 0, 'expires' => time() + $window_seconds];
        }

        if (time() > $attempts['expires']) {
            $attempts = ['count' => 0, 'expires' => time() + $window_seconds];
        }

        $attempts['count']++;
        $CI->app_object_cache->add($cache_key, $attempts);

        return $attempts['count'] <= $max_attempts;
    }
}

if (! function_exists('enforce_rate_limit')) {
    /**
     * Rate-limit gate — throws 429 if exceeded.
     */
    function enforce_rate_limit(string $action_key, int $max_attempts = 5, int $window_seconds = 300): void
    {
        if (! check_rate_limit($action_key, $max_attempts, $window_seconds)) {
            show_error('Too many attempts. Please try again later.', 429);
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 9. AUDIT LOGGING
// ─────────────────────────────────────────────────────────────────────────────

if (! function_exists('log_security_event')) {
    /**
     * Write a structured security audit log entry.
     * Logs to the application logs directory with a SEC prefix.
     */
    function log_security_event(string $event, string $details = '', ?int $user_id = null): void
    {
        $CI = &get_instance();
        $ip = $CI->input->ip_address();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : 'unknown';
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'unknown';
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'unknown';

        if ($user_id === null) {
            if (function_exists('get_staff_user_id') && get_staff_user_id()) {
                $user_id = get_staff_user_id();
            } elseif (function_exists('get_client_user_id') && get_client_user_id()) {
                $user_id = get_client_user_id();
            } else {
                $user_id = 0;
            }
        }

        $entry = sprintf(
            "[SEC] %s | User: %d | IP: %s | %s | %s %s | %s\n",
            $event,
            $user_id,
            $ip,
            $details,
            $method,
            $request_uri,
            $user_agent
        );

        log_message('error', $entry);
    }
}

if (! function_exists('log_auth_attempt')) {
    /**
     * Shortcut to log an authentication attempt.
     */
    function log_auth_attempt(string $email, bool $success, string $reason = ''): void
    {
        log_security_event(
            $success ? 'AUTH_SUCCESS' : 'AUTH_FAILURE',
            sprintf('email=%s reason=%s', $email, $reason ?: ($success ? 'ok' : 'invalid_credentials'))
        );
    }
}
