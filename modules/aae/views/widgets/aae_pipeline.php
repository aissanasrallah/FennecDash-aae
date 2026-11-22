<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI = &get_instance();
$CI->load->model('aae/aae_order_model');

$pipeline = $CI->aae_order_model->get_dashboard_pipeline();
$total    = (int) $pipeline['total'];

$tiles = [
    [
        'label'   => _l('aae_dashboard_active_orders'),
        'value'   => (int) $pipeline['active'],
        'percent' => $total > 0 ? round(($pipeline['active'] * 100) / $total, 2) : 0,
        'color'   => 'progress-bar-info',
        'path'    => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z',
    ],
    [
        'label'   => _l('aae_dashboard_quoting'),
        'value'   => (int) $pipeline['quoting'],
        'percent' => $total > 0 ? round(($pipeline['quoting'] * 100) / $total, 2) : 0,
        'color'   => 'progress-bar-warning',
        'path'    => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
    ],
    [
        'label'   => _l('aae_dashboard_in_transit'),
        'value'   => (int) $pipeline['shipping'],
        'percent' => $total > 0 ? round(($pipeline['shipping'] * 100) / $total, 2) : 0,
        'color'   => 'progress-bar-default',
        'path'    => 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
    ],
    [
        'label'   => _l('aae_dashboard_completed'),
        'value'   => (int) $pipeline['completed'],
        'percent' => $total > 0 ? round(($pipeline['completed'] * 100) / $total, 2) : 0,
        'color'   => 'progress-bar-success',
        'path'    => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ],
];
?>
<div class="widget" id="widget-<?php echo create_widget_id(); ?>"
    data-name="<?php echo e(_l('aae_dashboard_pipeline')); ?>">
    <div class="panel_s">
        <div class="panel-body padding-10">
            <div class="widget-dragger"></div>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-1.5">
                <p class="tw-font-semibold tw-flex tw-items-center tw-mb-0 tw-gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="tw-w-6 tw-h-6 tw-text-neutral-500">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                    </svg>
                    <span class="tw-text-neutral-700"><?php echo e(_l('aae_dashboard_pipeline')); ?></span>
                </p>
                <a href="<?php echo admin_url('aae/aae_order'); ?>">
                    <?php echo e(_l('home_widget_view_all')); ?>
                </a>
            </div>

            <hr class="-tw-mx-3 tw-mt-2 tw-mb-4">

            <?php if ($total === 0) { ?>
            <div class="text-center tw-p-4 tw-text-neutral-500">
                <i class="fa-regular fa-folder-open fa-2x" aria-hidden="true"></i>
                <p class="tw-mt-4 tw-mb-0"><?php echo e(_l('aae_dashboard_no_orders')); ?></p>
            </div>
            <?php } else { ?>
            <div class="row">
                <?php foreach ($tiles as $tile) { ?>
                <div class="col-xs-12 col-sm-6 tw-mb-4">
                    <div class="tw-flex tw-items-center tw-gap-2 tw-text-neutral-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="tw-w-5 tw-h-5 tw-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo e($tile['path']); ?>" />
                        </svg>
                        <span class="tw-truncate"><?php echo e($tile['label']); ?></span>
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-1 tw-mt-1">
                        <span
                            class="tw-text-2xl tw-font-semibold tw-text-neutral-800"><?php echo e($tile['value']); ?></span>
                        <span class="tw-text-sm tw-text-neutral-400">/ <?php echo e($total); ?></span>
                    </div>
                    <div class="progress progress-bar-mini tw-mb-0 tw-mt-2">
                        <div class="progress-bar <?php echo $tile['color']; ?> no-percent-text not-dynamic"
                            role="progressbar" aria-valuenow="<?php echo e($tile['percent']); ?>" aria-valuemin="0"
                            aria-valuemax="100" style="width: <?php echo e($tile['percent']); ?>%"
                            data-percent="<?php echo e($tile['percent']); ?>"></div>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
    </div>
</div>
