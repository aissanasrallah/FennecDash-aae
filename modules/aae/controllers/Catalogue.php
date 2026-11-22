<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Catalogue extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('aae/aae_model');
        $this->load->helper('aae/aae');
    }

    /**
     * Catalogue listing page
     */
    public function index()
    {
        if (staff_cant('view', 'aae')) {
            access_denied('aae');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('aae', 'catalogue/table'));
        }

        $data['title'] = _l('aae_catalogue');
        $data['company_name'] = aae_get_option('aae_company_name', 'Algerie Auto Export');
        $data['stats'] = $this->aae_model->get_catalogue_stats();
        $data['brands'] = $this->aae_model->get_catalogue_brands();
        $data['brands_filter'] = $this->input->get('brand') ?: '';

        // Get filtered list
        $filters = [];
        if ($data['brands_filter']) {
            $filters['brand'] = $data['brands_filter'];
        }
        $data['catalogue'] = $this->aae_model->get_catalogue_list($filters);

        $this->load->view('aae/catalogue/manage', $data);
    }

    /**
     * Add new catalogue entry (car)
     */
    public function car($id = 0)
    {
        if (staff_cant('create', 'aae')) {
            access_denied('aae');
        }

        $car = (object) [
            'id' => 0,
            'brand' => '',
            'model' => '',
            'engine' => '',
            'model_year' => '',
            'color' => '',
            'doors' => '',
            'transmission' => '',
            'fuel' => '',
            'fob_price' => 0,
            'doc_fee' => 0,
            'vehicle_fee' => 0,
            'delivery_fee' => 0,
            'total_usd' => 0,
            'price_eur' => 0,
            'commission' => 0,
            'total_ttc' => 0,
            'price_dzd' => 0,
            'price_millions' => 0,
            'customs_estimate' => 0,
            'link' => '',
            'notes' => '',
            'source' => 'manual',
            'addedfrom' => get_staff_user_id(),
            'updatedby' => get_staff_user_id(),
            'datecreated' => date('Y-m-d H:i:s'),
            'dateupdated' => date('Y-m-d H:i:s'),
        ];

        $id = (int) ($id ?: $this->input->get('id'));
        $is_edit = $id > 0;

        if ($is_edit) {
            $car = $this->aae_model->get_catalogue($id);
            if (! $car) {
                show_404();
            }
        }

        $data['title'] = $is_edit ? _l('aae_edit_car') : _l('aae_new_car');
        $data['car'] = $car;
        $data['is_edit'] = $is_edit;
        $data['brands'] = $this->aae_model->get_catalogue_brands();
        $data['models_by_brand'] = $this->aae_model->get_catalogue_models_by_brand();
        $data['statuses'] = aae_statuses();
        $data['destinations'] = aae_destinations();
        $data['rates'] = aae_get_rates();

        $this->load->view('aae/catalogue/car', $data);
    }

    /**
     * Save catalogue entry
     */
    public function save()
    {
        if (staff_cant('edit', 'aae')) {
            access_denied('aae');
        }

        $post = $this->input->post();

        $is_edit  = (int) ($post['id'] ?? 0) > 0;
        $existing = $is_edit ? $this->aae_model->get_catalogue((int) $post['id']) : null;

        if ($is_edit && ! $existing) {
            set_alert('danger', _l('aae_car_update_failed'));
            redirect(admin_url('aae/catalogue'));
        }

        $data = [
            'brand' => trim($post['brand'] ?? ''),
            'model' => trim($post['model'] ?? ''),
            'engine' => trim($post['engine'] ?? ''),
            'model_year' => (int) ($post['model_year'] ?? 0),
            'color' => trim($post['color'] ?? ''),
            'doors' => trim($post['doors'] ?? ''),
            'transmission' => trim($post['transmission'] ?? ''),
            'fuel' => trim($post['fuel'] ?? ''),
            'fob_price' => (float) ($post['fob_price'] ?? 0),
            'doc_fee' => (float) ($post['doc_fee'] ?? 0),
            'vehicle_fee' => (float) ($post['vehicle_fee'] ?? 0),
            'delivery_fee' => (float) ($post['delivery_fee'] ?? 0),
            'total_usd' => (float) ($post['total_usd'] ?? 0),
            'price_eur' => (float) ($post['price_eur'] ?? 0),
            'commission' => (float) ($post['commission'] ?? 0),
            'total_ttc' => (float) ($post['total_ttc'] ?? 0),
            'price_dzd' => (float) ($post['price_dzd'] ?? 0),
            'price_millions' => (float) ($post['price_millions'] ?? 0),
            'customs_estimate' => (float) ($post['customs_estimate'] ?? 0),
            'link' => trim($post['link'] ?? ''),
            'notes' => trim($post['notes'] ?? ''),
            'source' => $existing ? $existing->source : ($post['source'] ?? 'manual'),
            'addedfrom' => $existing ? (int) $existing->addedfrom : get_staff_user_id(),
            'updatedby' => get_staff_user_id(),
            'datecreated' => $existing ? $existing->datecreated : date('Y-m-d H:i:s'),
            'dateupdated' => date('Y-m-d H:i:s'),
        ];

        // Build import key
        $key = aae_make_import_key(
            $data['brand'],
            $data['model'],
            aae_parse_year($data['model_year'])
        );
        $data['import_key'] = $key;

        if ($is_edit) {
            $result = $this->aae_model->update_catalogue($data, (int) $post['id']);
            if ($result) {
                set_alert('success', _l('aae_car_updated'));
            } else {
                set_alert('danger', _l('aae_car_update_failed'));
            }
        } else {
            $id = $this->aae_model->add_catalogue($data);
            if ($id) {
                set_alert('success', _l('aae_car_added'));
            } else {
                set_alert('danger', _l('aae_car_add_failed'));
            }
        }

        redirect(admin_url('aae/catalogue'));
    }

    /**
     * Delete catalogue entry
     */
    public function delete($id = 0)
    {
        if (staff_cant('delete', 'aae')) {
            access_denied('aae');
        }

        $id = (int) ($id ?? $this->input->post('id') ?? 0);
        if (! $id) {
            set_alert('warning', _l('aae_no_id_selected'));
            redirect(admin_url('aae/catalogue'));
        }

        $result = $this->aae_model->delete_catalogue($id);
        if ($result === false) {
            set_alert('warning', _l('aae_catalogue_cannot_delete'));
        } else {
            set_alert('success', _l('aae_car_deleted'));
        }

        redirect(admin_url('aae/catalogue'));
    }

    /**
     * Import vehicles to catalogue
     */
    public function import()
    {
        if (staff_cant('import', 'aae')) {
            access_denied('aae');
        }

        $data['title']     = _l('aae_import');
        $data['preview']   = $this->session->flashdata('aae_import_result');
        $data['stats']     = $this->aae_model->get_catalogue_stats();
        $data['total_rows'] = $data['stats']['models'];

        if ($this->input->post()) {
            if (! isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
                set_alert('danger', _l('aae_import_no_file'));
                redirect(admin_url('aae/catalogue/import'));
            }

            if ($_FILES['file']['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($_FILES['file']['tmp_name'])) {
                set_alert('danger', _l('aae_import_upload_error'));
                redirect(admin_url('aae/catalogue/import'));
            }

            $filename = $_FILES['file']['name'];
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (! in_array($extension, ['csv', 'txt'], true)) {
                set_alert('danger', _l('aae_import_invalid_type'));
                redirect(admin_url('aae/catalogue/import'));
            }

            if ((int) $_FILES['file']['size'] > 10 * 1024 * 1024) {
                set_alert('danger', _l('aae_import_too_large'));
                redirect(admin_url('aae/catalogue/import'));
            }

            $parsed = aae_parse_csv_file($_FILES['file']['tmp_name']);

            if (isset($parsed['error'])) {
                set_alert('danger', $parsed['error']);
                redirect(admin_url('aae/catalogue/import'));
            }

            if (empty($parsed['rows'])) {
                set_alert('danger', _l('aae_import_no_rows'));
                redirect(admin_url('aae/catalogue/import'));
            }

            $dryRun = (bool) $this->input->post('dry_run');

            $result = $this->aae_model->import_catalogue_rows($parsed['rows'], $parsed['map'], [
                'update_existing' => (bool) $this->input->post('update_existing'),
                'dry_run'         => $dryRun,
            ]);

            $result['filename'] = $filename;

            if ($dryRun) {
                $this->session->set_flashdata('aae_import_result', $result);
                redirect(admin_url('aae/catalogue/import'));
            }

            $this->aae_model->log_import([
                'filename'      => $filename,
                'total_rows'    => $result['total_rows'],
                'inserted'      => $result['inserted'],
                'updated'       => $result['updated'],
                'skipped'       => $result['skipped'],
                'failed'        => $result['failed'],
                'messages'      => !empty($result['messages']) ? json_encode($result['messages'], JSON_UNESCAPED_UNICODE) : null,
            ]);

            if ($result['failed'] > 0 || $result['skipped'] > 0) {
                set_alert('warning', _l('aae_import_completed_with_warnings', $result['inserted'], $result['updated']));
            } else {
                set_alert('success', _l('aae_import_success', $result['inserted'] + $result['updated']));
            }

            redirect(admin_url('aae/catalogue/import'));
        }

        $this->load->view('aae/catalogue/import', $data);
    }

    /**
     * Export catalogue to CSV
     */
    public function export()
    {
        if (staff_cant('view', 'aae')) {
            access_denied('aae');
        }

        $this->load->helper('aae/aae');
        $rows = $this->aae_model->get_catalogue_list();

        $headers = [
            'brand', 'model', 'engine', 'model_year', 'color', 'doors', 'transmission', 'fuel',
            'fob_price', 'doc_fee', 'vehicle_fee', 'delivery_fee', 'total_usd',
            'price_eur', 'commission', 'total_ttc', 'price_dzd', 'price_millions',
            'customs_estimate', 'link', 'notes', 'import_key', 'source'
        ];

        $filename = 'aae_catalogue_export_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
        fputcsv($output, $headers);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row->brand,
                $row->model,
                $row->engine ?? '',
                $row->model_year ?? '',
                $row->color ?? '',
                $row->doors ?? '',
                $row->transmission ?? '',
                $row->fuel ?? '',
                $row->fob_price ?? 0,
                $row->doc_fee ?? 0,
                $row->vehicle_fee ?? 0,
                $row->delivery_fee ?? 0,
                $row->total_usd ?? 0,
                $row->price_eur ?? 0,
                $row->commission ?? 0,
                $row->total_ttc ?? 0,
                $row->price_dzd ?? 0,
                $row->price_millions ?? 0,
                $row->customs_estimate ?? 0,
                $row->link ?? '',
                $row->notes ?? '',
                $row->import_key ?? '',
                $row->source ?? 'manual',
            ]);
        }

        fclose($output);
        exit;
    }
}