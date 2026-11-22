<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = $this->ci;

$aColumns = [
    'id',
    'brand',
    'model',
    'engine',
    'model_year',
    'quantity',
    'status',
    'price_eur',
    'total_ttc',
    'price_dzd',
    'customs_estimate',
    'invoice_id',
    'dateupdated',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'aae_cars';

$join = [];

$where = [];

$brand = $CI->input->post('brand');
if ($brand !== null && $brand !== '') {
    $where[] = 'AND brand = ' . $CI->db->escape($brand);
}

$status = $CI->input->post('status');
if (in_array($status, aae_statuses(), true)) {
    $where[] = 'AND status = ' . $CI->db->escape($status);
}

$model = $CI->input->post('model');
if ($model !== null && $model !== '') {
    $where[] = 'AND model = ' . $CI->db->escape($model);
}

$clientId = (int) $CI->input->post('client_id');
if ($clientId > 0) {
    $where[] = 'AND client_id = ' . $clientId;
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

    $model = '<a href="' . admin_url('aae/car/' . $aRow['id']) . '" class="aae-model-link">' . e($aRow['model']) . '</a>';

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

    if (staff_can('edit', 'aae')) {
        $row[] = '<input type="number" min="0" step="1" class="form-control input-sm aae-qty-input"'
            . ' data-id="' . (int) $aRow['id'] . '"'
            . ' data-value="' . (int) $aRow['quantity'] . '"'
            . ' value="' . (int) $aRow['quantity'] . '" />';
    } else {
        $row[] = '<span class="aae-qty">' . (int) $aRow['quantity'] . '</span>';
    }

    if (staff_can('edit', 'aae')) {
        $statusSelect = '<select class="form-control input-sm aae-status-select aae-status-' . e($aRow['status']) . '"'
            . ' data-id="' . (int) $aRow['id'] . '"'
            . ' data-value="' . e($aRow['status']) . '">';

        foreach (aae_statuses() as $statusOption) {
            $statusSelect .= '<option value="' . e($statusOption) . '"'
                . ($statusOption === $aRow['status'] ? ' selected' : '')
                . '>' . e(_l('aae_status_' . $statusOption)) . '</option>';
        }

        $statusSelect .= '</select>';
        $row[] = $statusSelect;
    } else {
        $row[] = aae_status_badge($aRow['status']);
    }

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

    if ((int) $aRow['invoice_id'] > 0) {
        $row[] = '<a href="' . aae_invoice_url($aRow['invoice_id']) . '">'
            . e(format_invoice_number($aRow['invoice_id'])) . '</a>';
    } else {
        $row[] = '<span class="text-muted">-</span>';
    }

    if (! empty($aRow['dateupdated'])) {
        $row[] = '<span data-toggle="tooltip" title="' . e(_dt($aRow['dateupdated'])) . '">'
            . e(_d($aRow['dateupdated'])) . '</span>';
    } else {
        $row[] = '<span class="text-muted">-</span>';
    }

    $options = '<div class="aae-row-actions">';
    $options .= '<a href="' . admin_url('aae/car/' . $aRow['id']) . '" class="btn btn-default btn-icon btn-sm" title="' . _l('edit') . '">'
        . '<i class="fa-regular fa-pen-to-square"></i></a>';

    if (staff_can('delete', 'aae')) {
        $options .= '<a href="' . admin_url('aae/delete/' . $aRow['id']) . '" class="btn btn-danger btn-icon btn-sm _delete" title="' . _l('delete') . '">'
            . '<i class="fa-regular fa-trash-can"></i></a>';
    }

    $options .= '</div>';
    $row[] = $options;

    $row['DT_RowClass'] = 'has-row-options';

    $output['aaData'][] = $row;
}
