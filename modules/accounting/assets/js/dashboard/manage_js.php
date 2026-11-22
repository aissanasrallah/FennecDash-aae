<script type="text/javascript">
var date_filter;
(function($) {
	"use strict";
    $( document ).ready(function() {
        dashboard_custom_view('last_30_days',"<?php echo _l('last_30_days'); ?>",'last_30_days');
    });
})(jQuery);

// Sets table filters dropdown to active
function dashboard_do_filter_active(value, parent_selector) {
    "use strict";
    if (value !== '' && typeof(value) != 'undefined') {

        $('[data-cview="all"]').parents('li').removeClass('active');
        var selector = $('[data-cview="' + value + '"]');
        if (typeof(parent_selector) != 'undefined') {
            selector = $(parent_selector + ' [data-cview="' + value + '"]');
        }
        var parent = selector.parents('li');
        if (parent.hasClass('filter-group')) {
            var group = parent.data('filter-group');
            $('[data-filter-group="' + group + '"]').not(parent).removeClass('active');
            $.each($('[data-filter-group="' + group + '"]').not(parent), function() {
                $('input[name="' + $(this).find('a').attr('data-cview') + '"]').val('');
            });
        }
        if (!parent.not('.dropdown-submenu').hasClass('active')) {
            parent.addClass('active');

        }
        return value;
    } else {
        $('._filters input').val('');
        $('._filter_data li.active').removeClass('active');
        $('[data-cview="all"]').parents('li').addClass('active');
        return "";
    }
}

// Datatables custom view will fill input with the value
function dashboard_custom_view(value, $lang, custom_input_name, clear_other_filters) {
    "use strict";

    $('.tab_currency_default').addClass('active');
    $('.tab_non_currency_default').removeClass('active');

    date_filter = value;

    //boxloading
    $('#bank_accounts').html('<div class="loader"></div>');
    $('#convert_status').html('<div class="loader"></div>');
    $('#profit_and_loss').html('<div class="loader"></div>');
    $('#expenses_chart').html('<div class="loader"></div>');
    $('#income_chart').html('<div class="loader"></div>');
    $('#sales_chart').html('<div class="loader"></div>');

    $('#btn_filter').html('<i class="fa fa-filter" aria-hidden="true"></i> '+$lang);

    //show box loading
    
    var name = typeof(custom_input_name) == 'undefined' ? 'custom_view' : custom_input_name;
    if (typeof(clear_other_filters) != 'undefined') {
        var filters = $('._filter_data li.active').not('.clear-all-prevent');
        filters.removeClass('active');
        $.each(filters, function() {
            var input_name = $(this).find('a').attr('data-cview');
            $('._filters input[name="' + input_name + '"]').val('');
        });
    }
    var _cinput = dashboard_do_filter_active(name);
   
    requestGet('accounting/get_data_dashboard?type=profit_and_loss&date_filter=' + value).done(function(response) {
        response = JSON.parse(response);

        $('#bank_accounts').html(response.bank_accounts);
        $('#convert_status').html(response.convert_status);

        var pnlEl = document.getElementById('profit_and_loss');
        if (pnlEl) {
            pnlEl.innerHTML = '';
            fennecChart(pnlEl, {
                chart: { type: 'bar' },
                title: { text: '<?php echo _l('profit_and_loss'); ?>' },
                series: [{ data: response.profit_and_loss_chart }],
                xaxis: {
                    categories: ['<?php echo _l('acc_net_income'); ?>', '<?php echo _l('acc_income'); ?>', '<?php echo _l('expenses'); ?>']
                },
                dataLabels: { enabled: true },
                legend: { show: false },
                colors: ['#119EFA', '#84c529', '#626f80'],
                yaxis: {
                    title: { text: '<?php echo new_html_entity_decode($currency->name); ?>' }
                }
            });
        }

    //hide boxloading
    $('#box-loading').html('');
    });
    
    requestGet('accounting/get_data_dashboard?type=convert_status&date_filter=' + value).done(function(response) {
        response = JSON.parse(response);

        $('#convert_status').html(response.convert_status);

        //hide boxloading
        $('#box-loading').html('');
    });

    requestGet('accounting/get_data_dashboard?type=bank_accounts&date_filter=' + value).done(function(response) {
        response = JSON.parse(response);

        $('#bank_accounts').html(response.bank_accounts);
        $('#convert_status').html(response.convert_status);

        //hide boxloading
        $('#box-loading').html('');
    });

    requestGet('accounting/get_data_dashboard?type=income_chart&date_filter=' + value).done(function(response) {
        response = JSON.parse(response);

        var incEl = document.getElementById('income_chart');
        if (incEl) {
            fennecChart(incEl, {
                chart: { type: 'bar', stacked: true },
                title: { text: '<?php echo _l("acc_income"); ?>' },
                series: [{
                    name: 'Total',
                    data: response.income_chart.data
                }],
                xaxis: { categories: response.income_chart.category },
                colors: ['#626f80', '#ef370dc7', '#84c529', '#119EFA'],
                yaxis: { min: 0 },
                dataLabels: { enabled: true },
                legend: { show: false }
            });
        }
    });

    requestGet('accounting/get_data_dashboard?type=expenses_chart&date_filter=' + value).done(function(response) {
        response = JSON.parse(response);

        var expEl = document.getElementById('expenses_chart');
        if (expEl) {
            var series = response.expenses_chart.map(function(item) { return item.y; });
            var labels = response.expenses_chart.map(function(item) { return item.name; });
            fennecChart(expEl, {
                chart: { type: 'pie' },
                title: { text: '<?php echo _l("expense_account_overview"); ?>' },
                series: series,
                labels: labels,
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        var item = response.expenses_chart[opts.seriesIndex];
                        return item.name + ': ' + item.amount.toFixed(2) + ' <?php echo new_html_entity_decode($currency->name); ?>';
                    }
                },
                tooltip: {
                    y: { formatter: function(val) { return val.toFixed(2) + ' <?php echo new_html_entity_decode($currency->name); ?>'; } }
                }
            });
        }

    //hide boxloading
    $('#box-loading').html('');
    });

    requestGet('accounting/get_data_dashboard?type=sales_chart&date_filter=' + value).done(function(response) {
        response = JSON.parse(response);

    var salesEl = document.getElementById('sales_chart');
    if (salesEl) {
        fennecChart(salesEl, {
            chart: { type: 'line' },
            title: { text: '<?php echo _l("cash_flow"); ?>' },
            series: response.sales_chart.data,
            xaxis: { categories: response.sales_chart.categories },
            colors: ['#99ff66', '#84c529', '#ffcc99', '#ef370dc7'],
            dataLabels: { enabled: true },
            legend: { position: 'bottom' }
        });
    }

    //hide boxloading
    $('#box-loading').html('');
    });
}

function change_currency_convert_status(currency){
    "use strict";
    //show box loading
    var html = '';
      html += '<div class="Box">';
      html += '<span>';
      html += '<span></span>';
      html += '</span>';
      html += '</div>';
      $('#box-loading').html(html);

    requestGet('accounting/get_data_convert_status_dashboard?date_filter=' + date_filter+'&currency=' + currency).done(function(response) {
        response = JSON.parse(response);

        $('#convert_status').html(response.convert_status);
        //hide boxloading
        $('#box-loading').html('');
    });
}

function change_currency_income_chart(currency){
    "use strict";
    //show box loading
    var html = '';
      html += '<div class="Box">';
      html += '<span>';
      html += '<span></span>';
      html += '</span>';
      html += '</div>';
      $('#box-loading').html(html);
    requestGet('accounting/get_data_income_chart?date_filter=' + date_filter+'&currency=' + currency).done(function(response) {
        response = JSON.parse(response);

        var incEl = document.getElementById('income_chart');
        if (incEl) {
            if (incEl._apexChart) { incEl._apexChart.destroy(); }
            fennecChart(incEl, {
                chart: { type: 'bar', stacked: true },
                title: { text: '<?php echo _l("acc_income"); ?>' },
                series: [{
                    name: 'Total',
                    data: response.income_chart.data
                }],
                xaxis: { categories: response.income_chart.category },
                colors: ['#626f80', '#ef370dc7', '#84c529', '#119EFA'],
                yaxis: { min: 0 },
                dataLabels: { enabled: true },
                legend: { show: false }
            });
        }

        //hide boxloading
        $('#box-loading').html('');
    });
}

function change_currency_sales_chart(currency){
    "use strict";
    //show box loading
    var html = '';
      html += '<div class="Box">';
      html += '<span>';
      html += '<span></span>';
      html += '</span>';
      html += '</div>';
      $('#box-loading').html(html);
    requestGet('accounting/get_data_sales_chart?date_filter=' + date_filter+'&currency=' + currency).done(function(response) {
        response = JSON.parse(response);

        var salesEl = document.getElementById('sales_chart');
        if (salesEl) {
            if (salesEl._apexChart) { salesEl._apexChart.destroy(); }
            fennecChart(salesEl, {
                chart: { type: 'line' },
                title: { text: '<?php echo _l("cash_flow"); ?>' },
                series: response.sales_chart.data,
                xaxis: { categories: response.sales_chart.categories },
                colors: ['#99ff66', '#84c529', '#ffcc99', '#ef370dc7'],
                dataLabels: { enabled: true },
                legend: { position: 'bottom' }
            });
        }

        //hide boxloading
        $('#box-loading').html('');
    });
}


</script>