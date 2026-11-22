<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI = &get_instance();
$tokensTable = db_prefix() . 'api_tokens';
$auditTable = db_prefix() . 'api_audit_log';
$mcpInstalled = $CI->db->table_exists($tokensTable);
$activeTokenCount = 0;
$auditCount = 0;
$mcpTokens = [];

if ($mcpInstalled) {
    $activeTokenCount = (int) $CI->db->where('revoked_at IS NULL', null, false)->count_all_results($tokensTable);
    $mcpTokens = $CI->db
        ->select('id, label, token_prefix, risk_max, client, created_at, last_used_at, expires_at, revoked_at, request_count')
        ->order_by('revoked_at IS NULL', 'DESC', false)
        ->order_by('id', 'DESC')
        ->limit(10)
        ->get($tokensTable)
        ->result_array();
}

if ($CI->db->table_exists($auditTable)) {
    $auditCount = (int) $CI->db->count_all($auditTable);
}
?>

<div class="panel_s">
    <div class="panel-body">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
            <h4 class="bold no-margin">
                <i class="fa-solid fa-robot tw-mr-1"></i>
                <?= _l('fennec_mcp_integration_title'); ?>
            </h4>
            <?php if ($mcpInstalled) { ?>
                <span class="label label-success"><?= _l('fennec_mcp_status_connected'); ?></span>
            <?php } else { ?>
                <span class="label label-warning"><?= _l('fennec_mcp_status_not_installed'); ?></span>
            <?php } ?>
        </div>

        <div class="row mtop10">
            <div class="col-md-6">
                <p class="text-muted no-margin"><?= _l('fennec_mcp_endpoint_url'); ?></p>
                <p class="bold"><code><?= e(site_url('api/v1')); ?></code></p>
            </div>
            <div class="col-md-3">
                <p class="text-muted no-margin"><?= _l('fennec_mcp_active_tokens'); ?></p>
                <p class="bold"><?= (int) $activeTokenCount; ?></p>
            </div>
            <div class="col-md-3">
                <p class="text-muted no-margin"><?= _l('fennec_mcp_audit_events'); ?></p>
                <p class="bold"><?= (int) $auditCount; ?></p>
            </div>
        </div>

        <?php if ($mcpInstalled) { ?>
            <h5 class="bold mtop15"><?= _l('fennec_mcp_tokens_table_heading'); ?></h5>
            <?php if (! empty($mcpTokens)) { ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th><?= _l('fennec_mcp_token_label'); ?></th>
                                <th><?= _l('fennec_mcp_token_prefix'); ?></th>
                                <th><?= _l('fennec_mcp_token_client'); ?></th>
                                <th><?= _l('fennec_mcp_token_risk'); ?></th>
                                <th><?= _l('fennec_mcp_token_requests'); ?></th>
                                <th><?= _l('fennec_mcp_token_last_used'); ?></th>
                                <th><?= _l('status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mcpTokens as $token) { ?>
                                <tr>
                                    <td><?= e($token['label']); ?></td>
                                    <td><code><?= e($token['token_prefix']); ?></code></td>
                                    <td><?= e($token['client'] ?: '-'); ?></td>
                                    <td><span class="label label-default"><?= e($token['risk_max']); ?></span></td>
                                    <td><?= (int) $token['request_count']; ?></td>
                                    <td><?= ! empty($token['last_used_at']) ? e(_dt($token['last_used_at'])) : '-'; ?></td>
                                    <td>
                                        <?php if (empty($token['revoked_at'])) { ?>
                                            <span class="label label-success"><?= _l('active'); ?></span>
                                        <?php } else { ?>
                                            <span class="label label-danger"><?= _l('fennec_mcp_token_revoked'); ?></span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <p class="text-muted"><?= _l('fennec_mcp_no_tokens'); ?></p>
            <?php } ?>
        <?php } ?>
    </div>
</div>

<hr class="hr-panel-separator" />

<div class="com-md-12">
    <?= render_input('settings[openai_api_key]', _l('openai_api_key'), get_option('openai_api_key'), 'password'); ?>
</div>
<hr />

<div class="com-md-12">
    <?php
    $models = FennecCRM\Openai\OpenAiProvider::getModels();
    echo render_select('settings[openai_model]', $models, ['id', 'name'], 'openai_model', get_option('openai_model'), get_option('openai_use_fine_tuning') == '1' ? ['disabled' => true] : []);
    ?>

    <?= render_input('settings[openai_max_token]', _l('openai_max_token'), get_option('openai_max_token'), 'number'); ?>
</div>

<hr class="hr-panel-heading" />

<h4 class="bold">
    <?= _l('advanced_features'); ?>
</h4>

<div class="row">
    <div class="col-md-12">
        <a href="<?= admin_url('openai/finetuning'); ?>"
            class="btn btn-primary">
            <i class="fa fa-code-fork"></i>
            <?= _l('openai_fine_tuning'); ?>
        </a>
        <p class="mtop10">
            <?= _l('openai_fine_tuning_description'); ?>
        </p>
    </div>
</div>
