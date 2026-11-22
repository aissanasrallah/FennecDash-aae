<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = $this->ci;

$aColumns = [
    'id',
    'brand',
    'model',
    'engine',
    'model_year',
    'price_eur',
    'total_ttc',
    'price_dzd',
    'customs_estimate',
    'dateupdated',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'aae_catalogue';

$join = [];

$where = [];

$brand = $CI->input->post('brand');
if ($brand !== null && $brand !== '') {
    $where[] = 'AND brand = ' . $CI->db->escape($brand);
}

$model = $CI->input->post('model');
if ($model !== null && $model !== '') {
    $where[] = 'AND model = ' . $CI->db->escape($model);
}

$result = data_tables_init(
    $aColumns,
    $sIndexColumn,
    $sTable,
    $join,
    $where,
    ['color']
);

$output  = $result['output'];
$rResult = $result['rResult'];
$storefrontCars = function_exists('aae_fetch_storefront_cars') ? aae_fetch_storefront_cars() : [];

foreach ($rResult as $aRow) {
    $row = [];

    $row[] = '<span class="aae-id">#' . (int) $aRow['id'] . '</span>';

    $row[] = '<span class="aae-brand">' . e($aRow['brand']) . '</span>';

    $model = '<a href="' . admin_url('aae/catalogue/car/' . $aRow['id']) . '" class="aae-model-link">' . e($aRow['model']) . '</a>';

    if (! empty($aRow['color'])) {
        $model .= '<div class="aae-model-meta">' . e($aRow['color']) . '</div>';
    }

    $siteUrl = function_exists('aae_match_storefront_url') ? aae_match_storefront_url($aRow['brand'], $aRow['model'], $storefrontCars) : '';
    if ($siteUrl !== '') {
        $model .= '<div class="aae-model-meta" style="margin-top:3px;"><a href="' . e($siteUrl) . '" target="_blank" rel="noopener" class="text-success" style="font-size:11.5px;word-break:break-all;"><i class="fa fa-globe"></i> ' . e($siteUrl) . '</a></div>';
    }

    $row[] = $model;

    $row[] = $aRow['engine'] !== '' && $aRow['engine'] !== null
        ? e($aRow['engine'])
        : '<span class="text-muted">-</span>';

    $row[] = (int) $aRow['model_year'] > 0
        ? (int) $aRow['model_year']
        : '<span class="text-muted">-</span>';

    $row[] = '<span class="aae-money">' . aae_format_money($aRow['price_eur'], 'EUR') . '</span>';
    $row[] = '<span class="aae-money">' . aae_format_money($aRow['total_ttc'], 'EUR') . '</span>';

    $dzd = '<span class="aae-money">' . aae_format_money($aRow['price_dzd'], 'DZD') . '</span>';
    if ((float) $aRow['price_millions'] > 0) {
        $dzd .= '<div class="aae-model-meta">' . aae_format_millions($aRow['price_millions']) . '</div>';
    }
    $row[] = $dzd;

    $customs = (float) $aRow['customs_estimate'];
    $row[]   = $customs > 0
        ? '<span class="aae-money">' . aae_format_millions($customs) . ' DZD</span>'
        : '<span class="text-muted">-</span>';

    // Orders count
    $CI->db->where('catalogue_id', (int) $aRow['id']);
    $orders_count = $CI->db->count_all_results(db_prefix() . 'aae_order_vehicles');
    $row[] = $orders_count > 0
        ? '<a href="' . admin_url('aae/aae_order') . '?catalogue_id=' . (int) $aRow['id'] . '" class="aae-orders-count">' . (int) $orders_count . '</a>'
        : '<span class="text-muted">0</span>';

    if (! empty($aRow['dateupdated'])) {
        $row[] = '<span data-toggle="tooltip" title="' . e(_dt($aRow['dateupdated'])) . '">'
            . e(_d($aRow['dateupdated'])) . '</span>';
    } else {
        $row[] = '<span class="text-muted">-</span>';
    }

    $options = '<div class="aae-row-actions">';
    $options .= '<a href="' . admin_url('aae/catalogue/car/' . $aRow['id']) . '" class="btn btn-default btn-icon btn-sm" title="' . _l('edit') . '">'
        . '<i class="fa-regular fa-pen-to-square"></i></a>';

    if (staff_can('delete', 'aae')) {
        $options .= '<a href="' . admin_url('aae/catalogue/delete/' . $aRow['id']) . '" class="btn btn-danger btn-icon btn-sm _delete" title="' . _l('delete') . '">'
            . '<i class="fa-regular fa-trash-can"></i></a>';
    }

    $options .= '</div>';
    $row[] = $options;

    $row['DT_RowClass'] = 'has-row-options';

    $output['aaData'][] = $row;
}