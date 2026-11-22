<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
<div class="content">

<div class="row" style="margin-bottom:22px;">
    <div class="col-md-12">
        <h4 class="no-margin display-block" style="font-size:18px;"><i class="fa fa-file-excel-o"></i> <?php echo _l('aae_import_csv'); ?></h4>
        <div style="margin-top:10px;" class="btn-group">
            <a href="<?php echo admin_url('aae_importer/catalogue'); ?>" class="btn btn-default"><i class="fa fa-car"></i> <?php echo _l('aae_menu_catalogue'); ?></a>
            <a href="<?php echo admin_url('aae_importer/export_csv'); ?>" class="btn btn-default"><i class="fa fa-download"></i> <?php echo _l('aae_export_csv'); ?></a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="panel_s">
            <div class="panel-body">
                <div class="alert alert-info aae-notice">
                    <i class="fa fa-info-circle"></i> <?php echo _l('aae_import_hint'); ?>
                </div>

                <?php echo form_open_multipart(admin_url('aae_importer/import_csv')); ?>
                    <div class="form-group">
                        <label class="control-label" for="import_file"><?php echo _l('aae_import_select_file'); ?></label>
                        <input type="file" class="form-control" id="import_file" name="import_file" accept=".csv,text/csv" required>
                    </div>

                    <div class="form-group">
                        <label for="import_status"><?php echo _l('aae_import_status'); ?></label>
                        <select class="form-control" id="import_status" name="import_status">
                            <?php foreach ($vehicle_statuses as $key => $s) { ?>
                                <option value="<?php echo e($key); ?>"<?php echo $key === 'disponible' ? ' selected' : ''; ?>><?php echo e($s['name']); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="checkbox" style="margin-bottom:12px;">
                        <label>
                            <input type="checkbox" name="replace_all" value="1">
                            <?php echo _l('aae_import_replace_all'); ?>
                        </label>
                    </div>
                    <div class="alert alert-danger aae-notice">
                        <i class="fa fa-times-circle"></i> <?php echo _l('aae_replace_warning'); ?>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fa fa-upload"></i> <?php echo _l('aae_import_go'); ?></button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel_s">
            <div class="panel-body">
                <h5 class="bold" style="border-bottom:1px solid #eee;padding-bottom:8px;"><i class="fa fa-info-circle"></i> <?php echo _l('aae_vehicles'); ?></h5>
                <p style="margin-top:14px;" class="text-info bold">
                    <i class="fa fa-database"></i>
                    <?php echo _l('aae_import_current_count', $total); ?>
                </p>
                <hr>
                <p class="text-muted small">
                    Colonnes reconnues : MARQUE, MODELE, MOTEUR, ANNEE MODELE, COULEUR, FOB PRIX $, TRADUCTION DOCUMENTS, TRADUCTION DU VEHICULE, FRAIS DE LIVRAISON, PRIX TOTAL $, PRIX EN €, COMMISSION, TOTAL TTC, DA TAUX 282 ET 275, TTC EN MILLIONS, ESTIMATION DEDOUANEMENT, LIEN.
                </p>
                <p class="text-muted small" style="margin-bottom:0;">
                    Le fichier peut utiliser « ; » ou « , » comme separateur et les montants en format francais (8 493 €).
                </p>
            </div>
        </div>
    </div>
</div>

    </div>
</div>
<?php init_tail(); ?>
</body>

</html>
