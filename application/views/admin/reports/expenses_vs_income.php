<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php if (count($years) > 1 || (count($years) == 1 && $years[0] != date('Y'))) {
    ?>
                <select class="selectpicker tw-mb-4" name="expense_year"
                    onchange="change_expense_report_year(this.value);"
                    data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                    <?php foreach ($years as $year) {
        ?>
                    <option value="<?php echo e($year); ?>" <?php echo $year == $report_year ? 'selected' : ''; ?>>
                        <?php echo e($year); ?>
                    </option>
                    <?php
    } ?>
                </select>
                <?php
} ?>
                <div class="panel_s">
                    <div class="panel-body">

                        <p class="text-danger bold">
                            <?php echo _l('amount_display_in_base_currency'); ?>
                        </p>
                        <div class="relative" style="max-height:600px;">
                            <div id="report-expense-vs-income"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    init_currency(<?php echo e($base_currency->id); ?>, function() {
        var eviEl = document.getElementById('report-expense-vs-income');
        if (eviEl) {
            var evd = fennecTransformChartJsData(<?php echo $chart_expenses_vs_income_values; ?>, 'bar');
            fennecChart(eviEl, {
                chart: { type: 'bar' },
                series: evd.series,
                xaxis: { categories: evd.categories },
                yaxis: {
                    min: 0,
                    labels: {
                        formatter: function(value) { return format_money(value); }
                    }
                },
                dataLabels: { enabled: false },
                legend: { show: false },
                tooltip: {
                    y: {
                        formatter: function(value) { return format_money(value); }
                    }
                }
            });
        }
    });
});

function change_expense_report_year(year) {
    window.location.href = admin_url + 'reports/expenses_vs_income/' + year;
}
</script>
</body>

</html>