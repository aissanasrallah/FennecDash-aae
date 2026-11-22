<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Required app theme head hook
 */
hooks()->add_action('app_customers_head', 'app_theme_head_hook');

/**
 * Default theme menu items
 * In most cases you will want to add this hook because of all the features
 */
hooks()->add_action('clients_init', 'add_default_theme_menu_items');

register_theme_assets_hook('theme_assets');

function theme_assets()
{
    $CI = &get_instance();

    $groupName = $CI->app_scripts->default_theme_group();

    $CI->app_scripts->theme('bootstrap-js', 'assets/plugins/bootstrap/js/bootstrap.min.js');

    add_datatables_js_assets($groupName);
    add_jquery_validation_js_assets($groupName);
    add_bootstrap_select_js_assets($groupName);

    $CI->app_scripts->theme('datetimepicker-js', 'assets/plugins/datetimepicker/jquery.datetimepicker.full.min.js');
    $CI->app_scripts->theme('colorpicker-js', 'assets/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js');
    $CI->app_scripts->theme('lightbox-js', 'assets/plugins/lightbox/js/lightbox.min.js');

    if (is_client_logged_in()) {
        $CI->app_scripts->theme('dropzone-js', 'assets/plugins/dropzone/min/dropzone.min.js');
        $CI->app_scripts->theme('circle-progress-js', 'assets/plugins/jquery-circle-progress/circle-progress.min.js');

        $CI->app_scripts->theme('jquery-comments-js', 'assets/plugins/jquery-comments/js/jquery-comments.min.js');
        $CI->app_scripts->theme('frape-gantt-js', 'assets/plugins/frappe/frappe-gantt-es2015.js');
        $CI->app_scripts->theme('apexcharts-js', 'assets/plugins/apexcharts/apexcharts.min.js');
        $CI->app_scripts->theme('fennec-charts-js', 'assets/js/fennec-charts.js', ['apexcharts-js']);
        add_moment_js_assets($groupName);
        add_dropbox_js_assets($groupName);

        $CI->app_css->theme('jquery-comments-css', 'assets/plugins/jquery-comments/css/jquery-comments.css');
        $CI->app_css->theme('frappe-gantt-css', 'assets/plugins/frappe/frappe-gantt.css');
        add_calendar_assets($groupName);

        if (get_option('enable_google_picker') == '1') {
            add_google_api_js_assets($groupName);
        }
    }

    $CI->app_scripts->theme('common-js', 'assets/builds/common.js');

    $CI->app_scripts->theme(
        'theme-global-js',
        base_url($CI->app_scripts->core_file(theme_assets_path() . '/js', 'global.js')) . '?v=' . time(),
        ['common-js']
    );

    if (is_client_logged_in()) {
        $CI->app_scripts->theme(
            'theme-js',
            base_url($CI->app_scripts->core_file(theme_assets_path() . '/js', 'clients.js')) . '?v=' . time(),
            ['common-js']
        );
    }

    // CSS
    add_favicon_link_asset($groupName);

    $CI->app_css->theme(
        'reset-css',
        base_url($CI->app_css->core_file('assets/css', 'reset.css')) . '?v=' . $CI->app_css->core_version()
    );

    $CI->app_css->theme('bootstrap-css', 'assets/plugins/bootstrap/css/bootstrap.min.css');
    // $CI->app_css->theme('roboto-css', 'assets/plugins/roboto/roboto.css');
    $CI->app_css->theme('inter-font', 'assets/plugins/inter/inter.css');

    if (is_rtl()) {
        $CI->app_css->theme('bootstrap-rtl-css', 'assets/plugins/bootstrap-arabic/css/bootstrap-arabic.min.css');
    }

    $CI->app_css->theme('datatables-css', 'assets/plugins/datatables/datatables.min.css');

    $CI->app_css->theme('fontawesome-css', 'assets/plugins/font-awesome/css/fontawesome.min.css');
    $CI->app_css->theme('fontawesome-brands', 'assets/plugins/font-awesome/css/brands.min.css');
    $CI->app_css->theme('fontawesome-solid', 'assets/plugins/font-awesome/css/solid.min.css');
    $CI->app_css->theme('fontawesome-regular', 'assets/plugins/font-awesome/css/regular.min.css');
    $CI->app_css->theme('iconify-css', 'assets/vendor/fonts/iconify-icons.css');

    $CI->app_css->theme('datetimepicker-css', 'assets/plugins/datetimepicker/jquery.datetimepicker.min.css');
    $CI->app_css->theme('bootstrap-select-css', 'assets/plugins/bootstrap-select/css/bootstrap-select.min.css');

    if (is_client_logged_in()) {
        $CI->app_css->theme('dropzone-basic-css', 'assets/plugins/dropzone/min/basic.min.css');
        $CI->app_css->theme('dropzone-css', 'assets/plugins/dropzone/min/dropzone.min.css');
    }

    $CI->app_css->theme('lightbox-css', 'assets/plugins/lightbox/css/lightbox.min.css');
    $CI->app_css->theme('colorpicker-css', 'assets/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css');

    $CI->app_css->theme('tailwind-css', base_url($CI->app_css->core_file('assets/builds', 'tailwind.css')) . '?v=' . $CI->app_css->core_version(), ['bootstrap-css']);

    $CI->app_css->theme(
        'theme-css',
        base_url($CI->app_scripts->core_file(theme_assets_path() . '/css', 'style.css')) . '?v=' . time()
    );
}

/**
 * Demo mode — disable all client-side mutations (add, edit, delete)
 */
hooks()->add_action('app_customers_footer', 'fennec_demo_mode_js');

function fennec_demo_mode_js()
{
    if (!defined('DEMO_MODE') || !DEMO_MODE) {
        return;
    }
    if (!is_client_logged_in()) {
        return; // Don't block login/register pages
    }
    $msg = _l('demo_mode_action_not_allowed'); ?>
<script>
(function() {
    var msg = '<?= e($msg); ?>';
    // 1. Destroy Dropzone instances (file upload via AJAX bypasses form submit)
    (function destroyDropzone() {
        if (typeof Dropzone !== 'undefined' && Dropzone.instances) {
            Dropzone.instances.forEach(function(dz) { dz.destroy(); });
            // Also hide the upload zone visually
            var dzEls = document.querySelectorAll('.dropzone');
            for (var i = 0; i < dzEls.length; i++) {
                dzEls[i].style.pointerEvents = 'none';
                dzEls[i].style.opacity = '0.5';
            }
        } else {
            setTimeout(destroyDropzone, 500);
        }
    })();
    // 2. Disable all submit buttons visually
    document.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function(b) {
        b.disabled = true;
        b.title = msg;
    });
    // 3. Block mutating clicks only — allow all navigation — capture phase
    document.addEventListener('click', function(e) {
        var t = e.target.closest('a, button, input, [onclick]');
        if (!t) return;
        // <a> links = navigation. Only block if it's a delete/remove link
        if (t.tagName === 'A') {
            var href = t.getAttribute('href') || '';
            if (href.indexOf('delete') > -1 || href.indexOf('remove') > -1) {
                e.preventDefault();
                e.stopImmediatePropagation();
                alert(msg);
            }
            return;
        }
        // Allow safe non-link elements
        if (t.matches('.theme-toggle, .navbar-toggle, .mobile-nav-toggle, .mobile-menu-close, .mobile-menu-overlay, [data-bs-theme-value]')) return;
        // Block all other buttons/inputs/onclick
        e.preventDefault();
        e.stopImmediatePropagation();
        alert(msg);
    }, true);
    // 4. Safety net: block ALL form submissions (capture phase)
    document.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        alert(msg);
    }, true);
})();
</script>
<?php
}

/**
 * Render both dark-ink and light-ink company logos so client document previews
 * (invoice, estimate, proposal, contract, subscription) stay visible in both light and dark modes.
 */
function fennec_theme_doc_logo($uri = '')
{
    return get_dark_company_logo($uri, 'fennec-doc-logo-dark')
         . get_company_logo($uri, 'fennec-doc-logo-light');
}
