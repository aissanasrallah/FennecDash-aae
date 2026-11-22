/* =====================================================================
 * AAE Import Manager - JS module (admin)
 * Dependances Perfex admin: jQuery, DataTables (BS4), select2
 * Tout est defensive : le script ne casse rien si un element est absent.
 * ===================================================================== */
(function ($) {
    'use strict';

    if (typeof window.jQuery === 'undefined') { return; }

    var CSRF   = window.aaeCsrf || { name: '', hash: '' };
    var OPT    = window.aaeOptions || { eur_rate: 0.892, eur_fee_percent: 0.8, dzd_rate: 282, commission: 850 };

    function debounce(fn, ms) {
        var t = null;
        return function () {
            var args = arguments, self = this;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(self, args); }, ms || 350);
        };
    }

    function num(v) {
        var n = parseFloat(v);
        return isNaN(n) ? 0 : n;
    }

    function url(base, suffix) {
        return String(base || '').replace(/\/+$/, '') + '/' + String(suffix || '').replace(/^\/+/, '');
    }

    /* ==================================================================
     * Tableau catalogue server-side
     * ================================================================== */
    function initVehicleTable() {
        var $table = $('#aaeVehicleTable');
        if ($table.length === 0 || typeof $.fn.DataTable === 'undefined') { return null; }

        var table = $table.DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            pageLength: 25,
            order: [[0, 'desc']],
            columnDefs: [
                { targets: [5, 10, 11, 12, 13, 14], orderable: false }
            ],
            ajax: {
                url: $table.data('url'),
                type: 'POST',
                data: function (d) {
                    d.search    = $('#aaeSearch').val() || '';
                    d.f_status  = $('#aaeStatusFilter').val() || '';
                    d.f_marque  = $('#aaeMarqueFilter').val() || '';
                    if (CSRF.name) { d[CSRF.name] = CSRF.hash; }
                },
                dataSrc: function (json) {
                    // Le serveur renvoie un jeton CSRF a jour pour les prochains appels
                    if (json && json.csrf && json.csrf.hash) { CSRF.hash = json.csrf.hash; }
                    return json ? json.data : [];
                }
            }
        });

        var redraw = function () { table.draw(false); };

        $('#aaeStatusFilter, #aaeMarqueFilter').on('change', redraw);
        $('#aaeSearch').on('input', debounce(redraw, 400));
        $('#aaeResetFilters').on('click', function () {
            $('#aaeSearch').val('');
            $('#aaeStatusFilter').val('');
            $('#aaeMarqueFilter').val('');
            table.search('').draw(false);
        });

        return table;
    }

    /* ==================================================================
     * Modal vehicule : ouverture / remplissage / apercu calcul
     * ================================================================== */
    function vhJq(id) { return $('#vh_' + id); }

    function applyVhMode() {
        var manual = $('#vh_manual').prop('checked');
        $('#vhAutoBlock').toggle(!manual);
        $('#vhManualBlock').toggle(manual);
        // Les champs des blocs caches sont desactives (non soumis au serveur)
        $('#vhAutoBlock').find('input,select,textarea').prop('disabled', manual);
        $('#vhManualBlock').find('input,select,textarea').prop('disabled', !manual);
        updateVhPreview();
    }

    function updateVhPreview() {
        if ($('#vhPreview').length === 0) { return; }

        var fob = num(vhJq('fob').val());
        var doc = num(vhJq('doc_trans').val());
        var veh = num(vhJq('veh_trans').val());
        var shp = num(vhJq('shipping').val());
        var com = num(vhJq('commission').val());

        var totalUsd = fob + doc + veh + shp;

        var rate = num(vhJq('eur_rate').val());
        var fee  = num(vhJq('eur_fee').val());
        var dzd  = num(vhJq('dzd_rate').val());

        if (rate <= 0) { rate = OPT.eur_rate; }
        if (dzd <= 0) { dzd = OPT.dzd_rate; }

        var priceEur = totalUsd * rate * (1 + fee / 100);
        var ttcEur   = priceEur + (com > 0 ? com : OPT.commission);
        var ttcDzd   = ttcEur * dzd;

        $('#vhPrevTotalUsd').text(totalUsd > 0 ? totalUsd.toFixed(0) + ' $' : '—');
        $('#vhPrevTtcEur').text(ttcEur > 0 ? ttcEur.toFixed(0) + ' €' : '—');
        $('#vhPrevTtcDzd').text(ttcDzd > 0 ? ttcDzd.toFixed(0) + ' DA' : '—');
    }

    function resetVehicleForm() {
        $('#vh_id').val('');
        $('#aaeVehicleForm')[0].reset();
        $('#vh_status').val('disponible');
        // Valeurs par defaut (reglages) dans le bloc auto
        vhJq('eur_rate').val(OPT.eur_rate);
        vhJq('eur_fee').val(OPT.eur_fee_percent);
        vhJq('dzd_rate').val(OPT.dzd_rate);
        vhJq('commission').val(OPT.commission);
        $('#vh_manual').prop('checked', false).trigger('change');
        $('#aaeVehicleModalTitle').text($('#aaeVehicleModalTitle').data('new-title') || '');
    }

    function fillVehicleForm(v) {
        if (!v) { return; }

        $('#vh_id').val(v.id);

        var set = function (el, val) {
            $(el).val(val === null || typeof val === 'undefined' ? '' : val);
        };

        set('#vh_marque', v.marque);
        set('#vh_modele', v.modele);
        set('#vh_moteur', v.moteur);
        set('#vh_annee', v.annee);
        set('#vh_couleur', v.couleur);
        set('#vh_lien', v.lien);
        set('#vh_status', v.status);
        set('#vh_notes', v.notes);

        // Bloc auto
        set('#vh_fob', v.fob_usd);
        set('#vh_doc_trans', v.doc_translation);
        set('#vh_veh_trans', v.vehicle_translation);
        set('#vh_shipping', v.shipping_usd);
        set('#vh_commission', v.commission_eur !== null ? v.commission_eur : OPT.commission);
        set('#vh_eur_rate', v.eur_rate !== null ? v.eur_rate : OPT.eur_rate);
        set('#vh_eur_fee', v.eur_fee_percent !== null ? v.eur_fee_percent : OPT.eur_fee_percent);
        set('#vh_dzd_rate', v.dzd_rate !== null ? v.dzd_rate : OPT.dzd_rate);
        set('#vh_customs', v.customs_rate);

        // Bloc manuel
        set('#vm_fob', v.fob_usd);
        set('#vm_doc', v.doc_translation);
        set('#vm_veh', v.vehicle_translation);
        set('#vm_ship', v.shipping_usd);
        set('#vm_total_usd', v.total_usd);
        set('#vm_price_eur', v.price_eur);
        set('#vm_commission', v.commission_eur);
        set('#vm_ttc_eur', v.total_ttc_eur);
        set('#vm_ttc_dzd', v.ttc_dzd);
        set('#vm_dzd_rate', v.dzd_rate);
        set('#vm_eur_rate', v.eur_rate);
        set('#vm_eur_fee', v.eur_fee_percent);
        set('#vm_customs', v.customs_rate);

        applyVhMode();
    }

    function initVehicleModal() {
        var $wrapper = $('#aaeVehicleModal');
        if ($wrapper.length === 0) { return; }

        $('#aaeVehicleModalTitle').data('new-title', $('#aaeVehicleModalTitle').text());

        $(document).on('click', '#aaeNewVehicleBtn', function (e) {
            e.preventDefault();
            resetVehicleForm();
            $('#aaeVehicleModalTitle').text($('#aaeVehicleModalTitle').data('new-title'));
            $('#aaeVehicleModal').modal('show');
        });

        $(document).on('click', '.aae-edit-btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var base = $('#aaeVehicleForm').attr('action');
            if (!base) { return; }
            $.get(url(base.replace('vehicle_save', 'vehicle_get'), String(id)), function (res) {
                if (res && res.success && res.vehicle) {
                    fillVehicleForm(res.vehicle);
                    $('#aaeVehicleModalTitle').text(res.vehicle.marque + ' ' + res.vehicle.modele);
                    $('#aaeVehicleModal').modal('show');
                }
            }, 'json');
        });

        $('#vh_manual').on('change', applyVhMode);
        $('.aae-calc, #vh_customs').on('input change', debounce(updateVhPreview, 200));
    }

    /* ==================================================================
     * Modal devis rapide
     * ================================================================== */
    function currentBasisField(v) {
        var basis = $('#q_basis').val() || 'ttc_dzd';
        if (v === null || typeof v === 'undefined') { return basis; }
        return basis === 'total_usd' ? 'total_usd'
            : basis === 'total_ttc_eur' ? 'total_ttc_eur'
            : basis === 'fob_usd' ? 'fob_usd'
            : 'ttc_dzd';
    }

    function updateQuotePreview(vehicle) {
        var field = currentBasisField();
        var rate = (vehicle && typeof vehicle[field] !== 'undefined') ? num(vehicle[field]) : 0;
        var override = $('#q_rate_override').val();
        if (override !== '' && override !== null) {
            rate = num(override);
        }
        var qty = num($('#q_qty').val()) || 1;
        var symbol = $('#q_currency option:selected').data('symbol') || '';

        $('#aaeQuoteTotal').text(rate > 0 ? (rate * qty).toFixed(0) + ' ' + symbol : '—');

        if (vehicle) {
            var parts = [];
            if (vehicle.marque) { parts.push(vehicle.marque); }
            if (vehicle.modele) { parts.push(vehicle.modele); }
            $('#aaeQuoteVehicleLabel').text(parts.join(' '));
            var meta = [];
            if (vehicle.moteur) { meta.push(vehicle.moteur); }
            if (vehicle.annee) { meta.push(vehicle.annee); }
            if (vehicle.couleur) { meta.push(vehicle.couleur); }
            $('#aaeQuoteVehicleMeta').text(meta.join(' · '));
        }
    }

    function initQuoteModal() {
        var $modal = $('#aaeQuoteModal');
        if ($modal.length === 0) { return; }

        var vehicle = null;

        $(document).on('click', '.aae-quote-btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var base = $('#aaeVehicleForm').attr('action');
            if (!base) { return; }
            $.get(url(base.replace('vehicle_save', 'quote_get'), String(id)), function (res) {
                if (res && res.success && res.vehicle) {
                    vehicle = res.vehicle;
                    $('#q_vehicle_id').val(vehicle.id);
                    $('#q_qty').val(1);
                    $('#q_rate_override').val('');
                    $('#q_basis').val('ttc_dzd');
                    updateQuotePreview(vehicle);
                    $('#aaeQuoteModal').modal('show');
                }
            }, 'json');
        });

        $('#q_basis, #q_qty, #q_rate_override').on('input change', function () {
            updateQuotePreview(vehicle);
        });
        $('#q_currency').on('change', function () {
            updateQuotePreview(vehicle);
        });

        $('#aaeQuoteModal').on('hidden.bs.modal', function () {
            vehicle = null;
        });
    }

    /* ==================================================================
     * Tableau de bord : camembert des statuts d'operations (Chart.js)
     * ================================================================== */
    function initDashboardDoughnut() {
        var $canvas = $('#aaeOpsDoughnut');
        if ($canvas.length === 0 || typeof Chart === 'undefined') { return; }

        var labels;
        var colors;
        var values;

        try {
            labels = JSON.parse($canvas.attr('data-labels'));
            colors = JSON.parse($canvas.attr('data-colors'));
            values = JSON.parse($canvas.attr('data-values'));
        } catch (err) {
            return;
        }

        if (!values || values.length === 0) { return; }

        new Chart($canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                legend: { display: false },
                title: { display: false }
            }
        });
    }

    /* Boot */
    $(function () {
        initVehicleTable();
        initVehicleModal();
        initQuoteModal();
        initDashboardDoughnut();
    });

})(window.jQuery);
