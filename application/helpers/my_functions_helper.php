<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Register anti-inspect deterrent — one function per zone
 */
hooks()->add_action('app_admin_footer', 'FennecCRM_render_anti_inspect_admin');
hooks()->add_action('app_customers_footer', 'FennecCRM_render_anti_inspect_client');
hooks()->add_action('app_external_form_head', 'FennecCRM_render_anti_inspect_external');

/**
 * Register default options on admin_init (settings_helper is available by then)
 */
hooks()->add_action('admin_init', 'FennecCRM_register_inspect_options');

function FennecCRM_register_inspect_options()
{
    if (!function_exists('add_option')) {
        return;
    }
    add_option('inspect_management_enabled', '0', 1);
    add_option('inspect_management_alert_message', '[Fennec Dash Security]: Ya khouya wash rak t\'boussi hna? Inspect element is locked! Fox', 1);
    add_option('inspect_management_console_message', 'Fennec Dash: Stop inspecting! System core is fully protected.', 1);
    add_option('inspect_management_client_area', '1', 1);
    add_option('inspect_management_public_forms', '1', 1);
}

/**
 * Anti-inspect JS for admin area (uses alert_float)
 */
function FennecCRM_render_anti_inspect_admin()
{
    if (get_option('inspect_management_enabled') !== '1') {
        return;
    }

    $alertMsg   = get_option('inspect_management_alert_message');
    $consoleMsg = get_option('inspect_management_console_message');

    FennecCRM_output_anti_inspect_js($alertMsg, $consoleMsg, 'admin');
}

/**
 * Anti-inspect JS for client area (uses alert_float if available)
 */
function FennecCRM_render_anti_inspect_client()
{
    if (get_option('inspect_management_enabled') !== '1' || get_option('inspect_management_client_area') !== '1') {
        return;
    }

    $alertMsg   = get_option('inspect_management_alert_message');
    $consoleMsg = get_option('inspect_management_console_message');

    FennecCRM_output_anti_inspect_js($alertMsg, $consoleMsg, 'client');
}

/**
 * Anti-inspect JS for public forms (no jQuery, plain alert())
 */
function FennecCRM_render_anti_inspect_external()
{
    if (get_option('inspect_management_enabled') !== '1' || get_option('inspect_management_public_forms') !== '1') {
        return;
    }

    $alertMsg   = get_option('inspect_management_alert_message');
    $consoleMsg = get_option('inspect_management_console_message');

    FennecCRM_output_anti_inspect_js($alertMsg, $consoleMsg, 'external');
}

/**
 * Render the anti-inspect JS block
 */
function FennecCRM_output_anti_inspect_js($alertMessage, $consoleMessage, $zone)
{
    $alertMessageJs   = json_encode($alertMessage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $consoleMessageJs = json_encode($consoleMessage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    ?>
    <script>
    (function() {
        var alertMsg = <?= $alertMessageJs; ?>;
        var consoleMsg = <?= $consoleMessageJs; ?>;

        function triggerAlert() {
            <?php if ($zone === 'external'): ?>
            alert(alertMsg);
            <?php else: ?>
            if (typeof alert_float === 'function') {
                alert_float('danger', alertMsg);
            } else {
                alert(alertMsg);
            }
            <?php endif; ?>

            console.log(
                '%c🦊 Fennec Dash: %c' + consoleMsg,
                'color: #7367f0; font-size: 20px; font-weight: bold;',
                'color: #ff9f43; font-size: 14px;'
            );
        }

        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            triggerAlert();
        });

        document.addEventListener('keydown', function(e) {
            if (
                e.keyCode === 123 ||
                (e.ctrlKey && e.shiftKey && (e.keyCode === 73 || e.keyCode === 74)) ||
                (e.ctrlKey && e.keyCode === 85)
            ) {
                e.preventDefault();
                triggerAlert();
            }
        });
    })();
    </script>
    <?php
}
