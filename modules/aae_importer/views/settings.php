<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
<div class="content">

<div class="row" style="margin-bottom:18px;">
    <div class="col-md-12">
        <h4 class="no-margin display-block" style="font-size:18px;"><i class="fa fa-cog"></i> <?php echo _l('aae_settings'); ?></h4>
        <div style="margin-top:10px;">
            <a href="<?php echo admin_url('aae_importer/catalogue'); ?>" class="btn btn-default"><i class="fa fa-car"></i> <?php echo _l('aae_menu_catalogue'); ?></a>
            <a href="<?php echo admin_url('aae_importer/sync_prices'); ?>" class="btn btn-success" style="background-color:#16a34a;border-color:#16a34a;color:#fff;margin-left:8px;"><i class="fa fa-refresh"></i> Synchroniser les prix vers le site</a>
        </div>
    </div>
</div>

<div class="panel_s">
    <div class="panel-body">
        <div class="alert alert-info aae-notice"><i class="fa fa-info-circle"></i> <?php echo _l('aae_settings_hint'); ?></div>

        <?php echo form_open(admin_url('aae_importer/settings')); ?>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label" for="opt_eur_rate"><?php echo _l('aae_eur_rate'); ?></label>
                    <input type="number" step="0.0001" class="form-control" id="opt_eur_rate" name="aae_importer_eur_rate" value="<?php echo e(get_option('aae_importer_eur_rate')); ?>">
                    <p class="help-block">1 USD → ? EUR (catalogue : 0,892)</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label" for="opt_eur_fee"><?php echo _l('aae_eur_fee_percent'); ?></label>
                    <input type="number" step="0.01" class="form-control" id="opt_eur_fee" name="aae_importer_eur_fee_percent" value="<?php echo e(get_option('aae_importer_eur_fee_percent')); ?>">
                    <p class="help-block">+0,8 %</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label" for="opt_dzd_rate"><?php echo _l('aae_dzd_rate'); ?></label>
                    <input type="number" step="0.01" class="form-control" id="opt_dzd_rate" name="aae_importer_dzd_rate" value="<?php echo e(get_option('aae_importer_dzd_rate')); ?>">
                    <p class="help-block">282 ou 275 DA / €</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label" for="opt_commission"><?php echo _l('aae_commission'); ?> (EUR)</label>
                    <input type="number" step="0.01" class="form-control" id="opt_commission" name="aae_importer_default_commission" value="<?php echo e(get_option('aae_importer_default_commission')); ?>">
                    <p class="help-block">Commission par défaut (ex : 850 €)</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label" for="opt_validity"><?php echo _l('aae_quote'); ?> — validité (jours)</label>
                    <input type="number" step="1" min="1" class="form-control" id="opt_validity" name="aae_importer_quote_validity_days" value="<?php echo e(get_option('aae_importer_quote_validity_days')); ?>">
                    <p class="help-block">Durée de validité des devis créés depuis le catalogue</p>
                </div>
            </div>
            <div class="col-md-12">
                <div class="checkbox" style="margin-top:10px;">
                    <label>
                        <input type="hidden" name="aae_importer_client_portal" value="0">
                        <input type="checkbox" name="aae_importer_client_portal" value="1"<?php echo get_option('aae_importer_client_portal') == '1' ? ' checked' : ''; ?>>
                        <?php echo _l('aae_settings_portal'); ?>
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> <?php echo _l('save'); ?></button>

        <?php echo form_close(); ?>
    </div>
</div>

    </div>
</div>
<?php init_tail(); ?>
</body>

</html>
