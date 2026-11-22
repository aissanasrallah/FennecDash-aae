<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Auto Export
Description: Vehicle catalogue, availability, sales tracking and export order dossiers
Version: 1.1.0
Requires at least: 3.4.0
Author: Fennec Vision
Author URI: https://www.fennec-vision.com
*/

define('AAE_MODULE_NAME', 'aae');
define('AAE_MODULE_VERSION', '1.1.0');
define('AAE_ORDER_ATTACHMENTS_FOLDER', FCPATH . 'uploads/aae_orders' . '/');

register_activation_hook(AAE_MODULE_NAME, 'aae_module_activation_hook');
register_language_files(AAE_MODULE_NAME, [AAE_MODULE_NAME]);

hooks()->add_action('admin_init', 'aae_admin_init');
hooks()->add_action('app_admin_head', 'aae_add_head_components');
hooks()->add_action('app_admin_footer', 'aae_add_footer_components');
hooks()->add_filter('get_upload_path_by_type', 'aae_upload_path_by_type', 10, 2);
hooks()->add_filter('get_dashboard_widgets', 'aae_register_dashboard_widgets');

/**
 * Map the module attachment type to its upload folder so the core download
 * controller can serve order documents.
 */
function aae_upload_path_by_type($path, $type)
{
    if ($type === 'aae_order') {
        return AAE_ORDER_ATTACHMENTS_FOLDER;
    }

    return $path;
}

/**
 * Add the Auto Export widgets to the staff dashboard.
 *
 * The widgets are registered through the core filter so staff keep full
 * control over their order and visibility from the dashboard options.
 *
 * @param array $widgets
 *
 * @return array
 */
function aae_register_dashboard_widgets($widgets)
{
    if (! function_exists('staff_can') || staff_cant('orders', 'aae')) {
        return $widgets;
    }

    $widgets[] = [
        'path'      => 'aae/widgets/aae_pipeline',
        'container' => 'middle-left-6',
    ];

    $widgets[] = [
        'path'      => 'aae/widgets/aae_finance',
        'container' => 'middle-right-6',
    ];

    return $widgets;
}

/**
 * Create the module tables and default options when the module is activated.
 */
function aae_module_activation_hook()
{
    $CI = &get_instance();

    require_once __DIR__ . '/install.php';
}

/**
 * Register permissions, menu items and the customer profile tab.
 */
function aae_admin_init()
{
    $CI = &get_instance();
    $CI->load->helper(AAE_MODULE_NAME . '/aae');
    $CI->load->helper(AAE_MODULE_NAME . '/aae_order');

    aae_register_permissions();
    aae_init_menu_items();
    aae_init_customer_profile_tab();
}

function aae_register_permissions()
{
    register_staff_capabilities('aae', [
        'capabilities' => [
            'view'   => _l('aae_permission_view'),
            'create' => _l('aae_permission_create'),
            'edit'   => _l('aae_permission_edit'),
            'delete' => _l('aae_permission_delete'),
            'import' => _l('aae_permission_import'),
            'orders' => _l('aae_permission_orders'),
        ],
    ], _l('aae'));
}

function aae_init_menu_items()
{
    $CI = &get_instance();

    if (staff_cant('view', 'aae')) {
        return;
    }

    $CI->app_menu->add_sidebar_menu_item('aae', [
        'name'     => _l('aae'),
        'href'     => admin_url('aae'),
        'icon'     => 'fa-solid fa-car-side',
        'position' => 12,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('aae', [
        'slug'     => 'aae-catalogue',
        'name'     => _l('aae_catalogue'),
        'icon'     => 'fa-solid fa-folder',
        'href'     => admin_url('aae/catalogue'),
        'position' => 1,
    ]);

    if (staff_can('orders', 'aae')) {
        $CI->app_menu->add_sidebar_children_item('aae', [
            'slug'     => 'aae-orders',
            'name'     => _l('aae_orders'),
            'icon'     => 'fa-solid fa-file-contract',
            'href'     => admin_url('aae/aae_order'),
            'position' => 2,
        ]);
    }

    if (staff_can('import', 'aae')) {
        $CI->app_menu->add_sidebar_children_item('aae', [
            'slug'     => 'aae-import',
            'name'     => _l('aae_import'),
            'icon'     => 'fa-solid fa-file-import',
            'href'     => admin_url('aae/import'),
            'position' => 3,
        ]);
    }

    if (is_admin()) {
        $CI->app_menu->add_sidebar_children_item('aae', [
            'slug'     => 'aae-settings',
            'name'     => _l('aae_settings'),
            'icon'     => 'fa-solid fa-sliders',
            'href'     => admin_url('aae/settings'),
            'position' => 4,
        ]);
    }
}

function aae_init_customer_profile_tab()
{
    if (staff_cant('view', 'aae')) {
        return;
    }

    $CI = &get_instance();

    $CI->app_tabs->add_customer_profile_tab('aae_cars', [
        'name'     => _l('aae_cars'),
        'icon'     => 'fa-solid fa-car',
        'view'     => 'aae/client_profile/cars',
        'position' => 16,
        'badge'    => [],
    ]);

    if (staff_can('orders', 'aae')) {
        $CI->app_tabs->add_customer_profile_tab('aae_orders', [
            'name'     => _l('aae_orders'),
            'icon'     => 'fa-solid fa-file-contract',
            'view'     => 'aae/client_profile/orders',
            'position' => 17,
            'badge'    => [],
        ]);
    }
}

function aae_add_head_components()
{
    if (! aae_is_module_area()) {
        return;
    }

    if (! function_exists('aae_get_option')) {
        get_instance()->load->helper(AAE_MODULE_NAME . '/aae');
    }

    echo '<link rel="stylesheet" href="' . module_dir_url(AAE_MODULE_NAME, 'assets/css/aae.css') . '?v=' . AAE_MODULE_VERSION . '.' . (@filemtime(__DIR__ . '/assets/css/aae.css') ?: 0) . '" />';
}

function aae_add_footer_components()
{
    if (! aae_is_module_area()) {
        return;
    }

    if (! function_exists('aae_get_option')) {
        get_instance()->load->helper(AAE_MODULE_NAME . '/aae');
    }

    $settings = [
        'eurMarkup' => (float) aae_get_option('aae_eur_markup', 0.8),
        'dzdRate'   => (float) aae_get_option('aae_dzd_rate', 282),
        'urls'      => [
            'updateStatus'      => admin_url('aae/update_status'),
            'updateQuantity'    => admin_url('aae/update_quantity'),
            'invoicesForClient' => admin_url('aae/invoices_for_client/'),
            'whatsappTest'      => admin_url('aae/whatsapp/test_connection'),
            'whatsappSendApi'   => admin_url('aae/aae_order/send_whatsapp_api/'),
        ],
        'lang' => [
            'noInvoices'   => _l('aae_no_invoices'),
            'noInvoice'    => _l('aae_no_invoice'),
            'loading'      => _l('aae_loading'),
            'error'        => _l('aae_request_failed'),
            'noPhone'      => _l('aae_message_no_phone'),
            'emptyMessage' => _l('aae_message_empty'),
        ],
    ];
    ?>
    <script>
        window.aaeSettings = <?php echo json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?php echo module_dir_url(AAE_MODULE_NAME, 'assets/js/aae.js') . '?v=' . AAE_MODULE_VERSION . '.' . (@filemtime(__DIR__ . '/assets/js/aae.js') ?: 0); ?>"></script>
    <?php
}

/**
 * Assets are only needed on the module pages and on the customer profile tab.
 */
function aae_is_module_area()
{
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

    return strpos($uri, '/aae') !== false || strpos($uri, '/clients/client') !== false;
}
