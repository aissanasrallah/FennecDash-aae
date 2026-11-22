<?php defined('BASEPATH') or exit('No direct script access allowed');

$order       = array_keys($statuses);
$opsTotal    = array_sum($counts);
$redirectUrl = admin_url('aae_importer/operations?view=' . urlencode($view) . '&status=' . urlencode($selected) . '&search=' . urlencode($search));

init_head(); ?>
<div id="wrapper">
<div class="content">

<?php if ($view === 'kanban') { ?>

<div class="row" style="margin-bottom:18px;">
    <div class="col-md-12">
        <h4 class="no-margin display-block" style="font-size:18px;"><i class="fa fa-ship"></i> <?php echo _l('aae_menu_operations'); ?></h4>
        <div style="margin-top:10px;" class="btn-group">
            <?php if (staff_can('create', 'aae_importer')) { ?>
                <a href="<?php echo admin_url('aae_importer/operation_create'); ?>" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo _l('aae_new_operation'); ?></a>
            <?php } ?>
            <a href="<?php echo admin_url('aae_importer/operations?view=list'); ?>" class="btn btn-default"><i class="fa fa-list"></i> <?php echo _l('aae_view_list'); ?></a>
            <button type="button" class="btn btn-info" disabled><i class="fa fa-columns"></i> <?php echo _l('aae_view_kanban'); ?></button>
        </div>
    </div>
</div>

<div class="aae-kanban-wrap">
<div class="aae-kanban">
    <?php foreach ($statuses as $key => $s) { ?>
        <div class="aae-kanban-col">
            <div class="aae-kanban-colhead" style="border-top:3px solid <?php echo $s['color']; ?>;">
                <span class="bold"><?php echo e($s['name']); ?></span>
                <span class="pull-right aae-count"><?php echo isset($counts[$key]) ? (int)$counts[$key] : 0; ?></span>
            </div>
            <?php
            $ops = array_values(array_filter($operations, function ($o) use ($key) {
                return $o['status'] === $key;
            }));
            usort($ops, function ($a, $b) {
                return $a['id'] > $b['id'] ? -1 : 1;
            });
            foreach ($ops as $op) { ?>
                <a class="aae-kanban-card" href="<?php echo admin_url('aae_importer/operation_view/' . $op['id']); ?>">
                    <div class="aae-kanban-ref bold"><?php echo e($op['ref']); ?></div>
                    <div class="aae-kanban-veh"><?php echo e(trim($op['v_marque'] . ' ' . $op['v_modele'])); ?></div>
                    <?php if ($op['v_annee']) { ?><span class="aae-kanban-meta"><?php echo (int)$op['v_annee']; ?></span><?php } ?>
                    <span class="aae-kanban-meta"><?php echo _l('aae_op_qty'); ?> x<?php echo (int)$op['qty']; ?></span>
                    <?php if ($op['margin'] !== null && $op['margin'] !== '') { ?>
                        <div class="aae-kanban-margin" style="color:<?php echo (float)$op['margin'] >= 0 ? '#28a745' : '#dc3545'; ?>;margin-top:6px;">
                            <i class="fa fa-line-chart"></i> <?php echo number_format((float)$op['margin'], 0, ',', ' '); ?> DA
                        </div>
                    <?php } ?>
                    <?php if (!empty($op['eta_date'])) { ?>
                        <div class="aae-kanban-meta"><i class="fa fa-calendar"></i> <?php echo _d($op['eta_date']); ?></div>
                    <?php } ?>
                </a>
            <?php } ?>
        </div>
    <?php } ?>
</div>
</div>

<?php } else { ?>

<div class="row" style="margin-bottom:18px;">
    <div class="col-md-12">
        <h4 class="no-margin display-block" style="font-size:18px;"><i class="fa fa-ship"></i> <?php echo _l('aae_menu_operations'); ?></h4>
        <div style="margin-top:10px;" class="btn-group">
            <?php if (staff_can('create', 'aae_importer')) { ?>
                <a href="<?php echo admin_url('aae_importer/operation_create'); ?>" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo _l('aae_new_operation'); ?></a>
            <?php } ?>
            <button type="button" class="btn btn-info" disabled><i class="fa fa-list"></i> <?php echo _l('aae_view_list'); ?></button>
            <a href="<?php echo admin_url('aae_importer/operations?view=kanban'); ?>" class="btn btn-default"><i class="fa fa-columns"></i> <?php echo _l('aae_view_kanban'); ?></a>
        </div>
    </div>
</div>

<!-- Filtres statut -->
<div class="panel_s" style="margin-bottom:14px;">
    <div class="panel-body" style="padding:10px 15px;">
        <div class="row">
            <div class="col-md-8">
                <div class="aae-status-pills">
                    <a href="<?php echo admin_url('aae_importer/operations?view=' . $view); ?>"
                       class="aae-status-pill<?php echo $selected === '' ? ' active' : ''; ?>"><?php echo _l('aae_filter_all'); ?> (<?php echo (int)$opsTotal; ?>)</a>
                    <?php foreach ($statuses as $key => $s) { ?>
                        <a href="<?php echo admin_url('aae_importer/operations?view=' . $view . '&status=' . $key); ?>"
                           class="aae-status-pill<?php echo $selected === $key ? ' active' : ''; ?>"
                           style="<?php echo $selected === $key ? 'background:' . $s['color'] . ';color:#fff;border-color:' . $s['color'] . ';' : ''; ?>">
                            <?php echo e($s['name']); ?> (<?php echo isset($counts[$key]) ? (int)$counts[$key] : 0; ?>)
                        </a>
                    <?php } ?>
                </div>
            </div>
            <div class="col-md-4">
                <?php echo form_open(admin_url('aae_importer/operations'), ['method' => 'get']); ?>
                    <input type="hidden" name="view" value="<?php echo e($view); ?>">
                    <?php if ($selected !== '') { ?><input type="hidden" name="status" value="<?php echo e($selected); ?>"><?php } ?>
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="<?php echo _l('aae_search_placeholder'); ?>">
                        <span class="input-group-btn">
                            <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                        </span>
                    </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

<div class="panel_s">
    <div class="panel-body">
        <?php if (count($operations) > 0) { ?>
            <div class="table-responsive">
            <table class="table table-striped aae-table">
                <thead>
                    <tr>
                        <th><?php echo _l('aae_ref'); ?></th>
                        <th><?php echo _l('aae_vehicle'); ?></th>
                        <th><?php echo _l('aae_statut'); ?></th>
                        <th><?php echo _l('aae_op_qty'); ?></th>
                        <th><?php echo _l('aae_op_eta'); ?></th>
                        <th><?php echo _l('aae_total_cost'); ?></th>
                        <th><?php echo _l('aae_sale_price'); ?></th>
                        <th><?php echo _l('aae_margin'); ?></th>
                        <th><?php echo _l('aae_actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($operations as $op) {
                        $sname  = isset($statuses[$op['status']]) ? $statuses[$op['status']]['name'] : $op['status'];
                        $scolor = isset($statuses[$op['status']]) ? $statuses[$op['status']]['color'] : '#6c757d';

                        $idx      = array_search($op['status'], $order);
                        $nextKey  = false;
                        if ($idx !== false && isset($order[$idx + 1]) && $order[$idx + 1] !== 'annule') {
                            $nextKey = $order[$idx + 1];
                        }
                    ?>
                    <tr>
                        <td><a href="<?php echo admin_url('aae_importer/operation_view/' . $op['id']); ?>"><b><?php echo e($op['ref']); ?></b></a></td>
                        <td><?php echo e(trim($op['v_marque'] . ' ' . $op['v_modele'])); ?><?php echo $op['v_annee'] ? ' <span class="text-muted">(' . (int)$op['v_annee'] . ')</span>' : ''; ?></td>
                        <td><span class="aae-badge" style="background:<?php echo $scolor; ?>;color:#fff;"><?php echo e($sname); ?></span></td>
                        <td>x<?php echo (int)$op['qty']; ?></td>
                        <td><?php echo !empty($op['eta_date']) ? _d($op['eta_date']) : _l('aae_none'); ?></td>
                        <td><?php echo $op['total_cost'] !== null && $op['total_cost'] !== '' ? number_format((float)$op['total_cost'], 0, ',', ' ') . ' DA' : _l('aae_none'); ?></td>
                        <td><?php echo !empty($op['sale_price']) ? number_format((float)$op['sale_price'], 0, ',', ' ') . ' DA' : _l('aae_none'); ?></td>
                        <td>
                            <?php if ($op['margin'] !== null && $op['margin'] !== '') { ?>
                                <span class="bold" style="color:<?php echo (float)$op['margin'] >= 0 ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo number_format((float)$op['margin'], 0, ',', ' '); ?> DA
                                </span>
                            <?php } else { echo _l('aae_none'); } ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="<?php echo admin_url('aae_importer/operation_view/' . $op['id']); ?>" class="btn btn-default btn-xs" title="<?php echo _l('aae_view_full'); ?>"><i class="fa fa-eye"></i></a>
                                <?php if ($nextKey && staff_can('edit', 'aae_importer')) {
                                    $nextName = $statuses[$nextKey]['name'];
                                ?>
                                <?php echo form_open(admin_url('aae_importer/operation_status'), ['class' => 'aae-inline-form']); ?>
                                    <input type="hidden" name="id" value="<?php echo (int)$op['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo e($nextKey); ?>">
                                    <input type="hidden" name="redirect" value="<?php echo e($redirectUrl); ?>">
                                    <button type="submit" class="btn btn-default btn-xs" title="<?php echo _l('aae_advance_status') . ' : ' . e($nextName); ?>"><i class="fa fa-arrow-right"></i></button>
                                <?php echo form_close(); ?>
                                <?php } ?>
                                <?php if (staff_can('edit', 'aae_importer')) { ?>
                                <a href="<?php echo admin_url('aae_importer/operation_edit/' . $op['id']); ?>" class="btn btn-default btn-xs"><i class="fa fa-pencil"></i></a>
                                <?php } ?>
                                <?php if (staff_can('delete', 'aae_importer')) { ?>
                                <?php echo form_open(admin_url('aae_importer/operation_delete'), ['class' => 'aae-inline-form', 'onsubmit' => 'return confirm(\'' . _l('aae_confirm_delete_operation') . '\');']); ?>
                                    <input type="hidden" name="id" value="<?php echo (int)$op['id']; ?>">
                                    <button type="submit" class="btn btn-default btn-xs" style="color:#dc3545;"><i class="fa fa-trash"></i></button>
                                <?php echo form_close(); ?>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            </div>
        <?php } else { ?>
            <p class="text-muted text-center" style="padding:40px 0;">
                <i class="fa fa-ship fa-3x" style="opacity:.3;display:block;margin-bottom:14px;"></i>
                <?php echo _l('aae_no_operations'); ?>
            </p>
        <?php } ?>
    </div>
</div>

<?php } ?>

</div>
</div>
<?php init_tail(); ?>
</body>

</html>
