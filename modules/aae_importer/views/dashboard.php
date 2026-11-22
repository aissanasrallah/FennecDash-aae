<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
<div class="row aae-stats-row">

    <div class="col-lg-4 col-sm-6">
        <div class="panel_s aae-stat" style="border-left:4px solid #6c757d;">
            <div class="panel-body">
                <div class="aae-stat-icon" style="background:#6c757d;"><i class="fa fa-car"></i></div>
                <div class="aae-stat-body">
                    <div class="aae-stat-value"><?php echo $vehicles_total; ?></div>
                    <div class="aae-stat-label"><?php echo _l('aae_dash_total_vehicles'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-sm-6">
        <div class="panel_s aae-stat" style="border-left:4px solid #1bc5bd;">
            <div class="panel-body">
                <div class="aae-stat-icon" style="background:#1bc5bd;"><i class="fa fa-check-circle-o"></i></div>
                <div class="aae-stat-body">
                    <div class="aae-stat-value"><?php echo $vehicles_dispo; ?></div>
                    <div class="aae-stat-label"><?php echo _l('aae_dash_available'); ?></div>
                    <div class="text-muted small">Vendus : <?php echo $vehicles_sold; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-sm-6">
        <div class="panel_s aae-stat" style="border-left:4px solid #009efb;">
            <div class="panel-body">
                <div class="aae-stat-icon" style="background:#009efb;"><i class="fa fa-ship"></i></div>
                <div class="aae-stat-body">
                    <div class="aae-stat-value"><?php echo $ops_active; ?></div>
                    <div class="aae-stat-label"><?php echo _l('aae_dash_ongoing_ops'); ?></div>
                    <div class="text-muted small">Total : <?php echo $ops_total; ?> · Livrées : <?php echo $ops_livre; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-sm-6">
        <div class="panel_s aae-stat" style="border-left:4px solid #f0ad4e;">
            <div class="panel-body">
                <div class="aae-stat-icon" style="background:#f0ad4e;"><i class="fa fa-money"></i></div>
                <div class="aae-stat-body">
                    <div class="aae-stat-value aae-money"><?php echo number_format($revenue, 0, ',', ' '); ?> DA</div>
                    <div class="aae-stat-label"><?php echo _l('aae_dash_revenue'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-sm-6">
        <div class="panel_s aae-stat" style="border-left:4px solid #28a745;">
            <div class="panel-body">
                <div class="aae-stat-icon" style="background:#28a745;"><i class="fa fa-line-chart"></i></div>
                <div class="aae-stat-body">
                    <div class="aae-stat-value aae-money" style="color:<?php echo $margin >= 0 ? '#28a745' : '#dc3545'; ?>"><?php echo number_format($margin, 0, ',', ' '); ?> DA</div>
                    <div class="aae-stat-label"><?php echo _l('aae_dash_margin'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-sm-6">
        <div class="panel_s aae-stat aae-stat-actions">
            <div class="panel-body">
                <a href="<?php echo admin_url('aae_importer/operations?view=kanban'); ?>" class="btn btn-primary btn-block"><i class="fa fa-ship"></i> <?php echo _l('aae_menu_operations'); ?></a>
                <a href="<?php echo admin_url('aae_importer/catalogue'); ?>" class="btn btn-default btn-block" style="margin-top:8px;"><i class="fa fa-car"></i> <?php echo _l('aae_menu_catalogue'); ?></a>
                <a href="<?php echo admin_url('aae_importer/sync_prices'); ?>" class="btn btn-success btn-block" style="margin-top:8px;background-color:#16a34a;border-color:#16a34a;color:#fff;"><i class="fa fa-refresh"></i> Synchroniser les prix vers le site</a>
            </div>
        </div>
    </div>

</div>

<?php if ($vehicles_total == 0) { ?>
<div class="alert alert-warning aae-notice">
    <i class="fa fa-exclamation-triangle"></i> <?php echo _l('aae_no_vehicles'); ?>
    <a href="<?php echo admin_url('aae_importer/import_csv'); ?>" style="text-decoration:underline;margin-left:6px;"><?php echo _l('aae_import_csv'); ?></a>
</div>
<?php } ?>

<div class="row">
    <div class="col-md-4">
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?php echo _l('aae_dash_operations_by_status'); ?></h4>
                <hr class="hr-panel-heading">
                <?php if ($ops_total > 0) { ?>
                    <div style="max-width:220px;margin:0 auto;">
                        <canvas id="aaeOpsDoughnut" height="200"
                            data-labels="<?php echo e(json_encode(array_values(array_map(function ($s) { return $s['name']; }, $statuses)))); ?>"
                            data-colors="<?php echo e(json_encode(array_values(array_map(function ($s) { return $s['color']; }, $statuses)))); ?>"
                            data-values="<?php echo e(json_encode(array_values(array_map(function ($k) use ($ops_status) { return isset($ops_status[$k]) ? (int)$ops_status[$k] : 0; }, array_keys($statuses))))); ?>"></canvas>
                    </div>
                <?php } else { ?>
                    <p class="text-muted text-center" style="padding:30px 0;"><?php echo _l('aae_no_operations'); ?></p>
                <?php } ?>
                <div class="aae-legend">
                    <?php foreach ($statuses as $key => $s) { ?>
                        <div class="aae-legend-row">
                            <span class="aae-badge" style="background:<?php echo $s['color']; ?>;color:#fff;"><i class="fa fa-circle fa-xs"></i></span>
                            <span style="margin-left:8px;"><?php echo $s['name']; ?></span>
                            <span class="pull-right text-muted"><?php echo isset($ops_status[$key]) ? $ops_status[$key] : 0; ?></span>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="panel_s">
            <div class="panel-heading _buttons">
                <h4 class="display-block no-margin"><?php echo _l('aae_dash_latest_operations'); ?></h4>
                <div class="pull-right">
                    <a href="<?php echo admin_url('aae_importer/operations'); ?>" class="btn btn-default btn-xs"><i class="fa fa-list"></i> <?php echo _l('aae_view_list'); ?></a>
                </div>
            </div>
            <div class="panel-body">
                <?php if (count($recent_ops) > 0) { ?>
                    <table class="table aae-table">
                        <thead>
                            <tr>
                                <th><?php echo _l('aae_ref'); ?></th>
                                <th><?php echo _l('aae_vehicle'); ?></th>
                                <th><?php echo _l('aae_statut'); ?></th>
                                <th><?php echo _l('aae_total_cost'); ?></th>
                                <th><?php echo _l('aae_margin'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_ops as $op) {
                                $sname  = isset($statuses[$op['status']]) ? $statuses[$op['status']]['name'] : $op['status'];
                                $scolor = isset($statuses[$op['status']]) ? $statuses[$op['status']]['color'] : '#6c757d';
                            ?>
                            <tr>
                                <td><a href="<?php echo admin_url('aae_importer/operation_view/' . $op['id']); ?>"><?php echo e($op['ref']); ?></a></td>
                                <td><?php echo e(trim($op['v_marque'] . ' ' . $op['v_modele'])); ?></td>
                                <td><span class="aae-badge" style="background:<?php echo $scolor; ?>;color:#fff;"><?php echo e($sname); ?></span></td>
                                <td><?php echo $op['total_cost'] !== null && $op['total_cost'] !== '' ? number_format((float)$op['total_cost'], 0, ',', ' ') . ' DA' : _l('aae_none'); ?></td>
                                <td>
                                    <?php if ($op['margin'] !== null && $op['margin'] !== '') { ?>
                                        <span style="color:<?php echo (float)$op['margin'] >= 0 ? '#28a745' : '#dc3545'; ?>;">
                                            <?php echo number_format((float)$op['margin'], 0, ',', ' '); ?> DA
                                        </span>
                                    <?php } else { echo _l('aae_none'); } ?>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="text-muted text-center" style="padding:30px 0;"><?php echo _l('aae_no_operations'); ?></p>
                <?php } ?>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?php echo _l('aae_dash_top_brands'); ?></h4>
                <hr class="hr-panel-heading">
                <?php if (count($top_marques) > 0) {
                    $max = max(array_map(function ($m) { return (int)$m['c']; }, $top_marques));
                ?>
                    <?php foreach ($top_marques as $m) { ?>
                        <div style="margin-bottom:10px;">
                            <div class="clearfix">
                                <span class="pull-left bold"><?php echo e($m['marque']); ?></span>
                                <span class="pull-right text-muted"><?php echo (int)$m['c']; ?></span>
                            </div>
                            <div class="progress no-margin" style="height:8px;">
                                <div class="progress-bar progress-bar-info" style="width:<?php echo $max > 0 ? round((int)$m['c'] / $max * 100) : 0; ?>%;"></div>
                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <p class="text-muted text-center" style="padding:10px 0;"><?php echo _l('aae_no_vehicles'); ?></p>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

    </div>
</div>
<?php init_tail(); ?>
</body>

</html>
