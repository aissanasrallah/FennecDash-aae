<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Aae_order_model extends App_Model
{
    private $orders_table;
    private $vehicles_table;
    private $documents_table;
    private $payments_table;
    private $events_table;

    public function __construct()
    {
        parent::__construct();
        $pfx = db_prefix();
        $this->orders_table   = $pfx . 'aae_orders';
        $this->vehicles_table = $pfx . 'aae_order_vehicles';
        $this->documents_table = $pfx . 'aae_order_documents';
        $this->payments_table  = $pfx . 'aae_order_payments';
        $this->events_table    = $pfx . 'aae_order_events';
    }

    /* ---------------- Orders ---------------- */

    public function get_order($id)
    {
        $this->db->where('id', (int) $id);
        return $this->db->get($this->orders_table)->row();
    }

    public function get_orders($where = [], $limit = 0, $offset = 0, $order = 'datecreated DESC')
    {
        if (!empty($where)) {
            $this->db->where($where);
        }
        if ($limit > 0) {
            $this->db->limit($limit, $offset);
        }
        if ($order) {
            $this->db->order_by($order);
        }
        return $this->db->get($this->orders_table)->result();
    }

    public function count_orders($where = [])
    {
        if (!empty($where)) {
            $this->db->where($where);
        }
        return (int) $this->db->count_all_results($this->orders_table);
    }

    public function add_order($data)
    {
        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['dateupdated'] = $data['datecreated'];
        $data['addedfrom']   = get_staff_user_id();
        $data['updatedby']   = get_staff_user_id();
        $this->db->insert($this->orders_table, $data);
        return (int) $this->db->insert_id();
    }

    public function update_order($data, $id)
    {
        $data['dateupdated'] = date('Y-m-d H:i:s');
        $data['updatedby']   = get_staff_user_id();
        $this->db->where('id', (int) $id);
        return $this->db->update($this->orders_table, $data);
    }

    public function delete_order($id)
    {
        $this->db->where('id', (int) $id);
        return $this->db->delete($this->orders_table);
    }

    public function get_orders_by_client($client_id, $limit = 20)
    {
        $this->db->where('client_id', (int) $client_id);
        $this->db->order_by('datecreated', 'desc');
        $this->db->limit($limit);
        return $this->db->get($this->orders_table)->result();
    }

    /* ---------------- Order vehicles ---------------- */

    public function get_order_vehicles($order_id)
    {
        $this->db->where('order_id', (int) $order_id);
        $this->db->order_by('id', 'asc');
        return $this->db->get($this->vehicles_table)->result();
    }

    public function add_order_vehicle($data)
    {
        $this->db->insert($this->vehicles_table, $data);
        return (int) $this->db->insert_id();
    }

    public function delete_order_vehicles($order_id)
    {
        $this->db->where('order_id', (int) $order_id);
        return $this->db->delete($this->vehicles_table);
    }

    /* ---------------- Documents ---------------- */

    public function get_order_documents($order_id)
    {
        $this->db->where('order_id', (int) $order_id);
        $this->db->order_by('doc_type', 'asc');
        return $this->db->get($this->documents_table)->result();
    }

    public function get_order_document($id)
    {
        $this->db->where('id', (int) $id);
        return $this->db->get($this->documents_table)->row();
    }

    public function add_order_document($data)
    {
        $this->db->insert($this->documents_table, $data);
        return (int) $this->db->insert_id();
    }

    public function update_order_document($data, $id)
    {
        $this->db->where('id', (int) $id);
        return $this->db->update($this->documents_table, $data);
    }

    public function delete_order_document($id)
    {
        $this->db->where('id', (int) $id);
        return $this->db->delete($this->documents_table);
    }

    public function mark_document_status($id, $received = null, $sent = null)
    {
        $set = [];
        if ($received !== null) {
            $set['received_at'] = $received ? date('Y-m-d H:i:s') : null;
        }
        if ($sent !== null) {
            $set['sent_at'] = $sent ? date('Y-m-d H:i:s') : null;
        }
        if (!empty($set)) {
            $this->db->where('id', (int) $id);
            $this->db->update($this->documents_table, $set);
        }
    }

    /* ---------------- Payments ---------------- */

    public function get_order_payments($order_id)
    {
        $this->db->where('order_id', (int) $order_id);
        $this->db->order_by('datepaid', 'desc');
        return $this->db->get($this->payments_table)->result();
    }

    public function add_order_payment($data)
    {
        $data['datecreated'] = date('Y-m-d H:i:s');
        $this->db->insert($this->payments_table, $data);
        return (int) $this->db->insert_id();
    }

    public function delete_order_payment($id)
    {
        $this->db->where('id', (int) $id);
        return $this->db->delete($this->payments_table);
    }

    public function get_order_payment_totals($order_id)
    {
        $this->db->select("payment_type, SUM(amount) AS total");
        $this->db->where('order_id', (int) $order_id);
        $this->db->group_by('payment_type');
        return $this->db->get($this->payments_table)->result();
    }

    /* ---------------- Events / timeline ---------------- */

    public function get_order_events($order_id)
    {
        $this->db->where('order_id', (int) $order_id);
        $this->db->order_by('datecreated', 'asc');
        return $this->db->get($this->events_table)->result();
    }

    public function add_order_event($data)
    {
        $data['datecreated'] = date('Y-m-d H:i:s');
        $this->db->insert($this->events_table, $data);
        return (int) $this->db->insert_id();
    }

    /* ---------------- Stage pipeline ---------------- */

    public function get_orders_by_stage($stage)
    {
        $this->db->where('stage', $stage);
        $this->db->order_by('datecreated', 'asc');
        return $this->db->get($this->orders_table)->result();
    }

    public function get_stats_by_stage()
    {
        $this->db->select("stage, COUNT(*) AS total");
        $this->db->group_by('stage');
        return $this->db->get($this->orders_table)->result();
    }

    /**
     * Stage counters used by the dashboard pipeline widget.
     *
     * Orders are grouped by the phase of the workflow they are in. Cancelled
     * orders are counted separately and never considered active.
     *
     * @return array
     */
    public function get_dashboard_pipeline()
    {
        $groups = [
            'quoting'    => ['nouveau_contact', 'devis_prete', 'devis_envoye'],
            'processing' => ['commande_confirmee', 'paiement_client_recu', 'fournisseur_paye', 'dossier_fournisseur'],
            'shipping'   => ['depart_confirme', 'expedie', 'dedouanement', 'docs_transitaire', 'arrive'],
            'completed'  => ['livre', 'papiers_definitifs', 'cloture'],
            'cancelled'  => ['annule'],
        ];

        $select = ['COUNT(*) AS total'];

        foreach ($groups as $key => $stages) {
            $in       = "'" . implode("','", $stages) . "'";
            $select[] = "COALESCE(SUM(CASE WHEN stage IN ({$in}) THEN 1 ELSE 0 END), 0) AS {$key}";
        }

        $select[] = "COALESCE(SUM(CASE WHEN stage NOT IN ('cloture','annule') THEN 1 ELSE 0 END), 0) AS active";

        $row = $this->db->query('SELECT ' . implode(', ', $select) . ' FROM ' . $this->orders_table)->row();

        $stats = [
            'total'      => 0,
            'active'     => 0,
            'quoting'    => 0,
            'processing' => 0,
            'shipping'   => 0,
            'completed'  => 0,
            'cancelled'  => 0,
        ];

        if ($row) {
            foreach (array_keys($stats) as $key) {
                $stats[$key] = (int) $row->{$key};
            }
        }

        return $stats;
    }

    /**
     * Money totals for the active (not closed, not cancelled) orders.
     *
     * The balances are derived from the same formula as compute_order_balance()
     * so the widget always agrees with the order screen.
     *
     * @return array
     */
    public function get_dashboard_financials()
    {
        $active = "stage NOT IN ('cloture','annule')";

        $row = $this->db->query('
            SELECT
                COALESCE(SUM(CASE WHEN ' . $active . ' THEN amount_client ELSE 0 END), 0) AS order_value,
                COALESCE(SUM(CASE WHEN ' . $active . ' THEN amount_client + extras_cost - amount_net_received ELSE 0 END), 0) AS receivables,
                COALESCE(SUM(CASE WHEN ' . $active . ' THEN amount_supplier - amount_paid_supplier ELSE 0 END), 0) AS supplier_due,
                COALESCE(SUM(CASE WHEN ' . $active . ' THEN amount_client - amount_supplier - extras_cost ELSE 0 END), 0) AS expected_margin
            FROM ' . $this->orders_table . '
        ')->row();

        return [
            'order_value'     => $row ? (float) $row->order_value : 0.0,
            'receivables'     => $row ? (float) $row->receivables : 0.0,
            'supplier_due'    => $row ? (float) $row->supplier_due : 0.0,
            'expected_margin' => $row ? (float) $row->expected_margin : 0.0,
        ];
    }

    /**
     * Number of vehicles attached to active orders.
     *
     * @return int
     */
    public function count_active_order_vehicles()
    {
        $row = $this->db->query(
            'SELECT COALESCE(SUM(v.quantity), 0) AS total'
            . ' FROM ' . $this->vehicles_table . ' v'
            . ' INNER JOIN ' . $this->orders_table . ' o ON o.id = v.order_id'
            . " WHERE o.stage NOT IN ('cloture', 'annule')"
        )->row();

        return $row ? (int) $row->total : 0;
    }

    /**
     * Orders enriched with client, primary contact and vehicle data for CSV.
     *
     * @param string|null $stage
     *
     * @return array
     */
    public function get_orders_for_export($stage = null)
    {
        $this->db->select('o.*, c.company, c.phonenumber AS client_phone, ct.phonenumber AS contact_phone, car.brand, car.model, car.model_year, car.color');
        $this->db->from($this->orders_table . ' o');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = o.client_id', 'left');
        $this->db->join(db_prefix() . 'contacts ct', 'ct.userid = o.client_id AND ct.is_primary = 1', 'left');
        $this->db->join(db_prefix() . 'aae_cars car', 'car.id = o.vehicle_id', 'left');

        if ($stage) {
            $this->db->where('o.stage', $stage);
        }

        $this->db->order_by('o.datecreated', 'desc');

        return $this->db->get()->result();
    }

    /* ---------------- Money ---------------- */

    public function compute_order_balance($order)
    {
        $client_due   = (float) $order->amount_client + (float) $order->extras_cost - (float) $order->amount_net_received;
        $supplier_due = (float) $order->amount_supplier - (float) $order->amount_paid_supplier;
        return [
            'client_balance' => $client_due,
            'supplier_due'   => $supplier_due,
            'margin'         => (float) $order->amount_client - (float) $order->amount_supplier - (float) $order->extras_cost,
        ];
    }
}
