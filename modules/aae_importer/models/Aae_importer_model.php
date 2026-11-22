<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * AAE Import Manager - Model
 */

class Aae_importer_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /* =====================================================================
     *  STATUTS
     * ===================================================================== */

    /**
     * Statuts catalogue d'un vehicule (liste courte de vente)
     */
    public function vehicle_statuses_colors()
    {
        return [
            'catalogue'  => ['name' => _l('aae_vstat_catalogue'),  'color' => '#6c757d'],
            'disponible' => ['name' => _l('aae_vstat_disponible'), 'color' => '#1bc5bd'],
            'commande'   => ['name' => _l('aae_vstat_commande'),   'color' => '#03a9f4'],
            'reserve'    => ['name' => _l('aae_vstat_reserve'),    'color' => '#f0ad4e'],
            'vendu'      => ['name' => _l('aae_vstat_vendu'),      'color' => '#28a745'],
        ];
    }

    /**
     * Statuts d'operations d'import (pipeline complet)
     */
    public function operation_statuses_colors()
    {
        return [
            'demande'    => ['name' => _l('aae_opstat_demande'),    'color' => '#6c757d'],
            'commande'   => ['name' => _l('aae_opstat_commande'),   'color' => '#009efb'],
            'production' => ['name' => _l('aae_opstat_production'), 'color' => '#1989fa'],
            'embarque'   => ['name' => _l('aae_opstat_embarque'),   'color' => '#8862EA'],
            'transit'    => ['name' => _l('aae_opstat_transit'),    'color' => '#f0ad4e'],
            'arrive'     => ['name' => _l('aae_opstat_arrive'),     'color' => '#8e24aa'],
            'douane'     => ['name' => _l('aae_opstat_douane'),     'color' => '#ec407a'],
            'disponible' => ['name' => _l('aae_opstat_disponible'), 'color' => '#1bc5bd'],
            'livre'      => ['name' => _l('aae_opstat_livre'),      'color' => '#28a745'],
            'annule'     => ['name' => _l('aae_opstat_annule'),     'color' => '#dc3545'],
        ];
    }

    /** Date de reference attachee a un statut d'operation (colonne de la table) */
    public function operation_status_date_field($status)
    {
        return [
            'commande'   => 'order_date',
            'production' => 'production_date',
            'embarque'   => 'departure_date',
            'arrive'     => 'arrival_date',
            'douane'     => 'customs_date',
            'livre'      => 'delivery_date',
        ];
    }

    /* =====================================================================
     *  VEHICULES (Catalogue)
     * ===================================================================== */

    public function quote_bases()
    {
        return [
            'ttc_dzd'       => _l('aae_qbase_ttc_dzd'),
            'total_usd'     => _l('aae_qbase_total_usd'),
            'total_ttc_eur' => _l('aae_qbase_ttc_eur'),
            'fob_usd'       => _l('aae_qbase_fob'),
        ];
    }

    public function get_vehicle($id)
    {
        $vehicle = $this->db->where('id', $id)->get(db_prefix() . 'aae_vehicles')->row();
        if ($vehicle) {
            $statuses = $this->vehicle_statuses_colors();
            if (isset($statuses[$vehicle->status])) {
                $vehicle->status_name  = $statuses[$vehicle->status]['name'];
                $vehicle->status_color = $statuses[$vehicle->status]['color'];
            }
        }

        return $vehicle;
    }

    public function get_vehicles($search = '', $status = '', $marque = '', $limit = null, $offset = 0, $order_by = 'id', $order_dir = 'desc')
    {
        if ($search !== '') {
            $this->db->group_start();
            $this->db->where('marque LIKE "%' . $this->db->escape_str($search) . '%"');
            $this->db->or_where('modele LIKE "%' . $this->db->escape_str($search) . '%"');
            $this->db->or_where('moteur LIKE "%' . $this->db->escape_str($search) . '%"');
            $this->db->or_where('couleur LIKE "%' . $this->db->escape_str($search) . '%"');
            $this->db->group_end();
        }
        if ($status !== '') {
            $this->db->where('status', $status);
        }
        if ($marque !== '') {
            $this->db->where('marque', $marque);
        }
        if ($limit !== null) {
            $this->db->limit($limit, $offset);
        }
        $allowed = ['id', 'marque', 'modele', 'moteur', 'annee', 'total_usd', 'total_ttc_eur', 'ttc_dzd'];
        if (in_array($order_by, $allowed)) {
            $this->db->order_by($order_by, $order_dir === 'asc' ? 'asc' : 'desc');
        }

        return $this->db->get(db_prefix() . 'aae_vehicles')->result_array();
    }

    public function get_vehicles_count($search = '', $status = '', $marque = '')
    {
        if ($search !== '') {
            $this->db->group_start();
            $this->db->where('marque LIKE "%' . $this->db->escape_str($search) . '%"');
            $this->db->or_where('modele LIKE "%' . $this->db->escape_str($search) . '%"');
            $this->db->or_where('moteur LIKE "%' . $this->db->escape_str($search) . '%"');
            $this->db->or_where('couleur LIKE "%' . $this->db->escape_str($search) . '%"');
            $this->db->group_end();
        }
        if ($status !== '') {
            $this->db->where('status', $status);
        }
        if ($marque !== '') {
            $this->db->where('marque', $marque);
        }

        return $this->db->count_all_results(db_prefix() . 'aae_vehicles');
    }

    public function get_marques()
    {
        return $this->db->select('DISTINCT(marque) as marque')
            ->where('marque != ""')
            ->order_by('marque')
            ->get(db_prefix() . 'aae_vehicles')
            ->result_array();
    }

    /**
     * Prepare + recalcule les donnees d'un vehicule a partir du post
     */
    public function prepare_vehicle_data($data, $manual = false)
    {
        $vehicle = [];

        $vehicle['marque']  = isset($data['marque']) ? trim($data['marque']) : '';
        $vehicle['modele']  = isset($data['modele']) ? trim($data['modele']) : '';
        $vehicle['moteur']  = isset($data['moteur']) ? trim($data['moteur']) : '';
        $vehicle['annee']   = isset($data['annee']) && trim((string)$data['annee']) !== '' ? (int)$data['annee'] : null;
        $vehicle['couleur'] = isset($data['couleur']) ? trim($data['couleur']) : '';
        $vehicle['lien']    = isset($data['lien']) ? trim($data['lien']) : '';
        $vehicle['status']  = isset($data['status']) && array_key_exists($data['status'], $this->vehicle_statuses_colors()) ? $data['status'] : 'disponible';
        $vehicle['notes']   = isset($data['notes']) ? trim($data['notes']) : null;

        if ($manual) {
            // Saisie manuelle des montants (fichier client sans FOB)
            $manual_keys = ['fob_usd', 'doc_translation', 'vehicle_translation', 'shipping_usd',
                'total_usd', 'price_eur', 'commission_eur', 'total_ttc_eur', 'ttc_dzd', 'customs_rate'];
            foreach ($manual_keys as $k) {
                $vehicle[$k] = isset($data[$k]) && $data[$k] !== '' ? (float)$data[$k] : null;
            }
            $vehicle['eur_rate']        = isset($data['eur_rate']) && $data['eur_rate'] !== '' ? (float)$data['eur_rate'] : null;
            $vehicle['eur_fee_percent'] = isset($data['eur_fee_percent']) && $data['eur_fee_percent'] !== '' ? (float)$data['eur_fee_percent'] : null;
            $vehicle['dzd_rate']        = isset($data['dzd_rate']) && $data['dzd_rate'] !== '' ? (float)$data['dzd_rate'] : null;
        } else {
            $vehicle['fob_usd']             = isset($data['fob_usd']) ? (float)$data['fob_usd'] : 0;
            $vehicle['doc_translation']     = isset($data['doc_translation']) ? (float)$data['doc_translation'] : 0;
            $vehicle['vehicle_translation'] = isset($data['vehicle_translation']) ? (float)$data['vehicle_translation'] : 0;
            $vehicle['shipping_usd']        = isset($data['shipping_usd']) ? (float)$data['shipping_usd'] : 0;
            $vehicle['commission_eur']      = isset($data['commission_eur']) ? (float)$data['commission_eur'] : (float)get_option('aae_importer_default_commission');
            $vehicle['eur_rate']            = isset($data['eur_rate']) && $data['eur_rate'] !== '' ? (float)$data['eur_rate'] : (float)get_option('aae_importer_eur_rate');
            $vehicle['eur_fee_percent']     = isset($data['eur_fee_percent']) && $data['eur_fee_percent'] !== '' ? (float)$data['eur_fee_percent'] : (float)get_option('aae_importer_eur_fee_percent');
            $vehicle['dzd_rate']            = isset($data['dzd_rate']) && $data['dzd_rate'] !== '' ? (float)$data['dzd_rate'] : (float)get_option('aae_importer_dzd_rate');
            $vehicle['customs_rate']        = isset($data['customs_rate']) && $data['customs_rate'] !== '' ? (float)$data['customs_rate'] : null;

            $this->recalculate($vehicle);
        }

        return $vehicle;
    }

    /**
     * Recalcule total_usd => price_eur => total_ttc_eur => ttc_dzd
     * (meme logique que le fichier Excel du catalogue)
     */
    public function recalculate(&$vehicle)
    {
        $total_usd = (float)$vehicle['fob_usd']
            + (float)$vehicle['doc_translation']
            + (float)$vehicle['vehicle_translation']
            + (float)$vehicle['shipping_usd'];

        $eur_rate    = $vehicle['eur_rate'] > 0 ? $vehicle['eur_rate'] : 0.892;
        $fee         = $vehicle['eur_fee_percent'] > 0 ? $vehicle['eur_fee_percent'] : 0;
        $price_eur   = $total_usd * $eur_rate * (1 + $fee / 100);
        $ttc_eur     = $price_eur + (float)$vehicle['commission_eur'];
        $dzd_rate    = $vehicle['dzd_rate'] > 0 ? $vehicle['dzd_rate'] : 282;
        $ttc_dzd     = $ttc_eur * $dzd_rate;

        $vehicle['total_usd']     = round($total_usd, 2);
        $vehicle['price_eur']     = round($price_eur, 2);
        $vehicle['total_ttc_eur'] = round($ttc_eur, 2);
        $vehicle['ttc_dzd']       = round($ttc_dzd, 2);

        return $vehicle;
    }

    public function add_vehicle($data, $manual = false)
    {
        $vehicle = $this->prepare_vehicle_data($data, $manual);

        $vehicle['created_by']   = get_staff_user_id();
        $vehicle['date_created'] = date('Y-m-d H:i:s');
        $vehicle['date_updated'] = date('Y-m-d H:i:s');

        $this->db->insert(db_prefix() . 'aae_vehicles', $vehicle);
        $id = $this->db->insert_id();

        log_activity('Nouveau véhicule ajouté au catalogue AAE [ID: ' . $id . '] ' . $vehicle['marque'] . ' ' . $vehicle['modele']);

        return $id;
    }

    public function update_vehicle($id, $data, $manual = false)
    {
        $vehicle = $this->prepare_vehicle_data($data, $manual);

        $vehicle['date_updated'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'aae_vehicles', $vehicle);

        log_activity('Véhicule catalogue AAE modifié [ID: ' . $id . ']');

        $updated = $this->get_vehicle($id);
        if ($updated) {
            $this->sync_price_to_storefront($updated);
        }

        return $updated;
    }

    /**
     * Synchronise automatiquement le prix du vehicule vers le site Algérie Auto Export
     */
    public function sync_price_to_storefront($vehicle)
    {
        $defaultSyncUrl = (defined('APP_BASE_URL') && strpos(APP_BASE_URL, 'localhost') === false)
            ? 'https://algerie-autoexport.com/aae/sync-car-price'
            : 'http://localhost/algerie-autoexport/aae/sync-car-price';
        $url = getenv('AAE_STOREFRONT_PRICE_SYNC_URL') ?: $defaultSyncUrl;
        $secret = getenv('AAE_WEBHOOK_SECRET') ?: 'aae-fennec-dash-2026';

        $price = (float) ($vehicle->total_ttc_eur ?? 0);
        if ($price <= 0) {
            $price = (float) ($vehicle->price_eur ?? 0);
        }
        if ($price <= 0 || empty($vehicle->modele)) {
            return false;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-AAE-Webhook-Secret: ' . $secret,
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'brand' => (string) ($vehicle->marque ?? ''),
                'model' => (string) $vehicle->modele,
                'sale_price' => $price,
            ]),
        ]);
        $res = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code >= 200 && $code < 300;
    }

    public function delete_vehicle($id)
    {
        $ops = $this->db->where('vehicle_id', $id)->count_all_results(db_prefix() . 'aae_operations');
        if ($ops > 0) {
            return ['success' => false, 'message' => _l('aae_cannot_delete_vehicle_in_ops', $ops)];
        }
        $this->db->where('id', $id)->delete(db_prefix() . 'aae_vehicles');
        log_activity('Véhicule catalogue AAE supprimé [ID: ' . $id . ']');

        return ['success' => true];
    }

    /* =====================================================================
     *  IMPORT CSV
     * ===================================================================== */

    /**
     * Importe le catalogue depuis le fichier CSV (format "CATALOGUE VOITURE - CHINOISES")
     */
    public function import_csv($filepath, $delimiter = ',', $replace = false, $default_status = 'disponible')
    {
        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = 0;

        if ($replace) {
            $this->db->truncate(db_prefix() . 'aae_vehicles');
        }

        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 1];
        }

        $headerIndex = null;
        $columns     = null;
        $lineNumber  = 0;

        // Contenu du fichier (pour le nettoyage encodage)
        $contents = stream_get_contents($handle);
        fclose($handle);

        // Suppression BOM
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        // Normalisation des fins de ligne
        $contents = str_replace("\r\n", "\n", $contents);

        $rows = explode("\n", $contents);

        foreach ($rows as $row_raw) {
            $lineNumber++;
            $cells = str_getcsv($row_raw, $delimiter);

            if (count(array_filter($cells, function ($v) {
                return trim($v) !== '';
            })) === 0) {
                continue;
            }

            // Recherche de la ligne d'en-tete (MARQUE, MODELE, ...)
            if ($headerIndex === null) {
                $found = false;
                foreach ($cells as $c) {
                    $h = aae_importer_slug_header($c);
                    if ($h !== '' && aae_importer_contains($h, 'marque')) {
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    $headerIndex = $lineNumber;
                    $columns     = $this->map_csv_columns($cells);
                    continue;
                }
                continue;
            }

            if (count($cells) < 4) {
                continue;
            }

            $get = function ($key) use ($cells, $columns) {
                if (isset($columns[$key]) && isset($cells[$columns[$key]])) {
                    return trim($cells[$columns[$key]]);
                }

                return '';
            };

            $marque = $get('marque');
            $modele = $get('modele');

            if ($marque === '' && $modele === '') {
                continue;
            }

            $vehicle = [
                'marque'               => $marque,
                'modele'               => $modele,
                'moteur'               => $get('moteur'),
                'annee'                => aae_importer_money_value($get('annee')) ?: null,
                'couleur'              => $get('couleur'),
                'fob_usd'              => aae_importer_money_value($get('fob_usd')),
                'doc_translation'      => aae_importer_money_value($get('doc_trans')),
                'vehicle_translation'  => aae_importer_money_value($get('veh_trans')),
                'shipping_usd'         => aae_importer_money_value($get('shipping')),
                'total_usd'            => aae_importer_money_value($get('total_usd')),
                'price_eur'            => aae_importer_money_value($get('price_eur')),
                'commission_eur'       => aae_importer_money_value($get('commission')),
                'total_ttc_eur'        => aae_importer_money_value($get('total_ttc')),
                'ttc_dzd'              => aae_importer_money_value($get('ttc_dzd')),
                'dzd_rate'             => null,
                'eur_rate'             => (float)get_option('aae_importer_eur_rate'),
                'eur_fee_percent'      => (float)get_option('aae_importer_eur_fee_percent'),
                'customs_rate'         => aae_importer_money_value($get('customs')),
                'lien'                 => $get('lien'),
                'status'               => $default_status,
                'created_by'           => get_staff_user_id(),
                'date_created'         => date('Y-m-d H:i:s'),
                'date_updated'         => date('Y-m-d H:i:s'),
            ];

            // Total USD manquant ? -> reconstitué
            if ($vehicle['total_usd'] <= 0 && $vehicle['fob_usd'] > 0) {
                $vehicle['total_usd'] = $vehicle['fob_usd'] + $vehicle['doc_translation']
                    + $vehicle['vehicle_translation'] + $vehicle['shipping_usd'];
            }

            // TTC EUR manquant ? -> price_eur + commission
            if ($vehicle['total_ttc_eur'] <= 0 && $vehicle['price_eur'] > 0) {
                $vehicle['total_ttc_eur'] = $vehicle['price_eur'] + $vehicle['commission_eur'];
            }

            // TTC EUR absent et total USD present -> calcule via taux
            if ($vehicle['total_ttc_eur'] <= 0 && $vehicle['total_usd'] > 0) {
                $vehicle['total_ttc_eur'] = round($vehicle['total_usd'] * $vehicle['eur_rate'] * (1 + $vehicle['eur_fee_percent'] / 100), 2) + $vehicle['commission_eur'];
            }

            // TTC DZD manquant ? -> TTC EUR * taux ; derive le taux DZD utilise (282/275)
            if ($vehicle['ttc_dzd'] > 0 && $vehicle['total_ttc_eur'] > 0) {
                $vehicle['dzd_rate'] = round($vehicle['ttc_dzd'] / $vehicle['total_ttc_eur'], 2);
            } else {
                $vehicle['ttc_dzd'] = round($vehicle['total_ttc_eur'] * (float)get_option('aae_importer_dzd_rate'), 2);
                $vehicle['dzd_rate'] = (float)get_option('aae_importer_dzd_rate');
            }

            // Prix EUR manquant ?
            if ($vehicle['price_eur'] <= 0 && $vehicle['total_ttc_eur'] > 0 && $vehicle['commission_eur'] > 0) {
                $vehicle['price_eur'] = round($vehicle['total_ttc_eur'] - $vehicle['commission_eur'], 2);
            }

            // FOB manquant -> derive du total si possible
            if ($vehicle['fob_usd'] <= 0 && $vehicle['price_eur'] > 0 && $vehicle['eur_rate'] > 0) {
                $vehicle['fob_usd'] = round(($vehicle['total_ttc_eur'] - $vehicle['commission_eur']) / ($vehicle['eur_rate'] * (1 + $vehicle['eur_fee_percent'] / 100)) - $vehicle['doc_translation'] - $vehicle['vehicle_translation'] - $vehicle['shipping_usd'], 2);
            }

            // nettoyage des valeurs nulles -> null
            foreach (['annee', 'customs_rate', 'dzd_rate'] as $k) {
                if ($vehicle[$k] === 0) {
                    $vehicle[$k] = null;
                }
            }

            // matching pour mise a jour : marque + modele + moteur + annee
            $this->db->where('marque', $vehicle['marque']);
            $this->db->where('modele', $vehicle['modele']);
            $this->db->where('moteur', $vehicle['moteur']);
            $this->db->where('annee', $vehicle['annee']);
            $existing = $this->db->get(db_prefix() . 'aae_vehicles')->row();

            if ($existing && !$replace) {
                // On garde le statut / la creation d'origine du vehicule lors d'une mise a jour par correspondance
                $updateData = $vehicle;
                unset($updateData['status'], $updateData['created_by'], $updateData['date_created']);
                $updateData['date_updated'] = date('Y-m-d H:i:s');
                $this->db->where('id', $existing->id)->update(db_prefix() . 'aae_vehicles', $updateData);
                $updated++;
            } else {
                $this->db->insert(db_prefix() . 'aae_vehicles', $vehicle);
                $inserted++;
            }
        }

        if ($headerIndex === null) {
            return ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 1];
        }

        log_activity('Import catalogue AAE [insérés: ' . $inserted . ' - mis à jour: ' . $updated . ']');

        return ['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Correspondance des colonnes CSV -> clés du modèle
     */
    public function map_csv_columns($header_cells)
    {
        $map = [];
        foreach ($header_cells as $i => $cell) {
            $h = aae_importer_slug_header($cell);

            if ($h !== '' && $h !== null) {
                if (aae_importer_contains($h, 'marque')) {
                    $map['marque'] = $i;
                } elseif (aae_importer_contains($h, 'modele')) {
                    $map['modele'] = $i;
                } elseif (aae_importer_contains($h, 'moteur')) {
                    $map['moteur'] = $i;
                } elseif (aae_importer_contains($h, 'annee')) {
                    $map['annee'] = $i;
                } elseif (aae_importer_contains($h, 'couleur')) {
                    $map['couleur'] = $i;
                } elseif (aae_importer_contains($h, 'fob')) {
                    $map['fob_usd'] = $i;
                } elseif (aae_importer_contains($h, 'traduction document')) {
                    $map['doc_trans'] = $i;
                } elseif (aae_importer_contains($h, 'traduction du vehicule') || aae_importer_contains($h, 'traduction vehicule')) {
                    $map['veh_trans'] = $i;
                } elseif (aae_importer_contains($h, 'frais de livraison') || aae_importer_contains($h, 'livraison')) {
                    $map['shipping'] = $i;
                } elseif (aae_importer_contains($h, 'prix total')) {
                    $map['total_usd'] = $i;
                } elseif (aae_importer_contains($h, 'prix en')) {
                    $map['price_eur'] = $i;
                } elseif (aae_importer_contains($h, 'commission')) {
                    $map['commission'] = $i;
                } elseif (aae_importer_contains($h, 'total ttc')) {
                    $map['total_ttc'] = $i;
                } elseif (aae_importer_contains($h, 'da taux')) {
                    $map['ttc_dzd'] = $i;
                } elseif (aae_importer_contains($h, 'ttc en million')) {
                    $map['million'] = $i;
                } elseif (aae_importer_contains($h, 'estimation')) {
                    $map['customs'] = $i;
                } elseif (aae_importer_contains($h, 'lien')) {
                    $map['lien'] = $i;
                }
            }
        }

        return $map;
    }

    /* =====================================================================
     *  OPERATIONS D'IMPORT
     * ===================================================================== */

    public function add_operation($data)
    {
        $vehicle = $this->get_vehicle($data['vehicle_id']);

        $op = [
            'vehicle_id' => (int)$data['vehicle_id'],
            'clientid'   => isset($data['clientid']) && $data['clientid'] !== '' ? (int)$data['clientid'] : null,
            'staff_id'   => get_staff_user_id(),
            'status'     => 'demande',
            'qty'        => isset($data['qty']) && $data['qty'] > 0 ? (int)$data['qty'] : 1,
            'eta_date'   => !empty($data['eta_date']) ? trim($data['eta_date']) : null,
            'fob_paid'   => isset($data['fob_paid']) && $data['fob_paid'] !== '' ? (float)$data['fob_paid'] : null,
            'shipping_paid'    => isset($data['shipping_paid']) && $data['shipping_paid'] !== '' ? (float)$data['shipping_paid'] : null,
            'translation_paid' => isset($data['translation_paid']) && $data['translation_paid'] !== '' ? (float)$data['translation_paid'] : null,
            'commission_paid'  => isset($data['commission_paid']) && $data['commission_paid'] !== '' ? (float)$data['commission_paid'] : null,
            'customs_amount'   => isset($data['customs_amount']) && $data['customs_amount'] !== '' ? (float)$data['customs_amount'] : null,
            'other_costs'      => isset($data['other_costs']) && $data['other_costs'] !== '' ? (float)$data['other_costs'] : null,
            'sale_price'       => isset($data['sale_price']) && $data['sale_price'] !== '' ? (float)$data['sale_price'] : null,
            'sale_currency'    => 'DZD',
            'estimate_id'      => isset($data['estimate_id']) ? (int)$data['estimate_id'] : null,
            'notes'            => isset($data['notes']) ? trim($data['notes']) : null,
            'date_created'     => date('Y-m-d H:i:s'),
            'date_updated'     => date('Y-m-d H:i:s'),
            'ref'              => '', // rempli apres
        ];

        // Coût de deduction/paiement fournisseur en DZD par défaut = valeur TTC catalogue si part non remplie
        if ($op['customs_amount'] === null && $vehicle && $vehicle->customs_rate > 0 && $op['fob_paid'] > 0) {
            $op['customs_amount'] = round($op['fob_paid'] * $vehicle->customs_rate / 100, 2);
        }
        $op['total_cost'] = $this->compute_total_cost($op);
        $op['margin']     = $this->compute_margin($op);

        $this->db->insert(db_prefix() . 'aae_operations', $op);
        $id = $this->db->insert_id();

        $this->db->where('id', $id)->update(db_prefix() . 'aae_operations', [
            'ref' => 'AAE-OP-' . str_pad($id, 4, '0', STR_PAD_LEFT),
        ]);

        $this->add_operation_status($id, 'demande', null, true);

        log_activity('Nouvelle opération d\'import AAE créée [ID: ' . $id . ']');

        return $id;
    }

    public function update_operation($id, $data)
    {
        $existing = $this->get_operation($id);
        if (!$existing) {
            return false;
        }

        $update = [];

        $nullable_int_fields = ['clientid', 'vehicle_id'];
        foreach ($nullable_int_fields as $f) {
            if (isset($data[$f])) {
                $update[$f] = ($data[$f] !== '' && $data[$f] !== null) ? (int)$data[$f] : null;
            }
        }

        if (isset($data['qty'])) {
            $update['qty'] = ((int)$data['qty']) > 0 ? (int)$data['qty'] : 1;
        }

        $money_fields = ['fob_paid', 'shipping_paid', 'translation_paid', 'commission_paid', 'customs_amount', 'other_costs', 'sale_price'];
        foreach ($money_fields as $f) {
            if (isset($data[$f])) {
                $update[$f] = $data[$f] !== '' ? (float)$data[$f] : null;
            }
        }

        if (isset($data['notes'])) {
            $update['notes'] = trim($data['notes']);
        }

        if (array_key_exists('estimate_id', $data)) {
            $update['estimate_id'] = $data['estimate_id'] !== '' ? (int)$data['estimate_id'] : null;
        }

        // Date ETA
        if (array_key_exists('eta_date', $data)) {
            $update['eta_date'] = $data['eta_date'] !== '' ? trim($data['eta_date']) : null;
        }

        if (count($update) > 0) {
            // Recompute cout total + marge
            foreach (['fob_paid', 'shipping_paid', 'translation_paid', 'commission_paid', 'customs_amount', 'other_costs', 'sale_price'] as $f) {
                if (!array_key_exists($f, $update)) {
                    $update[$f] = $existing->{$f};
                }
            }
            $update['total_cost'] = round(
                ($update['fob_paid'] ?? 0) + ($update['shipping_paid'] ?? 0)
                + ($update['translation_paid'] ?? 0) + ($update['commission_paid'] ?? 0)
                + ($update['customs_amount'] ?? 0) + ($update['other_costs'] ?? 0),
                2
            );
            $update['margin'] = round(($update['sale_price'] ?? 0) - $update['total_cost'], 2);

            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'aae_operations', array_merge($update, ['date_updated' => date('Y-m-d H:i:s')]));

            log_activity('Opération d\'import AAE modifiée [ID: ' . $id . ' — ' . $existing->ref . ']');
        }

        return true;
    }

    /**
     * Changement de statut d'une operation + historique + date auto
     */
    public function set_operation_status($id, $status, $note = null)
    {
        $existing = $this->get_operation($id);
        if (!$existing || !array_key_exists($status, $this->operation_statuses_colors())) {
            return false;
        }

        $dateField = $this->operation_status_date_field($status);
        $update = [
            'status'       => $status,
            'date_updated' => date('Y-m-d H:i:s'),
        ];

        if ($dateField && $existing->{$dateField} === null && in_array($dateField, ['order_date', 'production_date', 'departure_date', 'arrival_date', 'customs_date', 'delivery_date'])) {
            $update[$dateField] = date('Y-m-d');
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'aae_operations', $update);

        if ($existing->status !== $status) {
            $this->add_operation_status($id, $status, $note);

            // Met a jour le statut catalogue du vehicule selon la progression
            if ($status === 'disponible') {
                $this->db->where('id', $existing->vehicle_id)->update(db_prefix() . 'aae_vehicles', ['status' => 'disponible']);
            } elseif ($status === 'livre') {
                $this->db->where('id', $existing->vehicle_id)->update(db_prefix() . 'aae_vehicles', ['status' => 'vendu']);
            } elseif (in_array($status, ['commande', 'production', 'embarque', 'transit', 'arrive', 'douane'])) {
                $this->db->where('id', $existing->vehicle_id)->update(db_prefix() . 'aae_vehicles', ['status' => 'commande']);
            }
        }

        log_activity('Opération AAE statut changé [ID: ' . $id . ' -> ' . $status . ']');

        return true;
    }

    public function add_operation_status($operation_id, $status, $note = null, $silent = false)
    {
        $data = [
            'operation_id' => $operation_id,
            'status'       => $status,
            'note'         => $note,
            'staff_id'     => get_staff_user_id(),
            'date_added'   => date('Y-m-d H:i:s'),
        ];
        $this->db->insert(db_prefix() . 'aae_operation_status', $data);

        return $this->db->insert_id();
    }

    public function get_operation_statuses_history($operation_id)
    {
        return $this->db->where('operation_id', $operation_id)
            ->order_by('id', 'asc')
            ->get(db_prefix() . 'aae_operation_status')
            ->result_array();
    }

    public function get_operation($id)
    {
        $this->db->select(db_prefix() . 'aae_operations.*, ' . db_prefix() . 'aae_vehicles.marque as v_marque, '
            . db_prefix() . 'aae_vehicles.modele as v_modele, ' . db_prefix() . 'aae_vehicles.moteur as v_moteur, '
            . db_prefix() . 'aae_vehicles.annee as v_annee, ' . db_prefix() . 'aae_vehicles.fob_usd as v_fob_usd, '
            . db_prefix() . 'aae_vehicles.shipping_usd as v_shipping_usd, '
            . db_prefix() . 'aae_vehicles.doc_translation as v_doc_translation, '
            . db_prefix() . 'aae_vehicles.vehicle_translation as v_vehicle_translation, '
            . db_prefix() . 'aae_vehicles.commission_eur as v_commission, '
            . db_prefix() . 'aae_vehicles.ttc_dzd as v_ttc_dzd, '
            . db_prefix() . 'aae_vehicles.total_ttc_eur as v_total_ttc_eur, '
            . db_prefix() . 'aae_vehicles.total_usd as v_total_usd, '
            . db_prefix() . 'aae_vehicles.customs_rate as v_customs_rate, '
            . db_prefix() . 'aae_vehicles.dzd_rate as v_dzd_rate, '
            . db_prefix() . 'aae_vehicles.lien as v_lien');

        $this->db->join(db_prefix() . 'aae_vehicles', db_prefix() . 'aae_vehicles.id = ' . db_prefix() . 'aae_operations.vehicle_id', 'left');
        $this->db->where(db_prefix() . 'aae_operations.id', $id);

        $operation = $this->db->get(db_prefix() . 'aae_operations')->row();

        if ($operation) {
            $operation->client = null;
            if ($operation->clientid) {
                $this->load->model('clients_model');
                $operation->client = $this->clients_model->get($operation->clientid);
            }
            $operation->vehicle = $this->get_vehicle($operation->vehicle_id);
        }

        return $operation;
    }

    public function get_operations($status = '', $search = '', $limit = 500)
    {
        $this->db->select(db_prefix() . 'aae_operations.*, '
            . db_prefix() . 'aae_vehicles.marque as v_marque, ' . db_prefix() . 'aae_vehicles.modele as v_modele, '
            . db_prefix() . 'aae_vehicles.annee as v_annee, ' . db_prefix() . 'aae_vehicles.ttc_dzd as v_ttc_dzd');

        $this->db->join(db_prefix() . 'aae_vehicles', db_prefix() . 'aae_vehicles.id = ' . db_prefix() . 'aae_operations.vehicle_id', 'left');

        if ($status !== '') {
            $this->db->where(db_prefix() . 'aae_operations.status', $status);
        }

        if ($search !== '') {
            $this->db->group_start();
            $this->db->where(db_prefix() . 'aae_operations.ref LIKE "%' . $this->db->escape_str($search) . '%"');
            $this->db->or_where(db_prefix() . 'aae_vehicles.marque LIKE "%' . $this->db->escape_str($search) . '%"');
            $this->db->or_where(db_prefix() . 'aae_vehicles.modele LIKE "%' . $this->db->escape_str($search) . '%"');
            $this->db->group_end();
        }

        $this->db->order_by(db_prefix() . 'aae_operations.id', 'desc');
        $this->db->limit($limit);

        return $this->db->get(db_prefix() . 'aae_operations')->result_array();
    }

    public function count_operations_by_status()
    {
        $status_counts = [];
        $result        = $this->db->select('status, COUNT(*) as c')
            ->group_by('status')
            ->get(db_prefix() . 'aae_operations')
            ->result_array();
        foreach ($result as $row) {
            $status_counts[$row['status']] = (int)$row['c'];
        }

        return $status_counts;
    }

    private function compute_total_cost(&$op)
    {
        return round(((float)($op['fob_paid'] ?? 0)) + ((float)($op['shipping_paid'] ?? 0))
            + ((float)($op['translation_paid'] ?? 0)) + ((float)($op['commission_paid'] ?? 0))
            + ((float)($op['customs_amount'] ?? 0)) + ((float)($op['other_costs'] ?? 0)), 2);
    }

    private function compute_margin(&$op)
    {
        $cost      = (float)($op['total_cost'] ?? $this->compute_total_cost($op));
        $salePrice = (float)($op['sale_price'] ?? 0);

        return round($salePrice - $cost, 2);
    }

    /* =====================================================================
     *  DEVIS RAPIDE (estimates)
     * ===================================================================== */

    /**
     * Cree un devis Perfex depuis un vehicule du catalogue
     */
    public function create_estimate_from_vehicle($data)
    {
        $vehicle = $this->get_vehicle($data['vehicle_id']);
        if (!$vehicle) {
            return ['success' => false, 'message' => _l('aae_vehicle_not_found')];
        }

        $bases   = $this->quote_bases();
        $basis   = isset($data['qbasis']) && array_key_exists($data['qbasis'], $bases) ? $data['qbasis'] : 'ttc_dzd';
        $qty     = isset($data['qty']) && $data['qty'] > 0 ? (int)$data['qty'] : 1;
        $rate    = 0;

        switch ($basis) {
            case 'total_usd':
                $rate = $vehicle->total_usd;
                break;
            case 'total_ttc_eur':
                $rate = $vehicle->total_ttc_eur;
                break;
            case 'fob_usd':
                $rate = $vehicle->fob_usd;
                break;
            default:
                $rate = $vehicle->ttc_dzd;
        }

        if (isset($data['rate_override']) && $data['rate_override'] !== '') {
            $rate = (float)$data['rate_override'];
        }

        $clientid = (int)$data['clientid'];
        $this->load->model('clients_model');
        $client = $this->clients_model->get($clientid);
        if (!$client) {
            return ['success' => false, 'message' => _l('aae_client_not_found')];
        }

        $currency = isset($data['currency']) && $data['currency'] !== '' ? (int)$data['currency'] : get_option('default_currency');

        $desc = $vehicle->marque . ' ' . $vehicle->modele;
        if ($vehicle->moteur) {
            $desc .= ' - ' . $vehicle->moteur;
        }
        if ($vehicle->annee) {
            $desc .= ' - ' . $vehicle->annee;
        }
        if ($vehicle->couleur) {
            $desc .= ' (' . $vehicle->couleur . ')';
        }

        // Detail dans la long description (bascule comme le catalogue)
        $long = '';
        if ($basis !== 'fob_usd') {
            $long .= _l('aae_quote_detail_line_fob', number_format($vehicle->fob_usd, 2, '.', ' ')) . "\n";
            $long .= _l('aae_quote_detail_line_trans', number_format($vehicle->doc_translation + $vehicle->vehicle_translation, 2, '.', ' ')) . "\n";
            $long .= _l('aae_quote_detail_line_shipping', number_format($vehicle->shipping_usd, 2, '.', ' ')) . "\n";
            $long .= _l('aae_quote_detail_line_usd', number_format($vehicle->total_usd, 2, '.', ' ')) . "\n";
            if ($vehicle->customs_rate > 0) {
                $long .= _l('aae_quote_detail_line_customs', number_format($vehicle->customs_rate, 0)) . "\n";
            }
        }

        $estimate_data = [
            'clientid'           => $clientid,
            'number'             => get_option('next_estimate_number'),
            'date'               => _d(date('Y-m-d')),
            'expirydate'         => _d(date('Y-m-d', strtotime('+' . (int)get_option('aae_importer_quote_validity_days') . ' DAY', strtotime(date('Y-m-d'))))),
            'currency'           => $currency,
            'status'             => 1, // brouillon
            'show_quantity_as'   => 1,
            'reference_no'       => 'AAE-' . $vehicle->id,
            'sale_agent'         => get_staff_user_id(),
            'billing_street'     => $client->billing_street ?? '',
            'billing_city'       => $client->billing_city ?? '',
            'billing_state'      => $client->billing_state ?? '',
            'billing_zip'        => $client->billing_zip ?? '',
            'billing_country'    => $client->billing_country ? (int)$client->billing_country : null,
            'adminnote'          => '[AAE Import] Véhicule catalogue #' . $vehicle->id . ' - base: ' . $basis . ' - FOB: ' . $vehicle->fob_usd . ' - TTC DZD: ' . $vehicle->ttc_dzd . ' (taux ' . $vehicle->dzd_rate . ')',
            'newitems'           => [
                1 => [
                    'description'      => $desc,
                    'long_description' => nl2br($long),
                    'qty'              => $qty,
                    'unit'             => 'véhicule',
                    'rate'             => $rate,
                    'taxname'          => [],
                    'order'            => 1,
                ],
            ],
        ];

        $this->load->model('estimates_model');

        // Numero du devis : meme logique que Perfex (copy_estimate)
        $estimate_data['number'] = get_option('next_estimate_number');

        $estimate_id = $this->estimates_model->add($estimate_data);

        if (!$estimate_id) {
            return ['success' => false, 'message' => _l('aae_estimate_failed')];
        }

        // Creation eventuelle d'une operation liee au devis
        $operationId = null;
        if (isset($data['create_operation']) && filter_var($data['create_operation'], FILTER_VALIDATE_BOOLEAN)) {
            $operationId = $this->add_operation([
                'vehicle_id'  => $vehicle->id,
                'clientid'    => $clientid,
                'qty'         => $qty,
                'estimate_id' => $estimate_id,
            ]);
        }

        $link = admin_url('estimates/list_estimates/' . $estimate_id);

        log_activity('Devis AAE créé depuis le catalogue [Estimate: ' . $estimate_id . ' - Véhicule: ' . $vehicle->id . ']');

        return [
            'success'      => true,
            'estimate_id'  => $estimate_id,
            'operation_id' => $operationId,
            'link'         => $link,
            'amount'       => $rate,
            'qty'          => $qty,
            'basis'        => $basis,
        ];
    }

    /* =====================================================================
     *  TABLEAU DE BORD
     * ===================================================================== */

    public function get_dashboard_data()
    {
        $vehicles_total  = $this->db->count_all(db_prefix() . 'aae_vehicles');
        $operations      = $this->count_operations_by_status();

        $available = $this->db->where('status', 'disponible')->count_all_results(db_prefix() . 'aae_vehicles');
        $sold      = $this->db->where('status', 'vendu')->count_all_results(db_prefix() . 'aae_vehicles');

        $result = [
            'vehicles_total'  => $vehicles_total,
            'vehicles_dispo'  => $available,
            'vehicles_sold'   => $sold,
            'ops_status'      => $operations,
            'ops_total'       => $this->db->count_all(db_prefix() . 'aae_operations'),
            'ops_active'      => $this->db->where_not_in('status', ['livre', 'annule'])->count_all_results(db_prefix() . 'aae_operations'),
            'ops_livre'       => isset($operations['livre']) ? $operations['livre'] : 0,
        ];

        $op_revenue = $this->db->select('SUM(sale_price) as sale, SUM(margin) as margin')
            ->where('status', 'livre')
            ->get(db_prefix() . 'aae_operations')->row();
        $result['revenue']  = (float)$op_revenue->sale;
        $result['margin']   = (float)$op_revenue->margin;

        $recent = $this->get_operations('', '', 8);
        $result['recent_ops'] = $recent;

        $top_marques = $this->db->select('marque, COUNT(*) as c')
            ->where('marque != ""')
            ->group_by('marque')
            ->order_by('c', 'desc')
            ->limit(8)
            ->get(db_prefix() . 'aae_vehicles')->result_array();
        $result['top_marques'] = $top_marques;

        return $result;
    }
}
