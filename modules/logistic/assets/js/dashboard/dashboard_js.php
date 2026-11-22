<script>    
(function($) {
    "use strict";
    rec_chart_by_status('package_by_status',<?php echo html_entity_decode($package_by_status); ?>, <?php echo json_encode(_l('package_by_status')); ?>);
    line_chart_init('package_sales_graph', <?php echo html_entity_decode($package_sales_graph); ?>, <?php echo json_encode(_l('package_sales_graph')); ?>);
    rec_chart_by_status('shipping_by_status',<?php echo html_entity_decode($shipping_by_status); ?>, <?php echo json_encode(_l('shipping_by_status')); ?>);
    column_chart_init('shipping_sales_graph', <?php echo html_entity_decode($shipping_sales_graph); ?>, <?php echo json_encode(_l('shipping_sales_graph')); ?>);

    rec_chart_by_status('consolidated_by_status',<?php echo html_entity_decode($consolidated_by_status); ?>, <?php echo json_encode(_l('consolidated_by_status')); ?>);
    line_chart_init('consolidated_sales_graph', <?php echo html_entity_decode($consolidated_sales_graph); ?>, <?php echo json_encode(_l('consolidated_sales_graph')); ?>);
    function rec_chart_by_status(id, value, title_c){
        "use strict";
        var el = document.getElementById(id);
        if (!el) return;
        var seriesData = value.map(function(item) { return item.y; });
        var labels = value.map(function(item) { return item.name; });
        var colors = ['#119EFA', '#ef370dc7', '#15f34f', '#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4', '#50B432', '#0d91efc7', '#ED561B'];
        fennecChart(el, {
            chart: {
                type: 'donut',
                events: {
                    dataPointSelection: function(e, ctx, config) {
                        var item = value[config.seriesIndex];
                        if (item && item.statusLink) {
                            window.location.href = item.statusLink;
                        }
                    }
                }
            },
            title: { text: title_c },
            series: seriesData,
            labels: labels,
            colors: colors,
            legend: { position: 'bottom' },
            dataLabels: { enabled: false },
            plotOptions: {
                pie: {
                    expandOnClick: true,
                    donut: { size: '65%' }
                }
            }
        });
    }



    function line_chart_init(id, value, title_c){
        "use strict";
        var el = document.getElementById(id);
        if (!el) return;
        fennecChart(el, {
            chart: { type: 'line' },
            title: { text: title_c },
            colors: ['#119EFA', '#ef370dc7', '#15f34f', '#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4', '#50B432', '#0d91efc7', '#ED561B'],
            series: [{ name: value.name, data: value.data }],
            xaxis: {
                categories: ['<?php echo _l('month_1') ?>', '<?php echo _l('month_2') ?>', '<?php echo _l('month_3') ?>', '<?php echo _l('month_4') ?>', '<?php echo _l('month_5') ?>', '<?php echo _l('month_6') ?>', '<?php echo _l('month_7') ?>', '<?php echo _l('month_8') ?>', '<?php echo _l('month_9') ?>', '<?php echo _l('month_10') ?>', '<?php echo _l('month_11') ?>', '<?php echo _l('month_12') ?>']
            },
            yaxis: { min: 0, title: { text: value.name } },
            stroke: { curve: 'smooth', width: 2 },
            dataLabels: { enabled: true },
            legend: { show: false },
            tooltip: {
                y: { formatter: function(val) { return val.toFixed(0) + value.unit; } }
            }
        });
    }


    function column_chart_init(id, value, title_c){
        "use strict";
        var el = document.getElementById(id);
        if (!el) return;
        fennecChart(el, {
            chart: { type: 'bar' },
            title: { text: title_c },
            colors: ['#ef370dc7', '#15f34f', '#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4', '#50B432', '#0d91efc7', '#ED561B'],
            series: [{ name: value.name, data: value.data }],
            xaxis: {
                categories: ['<?php echo _l('month_1') ?>', '<?php echo _l('month_2') ?>', '<?php echo _l('month_3') ?>', '<?php echo _l('month_4') ?>', '<?php echo _l('month_5') ?>', '<?php echo _l('month_6') ?>', '<?php echo _l('month_7') ?>', '<?php echo _l('month_8') ?>', '<?php echo _l('month_9') ?>', '<?php echo _l('month_10') ?>', '<?php echo _l('month_11') ?>', '<?php echo _l('month_12') ?>']
            },
            yaxis: { min: 0, title: { text: value.name } },
            dataLabels: { enabled: true },
            legend: { show: false },
            tooltip: {
                y: { formatter: function(val) { return val.toFixed(0) + value.unit; } }
            }
        });
    }
})(jQuery);
</script>