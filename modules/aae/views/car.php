<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
$car_id       = (int) aae_value($car, 'id', 0);
$is_edit      = $car_id > 0;
$form_action  = $is_edit ? admin_url('aae/car/' . $car_id) : admin_url('aae/car');
$client_id    = (int) aae_value($car, 'client_id', $prefill_client_id);
$invoice_id   = (int) aae_value($car, 'invoice_id', 0);
$status_value = aae_value($car, 'status', 'available');
$dest_value   = aae_value($car, 'destination', 'algeria');
$year_value   = (int) aae_value($car, 'model_year', 0);
$year_value   = $year_value > 0 ? $year_value : '';
$source       = aae_value($car, 'source', 'manual');
?>
<div id="wrapper">
    <div class="content">
        <?php echo form_open($form_action, ['id' => 'aae-car-form', 'data-is-new' => $is_edit ? '0' : '1']); ?>
        <div class="aae-page-header">
            <div>
                <a href="<?php echo admin_url('aae'); ?>" class="aae-back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    <?php echo _l('aae_catalogue'); ?>
                </a>
                <h1 class="aae-page-title"><?php echo $is_edit ? _l('aae_edit_car') : _l('aae_new_car'); ?></h1>
                <?php if ($is_edit) { ?>
                <p class="aae-page-subtitle">
                    <?php echo e(aae_value($car, 'brand')); ?>
                    <?php echo e(aae_value($car, 'model')); ?>
                    <?php if ($year_value) { ?>
                    &middot; <?php echo (int) $year_value; ?>
                    <?php } ?>
                </p>
                <?php } ?>
            </div>
            <div class="aae-page-actions">
                <a href="<?php echo admin_url('aae'); ?>" class="btn btn-default"><?php echo _l('aae_cancel'); ?></a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-regular fa-floppy-disk tw-mr-1"></i>
                    <?php echo _l('aae_save'); ?>
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="aae-section-title"><?php echo _l('aae_vehicle_information'); ?></h4>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="brand" class="control-label"><?php echo _l('aae_brand'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" id="brand" name="brand" class="form-control" required
                                        list="aae-brands" autocomplete="off"
                                        value="<?php echo e(aae_value($car, 'brand')); ?>" />
                                    <datalist id="aae-brands">
                                        <?php foreach ($brands as $brand) { ?>
                                        <option value="<?php echo e($brand); ?>"></option>
                                        <?php } ?>
                                    </datalist>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="model" class="control-label"><?php echo _l('aae_model'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" id="model" name="model" class="form-control" required
                                        list="aae-models" autocomplete="off"
                                        value="<?php echo e(aae_value($car, 'model')); ?>" />
                                    <datalist id="aae-models"></datalist>
                                    <p class="help-block tw-mb-0"><?php echo _l('aae_model_brand_hint'); ?></p>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="engine" class="control-label"><?php echo _l('aae_engine'); ?></label>
                                    <input type="text" id="engine" name="engine" class="form-control"
                                        value="<?php echo e(aae_value($car, 'engine')); ?>" />
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="model_year" class="control-label"><?php echo _l('aae_year'); ?></label>
                                    <input type="number" id="model_year" name="model_year" class="form-control"
                                        min="0" max="2100" step="1" placeholder="<?php echo _l('aae_year_placeholder'); ?>"
                                        value="<?php echo e($year_value); ?>" />
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="color" class="control-label"><?php echo _l('aae_color'); ?></label>
                                    <input type="text" id="color" name="color" class="form-control"
                                        value="<?php echo e(aae_value($car, 'color')); ?>" />
                                </div>
                            </div>
                        </div>

                        <hr />

                        <div class="aae-section-heading">
                            <h4 class="aae-section-title"><?php echo _l('aae_pricing_usd'); ?></h4>
                            <span class="aae-section-hint"><?php echo _l('aae_total_usd_hint'); ?></span>
                        </div>

                        <div class="row">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="fob_price" class="control-label"><?php echo _l('aae_fob_price'); ?> <span class="text-danger">*</span></label>
                                    <input type="number" id="fob_price" name="fob_price" class="form-control aae-price-base"
                                        min="0" step="0.01" value="<?php echo e(aae_value($car, 'fob_price')); ?>" />
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="doc_fee" class="control-label"><?php echo _l('aae_doc_fee'); ?></label>
                                    <input type="number" id="doc_fee" name="doc_fee" class="form-control aae-price-base"
                                        min="0" step="0.01" value="<?php echo e(aae_value($car, 'doc_fee', $defaults['doc_fee'])); ?>" />
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="vehicle_fee" class="control-label"><?php echo _l('aae_vehicle_fee'); ?></label>
                                    <input type="number" id="vehicle_fee" name="vehicle_fee" class="form-control aae-price-base"
                                        min="0" step="0.01" value="<?php echo e(aae_value($car, 'vehicle_fee', $defaults['vehicle_fee'])); ?>" />
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="delivery_fee" class="control-label"><?php echo _l('aae_delivery_fee'); ?></label>
                                    <input type="number" id="delivery_fee" name="delivery_fee" class="form-control aae-price-base"
                                        min="0" step="0.01" value="<?php echo e(aae_value($car, 'delivery_fee', $defaults['delivery_fee'])); ?>" />
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="total_usd" class="control-label"><?php echo _l('aae_total_usd'); ?></label>
                                    <input type="number" id="total_usd" name="total_usd" class="form-control aae-computed"
                                        readonly value="<?php echo e(aae_value($car, 'total_usd')); ?>" />
                                </div>
                            </div>
                        </div>

                        <div class="aae-section-heading">
                            <h4 class="aae-section-title"><?php echo _l('aae_pricing_eur_dzd'); ?></h4>
                            <div class="aae-inline-actions">
                                <label class="aae-checkbox">
                                    <input type="checkbox" id="aae-auto-calc" <?php echo $is_edit ? '' : 'checked'; ?> />
                                    <?php echo _l('aae_auto_calculate'); ?>
                                </label>
                                <button type="button" id="aae-recalculate" class="btn btn-default btn-sm">
                                    <i class="fa-solid fa-calculator tw-mr-1"></i>
                                    <?php echo _l('aae_recalculate'); ?>
                                </button>
                            </div>
                        </div>

                        <p class="aae-formula-note">
                            <?php echo _l('aae_rates_used', [
                                number_format($rates['eur_markup'], 2, '.', ''),
                                number_format($rates['dzd_rate'], 2, '.', ''),
                            ]); ?>
                        </p>

                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="price_eur" class="control-label"><?php echo _l('aae_price_eur'); ?></label>
                                    <input type="number" id="price_eur" name="price_eur" class="form-control aae-computed"
                                        min="0" step="0.01" value="<?php echo e(aae_value($car, 'price_eur')); ?>" />
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="commission" class="control-label"><?php echo _l('aae_commission'); ?></label>
                                    <input type="number" id="commission" name="commission" class="form-control aae-price-base"
                                        min="0" step="0.01" value="<?php echo e(aae_value($car, 'commission', $defaults['commission'])); ?>" />
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="total_ttc" class="control-label"><?php echo _l('aae_total_ttc'); ?></label>
                                    <input type="number" id="total_ttc" name="total_ttc" class="form-control aae-computed"
                                        min="0" step="0.01" value="<?php echo e(aae_value($car, 'total_ttc')); ?>" />
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="price_dzd" class="control-label"><?php echo _l('aae_price_dzd'); ?></label>
                                    <input type="number" id="price_dzd" name="price_dzd" class="form-control aae-computed"
                                        min="0" step="0.01" value="<?php echo e(aae_value($car, 'price_dzd')); ?>" />
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="price_millions" class="control-label"><?php echo _l('aae_price_millions'); ?></label>
                                    <input type="number" id="price_millions" name="price_millions" class="form-control aae-computed"
                                        min="0" step="0.001" value="<?php echo e(aae_value($car, 'price_millions')); ?>" />
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="customs_estimate" class="control-label"><?php echo _l('aae_customs_estimate'); ?></label>
                                    <input type="number" id="customs_estimate" name="customs_estimate" class="form-control"
                                        min="0" step="0.01" value="<?php echo e(aae_value($car, 'customs_estimate')); ?>" />
                                    <p class="help-block"><?php echo _l('aae_customs_hint'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="aae-section-title"><?php echo _l('aae_availability'); ?></h4>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="status" class="control-label"><?php echo _l('aae_status'); ?></label>
                                    <select id="status" name="status" class="form-control">
                                        <?php foreach ($statuses as $status) { ?>
                                        <option value="<?php echo e($status); ?>" <?php echo $status === $status_value ? 'selected' : ''; ?>>
                                            <?php echo e(_l('aae_status_' . $status)); ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="destination" class="control-label"><?php echo _l('aae_destination'); ?></label>
                                    <select id="destination" name="destination" class="form-control">
                                        <?php foreach ($destinations as $destination) { ?>
                                        <option value="<?php echo e($destination); ?>" <?php echo $destination === $dest_value ? 'selected' : ''; ?>>
                                            <?php echo e(_l('aae_destination_' . $destination)); ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="quantity" class="control-label"><?php echo _l('aae_quantity'); ?></label>
                                    <input type="number" id="quantity" name="quantity" class="form-control"
                                        min="0" step="1" value="<?php echo (int) aae_value($car, 'quantity', 1); ?>" />
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="client_id" value="<?php echo (int) $client_id; ?>" />
                        <input type="hidden" name="invoice_id" value="<?php echo (int) $invoice_id; ?>" />

                        <hr />

                        <h4 class="aae-section-title"><?php echo _l('aae_link_notes'); ?></h4>

                        <div class="form-group">
                            <label for="link" class="control-label"><?php echo _l('aae_link'); ?></label>
                            <input type="text" id="link" name="link" class="form-control"
                                placeholder="https://www.dongchedi.com/..." value="<?php echo e(aae_value($car, 'link')); ?>" />
                            <p class="help-block"><?php echo _l('aae_link_help'); ?></p>
                        </div>

                        <div class="form-group">
                            <label for="notes" class="control-label"><?php echo _l('aae_notes'); ?></label>
                            <textarea id="notes" name="notes" class="form-control" rows="4"><?php echo e(aae_value($car, 'notes')); ?></textarea>
                        </div>

                        <?php if ($is_edit) { ?>
                        <div class="aae-meta">
                            <span>
                                <i class="fa-solid fa-database"></i>
                                <?php echo _l('aae_source'); ?>:
                                <strong><?php echo e(_l('aae_source_' . $source)); ?></strong>
                            </span>
                            <span>
                                <i class="fa-regular fa-calendar"></i>
                                <?php echo _l('aae_datecreated'); ?>:
                                <strong><?php echo e(_d(aae_value($car, 'datecreated'))); ?></strong>
                            </span>
                            <?php if (aae_value($car, 'dateupdated')) { ?>
                            <span>
                                <i class="fa-solid fa-rotate"></i>
                                <?php echo _l('aae_dateupdated'); ?>:
                                <strong><?php echo e(_dt(aae_value($car, 'dateupdated'))); ?></strong>
                            </span>
                            <?php } ?>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="aae-form-footer">
            <a href="<?php echo admin_url('aae'); ?>" class="btn btn-default"><?php echo _l('aae_cancel'); ?></a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-regular fa-floppy-disk tw-mr-1"></i>
                <?php echo _l('aae_save'); ?>
            </button>
            <?php if ($is_edit && staff_can('delete', 'aae')) { ?>
            <a href="<?php echo admin_url('aae/delete/' . $car_id); ?>" class="btn btn-danger pull-right _delete">
                <i class="fa-regular fa-trash-can tw-mr-1"></i>
                <?php echo _l('delete'); ?>
            </a>
            <?php } ?>
        </div>

        <?php echo form_close(); ?>
    </div>
</div>
<script>
    window.aaeModelsByBrand = <?php echo json_encode($models_by_brand, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<?php init_tail(); ?>
</body>

</html>
