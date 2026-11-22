<script>
	'use strict';
	staff_chart_by_age('staff_chart_by_age',<?php echo new_html_entity_decode($staff_chart_by_age); ?>, <?php echo json_encode(_l('hr_staff_chart_by_age')); ?>);
	staff_chart_by_age('staff_departments_chart',<?php echo new_html_entity_decode($staff_departments_chart); ?>, <?php echo json_encode(_l('hr_chart_by_department')); ?>);
	staff_chart_by_age('staff_chart_by_job_positions',<?php echo new_html_entity_decode($staff_chart_by_job_positions); ?>, <?php echo json_encode(_l('hr_chart_by_job_positions')); ?>);
	staff_chart_by_fluctuate_according_to_seniority('staff_chart_by_fluctuate_according_to_seniority', '', '');
	report_by_staffs('report_by_staffs', '', '');
	
	

	function staff_chart_by_age(id, value, title_c){
		var el = document.getElementById(id);
		if (!el) return;
		fennecChart(el, {
			chart: { type: 'bar' },
			title: { text: title_c },
			series: [{ data: value }],
			xaxis: { type: 'category' },
			colors: ['#119EFA', '#ef370dc7', '#15f34f', '#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4', '#50B432', '#0d91efc7', '#ED561B'],
			legend: { show: false },
			dataLabels: { enabled: true }
		});
	}

	function staff_chart_by_fluctuate_according_to_seniority(id, value, title_c){
    'use strict';

		var data = {};
		var sort_from='0';
		var months_report = ''; 

		requestGetJSON('hr_profile/get_chart_senior_staff/' + sort_from+'/'+months_report).done(function (response) {
			var el = document.getElementById(id);
			if (!el) return;
			fennecChart(el, {
				chart: { type: 'bar' },
				title: { text: '<?php echo _l('hr_chart_seniority_fluctuations'); ?>' },
				series: [{
					name: '<?php echo _l('hr_number_of_employees'); ?>',
					type: 'bar',
					data: response.data
				}, {
					name: '<?php echo _l('ratio'); ?>',
					type: 'line',
					data: response.data_ratio
				}],
				xaxis: {
					categories: ['<= 1 <?php echo _l('months'); ?>', '<= 3 <?php echo _l('months'); ?>', '<= 6 <?php echo _l('months'); ?>', '<= 9 <?php echo _l('months'); ?>', '<= 12 <?php echo _l('months'); ?>', '> 12 <?php echo _l('months'); ?>']
				},
				colors: ['#119EFA', '#ef370dc7', '#15f34f', '#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4', '#50B432', '#0d91efc7', '#ED561B'],
				dataLabels: { enabled: true },
				stroke: { curve: 'smooth', width: 2 },
				yaxis: [
					{
						title: { text: '<?php echo _l('ratio'); ?>' },
						labels: { formatter: function(v) { return v + ' %'; } },
						opposite: false
					},
					{
						title: { text: '<?php echo _l('hr_number_of_employees'); ?>' },
						labels: { formatter: function(v) { return v + ''; } },
						opposite: true
					}
				]
			});
		});
	}


function report_by_staffs(id, value, title_c){
    'use strict';
	
	requestGetJSON('hr_profile/report_by_staffs').done(function (response) {
		var el = document.getElementById(id);
		if (!el) return;
		fennecChart(el, {
			chart: { type: 'bar' },
			title: { text: '<?php echo _l('employee_chart_by_month'); ?>' },
			series: [
				{ name: '<?php echo _l('hr_new_staff'); ?>', data: response.hr_new_staff },
				{ name: '<?php echo _l('hr_staff_are_working'); ?>', data: response.hr_staff_are_working },
				{ name: '<?php echo _l('hr_staff_quit'); ?>', data: response.hr_staff_quit }
			],
			xaxis: { categories: response.categories },
			colors: ['#119EFA', '#15f34f', '#ef370dc7', '#791db2d1', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4', '#50B432', '#0d91efc7', '#ED561B'],
			yaxis: { min: 0 },
			dataLabels: { enabled: true },
			legend: { position: 'bottom' }
		});
   });
}


var table_staff = $('table.table-table_staff');
$(function(){

	var StaffServerParams = {
		"hr_profile_deparment": "input[name='hr_profile_deparment']",
		"staff_role": "[name='staff_role[]']",
	};
	initDataTable(table_staff,admin_url + 'hr_profile/table_reception', '','', '');

	$('.table-table_staff').DataTable().on('draw', function() {
		var rows = $('.table-table_staff').find('tr');
		$.each(rows, function() {
			var td = $(this).find('td').eq(4);
			var percent = $(td).find('input[name="percent"]').val();

			$(td).find('.goal-progress').circleProgress({
				value: percent,
				size: 45,
				animation: false,
				fill: {
					gradient: ["#28b8da", "#059DC1"]
				}
			})
		})
	})

});



</script> 