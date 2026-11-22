<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (isset($client)) { ?>
<?php $client_orders = aae_get_client_orders($client->userid); ?>
<h4 class="customer-profile-group-heading"><?php echo _l('aae_orders'); ?></h4>

<div class="aae-client-tab">
    <div class="aae-client-tab-actions">
        <?php if (staff_can('create', 'aae')) { ?>
        <a href="<?php echo admin_url('aae/aae_order/create'); ?>" class="btn btn-primary">
            <i class="fa-regular fa-plus tw-mr-1"></i>
            <?php echo _l('aae_order_new'); ?>
        </a>
        <?php } ?>
        <a href="<?php echo admin_url('aae/aae_order'); ?>" class="btn btn-default">
            <i class="fa-solid fa-list tw-mr-1"></i>
            <?php echo _l('aae_orders'); ?>
        </a>
    </div>

    <?php if (empty($client_orders)) { ?>
    <div class="aae-empty-state">
        <i class="fa-solid fa-file-contract"></i>
        <p><?php echo _l('aae_client_no_orders'); ?></p>
    </div>
    <?php } else { ?>
    <div class="table-responsive">
        <table class="table table-striped aae-client-orders">
            <thead>
                <tr>
                    <th><?php echo _l('aae_id'); ?></th>
                    <th><?php echo _l('aae_order_stage'); ?></th>
                    <th><?php echo _l('aae_amount_client'); ?></th>
                    <th><?php echo _l('aae_amount_net_received'); ?></th>
                    <th><?php echo _l('aae_balance'); ?></th>
                    <th><?php echo _l('aae_datecreated'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($client_orders as $order) {
                    $balance = aae_order_client_balance($order);
                ?>
                <tr>
                    <td><a href="<?php echo admin_url('aae/aae_order/view/' . (int) $order->id); ?>">#<?php echo (int) $order->id; ?></a></td>
                    <td><?php echo aae_order_stage_badge($order->stage); ?></td>
                    <td><?php echo aae_format_eur($order->amount_client); ?></td>
                    <td><?php echo aae_format_eur($order->amount_net_received); ?></td>
                    <td>
                        <?php if ($balance > 0.01) { ?>
                        <span class="text-danger"><?php echo aae_format_eur($balance); ?></span>
                        <?php } elseif ($balance < -0.01) { ?>
                        <span class="text-warning"><?php echo aae_format_eur(abs($balance)); ?></span>
                        <?php } else { ?>
                        <span class="text-success"><i class="fa fa-check"></i></span>
                        <?php } ?>
                    </td>
                    <td><?php echo _d($order->datecreated); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>
</div>
<?php } ?>
