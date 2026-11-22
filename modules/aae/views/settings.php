<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
$eur_markup     = aae_get_option('aae_eur_markup', 0.8);
$dzd_rate       = aae_get_option('aae_dzd_rate', 282);
$doc_fee        = aae_get_option('aae_default_doc_fee', 100);
$vehicle_fee    = aae_get_option('aae_default_vehicle_fee', 0);
$delivery_fee   = aae_get_option('aae_default_delivery_fee', 1400);
$commission     = aae_get_option('aae_default_commission', 850);
$company_name   = aae_get_option('aae_company_name', 'Algerie Auto Export');

// WhatsApp Business API settings
$whatsapp_access_token      = aae_get_option('aae_whatsapp_access_token', '');
$whatsapp_phone_number_id   = aae_get_option('aae_whatsapp_phone_number_id', '');
$whatsapp_business_account_id = aae_get_option('aae_whatsapp_business_account_id', '');
$whatsapp_webhook_verify_token = aae_get_option('aae_whatsapp_webhook_verify_token', '');

// E-mail delivery (used by "Save & Send" on estimates and invoices).
$smtp_host   = trim((string) get_option('smtp_host'));
$smtp_email  = trim((string) get_option('smtp_email'));
$email_ready = get_option('email_protocol') !== 'smtp' || ($smtp_host !== '' && $smtp_email !== '');

$sample         = aae_calculate_prices([
    'fob_price'    => 8500,
    'doc_fee'      => (float) $doc_fee,
    'vehicle_fee'  => (float) $vehicle_fee,
    'delivery_fee' => (float) $delivery_fee,
    'commission'   => (float) $commission,
]);
?>
<div id="wrapper">
    <div class="content">
        <?php echo form_open(admin_url('aae/settings'), ['id' => 'aae-settings-form']); ?>
        <div class="aae-page-header">
            <div>
                <a href="<?php echo admin_url('aae'); ?>" class="aae-back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    <?php echo _l('aae_catalogue'); ?>
                </a>
                <h1 class="aae-page-title"><?php echo _l('aae_settings'); ?></h1>
                <p class="aae-page-subtitle"><?php echo _l('aae_settings_subtitle'); ?></p>
            </div>
            <div class="aae-page-actions">
                <a href="<?php echo admin_url('aae'); ?>" class="btn btn-default"><?php echo _l('aae_cancel'); ?></a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-regular fa-floppy-disk tw-mr-1"></i>
                    <?php echo _l('aae_save'); ?>
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="aae-section-title"><?php echo _l('aae_settings_company'); ?></h4>

                        <div class="form-group">
                            <label for="aae_company_name" class="control-label"><?php echo _l('aae_company_name'); ?></label>
                            <input type="text" id="aae_company_name" name="aae_company_name" class="form-control"
                                value="<?php echo e($company_name); ?>" />
                            <p class="help-block"><?php echo _l('aae_company_name_help'); ?></p>
                        </div>

                        <hr />

                        <h4 class="aae-section-title"><?php echo _l('aae_settings_rates'); ?></h4>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="aae_eur_markup" class="control-label"><?php echo _l('aae_eur_markup'); ?></label>
                                    <div class="input-group">
                                        <input type="number" id="aae_eur_markup" name="aae_eur_markup" class="form-control"
                                            min="0" step="0.01" value="<?php echo e($eur_markup); ?>" />
                                        <span class="input-group-addon">%</span>
                                    </div>
                                    <p class="help-block"><?php echo _l('aae_eur_markup_help'); ?></p>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="aae_dzd_rate" class="control-label"><?php echo _l('aae_dzd_rate'); ?></label>
                                    <input type="number" id="aae_dzd_rate" name="aae_dzd_rate" class="form-control"
                                        min="0.01" step="0.01" value="<?php echo e($dzd_rate); ?>" />
                                    <p class="help-block"><?php echo _l('aae_dzd_rate_help'); ?></p>
                                </div>
                            </div>
                        </div>

                        <hr />

                        <h4 class="aae-section-title"><?php echo _l('aae_settings_defaults'); ?></h4>
                        <p class="text-muted"><?php echo _l('aae_settings_defaults_help'); ?></p>

                        <div class="row">
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="aae_default_doc_fee" class="control-label"><?php echo _l('aae_doc_fee'); ?></label>
                                    <input type="number" id="aae_default_doc_fee" name="aae_default_doc_fee" class="form-control"
                                        min="0" step="0.01" value="<?php echo e($doc_fee); ?>" />
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="aae_default_vehicle_fee" class="control-label"><?php echo _l('aae_vehicle_fee'); ?></label>
                                    <input type="number" id="aae_default_vehicle_fee" name="aae_default_vehicle_fee" class="form-control"
                                        min="0" step="0.01" value="<?php echo e($vehicle_fee); ?>" />
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="aae_default_delivery_fee" class="control-label"><?php echo _l('aae_delivery_fee'); ?></label>
                                    <input type="number" id="aae_default_delivery_fee" name="aae_default_delivery_fee" class="form-control"
                                        min="0" step="0.01" value="<?php echo e($delivery_fee); ?>" />
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="aae_default_commission" class="control-label"><?php echo _l('aae_commission'); ?></label>
                                    <input type="number" id="aae_default_commission" name="aae_default_commission" class="form-control"
                                        min="0" step="0.01" value="<?php echo e($commission); ?>" />
                                </div>
                            </div>
                        </div>

                        <hr />

                        <!-- WhatsApp Business API Section -->
                        <div class="aae-settings-whatsapp">
                            <div class="panel_s">
                                <div class="panel-body">
                                    <h4 class="aae-section-title"><?php echo _l('aae_settings_whatsapp'); ?></h4>
                                    <p class="text-muted"><?php echo _l('aae_settings_whatsapp_help'); ?></p>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="aae_whatsapp_access_token" class="control-label">
                                                    <?php echo _l('aae_whatsapp_access_token'); ?>
                                                </label>
                                                <input type="password" id="aae_whatsapp_access_token" name="aae_whatsapp_access_token" class="form-control"
                                                    value="<?php echo e($whatsapp_access_token); ?>" autocomplete="new-password" />
                                                <p class="help-block"><?php echo _l('aae_whatsapp_access_token_help'); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="aae_whatsapp_phone_number_id" class="control-label">
                                                    <?php echo _l('aae_whatsapp_phone_number_id'); ?>
                                                </label>
                                                <input type="text" id="aae_whatsapp_phone_number_id" name="aae_whatsapp_phone_number_id" class="form-control"
                                                    value="<?php echo e($whatsapp_phone_number_id); ?>" />
                                                <p class="help-block"><?php echo _l('aae_whatsapp_phone_number_id_help'); ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="aae_whatsapp_business_account_id" class="control-label">
                                                    <?php echo _l('aae_whatsapp_business_account_id'); ?>
                                                </label>
                                                <input type="text" id="aae_whatsapp_business_account_id" name="aae_whatsapp_business_account_id" class="form-control"
                                                    value="<?php echo e($whatsapp_business_account_id); ?>" />
                                                <p class="help-block"><?php echo _l('aae_whatsapp_business_account_id_help'); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="aae_whatsapp_webhook_verify_token" class="control-label">
                                                    <?php echo _l('aae_whatsapp_webhook_verify_token'); ?>
                                                </label>
                                                <input type="text" id="aae_whatsapp_webhook_verify_token" name="aae_whatsapp_webhook_verify_token" class="form-control"
                                                    value="<?php echo e($whatsapp_webhook_verify_token); ?>" />
                                                <p class="help-block"><?php echo _l('aae_whatsapp_webhook_verify_token_help'); ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <hr />

                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5><?php echo _l('aae_whatsapp_templates'); ?></h5>
                                            <p class="text-muted small"><?php echo _l('aae_whatsapp_templates_help'); ?></p>
                                            <ul class="aae-template-list">
                                                <li><code>aae_welcome</code> - <?php echo _l('aae_welcome_desc'); ?></li>
                                                <li><code>aae_quote_ready</code> - <?php echo _l('aae_quote_ready_desc'); ?></li>
                                                <li><code>aae_quote_sent</code> - <?php echo _l('aae_quote_sent_desc'); ?></li>
                                                <li><code>aae_order_confirmed</code> - <?php echo _l('aae_order_confirmed_desc'); ?></li>
                                                <li><code>aae_payment_received</code> - <?php echo _l('aae_payment_received_desc'); ?></li>
                                                <li><code>aae_supplier_paid</code> - <?php echo _l('aae_supplier_paid_desc'); ?></li>
                                                <li><code>aae_supplier_dossier_sent</code> - <?php echo _l('aae_supplier_dossier_sent_desc'); ?></li>
                                                <li><code>aae_departure_confirmed</code> - <?php echo _l('aae_departure_confirmed_desc'); ?></li>
                                                <li><code>aae_shipped</code> - <?php echo _l('aae_shipped_desc'); ?></li>
                                                <li><code>aae_customs_clearance</code> - <?php echo _l('aae_customs_clearance_desc'); ?></li>
                                                <li><code>aae_transitaire_docs_request</code> - <?php echo _l('aae_transitaire_docs_request_desc'); ?></li>
                                                <li><code>aae_arrived</code> - <?php echo _l('aae_arrived_desc'); ?></li>
                                                <li><code>aae_delivered</code> - <?php echo _l('aae_delivered_desc'); ?></li>
                                                <li><code>aae_final_papers_ready</code> - <?php echo _l('aae_final_papers_ready_desc'); ?></li>
                                                <li><code>aae_closed</code> - <?php echo _l('aae_closed_desc'); ?></li>
                                                <li><code>aae_cancelled</code> - <?php echo _l('aae_cancelled_desc'); ?></li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h5><?php echo _l('aae_whatsapp_webhook'); ?></h5>
                                            <p class="text-muted small"><?php echo _l('aae_whatsapp_webhook_help'); ?></p>
                                            <div class="form-group">
                                                <label class="control-label"><?php echo _l('aae_whatsapp_webhook_url'); ?></label>
                                                <input type="text" class="form-control" readonly
                                                    value="<?php echo site_url('aae/whatsapp/webhook'); ?>" />
                                            </div>
                                            <button type="button" id="aae-whatsapp-test-btn" class="btn btn-default btn-sm">
                                                <i class="fa-solid fa-paper-plane tw-mr-1"></i>
                                                <?php echo _l('aae_whatsapp_test_connection'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="aae-form-footer aae-form-footer-inline">
                            <a href="<?php echo admin_url('aae'); ?>" class="btn btn-default"><?php echo _l('aae_cancel'); ?></a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-regular fa-floppy-disk tw-mr-1"></i>
                                <?php echo _l('aae_save'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="aae-section-title"><?php echo _l('aae_settings_email'); ?></h4>
                        <p class="text-muted"><?php echo _l('aae_settings_email_help'); ?></p>
                        <p>
                            <?php if ($email_ready) { ?>
                            <span class="label label-success"><?php echo _l('aae_email_configured'); ?></span>
                            <?php } else { ?>
                            <span class="label label-warning"><?php echo _l('aae_email_not_configured'); ?></span>
                            <?php } ?>
                        </p>
                        <a href="<?php echo admin_url('settings?group=email'); ?>" class="btn btn-default btn-sm">
                            <i class="fa fa-envelope tw-mr-1"></i><?php echo _l('aae_open_email_settings'); ?>
                        </a>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="aae-section-title"><?php echo _l('aae_formula'); ?></h4>

                        <ol class="aae-formula-list">
                            <li>
                                <span class="aae-formula-label"><?php echo _l('aae_total_usd'); ?></span>
                                <code>FOB + <?php echo _l('aae_doc_fee'); ?> + <?php echo _l('aae_vehicle_fee'); ?> + <?php echo _l('aae_delivery_fee'); ?></code>
                            </li>
                            <li>
                                <span class="aae-formula-label"><?php echo _l('aae_price_eur'); ?></span>
                                <code><?php echo _l('aae_total_usd'); ?> &times; (1 + <?php echo _l('aae_eur_markup'); ?> / 100)</code>
                            </li>
                            <li>
                                <span class="aae-formula-label"><?php echo _l('aae_total_ttc'); ?></span>
                                <code><?php echo _l('aae_price_eur'); ?> + <?php echo _l('aae_commission'); ?></code>
                            </li>
                            <li>
                                <span class="aae-formula-label"><?php echo _l('aae_price_dzd'); ?></span>
                                <code><?php echo _l('aae_total_ttc'); ?> &times; <?php echo _l('aae_dzd_rate'); ?></code>
                            </li>
                            <li>
                                <span class="aae-formula-label"><?php echo _l('aae_price_millions'); ?></span>
                                <code><?php echo _l('aae_price_dzd'); ?> / 10 000</code>
                            </li>
                        </ol>

                        <hr />

                        <h4 class="aae-section-title"><?php echo _l('aae_sample_calculation'); ?></h4>
                        <p class="text-muted"><?php echo _l('aae_sample_calculation_help'); ?></p>

                        <table class="table table-condensed aae-sample-table">
                            <tbody>
                                <tr>
                                    <td><?php echo _l('aae_fob_price'); ?></td>
                                    <td class="text-right"><?php echo aae_format_money(8500, 'EUR'); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo _l('aae_total_usd'); ?></td>
                                    <td class="text-right"><?php echo aae_format_money($sample['total_usd'], 'EUR'); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo _l('aae_price_eur'); ?></td>
                                    <td class="text-right"><?php echo aae_format_money($sample['price_eur'], 'EUR'); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo _l('aae_total_ttc'); ?></td>
                                    <td class="text-right"><?php echo aae_format_money($sample['total_ttc'], 'EUR'); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo _l('aae_price_dzd'); ?></td>
                                    <td class="text-right"><?php echo aae_format_money($sample['price_dzd'], 'DZD'); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo _l('aae_price_millions'); ?></td>
                                    <td class="text-right"><strong><?php echo aae_format_millions($sample['price_millions']); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="alert alert-info aae-settings-alert">
                            <i class="fa-solid fa-circle-info"></i>
                            <?php echo _l('aae_settings_apply_hint'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php echo form_close(); ?>
    </div>
</div>
<?php init_tail(); ?>
</body>

</html>
