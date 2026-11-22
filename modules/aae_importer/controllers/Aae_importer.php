<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * AAE Import Manager - Controleur admin
 * URL : admin/aae_importer/<method>
 */
class Aae_importer extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('aae_importer/Aae_importer_model', 'aae_model');

        if (!staff_can('view', 'aae_importer')) {
            access_denied('aae_importer');
        }
    }

    /* ------------------------------------------------------------------
     * Tableau de bord
     * ------------------------------------------------------------------ */
    public function index()
    {
        $data                    = $this->aae_model->get_dashboard_data();
        $data['title']           = _l('aae_module_name');
        $data['statuses']        = $this->aae_model->operation_statuses_colors();
        $data['vehicle_statuses'] = $this->aae_model->vehicle_statuses_colors();

        $this->load->view('dashboard', $data);
    }

    /* ------------------------------------------------------------------
     * Catalogue
     * ------------------------------------------------------------------ */
    public function catalogue()
    {
        if ($this->input->is_ajax_request()) {
            $this->vehicle_table();
            return;
        }

        $data['title']            = _l('aae_menu_catalogue');
        $data['marques']          = $this->aae_model->get_marques();
        $data['vehicle_statuses'] = $this->aae_model->vehicle_statuses_colors();
        $data['bases']            = $this->aae_model->quote_bases();
        $data['dzd_rates']        = [282, 275];

        $this->load->model('clients_model');
        $data['clients']    = $this->db->select('userid as id, company as label')->order_by('company', 'asc')->get(db_prefix() . 'clients')->result_array();
        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();

        $this->load->view('catalogue', $data);
    }

    /** Liste serveurs des vehicules (JSON) */
    protected function vehicle_table()
    {
        $search   = $this->input->post('search') ?: '';
        $status   = $this->input->post('f_status') ?: '';
        $marque   = $this->input->post('f_marque') ?: '';
        $start    = $this->input->post('start') ? (int)$this->input->post('start') : 0;
        $length   = $this->input->post('length') ? (int)$this->input->post('length') : 25;
        $orderCol = 'id';
        $orderDir = 'desc';

        if ($this->input->post('order')) {
            $order = $this->input->post('order');
            if (isset($order[0])) {
                $colsMap = [0 => 'id', 1 => 'marque', 2 => 'modele', 3 => 'moteur', 4 => 'annee', 6 => 'fob_usd', 7 => 'total_usd', 8 => 'total_ttc_eur', 9 => 'ttc_dzd'];
                if (isset($colsMap[$order[0]['column']])) {
                    $orderCol = $colsMap[$order[0]['column']];
                }
                $orderDir = $order[0]['dir'] === 'asc' ? 'asc' : 'desc';
            }
        }

        $total    = $this->aae_model->get_vehicles_count($search, $status, $marque);
        $vehicles = $this->aae_model->get_vehicles($search, $status, $marque, $length, $start, $orderCol, $orderDir);

        $statuses = $this->aae_model->vehicle_statuses_colors();
        $storefrontCars = $this->fetch_storefront_cars();
        $rows     = [];

        foreach ($vehicles as $v) {
            $status_name  = isset($statuses[$v['status']]) ? $statuses[$v['status']]['name'] : $v['status'];
            $status_color = isset($statuses[$v['status']]) ? $statuses[$v['status']]['color'] : '#6c757d';

            $siteUrl = $this->match_storefront_url($v['marque'] ?? '', $v['modele'] ?? '', $storefrontCars);
            $lien = '';
            if ($v['lien'] && aae_importer_starts_with($v['lien'], 'http')) {
                $lien = $v['lien'];
            }

            $linkHtml = '';
            if ($siteUrl !== '') {
                $linkHtml .= '<a href="' . e($siteUrl) . '" target="_blank" rel="noopener" class="text-success" style="font-size:12px;word-break:break-all;">'
                    . '<i class="fa fa-globe"></i> ' . e($siteUrl) . '</a>';
            } else {
                $linkHtml .= '<span class="text-muted" style="font-size:11px;">Non lié</span>';
            }
            if ($lien !== '') {
                $linkHtml .= ' <a href="' . e($lien) . '" target="_blank" class="text-muted" rel="noopener" title="Source fournisseur"><i class="fa fa-external-link"></i></a>';
            }

            $actions = '<div class="btn-group">';

            if (staff_can('create', 'aae_importer')) {
                $actions .= '<button type="button" class="btn btn-default btn-xs aae-quote-btn" data-id="' . $v['id'] . '">'
                    . '<i class="fa fa-file-text-o"></i> ' . _l('aae_quote') . '</button>';

                $actions .= '<a href="' . admin_url('aae_importer/operation_create/' . $v['id']) . '" class="btn btn-default btn-xs">'
                    . '<i class="fa fa-ship"></i></a>';
            }

            if (staff_can('edit', 'aae_importer')) {
                $actions .= '<button type="button" class="btn btn-default btn-xs aae-edit-btn" data-id="' . $v['id'] . '">'
                    . '<i class="fa fa-pencil"></i></button>';
            }

            if (staff_can('delete', 'aae_importer')) {
                $actions .= form_open(admin_url('aae_importer/vehicle_delete'), ['class' => 'aae-inline-form', 'onsubmit' => 'return confirm(\'' . _l('aae_confirm_delete_vehicle') . '\');'])
                    . aae_importer_csrf_field()
                    . '<input type="hidden" name="id" value="' . $v['id'] . '">'
                    . '<button type="submit" class="btn btn-default btn-xs" style="color:#dc3545"><i class="fa fa-trash"></i></button>'
                    . form_close();
            }

            $actions .= '</div>';

            $rows[] = [
                'DT_RowId'   => 'v_' . $v['id'],
                0            => $v['id'],
                1            => e($v['marque']),
                2            => e($v['modele']),
                3            => e($v['moteur']),
                4            => $v['annee'] ? $v['annee'] : '',
                5            => e($v['couleur']),
                6            => $v['fob_usd'] !== null && $v['fob_usd'] > 0 ? number_format($v['fob_usd'], 0, ',', ' ') : '-',
                7            => $v['total_usd'] !== null && $v['total_usd'] > 0 ? number_format($v['total_usd'], 0, ',', ' ') : '-',
                8            => $v['total_ttc_eur'] !== null && $v['total_ttc_eur'] > 0 ? number_format($v['total_ttc_eur'], 0, ',', ' ') : '-',
                9            => $v['ttc_dzd'] !== null && $v['ttc_dzd'] > 0 ? number_format($v['ttc_dzd'], 0, ',', ' ') : '-',
                10           => ($v['ttc_dzd'] !== null && $v['ttc_dzd'] > 0) ? number_format($v['ttc_dzd'] / 10000, 0, ',', ' ') . ' M' : '-',
                11           => $v['customs_rate'] !== null && $v['customs_rate'] !== '' ? strval($v['customs_rate']) . ' %' : '-',
                12           => $linkHtml,
                13           => '<span class="aae-badge" style="background:' . $status_color . ';color:#fff">' . e($status_name) . '</span>',
                14           => $actions,
            ];
        }

        header('Content-Type: application/json');
        $CI = &get_instance();
        echo json_encode([
            'draw'            => $this->input->post('draw') ? (int)$this->input->post('draw') : 1,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $rows,
            // Token CSRF renouvelable pour les requetes DataTatablesajax suivantes
            'csrf'            => [
                'name' => $CI->security->get_csrf_token_name(),
                'hash' => $CI->security->get_csrf_hash(),
            ],
        ]);
        exit;
    }

    /** Recupere un vehicule (JSON pour formulaire edition / devis) */
    public function vehicle_get($id)
    {
        $vehicle = $this->aae_model->get_vehicle($id);
        echo json_encode($vehicle
            ? ['success' => true, 'vehicle' => $vehicle]
            : ['success' => false]);
        exit;
    }

    /** Enregistrement (ajout/modif) d'un vehicule */
    public function vehicle_save()
    {
        if (!$this->input->post()) {
            redirect(admin_url('aae_importer/catalogue'));
        }

        $id = $this->input->post('id');
        $manual = $this->input->post('manual_input') == '1';

        if (is_numeric($id) && $id > 0) {
            if (!staff_can('edit', 'aae_importer')) {
                access_denied('aae_importer');
            }
            $this->aae_model->update_vehicle((int)$id, $this->input->post(), $manual);
            set_alert('success', _l('aae_vehicle_updated'));
        } else {
            if (!staff_can('create', 'aae_importer')) {
                access_denied('aae_importer');
            }
            $this->aae_model->add_vehicle($this->input->post(), $manual);
            set_alert('success', _l('aae_vehicle_added'));
        }

        redirect(admin_url('aae_importer/catalogue'));
    }

    public function sync_prices()
    {
        if (!staff_can('view', 'aae_importer')) {
            access_denied('aae_importer');
        }

        $vehicles = $this->aae_model->get_vehicles('', '', '', null, 0, 'id', 'asc');
        $items = [];
        foreach ($vehicles as $v) {
            $price = (float) ($v['total_ttc_eur'] ?? 0);
            if ($price <= 0) {
                $price = (float) ($v['price_eur'] ?? 0);
            }
            if ($price > 0 && !empty($v['modele'])) {
                $items[] = [
                    'brand' => (string) ($v['marque'] ?? ''),
                    'model' => (string) $v['modele'],
                    'sale_price' => $price,
                ];
            }
        }

        $defaultSyncUrl = (defined('APP_BASE_URL') && strpos(APP_BASE_URL, 'localhost') === false)
            ? 'https://algerie-autoexport.com/aae/sync-car-price'
            : 'http://localhost/algerie-autoexport/aae/sync-car-price';
        $url = getenv('AAE_STOREFRONT_PRICE_SYNC_URL') ?: $defaultSyncUrl;
        $secret = getenv('AAE_WEBHOOK_SECRET') ?: 'aae-fennec-dash-2026';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-AAE-Webhook-Secret: ' . $secret,
            ],
            CURLOPT_POSTFIELDS => json_encode(['items' => $items]),
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if ($code >= 200 && $code < 300 && is_array($decoded) && !empty($decoded['success'])) {
            $count = (int) ($decoded['updated'] ?? 0);
            set_alert('success', 'Prix synchronisés avec succès vers le site Algérie Auto Export (' . $count . ' véhicule(s) mis à jour).');
        } else {
            set_alert('warning', 'La synchronisation des prix avec le site a échoué (HTTP ' . $code . ').');
        }

        redirect(admin_url('aae_importer/catalogue'));
    }

    public function vehicle_delete()
    {
        if (!staff_can('delete', 'aae_importer')) {
            access_denied('aae_importer');
        }
        if (!is_numeric($this->input->post('id'))) {
            redirect(admin_url('aae_importer/catalogue'));
        }

        $result = $this->aae_model->delete_vehicle((int)$this->input->post('id'));

        if ($result['success']) {
            set_alert('success', _l('aae_vehicle_deleted'));
        } else {
            set_alert('warning', $result['message']);
        }

        redirect(admin_url('aae_importer/catalogue'));
    }

    /* ------------------------------------------------------------------
     * Import / export CSV
     * ------------------------------------------------------------------ */
    public function import_csv()
    {
        if ($this->input->post()) {
            if (!staff_can('create', 'aae_importer')) {
                access_denied('aae_importer');
            }

            $file = $_FILES['import_file'] ?? null;

            if (!$file || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
                set_alert('danger', _l('aae_no_file_selected'));
                redirect(admin_url('aae_importer/import_csv'));
            }

            $raw = file_get_contents($file['tmp_name']);
            if (function_exists('mb_detect_encoding')) {
                $encoding = mb_detect_encoding($raw, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
                if ($encoding && $encoding !== 'UTF-8') {
                    $raw = mb_convert_encoding($raw, 'UTF-8', $encoding);
                }
            }

            // Espace de stockage temporaire du module
            $dir = FCPATH . 'uploads/aae_importer';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
                @file_put_contents($dir . '/index.html', '');
            }

            // Detection du delimiter : ; ou ,
            $first_line = strtok($raw, "\n");
            $semicolon_count = substr_count($first_line, ';');
            $comma_count = substr_count($first_line, ',');
            $delimiter   = $semicolon_count > $comma_count ? ';' : ',';

            $work_path = $dir . '/' . md5(uniqid('catalogue', true)) . '.csv';
            file_put_contents($work_path, $raw);

            $replace = $this->input->post('replace_all') == '1';
            $status  = $this->input->post('import_status') ?: 'disponible';

            $result = $this->aae_model->import_csv($work_path, $delimiter, $replace, $status);

            @unlink($work_path);

            if ($result['errors'] > 0 && $result['inserted'] == 0 && $result['updated'] == 0) {
                set_alert('danger', _l('aae_import_failed'));
            } else {
                set_alert('success', _l('aae_import_success', $result['inserted'], $result['updated']));
            }

            log_activity('Import CSV catalogue AAE [fichier : ' . $file['name'] . ']');

            redirect(admin_url('aae_importer/catalogue'));
        }

        $data['title'] = _l('aae_menu_import_csv');
        $data['total'] = $this->db->count_all(db_prefix() . 'aae_vehicles');
        $data['vehicle_statuses'] = $this->aae_model->vehicle_statuses_colors();

        $this->load->view('import_csv', $data);
    }

    public function export_csv()
    {
        $vehicles = $this->aae_model->get_vehicles('', '', '', null, 0, 'id', 'asc');

        $this->output->set_content_type('application/csv', 'utf-8');
        $this->output->set_header('Content-Disposition: attachment; filename="catalogue_aae_' . date('Y_m_d') . '.csv"');

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'MARQUE', 'MODELE', 'MOTEUR', 'ANNEE MODELE', 'COULEUR',
            'FOB PRIX $', 'TRADUCTION DOCUMENTS', 'TRADUCTION DU VEHICULE', 'FRAIS DE LIVRAISON',
            'PRIX TOTAL $', 'PRIX EN EUR', 'COMMISSION', 'TOTAL TTC', 'TAUX DA', 'TTC DZD', 'ESTIMATION DEDOUANEMENT', 'LIEN VEHICULE', 'STATUT', 'NOTES',
        ], ',');

        foreach ($vehicles as $v) {
            fputcsv($handle, [
                $v['marque'], $v['modele'], $v['moteur'], $v['annee'], $v['couleur'],
                $v['fob_usd'], $v['doc_translation'], $v['vehicle_translation'], $v['shipping_usd'],
                $v['total_usd'], $v['price_eur'], $v['commission_eur'], $v['total_ttc_eur'],
                $v['dzd_rate'], $v['ttc_dzd'], $v['customs_rate'], $v['lien'], $v['status'], $v['notes'],
            ], ',');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        // BOM excel pour accents dans Excel FR
        echo "\xEF\xBB\xBF";
        echo $csv;
        exit;
    }

    /* ------------------------------------------------------------------
     * Operations d'import
     * ------------------------------------------------------------------ */
    public function operations()
    {
        $status     = $this->input->get('status') ?: '';
        $search     = $this->input->get('search') ?: '';
        $view       = $this->input->get('view') ?: 'list';

        $operations = $this->aae_model->get_operations($status, $search);
        $statuses   = $this->aae_model->operation_statuses_colors();
        $counts     = $this->aae_model->count_operations_by_status();

        $data = [
            'title'      => _l('aae_menu_operations'),
            'operations' => $operations,
            'statuses'   => $statuses,
            'counts'     => $counts,
            'selected'   => $status,
            'search'     => $search,
            'view'       => $view,
        ];

        $this->load->view('operations', $data);
    }

    public function operation_create($vehicle_id = null)
    {
        if (!staff_can('create', 'aae_importer')) {
            access_denied('aae_importer');
        }

        $data['title']    = _l('aae_new_operation');
        $data['statuses'] = $this->aae_model->operation_statuses_colors();

        if ($vehicle_id && is_numeric($vehicle_id)) {
            $data['vehicle'] = $this->aae_model->get_vehicle((int)$vehicle_id);
        }

        $data['clients'] = $this->db->select('userid as id, company as label')
            ->order_by('company', 'asc')
            ->get(db_prefix() . 'clients')->result_array();

        $data['vehicles'] = $this->vehicle_labels();

        $this->load->view('operation_form', $data);
    }

    public function operation_edit($id)
    {
        if (!staff_can('edit', 'aae_importer')) {
            access_denied('aae_importer');
        }

        $operation = $this->aae_model->get_operation((int)$id);
        if (!$operation) {
            redirect(admin_url('aae_importer/operations'));
        }

        $data['operation'] = $operation;
        $data['title']     = $operation->ref . ' — ' . _l('aae_edit');
        $data['statuses']  = $this->aae_model->operation_statuses_colors();

        $data['clients'] = $this->db->select('userid as id, company as label')
            ->order_by('company', 'asc')
            ->get(db_prefix() . 'clients')->result_array();

        $data['vehicles'] = $this->vehicle_labels();

        $this->load->view('operation_form', $data);
    }

    public function operation_view($id)
    {
        $operation = $this->aae_model->get_operation((int)$id);
        if (!$operation) {
            redirect(admin_url('aae_importer/operations'));
        }

        $statuses     = $this->aae_model->operation_statuses_colors();

        $data = [
            'title'       => $operation->ref . ' — ' . _l('aae_menu_operations'),
            'operation'   => $operation,
            'statuses'    => $statuses,
            'history'     => $this->aae_model->get_operation_statuses_history((int)$id),
        ];

        // Statut suivant (pipeline)
        $order = array_keys($statuses);
        $idx   = array_search($operation->status, $order);
        if ($idx !== false && isset($order[$idx + 1]) && $order[$idx + 1] !== 'annule') {
            $data['next_status'] = $order[$idx + 1];
        }

        $this->load->view('operation', $data);
    }

    public function operation_save()
    {
        if (!$this->input->post()) {
            redirect(admin_url('aae_importer/operations'));
        }

        $id = $this->input->post('id');

        if (is_numeric($id) && $id > 0) {
            if (!staff_can('edit', 'aae_importer')) {
                access_denied('aae_importer');
            }
            $id = (int)$id;
            $this->aae_model->update_operation($id, $this->input->post());

            if ($this->input->post('status')) {
                $this->aae_model->set_operation_status($id, $this->input->post('status'));
            }

            set_alert('success', _l('aae_operation_updated'));
            redirect(admin_url('aae_importer/operation_view/' . $id));
        }

        if (!staff_can('create', 'aae_importer')) {
            access_denied('aae_importer');
        }

        $id = $this->aae_model->add_operation($this->input->post());

        $status = $this->input->post('status');
        if ($status && $status !== 'demande') {
            $this->aae_model->set_operation_status($id, $status);
        }

        set_alert('success', _l('aae_operation_added'));
        redirect(admin_url('aae_importer/operation_view/' . $id));
    }

    /** AJAX : changer le statut d'une operation (aussi utilisable en POST formulaire classique) */
    public function operation_status()
    {
        if (!staff_can('edit', 'aae_importer')) {
            access_denied('aae_importer');
        }

        $id     = $this->input->post('id');
        $status = $this->input->post('status');
        $note   = $this->input->post('note');

        $done = false;
        if ($id && $status) {
            $done = $this->aae_model->set_operation_status((int)$id, $status, $note);
        }

        // Requete formulaire classique (boutons "etape suivante") : redirection avec flash
        if (!$this->input->is_ajax_request()) {
            if ($done) {
                set_alert('success', _l('aae_operation_updated'));
            }
            redirect($this->input->post('redirect') ?: admin_url('aae_importer/operations'));
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => (bool)$done]);
        exit;
    }

    public function operation_delete()
    {
        if (!staff_can('delete', 'aae_importer')) {
            access_denied('aae_importer');
        }
        if (!is_numeric($this->input->post('id'))) {
            redirect(admin_url('aae_importer/operations'));
        }

        $id = (int)$this->input->post('id');
        $this->db->where('operation_id', $id)->delete(db_prefix() . 'aae_operation_status');
        $this->db->where('id', $id)->delete(db_prefix() . 'aae_operations');

        log_activity('Opération d\'import AAE supprimée [ID: ' . $id . ']');
        set_alert('success', _l('aae_operation_deleted'));

        redirect(admin_url('aae_importer/operations'));
    }

    /** Liste vehicules pour les formulaires */
    protected function vehicle_labels()
    {
        $vehicles = $this->aae_model->get_vehicles('', '', '', null, 0, 'marque', 'asc');
        $out      = [];
        foreach ($vehicles as $v) {
            $out[] = [
                'id'            => $v['id'],
                'marque'        => $v['marque'],
                'modele'        => $v['modele'],
                'moteur'        => $v['moteur'],
                'annee'         => $v['annee'],
                'label'         => trim($v['marque'] . ' — ' . $v['modele']),
                'meta'          => trim(($v['annee'] ? $v['annee'] : '') . ' · ' . $v['moteur']),
                'ttc_dzd'       => $v['ttc_dzd'],
                'total_usd'     => $v['total_usd'],
                'total_ttc_eur' => $v['total_ttc_eur'],
                'fob_usd'       => $v['fob_usd'],
            ];
        }

        return $out;
    }

    /* ------------------------------------------------------------------
     * Devis rapide
     * ------------------------------------------------------------------ */
    public function quote_get($vehicle_id)
    {
        $vehicle = $this->aae_model->get_vehicle($vehicle_id);
        echo json_encode($vehicle
            ? ['success' => true, 'vehicle' => $vehicle]
            : ['success' => false]);
        exit;
    }

    public function quote_create()
    {
        if (!staff_can('create', 'aae_importer')) {
            access_denied('aae_importer');
        }
        if (!$this->input->post()) {
            redirect(admin_url('aae_importer/catalogue'));
        }

        $result = $this->aae_model->create_estimate_from_vehicle($this->input->post());

        if ($result['success']) {
            set_alert('success', _l('aae_estimate_created'));
            redirect($result['link']);
        }

        set_alert('danger', $result['message']);
        redirect(admin_url('aae_importer/catalogue'));
    }

    /* ------------------------------------------------------------------
     * Réglages
     * ------------------------------------------------------------------ */
    public function settings()
    {
        if (!is_admin()) {
            access_denied('aae_importer');
        }

        if ($this->input->post()) {
            $options = [
                'aae_importer_eur_rate',
                'aae_importer_eur_fee_percent',
                'aae_importer_dzd_rate',
                'aae_importer_default_commission',
                'aae_importer_quote_validity_days',
                'aae_importer_client_portal',
            ];
            foreach ($options as $option) {
                if ($this->input->post($option) !== null) {
                    update_option($option, $this->input->post($option));
                }
            }
            set_alert('success', _l('settings_saved'));
            redirect(admin_url('aae_importer/settings'));
        }

        $data['title'] = _l('aae_menu_settings');
        $this->load->view('settings', $data);
    }

    /** Récupère la liste des véhicules publiés sur Algérie Auto Export avec leur URL exacte */
    protected function fetch_storefront_cars(): array
    {
        $defaultLinksUrl = (defined('APP_BASE_URL') && strpos(APP_BASE_URL, 'localhost') === false)
            ? 'https://algerie-autoexport.com/aae/car-links'
            : 'http://localhost/algerie-autoexport/aae/car-links';
        $url = getenv('AAE_STOREFRONT_CAR_LINKS_URL') ?: $defaultLinksUrl;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || ! $raw) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded['cars'] ?? null) ? $decoded['cars'] : [];
    }

    /** Associe un véhicule Fennec Dash à son URL exacte sur Algérie Auto Export */
    protected function match_storefront_url(string $brand, string $model, array $storefrontCars): string
    {
        if (empty($storefrontCars) || trim($model) === '') {
            return '';
        }

        $normalize = static function (string $s): string {
            $s = mb_strtolower($s, 'UTF-8');
            return trim(preg_replace('/[^a-z0-9]+/u', ' ', $s));
        };

        $targetModel = $normalize($model);
        $targetFull = $normalize(trim($brand . ' ' . $model));

        // 1. Exact or substring match
        foreach ($storefrontCars as $car) {
            $carNorm = $normalize((string) ($car['name'] ?? ''));
            if ($carNorm === '') {
                continue;
            }
            if ($carNorm === $targetFull || $carNorm === $targetModel || ($targetModel !== '' && str_contains($carNorm, $targetModel))) {
                return (string) ($car['url'] ?? '');
            }
        }

        // 2. Best token overlap match (e.g. brand + model keywords)
        $tokens = array_values(array_filter(explode(' ', $targetFull), static fn ($t) => strlen($t) >= 2));
        $bestUrl = '';
        $bestScore = 0.0;
        foreach ($storefrontCars as $car) {
            $carNorm = $normalize((string) ($car['name'] ?? ''));
            if ($carNorm === '' || empty($tokens)) {
                continue;
            }
            $hits = 0;
            foreach ($tokens as $tok) {
                if (str_contains($carNorm, $tok)) {
                    $hits++;
                }
            }
            $score = $hits / count($tokens);
            if ($score >= 0.65 && $score > $bestScore) {
                $bestScore = $score;
                $bestUrl = (string) ($car['url'] ?? '');
            }
        }

        return $bestUrl;
    }
}
