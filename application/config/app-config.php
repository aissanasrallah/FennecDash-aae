<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
| --------------------------------------------------------------------------
| Local environment
| --------------------------------------------------------------------------
| Secrets and per-install values live in the project-root .env file, which is
| never committed. Copy .env.example to .env and fill in the values.
|
| The loader below is intentionally tiny so the application keeps working on
| shared hosting where Composer or phpdotenv may not be available.
*/
$app_env_file = (defined('FCPATH') ? FCPATH : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR) . '.env';

if (is_file($app_env_file)) {
    foreach (file($app_env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $app_env_line) {
        $app_env_line = trim($app_env_line);

        if ($app_env_line === '' || $app_env_line[0] === '#' || strpos($app_env_line, '=') === false) {
            continue;
        }

        list($app_env_key, $app_env_value) = explode('=', $app_env_line, 2);
        $app_env_key   = trim($app_env_key);
        $app_env_value = trim($app_env_value);

        if ($app_env_key === '') {
            continue;
        }

        $app_env_length = strlen($app_env_value);
        if (
            $app_env_length >= 2
            && (($app_env_value[0] === '"' && $app_env_value[$app_env_length - 1] === '"')
                || ($app_env_value[0] === "'" && $app_env_value[$app_env_length - 1] === "'"))
        ) {
            $app_env_value = substr($app_env_value, 1, -1);
        }

        if (getenv($app_env_key) === false) {
            @putenv($app_env_key . '=' . $app_env_value);
            $_ENV[$app_env_key] = $app_env_value;
        }
    }
}

if (! function_exists('app_config_env')) {
    /**
     * Read an environment value with a safe, non-secret default.
     */
    function app_config_env($key, $default = '')
    {
        $value = getenv($key);

        if ($value === false && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        }

        if ($value === false && isset($_SERVER[$key])) {
            $value = $_SERVER[$key];
        }

        return ($value === false || $value === '') ? $default : $value;
    }
}

/*
| --------------------------------------------------------------------------
| Base Site URL
| --------------------------------------------------------------------------
| URL to the CodeIgniter root, with a trailing slash.
*/
define('APP_BASE_URL', app_config_env('APP_BASE_URL', 'http://localhost/'));

/*
| --------------------------------------------------------------------------
| Encryption Key
| IMPORTANT: Do not change this after data has been encrypted.
| --------------------------------------------------------------------------
| Generate one with: php -r "echo bin2hex(random_bytes(16));"
*/
define('APP_ENC_KEY', app_config_env('APP_ENC_KEY', ''));

/*
| --------------------------------------------------------------------------
| Database Credentials
| --------------------------------------------------------------------------
*/
define('APP_DB_HOSTNAME', app_config_env('APP_DB_HOSTNAME', 'localhost'));
define('APP_DB_USERNAME', app_config_env('APP_DB_USERNAME', 'root'));
define('APP_DB_PASSWORD', app_config_env('APP_DB_PASSWORD', ''));
define('APP_DB_NAME', app_config_env('APP_DB_NAME', 'fennecdash'));
define('APP_DB_CHARSET', app_config_env('APP_DB_CHARSET', 'utf8mb4'));
define('APP_DB_COLLATION', app_config_env('APP_DB_COLLATION', 'utf8mb4_unicode_ci'));

/*
| --------------------------------------------------------------------------
| Session handler
| --------------------------------------------------------------------------
| Use the database driver by default. For file sessions:
|   SESS_DRIVER=files
|   SESS_SAVE_PATH=/absolute/path/to/writable/directory
*/
define('SESS_DRIVER', app_config_env('SESS_DRIVER', 'database'));
define('SESS_SAVE_PATH', app_config_env('SESS_SAVE_PATH', 'sessions'));
define('APP_SESSION_COOKIE_SAME_SITE', app_config_env('APP_SESSION_COOKIE_SAME_SITE', 'Lax'));

/*
| --------------------------------------------------------------------------
| Security flags
| --------------------------------------------------------------------------
*/
define('APP_CSRF_PROTECTION', filter_var(app_config_env('APP_CSRF_PROTECTION', 'true'), FILTER_VALIDATE_BOOLEAN));

/*
| URIs excluded from CSRF protection.
| Meta's WhatsApp Cloud API posts webhook events without a CSRF token.
*/
$app_csrf_exclude_uris = [
    'aae/whatsapp/webhook',
    'admin/aae/whatsapp/webhook',
    'admin/aae/aae_order/edit_payment',
    'admin/aae/aae_order/delete_payment',
    'admin/aae/aae_order/edit_payment/\d+',
    'admin/aae/aae_order/delete_payment/\d+',
];

define('APP_COOKIE_HTTPONLY', filter_var(app_config_env('APP_COOKIE_HTTPONLY', 'true'), FILTER_VALIDATE_BOOLEAN));
define('APP_SESSION_REGENERATE_DESTROY', filter_var(app_config_env('APP_SESSION_REGENERATE_DESTROY', 'true'), FILTER_VALIDATE_BOOLEAN));

/*
| --------------------------------------------------------------------------
| Demo Mode
| --------------------------------------------------------------------------
| When enabled, every write request in the admin area is blocked.
*/
define('DEMO_MODE', filter_var(app_config_env('DEMO_MODE', 'false'), FILTER_VALIDATE_BOOLEAN));
