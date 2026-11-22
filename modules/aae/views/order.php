<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="aae-page-header">
            <div>
                <a href="<?php echo admin_url('aae/aae_order'); ?>" class="aae-back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    <?php echo _l('aae_orders'); ?>
                </a>
                <h1 class="aae-page-title"><?php echo _l('aae_order_title', $order->id); ?></h1>
                <p class="aae-page-subtitle">
                    <?php if ($client) { ?>
                    <a href="<?php echo admin_url('clients/client/' . $client->userid); ?>"><?php echo e($client->company); ?></a>
                    <?php } ?>
                    &middot; <?php echo _dt($order->datecreated); ?>
                </p>
            </div>
            <div class="aae-page-actions">
                <?php echo aae_order_stage_badge($order->stage); ?>
            </div>
        </div>

        <!-- Stage Stepper / Progress Bar -->
        <div class="panel_s">
            <div class="panel-body">
                <div class="progress" style="height: 28px; margin-bottom: 10px;">
                    <?php $progress = aae_order_stage_progress($order->stage); ?>
                    <div class="progress-bar progress-bar-primary" role="progressbar" 
                         style="width: <?php echo $progress; ?>%;"
                         aria-valuenow="<?php echo $progress; ?>"
                         aria-valuemin="0"
                         aria-valuemax="100"
                         data-percent="<?php echo $progress; ?>">
                    </div>
                </div>
                <div class="tw-flex tw-justify-between tw-text-xs tw-text-muted">
                    <span><?php echo _l('aae_order_stage_nouveau_contact'); ?></span>
                    <span><?php echo _l('aae_order_stage_commande_confirmee'); ?></span>
                    <span><?php echo _l('aae_order_stage_expedie'); ?></span>
                    <span><?php echo _l('aae_order_stage_livre'); ?></span>
                    <span><?php echo _l('aae_order_stage_cloture'); ?></span>
                </div>

                <!-- Next Stage Action -->
                <?php 
                $next_stage = aae_order_next_stage($order->stage);
                if ($next_stage && staff_can('edit', 'aae')) { 
                ?>
                <hr style="margin: 15px 0;" />
                <div class="tw-flex tw-items-center tw-justify-between">
                    <div>
                        <strong><?php echo _l('aae_order_next_action'); ?> :</strong>
                        <span class="tw-ml-2"><?php echo aae_order_stage_badge($next_stage); ?></span>
                    </div>
                    <?php echo form_open(admin_url('aae/aae_order/transition_stage/' . $order->id), ['style' => 'display:inline;']); ?>
                        <input type="hidden" name="stage" value="<?php echo e($next_stage); ?>" />
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa fa-arrow-right tw-mr-1"></i>
                            <?php echo _l('aae_order_pass_to_stage', $stages[$next_stage]); ?>
                        </button>
                    <?php echo form_close(); ?>
                </div>
                <?php } ?>
            </div>
        </div>

        <!-- Financial Summary Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="text-muted tw-text-xs uppercase"><?php echo _l('aae_amount_client'); ?></div>
                        <div class="tw-text-2xl tw-font-bold tw-mt-1"><?php echo aae_format_eur($order->amount_client); ?></div>
                        <div class="tw-text-xs tw-text-muted tw-mt-1"><?php echo aae_format_dzd($order->amount_client * (float) aae_get_option('aae_dzd_rate', 282)); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="text-muted tw-text-xs uppercase"><?php echo _l('aae_amount_net_received'); ?></div>
                        <div class="tw-text-2xl tw-font-bold tw-text-success tw-mt-1"><?php echo aae_format_eur($order->amount_net_received); ?></div>
                        <div class="tw-text-xs tw-text-muted tw-mt-1"><?php echo aae_order_client_balance_label($order); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="text-muted tw-text-xs uppercase"><?php echo _l('aae_amount_supplier'); ?></div>
                        <div class="tw-text-2xl tw-font-bold tw-mt-1"><?php echo aae_format_eur($order->amount_supplier); ?></div>
                        <div class="tw-text-xs tw-text-muted tw-mt-1"><?php echo _l('aae_paid'); ?> : <?php echo aae_format_eur($order->amount_paid_supplier); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="text-muted tw-text-xs uppercase"><?php echo _l('aae_margin'); ?></div>
                        <div class="tw-text-2xl tw-font-bold tw-text-primary tw-mt-1"><?php echo aae_format_eur($order->margin); ?></div>
                        <div class="tw-text-xs tw-text-muted tw-mt-1"><?php echo round(($order->amount_client > 0 ? ($order->margin / $order->amount_client) * 100 : 0), 1); ?>%</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Tabs -->
        <div class="panel_s">
            <div class="panel-body">
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#tab_overview" role="tab" data-toggle="tab"><i class="fa fa-info-circle tw-mr-1"></i><?php echo _l('aae_overview'); ?></a>
                    </li>
                    <li role="presentation">
                        <a href="#tab_docs" role="tab" data-toggle="tab"><i class="fa fa-folder-open tw-mr-1"></i><?php echo _l('aae_documents'); ?> (<?php echo count($documents); ?>)</a>
                    </li>
                    <li role="presentation">
                        <a href="#tab_accounting" role="tab" data-toggle="tab"><i class="fa fa-file-invoice-dollar tw-mr-1"></i><?php echo _l('aae_accounting'); ?></a>
                    </li>
                    <li role="presentation">
                        <a href="#tab_payments" role="tab" data-toggle="tab"><i class="fa fa-money tw-mr-1"></i><?php echo _l('aae_payments'); ?> (<?php echo count($payments); ?>)</a>
                    </li>
                    <li role="presentation">
                        <a href="#tab_shipping" role="tab" data-toggle="tab"><i class="fa fa-ship tw-mr-1"></i><?php echo _l('aae_shipping'); ?></a>
                    </li>
                    <li role="presentation">
                        <a href="#tab_delivery" role="tab" data-toggle="tab"><i class="fa fa-truck tw-mr-1"></i><?php echo _l('aae_delivery'); ?></a>
                    </li>
                    <li role="presentation">
                        <a href="#tab_messages" role="tab" data-toggle="tab"><i class="fa fa-comments tw-mr-1"></i><?php echo _l('aae_messages'); ?></a>
                    </li>
                    <li role="presentation">
                        <a href="#tab_timeline" role="tab" data-toggle="tab"><i class="fa fa-history tw-mr-1"></i><?php echo _l('aae_timeline'); ?></a>
                    </li>
                </ul>

                <div class="tab-content mtop15">
                    <!-- Overview Tab -->
                    <div role="tabpanel" class="tab-pane active" id="tab_overview">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="tw-font-bold tw-mb-3"><?php echo _l('aae_vehicle_information'); ?></h4>
                                <?php if ($vehicle) { ?>
                                <table class="table table-bordered">
                                    <tr>
                                        <th class="tw-w-1/3"><?php echo _l('aae_brand'); ?> / <?php echo _l('aae_model'); ?></th>
                                        <td><strong><?php echo e($vehicle->brand . ' ' . $vehicle->model); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo _l('aae_year'); ?></th>
                                        <td><?php echo (int) $vehicle->model_year; ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo _l('aae_color'); ?></th>
                                        <td><?php echo e($vehicle->color); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo _l('aae_fob_price'); ?></th>
                                        <td><?php echo aae_format_eur($vehicle->fob_price); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo _l('aae_total_ttc'); ?></th>
                                        <td><?php echo aae_format_eur($vehicle->total_ttc); ?></td>
                                    </tr>
                                </table>
                                <?php } else { ?>
                                <p class="text-muted"><?php echo _l('aae_no_vehicle_linked'); ?></p>
                                <?php } ?>
                            </div>
                            <div class="col-md-6">
                                <h4 class="tw-font-bold tw-mb-3"><?php echo _l('aae_supplier_info'); ?></h4>
                                <table class="table table-bordered">
                                    <tr>
                                        <th class="tw-w-1/3"><?php echo _l('aae_supplier_name'); ?></th>
                                        <td><?php echo e($order->supplier_name ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo _l('aae_supplier_ref'); ?></th>
                                        <td><?php echo e($order->supplier_reference ?: '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo _l('aae_amount_supplier'); ?></th>
                                        <td><?php echo aae_format_eur($order->amount_supplier); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php echo _l('aae_paid'); ?></th>
                                        <td><?php echo aae_format_eur($order->amount_paid_supplier); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Tab -->
                    <div role="tabpanel" class="tab-pane" id="tab_docs">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th><?php echo _l('aae_doc_type'); ?></th>
                                    <th><?php echo _l('aae_doc_status'); ?></th>
                                    <th><?php echo _l('aae_file'); ?></th>
                                    <th><?php echo _l('aae_received_at'); ?></th>
                                    <th><?php echo _l('aae_sent_at'); ?></th>
                                    <th><?php echo _l('aae_actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc) { ?>
                                <tr>
                                    <td><strong><?php echo e(aae_order_document_label($doc)); ?></strong></td>
                                    <td>
                                        <?php if ($doc->received_at) { ?>
                                        <span class="label label-success"><i class="fa fa-check tw-mr-1"></i><?php echo _l('aae_received'); ?></span>
                                        <?php } else { ?>
                                        <span class="label label-default"><?php echo _l('aae_pending'); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php
                                        $file = $doc->file_id && isset($document_files[$doc->file_id]) ? $document_files[$doc->file_id] : null;
                                        ?>
                                        <?php if ($file) { ?>
                                        <a href="<?php echo site_url('download/file/sales_attachment/' . $file['attachment_key']); ?>" target="_blank" class="tw-block tw-mb-1">
                                            <i class="fa fa-paperclip tw-mr-1"></i><?php echo e($file['file_name']); ?>
                                        </a>
                                        <?php } ?>
                                        <?php if (staff_can('edit', 'aae')) { ?>
                                        <?php echo form_open_multipart(admin_url('aae/aae_order/upload_doc/' . $doc->id), ['style' => 'display:flex; gap:4px; align-items:center;']); ?>
                                            <input type="file" name="file" class="form-control input-sm" style="max-width:160px;" required />
                                            <button type="submit" class="btn btn-default btn-xs" title="<?php echo _l('aae_upload'); ?>">
                                                <i class="fa fa-upload"></i>
                                            </button>
                                        <?php echo form_close(); ?>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo $doc->received_at ? _dt($doc->received_at) : '-'; ?></td>
                                    <td><?php echo $doc->sent_at ? _dt($doc->sent_at) : '-'; ?></td>
                                    <td>
                                        <?php echo form_open(admin_url('aae/aae_order/mark_doc/' . $doc->id), ['style' => 'display:inline;']); ?>
                                            <input type="hidden" name="received" value="<?php echo $doc->received_at ? '0' : '1'; ?>" />
                                            <button type="submit" class="btn btn-default btn-xs">
                                                <i class="fa fa-<?php echo $doc->received_at ? 'times' : 'check'; ?>"></i>
                                                <?php echo $doc->received_at ? _l('aae_mark_unreceived') : _l('aae_mark_received'); ?>
                                            </button>
                                        <?php echo form_close(); ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Accounting Tab -->
                    <div role="tabpanel" class="tab-pane" id="tab_accounting">
                        <?php
                        $can_create_estimates = staff_can('create', 'estimates');
                        $can_create_invoices  = staff_can('create', 'invoices');
                        $can_create_contracts = staff_can('create', 'contracts');

                        // WhatsApp delivery of the estimate: the customer gets the
                        // public estimate link, so no SMTP setup is required.
                        $wa_phone    = preg_replace('/[^0-9]/', '', $message_context['phone']);
                        $client_lang = strtolower($client->default_language ?? '');
                        $wa_lang     = 'en';
                        if (strpos($client_lang, 'fr') === 0) {
                            $wa_lang = 'fr';
                        } elseif (strpos($client_lang, 'ar') === 0) {
                            $wa_lang = 'ar';
                        }
                        $wa_template = $message_templates['devis_prete'][$wa_lang]
                            ?? ($message_templates['devis_prete']['en'] ?? '');
                        $wa_message  = aae_render_order_message($wa_template, $message_context);
                        $wahref = 'https://wa.me/' . $wa_phone . '?text=' . rawurlencode($wa_message);
                        ?>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th class="tw-w-1/4"><?php echo _l('aae_accounting_document'); ?></th>
                                    <th><?php echo _l('aae_accounting_reference'); ?></th>
                                    <th class="tw-w-1/3"><?php echo _l('aae_actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong><?php echo _l('aae_estimate'); ?></strong></td>
                                    <td>
                                        <?php if ($order->estimate_id) { ?>
                                        <a href="<?php echo admin_url('estimates/list_estimates/' . (int) $order->estimate_id); ?>" target="_blank">
                                            <?php echo e(format_estimate_number($order->estimate_id)); ?>
                                        </a>
                                        <?php } else { ?>
                                        <span class="text-muted"><?php echo _l('aae_not_linked'); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if (! $order->estimate_id && $can_create_estimates) { ?>
                                        <?php echo form_open(admin_url('aae/aae_order/create_estimate/' . $order->id), ['style' => 'display:inline;']); ?>
                                            <button type="submit" class="btn btn-default btn-sm">
                                                <i class="fa fa-plus tw-mr-1"></i><?php echo _l('aae_create_estimate'); ?>
                                            </button>
                                        <?php echo form_close(); ?>
                                        <?php } elseif ($order->estimate_id) { ?>
                                        <a href="<?php echo admin_url('estimates/list_estimates/' . (int) $order->estimate_id); ?>" class="btn btn-default btn-sm" target="_blank">
                                            <i class="fa fa-external-link tw-mr-1"></i><?php echo _l('aae_open'); ?>
                                        </a>
                                        <?php if ($wa_phone && staff_can('edit', 'aae')) { ?>
                                        <a href="<?php echo e($wahref); ?>" target="_blank" rel="noopener" class="btn btn-success btn-sm" id="aae_estimate_wa">
                                            <i class="fa-brands fa-whatsapp tw-mr-1"></i><?php echo _l('aae_send_estimate_whatsapp'); ?>
                                        </a>
                                        <?php } ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo _l('aae_invoice'); ?></strong></td>
                                    <td>
                                        <?php if ($order->invoice_id) { ?>
                                        <a href="<?php echo admin_url('invoices/list_invoices/' . (int) $order->invoice_id); ?>" target="_blank">
                                            <?php echo e(format_invoice_number($order->invoice_id)); ?>
                                        </a>
                                        <?php } else { ?>
                                        <span class="text-muted"><?php echo _l('aae_not_linked'); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if (! $order->invoice_id && $can_create_invoices) { ?>
                                        <?php echo form_open(admin_url('aae/aae_order/create_invoice/' . $order->id), ['style' => 'display:inline;']); ?>
                                            <button type="submit" class="btn btn-default btn-sm">
                                                <i class="fa fa-plus tw-mr-1"></i><?php echo _l('aae_create_invoice'); ?>
                                            </button>
                                        <?php echo form_close(); ?>
                                        <?php } elseif ($order->invoice_id) { ?>
                                        <a href="<?php echo admin_url('invoices/list_invoices/' . (int) $order->invoice_id); ?>" class="btn btn-default btn-sm" target="_blank">
                                            <i class="fa fa-external-link tw-mr-1"></i><?php echo _l('aae_open'); ?>
                                        </a>
                                        <?php if (staff_can('create', 'payments')) { ?>
                                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal_invoice_payment"
                                            data-invoice-target="invoice" data-invoice-number="<?php echo e(format_invoice_number($order->invoice_id)); ?>">
                                            <i class="fa fa-money tw-mr-1"></i><?php echo _l('aae_record_client_payment'); ?>
                                        </button>
                                        <?php } ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo _l('aae_final_invoice'); ?></strong></td>
                                    <td>
                                        <?php if ($order->final_invoice_id) { ?>
                                        <a href="<?php echo admin_url('invoices/list_invoices/' . (int) $order->final_invoice_id); ?>" target="_blank">
                                            <?php echo e(format_invoice_number($order->final_invoice_id)); ?>
                                        </a>
                                        <?php } else { ?>
                                        <span class="text-muted"><?php echo _l('aae_not_linked'); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if (! $order->final_invoice_id && $can_create_invoices && $order->invoice_id) { ?>
                                        <?php echo form_open(admin_url('aae/aae_order/create_final_invoice/' . $order->id), ['style' => 'display:inline;']); ?>
                                            <button type="submit" class="btn btn-default btn-sm">
                                                <i class="fa fa-plus tw-mr-1"></i><?php echo _l('aae_create_final_invoice'); ?>
                                            </button>
                                        <?php echo form_close(); ?>
                                        <?php } elseif ($order->final_invoice_id) { ?>
                                        <a href="<?php echo admin_url('invoices/list_invoices/' . (int) $order->final_invoice_id); ?>" class="btn btn-default btn-sm" target="_blank">
                                            <i class="fa fa-external-link tw-mr-1"></i><?php echo _l('aae_open'); ?>
                                        </a>
                                        <?php if (staff_can('create', 'payments')) { ?>
                                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal_invoice_payment"
                                            data-invoice-target="final" data-invoice-number="<?php echo e(format_invoice_number($order->final_invoice_id)); ?>">
                                            <i class="fa fa-money tw-mr-1"></i><?php echo _l('aae_record_client_payment'); ?>
                                        </button>
                                        <?php } ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo _l('aae_contract'); ?></strong></td>
                                    <td>
                                        <?php if ($order->contract_id) { ?>
                                        <a href="<?php echo admin_url('contracts/contract/' . (int) $order->contract_id); ?>" target="_blank">
                                            <?php echo e(_l('aae_contract_number', $order->contract_id)); ?>
                                        </a>
                                        <?php } else { ?>
                                        <span class="text-muted"><?php echo _l('aae_not_linked'); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if (! $order->contract_id && $can_create_contracts) { ?>
                                        <?php echo form_open(admin_url('aae/aae_order/create_contract/' . $order->id), ['style' => 'display:inline;']); ?>
                                            <button type="submit" class="btn btn-default btn-sm">
                                                <i class="fa fa-plus tw-mr-1"></i><?php echo _l('aae_create_contract'); ?>
                                            </button>
                                        <?php echo form_close(); ?>
                                        <?php } elseif ($order->contract_id) { ?>
                                        <a href="<?php echo admin_url('contracts/contract/' . (int) $order->contract_id); ?>" class="btn btn-default btn-sm" target="_blank">
                                            <i class="fa fa-external-link tw-mr-1"></i><?php echo _l('aae_open'); ?>
                                        </a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <?php if ($order->estimate_id && staff_can('edit', 'aae')) { ?>
                        <?php echo form_open(admin_url('aae/aae_order/mark_estimate_sent/' . $order->id), ['id' => 'aae_estimate_sent_form', 'class' => 'hidden']); ?>
                        <input type="hidden" name="channel" value="whatsapp" />
                        <?php echo form_close(); ?>
                        <?php } ?>

                        <?php if (staff_can('edit', 'aae')) { ?>
                        <hr />
                        <h4 class="tw-font-bold tw-mb-3"><?php echo _l('aae_link_existing_records'); ?></h4>
                        <p class="text-muted tw-text-sm"><?php echo _l('aae_link_existing_records_help'); ?></p>
                        <?php echo form_open(admin_url('aae/aae_order/link_accounting/' . $order->id)); ?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?php echo _l('aae_estimate'); ?></label>
                                    <select name="estimate_id" class="form-control">
                                        <option value=""><?php echo _l('aae_link_select_none'); ?></option>
                                        <?php foreach ($client_estimates as $estimate) { ?>
                                        <option value="<?php echo (int) $estimate['id']; ?>" <?php echo (int) $order->estimate_id === (int) $estimate['id'] ? 'selected' : ''; ?>>
                                            <?php echo e(format_estimate_number($estimate['id'])); ?>
                                            &mdash; <?php echo e(app_format_money($estimate['total'], $estimate['currency'])); ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                    <?php if (empty($client_estimates)) { ?>
                                    <p class="help-block tw-text-xs tw-mb-0"><?php echo _l('aae_link_no_records'); ?></p>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?php echo _l('aae_invoice'); ?></label>
                                    <select name="invoice_id" class="form-control">
                                        <option value=""><?php echo _l('aae_link_select_none'); ?></option>
                                        <?php foreach ($client_invoices as $invoice) { ?>
                                        <option value="<?php echo (int) $invoice['id']; ?>" <?php echo (int) $order->invoice_id === (int) $invoice['id'] ? 'selected' : ''; ?>>
                                            <?php echo e(format_invoice_number($invoice['id'])); ?>
                                            &mdash; <?php echo e(app_format_money($invoice['total'], $invoice['currency'])); ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                    <?php if (empty($client_invoices)) { ?>
                                    <p class="help-block tw-text-xs tw-mb-0"><?php echo _l('aae_link_no_records'); ?></p>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?php echo _l('aae_contract'); ?></label>
                                    <select name="contract_id" class="form-control">
                                        <option value=""><?php echo _l('aae_link_select_none'); ?></option>
                                        <?php foreach ($client_contracts as $contract) { ?>
                                        <option value="<?php echo (int) $contract['id']; ?>" <?php echo (int) $order->contract_id === (int) $contract['id'] ? 'selected' : ''; ?>>
                                            #<?php echo (int) $contract['id']; ?> &mdash; <?php echo e($contract['subject']); ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                    <?php if (empty($client_contracts)) { ?>
                                    <p class="help-block tw-text-xs tw-mb-0"><?php echo _l('aae_link_no_records'); ?></p>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-default btn-sm"><?php echo _l('aae_link_records'); ?></button>
                        <?php echo form_close(); ?>
                        <?php } ?>

                        <hr />
                        <h4 class="tw-font-bold tw-mb-3"><?php echo _l('aae_final_settlement'); ?></h4>
                        <?php if ($order->stage === 'cloture') { ?>
                        <table class="table table-bordered">
                            <tr>
                                <th class="tw-w-1/3"><?php echo _l('aae_final_balance'); ?></th>
                                <td><?php echo aae_format_eur($order->final_balance); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo _l('aae_refund_amount'); ?></th>
                                <td><?php echo aae_format_eur($order->refund_amount); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo _l('aae_doc_papiers_definitifs'); ?></th>
                                <td>
                                    <?php if ($order->final_papers_received) { ?>
                                    <span class="label label-success"><?php echo _l('aae_received'); ?></span>
                                    <?php } else { ?>
                                    <span class="label label-default"><?php echo _l('aae_pending'); ?></span>
                                    <?php } ?>
                                </td>
                            </tr>
                        </table>
                        <?php } elseif (staff_can('edit', 'aae')) { ?>
                        <p class="text-muted tw-text-sm"><?php echo _l('aae_close_order_help'); ?></p>
                        <?php
                        $client_balance = aae_order_client_balance($order);
                        $balance_due    = $client_balance > 0 ? $client_balance : 0;
                        $refund_due     = $client_balance < 0 ? abs($client_balance) : 0;
                        ?>
                        <?php echo form_open(admin_url('aae/aae_order/close_order/' . $order->id)); ?>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?php echo _l('aae_final_balance'); ?> (€)</label>
                                    <input type="number" step="0.01" name="final_balance" class="form-control" value="<?php echo number_format($balance_due, 2, '.', ''); ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?php echo _l('aae_refund_amount'); ?> (€)</label>
                                    <input type="number" step="0.01" name="refund_amount" class="form-control" value="<?php echo number_format($refund_due, 2, '.', ''); ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-danger btn-block">
                                        <i class="fa fa-check-circle tw-mr-1"></i><?php echo _l('aae_close_order'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php echo form_close(); ?>
                        <?php } ?>
                    </div>

                    <!-- Payments Tab -->
                    <div role="tabpanel" class="tab-pane" id="tab_payments">
                        <div class="tw-mb-4 text-right">
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal_add_payment">
                                <i class="fa fa-plus tw-mr-1"></i><?php echo _l('aae_add_payment'); ?>
                            </button>
                        </div>
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th><?php echo _l('aae_payment_type'); ?></th>
                                    <th><?php echo _l('aae_amount'); ?></th>
                                    <th><?php echo _l('aae_reference'); ?></th>
                                    <th><?php echo _l('aae_date'); ?></th>
                                    <th class="tw-w-48"><?php echo _l('aae_actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $pmt) { ?>
                                <tr>
                                    <td><?php echo aae_order_payment_types()[$pmt->payment_type] ?? $pmt->payment_type; ?></td>
                                    <td><strong><?php echo aae_format_eur($pmt->amount); ?></strong></td>
                                    <td><?php echo e($pmt->reference ?: '-'); ?></td>
                                    <td><?php echo _dt($pmt->datepaid); ?></td>
                                    <td>
                                        <?php if (staff_can('edit', 'aae')) { ?>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-default" data-toggle="modal" data-target="#modal_edit_payment_<?php echo (int) $pmt->id; ?>" title="<?php echo _l('aae_edit_payment'); ?>">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-default" data-toggle="modal" data-target="#modal_delete_payment_<?php echo (int) $pmt->id; ?>" title="<?php echo _l('aae_delete_payment'); ?>">
                                                <i class="fa fa-trash text-danger"></i>
                                            </button>
                                        </div>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Shipping Tab -->
                    <div role="tabpanel" class="tab-pane" id="tab_shipping">
                        <?php echo form_open(admin_url('aae/aae_order/update_shipping/' . $order->id)); ?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?php echo _l('aae_container_number'); ?></label>
                                    <input type="text" name="container_number" class="form-control" value="<?php echo e($order->container_number); ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?php echo _l('aae_bl_number'); ?></label>
                                    <input type="text" name="bl_number" class="form-control" value="<?php echo e($order->bl_number); ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?php echo _l('aae_tracking_link'); ?></label>
                                    <input type="url" name="tracking_link" class="form-control" value="<?php echo e($order->tracking_link); ?>" placeholder="https://..." />
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm"><?php echo _l('aae_save'); ?></button>
                        <?php echo form_close(); ?>
                    </div>

                    <!-- Delivery Tab -->
                    <div role="tabpanel" class="tab-pane" id="tab_delivery">
                        <?php echo form_open(admin_url('aae/aae_order/update_delivery/' . $order->id)); ?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?php echo _l('aae_delivery_method'); ?></label>
                                    <select name="delivery_method" class="form-control">
                                        <option value="enlevement" <?php echo $order->delivery_method === 'enlevement' ? 'selected' : ''; ?>><?php echo _l('aae_order_delivery_enlevement'); ?></option>
                                        <option value="domicile" <?php echo $order->delivery_method === 'domicile' ? 'selected' : ''; ?>><?php echo _l('aae_order_delivery_domicile'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?php echo _l('aae_extras'); ?></label>
                                    <input type="text" name="extras" class="form-control" value="<?php echo e($order->extras); ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?php echo _l('aae_extras_cost'); ?> (€)</label>
                                    <input type="number" step="0.01" name="extras_cost" class="form-control" value="<?php echo (float) $order->extras_cost; ?>" />
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm"><?php echo _l('aae_save'); ?></button>
                        <?php echo form_close(); ?>
                    </div>

                    <!-- Messages Tab -->
                    <div role="tabpanel" class="tab-pane" id="tab_messages">
                        <?php echo form_open(admin_url('aae/aae_order/log_message/' . $order->id), ['id' => 'aae_message_form']); ?>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?php echo _l('aae_message_stage'); ?></label>
                                    <select name="stage" id="aae_msg_stage" class="form-control">
                                        <?php foreach ($stages as $stage_key => $stage_label) { ?>
                                        <option value="<?php echo e($stage_key); ?>" <?php echo $order->stage === $stage_key ? 'selected' : ''; ?>><?php echo e($stage_label); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><?php echo _l('aae_message_language'); ?></label>
                                    <select name="lang" id="aae_msg_lang" class="form-control">
                                        <option value="fr">Français</option>
                                        <option value="en" <?php echo get_option('active_language') === 'english' ? 'selected' : ''; ?>>English</option>
                                        <option value="ar">العربية</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><?php echo _l('aae_message_channel'); ?></label>
                                    <select name="channel" id="aae_msg_channel" class="form-control">
                                        <option value="whatsapp"><?php echo _l('aae_message_whatsapp'); ?></option>
                                        <option value="email"><?php echo _l('aae_message_email'); ?></option>
                                        <option value="copy"><?php echo _l('aae_message_copy'); ?></option>
                                    </select>
                                </div>
                                <p class="text-muted tw-text-xs"><?php echo _l('aae_message_placeholders_help'); ?></p>
                            </div>
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label><?php echo _l('aae_message_body'); ?></label>
                                    <textarea name="message" id="aae_msg_body" class="form-control" rows="16"></textarea>
                                </div>
                                <div class="tw-flex tw-flex-wrap tw-gap-2">
                                    <a href="#" id="aae_msg_wa" class="btn btn-success" target="_blank" rel="noopener">
                                        <i class="fab fa-whatsapp tw-mr-1"></i><?php echo _l('aae_message_whatsapp'); ?>
                                    </a>
                                    <?php if (aae_get_whatsapp()->is_configured()) { ?>
                                    <button type="button" id="aae_msg_wa_api" class="btn btn-success">
                                        <i class="fa-solid fa-paper-plane tw-mr-1"></i><?php echo _l('aae_send_via_api'); ?>
                                    </button>
                                    <?php } ?>
                                    <a href="#" id="aae_msg_email" class="btn btn-default">
                                        <i class="fa fa-envelope tw-mr-1"></i><?php echo _l('aae_message_email'); ?>
                                    </a>
                                    <button type="button" id="aae_msg_copy" class="btn btn-default">
                                        <i class="fa fa-copy tw-mr-1"></i><?php echo _l('aae_message_copy'); ?>
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-paper-plane tw-mr-1"></i><?php echo _l('aae_message_log'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php echo form_close(); ?>
                    </div>

                    <script>
                    window.aaeOrderMessages = {
                        templates: <?php echo json_encode($message_templates, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
                        context: <?php echo json_encode($message_context, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
                        phone: <?php echo json_encode(preg_replace('/[^0-9]/', '', $message_context['phone'])); ?>,
                        email: <?php echo json_encode($message_context['email']); ?>,
                        copiedLabel: <?php echo json_encode(_l('aae_copied')); ?>,
                        copyLabel: <?php echo json_encode(_l('aae_message_copy')); ?>,
                        order: <?php echo (int) $order->id; ?>,
                        sendViaApi: <?php echo json_encode(_l('aae_send_via_api')); ?>,
                        sendFailed: <?php echo json_encode(_l('aae_whatsapp_send_failed')); ?>
                    };
                    </script>

                    <script>
                    // Fallback initialization for Messages tab handlers
                    // Runs immediately if aae.js handlers didn't attach (e.g., document ready already fired)
                    (function($) {
                        function initAaeMessagesFallback() {
                            var data = window.aaeOrderMessages;
                            if (!data || !$("#aae_msg_stage").length) {
                                return;
                            }

                            var $stage = $("#aae_msg_stage");
                            var $lang = $("#aae_msg_lang");
                            var $body = $("#aae_msg_body");
                            var $channel = $("#aae_msg_channel");
                            var $wa = $("#aae_msg_wa");
                            var $email = $("#aae_msg_email");
                            var $copy = $("#aae_msg_copy");
                            var $api = $("#aae_msg_wa_api");

                            function render(template) {
                                var message = String(template || "").replace(/\{([a-z_]+)\}/gi, function (match, key) {
                                    return Object.prototype.hasOwnProperty.call(data.context, key) ? data.context[key] : match;
                                });
                                var link = data.context.order_link || "";
                                if (link && message.indexOf(link) === -1) {
                                    message = message.replace(/\s+$/, "") + "\n\n" + link;
                                }
                                return message;
                            }

                            function currentTemplate() {
                                var stage = $stage.val();
                                var lang = $lang.val();
                                if (!data.templates[stage] || !data.templates[stage][lang]) return "";
                                return data.templates[stage][lang];
                            }

                            function refreshLinks() {
                                var message = $body.val();
                                $wa.attr("href", data.phone ? "https://wa.me/" + data.phone + "?text=" + encodeURIComponent(message) : "#");
                                $email.attr("href", data.email ? "mailto:" + data.email + "?subject=" + encodeURIComponent("Export #" + data.context.order) + "&body=" + encodeURIComponent(message) : "#");
                            }

                            function refreshMessage() {
                                $body.val(render(currentTemplate()));
                                refreshLinks();
                            }

                            $stage.add($lang).on("change", refreshMessage);
                            $body.on("input", refreshLinks);

                            $wa.on("click", function (event) {
                                if (!data.phone) {
                                    event.preventDefault();
                                    alert(data.noPhone || "No phone number is available for this customer.");
                                    return;
                                }
                                event.preventDefault();
                                $channel.val("whatsapp");
                                // Auto-submit form to log the message
                                $("#aae_message_form").submit();
                            });

                            $email.on("click", function (event) {
                                event.preventDefault();
                                $channel.val("email");
                                // Auto-submit form to log the message
                                $("#aae_message_form").submit();
                            });

                            $api.on("click", function () {
                                var $button = $(this);
                                var original = $button.html();
                                var message = $body.val();

                                if (!message || !message.trim()) {
                                    alert(data.emptyMessage || "The message is empty.");
                                    return;
                                }

                                $button.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin tw-mr-1"></i>');

                                $.ajax({
                                    url: ((window.aaeSettings && window.aaeSettings.urls && window.aaeSettings.urls.whatsappSendApi) || "admin/aae/aae_order/send_whatsapp_api/") + data.order,
                                    type: "POST",
                                    dataType: "json",
                                    data: {
                                        message: message,
                                        stage: $stage.val(),
                                        lang: $lang.val(),
                                    },
                                    headers: { "X-Requested-With": "XMLHttpRequest" },
                                })
                                    .done(function (response) {
                                        var text = response.message || (response.success ? "" : data.sendFailed);
                                        if (!response.success && response.error) {
                                            text += "\n\n" + response.error;
                                        }
                                        if (typeof alert_float === "function") {
                                            alert_float(response.success ? "success" : "danger", text);
                                        } else {
                                            alert(text);
                                        }
                                        if (response.success) {
                                            $channel.val("whatsapp");
                                            // Auto-submit form to log the message
                                            $("#aae_message_form").submit();
                                        }
                                    })
                                    .fail(function (xhr) {
                                        var text = data.sendFailed || "WhatsApp could not send the message.";
                                        text += " (" + xhr.status + ")";
                                        if (typeof alert_float === "function") {
                                            alert_float("danger", text);
                                        } else {
                                            alert(text);
                                        }
                                    })
                                    .always(function () {
                                        $button.prop("disabled", false).html(original);
                                    });
                            });

                            $copy.on("click", function () {
                                var $button = $(this);
                                var message = $body.val();
                                $channel.val("copy");

                                var done = function () {
                                    $button.html('<i class="fa fa-check tw-mr-1"></i>' + data.copiedLabel);
                                    setTimeout(function () {
                                        $button.html('<i class="fa fa-copy tw-mr-1"></i>' + data.copyLabel);
                                    }, 1500);
                                };

                                if (navigator.clipboard && navigator.clipboard.writeText) {
                                    navigator.clipboard.writeText(message).then(done);
                                } else {
                                    var textarea = document.createElement("textarea");
                                    textarea.value = message;
                                    document.body.appendChild(textarea);
                                    textarea.select();
                                    document.execCommand("copy");
                                    document.body.removeChild(textarea);
                                    done();
                                }
                            });

                            refreshMessage();
                        }

                        // Run immediately if document is ready, otherwise on ready
                        if (document.readyState === "complete" || document.readyState === "interactive") {
                            initAaeMessagesFallback();
                        } else {
                            $(document).ready(initAaeMessagesFallback);
                        }
                    })(jQuery);
                    </script>

                    <!-- Timeline Tab -->
                    <div role="tabpanel" class="tab-pane" id="tab_timeline">
                        <ul class="list-group">
                            <?php foreach ($events as $ev) { ?>
                            <li class="list-group-item">
                                <div class="tw-flex tw-justify-between">
                                    <strong><?php echo e($ev->event_title); ?></strong>
                                    <span class="text-muted tw-text-xs"><?php echo _dt($ev->datecreated); ?></span>
                                </div>
                                <?php if ($ev->event_description) { ?>
                                <p class="tw-mb-0 tw-mt-1 text-muted"><?php echo e($ev->event_description); ?></p>
                                <?php } ?>
                            </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Payment -->
<div class="modal fade" id="modal_add_payment" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open(admin_url('aae/aae_order/add_payment/' . $order->id)); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo _l('aae_add_payment'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-info tw-text-sm">
                    <?php echo _l('aae_payment_client_invoice_hint'); ?>
                </div>
                <div class="form-group">
                    <label><?php echo _l('aae_payment_type'); ?></label>
                    <select name="payment_type" class="form-control" required>
                        <?php foreach (aae_order_payment_types() as $k => $lbl) { ?>
                        <option value="<?php echo e($k); ?>"><?php echo e($lbl); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo _l('aae_amount'); ?> (€)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required />
                </div>
                <div class="form-group">
                    <label><?php echo _l('aae_reference'); ?></label>
                    <input type="text" name="reference" class="form-control" placeholder="ex. Virement BNA..." />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('aae_cancel'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('aae_save'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<!-- Modal Edit Payment (generated per payment) -->
<?php foreach ($payments as $pmt) { ?>
<div class="modal fade" id="modal_edit_payment_<?php echo (int) $pmt->id; ?>" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open(admin_url('aae/aae_order/edit_payment/' . $order->id)); ?>
            <input type="hidden" name="payment_id" value="<?php echo (int) $pmt->id; ?>" />
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo _l('aae_edit_payment'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><?php echo _l('aae_payment_type'); ?></label>
                    <select name="payment_type" class="form-control" required>
                        <?php foreach (aae_order_payment_types() as $k => $lbl) { ?>
                        <option value="<?php echo e($k); ?>" <?php echo $pmt->payment_type === $k ? 'selected' : ''; ?>><?php echo e($lbl); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo _l('aae_amount'); ?> (€)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required value="<?php echo e($pmt->amount); ?>" />
                </div>
                <div class="form-group">
                    <label><?php echo _l('aae_currency'); ?></label>
                    <input type="text" name="currency" class="form-control" value="<?php echo e($pmt->currency ?? 'EUR'); ?>" />
                </div>
                <div class="form-group">
                    <label><?php echo _l('aae_reference'); ?></label>
                    <input type="text" name="reference" class="form-control" placeholder="ex. Virement BNA..." value="<?php echo e($pmt->reference); ?>" />
                </div>
                <div class="form-group">
                    <label><?php echo _l('aae_date'); ?></label>
                    <input type="datetime-local" name="datepaid" class="form-control" value="<?php echo e(date('Y-m-d\TH:i', strtotime($pmt->datepaid))); ?>" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('aae_cancel'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('aae_save'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<!-- Modal Delete Payment (generated per payment) -->
<div class="modal fade" id="modal_delete_payment_<?php echo (int) $pmt->id; ?>" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open(admin_url('aae/aae_order/delete_payment/' . $order->id)); ?>
            <input type="hidden" name="payment_id" value="<?php echo (int) $pmt->id; ?>" />
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo _l('aae_delete_payment'); ?></h4>
            </div>
            <div class="modal-body">
                <p class="text-danger"><?php echo _l('aae_confirm_delete_payment'); ?></p>
                <div class="alert alert-info">
                    <strong><?php echo aae_order_payment_types()[$pmt->payment_type] ?? $pmt->payment_type; ?></strong> :
                    <?php echo aae_format_eur($pmt->amount); ?><br>
                    <?php echo _l('aae_reference'); ?>: <?php echo e($pmt->reference ?: '-'); ?><br>
                    <?php echo _l('aae_date'); ?>: <?php echo _dt($pmt->datepaid); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('aae_cancel'); ?></button>
                <button type="submit" class="btn btn-danger"><?php echo _l('aae_delete_payment'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php } ?>

<!-- Modal Record Client Payment (accounting invoice) -->
<?php if (($order->invoice_id || $order->final_invoice_id) && staff_can('create', 'payments')) { ?>
<div class="modal fade" id="modal_invoice_payment" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open(admin_url('aae/aae_order/record_client_payment/' . $order->id)); ?>
            <input type="hidden" name="invoice_target" id="aae_invoice_payment_target" value="<?php echo $order->invoice_id ? 'invoice' : 'final'; ?>" />
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo _l('aae_record_client_payment'); ?></h4>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    <?php echo _l('aae_invoice'); ?> :
                    <strong id="aae_invoice_payment_number"><?php echo e(format_invoice_number($order->invoice_id ?: $order->final_invoice_id)); ?></strong>
                </p>
                <div class="form-group">
                    <label><?php echo _l('aae_amount'); ?> (€)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required />
                </div>
                <div class="form-group">
                    <label><?php echo _l('aae_payment_mode'); ?></label>
                    <select name="paymentmode" class="form-control">
                        <option value=""><?php echo _l('aae_none'); ?></option>
                        <?php foreach ($payment_modes as $mode) { ?>
                        <option value="<?php echo (int) $mode['id']; ?>"><?php echo e($mode['name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo _l('aae_reference'); ?></label>
                    <input type="text" name="transactionid" class="form-control" placeholder="ex. Virement BNA..." />
                </div>
                <div class="form-group">
                    <label><?php echo _l('aae_date'); ?></label>
                    <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" />
                </div>
                <div class="form-group">
                    <label><?php echo _l('aae_notes'); ?></label>
                    <textarea name="note" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('aae_cancel'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('aae_save'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php } ?>

<!-- Modal Message History -->
<div class="modal fade" id="modal_message_history" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo _l('aae_message_history'); ?></h4>
            </div>
            <div class="modal-body">
                <?php
                // Filter events for message type
                $message_events = array_filter($events, function($ev) {
                    return $ev->event_type === 'message';
                });
                ?>
                <?php if (empty($message_events)) { ?>
                    <div class="text-center tw-p-8">
                        <i class="fa fa-comments fa-3x tw-text-neutral-300" aria-hidden="true"></i>
                        <p class="tw-mt-4 tw-text-neutral-500"><?php echo _l('aae_no_message_history'); ?></p>
                    </div>
                <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th><?php echo _l('aae_message_date'); ?></th>
                                    <th><?php echo _l('aae_message_channel'); ?></th>
                                    <th><?php echo _l('aae_message_stage'); ?></th>
                                    <th><?php echo _l('aae_message_language'); ?></th>
                                    <th><?php echo _l('aae_message_content'); ?></th>
                                    <th><?php echo _l('aae_message_sent_by'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_reverse($message_events) as $ev) { ?>
                                    <?php
                                    // Parse event_description: '[LANG - STAGE] message content'
                                    $desc = $ev->event_description;
                                    $lang = '';
                                    $stage_label = '';
                                    $content = $desc;
                                    
                                    if (preg_match('/^\[(.*?) - (.*?)\]\s*(.*)$/s', $desc, $matches)) {
                                        $lang = $matches[1];
                                        $stage_label = $matches[2];
                                        $content = $matches[3];
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo _dt($ev->datecreated); ?></td>
                                        <td>
                                            <?php
                                            // Extract channel from event_title: 'Message sent (WhatsApp)'
                                            if (preg_match('/\((.*?)\)/', $ev->event_title, $m)) {
                                                echo e($m[1]);
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo e($stage_label); ?></td>
                                        <td><?php echo e($lang); ?></td>
                                        <td class="tw-max-w-xs tw-truncate tw-block"><?php echo e(mb_substr($content, 0, 200)); ?></td>
                                        <td><?php echo e(get_staff_full_name($ev->addedfrom)); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('aae_close'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
</body>
</html>
