<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI = &get_instance();
$CI->load->model('aae/aae_order_model');

if (! function_exists('aae_format_money')) {
    $CI->load->helper('aae/aae');
}

$financials = $CI->aae_order_model->get_dashboard_financials();
$vehicles   = $CI->aae_order_model->count_active_order_vehicles();

$tiles = [
    [
        'label' => _l('aae_dashboard_order_value'),
        'value' => aae_format_money($financials['order_value']),
        'hint'  => $vehicles . ' ' . _l('aae_dashboard_vehicles'),
        'tone'  => 'tw-text-neutral-800',
        'path'  => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
    ],
    [
        'label' => _l('aae_dashboard_receivables'),
        'value' => aae_format_money($financials['receivables']),
        'hint'  => _l('aae_dashboard_receivables_hint'),
        'tone'  => 'tw-text-warning-600',
        'path'  => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ],
    [
        'label' => _l('aae_dashboard_supplier_due'),
        'value' => aae_format_money($financials['supplier_due']),
        'hint'  => _l('aae_dashboard_supplier_due_hint'),
        'tone'  => 'tw-text-danger-600',
        'path'  => 'M12 19.5v-15m0 0l-6.75 6.75M12 4.5l6.75 6.75',
    ],
    [
        'label' => _l('aae_dashboard_expected_margin'),
        'value' => aae_format_money($financials['expected_margin']),
        'hint'  => _l('aae_dashboard_expected_margin_hint'),
        'tone'  => 'tw-text-success-600',
        'path'  => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
    ],
];
?>
<div class="widget" id="widget-<?php echo create_widget_id(); ?>"
    data-name="<?php echo e(_l('aae_dashboard_finance')); ?>">
    <div class="panel_s">
        <div class="panel-body padding-10">
            <div class="widget-dragger"></div>
            <div class="tw-flex tw-justify-between tw-items-center tw-p-1.5">
                <p class="tw-font-semibold tw-flex tw-items-center tw-mb-0 tw-gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="tw-w-6 tw-h-6 tw-text-neutral-500">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                    <span class="tw-text-neutral-700"><?php echo e(_l('aae_dashboard_finance')); ?></span>
                </p>
                <a href="<?php echo admin_url('aae/aae_order'); ?>">
                    <?php echo e(_l('home_widget_view_all')); ?>
                </a>
            </div>

            <hr class="-tw-mx-3 tw-mt-2 tw-mb-4">

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
                    <div class="tw-mt-1 tw-text-lg tw-font-semibold <?php echo e($tile['tone']); ?> tw-truncate"
                        title="<?php echo e($tile['value']); ?>">
                        <?php echo e($tile['value']); ?>
                    </div>
                    <div class="tw-text-xs tw-text-neutral-400 tw-truncate">
                        <?php echo e($tile['hint']); ?>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
