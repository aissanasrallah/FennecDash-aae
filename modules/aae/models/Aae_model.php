<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Aae_model extends App_Model
{
    private $cars_table;

    private $imports_table;

    private $catalogue_table;

    public function __construct()
    {
        parent::__construct();

        $this->cars_table      = db_prefix() . 'aae_cars';
        $this->imports_table   = db_prefix() . 'aae_imports';
        $this->catalogue_table = db_prefix() . 'aae_catalogue';
    }

    public function get_statuses()
    {
        return ['available', 'reserved', 'in_transit', 'delivered', 'unavailable'];
    }

    public function get_destinations()
    {
        return ['algeria', 'tunisia', 'france', 'china', 'other'];
    }

    public function get($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->get($this->cars_table)->row();
    }

    public function get_by_import_key($key)
    {
        $this->db->where('import_key', $key);
        $this->db->order_by('id', 'asc');
        $this->db->limit(1);

        return $this->db->get($this->cars_table)->row();
    }

    public function add($data)
    {
        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['dateupdated'] = $data['datecreated'];
        $data['addedfrom']   = get_staff_user_id();
        $data['updatedby']   = get_staff_user_id();

        $this->db->insert($this->cars_table, $data);

        return (int) $this->db->insert_id();
    }

    public function update($data, $id)
    {
        $data['dateupdated'] = date('Y-m-d H:i:s');
        $data['updatedby']   = get_staff_user_id();

        $this->db->where('id', (int) $id);

        $ok = $this->db->update($this->cars_table, $data);
        if ($ok && function_exists('aae_sync_storefront_price')) {
            $row = $this->get($id);
            if ($row) {
                $price = (float) ($row->total_ttc ?? 0) > 0 ? $row->total_ttc : ($row->price_eur ?? 0);
                aae_sync_storefront_price($row->brand ?? '', $row->model ?? '', $price);
            }
        }

        return $ok;
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->delete($this->cars_table);
    }

    public function update_status($id, $status)
    {
        $this->db->where('id', (int) $id);

        return $this->db->update($this->cars_table, [
            'status'      => $status,
            'dateupdated' => date('Y-m-d H:i:s'),
            'updatedby'   => get_staff_user_id(),
        ]);
    }

    public function update_quantity($id, $quantity)
    {
        $this->db->where('id', (int) $id);

        return $this->db->update($this->cars_table, [
            'quantity'    => max(0, (int) $quantity),
            'dateupdated' => date('Y-m-d H:i:s'),
            'updatedby'   => get_staff_user_id(),
        ]);
    }

    /**
     * Summary values used on the stock page.
     *
     * @return array
     */
    public function get_stats()
    {
        $select = "
            COUNT(*) AS models,
            COALESCE(SUM(CASE WHEN status = 'available' THEN quantity ELSE 0 END), 0) AS available,
            COALESCE(SUM(CASE WHEN status = 'reserved' THEN quantity ELSE 0 END), 0) AS reserved,
            COALESCE(SUM(CASE WHEN status = 'in_transit' THEN quantity ELSE 0 END), 0) AS in_transit,
            COALESCE(SUM(CASE WHEN status = 'delivered' THEN quantity ELSE 0 END), 0) AS delivered,
            COALESCE(SUM(CASE WHEN status = 'unavailable' THEN quantity ELSE 0 END), 0) AS unavailable,
            COALESCE(SUM(CASE WHEN status IN ('available', 'reserved', 'in_transit') THEN total_usd * quantity ELSE 0 END), 0) AS stock_value_usd,
            COALESCE(SUM(CASE WHEN status IN ('available', 'reserved', 'in_transit') THEN total_ttc * quantity ELSE 0 END), 0) AS stock_value_eur
        ";

        $row = $this->db->query('SELECT ' . $select . ' FROM ' . $this->cars_table)->row();

        if (! $row) {
            return [
                'models'           => 0,
                'available'        => 0,
                'reserved'         => 0,
                'in_transit'       => 0,
                'delivered'        => 0,
                'unavailable'      => 0,
                'stock_value_usd'  => 0,
                'stock_value_eur'  => 0,
            ];
        }

        return [
            'models'          => (int) $row->models,
            'available'       => (int) $row->available,
            'reserved'        => (int) $row->reserved,
            'in_transit'      => (int) $row->in_transit,
            'delivered'       => (int) $row->delivered,
            'unavailable'     => (int) $row->unavailable,
            'stock_value_usd' => (float) $row->stock_value_usd,
            'stock_value_eur' => (float) $row->stock_value_eur,
        ];
    }

    public function get_brands()
    {
        $this->db->select('brand');
        $this->db->distinct();
        $this->db->where('brand !=', '');
        $this->db->order_by('brand', 'asc');

        $rows = $this->db->get($this->cars_table)->result_array();

        return array_values(array_filter(array_column($rows, 'brand')));
    }

    /**
     * Models grouped by brand, used by the car form to suggest the models
     * that belong to the selected brand.
     *
     * @return array<string, array<int, string>>
     */
    public function get_models_by_brand()
    {
        $this->db->select('brand, model');
        $this->db->where('brand !=', '');
        $this->db->where('model !=', '');
        $this->db->order_by('brand', 'asc');
        $this->db->order_by('model', 'asc');

        $rows   = $this->db->get($this->cars_table)->result_array();
        $models = [];

        foreach ($rows as $row) {
            $brand = trim((string) $row['brand']);
            $model = trim((string) $row['model']);

            if ($brand === '' || $model === '') {
                continue;
            }

            if (! isset($models[$brand])) {
                $models[$brand] = [];
            }

            if (! in_array($model, $models[$brand], true)) {
                $models[$brand][] = $model;
            }
        }

        return $models;
    }

    public function get_clients_list()
    {
        $this->db->select('userid, company');
        $this->db->from(db_prefix() . 'clients');
        $this->db->order_by('company', 'asc');

        return $this->db->get()->result_array();
    }

    public function get_client($id)
    {
        $this->db->select('userid, company, phonenumber, default_language');
        $this->db->where('userid', (int) $id);

        return $this->db->get(db_prefix() . 'clients')->row();
    }

    public function get_invoices_for_client($client_id)
    {
        $this->db->select('id, number, prefix, number_format, date, status, total, currency');
        $this->db->where('clientid', (int) $client_id);
        $this->db->order_by('id', 'desc');

        return $this->db->get(db_prefix() . 'invoices')->result_array();
    }

    public function get_estimates_for_client($client_id)
    {
        $this->db->select('id, number, prefix, number_format, formatted_number, date, expirydate, status, total, currency');
        $this->db->where('clientid', (int) $client_id);
        $this->db->order_by('id', 'desc');

        return $this->db->get(db_prefix() . 'estimates')->result_array();
    }

    public function get_contracts_for_client($client_id)
    {
        $this->db->select('id, subject, client, contract_value, datestart, dateend, trash');
        $this->db->where('client', (int) $client_id);
        $this->db->where('trash', 0);
        $this->db->order_by('id', 'desc');

        return $this->db->get(db_prefix() . 'contracts')->result_array();
    }

    public function get_client_cars($client_id)
    {
        $this->db->where('client_id', (int) $client_id);
        $this->db->order_by('datecreated', 'desc');

        return $this->db->get($this->cars_table)->result();
    }

    public function get_all_for_export()
    {
        $this->db->select('c.*, cl.company AS client_company');
        $this->db->from($this->cars_table . ' c');
        $this->db->join(db_prefix() . 'clients cl', 'cl.userid = c.client_id', 'left');
        $this->db->order_by('c.brand', 'asc');
        $this->db->order_by('c.model', 'asc');

        return $this->db->get()->result_array();
    }

    /**
     * Resolve a vehicle from either the legacy cars table or the catalogue.
     *
     * @param int    $id
     * @param string $source "cars" or "catalogue"
     *
     * @return object|null
     */
    public function get_vehicle_by_source($id, $source = 'cars')
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        return $source === 'catalogue' ? $this->get_catalogue($id) : $this->get($id);
    }

    /**
     * Catalogue rows used by the export order form.
     *
     * @return array
     */
    public function get_all_catalogue_for_export()
    {
        $this->db->from($this->catalogue_table);
        $this->db->order_by('brand', 'asc');
        $this->db->order_by('model', 'asc');

        return $this->db->get()->result_array();
    }

    public function log_import($data)
    {
        $data['staff_id']    = get_staff_user_id();
        $data['datecreated'] = date('Y-m-d H:i:s');

        if (isset($data['messages']) && is_array($data['messages'])) {
            $data['messages'] = json_encode($data['messages'], JSON_UNESCAPED_UNICODE);
        }

        $this->db->insert($this->imports_table, $data);

        return (int) $this->db->insert_id();
    }

    public function get_imports($limit = 15)
    {
        $this->db->select($this->imports_table . '.*, CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) AS staff_name');
        $this->db->from($this->imports_table);
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . $this->imports_table . '.staff_id', 'left');
        $this->db->order_by($this->imports_table . '.id', 'desc');
        $this->db->limit((int) $limit);

        return $this->db->get()->result();
    }

    /**
     * Import parsed CSV rows.
     *
     * Existing cars keep their manual status, quantity, destination, client,
     * invoice and notes. Only catalogue/price fields are refreshed.
     *
     * @param array $rows
     * @param array $map
     * @param array $options
     *
     * @return array
     */
    public function import_rows($rows, $map, $options)
    {
        $rates = aae_get_rates();

        $default_status = in_array($options['default_status'] ?? '', $this->get_statuses(), true)
            ? $options['default_status']
            : 'available';

        $default_destination = in_array($options['default_destination'] ?? '', $this->get_destinations(), true)
            ? $options['default_destination']
            : 'algeria';

        $default_quantity = max(0, (int) ($options['default_quantity'] ?? 0));
        $update_existing  = ! empty($options['update_existing']);
        $dry_run          = ! empty($options['dry_run']);

        $result = [
            'total_rows' => 0,
            'inserted'   => 0,
            'updated'    => 0,
            'skipped'    => 0,
            'failed'     => 0,
            'messages'   => [],
            'preview'    => [],
        ];

        // Collapse duplicate make/model/year rows first so the last row wins,
        // even when refreshing existing vehicles is disabled.
        $rows = $this->collapse_duplicate_rows($rows, $map, $result);

        if (! $dry_run) {
            $this->db->trans_start();
        }

        foreach ($rows as $row) {
            $line  = (int) $row['line'];
            $cells = $row['cells'];

            $brand = trim(aae_csv_cell($cells, $map, 'brand'));
            $model = trim(aae_csv_cell($cells, $map, 'model'));

            if ($brand === '' || $model === '') {
                $this->add_import_message($result, $line, 'skipped', _l('aae_import_skip_missing'));
                $result['skipped']++;

                continue;
            }

            $fob = aae_parse_number(aae_csv_cell($cells, $map, 'fob_price'));

            if ($fob === null || $fob <= 0) {
                $this->add_import_message($result, $line, 'skipped', _l('aae_import_skip_fob'));
                $result['skipped']++;

                continue;
            }

            $payload = $this->build_import_payload($cells, $map, $rates);
            $key     = aae_make_import_key($payload['brand'], $payload['model'], $payload['model_year']);

            $existing = $this->get_by_import_key($key);
            $action   = 'insert';

            if ($existing) {
                if (! $update_existing) {
                    $this->add_import_message($result, $line, 'skipped', _l('aae_import_skip_existing'));
                    $result['skipped']++;

                    if (count($result['preview']) < 100) {
                        $result['preview'][] = $this->preview_row($line, $payload, 'skip');
                    }

                    continue;
                }

                $action = 'update';
            }

            if ($dry_run) {
                if ($action === 'update') {
                    $result['updated']++;
                } else {
                    $result['inserted']++;
                }
            } else {
                if ($existing) {
                    $this->update($payload, $existing->id);
                    $result['updated']++;
                } else {
                    $payload['import_key']  = $key;
                    $payload['quantity']    = $default_quantity;
                    $payload['status']      = $default_status;
                    $payload['destination'] = $default_destination;
                    $payload['source']      = 'csv';

                    $insert_id = $this->add($payload);

                    if ($insert_id) {
                        $result['inserted']++;
                    } else {
                        $result['failed']++;
                        $this->add_import_message($result, $line, 'failed', _l('aae_import_row_failed'));

                        continue;
                    }
                }
            }

            if (count($result['preview']) < 100) {
                $result['preview'][] = $this->preview_row($line, $payload, $action);
            }
        }

        if (! $dry_run) {
            $this->db->trans_complete();

            if ($this->db->trans_status() === false) {
                $result['failed']  += $result['inserted'] + $result['updated'];
                $result['inserted'] = 0;
                $result['updated']  = 0;

                $this->add_import_message($result, 0, 'failed', _l('aae_import_db_error'));
            }
        }

        return $result;
    }

    /**
     * Remove rows that share the same normalized make/model/year.
     *
     * The last occurrence wins (that is usually the most recent price in the
     * supplier sheet). Removed rows are reported as skipped so the totals add
     * up to the number of parsed rows.
     *
     * @param array $rows
     * @param array $map
     * @param array $result
     *
     * @return array
     */
    private function collapse_duplicate_rows($rows, $map, &$result)
    {
        $keys         = [];
        $last_by_key  = [];

        foreach ($rows as $index => $row) {
            $result['total_rows']++;

            $brand = trim(aae_csv_cell($row['cells'], $map, 'brand'));
            $model = trim(aae_csv_cell($row['cells'], $map, 'model'));

            if ($brand === '' || $model === '') {
                continue;
            }

            $fob = aae_parse_number(aae_csv_cell($row['cells'], $map, 'fob_price'));

            if ($fob === null || $fob <= 0) {
                continue;
            }

            $key = aae_make_import_key(
                $brand,
                $model,
                aae_parse_year(aae_csv_cell($row['cells'], $map, 'model_year'))
            );

            $keys[$index]       = $key;
            $last_by_key[$key]  = $index;
        }

        $collapsed = [];

        foreach ($rows as $index => $row) {
            if (isset($keys[$index]) && $last_by_key[$keys[$index]] !== $index) {
                $this->add_import_message($result, $row['line'], 'skipped', _l('aae_import_skip_duplicate'));
                $result['skipped']++;

                continue;
            }

            $collapsed[] = $row;
        }

        return $collapsed;
    }

    private function build_import_payload($cells, $map, $rates)
    {
        $fob      = (float) aae_parse_number(aae_csv_cell($cells, $map, 'fob_price'));
        $doc      = (float) aae_parse_number(aae_csv_cell($cells, $map, 'doc_fee'));
        $vehicle  = (float) aae_parse_number(aae_csv_cell($cells, $map, 'vehicle_fee'));
        $delivery = (float) aae_parse_number(aae_csv_cell($cells, $map, 'delivery_fee'));

        $total_usd = aae_parse_number(aae_csv_cell($cells, $map, 'total_usd'));

        if ($total_usd === null || $total_usd <= 0) {
            $total_usd = $fob + $doc + $vehicle + $delivery;
        }

        $commission = aae_parse_number(aae_csv_cell($cells, $map, 'commission'));

        if ($commission === null) {
            $commission = 0;
        }

        $price_eur = aae_parse_number(aae_csv_cell($cells, $map, 'price_eur'));

        if ($price_eur === null || $price_eur <= 0) {
            $price_eur = $total_usd * (1 + ((float) $rates['eur_markup'] / 100));
        }

        $total_ttc = aae_parse_number(aae_csv_cell($cells, $map, 'total_ttc'));

        if ($total_ttc === null || $total_ttc <= 0) {
            $total_ttc = $price_eur + $commission;
        }

        $price_dzd = aae_parse_number(aae_csv_cell($cells, $map, 'price_dzd'));

        if ($price_dzd === null || $price_dzd <= 0) {
            $price_dzd = $total_ttc * (float) $rates['dzd_rate'];
        }

        $price_millions = aae_parse_number(aae_csv_cell($cells, $map, 'price_millions'));

        if ($price_millions === null || $price_millions <= 0) {
            $price_millions = $price_dzd / 10000;
        }

        $customs = aae_parse_number(aae_csv_cell($cells, $map, 'customs_estimate'));

        if ($customs === null) {
            $customs = 0;
        }

        return [
            'brand'            => trim(aae_csv_cell($cells, $map, 'brand')),
            'model'            => trim(aae_csv_cell($cells, $map, 'model')),
            'engine'           => trim(aae_csv_cell($cells, $map, 'engine')),
            'model_year'       => aae_parse_year(aae_csv_cell($cells, $map, 'model_year')),
            'color'            => trim(aae_csv_cell($cells, $map, 'color')),
            'fob_price'        => $fob,
            'doc_fee'          => $doc,
            'vehicle_fee'      => $vehicle,
            'delivery_fee'     => $delivery,
            'total_usd'        => $total_usd,
            'price_eur'        => $price_eur,
            'commission'       => $commission,
            'total_ttc'        => $total_ttc,
            'price_dzd'        => $price_dzd,
            'price_millions'   => $price_millions,
            'customs_estimate' => $customs,
            'link'             => trim(aae_csv_cell($cells, $map, 'link')),
        ];
    }

    private function preview_row($line, $payload, $action)
    {
        return [
            'line'         => (int) $line,
            'brand'        => $payload['brand'],
            'model'        => $payload['model'],
            'model_year'   => $payload['model_year'],
            'fob_price'    => $payload['fob_price'],
            'price_eur'    => $payload['price_eur'],
            'price_dzd'    => $payload['price_dzd'],
            'action'       => $action,
        ];
    }

    private function add_import_message(&$result, $line, $type, $message)
    {
        if (count($result['messages']) < 200) {
            $result['messages'][] = [
                'line'    => (int) $line,
                'type'    => $type,
                'message' => $message,
            ];
        }
    }

    /**
     * Import parsed CSV rows into catalogue.
     *
     * Existing catalogue entries keep their data. Only catalogue/price fields are refreshed.
     *
     * @param array $rows
     * @param array $map
     * @param array $options
     *
     * @return array
     */
    public function import_catalogue_rows($rows, $map, $options)
    {
        $rates = aae_get_rates();

        $update_existing = ! empty($options['update_existing']);
        $dry_run         = ! empty($options['dry_run']);

        $result = [
            'total_rows' => 0,
            'inserted'   => 0,
            'updated'    => 0,
            'skipped'    => 0,
            'failed'     => 0,
            'messages'   => [],
        ];

        // Collapse duplicate make/model/year rows first so the last row wins,
        // even when refreshing existing vehicles is disabled.
        $rows = $this->collapse_duplicate_rows($rows, $map, $result);

        if (! $dry_run) {
            $this->db->trans_start();
        }

        foreach ($rows as $row) {
            $line  = (int) $row['line'];
            $cells = $row['cells'];

            $brand = trim(aae_csv_cell($cells, $map, 'brand'));
            $model = trim(aae_csv_cell($cells, $map, 'model'));

            if ($brand === '' || $model === '') {
                $this->add_import_message($result, $line, 'skipped', _l('aae_import_skip_missing'));
                $result['skipped']++;
                continue;
            }

            $fob = aae_parse_number(aae_csv_cell($cells, $map, 'fob_price'));

            if ($fob === null || $fob <= 0) {
                $this->add_import_message($result, $line, 'skipped', _l('aae_import_skip_fob'));
                $result['skipped']++;
                continue;
            }

            // build_import_payload() coerces empty numeric cells to 0 and
            // fills derived prices, so NOT NULL catalogue columns stay valid.
            $payload = $this->build_import_payload($cells, $map, $rates);
            $key     = aae_make_import_key($payload['brand'], $payload['model'], $payload['model_year']);

            $existing = $this->get_catalogue_by_import_key($key);

            if ($existing && ! $update_existing) {
                $this->add_import_message($result, $line, 'skipped', _l('aae_import_skip_existing'));
                $result['skipped']++;
                continue;
            }

            if ($dry_run) {
                if ($existing) {
                    $result['updated']++;
                } else {
                    $result['inserted']++;
                }

                continue;
            }

            if ($existing) {
                // Never blank an existing note when the CSV has no notes column.
                $notes = trim(aae_csv_cell($cells, $map, 'notes'));

                if ($notes !== '') {
                    $payload['notes'] = $notes;
                }

                $saved = $this->update_catalogue($payload, $existing->id);

                if ($saved === false) {
                    $result['failed']++;
                    $this->add_import_message($result, $line, 'failed', _l('aae_import_row_failed'));

                    continue;
                }

                $result['updated']++;
                $action = 'updated';
            } else {
                $payload['notes']      = trim(aae_csv_cell($cells, $map, 'notes'));
                $payload['source']     = 'csv';
                $payload['import_key'] = $key;

                $insert_id = $this->add_catalogue($payload);

                if (! $insert_id) {
                    $result['failed']++;
                    $this->add_import_message($result, $line, 'failed', _l('aae_import_row_failed'));

                    continue;
                }

                $result['inserted']++;
                $action = 'inserted';
            }

            if (count($result['messages']) < 200) {
                $result['messages'][] = [
                    'line'    => $line,
                    'type'    => $action,
                    'message' => $action === 'updated' ? _l('aae_import_updated') : _l('aae_import_new'),
                ];
            }
        }

        if (! $dry_run) {
            $this->db->trans_complete();

            if ($this->db->trans_status() === false) {
                $result['failed']  += $result['inserted'] + $result['updated'];
                $result['inserted'] = 0;
                $result['updated']  = 0;
                $this->add_import_message($result, 0, 'failed', _l('aae_import_db_error'));
            }
        }

        return $result;
    }

    /**
     * Get one catalogue entry by id.
     *
     * @param int $id
     *
     * @return object|null
     */
    public function get_catalogue($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->get($this->catalogue_table)->row();
    }

    /**
     * Get one catalogue entry by its import key.
     *
     * @param string $key
     *
     * @return object|null
     */
    public function get_catalogue_by_import_key($key)
    {
        if ($key === null || $key === '') {
            return null;
        }

        $this->db->where('import_key', $key);
        $this->db->order_by('id', 'asc');
        $this->db->limit(1);

        return $this->db->get($this->catalogue_table)->row();
    }

    /**
     * Insert a catalogue entry.
     *
     * @param array $data
     *
     * @return int
     */
    public function add_catalogue($data)
    {
        $data['datecreated'] = $data['datecreated'] ?? date('Y-m-d H:i:s');
        $data['dateupdated'] = date('Y-m-d H:i:s');
        $data['addedfrom']   = $data['addedfrom'] ?? get_staff_user_id();
        $data['updatedby']   = get_staff_user_id();

        $this->db->insert($this->catalogue_table, $data);

        return (int) $this->db->insert_id();
    }

    /**
     * Update a catalogue entry.
     *
     * @param array $data
     * @param int   $id
     *
     * @return bool
     */
    public function update_catalogue($data, $id)
    {
        $data['dateupdated'] = date('Y-m-d H:i:s');
        $data['updatedby']   = get_staff_user_id();

        $this->db->where('id', (int) $id);

        $ok = $this->db->update($this->catalogue_table, $data);
        if ($ok && function_exists('aae_sync_storefront_price')) {
            $row = $this->get_catalogue($id);
            if ($row) {
                $price = (float) ($row->total_ttc ?? 0) > 0 ? $row->total_ttc : ($row->price_eur ?? 0);
                aae_sync_storefront_price($row->brand ?? '', $row->model ?? '', $price);
            }
        }

        return $ok;
    }

    /**
     * Delete a catalogue entry unless it is used by an export order.
     *
     * @param int $id
     *
     * @return bool|null false when the entry is in use
     */
    public function delete_catalogue($id)
    {
        $id = (int) $id;

        if (! $id) {
            return false;
        }

        if ($this->db->table_exists(db_prefix() . 'aae_order_vehicles')) {
            $used = $this->db->where('catalogue_id', $id)
                ->count_all_results(db_prefix() . 'aae_order_vehicles');

            if ($used > 0) {
                return false;
            }
        }

        $this->db->where('id', $id);

        return $this->db->delete($this->catalogue_table);
    }

    /**
     * Get catalogue statistics
     *
     * @return array
     */
    public function get_catalogue_stats()
    {
        $select = "
            COUNT(*) AS models,
            COUNT(DISTINCT brand) AS brands,
            COALESCE(AVG(price_eur), 0) AS avg_price_eur
        ";

        $row = $this->db->query('SELECT ' . $select . ' FROM ' . $this->catalogue_table)->row();

        if (! $row) {
            return [
                'models'        => 0,
                'brands'        => 0,
                'avg_price_eur' => 0,
            ];
        }

        return [
            'models'        => (int) $row->models,
            'brands'        => (int) $row->brands,
            'avg_price_eur' => (float) $row->avg_price_eur,
        ];
    }

    /**
     * Get brands from catalogue.
     */
    public function get_catalogue_brands()
    {
        $this->db->select('brand');
        $this->db->distinct();
        $this->db->where('brand !=', '');
        $this->db->order_by('brand', 'asc');

        $rows = $this->db->get($this->catalogue_table)->result_array();

        return array_values(array_filter(array_column($rows, 'brand')));
    }

    /**
     * Get models grouped by brand from catalogue.
     */
    public function get_catalogue_models_by_brand()
    {
        $this->db->select('brand, model');
        $this->db->where('brand !=', '');
        $this->db->where('model !=', '');
        $this->db->order_by('brand', 'asc');
        $this->db->order_by('model', 'asc');

        $rows   = $this->db->get($this->catalogue_table)->result_array();
        $models = [];

        foreach ($rows as $row) {
            $brand = trim((string) $row['brand']);
            $model = trim((string) $row['model']);
            if ($brand === '' || $model === '') continue;
            if (! isset($models[$brand])) $models[$brand] = [];
            if (! in_array($model, $models[$brand], true)) $models[$brand][] = $model;
        }

        return $models;
    }

    /**
     * Get catalogue entries for listing with filters.
     */
    public function get_catalogue_list($filters = [])
    {
        $this->db->select('c.*, 
            (SELECT COUNT(*) FROM ' . db_prefix() . 'aae_order_vehicles ov WHERE ov.catalogue_id = c.id) AS order_count');
        $this->db->from($this->catalogue_table . ' c');
        $this->db->order_by('c.brand', 'asc');
        $this->db->order_by('c.model', 'asc');
        $this->db->order_by('c.model_year', 'desc');

        if (!empty($filters['brand'])) {
            $this->db->where('c.brand', $filters['brand']);
        }
        if (!empty($filters['model'])) {
            $this->db->where('c.model', $filters['model']);
        }

        return $this->db->get()->result();
    }
}
