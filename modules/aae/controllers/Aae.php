<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Aae extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('aae/aae_model');
        $this->load->helper('aae/aae');
    }

    public function index()
    {
        if (staff_cant('view', 'aae')) {
            access_denied('aae');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('aae', 'table'));
        }

        $filterClientId = (int) $this->input->get('client_id');
        $filterClient   = $filterClientId ? $this->aae_model->get_client($filterClientId) : null;

        $data['title']          = _l('aae_catalogue');
        $data['company_name']   = aae_get_option('aae_company_name', 'Algerie Auto Export');
        $data['stats']          = $this->aae_model->get_stats();
        $data['brands']         = $this->aae_model->get_brands();
        $data['models_by_brand'] = $this->aae_model->get_models_by_brand();
        $data['statuses']       = aae_statuses();
        $data['filter_client']  = $filterClient;
        $data['filter_client_id'] = $filterClient ? (int) $filterClient->userid : 0;

        $this->load->view('aae/manage', $data);
    }

    public function car($id = '')
    {
        if (staff_cant('view', 'aae')) {
            access_denied('aae');
        }

        $car = null;

        if ($id !== '') {
            $car = $this->aae_model->get($id);

            if (! $car) {
                show_404();
            }

            if (staff_cant('edit', 'aae')) {
                access_denied('aae');
            }
        } elseif (staff_cant('create', 'aae')) {
            access_denied('aae');
        }

        if ($this->input->post()) {
            $post  = $this->input->post();
            $brand = trim((string) ($post['brand'] ?? ''));
            $model = trim((string) ($post['model'] ?? ''));

            if ($brand === '' || $model === '') {
                set_alert('danger', _l('aae_brand_model_required'));

                $car = (object) array_merge(
                    $this->car_defaults(),
                    $car ? (array) $car : [],
                    $post
                );
            } else {
                $payload = $this->build_car_payload($post, $car);

                if ($id !== '') {
                    $this->aae_model->update($payload, $id);
                    set_alert('success', _l('aae_car_updated'));
                } else {
                    $payload['source'] = 'manual';
                    $id                = $this->aae_model->add($payload);
                    set_alert('success', _l('aae_car_added'));
                }

                redirect(admin_url('aae/car/' . $id));
            }
        }

        $prefillClientId = (int) $this->input->get('client_id');
        $selectedClient  = (int) aae_value($car, 'client_id', $prefillClientId);

        $data['title']            = $id !== '' ? _l('aae_edit_car') : _l('aae_new_car');
        $data['car']              = $car;
        $data['is_edit']          = $id !== '';
        $data['clients']          = $this->aae_model->get_clients_list();
        $data['brands']           = $this->aae_model->get_brands();
        $data['models_by_brand']  = $this->aae_model->get_models_by_brand();
        $data['statuses']         = aae_statuses();
        $data['destinations']     = aae_destinations();
        $data['rates']            = aae_get_rates();
        $data['defaults']         = aae_get_form_defaults();
        $data['prefill_client_id'] = $prefillClientId;
        $data['selected_client']  = $selectedClient;
        $data['invoices']         = $selectedClient ? $this->aae_model->get_invoices_for_client($selectedClient) : [];
        $data['can_view_invoices'] = staff_can('view', 'invoices');

        $this->load->view('aae/car', $data);
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'aae')) {
            access_denied('aae');
        }

        if (! $this->aae_model->get($id)) {
            show_404();
        }

        $this->aae_model->delete($id);

        set_alert('success', _l('aae_car_deleted'));
        redirect(admin_url('aae'));
    }

    public function update_status()
    {
        if (! $this->input->is_ajax_request()) {
            show_404();
        }

        if (staff_cant('edit', 'aae')) {
            $this->json_response([
                'success' => false,
                'message' => _l('aae_access_denied'),
            ]);

            return;
        }

        $id     = (int) $this->input->post('id');
        $status = $this->input->post('status');

        if (! $id || ! in_array($status, aae_statuses(), true) || ! $this->aae_model->get($id)) {
            $this->json_response([
                'success' => false,
                'message' => _l('aae_invalid_request'),
            ]);

            return;
        }

        $this->aae_model->update_status($id, $status);

        $this->json_response([
            'success' => true,
            'message' => _l('aae_status_updated'),
            'stats'   => $this->aae_model->get_stats(),
        ]);
    }

    public function update_quantity()
    {
        if (! $this->input->is_ajax_request()) {
            show_404();
        }

        if (staff_cant('edit', 'aae')) {
            $this->json_response([
                'success' => false,
                'message' => _l('aae_access_denied'),
            ]);

            return;
        }

        $id       = (int) $this->input->post('id');
        $quantity = $this->input->post('quantity');

        if (! $id || $quantity === null || ! is_numeric($quantity) || (int) $quantity < 0 || ! $this->aae_model->get($id)) {
            $this->json_response([
                'success' => false,
                'message' => _l('aae_invalid_request'),
            ]);

            return;
        }

        $this->aae_model->update_quantity($id, (int) $quantity);

        $this->json_response([
            'success' => true,
            'message' => _l('aae_quantity_updated'),
            'stats'   => $this->aae_model->get_stats(),
        ]);
    }

    public function import()
    {
        if (staff_cant('import', 'aae')) {
            access_denied('aae');
        }

        $data['title']        = _l('aae_import');
        $data['statuses']     = aae_statuses();
        $data['destinations'] = aae_destinations();
        $data['preview']      = $this->session->flashdata('aae_import_result');

        if ($this->input->post()) {
            if (! isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
                set_alert('danger', _l('aae_import_no_file'));
                redirect(admin_url('aae/import'));
            }

            if ($_FILES['file']['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($_FILES['file']['tmp_name'])) {
                set_alert('danger', _l('aae_import_upload_error'));
                redirect(admin_url('aae/import'));
            }

            $filename = $_FILES['file']['name'];
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (! in_array($extension, ['csv', 'txt'], true)) {
                set_alert('danger', _l('aae_import_invalid_type'));
                redirect(admin_url('aae/import'));
            }

            if ((int) $_FILES['file']['size'] > 10 * 1024 * 1024) {
                set_alert('danger', _l('aae_import_too_large'));
                redirect(admin_url('aae/import'));
            }

            $parsed = aae_parse_csv_file($_FILES['file']['tmp_name']);

            if (isset($parsed['error'])) {
                set_alert('danger', $parsed['error']);
                redirect(admin_url('aae/import'));
            }

            if (empty($parsed['rows'])) {
                set_alert('danger', _l('aae_import_no_rows'));
                redirect(admin_url('aae/import'));
            }

            $dryRun = (bool) $this->input->post('dry_run');

            $result = $this->aae_model->import_rows($parsed['rows'], $parsed['map'], [
                'default_status'      => $this->input->post('default_status'),
                'default_destination' => $this->input->post('default_destination'),
                'default_quantity'    => $this->input->post('default_quantity'),
                'update_existing'     => (bool) $this->input->post('update_existing'),
                'dry_run'             => $dryRun,
            ]);

            $result['filename'] = $filename;

            if ($dryRun) {
                $data['preview'] = $result;

                set_alert('info', _l('aae_import_preview_note'));
            } else {
                $this->aae_model->log_import([
                    'filename'   => $filename,
                    'total_rows' => $result['total_rows'],
                    'inserted'   => $result['inserted'],
                    'updated'    => $result['updated'],
                    'skipped'    => $result['skipped'],
                    'failed'     => $result['failed'],
                    'status'     => 'completed',
                    'messages'   => $result['messages'],
                ]);

                unset($result['preview']);

                $this->session->set_flashdata('aae_import_result', $result);

                set_alert('success', _l('aae_import_completed'));
                redirect(admin_url('aae/import'));
            }
        }

        $data['imports'] = $this->aae_model->get_imports();

        $this->load->view('aae/import', $data);
    }

    public function settings()
    {
        if (! is_admin()) {
            access_denied('aae');
        }

        if ($this->input->post()) {
            $options = [
                'aae_eur_markup'       => aae_parse_number($this->input->post('aae_eur_markup')),
                'aae_dzd_rate'         => aae_parse_number($this->input->post('aae_dzd_rate')),
                'aae_default_doc_fee'  => aae_parse_number($this->input->post('aae_default_doc_fee')),
                'aae_default_vehicle_fee' => aae_parse_number($this->input->post('aae_default_vehicle_fee')),
                'aae_default_delivery_fee' => aae_parse_number($this->input->post('aae_default_delivery_fee')),
                'aae_default_commission' => aae_parse_number($this->input->post('aae_default_commission')),
            ];

            $valid = true;

            foreach (['aae_default_doc_fee', 'aae_default_vehicle_fee', 'aae_default_delivery_fee', 'aae_default_commission'] as $fee_option) {
                if ($options[$fee_option] === null) {
                    $options[$fee_option] = 0;
                }
            }

            foreach ($options as $name => $value) {
                if ($value === null || $value < 0) {
                    $valid = false;
                }

                if ($name === 'aae_dzd_rate' && $value <= 0) {
                    $valid = false;
                }
            }

            if (! $valid) {
                set_alert('danger', _l('aae_settings_invalid'));
                redirect(admin_url('aae/settings'));
            }

            foreach ($options as $name => $value) {
                update_option($name, (string) $value);
            }

            update_option('aae_company_name', trim((string) $this->input->post('aae_company_name')));

            foreach ([
                'aae_whatsapp_access_token',
                'aae_whatsapp_phone_number_id',
                'aae_whatsapp_business_account_id',
                'aae_whatsapp_webhook_verify_token',
            ] as $whatsapp_option) {
                update_option($whatsapp_option, trim((string) $this->input->post($whatsapp_option)));
            }

            set_alert('success', _l('aae_settings_saved'));
            redirect(admin_url('aae/settings'));
        }

        $data['title'] = _l('aae_settings');

        $this->load->view('aae/settings', $data);
    }

    public function invoices_for_client($client_id = 0)
    {
        if (! $this->input->is_ajax_request()) {
            show_404();
        }

        if (staff_cant('view', 'aae') || staff_cant('view', 'invoices')) {
            $this->json_response([
                'success'  => false,
                'invoices' => [],
                'message'  => _l('aae_access_denied'),
            ]);

            return;
        }

        $invoices = $this->aae_model->get_invoices_for_client((int) $client_id);
        $output   = [];

        foreach ($invoices as $invoice) {
            $currency = get_currency($invoice['currency']);

            if (! $currency) {
                $currency = 'USD';
            }

            $output[] = [
                'id'    => (int) $invoice['id'],
                'label' => format_invoice_number((object) $invoice) . ' - ' . app_format_money($invoice['total'], $currency),
            ];
        }

        $this->json_response([
            'success'  => true,
            'invoices' => $output,
        ]);
    }

    public function export()
    {
        if (staff_cant('view', 'aae')) {
            access_denied('aae');
        }

        $rows = $this->aae_model->get_all_for_export();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="aae-stock-' . date('Ymd-His') . '.csv"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM for Excel.
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            _l('aae_id'),
            _l('aae_brand'),
            _l('aae_model'),
            _l('aae_engine'),
            _l('aae_year'),
            _l('aae_color'),
            _l('aae_quantity'),
            _l('aae_status'),
            _l('aae_destination'),
            _l('aae_fob_price'),
            _l('aae_doc_fee'),
            _l('aae_vehicle_fee'),
            _l('aae_delivery_fee'),
            _l('aae_total_usd'),
            _l('aae_price_eur'),
            _l('aae_commission'),
            _l('aae_total_ttc'),
            _l('aae_price_dzd'),
            _l('aae_price_millions'),
            _l('aae_customs_estimate'),
            _l('aae_client'),
            _l('aae_invoice'),
            _l('aae_link'),
            _l('aae_notes'),
            _l('aae_datecreated'),
            _l('aae_dateupdated'),
        ]);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id'],
                $row['brand'],
                $row['model'],
                $row['engine'],
                $row['model_year'],
                $row['color'],
                $row['quantity'],
                aae_status_label($row['status']),
                aae_destination_label($row['destination']),
                $row['fob_price'],
                $row['doc_fee'],
                $row['vehicle_fee'],
                $row['delivery_fee'],
                $row['total_usd'],
                $row['price_eur'],
                $row['commission'],
                $row['total_ttc'],
                $row['price_dzd'],
                $row['price_millions'],
                $row['customs_estimate'],
                $row['client_company'],
                $row['invoice_id'] ? format_invoice_number($row['invoice_id']) : '',
                $row['link'],
                $row['notes'],
                $row['datecreated'],
                $row['dateupdated'],
            ]);
        }

        fclose($output);
        exit;
    }

    private function build_car_payload($post, $existing = null)
    {
        $rates = aae_get_rates();

        $fob      = aae_parse_number($post['fob_price'] ?? null);
        $doc      = aae_parse_number($post['doc_fee'] ?? null);
        $vehicle  = aae_parse_number($post['vehicle_fee'] ?? null);
        $delivery = aae_parse_number($post['delivery_fee'] ?? null);

        $fob      = $fob === null ? 0 : max(0, $fob);
        $doc      = $doc === null ? 0 : max(0, $doc);
        $vehicle  = $vehicle === null ? 0 : max(0, $vehicle);
        $delivery = $delivery === null ? 0 : max(0, $delivery);

        $calculated = aae_calculate_prices([
            'fob_price'    => $fob,
            'doc_fee'      => $doc,
            'vehicle_fee'  => $vehicle,
            'delivery_fee' => $delivery,
            'commission'   => aae_parse_number($post['commission'] ?? null) ?? 0,
        ], $rates);

        $price_eur = aae_parse_number($post['price_eur'] ?? null);

        if ($price_eur === null) {
            $price_eur = $calculated['price_eur'];
        }

        $price_eur  = max(0, $price_eur);
        $commission = max(0, $calculated['commission']);

        $total_ttc = aae_parse_number($post['total_ttc'] ?? null);

        if ($total_ttc === null) {
            $total_ttc = $price_eur + $commission;
        }

        $total_ttc = max(0, $total_ttc);

        $price_dzd = aae_parse_number($post['price_dzd'] ?? null);

        if ($price_dzd === null) {
            $price_dzd = $total_ttc * (float) $rates['dzd_rate'];
        }

        $price_dzd = max(0, $price_dzd);

        $price_millions = aae_parse_number($post['price_millions'] ?? null);

        if ($price_millions === null) {
            $price_millions = $price_dzd / 10000;
        }

        $price_millions = max(0, $price_millions);

        $customs = aae_parse_number($post['customs_estimate'] ?? null);
        $customs = $customs === null ? 0 : max(0, $customs);

        $brand = trim((string) ($post['brand'] ?? ''));
        $model = trim((string) ($post['model'] ?? ''));
        $year  = aae_parse_year($post['model_year'] ?? '');

        $status = in_array($post['status'] ?? '', aae_statuses(), true)
            ? $post['status']
            : 'available';

        $destination = in_array($post['destination'] ?? '', aae_destinations(), true)
            ? $post['destination']
            : 'algeria';

        $client_id = (int) ($post['client_id'] ?? 0);

        if ($client_id && ! $this->aae_model->get_client($client_id)) {
            $client_id = 0;
        }

        if (staff_can('view', 'invoices')) {
            $invoice_id = (int) ($post['invoice_id'] ?? 0);

            if ($invoice_id) {
                $invoice = $this->db->select('id, clientid')
                    ->where('id', $invoice_id)
                    ->get(db_prefix() . 'invoices')->row();

                if (! $invoice) {
                    $invoice_id = 0;
                } elseif ($client_id && (int) $invoice->clientid !== $client_id) {
                    $invoice_id = 0;
                } elseif (! $client_id) {
                    $client_id = (int) $invoice->clientid;
                }
            }
        } else {
            $invoice_id = $existing ? (int) $existing->invoice_id : 0;
        }

        return [
            'brand'            => $brand,
            'model'            => $model,
            'engine'           => trim((string) ($post['engine'] ?? '')),
            'model_year'       => $year,
            'color'            => trim((string) ($post['color'] ?? '')),
            'fob_price'        => $fob,
            'doc_fee'          => $doc,
            'vehicle_fee'      => $vehicle,
            'delivery_fee'     => $delivery,
            'total_usd'        => $calculated['total_usd'],
            'price_eur'        => $price_eur,
            'commission'       => $commission,
            'total_ttc'        => $total_ttc,
            'price_dzd'        => $price_dzd,
            'price_millions'   => $price_millions,
            'customs_estimate' => $customs,
            'link'             => trim((string) ($post['link'] ?? '')),
            'quantity'         => max(0, (int) ($post['quantity'] ?? 0)),
            'status'           => $status,
            'destination'      => $destination,
            'client_id'        => $client_id,
            'invoice_id'       => $invoice_id,
            'notes'            => trim((string) ($post['notes'] ?? '')),
            'import_key'       => aae_make_import_key($brand, $model, $year),
        ];
    }

    private function car_defaults()
    {
        $defaults = aae_get_form_defaults();

        return [
            'id'               => 0,
            'brand'            => '',
            'model'            => '',
            'engine'           => '',
            'model_year'       => 0,
            'color'            => '',
            'fob_price'        => '',
            'doc_fee'          => $defaults['doc_fee'],
            'vehicle_fee'      => $defaults['vehicle_fee'],
            'delivery_fee'     => $defaults['delivery_fee'],
            'total_usd'        => '',
            'price_eur'        => '',
            'commission'       => $defaults['commission'],
            'total_ttc'        => '',
            'price_dzd'        => '',
            'price_millions'   => '',
            'customs_estimate' => '',
            'link'             => '',
            'quantity'         => 1,
            'status'           => 'available',
            'destination'      => 'algeria',
            'client_id'        => 0,
            'invoice_id'       => 0,
            'notes'            => '',
            'source'           => 'manual',
        ];
    }

    private function json_response($data)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));

        return;
    }
}
