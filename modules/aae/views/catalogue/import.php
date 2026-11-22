<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="aae-page-header">
                    <div>
                        <h1 class="aae-page-title"><?php echo _l('aae_import'); ?></h1>
                        <p class="aae-page-subtitle"><?php echo _l('aae_import_catalogue_subtitle'); ?></p>
                    </div>
                    <div class="aae-page-actions">
                        <a href="<?php echo admin_url('aae/catalogue'); ?>" class="btn btn-default">
                            <i class="fa-solid fa-arrow-left tw-mr-1"></i>
                            <?php echo _l('aae_cancel'); ?>
                        </a>
                    </div>
                </div>

                <div class="aae-summary">
                    <div class="aae-stat">
                        <div class="aae-stat-icon aae-icon-blue"><i class="fa-solid fa-car"></i></div>
                        <div>
                            <div class="aae-stat-value"><?php echo $total_rows; ?></div>
                            <div class="aae-stat-label"><?php echo _l('aae_total_rows'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="aae-section-title"><?php echo _l('aae_select_csv_file'); ?></h4>

                        <form action="<?php echo admin_url('aae/catalogue/import'); ?>" method="post" enctype="multipart/form-data" id="aae-import-form">

                            <div class="form-group">
                                <label for="file" class="control-label"><?php echo _l('aae_import_file'); ?> <span class="text-danger">*</span></label>
                                <input type="file" id="file" name="file" accept=".csv,.txt" required />
                                <p class="help-block"><?php echo _l('aae_import_file_help'); ?></p>
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
                                <a href="<?php echo admin_url('aae/catalogue'); ?>" class="btn btn-default">
                                    <i class="fa-solid fa-times tw-mr-1"></i>
                                    <?php echo _l('aae_cancel'); ?>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (! empty($preview) && is_array($preview)) { ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="aae-section-title"><?php echo _l('aae_import_results'); ?></h4>

                        <?php if (! empty($preview['filename'])) { ?>
                        <p class="aae-page-subtitle"><?php echo e($preview['filename']); ?></p>
                        <?php } ?>

                        <div class="aae-summary">
                            <div class="aae-stat">
                                <div class="aae-stat-icon aae-icon-blue"><i class="fa-solid fa-list"></i></div>
                                <div>
                                    <div class="aae-stat-value"><?php echo (int) ($preview['total_rows'] ?? 0); ?></div>
                                    <div class="aae-stat-label"><?php echo _l('aae_import_rows_total'); ?></div>
                                </div>
                            </div>
                            <div class="aae-stat">
                                <div class="aae-stat-icon aae-icon-green"><i class="fa-solid fa-plus"></i></div>
                                <div>
                                    <div class="aae-stat-value"><?php echo (int) ($preview['inserted'] ?? 0); ?></div>
                                    <div class="aae-stat-label"><?php echo _l('aae_import_new'); ?></div>
                                </div>
                            </div>
                            <div class="aae-stat">
                                <div class="aae-stat-icon aae-icon-blue"><i class="fa-solid fa-pen"></i></div>
                                <div>
                                    <div class="aae-stat-value"><?php echo (int) ($preview['updated'] ?? 0); ?></div>
                                    <div class="aae-stat-label"><?php echo _l('aae_import_updated'); ?></div>
                                </div>
                            </div>
                            <div class="aae-stat">
                                <div class="aae-stat-icon aae-icon-amber"><i class="fa-solid fa-forward"></i></div>
                                <div>
                                    <div class="aae-stat-value"><?php echo (int) ($preview['skipped'] ?? 0); ?></div>
                                    <div class="aae-stat-label"><?php echo _l('aae_import_skipped'); ?></div>
                                </div>
                            </div>
                            <div class="aae-stat">
                                <div class="aae-stat-icon aae-icon-red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                                <div>
                                    <div class="aae-stat-value"><?php echo (int) ($preview['failed'] ?? 0); ?></div>
                                    <div class="aae-stat-label"><?php echo _l('aae_import_failed'); ?></div>
                                </div>
                            </div>
                        </div>

                        <?php if (! empty($preview['messages']) && is_array($preview['messages'])) { ?>
                        <table class="table table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo _l('aae_import_log_row'); ?></th>
                                    <th><?php echo _l('aae_import_action'); ?></th>
                                    <th><?php echo _l('aae_import_log_details'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($preview['messages'], 0, 100) as $message) { ?>
                                <tr>
                                    <td><?php echo (int) ($message['line'] ?? 0); ?></td>
                                    <td><?php echo e($message['type'] ?? ''); ?></td>
                                    <td><?php echo e($message['message'] ?? ''); ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>