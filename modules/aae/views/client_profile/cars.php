<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (isset($client)) { ?>
<?php $client_cars = aae_get_client_cars($client->userid); ?>
<h4 class="customer-profile-group-heading"><?php echo _l('aae_cars'); ?></h4>

<div class="aae-client-tab">
    <div class="aae-client-tab-actions">
        <?php if (staff_can('create', 'aae')) { ?>
        <a href="<?php echo admin_url('aae/car?client_id=' . (int) $client->userid); ?>" class="btn btn-primary">
            <i class="fa-regular fa-plus tw-mr-1"></i>
            <?php echo _l('aae_new_car'); ?>
        </a>
        <?php } ?>
        <a href="<?php echo admin_url('aae?client_id=' . (int) $client->userid); ?>" class="btn btn-default">
            <i class="fa-solid fa-list tw-mr-1"></i>
            <?php echo _l('aae_view_in_catalogue'); ?>
        </a>
    </div>

    <?php if (empty($client_cars)) { ?>
    <div class="aae-empty-state">
        <i class="fa-solid fa-car-side"></i>
        <p><?php echo _l('aae_client_no_cars'); ?></p>
    </div>
    <?php } else { ?>
    <div class="table-responsive">
        <table class="table table-striped aae-client-cars">
            <thead>
                <tr>
                    <th><?php echo _l('aae_id'); ?></th>
                    <th><?php echo _l('aae_vehicle'); ?></th>
                    <th><?php echo _l('aae_year'); ?></th>
                    <th><?php echo _l('aae_quantity'); ?></th>
                    <th><?php echo _l('aae_status'); ?></th>
                    <th><?php echo _l('aae_destination'); ?></th>
                    <th><?php echo _l('aae_total_ttc'); ?></th>
                    <th><?php echo _l('aae_price_dzd'); ?></th>
                    <th><?php echo _l('aae_invoice'); ?></th>
                    <th><?php echo _l('aae_options'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($client_cars as $car) { ?>
                <tr>
                    <td><span class="aae-id">#<?php echo (int) $car->id; ?></span></td>
                    <td>
                        <a href="<?php echo admin_url('aae/car/' . (int) $car->id); ?>" class="aae-model-link">
                            <?php echo e($car->brand); ?> <?php echo e($car->model); ?>
                        </a>
                        <?php if (! empty($car->engine)) { ?>
                        <div class="aae-model-meta"><?php echo e($car->engine); ?></div>
                        <?php } ?>
                        <?php if (! empty($car->color)) { ?>
                        <div class="aae-model-meta"><?php echo e($car->color); ?></div>
                        <?php } ?>
                    </td>
                    <td><?php echo (int) $car->model_year > 0 ? (int) $car->model_year : '-'; ?></td>
                    <td><span class="aae-qty"><?php echo (int) $car->quantity; ?></span></td>
                    <td><?php echo aae_status_badge($car->status); ?></td>
                    <td><?php echo aae_destination_badge($car->destination); ?></td>
                    <td><span class="aae-money"><?php echo aae_format_money($car->total_ttc, 'EUR'); ?></span></td>
                    <td>
                        <span class="aae-money"><?php echo aae_format_money($car->price_dzd, 'DZD'); ?></span>
                        <?php if ((float) $car->price_millions > 0) { ?>
                        <div class="aae-model-meta"><?php echo aae_format_millions($car->price_millions); ?></div>
                        <?php } ?>
                    </td>
                    <td>
                        <?php if ((int) $car->invoice_id > 0) { ?>
                        <a href="<?php echo aae_invoice_url($car->invoice_id); ?>">
                            <?php echo e(format_invoice_number($car->invoice_id)); ?>
                        </a>
                        <?php } else { ?>
                        <span class="text-muted">-</span>
                        <?php } ?>
                    </td>
                    <td>
                        <div class="aae-row-actions">
                            <a href="<?php echo admin_url('aae/car/' . (int) $car->id); ?>"
                                class="btn btn-default btn-icon btn-sm" title="<?php echo _l('edit'); ?>">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>
</div>
<?php } ?>
