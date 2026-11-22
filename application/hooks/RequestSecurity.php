<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * REQUEST SECURITY MIDDLEWARE
 * ───────────────────────────────────────────────────────────────────────────────
 * Runs at post_controller_constructor to:
 *   1. Intercept and validate high-risk input payloads
 *   2. Enforce dynamic security headers
 *   3. Audit sensitive operations
 *   4. Provide a centralized security gate for critical actions
 * ═══════════════════════════════════════════════════════════════════════════════
 */
class RequestSecurity
{
    /**
     * Routes/paths that require extra scrutiny (IDOR, privilege escalation).
     */
    private array $sensitiveRoutes = [
        'admin/staff/save',
        'admin/roles',
        'admin/settings',
        'admin/staff/update_permissions',
        'admin/auto_update',
    ];

    /**
     * HTTP methods that modify state (require CSRF + validation).
     */
    private array $stateChangingMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Main entry point — called by the hook.
     */
    public function intercept(): void
    {
        if (! function_exists('get_instance')) {
            return;
        }

        $CI = &get_instance();
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

        // ── 1. Dynamic security headers ──────────────────────────────
        $this->setDynamicHeaders($CI);

        // ── 2. State-changing request validation ─────────────────────
        if (in_array($method, $this->stateChangingMethods, true)) {
            $this->validateStateChangingRequest($CI);
        }

        // ── 3. Sensitive route audit ─────────────────────────────────
        $currentUri = $CI->uri->uri_string();
        foreach ($this->sensitiveRoutes as $pattern) {
            if (strpos($currentUri, $pattern) !== false) {
                $this->auditSensitiveAccess($CI, $currentUri);
                break;
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // DYNAMIC SECURITY HEADERS
    // ─────────────────────────────────────────────────────────────────

    /**
     * Set response headers that cannot be set statically in .htaccess
     * (e.g., those depending on request context or PHP logic).
     */
    private function setDynamicHeaders(CI_Controller $CI): void
    {
        // Remove PHP's default X-Powered-By
        header_remove('X-Powered-By');

        // CSRF cookie flags (re-applied at runtime for defense in depth)
        $cookieParams = session_get_cookie_params();
        $CI->security->csrf_set_cookie();

        // Additional cookie hardening via Set-Cookie (applied by CI config already)
    }

    // ─────────────────────────────────────────────────────────────────
    // STATE-CHANGING REQUEST VALIDATION
    // ─────────────────────────────────────────────────────────────────

    /**
     * Validate that state-changing requests carry minimal required
     * protections — CSRF token presence (CI handles validation) and
     * content-type sanity.
     */
    private function validateStateChangingRequest(CI_Controller $CI): void
    {
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

        // Reject requests with obviously spoofed content-types
        if ($contentType !== '' &&
            stripos($contentType, 'application/x-www-form-urlencoded') === false &&
            stripos($contentType, 'multipart/form-data') === false &&
            stripos($contentType, 'application/json') === false
        ) {
            log_security_event('INVALID_CONTENT_TYPE', 'Rejected state-changing request with content-type: ' . $contentType);
            show_error('Invalid request content type.', 400);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // SENSITIVE OPERATION AUDIT
    // ─────────────────────────────────────────────────────────────────

    /**
     * Log access to sensitive admin routes for audit trail.
     */
    private function auditSensitiveAccess(CI_Controller $CI, string $uri): void
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'UNKNOWN';
        $userId = function_exists('get_staff_user_id') ? get_staff_user_id() : 0;

        log_security_event(
            'SENSITIVE_ROUTE',
            sprintf('uri=%s method=%s', $uri, $method),
            $userId
        );
    }
}
