<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$prefill       = isset($prefill) && is_array($prefill) ? $prefill : [];
$prefill_client = (int) ($prefill['client_id'] ?? 0);
$prefill_notes  = (string) ($prefill['notes'] ?? '');
$from_lead_id   = (int) ($lead_id ?? 0);
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="aae-page-header">
            <div>
                <a href="<?php echo admin_url('aae/aae_order'); ?>" class="aae-back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    <?php echo _l('aae_orders'); ?>
                </a>
                <h1 class="aae-page-title"><?php echo _l('aae_order_new'); ?></h1>
            </div>
        </div>

        <?php if ($from_lead_id) { ?>
        <div class="alert alert-info">
            <i class="fa-regular fa-lightbulb"></i>
            <?php echo _l('aae_order_from_lead_help', $from_lead_id); ?>
            <a href="<?php echo admin_url('leads/lead/' . $from_lead_id); ?>" class="alert-link">
                <?php echo _l('view'); ?> <?php echo _l('lead'); ?>
            </a>
        </div>
        <?php } ?>

        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo form_open(admin_url('aae/aae_order/create')); ?>
                        <?php echo form_hidden('vehicle_source', $vehicle_source ?? 'catalogue'); ?>
                        
                        <div class="form-group">
                            <label for="client_id"><?php echo _l('aae_client'); ?> <span class="text-danger">*</span></label>
                            <select name="client_id" id="client_id" class="form-control selectpicker" data-live-search="true" required>
                                <option value=""><?php echo _l('dropdown_non_selected_tex'); ?></option>
                                <?php foreach ($clients as $c) { ?>
                                <option value="<?php echo (int) $c['userid']; ?>"<?php echo $prefill_client === (int) $c['userid'] ? ' selected' : ''; ?>><?php echo e($c['company']); ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="vehicle_id"><?php echo _l('aae_vehicle'); ?></label>
                            <select name="vehicle_id" id="vehicle_id" class="form-control selectpicker" data-live-search="true">
                                <option value=""><?php echo _l('dropdown_non_selected_tex'); ?></option>
                                <?php foreach ($cars as $car) { ?>
                                <option value="<?php echo (int) $car['id']; ?>"
                                    data-price-client="<?php echo (float) $car['total_ttc']; ?>"
                                    data-price-supplier="<?php echo (float) $car['price_eur']; ?>">
                                    #<?php echo (int) $car['id']; ?> - <?php echo e($car['brand'] . ' ' . $car['model'] . ' (' . $car['model_year'] . ') - ' . aae_format_money($car['total_ttc'], 'EUR')); ?>
                                </option>
                                <?php } ?>
                            </select>
                            <p class="help-block"><?php echo _l('aae_order_vehicle_help'); ?></p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount_client"><?php echo _l('aae_amount_client'); ?> (€)</label>
                                    <input type="number" step="0.01" name="amount_client" id="amount_client" class="form-control" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount_supplier"><?php echo _l('aae_amount_supplier'); ?> (€)</label>
                                    <input type="number" step="0.01" name="amount_supplier" id="amount_supplier" class="form-control" />
                                </div>
                            </div>
                        </div>
                        <p class="help-block"><?php echo _l('aae_order_amounts_autofill'); ?></p>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_name"><?php echo _l('aae_supplier_name'); ?></label>
                                    <input type="text" name="supplier_name" id="supplier_name" class="form-control" placeholder="ex. Fournisseur Chine..." />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_reference"><?php echo _l('aae_supplier_ref'); ?></label>
                                    <input type="text" name="supplier_reference" id="supplier_reference" class="form-control" />
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes"><?php echo _l('aae_notes'); ?></label>
                            <textarea name="notes" id="notes" class="form-control" rows="3"><?php echo e($prefill_notes); ?></textarea>
                        </div>

                        <hr />

                        <div class="text-right">
                            <a href="<?php echo admin_url('aae/aae_order'); ?>" class="btn btn-default"><?php echo _l('aae_cancel'); ?></a>
                            <button type="submit" class="btn btn-primary"><?php echo _l('aae_save'); ?></button>
                        </div>

                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
