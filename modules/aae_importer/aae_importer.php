<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * AAE Import Manager
 * Module pour Algerie Auto Export :
 * - Catalogue de véhicules chinois (import CSV du catalogue)
 * - Suivi des opérations d'import (commande -> transit -> dédouanement -> livraison)
 * - Devis rapides vers Perfex (Estimates) depuis le catalogue
 * - Catalogue lisible par les clients (espace client)
 */

/*
 Module Name: AAE Import Manager
 Module URI: https://algerieautoexport.com
 Description: Gestion complète des activités d'import de véhicules : catalogue véhicules, suivi des opérations (transport/dédouanement), devis rapides depuis le catalogue et portail client.
 Version: 1.0.0
 Requires at least: 3.0.*
 Author: AAE / Fennec Vision
 Author URI: https://algerieautoexport.com
*/

if (!defined('AAE_IMPORTER_MODULE_NAME')) {
    define('AAE_IMPORTER_MODULE_NAME', 'aae_importer');
    define('AAE_IMPORTER_VERSION', '1.0.0');
}

register_activation_hook(AAE_IMPORTER_MODULE_NAME, function () {
    require_once __DIR__ . '/install.php';
    aae_importer_run_install(true);
});

register_deactivation_hook(AAE_IMPORTER_MODULE_NAME, function () {
    require_once __DIR__ . '/install.php';
    aae_importer_ensure_version();
});

register_uninstall_hook(AAE_IMPORTER_MODULE_NAME, 'aae_importer_module_uninstall');

function aae_importer_module_uninstall()
{
    require_once __DIR__ . '/uninstall.php';
    aae_importer_run_uninstall();
}

hooks()->add_action('admin_init', 'aae_importer_admin_init');
hooks()->add_action('clients_init', 'aae_importer_clients_init');

register_language_files(AAE_IMPORTER_MODULE_NAME);

/**
 * Admin init : schema, menu, permissions
 */
function aae_importer_admin_init()
{
    // Mise a jour du schema / version (light upgrade path)
    require_once __DIR__ . '/install.php';
    aae_importer_run_install(false);

    $CI = &get_instance();

    register_staff_capabilities('aae_importer', [
        'capabilities' => [
            'view'   => _l('aae_perm_view'),
            'create' => _l('aae_perm_create'),
            'edit'   => _l('aae_perm_edit'),
            'delete' => _l('aae_perm_delete'),
        ],
    ], _l('aae_module_name'));

    if (!function_exists('staff_can') || !staff_can('view', 'aae_importer')) {
        return;
    }

    $CI->app_menu->add_sidebar_menu_item('aae_importer', [
        'name'     => _l('aae_module_name'),
        'href'     => admin_url('aae_importer'),
        'position' => 13,
        'icon'     => 'fa fa-car',
        'collapse' => true,
    ]);

    $CI->app_menu->add_sidebar_children_item('aae_importer', [
        'slug'     => 'aae_importer_dashboard',
        'name'     => _l('aae_menu_dashboard'),
        'href'     => admin_url('aae_importer'),
        'position' => 5,
        'icon'     => 'fa fa-tachometer',
    ]);

    $CI->app_menu->add_sidebar_children_item('aae_importer', [
        'slug'     => 'aae_importer_catalogue',
        'name'     => _l('aae_menu_catalogue'),
        'href'     => admin_url('aae_importer/catalogue'),
        'position' => 10,
        'icon'     => 'fa fa-car',
    ]);

    $CI->app_menu->add_sidebar_children_item('aae_importer', [
        'slug'     => 'aae_importer_sync_prices',
        'name'     => 'Synchroniser les prix',
        'href'     => admin_url('aae_importer/sync_prices'),
        'position' => 12,
        'icon'     => 'fa fa-refresh',
    ]);

    $CI->app_menu->add_sidebar_children_item('aae_importer', [
        'slug'     => 'aae_importer_operations',
        'name'     => _l('aae_menu_operations'),
        'href'     => admin_url('aae_importer/operations'),
        'position' => 15,
        'icon'     => 'fa fa-ship',
    ]);

    $CI->app_menu->add_sidebar_children_item('aae_importer', [
        'slug'     => 'aae_importer_import_csv',
        'name'     => _l('aae_menu_import_csv'),
        'href'     => admin_url('aae_importer/import_csv'),
        'position' => 20,
        'icon'     => 'fa fa-file-excel-o',
    ]);

    if (is_admin()) {
        $CI->app_menu->add_sidebar_children_item('aae_importer', [
            'slug'     => 'aae_importer_settings',
            'name'     => _l('aae_menu_settings'),
            'href'     => admin_url('aae_importer/settings'),
            'position' => 25,
            'icon'     => 'fa fa-cog',
        ]);
    }
}

/**
 * Clients area : menu item + page catalogue
 */
function aae_importer_clients_init()
{
    add_theme_menu_item('aae_importer_catalogue_client', [
        'name'     => _l('aae_client_menu_item'),
        'href'     => site_url('aae_importer/catalogue'),
        'position' => 32,
    ]);
}

/**
 * Helper : champ CSRF a inclure dans les formulaires (utile aussi pour les posts AJAX)
 */
if (!function_exists('aae_importer_csrf_field')) {
    function aae_importer_csrf_field()
    {
        $CI = &get_instance();
        $name = $CI->security->get_csrf_token_name();
        $hash = $CI->security->get_csrf_hash();
        return '<input type="hidden" name="' . $name . '" value="' . $hash . '" />';
    }
}

/**
 * Helper : nettoie un montant texte ("8 493 €" -> 8493)
 */
if (!function_exists('aae_importer_money_value')) {
    function aae_importer_money_value($value)
    {
        if ($value === null) {
            return null;
        }
        $value = strip_tags((string)$value);
        // espaces normaux, insécables, étroits
        $value = str_replace(["\xC2\xA0", "\xE2\x80\x89", "\xE2\x80\xAF", "\x20", "\x09"], '', $value);
        $value = preg_replace('/[^\d\.,\-]/u', '', $value);
        $value = str_replace(',', '.', $value);
        // "2388.129" venant de milliers : pas de remplacement car on a retiré les espaces avant le point
        if ($value === '' || $value === '-') {
            return 0;
        }
        return (float)$value;
    }
}

/**
 * Helper : texte d'en-tete CSV normalise (accents retires)
 */
if (!function_exists('aae_importer_slug_header')) {
    function aae_importer_slug_header($s)
    {
        $s = trim(strip_tags((string)$s));
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            if ($converted !== false && $converted !== null) {
                $s = $converted;
            }
        }
        $s = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $s));
        return trim($s);
    }
}

/**
 * Helper : contient (compatible PHP 7.4, repli de str_contains)
 */
if (!function_exists('aae_importer_contains')) {
    function aae_importer_contains($haystack, $needle)
    {
        if ((string)$needle === '') {
            return true;
        }

        return strpos((string)$haystack, (string)$needle) !== false;
    }
}

/**
 * Helper : commence par (compatible PHP 7.4, repli de str_starts_with)
 */
if (!function_exists('aae_importer_starts_with')) {
    function aae_importer_starts_with($haystack, $needle)
    {
        $haystack = (string)$haystack;
        $needle   = (string)$needle;
        if ($needle === '') {
            return true;
        }

        return strpos($haystack, $needle) === 0;
    }
}

/**
 * Helper : la page admin courante appartient-elle au module ?
 */
if (!function_exists('aae_importer_on_admin_page')) {
    function aae_importer_on_admin_page()
    {
        $CI = &get_instance();

        return $CI->uri->segment(1) === 'admin' && $CI->uri->segment(2) === AAE_IMPORTER_MODULE_NAME;
    }
}

/**
 * Helper : URL d'un asset du module (avec cache-busting)
 */
if (!function_exists('aae_importer_asset_url')) {
    function aae_importer_asset_url($path)
    {
        $url = function_exists('module_dir_url')
            ? module_dir_url(AAE_IMPORTER_MODULE_NAME, 'assets/' . $path)
            : base_url('modules/' . AAE_IMPORTER_MODULE_NAME . '/assets/' . $path);

        return $url . '?v=' . AAE_IMPORTER_VERSION;
    }
}

/**
 * CSS / JS du module dans l'entete et le pied de page admin
 * (charge uniquement sur les pages admin/aae_importer/*)
 */
hooks()->add_action('app_admin_head', 'aae_importer_admin_head');
hooks()->add_action('app_admin_footer', 'aae_importer_admin_footer');

if (!function_exists('aae_importer_admin_head')) {
    function aae_importer_admin_head()
    {
        if (!aae_importer_on_admin_page()) {
            return;
        }

        echo '<link href="' . aae_importer_asset_url('css/aae_importer.css') . '" rel="stylesheet" id="aae-importer-css">' . "\n";
    }
}

if (!function_exists('aae_importer_admin_footer')) {
    function aae_importer_admin_footer()
    {
        if (!aae_importer_on_admin_page()) {
            return;
        }

        echo '<script src="' . aae_importer_asset_url('js/aae_importer.js') . '" id="aae-importer-js"></script>' . "\n";
    }
}
