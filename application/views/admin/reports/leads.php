<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-inline-flex tw-items-center tw-space-x-4 tw-mb-4">
                    <a href="<?php echo admin_url('reports/leads?type=staff'); ?>" class="btn btn-primary">
                        <?php echo _l('switch_to_general_report'); ?>
                    </a>
                    <p  class="tw-m-0" data-placement="bottom" data-toggle="tooltip"
                        data-title="<?php echo _l('leads_report_converted_notice'); ?>">
                        <i class="fa-regular fa-circle-question fa-lg"></i>
                    </p>
                </div>
            </div>
            <div class="col-md-6 animated fadeIn">
                <div class="panel_s">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?php echo _l('report_this_week_leads_conversions'); ?></h3>
                    </div>
                    <div class="panel-body">
                        <div id="leads-this-week"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 animated fadeIn">
                <div class="panel_s">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?php echo _l('report_leads_sources_conversions'); ?></h3>
                    </div>
                    <div class="panel-body">
                        <div id="leads-sources-report"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 animated fadeIn">
                <div class="panel_s">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?php echo _l('report_leads_monthly_conversions'); ?></h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <?php
                            echo '<select name="month" class="selectpicker" data-none-selected-text="' . _l('dropdown_non_selected_tex') . '">' . PHP_EOL;
                            for ($m = 1; $m <= 12; $m++) {
                                $_selected = '';
                                if ($m == date('m')) {
                                    $_selected = ' selected';
                                }
                                echo '  <option value="' . $m . '"' . $_selected . '>' . _l(date('F', mktime(0, 0, 0, $m, 1))) . '</option>' . PHP_EOL;
                            }
                      echo '</select>' . PHP_EOL;
                      ?>
                            </div>
                        </div>
                        <div class="relative" style="max-height:400px;">
                            <div id="leads-monthly"></div>
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
    function initMonthlyLeadsChart() {
        var mlEl = document.getElementById('leads-monthly');
        if (!mlEl) return;
        $.get(admin_url + 'reports/leads_monthly_report/' + $('select[name="month"]').val(), function(response) {
            if (mlEl._apexChart) {
                mlEl._apexChart.destroy();
            }
            var mld = fennecTransformChartJsData(response, 'bar');
            fennecChart(mlEl, {
                chart: { type: 'bar' },
                series: mld.series,
                xaxis: { categories: mld.categories },
                yaxis: { min: 0 },
                dataLabels: { enabled: false },
                legend: { show: false }
            });
        }, 'json');
    }
    initMonthlyLeadsChart();

    $('select[name="month"]').on('change', function() {
        initMonthlyLeadsChart();
    });

    // Leads this week (pie)
    var lwEl = document.getElementById('leads-this-week');
    if (lwEl) {
        var lwd = fennecTransformChartJsData(<?php echo $leads_this_week_report; ?>, 'pie');
        fennecChart(lwEl, {
            chart: { type: 'pie' },
            series: lwd.series,
            labels: lwd.labels,
            colors: lwd.colors,
            legend: { position: 'bottom' }
        });
    }

    // Leads sources report (bar)
    var lsEl = document.getElementById('leads-sources-report');
    if (lsEl) {
        var lsd = fennecTransformChartJsData(<?php echo $leads_sources_report; ?>, 'bar');
        fennecChart(lsEl, {
            chart: { type: 'bar' },
            series: lsd.series,
            xaxis: { categories: lsd.categories },
            legend: { show: false },
            yaxis: { min: 0 },
            dataLabels: { enabled: false }
        });
    }
});
</script>
</body>

</html>
