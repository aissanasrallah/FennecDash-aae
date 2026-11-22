<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin font-bold"><?php echo lg_html_entity_decode($title); ?></h4>
                        <hr class="hr-panel-heading" />

                        <form method="get" action="<?php echo admin_url('logistic/operational_reports'); ?>" class="mbot20">
                            <div class="row">
                                <div class="col-md-2">
                                    <?php echo render_date_input('from_date', 'lg_from_date', $filters['from_date'] ?? ''); ?>
                                </div>
                                <div class="col-md-2">
                                    <?php echo render_date_input('to_date', 'lg_to_date', $filters['to_date'] ?? ''); ?>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="transport_type"><?php echo _l('lg_transport_type'); ?></label>
                                        <select name="transport_type" id="transport_type" class="selectpicker" data-width="100%">
                                            <option value=""><?php echo _l('lg_all_transport_types'); ?></option>
                                            <option value="internal" <?php if (($filters['transport_type'] ?? '') === 'internal') echo 'selected'; ?>><?php echo _l('lg_internal_fleet'); ?></option>
                                            <option value="external" <?php if (($filters['transport_type'] ?? '') === 'external') echo 'selected'; ?>><?php echo _l('lg_external_carrier'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <?php echo render_select('office_group_id', $office_groups ?? [], ['id', 'office_name'], 'lg_assigned_depot', $filters['office_group_id'] ?? ''); ?>
                                </div>
                                <div class="col-md-2" style="padding-top:25px;">
                                    <button type="submit" class="btn btn-primary"><?php echo _l('lg_filter'); ?></button>
                                    <a href="<?php echo admin_url('logistic/operational_reports'); ?>" class="btn btn-default"><?php echo _l('lg_reset'); ?></a>
                                </div>
                            </div>
                        </form>

                        <?php $totals = $report['totals'] ?? []; ?>
                        <div class="row mbot20">
                            <div class="col-md-2 col-xs-6"><div class="panel_s"><div class="panel-body text-center"><span class="text-muted"><?php echo _l('lg_total_deliveries_kpi'); ?></span><h4 class="bold no-margin"><?php echo (int) ($totals['shipments_total'] ?? 0); ?></h4></div></div></div>
                            <div class="col-md-2 col-xs-6"><div class="panel_s"><div class="panel-body text-center"><span class="text-success"><?php echo _l('lg_delivered_kpi'); ?></span><h4 class="bold no-margin text-success"><?php echo (int) ($totals['delivered_total'] ?? 0); ?></h4></div></div></div>
                            <div class="col-md-2 col-xs-6"><div class="panel_s"><div class="panel-body text-center"><span class="text-danger"><?php echo _l('lg_failed_returned_kpi'); ?></span><h4 class="bold no-margin text-danger"><?php echo (int) ($totals['failed_total'] ?? 0); ?></h4></div></div></div>
                            <div class="col-md-2 col-xs-6"><div class="panel_s"><div class="panel-body text-center"><span class="text-warning"><?php echo _l('lg_external_fees_kpi'); ?></span><h4 class="bold no-margin"><?php echo app_format_money($totals['external_agreed_total'] ?? 0, $base_currency); ?></h4></div></div></div>
                            <div class="col-md-2 col-xs-6"><div class="panel_s"><div class="panel-body text-center"><span class="text-info"><?php echo _l('lg_transport_costs_kpi'); ?></span><h4 class="bold no-margin"><?php echo app_format_money($totals['costs_amount_total'] ?? 0, $base_currency); ?></h4></div></div></div>
                            <div class="col-md-2 col-xs-6"><div class="panel_s"><div class="panel-body text-center"><span class="text-muted"><?php echo _l('lg_paid_costs_kpi'); ?></span><h4 class="bold no-margin"><?php echo app_format_money($totals['costs_paid_total'] ?? 0, $base_currency); ?></h4></div></div></div>
                        </div>

                        <h5><strong>1. <?php echo _l('lg_internal_fleet') . ' vs ' . _l('lg_external_carrier'); ?></strong></h5>
                        <table class="table table-bordered">
                            <thead><tr><th><?php echo _l('lg_transport_type'); ?></th><th>Total</th><th><?php echo _l('lg_delivered'); ?></th><th><?php echo _l('lg_failed_delivery') . ' / ' . _l('lg_returned') . ' / ' . _l('lg_cancelled'); ?></th></tr></thead>
                            <tbody>
                                <?php foreach ($report['by_transport_type'] as $r) { ?>
                                <tr>
                                    <td><?php echo $r['transport_type'] === 'external' ? _l('lg_external_carrier') : _l('lg_internal_fleet'); ?></td>
                                    <td><?php echo (int)$r['total']; ?></td>
                                    <td><?php echo (int)$r['delivered_count']; ?></td>
                                    <td><?php echo (int)$r['failed_count']; ?></td>
                                </tr>
                                <?php } ?>
                                <?php if (empty($report['by_transport_type'])) { ?><tr><td colspan="4" class="text-muted text-center"><?php echo _l('no_entries_found'); ?></td></tr><?php } ?>
                            </tbody>
                            <tfoot><tr class="bold info"><td><?php echo _l('lg_totals'); ?></td><td><?php echo (int)($totals['shipments_total'] ?? 0); ?></td><td><?php echo (int)($totals['delivered_total'] ?? 0); ?></td><td><?php echo (int)($totals['failed_total'] ?? 0); ?></td></tr></tfoot>
                        </table>

                        <h5 class="mtop20"><strong>2. <?php echo _l('lg_driver'); ?> (Workload &amp; Performance)</strong></h5>
                        <table class="table table-bordered">
                            <thead><tr><th><?php echo _l('lg_driver'); ?></th><th>Total</th><th><?php echo _l('lg_delivered'); ?></th><th>Échecs / Retours</th><th>Délai moyen (h)</th></tr></thead>
                            <tbody>
                                <?php $drvTot=0;$drvDel=0;$drvFail=0; foreach ($report['by_driver'] as $r) { $drvTot+=(int)$r['total_deliveries'];$drvDel+=(int)$r['delivered_count'];$drvFail+=(int)$r['failed_count']; ?>
                                <tr>
                                    <td><?php echo e($r['driver_name']); ?></td>
                                    <td><?php echo (int)$r['total_deliveries']; ?></td>
                                    <td><?php echo (int)$r['delivered_count']; ?></td>
                                    <td><?php echo (int)$r['failed_count']; ?></td>
                                    <td><?php echo $r['avg_hours'] !== null ? e($r['avg_hours']) : '-'; ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot><tr class="bold info"><td><?php echo _l('lg_totals'); ?></td><td><?php echo $drvTot; ?></td><td><?php echo $drvDel; ?></td><td><?php echo $drvFail; ?></td><td>-</td></tr></tfoot>
                        </table>

                        <div class="row mtop20">
                            <div class="col-md-6">
                                <h5><strong>3. <?php echo _l('lg_vehicles'); ?></strong></h5>
                                <table class="table table-bordered">
                                    <thead><tr><th><?php echo _l('lg_vehicle'); ?></th><th><?php echo _l('lg_vehicle_status'); ?></th><th>Total</th><th><?php echo _l('lg_delivered'); ?></th></tr></thead>
                                    <tbody>
                                        <?php $vehTot=0;$vehDel=0; foreach ($report['by_vehicle'] as $r) { $vehTot+=(int)$r['total_deliveries'];$vehDel+=(int)$r['delivered_count']; ?>
                                        <tr><td><?php echo e($r['vehicle_code'] . ($r['registration_plate'] ? ' (' . $r['registration_plate'] . ')' : '')); ?></td><td><?php echo _l('lg_vehicle_' . $r['status']); ?></td><td><?php echo (int)$r['total_deliveries']; ?></td><td><?php echo (int)$r['delivered_count']; ?></td></tr>
                                        <?php } ?>
                                        <?php if (empty($report['by_vehicle'])) { ?><tr><td colspan="4" class="text-muted text-center"><?php echo _l('no_entries_found'); ?></td></tr><?php } ?>
                                    </tbody>
                                    <tfoot><tr class="bold info"><td colspan="2"><?php echo _l('lg_totals'); ?></td><td><?php echo $vehTot; ?></td><td><?php echo $vehDel; ?></td></tr></tfoot>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5><strong>4. <?php echo _l('lg_courrier_company'); ?></strong></h5>
                                <table class="table table-bordered">
                                    <thead><tr><th><?php echo _l('lg_courrier_company'); ?></th><th>Total</th><th><?php echo _l('lg_delivered'); ?></th><th><?php echo _l('lg_external_agreed_price'); ?></th></tr></thead>
                                    <tbody>
                                        <?php $carTot=0;$carDel=0;$carAmt=0.0; foreach ($report['by_carrier'] as $r) { $carTot+=(int)$r['total_deliveries'];$carDel+=(int)$r['delivered_count'];$carAmt+=(float)$r['total_agreed_price']; ?>
                                        <tr><td><?php echo e($r['shipping_company_name']); ?></td><td><?php echo (int)$r['total_deliveries']; ?></td><td><?php echo (int)$r['delivered_count']; ?></td><td><?php echo app_format_money($r['total_agreed_price'], $base_currency); ?></td></tr>
                                        <?php } ?>
                                        <?php if (empty($report['by_carrier'])) { ?><tr><td colspan="4" class="text-muted text-center"><?php echo _l('no_entries_found'); ?></td></tr><?php } ?>
                                    </tbody>
                                    <tfoot><tr class="bold info"><td><?php echo _l('lg_totals'); ?></td><td><?php echo $carTot; ?></td><td><?php echo $carDel; ?></td><td><?php echo app_format_money($carAmt, $base_currency); ?></td></tr></tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="row mtop20">
                            <div class="col-md-6">
                                <h5><strong>5. <?php echo _l('lg_assigned_depot'); ?> &amp; Zone</strong></h5>
                                <table class="table table-bordered">
                                    <thead><tr><th><?php echo _l('lg_assigned_depot'); ?></th><th><?php echo _l('client_state'); ?></th><th>Total</th><th><?php echo _l('lg_delivered'); ?></th><th>Échecs</th></tr></thead>
                                    <tbody>
                                        <?php $dzTot=0;$dzDel=0;$dzFail=0; foreach ($report['by_depot_zone'] as $r) { $dzTot+=(int)$r['total_deliveries'];$dzDel+=(int)$r['delivered_count'];$dzFail+=(int)$r['failed_count']; ?>
                                        <tr><td><?php echo e($r['depot_name']); ?></td><td><?php echo e($r['dest_state']); ?></td><td><?php echo (int)$r['total_deliveries']; ?></td><td><?php echo (int)$r['delivered_count']; ?></td><td><?php echo (int)$r['failed_count']; ?></td></tr>
                                        <?php } ?>
                                        <?php if (empty($report['by_depot_zone'])) { ?><tr><td colspan="5" class="text-muted text-center"><?php echo _l('no_entries_found'); ?></td></tr><?php } ?>
                                    </tbody>
                                    <tfoot><tr class="bold info"><td colspan="2"><?php echo _l('lg_totals'); ?></td><td><?php echo $dzTot; ?></td><td><?php echo $dzDel; ?></td><td><?php echo $dzFail; ?></td></tr></tfoot>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5><strong>6. <?php echo _l('lg_transport_costs'); ?></strong></h5>
                                <table class="table table-bordered">
                                    <thead><tr><th><?php echo _l('lg_transport_type'); ?></th><th><?php echo _l('lg_cost_category'); ?></th><th>Nb</th><th>Total</th><th>Payé</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($report['costs_by_method'] as $r) { ?>
                                        <tr><td><?php echo $r['transport_type'] === 'external' ? _l('lg_external_carrier') : _l('lg_internal_fleet'); ?></td><td><?php echo _l('lg_cost_' . $r['cost_category']); ?></td><td><?php echo (int)$r['entries']; ?></td><td><?php echo app_format_money($r['total_amount'], $base_currency); ?></td><td><?php echo app_format_money($r['paid_amount'], $base_currency); ?></td></tr>
                                        <?php } ?>
                                        <?php if (empty($report['costs_by_method'])) { ?><tr><td colspan="5" class="text-muted text-center"><?php echo _l('no_entries_found'); ?></td></tr><?php } ?>
                                    </tbody>
                                    <tfoot><tr class="bold info"><td colspan="2"><?php echo _l('lg_totals'); ?></td><td><?php echo (int)($totals['costs_entries_total'] ?? 0); ?></td><td><?php echo app_format_money($totals['costs_amount_total'] ?? 0, $base_currency); ?></td><td><?php echo app_format_money($totals['costs_paid_total'] ?? 0, $base_currency); ?></td></tr></tfoot>
                                </table>
                            </div>
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
