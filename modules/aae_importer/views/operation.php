<?php defined('BASEPATH') or exit('No direct script access allowed');

// Dates du pipeline -> label / cle de statut
$pipeline = [
    'order_date'      => ['status' => 'commande',   'label' => _l('aae_op_order_date'),     'icon' => 'fa-file-text-o'],
    'production_date' => ['status' => 'production', 'label' => _l('aae_op_production_date'), 'icon' => 'fa-wrench'],
    'departure_date'  => ['status' => 'embarque',   'label' => _l('aae_op_departure_date'), 'icon' => 'fa-ship'],
    'arrival_date'    => ['status' => 'arrive',     'label' => _l('aae_op_arrival_date'),   'icon' => 'fa-map-marker'],
    'customs_date'    => ['status' => 'douane',     'label' => _l('aae_op_customs_date'),   'icon' => 'fa-flag'],
    'delivery_date'   => ['status' => 'livre',      'label' => _l('aae_op_delivery_date'),  'icon' => 'fa-check-circle-o'],
];

$sname  = isset($statuses[$operation->status]) ? $statuses[$operation->status]['name'] : $operation->status;
$scolor = isset($statuses[$operation->status]) ? $statuses[$operation->status]['color'] : '#6c757d';

init_head(); ?>
<div id="wrapper">
<div class="content">

<div class="row" style="margin-bottom:18px;">
    <div class="col-md-12">
        <h4 class="no-margin display-block" style="font-size:18px;">
            <i class="fa fa-ship"></i> <?php echo e($operation->ref); ?>
            <span class="aae-badge" style="background:<?php echo $scolor; ?>;color:#fff;vertical-align:middle;margin-left:10px;"><?php echo e($sname); ?></span>
        </h4>
        <div style="margin-top:10px;" class="btn-group">
            <a href="<?php echo admin_url('aae_importer/operations'); ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> <?php echo _l('aae_back_to_list'); ?></a>
            <?php if (staff_can('edit', 'aae_importer')) { ?>
                <a href="<?php echo admin_url('aae_importer/operation_edit/' . (int)$operation->id); ?>" class="btn btn-default"><i class="fa fa-pencil"></i> <?php echo _l('aae_edit'); ?></a>
            <?php } ?>
            <?php if (isset($next_status) && $next_status) {
                $nextName = isset($statuses[$next_status]) ? $statuses[$next_status]['name'] : $next_status;
                $nextColor = isset($statuses[$next_status]) ? $statuses[$next_status]['color'] : '#009efb';
            ?>
                <?php echo form_open(admin_url('aae_importer/operation_status'), ['style' => 'display:inline-block;']); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$operation->id; ?>">
                    <input type="hidden" name="status" value="<?php echo e($next_status); ?>">
                    <input type="hidden" name="redirect" value="<?php echo e(current_url()); ?>">
                    <button type="submit" class="btn btn-primary" style="border-color:<?php echo $nextColor; ?>;"><i class="fa fa-arrow-right"></i> <?php echo _l('aae_advance_status'); ?> : <?php echo e($nextName); ?></button>
                <?php echo form_close(); ?>
            <?php } ?>
            <?php if (staff_can('delete', 'aae_importer')) { ?>
                <?php echo form_open(admin_url('aae_importer/operation_delete'), ['style' => 'display:inline-block;', 'onsubmit' => 'return confirm(\'' . _l('aae_confirm_delete_operation') . '\');']); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$operation->id; ?>">
                    <button type="submit" class="btn btn-default" style="color:#dc3545;"><i class="fa fa-trash"></i> <?php echo _l('delete'); ?></button>
                <?php echo form_close(); ?>
            <?php } ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">

        <!-- Vehicule -->
        <div class="panel_s">
            <div class="panel-body">
                <h5 class="bold no-margin"><i class="fa fa-car"></i> <?php echo _l('aae_vehicle'); ?></h5>
                <hr class="hr-panel-heading">
                <?php if ($operation->vehicle) { ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="aae-detail-line">
                                <span class="aae-detail-label"><?php echo _l('aae_marque'); ?></span>
                                <span class="bold"><?php echo e($operation->vehicle->marque); ?></span>
                            </div>
                            <div class="aae-detail-line">
                                <span class="aae-detail-label"><?php echo _l('aae_modele'); ?></span>
                                <span class="bold"><?php echo e($operation->vehicle->modele); ?></span>
                            </div>
                            <div class="aae-detail-line">
                                <span class="aae-detail-label"><?php echo _l('aae_moteur'); ?></span>
                                <span><?php echo e($operation->vehicle->moteur); ?></span>
                            </div>
                            <div class="aae-detail-line">
                                <span class="aae-detail-label"><?php echo _l('aae_annee'); ?></span>
                                <span><?php echo $operation->vehicle->annee ? (int)$operation->vehicle->annee : _l('aae_none'); ?></span>
                            </div>
                            <div class="aae-detail-line">
                                <span class="aae-detail-label"><?php echo _l('aae_op_qty'); ?></span>
                                <span>x<?php echo (int)$operation->qty; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="aae-detail-line">
                                <span class="aae-detail-label"><?php echo _l('aae_ttc_dzd'); ?></span>
                                <span class="bold"><?php echo $operation->vehicle->ttc_dzd ? number_format((float)$operation->vehicle->ttc_dzd, 0, ',', ' ') . ' DA' : _l('aae_none'); ?></span>
                            </div>
                            <div class="aae-detail-line">
                                <span class="aae-detail-label"><?php echo _l('aae_customs_rate'); ?></span>
                                <span><?php echo $operation->vehicle->customs_rate !== null ? (float)$operation->vehicle->customs_rate . ' %' : _l('aae_none'); ?></span>
                            </div>
                            <div class="aae-detail-line">
                                <span class="aae-detail-label"><?php echo _l('aae_op_eta'); ?></span>
                                <span><?php echo $operation->eta_date ? _d($operation->eta_date) : _l('aae_none'); ?></span>
                            </div>
                            <?php if ($operation->v_lien) { ?>
                                <div class="aae-detail-line">
                                    <span class="aae-detail-label"><?php echo _l('aae_lien'); ?></span>
                                    <a href="<?php echo e($operation->v_lien); ?>" target="_blank" rel="noopener"><i class="fa fa-external-link"></i> <?php echo _l('aae_view_full'); ?></a>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- Dates du pipeline -->
        <div class="panel_s">
            <div class="panel-body">
                <h5 class="bold no-margin"><i class="fa fa-calendar"></i> <?php echo _l('aae_dates'); ?></h5>
                <hr class="hr-panel-heading">
                <div class="row">
                    <?php $sidxOrder = array_keys($statuses); $currentIdx = array_search($operation->status, $sidxOrder); ?>
                    <?php foreach ($pipeline as $field => $meta) {
                        $stepIdx   = array_search($meta['status'], $sidxOrder);
                        $isTrash   = $operation->status === 'annule';
                        $isDone    = !$isTrash && $currentIdx !== false && $stepIdx !== false && $stepIdx <= $currentIdx;
                        $isCurrent = !$isTrash && $currentIdx !== false && $stepIdx === $currentIdx;
                        $dateValue = !empty($operation->{$field}) ? _d($operation->{$field}) : _l('aae_none');
                    ?>
                        <div class="col-md-4 aae-pipeline-step<?php echo $isCurrent ? ' current' : ($isDone ? ' done' : ''); ?>">
                            <i class="fa <?php echo e($meta['icon']); ?>"></i>
                            <div class="aae-pipeline-label"><?php echo e($meta['label']); ?></div>
                            <div class="aae-pipeline-date"><?php echo $dateValue; ?></div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Couts -->
        <div class="panel_s">
            <div class="panel-body">
                <h5 class="bold no-margin"><i class="fa fa-money"></i> <?php echo _l('aae_costs'); ?></h5>
                <hr class="hr-panel-heading">
                <div class="row">
                    <div class="col-md-4">
                        <div class="aae-detail-line">
                            <span class="aae-detail-label"><?php echo _l('aae_fob_paid'); ?></span>
                            <span><?php echo !empty($operation->fob_paid) ? number_format((float)$operation->fob_paid, 0, ',', ' ') . ' $' : _l('aae_none'); ?></span>
                        </div>
                        <div class="aae-detail-line">
                            <span class="aae-detail-label"><?php echo _l('aae_shipping_paid'); ?></span>
                            <span><?php echo !empty($operation->shipping_paid) ? number_format((float)$operation->shipping_paid, 0, ',', ' ') . ' $' : _l('aae_none'); ?></span>
                        </div>
                        <div class="aae-detail-line">
                            <span class="aae-detail-label"><?php echo _l('aae_translation_paid'); ?></span>
                            <span><?php echo !empty($operation->translation_paid) ? number_format((float)$operation->translation_paid, 0, ',', ' ') . ' $' : _l('aae_none'); ?></span>
                        </div>
                        <div class="aae-detail-line">
                            <span class="aae-detail-label"><?php echo _l('aae_commission_paid'); ?></span>
                            <span><?php echo !empty($operation->commission_paid) ? number_format((float)$operation->commission_paid, 0, ',', ' ') . ' €' : _l('aae_none'); ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="aae-detail-line">
                            <span class="aae-detail-label"><?php echo _l('aae_customs_amount'); ?></span>
                            <span><?php echo !empty($operation->customs_amount) ? number_format((float)$operation->customs_amount, 0, ',', ' ') . ' DA' : _l('aae_none'); ?></span>
                        </div>
                        <div class="aae-detail-line">
                            <span class="aae-detail-label"><?php echo _l('aae_other_costs'); ?></span>
                            <span><?php echo !empty($operation->other_costs) ? number_format((float)$operation->other_costs, 0, ',', ' ') . ' DA' : _l('aae_none'); ?></span>
                        </div>
                        <div class="aae-detail-line">
                            <span class="aae-detail-label"><?php echo _l('aae_total_cost'); ?></span>
                            <span class="bold"><?php echo !empty($operation->total_cost) ? number_format((float)$operation->total_cost, 0, ',', ' ') . ' DA' : _l('aae_none'); ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="aae-detail-line">
                            <span class="aae-detail-label"><?php echo _l('aae_sale_price'); ?></span>
                            <span class="bold"><?php echo !empty($operation->sale_price) ? number_format((float)$operation->sale_price, 0, ',', ' ') . ' DA' : _l('aae_none'); ?></span>
                        </div>
                        <div class="aae-detail-line">
                            <span class="aae-detail-label"><?php echo _l('aae_margin'); ?></span>
                            <?php if (!empty($operation->margin)) { ?>
                                <span class="bold" style="font-size:16px;color:<?php echo (float)$operation->margin >= 0 ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo number_format((float)$operation->margin, 0, ',', ' '); ?> DA
                                </span>
                            <?php } else { echo _l('aae_none'); } ?>
                        </div>
                        <div class="aae-detail-line">
                            <span class="aae-detail-label"><?php echo _l('aae_op_owner'); ?></span>
                            <span><?php echo !empty($operation->staff_id) ? '#' . (int)$operation->staff_id : _l('aae_none'); ?></span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($operation->notes)) { ?>
                    <hr class="hr-panel-heading">
                    <div class="text-muted"><b><?php echo _l('aae_notes'); ?> :</b></div>
                    <div class="aae-notes"><?php echo nl2br(e($operation->notes)); ?></div>
                <?php } ?>
            </div>
        </div>

        <!-- Historique -->
        <div class="panel_s">
            <div class="panel-body">
                <h5 class="bold no-margin"><i class="fa fa-history"></i> <?php echo _l('aae_history'); ?></h5>
                <hr class="hr-panel-heading">
                <?php if (count($history) > 0) { ?>
                    <ul class="aae-history">
                        <?php foreach ($history as $hrow) {
                            $hname = isset($statuses[$hrow['status']]) ? $statuses[$hrow['status']]['name'] : $hrow['status'];
                            $hcolor = isset($statuses[$hrow['status']]) ? $statuses[$hrow['status']]['color'] : '#6c757d';
                        ?>
                            <li>
                                <span class="aae-badge" style="background:<?php echo $hcolor; ?>;color:#fff;"><?php echo e($hname); ?></span>
                                <span class="text-muted" style="margin-left:8px;"><i class="fa fa-clock-o"></i> <?php echo _dt($hrow['date_added']); ?></span>
                                <?php if (!empty($hrow['note'])) { ?>
                                    <div class="aae-history-note"><?php echo nl2br(e($hrow['note'])); ?></div>
                                <?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } else { ?>
                    <p class="text-muted"><?php echo _l('aae_none'); ?></p>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Colonne droite -->
    <div class="col-md-4">

        <!-- Client -->
        <div class="panel_s">
            <div class="panel-body">
                <h5 class="bold no-margin"><i class="fa fa-building"></i> <?php echo _l('aae_op_client'); ?></h5>
                <hr class="hr-panel-heading">
                <?php if ($operation->client) { ?>
                    <a href="<?php echo admin_url('clients/client/' . (int)$operation->clientid); ?>" class="bold">
                        <?php echo e($operation->client->company); ?>
                    </a>
                <?php } else { ?>
                    <p class="text-muted"><?php echo _l('aae_select_client'); ?></p>
                <?php } ?>
            </div>
        </div>

        <!-- Changer statut -->
        <?php if (staff_can('edit', 'aae_importer')) { ?>
        <div class="panel_s">
            <div class="panel-body">
                <h5 class="bold no-margin"><i class="fa fa-exchange"></i> <?php echo _l('aae_change_status'); ?></h5>
                <hr class="hr-panel-heading">
                <?php echo form_open(admin_url('aae_importer/operation_status')); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$operation->id; ?>">
                    <input type="hidden" name="redirect" value="<?php echo e(current_url()); ?>">
                    <div class="form-group">
                        <select class="form-control" name="status" id="aaeChangeStatusSelect">
                            <?php foreach ($statuses as $key => $s) { ?>
                                <option value="<?php echo e($key); ?>"<?php echo $key === $operation->status ? ' selected' : ''; ?>><?php echo e($s['name']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <textarea class="form-control" name="note" rows="2" placeholder="<?php echo _l('aae_status_note'); ?>"></textarea>
                    </div>
                    <button type="submit" class="btn btn-info btn-block"><i class="fa fa-exchange"></i> <?php echo _l('aae_change_status'); ?></button>
                <?php echo form_close(); ?>
            </div>
        </div>
        <?php } ?>

        <!-- Devis lie -->
        <?php if ((int)$operation->estimate_id > 0) { ?>
        <div class="panel_s">
            <div class="panel-body">
                <h5 class="bold no-margin"><i class="fa fa-file-text-o"></i> <?php echo _l('aae_estimate_link'); ?></h5>
                <hr class="hr-panel-heading">
                <a href="<?php echo admin_url('estimates/list_estimates/' . (int)$operation->estimate_id); ?>" class="btn btn-default btn-block">
                    <i class="fa fa-file-text-o"></i> #<?php echo (int)$operation->estimate_id; ?>
                </a>
            </div>
        </div>
        <?php } ?>

    </div>
</div>

    </div>
</div>
<?php init_tail(); ?>
</body>

</html>
