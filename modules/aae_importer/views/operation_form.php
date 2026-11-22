<?php defined('BASEPATH') or exit('No direct script access allowed');

$isEdit     = isset($operation) && $operation;
$opVehicle  = $isEdit ? (int)$operation->vehicle_id : (isset($vehicle) && $vehicle ? (int)$vehicle->id : 0);
$opClient   = $isEdit ? (int)$operation->clientid : 0;

init_head(); ?>
<div id="wrapper">
<div class="content">

<div class="row" style="margin-bottom:18px;">
    <div class="col-md-12">
        <h4 class="no-margin display-block" style="font-size:18px;">
            <i class="fa fa-ship"></i>
            <?php echo $isEdit ? e($operation->ref) . ' — ' . _l('aae_edit') : _l('aae_new_operation'); ?>
        </h4>
        <div style="margin-top:10px;">
            <a href="<?php echo admin_url('aae_importer/operations'); ?>" class="btn btn-default"><i class="fa fa-arrow-left"></i> <?php echo _l('aae_back_to_list'); ?></a>
        </div>
    </div>
</div>

<div class="panel_s">
    <div class="panel-body">

        <?php echo form_open(admin_url('aae_importer/operation_save')); ?>
        <?php if ($isEdit) { ?>
            <input type="hidden" name="id" value="<?php echo (int)$operation->id; ?>">
        <?php } ?>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label" for="op_vehicle"><?php echo _l('aae_vehicle'); ?> *</label>
                    <select class="form-control" id="op_vehicle" name="vehicle_id" required>
                        <option value=""><?php echo _l('aae_select_vehicle'); ?></option>
                        <?php foreach ($vehicles as $v) { ?>
                            <option value="<?php echo (int)$v['id']; ?>"<?php echo $opVehicle === (int)$v['id'] ? ' selected' : ''; ?>>
                                <?php echo e($v['label']); ?><?php echo $v['meta'] !== '' ? ' (' . e($v['meta']) . ')' : ''; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="op_client"><?php echo _l('aae_op_client'); ?></label>
                    <select class="form-control" id="op_client" name="clientid">
                        <option value=""><?php echo _l('aae_select_client'); ?></option>
                        <?php foreach ($clients as $c) { ?>
                            <option value="<?php echo (int)$c['id']; ?>"<?php echo $opClient === (int)$c['id'] ? ' selected' : ''; ?>><?php echo e($c['label']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="op_qty"><?php echo _l('aae_op_qty'); ?></label>
                    <input type="number" min="1" step="1" class="form-control" id="op_qty" name="qty" value="<?php echo $isEdit ? (int)$operation->qty : 1; ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="op_status"><?php echo _l('aae_statut'); ?></label>
                    <select class="form-control" id="op_status" name="status">
                        <?php
                        $currentStatus = $isEdit ? $operation->status : 'demande';
                        foreach ($statuses as $key => $s) { ?>
                            <option value="<?php echo e($key); ?>"<?php echo $key === $currentStatus ? ' selected' : ''; ?>><?php echo e($s['name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="op_eta"><?php echo _l('aae_op_eta'); ?></label>
                    <input type="date" class="form-control" id="op_eta" name="eta_date" value="<?php echo $isEdit && $operation->eta_date ? $operation->eta_date : ''; ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="op_estimate">&nbsp;</label>
                    <p class="form-control-static text-muted small" style="display:block;">
                        <?php if ($isEdit && (int)$operation->estimate_id > 0) { ?>
                            <a href="<?php echo admin_url('estimates/list_estimates/' . (int)$operation->estimate_id); ?>"><i class="fa fa-file-text-o"></i> <?php echo _l('aae_estimate_link'); ?> #<?php echo (int)$operation->estimate_id; ?></a>
                        <?php } else { ?>
                            &nbsp;
                        <?php } ?>
                    </p>
                </div>
            </div>
        </div>

        <h5 class="bold" style="border-bottom:1px solid #eee;padding-bottom:6px;"><i class="fa fa-money"></i> <?php echo _l('aae_costs'); ?></h5>
        <p class="text-muted small"><?php echo _l('aae_costs_hint'); ?></p>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="op_fob_paid"><?php echo _l('aae_fob_paid'); ?> ($)</label>
                    <input type="number" step="0.01" class="form-control" id="op_fob_paid" name="fob_paid" value="<?php echo $isEdit ? e(isset($operation->fob_paid) ? $operation->fob_paid : '') : ''; ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="op_shipping_paid"><?php echo _l('aae_shipping_paid'); ?> ($)</label>
                    <input type="number" step="0.01" class="form-control" id="op_shipping_paid" name="shipping_paid" value="<?php echo $isEdit ? e(isset($operation->shipping_paid) ? $operation->shipping_paid : '') : ''; ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="op_translation_paid"><?php echo _l('aae_translation_paid'); ?> ($)</label>
                    <input type="number" step="0.01" class="form-control" id="op_translation_paid" name="translation_paid" value="<?php echo $isEdit ? e(isset($operation->translation_paid) ? $operation->translation_paid : '') : ''; ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="op_commission_paid"><?php echo _l('aae_commission_paid'); ?> (€)</label>
                    <input type="number" step="0.01" class="form-control" id="op_commission_paid" name="commission_paid" value="<?php echo $isEdit ? e(isset($operation->commission_paid) ? $operation->commission_paid : '') : ''; ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="op_customs_amount"><?php echo _l('aae_customs_amount'); ?> (DA)</label>
                    <input type="number" step="0.01" class="form-control" id="op_customs_amount" name="customs_amount" value="<?php echo $isEdit ? e(isset($operation->customs_amount) ? $operation->customs_amount : '') : ''; ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="op_other_costs"><?php echo _l('aae_other_costs'); ?> (DA)</label>
                    <input type="number" step="0.01" class="form-control" id="op_other_costs" name="other_costs" value="<?php echo $isEdit ? e(isset($operation->other_costs) ? $operation->other_costs : '') : ''; ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="op_sale_price"><?php echo _l('aae_sale_price'); ?> (DA)</label>
                    <input type="number" step="0.01" class="form-control" id="op_sale_price" name="sale_price" value="<?php echo $isEdit ? e(isset($operation->sale_price) ? $operation->sale_price : '') : ''; ?>">
                </div>
            </div>
            <div class="col-md-8">
                <div class="form-group">
                    <label for="op_notes"><?php echo _l('aae_notes'); ?></label>
                    <textarea class="form-control" id="op_notes" name="notes" rows="3"><?php echo $isEdit ? e(isset($operation->notes) ? $operation->notes : '') : ''; ?></textarea>
                </div>
            </div>
        </div>

        <div class="pull-right" style="margin-top:6px;">
            <a href="<?php echo admin_url('aae_importer/operations'); ?>" class="btn btn-default"><?php echo _l('cancel'); ?></a>
            <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> <?php echo _l('save'); ?></button>
        </div>

        <?php echo form_close(); ?>
    </div>
</div>

    </div>
</div>
<?php init_tail(); ?>
</body>

</html>
