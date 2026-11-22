<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="aae-page-header">
                    <div>
                        <h1 class="aae-page-title"><?php echo _l('aae_orders'); ?></h1>
                        <p class="aae-page-subtitle"><?php echo _l('aae_orders_subtitle'); ?></p>
                    </div>
                    <div class="aae-page-actions">
                        <a href="<?php echo admin_url('aae/aae_order/export_orders' . ($stage ? '?stage=' . $stage : '')); ?>" class="btn btn-default">
                            <i class="fa-solid fa-download tw-mr-1"></i>
                            <?php echo _l('aae_export_csv'); ?>
                        </a>
                        <a href="<?php echo admin_url('aae'); ?>" class="btn btn-default">
                            <i class="fa-solid fa-car tw-mr-1"></i>
                            <?php echo _l('aae_catalogue'); ?>
                        </a>
                        <?php if (staff_can('create', 'aae')) { ?>
                        <a href="<?php echo admin_url('aae/aae_order/create'); ?>" class="btn btn-primary">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo _l('aae_order_new'); ?>
                        </a>
                        <?php } ?>
                    </div>
                </div>

                <!-- Stage filter tabs -->
                <div class="panel_s">
                    <div class="panel-body">
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="<?php echo !$stage ? 'active' : ''; ?>">
                                <a href="<?php echo admin_url('aae/aae_order'); ?>">
                                    <?php echo _l('aae_all_stages'); ?>
                                </a>
                            </li>
                            <?php foreach ($stages as $key => $name) { ?>
                            <li role="presentation" class="<?php echo $stage === $key ? 'active' : ''; ?>">
                                <a href="<?php echo admin_url('aae/aae_order?stage=' . $key); ?>">
                                    <?php echo e($name); ?>
                                </a>
                            </li>
                            <?php } ?>
                        </ul>

                        <div class="tab-content mtop15">
                            <table class="table table-striped table-bordered dt-table" data-order-col="0" data-order-type="desc">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th><?php echo _l('aae_client'); ?></th>
                                        <th><?php echo _l('aae_vehicle'); ?></th>
                                        <th><?php echo _l('aae_order_stage'); ?></th>
                                        <th><?php echo _l('aae_amount_client'); ?></th>
                                        <th><?php echo _l('aae_amount_net_received'); ?></th>
                                        <th><?php echo _l('aae_balance'); ?></th>
                                        <th><?php echo _l('aae_datecreated'); ?></th>
                                        <th><?php echo _l('aae_options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order) { 
                                        $client = $this->aae_model->get_client($order->client_id);
                                        $vehicle = $this->aae_model->get_vehicle_by_source($order->vehicle_id, $order->vehicle_source ?? 'cars');
                                    ?>
                                    <tr>
                                        <td><a href="<?php echo admin_url('aae/aae_order/view/' . $order->id); ?>">#<?php echo (int) $order->id; ?></a></td>
                                        <td>
                                            <?php if ($client) { ?>
                                            <a href="<?php echo admin_url('clients/client/' . $client->userid); ?>"><?php echo e($client->company); ?></a>
                                            <?php } else { ?>
                                            <span class="text-muted"><?php echo _l('aae_no_client'); ?></span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if ($vehicle) { ?>
                                            <a href="<?php echo admin_url('aae/car/' . $vehicle->id); ?>"><?php echo e($vehicle->brand . ' ' . $vehicle->model); ?></a>
                                            <?php } else { ?>
                                            <span class="text-muted">-</span>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo aae_order_stage_badge($order->stage); ?></td>
                                        <td><?php echo aae_format_eur($order->amount_client); ?></td>
                                        <td><?php echo aae_format_eur($order->amount_net_received); ?></td>
                                        <td>
                                            <?php 
                                            $bal = (float) $order->amount_client - (float) $order->amount_net_received;
                                            if ($bal > 0.01) {
                                                echo '<span class="text-danger">' . aae_format_eur($bal) . '</span>';
                                            } else {
                                                echo '<span class="text-success"><i class="fa fa-check"></i></span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo _d($order->datecreated); ?></td>
                                        <td>
                                            <a href="<?php echo admin_url('aae/aae_order/view/' . $order->id); ?>" class="btn btn-default btn-sm">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
