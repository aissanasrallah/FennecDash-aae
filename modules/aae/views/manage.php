<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="aae-page-header">
                    <div>
                        <h1 class="aae-page-title"><?php echo _l('aae_catalogue'); ?></h1>
                        <p class="aae-page-subtitle"><?php echo e($company_name); ?> &middot; <?php echo _l('aae_catalogue_subtitle'); ?></p>
                    </div>
                    <div class="aae-page-actions">
                        <?php if (staff_can('create', 'aae')) { ?>
                        <a href="<?php echo admin_url('aae/car'); ?>" class="btn btn-primary">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo _l('aae_new_car'); ?>
                        </a>
                        <?php } ?>
                        <?php if (staff_can('import', 'aae')) { ?>
                        <a href="<?php echo admin_url('aae/import'); ?>" class="btn btn-default">
                            <i class="fa-solid fa-file-import tw-mr-1"></i>
                            <?php echo _l('aae_import'); ?>
                        </a>
                        <?php } ?>
                        <a href="<?php echo admin_url('aae/export'); ?>" class="btn btn-default">
                            <i class="fa-solid fa-file-export tw-mr-1"></i>
                            <?php echo _l('aae_export_csv'); ?>
                        </a>
                        <a href="<?php echo admin_url('aae/sync_prices'); ?>" class="btn btn-success" style="background-color:#16a34a;border-color:#16a34a;color:#fff;">
                            <i class="fa-solid fa-rotate tw-mr-1"></i>
                            Synchroniser les prix vers le site
                        </a>
                        <?php if (is_admin()) { ?>
                        <a href="<?php echo admin_url('aae/settings'); ?>" class="btn btn-default" title="<?php echo _l('aae_settings'); ?>">
                            <i class="fa-solid fa-sliders"></i>
                        </a>
                        <?php } ?>
                    </div>
                </div>

                <div class="aae-summary">
                    <div class="aae-stat">
                        <div class="aae-stat-icon aae-icon-blue"><i class="fa-solid fa-car"></i></div>
                        <div>
                            <div class="aae-stat-value" data-aae-stat="models"><?php echo (int) $stats['models']; ?></div>
                            <div class="aae-stat-label"><?php echo _l('aae_total_models'); ?></div>
                        </div>
                    </div>
                    <div class="aae-stat">
                        <div class="aae-stat-icon aae-icon-green"><i class="fa-solid fa-circle-check"></i></div>
                        <div>
                            <div class="aae-stat-value" data-aae-stat="available"><?php echo (int) $stats['available']; ?></div>
                            <div class="aae-stat-label"><?php echo _l('aae_available_units'); ?></div>
                        </div>
                    </div>
                    <div class="aae-stat">
                        <div class="aae-stat-icon aae-icon-amber"><i class="fa-solid fa-clock"></i></div>
                        <div>
                            <div class="aae-stat-value" data-aae-stat="reserved"><?php echo (int) $stats['reserved']; ?></div>
                            <div class="aae-stat-label"><?php echo _l('aae_reserved_units'); ?></div>
                        </div>
                    </div>
                    <div class="aae-stat">
                        <div class="aae-stat-icon aae-icon-cyan"><i class="fa-solid fa-ship"></i></div>
                        <div>
                            <div class="aae-stat-value" data-aae-stat="in_transit"><?php echo (int) $stats['in_transit']; ?></div>
                            <div class="aae-stat-label"><?php echo _l('aae_in_transit_units'); ?></div>
                        </div>
                    </div>
                    <div class="aae-stat">
                        <div class="aae-stat-icon aae-icon-violet"><i class="fa-solid fa-sack-dollar"></i></div>
                        <div>
                            <div class="aae-stat-value aae-stat-value-money" data-aae-stat="stock_value_usd"><?php echo aae_format_money($stats['stock_value_usd'], 'EUR'); ?></div>
                            <div class="aae-stat-label"><?php echo _l('aae_catalogue_value'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="aae-stats-note">
                    <span><?php echo _l('aae_delivered'); ?>: <strong data-aae-stat="delivered"><?php echo (int) $stats['delivered']; ?></strong></span>
                    <span><?php echo _l('aae_unavailable'); ?>: <strong data-aae-stat="unavailable"><?php echo (int) $stats['unavailable']; ?></strong></span>
                </div>

                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php if ($filter_client) { ?>
                        <div class="aae-filter-client">
                            <i class="fa-regular fa-user"></i>
                            <?php echo _l('aae_filtered_client'); ?>
                            <strong><?php echo e($filter_client->company); ?></strong>
                            <a href="<?php echo admin_url('aae'); ?>" class="aae-filter-clear">
                                <i class="fa-solid fa-xmark"></i>
                                <?php echo _l('aae_clear_filter'); ?>
                            </a>
                        </div>
                        <?php } ?>

                        <div class="row aae-filters">
                            <div class="col-sm-4 col-md-3">
                                <label for="aae-filter-brand"><?php echo _l('aae_brand'); ?></label>
                                <select id="aae-filter-brand" class="form-control aae-filter">
                                    <option value=""><?php echo _l('aae_all_brands'); ?></option>
                                    <?php foreach ($brands as $brand) { ?>
                                    <option value="<?php echo e($brand); ?>"><?php echo e($brand); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-sm-4 col-md-3">
                                <label for="aae-filter-status"><?php echo _l('aae_status'); ?></label>
                                <select id="aae-filter-status" class="form-control aae-filter">
                                    <option value=""><?php echo _l('aae_all_statuses'); ?></option>
                                    <?php foreach ($statuses as $status) { ?>
                                    <option value="<?php echo e($status); ?>"><?php echo e(_l('aae_status_' . $status)); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-sm-4 col-md-3">
                                <label for="aae-filter-model"><?php echo _l('aae_model'); ?></label>
                                <select id="aae-filter-model" class="form-control aae-filter" disabled
                                    data-placeholder-brand="<?php echo e(_l('aae_select_brand_first')); ?>"
                                    data-placeholder-all="<?php echo e(_l('aae_all_models')); ?>">
                                    <option value=""><?php echo _l('aae_select_brand_first'); ?></option>
                                </select>
                            </div>
                            <div class="col-sm-4 col-md-3">
                                <label>&nbsp;</label>
                                <a href="<?php echo admin_url('aae'); ?>" class="btn btn-default btn-block">
                                    <i class="fa-solid fa-rotate-left tw-mr-1"></i>
                                    <?php echo _l('aae_reset_filters'); ?>
                                </a>
                            </div>
                            <input type="hidden" id="aae-filter-client" value="<?php echo (int) $filter_client_id; ?>" />
                        </div>

                        <?php render_datatable([
                            ['name' => _l('aae_id'), 'th_attrs' => ['data-custom-field' => '1', 'data-type' => 'number']],
                            _l('aae_brand'),
                            _l('aae_model'),
                            _l('aae_engine'),
                            ['name' => _l('aae_year'), 'th_attrs' => ['data-custom-field' => '1', 'data-type' => 'number']],
                            ['name' => _l('aae_quantity'), 'th_attrs' => ['data-custom-field' => '1', 'data-type' => 'number']],
                            _l('aae_status'),
                            ['name' => _l('aae_price_eur'), 'th_attrs' => ['data-custom-field' => '1', 'data-type' => 'number']],
                            ['name' => _l('aae_total_ttc'), 'th_attrs' => ['data-custom-field' => '1', 'data-type' => 'number']],
                            ['name' => _l('aae_price_dzd'), 'th_attrs' => ['data-custom-field' => '1', 'data-type' => 'number']],
                            ['name' => _l('aae_customs_column'), 'th_attrs' => ['data-custom-field' => '1', 'data-type' => 'number']],
                            _l('aae_invoice'),
                            _l('aae_dateupdated'),
                            _l('aae_options'),
                        ], 'aae-cars', [''], ['data-last-order-identifier' => 'aae-cars']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    window.aaeModelsByBrand = <?php echo json_encode($models_by_brand, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<?php init_tail(); ?>
<script>
$(function() {
    initDataTable('.table-aae-cars', window.location.href, [13], [13], {
        brand: '#aae-filter-brand',
        status: '#aae-filter-status',
        model: '#aae-filter-model',
        client_id: '#aae-filter-client'
    }, [0, 'desc']);

    $(document).on('change', '.aae-filter', function() {
        var table = $('.table-aae-cars');

        if (table.length && $.fn.DataTable.isDataTable(table)) {
            table.DataTable().ajax.reload();
        }
    });
});
</script>
</body>

</html>
