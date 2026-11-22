<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <?php if (has_permission('lg_transport_costs', '', 'create')) { ?>
                                <a href="<?php echo admin_url('logistic/transport_cost'); ?>" class="btn btn-info pull-left">
                                    <i class="fa fa-plus"></i> <?php echo _l('lg_add_transport_cost'); ?>
                                </a>
                            <?php } ?>
                            <h4 class="pull-right no-margin font-bold"><?php echo lg_html_entity_decode($title); ?></h4>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-heading" />
                        <table class="table dt-table">
                            <thead>
                                <th><?php echo _l('lg_cost_date'); ?></th>
                                <th><?php echo _l('lg_transport_type'); ?></th>
                                <th><?php echo _l('lg_cost_category'); ?></th>
                                <th><?php echo _l('lg_shipping'); ?></th>
                                <th><?php echo _l('lg_driver') . ' / ' . _l('lg_courrier_company'); ?></th>
                                <th><?php echo _l('lg_vehicle'); ?></th>
                                <th><?php echo _l('lg_reference_no'); ?></th>
                                <th><?php echo _l('lg_cost_amount'); ?></th>
                                <th><?php echo _l('payment_status'); ?></th>
                                <th><?php echo _l('options'); ?></th>
                            </thead>
                            <tbody>
                                <?php foreach ($costs as $c) { ?>
                                <tr>
                                    <td><?php echo _d($c['cost_date']); ?></td>
                                    <td><?php echo $c['transport_type'] === 'external' ? _l('lg_external_carrier') : _l('lg_internal_fleet'); ?></td>
                                    <td><?php echo _l('lg_cost_' . $c['cost_category']); ?></td>
                                    <td><?php echo e($c['shipping_ref'] ?: '-'); ?></td>
                                    <td><?php echo $c['transport_type'] === 'external' ? e($c['carrier_name'] ?: '-') : e(trim((string)($c['driver_name'] ?? '')) ?: '-'); ?></td>
                                    <td><?php echo e($c['vehicle_code'] ?: '-'); ?></td>
                                    <td><?php echo e($c['reference_no'] ?: '-'); ?></td>
                                    <td><?php echo app_format_money($c['amount'], $base_currency); ?></td>
                                    <td><?php echo e($c['payment_status']); ?></td>
                                    <td>
                                        <?php if (has_permission('lg_transport_costs', '', 'edit')) { ?>
                                            <a href="<?php echo admin_url('logistic/transport_cost/' . $c['id']); ?>" class="btn btn-default btn-icon"><i class="fa fa-pencil-square"></i></a>
                                        <?php } ?>
                                        <?php if (has_permission('lg_transport_costs', '', 'delete')) { ?>
                                            <a href="<?php echo admin_url('logistic/delete_transport_cost/' . $c['id']); ?>" class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
                                        <?php } ?>
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
<?php init_tail(); ?>
</body>
</html>
