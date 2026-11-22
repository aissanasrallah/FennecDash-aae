<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $isRTL = (is_rtl() ? 'true' : 'false'); ?>

<!DOCTYPE html>
<html lang="<?= e($locale); ?>"
    dir="<?= ($isRTL == 'true') ? 'rtl' : 'ltr' ?>">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

    <script>
        (function() {
            var theme = localStorage.getItem('fennec-theme');
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
            }
        })();
    </script>

    <title>
        <?= $title ?? get_option('companyname'); ?>
    </title>

    <?= app_compile_css(); ?>
    <?php
        $fennecCssFile = file_exists(FCPATH . 'assets/css/fennec.min.css') ? 'assets/css/fennec.min.css' : 'assets/css/fennec.css';
        $fennecIconsFile = file_exists(FCPATH . 'assets/css/fennec-icons.min.css') ? 'assets/css/fennec-icons.min.css' : 'assets/css/fennec-icons.css';
        $fennecCssVer = get_app_version() . '.' . (file_exists(FCPATH . 'assets/css/fennec.css') ? filemtime(FCPATH . 'assets/css/fennec.css') : '0');
    ?>
    <link href="<?= base_url('assets/css/tabler/tabler-icons.min.css?v=' . get_app_version()); ?>" rel="stylesheet">
    <link href="<?= base_url($fennecCssFile . '?v=' . $fennecCssVer); ?>" rel="stylesheet">
    <link href="<?= base_url($fennecIconsFile . '?v=' . get_app_version()); ?>" rel="stylesheet">
    <?php render_admin_js_variables(); ?>

    <script>
        var totalUnreadNotifications = <?= e($current_user->total_unread_notifications); ?> ,
            proposalsTemplates = <?= json_encode(get_proposal_templates()); ?> ,
            contractsTemplates = <?= json_encode(get_contract_templates()); ?> ,
            billingAndShippingFields = ['billing_street', 'billing_city', 'billing_state', 'billing_zip',
                'billing_country',
                'shipping_street', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'
            ],
            isRTL = '<?= e($isRTL); ?>',
            taskid, taskTrackingStatsData, taskAttachmentDropzone, taskCommentAttachmentDropzone, newsFeedDropzone,
            expensePreviewDropzone, taskTrackingChart, cfh_popover_templates = {},
            _table_api;
    </script>
    <?php app_admin_head(); ?>
</head>

<body <?= admin_body_class($bodyclass ?? ''); ?>>
    <?php hooks()->do_action('after_body_start'); ?>