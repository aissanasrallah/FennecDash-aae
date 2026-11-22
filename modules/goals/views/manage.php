<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="row tw-mb-2">
                    <div class="col-md-6">
                        <?php if (staff_can('create', 'goals')) { ?>
                        <a href="<?php echo admin_url('goals/goal'); ?>" class="btn btn-primary">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo _l('new_goal'); ?>
                        </a>
                        <?php } ?>
                    </div>
                    <div class="col-md-6 text-right">
                        <div class="btn-group" role="group">
                            <a href="<?php echo admin_url('goals?period=all'); ?>" class="btn btn-<?php echo ($period_filter ?? 'all') === 'all' ? 'info' : 'default'; ?>"><?php echo _l('goal_filter_all'); ?></a>
                            <a href="<?php echo admin_url('goals?period=current'); ?>" class="btn btn-<?php echo ($period_filter ?? 'all') === 'current' ? 'info' : 'default'; ?>"><?php echo _l('goal_filter_current'); ?></a>
                            <a href="<?php echo admin_url('goals?period=historical'); ?>" class="btn btn-<?php echo ($period_filter ?? 'all') === 'historical' ? 'info' : 'default'; ?>"><?php echo _l('goal_filter_historical'); ?></a>
                        </div>
                    </div>
                </div>
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                            _l('goal_subject'),
                            _l('goal_assigned_staff'),
                            _l('goal_achievement'),
                            _l('goal_period_covered'),
                            _l('goal_recurrence_type'),
                            _l('goal_type'),
                            _l('goal_progress'),
                        ], 'goals'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    var tableUrl = '<?php echo admin_url('goals?period=' . e($period_filter ?? 'all')); ?>';
    initDataTable('.table-goals', tableUrl, [6], [6]);
    $('.table-goals').DataTable().on('draw', function() {
        var rows = $('.table-goals').find('tr');
        $.each(rows, function() {
            var td = $(this).find('td').eq(6);
            var percent = $(td).find('input[name="percent"]').val();
            if (percent !== undefined) {
                $(td).find('.goal-progress').circleProgress({
                    value: percent,
                    size: 45,
                    animation: false,
                    fill: {
                        gradient: ["#28b8da", "#059DC1"]
                    }
                });
            }
        });
    });
});
</script>
</body>

</html>
