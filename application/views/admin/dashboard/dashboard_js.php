<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
var user_dashboard_visibility = <?php echo $user_dashboard_visibility; ?>  || [];
$(function() {
    $("[data-container]").sortable({
        connectWith: "[data-container]",
        helper: 'clone',
        handle: '.widget-dragger',
        tolerance: 'pointer',
        forcePlaceholderSize: true,
        placeholder: 'placeholder-dashboard-widgets',
        start: function(event, ui) {
            $("body,#wrapper").addClass('noscroll');
            $('body').find('[data-container]').css('min-height', '20px');
        },
        stop: function(event, ui) {
            $("body,#wrapper").removeClass('noscroll');
            $('body').find('[data-container]').removeAttr('style');
        },
        update: function(event, ui) {
            if (this === ui.item.parent()[0]) {
                var data = {};
                $.each($("[data-container]"), function() {
                    var cId = $(this).attr('data-container');
                    data[cId] = $(this).sortable('toArray');
                    if (data[cId].length == 0) {
                        data[cId] = 'empty';
                    }
                });
                $.post(admin_url + 'staff/save_dashboard_widgets_order', data, "json");
            }
        }
    });

    // Read more for dashboard todo items
    $('.read-more').readmore({
        collapsedHeight: 150,
        moreLink: "<a href=\"#\"><?php echo _l('read_more'); ?></a>",
        lessLink: "<a href=\"#\"><?php echo _l('show_less'); ?></a>",
    });

    $('body').on('click', '#viewWidgetableArea', function(e) {
        e.preventDefault();

        if (!$(this).hasClass('preview')) {
            $(this).html("<?php echo _l('hide_widgetable_area'); ?>");
            $('[data-container]').append(
                '<div class="placeholder-dashboard-widgets pl-preview"></div>');
        } else {
            $(this).html("<?php echo _l('view_widgetable_area'); ?>");
            $('[data-container]').find('.pl-preview').remove();
        }

        $('[data-container]').toggleClass('preview-widgets');
        $(this).toggleClass('preview');
    });

    var $widgets = $('.widget');
    var widgetsOptionsHTML = '';
    widgetsOptionsHTML += '<div id="dashboard-options">';
    widgetsOptionsHTML +=
        "<div class=\"tw-flex tw-space-x-4 tw-items-center\"><h4 class='tw-font-medium tw-text-neutral-600 tw-text-lg'><i class='fa-regular fa-circle-question' data-toggle='tooltip' data-placement=\"bottom\" data-title=\"<?php echo _l('widgets_visibility_help_text'); ?>\"></i> <?php echo _l('widgets'); ?></h4><a href=\"<?php echo admin_url('staff/reset_dashboard'); ?>\" class=\"tw-text-sm\"><?php echo _l('reset_dashboard'); ?></a>";

    widgetsOptionsHTML +=
        ' <a href=\"#\" id="viewWidgetableArea" class=\"tw-text-sm\"><?php echo _l('view_widgetable_area'); ?></a></div>';

    $.each($widgets, function() {
        var widget = $(this);
        var widgetOptionsHTML = '';
        if (widget.data('name') && widget.html().trim().length > 0) {
            widgetOptionsHTML += '<div class="checkbox">';
            var wID = widget.attr('id');
            wID = wID.split('widget-');
            wID = wID[wID.length - 1];
            var checked = ' ';
            var db_result = $.grep(user_dashboard_visibility, function(e) {
                return e.id == wID;
            });
            if (db_result.length >= 0) {
                // no options saved or really visible
                if (typeof(db_result[0]) == 'undefined' || db_result[0]['visible'] == 1) {
                    checked = ' checked ';
                }
            }
            widgetOptionsHTML += '<input type="checkbox" class="widget-visibility" value="' + wID +
                '"' + checked + 'id="widget_option_' + wID + '" name="dashboard_widgets[' + wID + ']">';
            widgetOptionsHTML += '<label for="widget_option_' + wID + '">' + widget.data('name') +
                '</label>';
            widgetOptionsHTML += '</div>';
        }
        widgetsOptionsHTML += widgetOptionsHTML;
    });

    $('.screen-options-area').append(widgetsOptionsHTML);
    $('body').find('#dashboard-options input.widget-visibility').on('change', function() {
        if ($(this).prop('checked') == false) {
            $('#widget-' + $(this).val()).addClass('hide');
        } else {
            $('#widget-' + $(this).val()).removeClass('hide');
        }

        var data = {};
        var options = $('#dashboard-options input[type="checkbox"]').map(function() {
            return {
                id: this.value,
                visible: this.checked ? 1 : 0
            };
        }).get();

        data.widgets = options;
        /*
                if (typeof(csrfData) !== 'undefined') {
                    data[csrfData['token_name']] = csrfData['hash'];
                }
        */
        $.post(admin_url + 'staff/save_dashboard_widgets_visibility', data).fail(function(data) {
            // Demo usage, prevent multiple alerts
            if ($('body').find('.float-alert').length == 0) {
                alert_float('danger', data.responseText);
            }
        });
    });

    function makeDoughnutChart(elementId, chartData, clickHandler) {
        var el = document.getElementById(elementId);
        if (!el) return;
        var transformed = fennecTransformChartJsData(chartData, 'donut');
        var redirectUrls = (clickHandler && chartData && chartData.datasets && chartData.datasets[0]) ? (chartData.datasets[0].statusLink || []) : [];
        fennecChart(el, {
            chart: {
                height: 250,
                type: 'donut',
                events: clickHandler ? {
                    dataPointSelection: function(event, chartContext, config) {
                        var link = redirectUrls[config.dataPointIndex];
                        if (link) {
                            window.location.href = link;
                        }
                    }
                } : {}
            },
            series: transformed.series,
            labels: transformed.labels,
            colors: transformed.colors,
            plotOptions: {
                pie: {
                    expandOnClick: true,
                    donut: { size: '65%' }
                }
            },
            dataLabels: { enabled: false },
            legend: { position: 'bottom' }
        });
    }

    if (document.getElementById('tickets-awaiting-reply-by-department')) {
        makeDoughnutChart('tickets-awaiting-reply-by-department', <?php echo $tickets_awaiting_reply_by_department; ?>);
    }
    if (document.getElementById('tickets-awaiting-reply-by-status')) {
        makeDoughnutChart('tickets-awaiting-reply-by-status', <?php echo $tickets_reply_by_status; ?>, true);
    }
    if (document.getElementById('leads_status_stats')) {
        makeDoughnutChart('leads_status_stats', <?php echo $leads_status_stats; ?>, true);
    }
    if (document.getElementById('projects_status_stats')) {
        makeDoughnutChart('projects_status_stats', <?php echo $projects_status_stats; ?>, true);
    }

    fix_user_data_widget_tabs();
    $(window).on('resize', function() {
        $('.horizontal-scrollable-tabs ul.nav-tabs-horizontal').removeAttr('style');
        fix_user_data_widget_tabs();
    });
    // Payments statistics
    init_weekly_payment_statistics(<?php echo $weekly_payment_stats; ?>);

    $('select[name="currency"]').on('change', function() {
        let $activeChart = $('#Payment-chart-name').data('active-chart');

        var payChartEl = document.getElementById('payment-statistics');
        if (payChartEl && payChartEl._apexChart) {
            payChartEl._apexChart.destroy();
        }

        if ($activeChart == 'weekly') {
            init_weekly_payment_statistics();
        } else if ($activeChart == 'monthly') {
            init_monthly_payment_statistics();
        }

    });
});

function fix_user_data_widget_tabs() {
    if ((app.browser != 'firefox' &&
            isRTL == 'false' && is_mobile()) || (app.browser == 'firefox' &&
            isRTL == 'false' && is_mobile())) {
        $('.horizontal-scrollable-tabs ul.nav-tabs-horizontal').css('margin-bottom', '26px');
    }
}

function init_weekly_payment_statistics(data) {
    var el = document.getElementById('payment-statistics');
    if (!el) return;

    if (el._apexChart) {
        el._apexChart.destroy();
    }

    if (typeof(data) == 'undefined') {
        var currency = $('select[name="currency"]').val();
        $.get(admin_url + 'dashboard/weekly_payments_statistics/' + currency, function(response) {
            var transformed = fennecTransformChartJsData(response, 'bar');
            fennecChart(el, {
                chart: { type: 'bar' },
                series: transformed.series,
                xaxis: { categories: transformed.categories },
                yaxis: { min: 0 },
                dataLabels: { enabled: false },
                legend: { show: false }
            });
        }, 'json');
    } else {
        var transformed = fennecTransformChartJsData(data, 'bar');
        fennecChart(el, {
            chart: { type: 'bar' },
            series: transformed.series,
            xaxis: { categories: transformed.categories },
            yaxis: { min: 0 },
            dataLabels: { enabled: false },
            legend: { show: false }
        });
    }
}

function init_monthly_payment_statistics() {
    var el = document.getElementById('payment-statistics');
    if (!el) return;
    if (el._apexChart) {
        el._apexChart.destroy();
    }
    var currency = $('select[name="currency"]').val();
    $.get(admin_url + 'dashboard/monthly_payments_statistics/' + currency, function(response) {
        var transformed = fennecTransformChartJsData(response, 'bar');
        fennecChart(el, {
            chart: { type: 'bar' },
            series: transformed.series,
            xaxis: { categories: transformed.categories },
            yaxis: { min: 0 },
            dataLabels: { enabled: false },
            legend: { show: false }
        });
    }, 'json');
}

function update_payment_statistics(el) {
    let type = $(el).data('type');
    let $chartNameWrapper = $('#Payment-chart-name');
    $chartNameWrapper.data('active-chart', type);
    $chartNameWrapper.text($(el).text());

    var payChartEl = document.getElementById('payment-statistics');
    if (payChartEl && payChartEl._apexChart) {
        payChartEl._apexChart.destroy();
        payChartEl._apexChart = null;
    }

    if (type == 'weekly') {
        init_weekly_payment_statistics();
    } else if (type == 'monthly') {
        init_monthly_payment_statistics();
    }

}

function update_tickets_report_table(el) {
    var $el = $(el);
    var type = $el.data('type')
    $('#tickets-report-mode-name').text($el.text())

    $('#tickets-report-table-wrapper').load(admin_url + 'dashboard/ticket_widget/' + type, function(data) {
        $('.table-ticket-reports').dataTable().fnDestroy()
        initDataTableInline('.table-ticket-reports')
    });
    return false
}
</script>
