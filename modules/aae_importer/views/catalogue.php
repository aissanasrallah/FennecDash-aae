<?php defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();

init_head(); ?>
<div id="wrapper">
<div class="content">

<div class="row" style="margin-bottom:22px;">
    <div class="col-md-12">
        <h4 class="no-margin display-block" style="font-size:18px;"><i class="fa fa-car"></i> <?php echo _l('aae_menu_catalogue'); ?></h4>
        <div style="margin-top:10px;" class="btn-group">
            <?php if (staff_can('create', 'aae_importer')) { ?>
            <a href="#" class="btn btn-primary" id="aaeNewVehicleBtn"><i class="fa fa-plus"></i> <?php echo _l('aae_new_vehicle'); ?></a>
            <?php } ?>
            <a href="<?php echo admin_url('aae_importer/import_csv'); ?>" class="btn btn-default"><i class="fa fa-file-excel-o"></i> <?php echo _l('aae_import_csv'); ?></a>
            <a href="<?php echo admin_url('aae_importer/export_csv'); ?>" class="btn btn-default"><i class="fa fa-download"></i> <?php echo _l('aae_export_csv'); ?></a>
            <a href="<?php echo admin_url('aae_importer/sync_prices'); ?>" class="btn btn-success" style="background-color:#16a34a;border-color:#16a34a;color:#fff;"><i class="fa fa-refresh"></i> Synchroniser les prix vers le site</a>
        </div>
    </div>
</div>

<div class="panel_s">
    <div class="panel-body">

        <!-- Filtres -->
        <div class="row aae-filters">
            <div class="col-md-4">
                <input type="text" id="aaeSearch" class="form-control" placeholder="<?php echo _l('aae_search_placeholder'); ?>">
            </div>
            <div class="col-md-3">
                <select id="aaeStatusFilter" class="form-control">
                    <option value=""><?php echo _l('aae_statut'); ?> : <?php echo _l('aae_filter_all'); ?></option>
                    <?php foreach ($vehicle_statuses as $key => $s) { ?>
                        <option value="<?php echo e($key); ?>"><?php echo e($s['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-3">
                <select id="aaeMarqueFilter" class="form-control">
                    <option value=""><?php echo _l('aae_marque'); ?> : <?php echo _l('aae_filter_all'); ?></option>
                    <?php foreach ($marques as $m) { ?>
                        <option value="<?php echo e($m['marque']); ?>"><?php echo e($m['marque']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-default" id="aaeResetFilters" style="width:100%;"><i class="fa fa-refresh"></i> <?php echo _l('aae_filter_all'); ?></button>
            </div>
        </div>

        <?php if ($this->db->count_all(db_prefix() . 'aae_vehicles') == 0) { ?>
            <div class="alert alert-warning aae-notice"><i class="fa fa-exclamation-triangle"></i> <?php echo _l('aae_no_vehicles'); ?></div>
        <?php } ?>

        <!-- Tableau server-side -->
        <table class="table table-striped aae-table dt-table" id="aaeVehicleTable"
            data-url="<?php echo admin_url('aae_importer/catalogue'); ?>" style="width:100%;">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo _l('aae_marque'); ?></th>
                    <th><?php echo _l('aae_modele'); ?></th>
                    <th><?php echo _l('aae_moteur'); ?></th>
                    <th><?php echo _l('aae_annee'); ?></th>
                    <th><?php echo _l('aae_couleur'); ?></th>
                    <th><?php echo _l('aae_fob_usd'); ?></th>
                    <th><?php echo _l('aae_total_usd'); ?></th>
                    <th><?php echo _l('aae_total_ttc_eur'); ?></th>
                    <th><?php echo _l('aae_ttc_dzd'); ?></th>
                    <th><?php echo _l('aae_ttc_millions'); ?></th>
                    <th><?php echo _l('aae_customs_rate'); ?></th>
                    <th><?php echo _l('aae_lien'); ?></th>
                    <th><?php echo _l('aae_statut'); ?></th>
                    <th><?php echo _l('aae_actions'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<?php if (staff_can('create', 'aae_importer') || staff_can('edit', 'aae_importer')) { ?>
<!-- ================= MODAL VEHICULE ================= -->
<div class="modal fade" id="aaeVehicleModal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <?php echo form_open(admin_url('aae_importer/vehicle_save'), ['id' => 'aaeVehicleForm']); ?>
            <div class="modal-header">
                <h4 class="modal-title" id="aaeVehicleModalTitle"><?php echo _l('aae_new_vehicle'); ?></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">

                <input type="hidden" id="vh_id" name="id" value="">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label" for="vh_marque"><?php echo _l('aae_marque'); ?> *</label>
                            <input type="text" class="form-control" id="vh_marque" name="marque" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label" for="vh_modele"><?php echo _l('aae_modele'); ?> *</label>
                            <input type="text" class="form-control" id="vh_modele" name="modele" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="vh_moteur"><?php echo _l('aae_moteur'); ?></label>
                            <input type="text" class="form-control" id="vh_moteur" name="moteur">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="vh_annee"><?php echo _l('aae_annee'); ?></label>
                            <input type="number" min="1990" max="2100" class="form-control" id="vh_annee" name="annee">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="vh_couleur"><?php echo _l('aae_couleur'); ?></label>
                            <input type="text" class="form-control" id="vh_couleur" name="couleur">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="vh_lien"><?php echo _l('aae_lien'); ?></label>
                            <input type="url" class="form-control" id="vh_lien" name="lien" placeholder="https://...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="vh_status"><?php echo _l('aae_statut'); ?></label>
                            <select class="form-control" id="vh_status" name="status">
                                <?php foreach ($vehicle_statuses as $key => $s) { ?>
                                    <option value="<?php echo e($key); ?>"<?php echo $key === 'disponible' ? ' selected' : ''; ?>><?php echo e($s['name']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="vh_notes"><?php echo _l('aae_notes'); ?></label>
                            <textarea class="form-control" id="vh_notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="checkbox" style="margin-top:8px;">
                    <label>
                        <input type="checkbox" id="vh_manual" name="manual_input" value="1">
                        <?php echo _l('aae_manual_input'); ?>
                    </label>
                </div>

                <!-- Bloc calcul automatique -->
                <div class="aae-block" id="vhAutoBlock">
                    <h5 class="bold" style="border-bottom:1px solid #eee;padding-bottom:6px;"><i class="fa fa-calculator"></i> <?php echo _l('aae_auto_calculated'); ?></h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="vh_fob"><?php echo _l('aae_fob_usd'); ?></label>
                                <input type="number" step="0.01" class="form-control aae-calc" id="vh_fob" name="fob_usd">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="vh_doc_trans"><?php echo _l('aae_doc_translation'); ?></label>
                                <input type="number" step="0.01" class="form-control aae-calc" id="vh_doc_trans" name="doc_translation">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="vh_veh_trans"><?php echo _l('aae_vehicle_translation'); ?></label>
                                <input type="number" step="0.01" class="form-control aae-calc" id="vh_veh_trans" name="vehicle_translation">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="vh_shipping"><?php echo _l('aae_shipping_usd'); ?></label>
                                <input type="number" step="0.01" class="form-control aae-calc" id="vh_shipping" name="shipping_usd">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="vh_commission"><?php echo _l('aae_commission'); ?> (€)</label>
                                <input type="number" step="0.01" class="form-control aae-calc" id="vh_commission" name="commission_eur">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="vh_eur_rate"><?php echo _l('aae_eur_rate'); ?></label>
                                <input type="number" step="0.001" class="form-control aae-calc" id="vh_eur_rate" name="eur_rate">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="vh_eur_fee"><?php echo _l('aae_eur_fee_percent'); ?></label>
                                <input type="number" step="0.01" class="form-control aae-calc" id="vh_eur_fee" name="eur_fee_percent">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="vh_dzd_rate"><?php echo _l('aae_dzd_rate'); ?></label>
                                <input type="number" step="0.01" class="form-control aae-calc" id="vh_dzd_rate" name="dzd_rate" list="aaeDzdRates">
                                <datalist id="aaeDzdRates">
                                    <option value="282"></option>
                                    <option value="275"></option>
                                </datalist>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="vh_customs"><?php echo _l('aae_customs_rate'); ?> (%)</label>
                                <input type="number" step="1" class="form-control" id="vh_customs" name="customs_rate">
                            </div>
                        </div>
                    </div>
                    <div class="aae-preview" id="vhPreview">
                        <span class="aae-preview-line">
                            <span class="text-muted"><?php echo _l('aae_total_usd'); ?> :</span>
                            <b id="vhPrevTotalUsd">—</b>
                        </span>
                        <span class="aae-preview-line">
                            <span class="text-muted"><?php echo _l('aae_total_ttc_eur'); ?> :</span>
                            <b id="vhPrevTtcEur">—</b>
                        </span>
                        <span class="aae-preview-line">
                            <span class="text-muted"><?php echo _l('aae_ttc_dzd'); ?> :</span>
                            <b id="vhPrevTtcDzd">—</b>
                        </span>
                    </div>
                </div>

                <!-- Bloc saisie manuelle -->
                <div class="aae-block" id="vhManualBlock" style="display:none;">
                    <h5 class="bold" style="border-bottom:1px solid #eee;padding-bottom:6px;"><i class="fa fa-file-text-o"></i> <?php echo _l('aae_manual_input'); ?></h5>
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="vm_fob"><?php echo _l('aae_fob_usd'); ?></label>
                                <input type="number" step="0.01" class="form-control" id="vm_fob" name="fob_usd" disabled>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="vm_doc"><?php echo _l('aae_doc_translation'); ?></label>
                                <input type="number" step="0.01" class="form-control" id="vm_doc" name="doc_translation" disabled>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="vm_veh"><?php echo _l('aae_vehicle_translation'); ?></label>
                                <input type="number" step="0.01" class="form-control" id="vm_veh" name="vehicle_translation" disabled>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="vm_ship"><?php echo _l('aae_shipping_usd'); ?></label>
                                <input type="number" step="0.01" class="form-control" id="vm_ship" name="shipping_usd" disabled>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="vm_total_usd"><?php echo _l('aae_total_usd'); ?></label>
                                <input type="number" step="0.01" class="form-control" id="vm_total_usd" name="total_usd" disabled>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="vm_price_eur"><?php echo _l('aae_price_eur'); ?></label>
                                <input type="number" step="0.01" class="form-control" id="vm_price_eur" name="price_eur" disabled>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="vm_commission"><?php echo _l('aae_commission'); ?> (€)</label>
                                <input type="number" step="0.01" class="form-control" id="vm_commission" name="commission_eur" disabled>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="vm_ttc_eur"><?php echo _l('aae_total_ttc_eur'); ?></label>
                                <input type="number" step="0.01" class="form-control" id="vm_ttc_eur" name="total_ttc_eur" disabled>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="vm_ttc_dzd"><?php echo _l('aae_ttc_dzd'); ?></label>
                                <input type="number" step="0.01" class="form-control" id="vm_ttc_dzd" name="ttc_dzd" disabled>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="vm_dzd_rate"><?php echo _l('aae_dzd_rate'); ?></label>
                                <input type="number" step="0.01" class="form-control" id="vm_dzd_rate" name="dzd_rate" disabled>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="vm_eur_rate"><?php echo _l('aae_eur_rate'); ?></label>
                                <input type="number" step="0.001" class="form-control" id="vm_eur_rate" name="eur_rate" disabled>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="vm_eur_fee"><?php echo _l('aae_eur_fee_percent'); ?></label>
                                <input type="number" step="0.01" class="form-control" id="vm_eur_fee" name="eur_fee_percent" disabled>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="vm_customs"><?php echo _l('aae_customs_rate'); ?> (%)</label>
                                <input type="number" step="1" class="form-control" id="vm_customs" name="customs_rate" disabled>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> <?php echo _l('save'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php } ?>

<?php if (staff_can('create', 'aae_importer')) { ?>
<!-- ================= MODAL DEVIS RAPIDE ================= -->
<div class="modal fade" id="aaeQuoteModal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <?php echo form_open(admin_url('aae_importer/quote_create'), ['id' => 'aaeQuoteForm']); ?>
            <div class="modal-header">
                <h4 class="modal-title" id="aaeQuoteModalTitle"><?php echo _l('aae_quote_from_catalogue'); ?></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">

                <input type="hidden" id="q_vehicle_id" name="vehicle_id" value="">

                <div class="aae-preview" id="aaeQuoteVehicleBox" style="margin-bottom:16px;">
                    <b id="aaeQuoteVehicleLabel">—</b><br>
                    <span class="text-muted small" id="aaeQuoteVehicleMeta"></span>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="control-label" for="q_clientid"><?php echo _l('aae_quote_select_client'); ?> *</label>
                            <select class="form-control" id="q_clientid" name="clientid" required>
                                <option value=""></option>
                                <?php foreach ($clients as $c) { ?>
                                    <option value="<?php echo (int)$c['id']; ?>"><?php echo e($c['label']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="q_qty"><?php echo _l('aae_quote_qty'); ?></label>
                            <input type="number" min="1" step="1" class="form-control" id="q_qty" name="qty" value="1">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group select-currency">
                            <label for="q_currency"><?php echo _l('aae_quote_currency'); ?></label>
                            <select class="form-control" id="q_currency" name="currency">
                                <?php foreach ($currencies as $cur) { ?>
                                    <option value="<?php echo (int)$cur->id; ?>"<?php echo (int)$cur->id === (int)get_option('default_currency') ? ' selected' : ''; ?> data-symbol="<?php echo e(isset($cur->symbol) ? $cur->symbol : ''); ?>">
                                        <?php echo e(isset($cur->name) && $cur->name !== '' ? $cur->name : $cur->code); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="q_basis"><?php echo _l('aae_quote_basis'); ?></label>
                            <select class="form-control" id="q_basis" name="qbasis">
                                <?php foreach ($bases as $bkey => $blabel) { ?>
                                    <option value="<?php echo e($bkey); ?>" data-field="<?php echo e($bkey); ?>"><?php echo e($blabel); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="q_rate_override"><?php echo _l('aae_quote_rate_override'); ?></label>
                            <input type="number" step="0.01" class="form-control" id="q_rate_override" name="rate_override">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="create_operation" value="1" id="q_create_operation">
                                <?php echo _l('aae_quote_create_operation'); ?>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="aae-preview" style="text-align:center;">
                    <span class="text-muted"><?php echo _l('aae_quote_preview_total'); ?> :</span>
                    <b id="aaeQuoteTotal" style="font-size:16px;">—</b>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <button type="submit" class="btn btn-success"><i class="fa fa-file-text-o"></i> <?php echo _l('aae_quote'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php } ?>

    </div>
</div>
<?php init_tail(); ?>
<script>
window.aaeCsrf = {
    name: '<?php echo $CI->security->get_csrf_token_name(); ?>',
    hash: '<?php echo $CI->security->get_csrf_hash(); ?>'
};
window.aaeOptions = {
    eur_rate: <?php echo (float)get_option('aae_importer_eur_rate'); ?>,
    eur_fee_percent: <?php echo (float)get_option('aae_importer_eur_fee_percent'); ?>,
    dzd_rate: <?php echo (float)get_option('aae_importer_dzd_rate'); ?>,
    commission: <?php echo (float)get_option('aae_importer_default_commission'); ?>
};
</script>
</body>

</html>
