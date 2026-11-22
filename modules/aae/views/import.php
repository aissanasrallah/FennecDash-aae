<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="aae-page-header">
            <div>
                <a href="<?php echo admin_url('aae'); ?>" class="aae-back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    <?php echo _l('aae_catalogue'); ?>
                </a>
                <h1 class="aae-page-title"><?php echo _l('aae_import'); ?></h1>
                <p class="aae-page-subtitle"><?php echo _l('aae_import_subtitle'); ?></p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo form_open_multipart(admin_url('aae/import'), ['id' => 'aae-import-form']); ?>

                        <div class="form-group">
                            <label for="file" class="control-label"><?php echo _l('aae_import_file'); ?> <span class="text-danger">*</span></label>
                            <input type="file" id="file" name="file" accept=".csv,.txt" required />
                            <p class="help-block"><?php echo _l('aae_import_file_help'); ?></p>
                        </div>

                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="default_status" class="control-label"><?php echo _l('aae_import_default_status'); ?></label>
                                    <select id="default_status" name="default_status" class="form-control">
                                        <?php foreach ($statuses as $status) { ?>
                                        <option value="<?php echo e($status); ?>" <?php echo $status === 'available' ? 'selected' : ''; ?>>
                                            <?php echo e(_l('aae_status_' . $status)); ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                    <p class="help-block"><?php echo _l('aae_import_default_status_help'); ?></p>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="default_destination" class="control-label"><?php echo _l('aae_import_default_destination'); ?></label>
                                    <select id="default_destination" name="default_destination" class="form-control">
                                        <?php foreach ($destinations as $destination) { ?>
                                        <option value="<?php echo e($destination); ?>" <?php echo $destination === 'algeria' ? 'selected' : ''; ?>>
                                            <?php echo e(_l('aae_destination_' . $destination)); ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="default_quantity" class="control-label"><?php echo _l('aae_import_default_quantity'); ?></label>
                                    <input type="number" id="default_quantity" name="default_quantity" class="form-control"
                                        min="0" step="1" value="1" />
                                </div>
                            </div>
                        </div>

                        <div class="aae-import-options">
                            <label class="aae-checkbox">
                                <input type="checkbox" name="update_existing" value="1" checked />
                                <?php echo _l('aae_import_update_existing'); ?>
                            </label>
                            <label class="aae-checkbox">
                                <input type="checkbox" name="dry_run" value="1" />
                                <?php echo _l('aae_import_dry_run'); ?>
                            </label>
                        </div>

                        <div class="aae-form-footer aae-form-footer-inline">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-file-import tw-mr-1"></i>
                                <?php echo _l('aae_import_start'); ?>
                            </button>
                        </div>

                        <?php echo form_close(); ?>
                    </div>
                </div>

                <?php if (! empty($preview)) { ?>
                <?php $dry_run = isset($preview['preview']); ?>
                <div class="panel_s aae-import-preview">
                    <div class="panel-body">
                        <h4 class="aae-section-title"><?php echo _l('aae_import_results'); ?></h4>

                        <?php if (! empty($preview['filename'])) { ?>
                        <p class="aae-import-file"><i class="fa-regular fa-file-lines"></i> <?php echo e($preview['filename']); ?></p>
                        <?php } ?>

                        <?php if ($dry_run) { ?>
                        <div class="alert alert-info"><?php echo _l('aae_import_preview_note'); ?></div>
                        <?php } ?>

                        <div class="aae-import-counts">
                            <span class="aae-import-count"><strong><?php echo (int) $preview['total_rows']; ?></strong> <?php echo _l('aae_import_rows_total'); ?></span>
                            <span class="aae-import-count aae-count-green"><strong><?php echo (int) $preview['inserted']; ?></strong> <?php echo _l('aae_import_new'); ?></span>
                            <span class="aae-import-count aae-count-blue"><strong><?php echo (int) $preview['updated']; ?></strong> <?php echo _l('aae_import_updated'); ?></span>
                            <span class="aae-import-count aae-count-amber"><strong><?php echo (int) $preview['skipped']; ?></strong> <?php echo _l('aae_import_skipped'); ?></span>
                            <span class="aae-import-count aae-count-red"><strong><?php echo (int) $preview['failed']; ?></strong> <?php echo _l('aae_import_failed'); ?></span>
                        </div>

                        <?php if (! empty($preview['messages'])) { ?>
                        <div class="aae-import-messages">
                            <h5><?php echo _l('aae_import_skipped_details'); ?></h5>
                            <ul>
                                <?php foreach ($preview['messages'] as $message) { ?>
                                <li>
                                    <span class="label label-<?php echo $message['type'] === 'failed' ? 'danger' : 'warning'; ?>">
                                        <?php echo e($message['type']); ?>
                                    </span>
                                    <?php if (! empty($message['line'])) { ?>
                                    <span class="aae-import-line"><?php echo _l('aae_line'); ?> <?php echo (int) $message['line']; ?></span>
                                    <?php } ?>
                                    <?php echo e($message['message']); ?>
                                </li>
                                <?php } ?>
                            </ul>
                        </div>
                        <?php } ?>

                        <?php if (! empty($preview['preview'])) { ?>
                        <div class="table-responsive">
                            <table class="table table-condensed table-striped aae-import-table">
                                <thead>
                                    <tr>
                                        <th><?php echo _l('aae_line'); ?></th>
                                        <th><?php echo _l('aae_brand'); ?></th>
                                        <th><?php echo _l('aae_model'); ?></th>
                                        <th><?php echo _l('aae_year'); ?></th>
                                        <th><?php echo _l('aae_fob_price'); ?></th>
                                        <th><?php echo _l('aae_price_eur'); ?></th>
                                        <th><?php echo _l('aae_price_dzd'); ?></th>
                                        <th><?php echo _l('aae_import_action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($preview['preview'] as $row) { ?>
                                    <tr>
                                        <td><?php echo (int) $row['line']; ?></td>
                                        <td><?php echo e($row['brand']); ?></td>
                                        <td><?php echo e($row['model']); ?></td>
                                        <td><?php echo (int) $row['model_year'] ?: '-'; ?></td>
                                        <td class="aae-money"><?php echo aae_format_money($row['fob_price'], 'EUR'); ?></td>
                                        <td class="aae-money"><?php echo aae_format_money($row['price_eur'], 'EUR'); ?></td>
                                        <td class="aae-money"><?php echo aae_format_money($row['price_dzd'], 'DZD'); ?></td>
                                        <td>
                                            <?php
                                            $action_class = 'default';
                                            if ($row['action'] === 'insert') {
                                                $action_class = 'success';
                                            } elseif ($row['action'] === 'update') {
                                                $action_class = 'info';
                                            } elseif ($row['action'] === 'skip') {
                                                $action_class = 'warning';
                                            }
                                            ?>
                                            <span class="label label-<?php echo $action_class; ?>">
                                                <?php echo _l('aae_import_action_' . $row['action']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
            </div>

            <div class="col-md-5">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="aae-section-title"><?php echo _l('aae_import_sheet_format'); ?></h4>
                        <p class="text-muted"><?php echo _l('aae_import_sheet_help'); ?></p>
                        <ul class="aae-import-columns">
                            <li><code>MARQUE</code> &middot; <code>MODELE</code> &middot; <code>MOTEUR</code></li>
                            <li><code>ANNEE MODELE</code> &middot; <code>COULEUR</code></li>
                            <li><code>FOB PRIX $</code> &middot; <code>TRADUCTION DOCUMENTS</code></li>
                            <li><code>TRADUCTION DU VEHICULE</code> &middot; <code>FRAIS DE LIVRAISON</code></li>
                            <li><code>PRIX TOTAL $</code> &middot; <code>PRIX EN €</code> &middot; <code>COMMISSION</code></li>
                            <li><code>TOTAL TTC</code> &middot; <code>DA TAUX 282</code> &middot; <code>TTC EN MILLIONS</code></li>
                            <li><code>ESTIMATION DEDOUANEMENT</code> &middot; <code>liens vehicule</code></li>
                        </ul>
                        <div class="alert alert-warning aae-import-alert">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <?php echo _l('aae_import_header_hint'); ?>
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="aae-section-title"><?php echo _l('aae_import_history'); ?></h4>

                        <?php if (empty($imports)) { ?>
                        <p class="text-muted"><?php echo _l('aae_import_no_history'); ?></p>
                        <?php } else { ?>
                        <div class="table-responsive">
                            <table class="table table-condensed aae-import-history">
                                <thead>
                                    <tr>
                                        <th><?php echo _l('aae_date'); ?></th>
                                        <th><?php echo _l('aae_import_file'); ?></th>
                                        <th><?php echo _l('aae_import_new'); ?></th>
                                        <th><?php echo _l('aae_import_updated'); ?></th>
                                        <th><?php echo _l('aae_import_skipped'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($imports as $import) { ?>
                                    <tr>
                                        <td data-toggle="tooltip" title="<?php echo e(_dt($import->datecreated)); ?>">
                                            <?php echo e(_d($import->datecreated)); ?>
                                        </td>
                                        <td class="aae-import-history-file">
                                            <?php echo e($import->filename); ?>
                                            <?php if (! empty($import->staff_name)) { ?>
                                            <div class="aae-model-meta"><?php echo e($import->staff_name); ?></div>
                                            <?php } ?>
                                        </td>
                                        <td><span class="text-success"><?php echo (int) $import->inserted; ?></span></td>
                                        <td><span class="text-info"><?php echo (int) $import->updated; ?></span></td>
                                        <td><span class="text-warning"><?php echo (int) $import->skipped; ?></span></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
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
