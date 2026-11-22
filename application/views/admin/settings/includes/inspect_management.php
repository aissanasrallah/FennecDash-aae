<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="com-md-12">
    <?php render_yes_no_option('inspect_management_enabled', 'Enable Inspect Management', 'When enabled, right-click, F12, Ctrl+Shift+I/J, and Ctrl+U are blocked across protected areas'); ?>
</div>

<hr />

<div class="com-md-12">
    <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip" data-title="The alert message shown when someone tries to inspect the page. HTML and emojis supported."></i>
    <?= render_textarea('settings[inspect_management_alert_message]', 'Alert Message', get_option('inspect_management_alert_message')); ?>
</div>

<hr />

<div class="com-md-12">
    <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip" data-title="The message displayed in the browser console when inspection is attempted."></i>
    <?= render_textarea('settings[inspect_management_console_message]', 'Console Message', get_option('inspect_management_console_message')); ?>
</div>

<hr />

<div class="com-md-12">
    <?php render_yes_no_option('inspect_management_client_area', 'Protect Client Area', 'Also block inspect on customer portal pages (themes/fennec)'); ?>
</div>

<hr />

<div class="com-md-12">
    <?php render_yes_no_option('inspect_management_public_forms', 'Protect Public Forms', 'Also block inspect on public forms (ticket submission, lead forms, estimate requests)'); ?>
</div>

<?php hooks()->do_action('settings_inspect_management'); ?>
