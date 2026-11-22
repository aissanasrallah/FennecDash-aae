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
                        <a href="<?php echo admin_url('aae/catalogue/car'); ?>" class="btn btn-primary">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo _l('aae_new_car'); ?>
                        </a>
                        <?php } ?>
                        <?php if (staff_can('import', 'aae')) { ?>
                        <a href="<?php echo admin_url('aae/catalogue/import'); ?>" class="btn btn-default">
                            <i class="fa-solid fa-file-import tw-mr-1"></i>
                            <?php echo _l('aae_import'); ?>
                        </a>
                        <?php } ?>
                        <a href="<?php echo admin_url('aae/catalogue/export'); ?>" class="btn btn-default">
                            <i class="fa-solid fa-file-export tw-mr-1"></i>
                            <?php echo _l('aae_export_csv'); ?>
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
                        <div class="aae-stat-icon aae-icon-green"><i class="fa-solid fa-layer-group"></i></div>
                        <div>
                            <div class="aae-stat-value" data-aae-stat="brands"><?php echo (int) $stats['brands']; ?></div>
                            <div class="aae-stat-label"><?php echo _l('aae_total_brands'); ?></div>
                        </div>
                    </div>
                    <div class="aae-stat">
                        <div class="aae-stat-icon aae-icon-violet"><i class="fa-solid fa-sack-dollar"></i></div>
                        <div>
                            <div class="aae-stat-value aae-stat-value-money" data-aae-stat="avg_price_eur"><?php echo aae_format_money($stats['avg_price_eur'], 'EUR'); ?></div>
                            <div class="aae-stat-label"><?php echo _l('aae_avg_price_eur'); ?></div>
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body panel-table-full">
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
                                <label for="aae-filter-model"><?php echo _l('aae_model'); ?></label>
                                <select id="aae-filter-model" class="form-control aae-filter" disabled
                                    data-placeholder-brand="<?php echo e(_l('aae_select_brand_first')); ?>"
                                    data-placeholder-all="<?php echo e(_l('aae_all_models')); ?>">
                                    <option value=""><?php echo _l('aae_select_brand_first'); ?></option>
                                </select>
                            </div>
                            <div class="col-sm-4 col-md-3">
                                <label>&nbsp;</label>
                                <a href="<?php echo admin_url('aae/catalogue'); ?>" class="btn btn-default btn-block">
                                    <i class="fa-solid fa-rotate-left tw-mr-1"></i>
                                    <?php echo _l('aae_reset_filters'); ?>
                                </a>
                            </div>
                        </div>

                        <?php render_datatable([
                            ['name' => _l('aae_id'), 'th_attrs' => ['data-custom-field' => '1', 'data-type' => 'number']],
                            _l('aae_brand'),
                            _l('aae_model'),
                            _l('aae_engine'),
                            ['name' => _l('aae_year'), 'th_attrs' => ['data-custom-field' => '1', 'data-type' => 'number']],
                            ['name' => _l('aae_price_eur'), 'th_attrs' => ['data-custom-field' => '1', 'data-type' => 'number']],
                            ['name' => _l('aae_total_ttc'), 'th_attrs' => ['data-custom-field' => '1', 'data-type' => 'number']],
                            ['name' => _l('aae_price_dzd'), 'th_attrs' => ['data-custom-field' => '1', 'data-type' => 'number']],
                            ['name' => _l('aae_customs_column'), 'th_attrs' => ['data-custom-field' => '1', 'data-type' => 'number']],
                            ['name' => _l('aae_orders_count'), 'th_attrs' => ['data-custom-field' => '1', 'data-type' => 'number']],
                            _l('aae_options'),
                        ], 'aae-catalogue', [''], ['data-last-order-identifier' => 'aae-catalogue']); ?>
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
    initDataTable('.table-aae-catalogue', window.location.href, [10], [10], {
        brand: '#aae-filter-brand',
        model: '#aae-filter-model'
    }, [0, 'desc']);

    $(document).on('change', '.aae-filter', function() {
        var table = $('.table-aae-catalogue');
        if (table.length && $.fn.DataTable.isDataTable(table)) {
            table.DataTable().ajax.reload();
        }
    });
});
</script>
</body>
</html>