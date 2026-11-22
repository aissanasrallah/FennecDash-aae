<?php

defined('BASEPATH') or exit('No direct script access allowed');

$has_permission_delete = has_permission('staff', '', 'delete');

$shTbl = db_prefix() . 'lg_shippings';
$aColumns = [
    $shTbl . '.number_code',
    $shTbl . '.created_at',
    $shTbl . '.customer_id',
    $shTbl . '.recipient_id',
    $shTbl . '.recipient_address_id',
    $shTbl . '.courrier_company',
    $shTbl . '.transport_type',
    $shTbl . '.planned_delivery_date',
    $shTbl . '.customer_address',
    $shTbl . '.payment_term_id',
    $shTbl . '.store_supplier',
    $shTbl . '.tracking_purchase',
    $shTbl . '.delivery_status',
];

$join = [
    'LEFT JOIN ' . db_prefix() . 'invoices ON ' . db_prefix() . 'invoices.id = ' . $shTbl . '.invoice_id',
    'LEFT JOIN ' . db_prefix() . 'lg_vehicles ON ' . db_prefix() . 'lg_vehicles.id = ' . $shTbl . '.vehicle_id',
    'LEFT JOIN ' . db_prefix() . 'lg_shipping_companies ON ' . db_prefix() . 'lg_shipping_companies.id = ' . $shTbl . '.courrier_company',
];

$sIndexColumn = 'id';
$sTable       = $shTbl;

$where  = [];

$user = get_staff_user_id();
if(!has_permission('lg_shipping', '', 'view')){
    array_push($where, 'AND (' . $shTbl . '.created_by = '.$user.' OR ' . $shTbl . '.assign_driver = '.$user.')');
}

if ($this->ci->input->post('status') && count($this->ci->input->post('status')) > 0) {
    $statusIds = array_map('intval', $this->ci->input->post('status'));
    array_push($where, 'AND ' . $shTbl . '.delivery_status IN (' . implode(',', $statusIds) . ')');
}

if ($this->ci->input->post('clients') && count($this->ci->input->post('clients')) > 0) {
    $clientIds = array_map('intval', $this->ci->input->post('clients'));
    array_push($where, 'AND ' . $shTbl . '.customer_id IN (' . implode(',', $clientIds) . ')');
}

if ($this->ci->input->post('transport_type') && in_array($this->ci->input->post('transport_type'), ['internal', 'external'], true)) {
    array_push($where, 'AND ' . $shTbl . '.transport_type = ' . $this->ci->db->escape($this->ci->input->post('transport_type')));
}

if ($this->ci->input->post('from_date')
    && $this->ci->input->post('from_date') != '') {
    array_push($where, 'AND date(' . $shTbl . '.created_at) >= ' . $this->ci->db->escape(to_sql_date($this->ci->input->post('from_date'))));
}

if ($this->ci->input->post('to_date')
    && $this->ci->input->post('to_date') != '') {
    array_push($where, 'AND date(' . $shTbl . '.created_at) <= ' . $this->ci->db->escape(to_sql_date($this->ci->input->post('to_date'))));
}

if(isset($shipping_type) && $shipping_type == 'pickup'){
     array_push($where, 'AND ' . $shTbl . '.shipping_type = "pickup"');
}else{
    array_push($where, 'AND (' . $shTbl . '.shipping_type IS NULL or ' . $shTbl . '.shipping_type = "shipping")');
}

if (!class_exists('Invoices_model', false)) {
    $this->load->model('invoices_model');
}


$filter = [];


$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    $shTbl . '.id as shipping_id',
    $shTbl . '.shipping_prefix as shipping_prefix',
    $shTbl . '.number_code as number_code',
    $shTbl . '.created_at as created_at',
    $shTbl . '.customer_id as customer_id',
    $shTbl . '.recipient_id as recipient_id',
    $shTbl . '.customer_address as customer_address',
    $shTbl . '.recipient_address_id as recipient_address_id',
    $shTbl . '.payment_term_id as payment_term_id',
    $shTbl . '.delivery_status as delivery_status',
    $shTbl . '.tracking_purchase as tracking_purchase',
    db_prefix().'invoices.status as invoice_status',
    'CONCAT(' . $shTbl . '.shipping_prefix, \'\', ' . $shTbl . '.number_code) as tracking_number',
    $shTbl . '.shipping_type as shipping_type',
    $shTbl . '.assign_driver as assign_driver',
    $shTbl . '.transport_type as transport_type',
    $shTbl . '.planned_delivery_date as planned_delivery_date',
    $shTbl . '.vehicle_id as vehicle_id',
    $shTbl . '.courrier_company as courrier_company',
    $shTbl . '.external_carrier_ref as external_carrier_ref',
    $shTbl . '.external_agreed_price as external_agreed_price',
    $shTbl . '.delivery_notes as delivery_notes',
    db_prefix().'lg_vehicles.vehicle_code as vehicle_code',
    db_prefix().'lg_vehicles.registration_plate as registration_plate',
    db_prefix().'lg_shipping_companies.shipping_company_name as shipping_company_name',
    $shTbl . '.total as package_total',
    $shTbl . '.invoice_id as invoice_id',
    $shTbl . '.currency as package_currency',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $invoice_status_str = '';
    $invoice_status = '';
    if(is_numeric($aRow['invoice_id']) && $aRow['invoice_id'] > 0){
        $invoice_status_str = '<a href="'.admin_url('invoices/list_invoices/'.$aRow['invoice_id']).'">'.format_invoice_number($aRow['invoice_id']).'</a>&nbsp;'.format_invoice_status($aRow['invoice_status']);
    }else{
        $invoice_status = 'pending';
        $invoice_status_str = '<span class="label label-warning">'._l('lg_pending').'</span>';
    }


    $numberOutput = '<a href="'.admin_url('logistic/shipping_detail/'.$aRow['shipping_id']).'">'.$aRow['shipping_prefix'].$aRow['number_code'].'</a>';

    $numberOutput .= '<div class="row-options">';

    if (has_permission('lg_shipping', '', 'edit') && !is_driver_staff() ) {
        $numberOutput .= ' <a href="' . admin_url('logistic/shipment/0/' . $aRow['shipping_id']) . '">' . _l('edit') . '</a>';
    }
    if (has_permission('lg_shipping', '', 'delete') && $invoice_status == 'pending' && !is_driver_staff()) {
        $numberOutput .= ' | <a href="' . admin_url('logistic/delete_shipping/' . $aRow['shipping_id']) . '" class="text-danger _delete">' . _l('lg_delete') . '</a>';
    }
    $numberOutput .= '</div>';

    $clientFallbackAddr = lg_get_client_fallback_address($aRow['customer_id']);
    $originAddr = lg_get_customer_address_str($aRow['customer_address']);
    if ($originAddr === '') {
        $originAddr = $clientFallbackAddr !== '' ? e($clientFallbackAddr) : '-';
    }
    $destAddr = lg_get_recipient_address_str($aRow['recipient_address_id']);
    if ($destAddr === '') {
        $destAddr = $clientFallbackAddr !== '' ? e($clientFallbackAddr) : '-';
    }
    $recipientName = lg_get_recipient_name($aRow['recipient_id']);
    if ($recipientName === '') {
        $recipientName = '<span class="text-muted">' . e(get_company_name($aRow['customer_id'])) . '</span>';
    }

    $row[] = $numberOutput;
    $row[] = _d($aRow['created_at']);
    $row[] = '<a href="'.admin_url('clients/client/'.(int)$aRow['customer_id']).'">'.e(get_company_name($aRow['customer_id'])).'</a>';
    $row[] = $recipientName;
    $row[] = $originAddr;
    $row[] = $destAddr;
    $row[] = lg_format_transport_summary($aRow);
    $row[] = !empty($aRow['planned_delivery_date']) ? _dt($aRow['planned_delivery_date']) : '-';

    $row[] = lg_get_payment_term_str($aRow['payment_term_id']);

    $row[] =  format_lg_package_status($aRow['delivery_status']);

    $row[] = app_format_money($aRow['package_total'], $aRow['package_currency']);

   
    $row[] = $invoice_status_str;

    $action = '';
   
    if(has_permission('invoices', '', 'create') && $invoice_status == 'pending' && $aRow['shipping_type'] != 'pickup'){
        $action .= '<a href="'.admin_url('logistic/shipping_create_invoice/'.$aRow['shipping_id']).'" class="btn btn-icon btn-success mleft5" data-toggle="tooltip" data-placement="top" title="'._l('lg_create_invoice').'"><i class="fa fa-receipt"></i></a>';
    }

    $action .= '<a href="'.admin_url('logistic/export_shipping_shipment/'.$aRow['shipping_id']).'?output_type=I" target="_blank" class="btn btn-icon btn-warning mleft5" data-toggle="tooltip" data-placement="top" title="'._l('lg_export_shipment').'"><i class="fa fa-file-lines"></i></a>';

    $action .= '<a href="'.admin_url('logistic/export_shipping_label/'.$aRow['shipping_id']).'?output_type=I" target="_blank"  class="btn btn-icon btn-info mleft5" data-toggle="tooltip" data-placement="top" title="'._l('lg_export_label').'"><i class="fa fa-file-contract"></i></a>';

    $action .= '<a href="javascript:void(0);" class="btn btn-icon btn-success mleft5" onclick="send_shipping('.$aRow['shipping_id'].'); return false;" data-toggle="tooltip" data-placement="top" title="'._l('lg_send_mail').'"><i class="fa fa-envelope"></i></a>';

    $action .= '<a href="javascript:void(0);" onclick="assign_driver(this); return false;" data-shipping_id="'.$aRow['shipping_id'].'" data-assign_driver="'.$aRow['assign_driver'].'" class="btn btn-icon btn-primary mleft5" data-toggle="tooltip" data-placement="top" title="'._l('lg_assign_driver').'"><i class="fa fa-user"></i></a>';

    if (has_permission('lg_shipping', '', 'edit')  && $aRow['shipping_type'] == 'pickup' ) {
        $action .= '<a href="javascript:void(0);" onclick="approval_pickup(this); return false;" data-shipping_id="'.$aRow['shipping_id'].'" class="btn btn-icon btn-default mleft5" data-toggle="tooltip" data-placement="top" title="'._l('lg_approval').'"><i class="fa fa-check"></i></a>';
    }

    
    $row[] = $action;


    $output['aaData'][] = $row;
}

echo json_encode($output);
die();
