<?php

/**
 * Resolve private non-webroot base directory for QuickShare file storage
 * @return string
 */
function qs_private_upload_base()
{
    if (defined('APP_PRIVATE_STORAGE_PATH') && APP_PRIVATE_STORAGE_PATH !== '') {
        $base = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, APP_PRIVATE_STORAGE_PATH), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'quick_share' . DIRECTORY_SEPARATOR;
    } else {
        $webroot = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, FCPATH), DIRECTORY_SEPARATOR);
        $instance = basename($webroot) ?: 'default';
        $base = dirname($webroot) . DIRECTORY_SEPARATOR . 'private_storage' . DIRECTORY_SEPARATOR . $instance . DIRECTORY_SEPARATOR . 'quick_share' . DIRECTORY_SEPARATOR;
    }
    if (!is_dir($base)) {
        @mkdir($base, 0750, true);
    }
    return $base;
}

/**
 * Resolve a safe local QuickShare file path strictly within private or legacy storage
 * @param int $fileId
 * @param string $storedFilePath
 * @return string|false
 */
function qs_resolve_private_file_path($fileId, $storedFilePath)
{
    $safeName = basename((string)$storedFilePath);
    if ($safeName === '' || $safeName === '.' || $safeName === '..') {
        return false;
    }
    $id = (int)$fileId;
    if ($id <= 0) {
        return false;
    }
    $roots = [
        qs_private_upload_base(),
        rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'quick_share' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR,
    ];
    foreach ($roots as $root) {
        $realRoot = realpath($root);
        if ($realRoot === false) {
            continue;
        }
        $real = realpath($realRoot . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . $safeName);
        if ($real !== false && is_file($real) && strpos($real, rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) === 0) {
            return $real;
        }
    }
    return false;
}

/**
 * Check if extension is allowed for upload
 * @param  string $filename filename
 * @return boolean
 */
function qs_upload_extension_allowed($filename)
{
    $extension = strtolower(pathinfo((string)$filename, PATHINFO_EXTENSION));
    $blocked_executables = [
        'php', 'phtml', 'phar', 'pht', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps',
        'cgi', 'pl', 'py', 'sh', 'exe', 'bat', 'cmd', 'com', 'msi', 'dll', 'vbs', 'js', 'html', 'htm', 'svg', 'htaccess'
    ];
    if ($extension === '' || in_array($extension, $blocked_executables, true)) {
        return false;
    }

    $browser = get_instance()->agent->browser();

    $allowed_extensions = explode(',', get_option('qs_allowed_file_extensions'));
    $allowed_extensions = array_map('trim', $allowed_extensions);

    //  https://discussions.apple.com/thread/7229860
    //  Used in main.js too for Dropzone
    if (strtolower($browser) === 'safari'
        && in_array('.jpg', $allowed_extensions)
        && !in_array('.jpeg', $allowed_extensions)
    ) {
        $allowed_extensions[] = '.jpeg';
    }
    // Check for all cases if this extension is allowed
    if (!in_array('.' . $extension, $allowed_extensions)) {
        return false;
    }

    return true;
}