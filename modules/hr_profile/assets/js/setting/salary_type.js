function toggleSalaryModeFields(mode) {
    'use strict';
    if (mode === 'percentage') {
        $('#fixed_salary_fields').addClass('hide');
        $('#percentage_salary_fields').removeClass('hide');
    } else {
        $('#percentage_salary_fields').addClass('hide');
        $('#fixed_salary_fields').removeClass('hide');
    }
}

function new_salary_form() {
    'use strict';
    $('#salary_form').modal('show');
    $('.edit-title').addClass('hide');
    $('.add-title').removeClass('hide');
    $('#additional_salary_form').html('');

    $('#salary_form input[name="form_name"]').val('');
    $('#salary_form input[name="salary_val"]').val('');
    $('#salary_form input[name="percentage_rate"]').val('');
    $('#salary_form input[name="min_achievement_pct"]').val('0');
    $('#salary_calc_mode').val('fixed');
    $('#revenue_attribution').val('individual');
    $('#salary_goal_id').selectpicker('val', '');
    toggleSalaryModeFields('fixed');
}

function edit_salary_form(invoker, id) {
    'use strict';
    var $el = $(invoker);
    var mode = $el.data('calc-mode') || 'fixed';
    $('#additional_salary_form').html('');
    $('#additional_salary_form').append(hidden_input('id', id));
    $('#salary_form input[name="form_name"]').val($el.data('name'));
    $('#salary_form input[name="salary_val"]').val($el.data('amount'));
    $('#salary_form input[name="percentage_rate"]').val($el.data('percentage-rate') || '');
    $('#salary_form input[name="min_achievement_pct"]').val($el.data('min-pct') || '0');
    $('#salary_calc_mode').val(mode);
    $('#revenue_attribution').val($el.data('attribution') || 'individual');
    $('#salary_goal_id').selectpicker('val', $el.data('goal-id') || '');
    $('#salary_form select[name="tax"]').val($el.data('taxable')).change();
    toggleSalaryModeFields(mode);
    $('#salary_form').modal('show');
    $('.add-title').addClass('hide');
    $('.edit-title').removeClass('hide');
}

function showCommissionBreakdown(data) {
    'use strict';
    if (!data || typeof data !== 'object') {
        $('#commission_breakdown_body').html('<p class="text-muted">Aucun détail disponible.</p>');
        $('#commission_breakdown_modal').modal('show');
        return;
    }
    var html = '<div class="well well-sm">';
    html += '<strong>Objectif :</strong> ' + (data.goal_subject || '') + ' (' + (data.period_label || '') + ')<br>';
    html += '<strong>Base :</strong> Net facturé HT (Factures hors annulées/brouillons − Avoirs HT)<br>';
    html += '<strong>Attribution :</strong> ' + (data.attribution_mode === 'shared' ? 'Partagée / Entreprise' : 'Individuelle par employé') + '<br>';
    html += '<strong>Formule :</strong> ' + Number(data.eligible_invoiced_ht || 0).toFixed(2) + ' × ' + Number(data.percentage_rate || 0).toFixed(2) + '% = <strong>' + Number(data.commission_amount || 0).toFixed(2) + ' DA</strong>';
    html += '</div>';

    if (Array.isArray(data.invoices) && data.invoices.length) {
        html += '<h5>Factures éligibles incluses (' + data.invoices.length + ')</h5><table class="table table-condensed table-bordered"><thead><tr><th>N°</th><th>Date</th><th class="text-right">HT</th><th class="text-right">TTC</th></tr></thead><tbody>';
        data.invoices.forEach(function (inv) {
            html += '<tr><td>' + (inv.formatted_number || ('#' + inv.id)) + '</td><td>' + (inv.date || '') + '</td><td class="text-right">' + Number(inv.subtotal || 0).toFixed(2) + '</td><td class="text-right">' + Number(inv.total || 0).toFixed(2) + '</td></tr>';
        });
        html += '</tbody></table>';
    }
    if (Array.isArray(data.credit_notes) && data.credit_notes.length) {
        html += '<h5>Avoirs déduits (' + data.credit_notes.length + ')</h5><table class="table table-condensed table-bordered"><thead><tr><th>N°</th><th>Date</th><th class="text-right">HT déduit</th></tr></thead><tbody>';
        data.credit_notes.forEach(function (cn) {
            html += '<tr><td>' + (cn.formatted_number || ('#' + cn.id)) + '</td><td>' + (cn.date || '') + '</td><td class="text-right">-' + Number(cn.subtotal || 0).toFixed(2) + '</td></tr>';
        });
        html += '</tbody></table>';
    }
    $('#commission_breakdown_body').html(html);
    $('#commission_breakdown_modal').modal('show');
}

$(function () {
    'use strict';
    $('#salary_calc_mode').on('change', function () {
        toggleSalaryModeFields($(this).val());
    });

    $('#comm_salary_form_id').on('change', function () {
        var goalId = $(this).find('option:selected').data('goal-id');
        var $perSelect = $('#comm_goal_period_id');
        if (!goalId) {
            $perSelect.html('<option value="">— Sélectionner d\'abord une règle (%) —</option>');
            return;
        }
        $perSelect.html('<option value="">Chargement des périodes...</option>');
        $.getJSON(admin_url + 'goals/periods_json/' + goalId, function (resp) {
            var opts = '';
            if (resp && Array.isArray(resp.periods) && resp.periods.length) {
                resp.periods.forEach(function (p) {
                    opts += '<option value="' + p.id + '">' + p.period_label + ' (' + p.period_start + ' → ' + p.period_end + ')</option>';
                });
            } else {
                opts = '<option value="">Aucune période générée pour cet objectif</option>';
            }
            $perSelect.html(opts);
        });
    });
});
