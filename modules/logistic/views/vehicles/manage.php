<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <?php if (has_permission('lg_vehicles', '', 'create')) { ?>
                                <a href="<?php echo admin_url('logistic/vehicle'); ?>" class="btn btn-info pull-left">
                                    <i class="fa fa-plus"></i> <?php echo _l('lg_add_vehicle'); ?>
                                </a>
                            <?php } ?>
                            <h4 class="pull-right no-margin font-bold"><?php echo lg_html_entity_decode($title); ?></h4>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-heading" />
                        <table class="table dt-table">
                            <thead>
                                <th><?php echo _l('lg_vehicle_code'); ?></th>
                                <th><?php echo _l('lg_vehicle_license_plate'); ?></th>
                                <th><?php echo _l('lg_vehicle_type'); ?></th>
                                <th><?php echo _l('lg_brand_model'); ?></th>
                                <th><?php echo _l('lg_capacity_kg'); ?></th>
                                <th><?php echo _l('lg_fuel_type'); ?></th>
                                <th><?php echo _l('lg_assigned_depot'); ?></th>
                                <th><?php echo _l('lg_assigned_driver'); ?></th>
                                <th><?php echo _l('lg_vehicle_status'); ?></th>
                                <th><?php echo _l('lg_insurance_expiry'); ?></th>
                                <th><?php echo _l('options'); ?></th>
                            </thead>
                            <tbody>
                                <?php foreach ($vehicles as $v) {
                                    $stBadge = 'label-default';
                                    if ($v['status'] === 'available') { $stBadge = 'label-success'; }
                                    elseif ($v['status'] === 'assigned') { $stBadge = 'label-info'; }
                                    elseif ($v['status'] === 'maintenance') { $stBadge = 'label-warning'; }
                                ?>
                                <tr>
                                    <td><?php echo e($v['vehicle_code']); ?></td>
                                    <td><?php echo e($v['registration_plate'] ?: '-'); ?></td>
                                    <td><?php echo e($v['vehicle_type']); ?></td>
                                    <td><?php echo e($v['brand_model'] ?: '-'); ?></td>
                                    <td><?php echo is_numeric($v['capacity_kg']) ? e($v['capacity_kg']) : '-'; ?></td>
                                    <td><?php echo e($v['fuel_type'] ?: '-'); ?></td>
                                    <td><?php echo e($v['office_name'] ?: '-'); ?></td>
                                    <td><?php echo e(trim((string)($v['driver_name'] ?? '')) ?: '-'); ?></td>
                                    <td><span class="label <?php echo $stBadge; ?>"><?php echo _l('lg_vehicle_' . $v['status']); ?></span></td>
                                    <td><?php echo !empty($v['insurance_expiry']) ? _d($v['insurance_expiry']) : '-'; ?></td>
                                    <td>
                                        <?php if (has_permission('lg_vehicles', '', 'edit')) { ?>
                                            <a href="<?php echo admin_url('logistic/vehicle/' . $v['id']); ?>" class="btn btn-default btn-icon"><i class="fa fa-pencil-square"></i></a>
                                        <?php } ?>
                                        <?php if (has_permission('lg_vehicles', '', 'delete')) { ?>
                                            <a href="<?php echo admin_url('logistic/delete_vehicle/' . $v['id']); ?>" class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>
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
