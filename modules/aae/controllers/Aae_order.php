<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Aae_order extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('aae/aae_order_model');
        $this->load->model('aae/aae_model');
        $this->load->model('leads_model');
        $this->load->model('misc_model');
        $this->load->model('clients_model');
        $this->load->helper('aae/aae_order');
        $this->load->helper('aae/aae');
        $this->load->helper('aae/aae_whatsapp');
    }

    public function index()
    {
        if (staff_cant('view', 'aae')) {
            access_denied('aae');
        }

        $stage = $this->input->get('stage');
        $where = [];
        if ($stage && array_key_exists($stage, aae_order_stages())) {
            $where['stage'] = $stage;
        }

        $data['title']       = _l('aae_orders');
        $data['stage']       = $stage;
        $data['stages']      = aae_order_stages();
        $data['orders']      = $this->aae_order_model->get_orders($where, 100);
        $data['stage_stats'] = $this->aae_order_model->get_stats_by_stage();

        $this->load->view('aae/orders', $data);
    }

    public function view($id = 0)
    {
        if (staff_cant('view', 'aae')) {
            access_denied('aae');
        }

        $order = $this->aae_order_model->get_order($id);
        if (!$order) {
            show_404();
        }

        $data['title']     = _l('aae_order_title', $order->id);
        $data['order']     = $order;
        $data['client']    = $this->aae_model->get_client($order->client_id);
        $data['vehicle']   = $this->aae_model->get_vehicle_by_source($order->vehicle_id, $order->vehicle_source ?? 'cars');
        $data['vehicles']  = $this->aae_order_model->get_order_vehicles($order->id);
        $data['documents'] = $this->aae_order_model->get_order_documents($order->id);
        $data['payments']  = $this->aae_order_model->get_order_payments($order->id);
        $data['events']    = $this->aae_order_model->get_order_events($order->id);
        $data['stages']    = aae_order_stages();
        $data['balance']   = $this->aae_order_model->compute_order_balance($order);

        $this->db->where('rel_type', 'aae_order');
        $this->db->where('rel_id', $order->id);
        $data['document_files'] = array_column($this->db->get(db_prefix() . 'files')->result_array(), null, 'id');

        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get();

        $data['message_templates'] = aae_order_message_templates();
        $data['message_context']   = aae_order_message_context($order, $data['client'], $data['vehicle']);

        // Records already belonging to this customer, used by the
        // "link existing records" form so staff pick a real document
        // instead of typing a raw database id.
        $data['client_estimates'] = $this->aae_model->get_estimates_for_client($order->client_id);
        $data['client_invoices']  = $this->aae_model->get_invoices_for_client($order->client_id);
        $data['client_contracts'] = $this->aae_model->get_contracts_for_client($order->client_id);

        $this->load->view('aae/order', $data);
    }

    /**
     * Create an export order pre-filled from a lead.
     *
     * If the lead is not yet converted to a client, the conversion is
     * performed first (same fields as the standard Convert to customer
     * flow) so the order can be linked without re-typing data.
     */
    public function create_from_lead($lead_id = 0)
    {
        if (staff_cant('create', 'aae')) {
            access_denied('aae');
        }

        $lead_id = (int) ($lead_id ?: $this->input->get('lead_id'));
        $lead = $this->leads_model->get($lead_id);

        if (! $lead) {
            set_alert('warning', _l('aae_lead_not_found'));
            redirect(admin_url('aae/aae_order'));
        }

        // Resolve or create the client record for this lead.
        $client_id = $this->_resolve_client_from_lead($lead);

        if (! $client_id) {
            set_alert('danger', _l('aae_lead_convert_failed'));
            redirect(admin_url('leads/lead/' . $lead->id));
        }

        $data['title']    = _l('aae_order_new');
        $data['clients']  = $this->aae_model->get_clients_list();
        $data['cars']     = $this->aae_model->get_all_catalogue_for_export();
        $data['vehicle_source'] = 'catalogue';
        $data['prefill']  = [
            'client_id' => $client_id,
            'notes'     => trim((string) $lead->description),
        ];
        $data['lead_id']  = $lead->id;

        $this->load->view('aae/order_create', $data);
    }

    /**
     * Ensure a client record exists for the given lead.
     *
     * Returns the client userid, or 0 on failure.
     */
    private function _resolve_client_from_lead($lead)
    {
        $existing = total_rows(db_prefix() . 'clients', ['leadid' => $lead->id]);

        if ($existing > 0) {
            $row = $this->db->select('userid')
                ->where('leadid', $lead->id)
                ->limit(1)
                ->get(db_prefix() . 'clients')
                ->row();

            return $row ? (int) $row->userid : 0;
        }

        // Lead not converted yet — create the client record inline.
        // Split the lead name the same way the "Convert to customer" modal does.
        $firstname = $lead->name;
        $lastname  = '';
        if (mb_strpos($lead->name, ' ') !== false) {
            $_temp = explode(' ', $lead->name);
            $firstname = $_temp[0];
            $lastname  = isset($_temp[2]) ? $_temp[1] . ' ' . $_temp[2] : $_temp[1];
        }

        // A lead without a company is a B2C customer: use the person's name as
        // the display name and flag the client record accordingly.
        $is_b2c  = trim((string) $lead->company) === '';
        $company = $is_b2c ? $lead->name : $lead->company;

        $data = [
            'company'            => $company,
            'customer_type'      => $is_b2c ? 'b2c' : 'b2b',
            'firstname'          => $firstname,
            'lastname'           => $lastname,
            'title'              => (string) ($lead->title ?? ''),
            'email'              => $lead->email,
            'phonenumber'        => $lead->phonenumber,
            'website'            => $lead->website,
            'address'            => $lead->address,
            'billing_street'     => $lead->address,
            'billing_city'       => $lead->city,
            'billing_state'      => $lead->state,
            'billing_zip'        => $lead->zip,
            'billing_country'    => $lead->country,
            'city'               => $lead->city,
            'state'              => $lead->state,
            'zip'                => $lead->zip,
            'country'            => $lead->country,
            'leadid'             => $lead->id,
            'is_primary'         => 1,
            'default_language'   => $lead->default_language,
            'donotsendwelcomeemail' => true,
        ];

        $client_id = $this->clients_model->add($data, true);

        if (! $client_id) {
            return 0;
        }

        // Copy notes from lead to client.
        $notes = $this->misc_model->get_notes($lead->id, 'lead');
        if (is_array($notes)) {
            foreach ($notes as $note) {
                $this->db->insert(db_prefix() . 'notes', [
                    'rel_id'         => $client_id,
                    'rel_type'       => 'customer',
                    'dateadded'      => $note['dateadded'],
                    'addedfrom'      => $note['addedfrom'],
                    'description'    => $note['description'],
                    'date_contacted' => $note['date_contacted'],
                ]);
            }
        }

        // Mark the lead as converted.
        $default_status = $this->leads_model->get_status('', ['isdefault' => 1]);
        $this->db->where('id', $lead->id);
        $this->db->update(db_prefix() . 'leads', [
            'date_converted' => date('Y-m-d H:i:s'),
            'status'         => $default_status && isset($default_status[0]['id']) ? $default_status[0]['id'] : $lead->status,
            'junk'           => 0,
            'lost'           => 0,
        ]);

        $this->leads_model->log_lead_activity($lead->id, 'not_lead_activity_converted', false, serialize([
            get_staff_full_name(),
        ]));

        return (int) $client_id;
    }

    public function create()
    {
        if (staff_cant('create', 'aae')) {
            access_denied('aae');
        }

        if ($this->input->post()) {
            $post = $this->input->post();
            $vehicle_source = ($post['vehicle_source'] ?? '') === 'catalogue' ? 'catalogue' : 'cars';
            $data = [
                'client_id'          => (int) ($post['client_id'] ?? 0),
                'vehicle_id'         => (int) ($post['vehicle_id'] ?? 0),
                'vehicle_source'     => $vehicle_source,
                'supplier_name'      => trim($post['supplier_name'] ?? ''),
                'supplier_reference' => trim($post['supplier_reference'] ?? ''),
                'stage'              => 'nouveau_contact',
                'amount_client'      => (float) ($post['amount_client'] ?? 0),
                'amount_supplier'    => (float) ($post['amount_supplier'] ?? 0),
                'margin'             => (float) ($post['amount_client'] ?? 0) - (float) ($post['amount_supplier'] ?? 0),
                'amount_balance'     => (float) ($post['amount_client'] ?? 0),
                'amount_supplier_due' => (float) ($post['amount_supplier'] ?? 0),
                'notes'              => trim($post['notes'] ?? ''),
            ];

            $order_id = $this->aae_order_model->add_order($data);
            if ($order_id) {
                // If a vehicle was selected, add it to order vehicles
                if ($data['vehicle_id']) {
                    $car = $this->aae_model->get_vehicle_by_source($data['vehicle_id'], $vehicle_source);
                    if ($car) {
                        $this->aae_order_model->add_order_vehicle([
                            'order_id'         => $order_id,
                            'vehicle_id'       => $vehicle_source === 'catalogue' ? 0 : (int) $car->id,
                            'catalogue_id'     => $vehicle_source === 'catalogue' ? (int) $car->id : 0,
                            'color'            => $car->color,
                            'cost_eur'         => $car->price_eur,
                            'price_client_eur' => $car->total_ttc,
                            'price_client_dzd' => $car->price_dzd,
                            'quantity'         => 1,
                        ]);
                    }
                }

                // Add default documents checklist
                $default_docs = aae_order_required_docs();
                foreach ($default_docs as $type => $label) {
                    $this->aae_order_model->add_order_document([
                        'order_id'    => $order_id,
                        'doc_type'    => $type,
                        'label'       => $label,
                        'is_required' => 1,
                    ]);
                }

                // Add initial event
                $this->aae_order_model->add_order_event([
                    'order_id'          => $order_id,
                    'event_type'        => 'stage_change',
                    'event_title'       => _l('aae_order_event_created'),
                    'event_description' => _l('aae_order_stage_nouveau_contact'),
                    'addedfrom'         => get_staff_user_id(),
                ]);

                set_alert('success', _l('aae_order_created'));
                redirect(admin_url('aae/aae_order/view/' . $order_id));
            }
        }

        $data['title']    = _l('aae_order_new');
        $data['clients']  = $this->aae_model->get_clients_list();
        $data['cars']     = $this->aae_model->get_all_catalogue_for_export();
        $data['vehicle_source'] = 'catalogue';
        $this->load->view('aae/order_create', $data);
    }

    public function transition_stage($id)
    {
        if (staff_cant('edit', 'aae')) {
            access_denied('aae');
        }

        $order = $this->aae_order_model->get_order($id);
        if (!$order) {
            show_404();
        }

        $to = $this->input->post('stage');
        if (!aae_order_can_transition($order->stage, $to)) {
            set_alert('danger', _l('aae_order_invalid_transition'));
            redirect(admin_url('aae/aae_order/view/' . $id));
        }

        $this->aae_order_model->update_order(['stage' => $to], $id);

        $stages = aae_order_stages();
        $this->aae_order_model->add_order_event([
            'order_id'          => $id,
            'event_type'        => 'stage_change',
            'event_title'       => _l('aae_order_event_stage_changed'),
            'event_description' => $stages[$order->stage] . ' → ' . $stages[$to],
            'addedfrom'         => get_staff_user_id(),
        ]);

        $this->_notify_whatsapp_stage($order, $to);

        set_alert('success', _l('aae_order_stage_updated'));
        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    /**
     * Fire the automated WhatsApp notification for a stage. Any failure is
     * logged and swallowed so it can never block the workflow.
     */
    private function _notify_whatsapp_stage($order, $stage)
    {
        try {
            aae_send_whatsapp_notification($order, $stage);
        } catch (\Throwable $e) {
            log_message('error', 'AAE WhatsApp notification failed: ' . $e->getMessage());
        }
    }

    public function add_payment($id)
    {
        if (staff_cant('edit', 'aae')) {
            access_denied('aae');
        }

        $order = $this->aae_order_model->get_order($id);
        if (!$order) {
            show_404();
        }

        $post = $this->input->post();
        $data = [
            'order_id'           => $id,
            'payment_type'       => $post['payment_type'],
            'amount'             => (float) $post['amount'],
            'currency'           => $post['currency'] ?? 'EUR',
            'reference'          => trim($post['reference'] ?? ''),
            'related_invoice_id' => (int) ($post['related_invoice_id'] ?? 0),
            'datepaid'           => date('Y-m-d H:i:s'),
            'addedfrom'          => get_staff_user_id(),
        ];

        $payment_id = $this->aae_order_model->add_order_payment($data);

        // Update order aggregates
        if ($data['payment_type'] === 'client_in') {
            $new_received = (float) $order->amount_net_received + $data['amount'];
            $new_paid     = (float) $order->amount_paid_client + $data['amount'];
            $this->aae_order_model->update_order([
                'amount_net_received' => $new_received,
                'amount_paid_client'  => $new_paid,
                'amount_balance'      => (float) $order->amount_client + (float) $order->extras_cost - $new_received,
            ], $id);
        } elseif ($data['payment_type'] === 'supplier_out') {
            $new_supp_paid = (float) $order->amount_paid_supplier + $data['amount'];
            $this->aae_order_model->update_order([
                'amount_paid_supplier' => $new_supp_paid,
                'amount_supplier_due'  => (float) $order->amount_supplier - $new_supp_paid,
            ], $id);
        }

        // Add event
        $ptypes = aae_order_payment_types();
        $this->aae_order_model->add_order_event([
            'order_id'          => $id,
            'event_type'        => 'payment',
            'event_title'       => _l('aae_order_event_payment_recorded'),
            'event_description' => $ptypes[$data['payment_type']] . ' : ' . aae_format_eur($data['amount']),
            'addedfrom'         => get_staff_user_id(),
        ]);

        set_alert('success', _l('aae_payment_recorded'));
        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    /**
     * Edit an existing payment record.
     */
    public function edit_payment($id)
    {
        if (staff_cant('edit', 'aae')) {
            access_denied('aae');
        }

        $order = $this->aae_order_model->get_order($id);
        if (!$order) {
            show_404();
        }

        $payment_id = (int) $this->input->post('payment_id');
        $payments_table = db_prefix() . 'aae_order_payments';
        $payment = $this->db->where('id', $payment_id)->get($payments_table)->row();
        if (!$payment || $payment->order_id != $id) {
            show_404();
        }

        $post = $this->input->post();
        $data = [
            'payment_type'       => $post['payment_type'],
            'amount'             => (float) $post['amount'],
            'currency'           => $post['currency'] ?? 'EUR',
            'reference'          => trim($post['reference'] ?? ''),
            'related_invoice_id' => (int) ($post['related_invoice_id'] ?? 0),
            'datepaid'           => $post['datepaid'] ?? date('Y-m-d H:i:s'),
        ];

        $this->db->where('id', $payment_id);
        $this->db->update($payments_table, $data);

        // Recalculate order aggregates from scratch
        $totals = $this->aae_order_model->get_order_payment_totals($id);
        $client_in  = 0;
        $supplier_out = 0;
        $transitaire = 0;
        $carrier = 0;
        $refund = 0;
        foreach ($totals as $t) {
            switch ($t->payment_type) {
                case 'client_in':   $client_in   = (float) $t->total; break;
                case 'supplier_out': $supplier_out = (float) $t->total; break;
                case 'transitaire': $transitaire  = (float) $t->total; break;
                case 'carrier':     $carrier      = (float) $t->total; break;
                case 'refund':      $refund       = (float) $t->total; break;
            }
        }

        $this->aae_order_model->update_order([
            'amount_paid_client'   => $client_in,
            'amount_net_received'  => $client_in + $refund,
            'amount_balance'       => (float) $order->amount_client + (float) $order->extras_cost - $client_in - $refund,
            'amount_paid_supplier' => $supplier_out + $transitaire + $carrier,
            'amount_supplier_due'  => (float) $order->amount_supplier - $supplier_out - $transitaire - $carrier,
        ], $id);

        // Add event
        $ptypes = aae_order_payment_types();
        $this->aae_order_model->add_order_event([
            'order_id'          => $id,
            'event_type'        => 'payment',
            'event_title'       => _l('aae_order_event_payment_updated'),
            'event_description' => $ptypes[$data['payment_type']] . ' : ' . aae_format_eur($data['amount']),
            'addedfrom'         => get_staff_user_id(),
        ]);

        set_alert('success', _l('aae_payment_updated'));
        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    /**
     * Delete a payment record.
     */
    public function delete_payment($id)
    {
        if (staff_cant('edit', 'aae')) {
            access_denied('aae');
        }

        $order = $this->aae_order_model->get_order($id);
        if (!$order) {
            show_404();
        }

        $payment_id = (int) $this->input->post('payment_id');
        $payments_table = db_prefix() . 'aae_order_payments';
        $payment = $this->db->where('id', $payment_id)->get($payments_table)->row();
        if (!$payment || $payment->order_id != $id) {
            show_404();
        }

        // Store payment details for event log before deletion
        $ptypes = aae_order_payment_types();
        $payment_desc = $ptypes[$payment->payment_type] . ' : ' . aae_format_eur($payment->amount);

        $this->aae_order_model->delete_order_payment($payment_id);

        // Recalculate order aggregates from scratch
        $totals = $this->aae_order_model->get_order_payment_totals($id);
        $client_in  = 0;
        $supplier_out = 0;
        $transitaire = 0;
        $carrier = 0;
        $refund = 0;
        foreach ($totals as $t) {
            switch ($t->payment_type) {
                case 'client_in':   $client_in   = (float) $t->total; break;
                case 'supplier_out': $supplier_out = (float) $t->total; break;
                case 'transitaire': $transitaire  = (float) $t->total; break;
                case 'carrier':     $carrier      = (float) $t->total; break;
                case 'refund':      $refund       = (float) $t->total; break;
            }
        }

        $this->aae_order_model->update_order([
            'amount_paid_client'   => $client_in,
            'amount_net_received'  => $client_in + $refund,
            'amount_balance'       => (float) $order->amount_client + (float) $order->extras_cost - $client_in - $refund,
            'amount_paid_supplier' => $supplier_out + $transitaire + $carrier,
            'amount_supplier_due'  => (float) $order->amount_supplier - $supplier_out - $transitaire - $carrier,
        ], $id);

        // Add event
        $this->aae_order_model->add_order_event([
            'order_id'          => $id,
            'event_type'        => 'payment',
            'event_title'       => _l('aae_order_event_payment_deleted'),
            'event_description' => $payment_desc,
            'addedfrom'         => get_staff_user_id(),
        ]);

        set_alert('success', _l('aae_payment_deleted'));
        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    public function mark_doc($doc_id)
    {        if (staff_cant('edit', 'aae')) {
            access_denied('aae');
        }

        $doc = $this->aae_order_model->get_order_document($doc_id);
        if (!$doc) {
            show_404();
        }

        $received = $this->input->post('received');
        $sent     = $this->input->post('sent');

        $this->aae_order_model->mark_document_status($doc_id, $received, $sent);

        $this->aae_order_model->add_order_event([
            'order_id'          => $doc->order_id,
            'event_type'        => 'document',
            'event_title'       => _l('aae_order_event_doc_status'),
            'event_description' => $doc->label,
            'addedfrom'         => get_staff_user_id(),
        ]);

        set_alert('success', _l('aae_doc_updated'));
        redirect(admin_url('aae/aae_order/view/' . $doc->order_id));
    }

    /**
     * Attach a file to an order document (stored in tblfiles).
     */
    public function upload_doc($doc_id)
    {
        if (staff_cant('edit', 'aae')) {
            access_denied('aae');
        }

        $doc = $this->aae_order_model->get_order_document($doc_id);
        if (! $doc) {
            show_404();
        }

        $redirect = admin_url('aae/aae_order/view/' . $doc->order_id);

        if (! isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            set_alert('danger', _l('aae_doc_upload_failed'));
            redirect($redirect);
        }

        $path = AAE_ORDER_ATTACHMENTS_FOLDER . (int) $doc->order_id . '/';
        _maybe_create_upload_path(AAE_ORDER_ATTACHMENTS_FOLDER);
        _maybe_create_upload_path($path);

        $filename = unique_filename($path, $_FILES['file']['name']);

        if (! move_uploaded_file($_FILES['file']['tmp_name'], $path . $filename)) {
            set_alert('danger', _l('aae_doc_upload_failed'));
            redirect($redirect);
        }

        $this->load->model('misc_model');
        $file_id = $this->misc_model->add_attachment_to_database($doc->order_id, 'aae_order', [[
            'file_name' => $filename,
            'filetype'  => $_FILES['file']['type'],
        ]]);

        $this->aae_order_model->update_order_document(['file_id' => $file_id], $doc->id);

        $this->aae_order_model->add_order_event([
            'order_id'          => $doc->order_id,
            'event_type'        => 'document',
            'event_title'       => _l('aae_order_event_doc_uploaded'),
            'event_description' => aae_order_document_label($doc) . ' - ' . $filename,
            'addedfrom'         => get_staff_user_id(),
        ]);

        set_alert('success', _l('aae_doc_uploaded'));
        redirect($redirect);
    }

    public function update_shipping($id)
    {
        if (staff_cant('edit', 'aae')) {
            access_denied('aae');
        }

        $order = $this->aae_order_model->get_order($id);
        if (!$order) {
            show_404();
        }

        $post = $this->input->post();
        $this->aae_order_model->update_order([
            'container_number' => trim($post['container_number'] ?? ''),
            'bl_number'        => trim($post['bl_number'] ?? ''),
            'tracking_link'    => trim($post['tracking_link'] ?? ''),
        ], $id);

        $this->aae_order_model->add_order_event([
            'order_id'          => $id,
            'event_type'        => 'shipping',
            'event_title'       => _l('aae_order_event_shipping_updated'),
            'event_description' => 'Container: ' . $post['container_number'] . ' | BL: ' . $post['bl_number'],
            'addedfrom'         => get_staff_user_id(),
        ]);

        set_alert('success', _l('aae_shipping_updated'));
        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    public function update_delivery($id)
    {
        if (staff_cant('edit', 'aae')) {
            access_denied('aae');
        }

        $order = $this->aae_order_model->get_order($id);
        if (!$order) {
            show_404();
        }

        $post = $this->input->post();
        $extras_cost = (float) ($post['extras_cost'] ?? 0);

        $this->aae_order_model->update_order([
            'delivery_method' => $post['delivery_method'] ?? 'enlevement',
            'extras'          => trim($post['extras'] ?? ''),
            'extras_cost'     => $extras_cost,
            'amount_balance'  => (float) $order->amount_client + $extras_cost - (float) $order->amount_net_received,
            'margin'          => (float) $order->amount_client - (float) $order->amount_supplier - $extras_cost,
        ], $id);

        $this->aae_order_model->add_order_event([
            'order_id'          => $id,
            'event_type'        => 'delivery',
            'event_title'       => _l('aae_order_event_delivery_updated'),
            'event_description' => $post['delivery_method'] ?? '',
            'addedfrom'         => get_staff_user_id(),
        ]);

        set_alert('success', _l('aae_delivery_updated'));
        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    /**
     * Log a prepared client message in the order timeline.
     */
    public function log_message($id)
    {
        if (staff_cant('edit', 'aae')) {
            access_denied('aae');
        }

        $order = $this->aae_order_model->get_order($id);
        if (! $order) {
            show_404();
        }

        $post    = $this->input->post();
        $message = trim($post['message'] ?? '');

        if ($message === '') {
            set_alert('warning', _l('aae_message_empty'));
            redirect(admin_url('aae/aae_order/view/' . $id));
        }

        $channels = [
            'whatsapp' => _l('aae_message_whatsapp'),
            'email'    => _l('aae_message_email'),
            'copy'     => _l('aae_message_copy'),
        ];
        $channel = $post['channel'] ?? 'whatsapp';
        $label   = $channels[$channel] ?? $channel;
        $stage   = $post['stage'] ?? $order->stage;
        $lang    = strtoupper($post['lang'] ?? 'FR');

        $this->aae_order_model->add_order_event([
            'order_id'          => $id,
            'event_type'        => 'message',
            'event_title'       => _l('aae_order_event_message_sent', $label),
            'event_description' => '[' . $lang . ' - ' . aae_order_stage_label($stage) . '] ' . mb_substr($message, 0, 1500),
            'addedfrom'         => get_staff_user_id(),
        ]);

        set_alert('success', _l('aae_message_logged'));
        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    public function close_order($id)
    {
        if (staff_cant('edit', 'aae')) {
            access_denied('aae');
        }

        $order = $this->aae_order_model->get_order($id);
        if (!$order) {
            show_404();
        }

        $post = $this->input->post();
        $final_balance = (float) ($post['final_balance'] ?? 0);
        $refund_amount = (float) ($post['refund_amount'] ?? 0);

        $this->aae_order_model->update_order([
            'stage'                 => 'cloture',
            'final_papers_received' => 1,
            'final_balance'         => $final_balance,
            'refund_amount'         => $refund_amount,
        ], $id);

        $this->aae_order_model->add_order_event([
            'order_id'          => $id,
            'event_type'        => 'closure',
            'event_title'       => _l('aae_order_event_closed'),
            'event_description' => _l('aae_order_balance_settled'),
            'addedfrom'         => get_staff_user_id(),
        ]);

        $this->_notify_whatsapp_stage($order, 'cloture');

        set_alert('success', _l('aae_order_closed_success'));
        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    /* ---------------- Accounting integration ---------------- */

    /**
     * Create an estimate from the order lines and link it to the dossier.
     */
    public function create_estimate($id)
    {
        if (staff_cant('create', 'estimates')) {
            access_denied('estimates');
        }

        $order = $this->aae_order_model->get_order($id);
        if (!$order) {
            show_404();
        }

        if ($order->estimate_id) {
            set_alert('warning', _l('aae_accounting_already_linked'));
            redirect(admin_url('aae/aae_order/view/' . $id));
        }

        $this->load->model('estimates_model');

        [$items, $total] = $this->_order_line_items($order);
        $client          = $this->clients_model->get($order->client_id);

        $estimate_id = $this->estimates_model->add([
            'clientid'           => (int) $order->client_id,
            'number'             => (int) get_option('next_estimate_number'),
            'date'               => date('Y-m-d'),
            'expirydate'         => date('Y-m-d', strtotime('+' . (int) get_option('estimate_due_after') . ' DAY')),
            'currency'           => aae_order_currency_id(),
            'subtotal'           => $total,
            'total'              => $total,
            'adjustment'         => 0,
            'discount_percent'   => 0,
            'discount_total'     => 0,
            'discount_type'      => 'before_tax',
            'show_quantity_as'   => 1,
            'status'             => 1,
            'reference_no'       => 'AAE-' . $order->id,
            'billing_street'     => $client->billing_street ?? '',
            'billing_city'       => $client->billing_city ?? '',
            'billing_state'      => $client->billing_state ?? '',
            'billing_zip'        => $client->billing_zip ?? '',
            'billing_country'    => $client->billing_country ?? 0,
            'clientnote'         => '',
            'adminnote'          => '',
            'newitems'           => $items,
        ]);

        if ($estimate_id) {
            $this->aae_order_model->update_order(['estimate_id' => $estimate_id], $id);
            $this->_accounting_event($id, _l('aae_accounting_estimate_created'), format_estimate_number($estimate_id));
            set_alert('success', _l('aae_accounting_created'));
        } else {
            set_alert('danger', _l('aae_accounting_create_failed'));
        }

        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    /**
     * Create an invoice from the order lines and link it to the dossier.
     */
    public function create_invoice($id)
    {
        if (staff_cant('create', 'invoices')) {
            access_denied('invoices');
        }

        $order = $this->aae_order_model->get_order($id);
        if (!$order) {
            show_404();
        }

        if ($order->invoice_id) {
            set_alert('warning', _l('aae_accounting_already_linked'));
            redirect(admin_url('aae/aae_order/view/' . $id));
        }

        $this->load->model('invoices_model');

        [$items, $total] = $this->_order_line_items($order);
        $client          = $this->clients_model->get($order->client_id);

        $invoice_id = $this->invoices_model->add([
            'clientid'                 => (int) $order->client_id,
            'number'                   => (int) get_option('next_invoice_number'),
            'date'                     => date('Y-m-d'),
            'duedate'                  => date('Y-m-d', strtotime('+' . (int) get_option('invoice_due_after') . ' DAY')),
            'currency'                 => aae_order_currency_id(),
            'subtotal'                 => $total,
            'total'                    => $total,
            'adjustment'               => 0,
            'discount_percent'         => 0,
            'discount_total'           => 0,
            'discount_type'            => 'before_tax',
            'show_quantity_as'         => 1,
            'billing_street'           => $client->billing_street ?? '',
            'billing_city'             => $client->billing_city ?? '',
            'billing_state'            => $client->billing_state ?? '',
            'billing_zip'              => $client->billing_zip ?? '',
            'billing_country'          => $client->billing_country ?? 0,
            'allowed_payment_modes'    => [],
            'cancel_overdue_reminders' => 0,
            'newitems'                 => $items,
        ]);

        if ($invoice_id) {
            $this->aae_order_model->update_order(['invoice_id' => $invoice_id], $id);
            $this->_accounting_event($id, _l('aae_accounting_invoice_created'), format_invoice_number($invoice_id));
            set_alert('success', _l('aae_accounting_created'));
        } else {
            set_alert('danger', _l('aae_accounting_create_failed'));
        }

        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    /**
     * Create the final invoice for the outstanding client balance.
     */
    public function create_final_invoice($id)
    {
        if (staff_cant('create', 'invoices')) {
            access_denied('invoices');
        }

        $order = $this->aae_order_model->get_order($id);
        if (! $order) {
            show_404();
        }

        if ($order->final_invoice_id) {
            set_alert('warning', _l('aae_accounting_already_linked'));
            redirect(admin_url('aae/aae_order/view/' . $id));
        }

        $invoiced_total = 0;
        if ($order->invoice_id) {
            $main_invoice = $this->db->select('total')
                ->where('id', (int) $order->invoice_id)
                ->get(db_prefix() . 'invoices')
                ->row();
            $invoiced_total = $main_invoice ? (float) $main_invoice->total : 0;
        }

        $final_total = round((float) $order->amount_client + (float) $order->extras_cost - $invoiced_total, 2);

        if ($final_total <= 0.01) {
            set_alert('warning', _l('aae_final_invoice_no_balance'));
            redirect(admin_url('aae/aae_order/view/' . $id));
        }

        $this->load->model('invoices_model');

        $client = $this->clients_model->get($order->client_id);

        $invoice_id = $this->invoices_model->add([
            'clientid'                 => (int) $order->client_id,
            'number'                   => (int) get_option('next_invoice_number'),
            'date'                     => date('Y-m-d'),
            'duedate'                  => date('Y-m-d', strtotime('+' . (int) get_option('invoice_due_after') . ' DAY')),
            'currency'                 => aae_order_currency_id(),
            'subtotal'                 => $final_total,
            'total'                    => $final_total,
            'adjustment'               => 0,
            'discount_percent'         => 0,
            'discount_total'           => 0,
            'discount_type'            => 'before_tax',
            'show_quantity_as'         => 1,
            'billing_street'           => $client->billing_street ?? '',
            'billing_city'             => $client->billing_city ?? '',
            'billing_state'            => $client->billing_state ?? '',
            'billing_zip'              => $client->billing_zip ?? '',
            'billing_country'          => $client->billing_country ?? 0,
            'allowed_payment_modes'    => [],
            'cancel_overdue_reminders' => 0,
            'newitems'                 => [[
                'description'      => _l('aae_final_invoice_line', $order->id),
                'long_description' => '',
                'qty'              => 1,
                'rate'             => $final_total,
                'order'            => 1,
                'unit'             => '',
            ]],
        ]);

        if ($invoice_id) {
            $this->aae_order_model->update_order(['final_invoice_id' => $invoice_id], $id);
            $this->_accounting_event($id, _l('aae_accounting_final_invoice_created'), format_invoice_number($invoice_id));
            set_alert('success', _l('aae_accounting_created'));
        } else {
            set_alert('danger', _l('aae_accounting_create_failed'));
        }

        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    /**
     * Create a contract from the order and link it to the dossier.
     */
    public function create_contract($id)
    {
        if (staff_cant('create', 'contracts')) {
            access_denied('contracts');
        }

        $order = $this->aae_order_model->get_order($id);
        if (!$order) {
            show_404();
        }

        if ($order->contract_id) {
            set_alert('warning', _l('aae_accounting_already_linked'));
            redirect(admin_url('aae/aae_order/view/' . $id));
        }

        $this->load->model('contracts_model');

        $client = $this->clients_model->get($order->client_id);
        $label  = trim(($client->company ?? '') . ' - ' . _l('aae_export_order') . ' #' . $order->id);

        $contract_id = $this->contracts_model->add([
            'client'               => (int) $order->client_id,
            'subject'              => mb_substr($label, 0, 190),
            'datestart'            => date('Y-m-d'),
            'dateend'              => '',
            'contract_type'        => null,
            'contract_value'       => (float) $order->amount_client,
            'description'          => _l('aae_contract_description', $order->id),
            'content'              => '',
            'not_visible_to_client' => 0,
        ]);

        if ($contract_id) {
            $this->aae_order_model->update_order(['contract_id' => $contract_id], $id);
            $this->_accounting_event($id, _l('aae_accounting_contract_created'), _l('aae_contract_number', $contract_id));
            set_alert('success', _l('aae_accounting_created'));
        } else {
            set_alert('danger', _l('aae_accounting_create_failed'));
        }

        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    /**
     * Link existing accounting records to the dossier without re-typing data.
     */
    public function link_accounting($id)
    {
        if (staff_cant('edit', 'aae')) {
            access_denied('aae');
        }

        $order = $this->aae_order_model->get_order($id);
        if (!$order) {
            show_404();
        }

        $update = [];
        $links  = [
            'estimate_id' => 'estimates',
            'invoice_id'  => 'invoices',
            'contract_id' => 'contracts',
        ];

        foreach ($links as $field => $table) {
            $value = (int) $this->input->post($field);
            if ($value > 0 && total_rows(db_prefix() . $table, ['id' => $value]) > 0) {
                $update[$field] = $value;
            }
        }

        if (empty($update)) {
            set_alert('warning', _l('aae_accounting_nothing_linked'));
            redirect(admin_url('aae/aae_order/view/' . $id));
        }

        $this->aae_order_model->update_order($update, $id);
        $this->_accounting_event($id, _l('aae_accounting_linked'), implode(', ', array_keys($update)));
        set_alert('success', _l('aae_accounting_linked_success'));

        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    /**
     * Mark the linked estimate as sent after the staff opened it in WhatsApp.
     *
     * The customer receives the public estimate link (view/accept page),
     * so no e-mail configuration is required for this channel.
     */
    public function mark_estimate_sent($id)
    {
        if (staff_cant('edit', 'aae')) {
            access_denied('aae');
        }

        $order = $this->aae_order_model->get_order($id);
        if (! $order || ! $order->estimate_id) {
            show_404();
        }

        $estimate = $this->db->select('id, status')
            ->where('id', (int) $order->estimate_id)
            ->get(db_prefix() . 'estimates')
            ->row();

        if (! $estimate) {
            set_alert('danger', _l('aae_accounting_create_failed'));
            redirect(admin_url('aae/aae_order/view/' . $id));
        }

        $update = [
            'sent'     => 1,
            'datesend' => date('Y-m-d H:i:s'),
        ];

        // Draft estimates become "sent" once they reach the customer.
        if ((int) $estimate->status === 1) {
            $update['status'] = 2;
        }

        $this->db->where('id', (int) $estimate->id);
        $this->db->update(db_prefix() . 'estimates', $update);

        $this->load->model('estimates_model');
        $this->estimates_model->log_estimate_activity($estimate->id, 'invoice_estimate_activity_sent_to_client');

        $this->_accounting_event(
            $id,
            _l('aae_accounting_estimate_sent'),
            format_estimate_number($estimate->id)
        );

        set_alert('success', _l('aae_accounting_estimate_sent_success'));
        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    /**
     * Send the composed message through the WhatsApp Cloud API.
     *
     * Returns JSON so the Messages tab can report the exact Meta error
     * (expired token, closed 24h window, unapproved template, ...).
     */
    public function send_whatsapp_api($id)
    {
        if (! $this->input->is_ajax_request()) {
            show_404();
        }

        if (! is_staff_logged_in() || staff_cant('edit', 'aae')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        $order = $this->aae_order_model->get_order($id);
        if (! $order) {
            echo json_encode(['success' => false, 'message' => _l('aae_order_not_found')]);
            return;
        }

        $message = trim((string) $this->input->post('message'));
        if ($message === '') {
            echo json_encode(['success' => false, 'message' => _l('aae_message_empty')]);
            return;
        }

        $this->load->helper('aae/aae_whatsapp');
        $whatsapp = aae_get_whatsapp();

        if (! $whatsapp->is_configured()) {
            echo json_encode(['success' => false, 'message' => _l('aae_whatsapp_not_configured')]);
            return;
        }

        $contact = aae_get_client_contact($order->client_id);
        $phone   = $contact->phonenumber ?? '';

        if (trim($phone) === '') {
            echo json_encode(['success' => false, 'message' => _l('aae_message_no_phone')]);
            return;
        }

        $result = $whatsapp->send_text($phone, $message, true);

        if (! $result) {
            echo json_encode([
                'success' => false,
                'message' => _l('aae_whatsapp_send_failed'),
                'error'   => $whatsapp->get_last_error(),
            ]);
            return;
        }

        $stage = $this->input->post('stage') ?: $order->stage;
        $lang  = strtoupper($this->input->post('lang') ?: 'FR');

        $this->aae_order_model->add_order_event([
            'order_id'          => $id,
            'event_type'        => 'message',
            'event_title'       => _l('aae_order_event_message_sent', _l('aae_message_whatsapp_api')),
            'event_description' => '[' . $lang . ' - ' . aae_order_stage_label($stage) . '] ' . mb_substr($message, 0, 1500),
            'addedfrom'         => get_staff_user_id(),
        ]);

        echo json_encode([
            'success' => true,
            'message' => _l('aae_whatsapp_sent'),
            'id'      => $result['messages'][0]['id'] ?? '',
        ]);
    }

    /**
     * Record a client payment on the linked invoice and mirror it in the dossier.
     */
    public function record_client_payment($id)
    {
        if (staff_cant('create', 'payments')) {
            access_denied('payments');
        }

        $order = $this->aae_order_model->get_order($id);
        if (!$order) {
            show_404();
        }

        $post   = $this->input->post();
        $target = ($post['invoice_target'] ?? 'invoice') === 'final' ? 'final' : 'invoice';
        $invoice_id = $target === 'final' ? (int) $order->final_invoice_id : (int) $order->invoice_id;

        if (! $invoice_id) {
            set_alert('danger', _l('aae_accounting_invoice_required'));
            redirect(admin_url('aae/aae_order/view/' . $id));
        }

        $amount = (float) ($post['amount'] ?? 0);

        if ($amount <= 0) {
            set_alert('danger', _l('aae_accounting_amount_required'));
            redirect(admin_url('aae/aae_order/view/' . $id));
        }

        $this->load->model('payments_model');

        $payment_id = $this->payments_model->add([
            'invoiceid'                 => $invoice_id,
            'amount'                    => $amount,
            'paymentmode'               => ($post['paymentmode'] ?? '') !== '' ? (int) $post['paymentmode'] : null,
            'transactionid'             => trim($post['transactionid'] ?? ''),
            'date'                      => $post['date'] ?? date('Y-m-d'),
            'note'                      => trim($post['note'] ?? ''),
            'do_not_send_email_template' => true,
        ]);

        if (! $payment_id) {
            set_alert('danger', _l('aae_accounting_create_failed'));
            redirect(admin_url('aae/aae_order/view/' . $id));
        }

        $this->aae_order_model->add_order_payment([
            'order_id'           => $id,
            'payment_type'       => 'client_in',
            'amount'             => $amount,
            'currency'           => 'EUR',
            'reference'          => trim($post['transactionid'] ?? ''),
            'related_invoice_id' => $invoice_id,
            'datepaid'           => date('Y-m-d H:i:s', strtotime($post['date'] ?? date('Y-m-d'))),
            'addedfrom'          => get_staff_user_id(),
        ]);

        $this->_apply_payment_aggregates($order, 'client_in', $amount);

        $this->aae_order_model->add_order_event([
            'order_id'          => $id,
            'event_type'        => 'payment',
            'event_title'       => _l('aae_order_event_payment_recorded'),
            'event_description' => _l('aae_payment_client_in') . ' : ' . aae_format_eur($amount) . ' (' . _l('aae_invoice') . ' ' . format_invoice_number($invoice_id) . ')',
            'addedfrom'         => get_staff_user_id(),
        ]);

        set_alert('success', _l('aae_payment_recorded'));
        redirect(admin_url('aae/aae_order/view/' . $id));
    }

    /**
     * Export the order pipeline to CSV (Excel friendly, UTF-8 BOM).
     */
    public function export_orders()
    {
        if (staff_cant('view', 'aae')) {
            access_denied('aae');
        }

        $stage = $this->input->get('stage');
        $rows  = $this->aae_order_model->get_orders_for_export($stage);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="aae-orders-' . date('Ymd-His') . '.csv"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM for Excel.
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            _l('aae_id'),
            _l('aae_date'),
            _l('aae_client'),
            _l('aae_phone'),
            _l('aae_vehicle'),
            _l('aae_order_stage'),
            _l('aae_supplier_name'),
            _l('aae_amount_client') . ' (EUR)',
            _l('aae_amount_paid_client'),
            _l('aae_amount_net_received'),
            _l('aae_balance'),
            _l('aae_amount_supplier'),
            _l('aae_amount_paid_supplier'),
            _l('aae_amount_supplier_due'),
            _l('aae_margin'),
            _l('aae_container_number'),
            _l('aae_bl_number'),
            _l('aae_delivery_method'),
            _l('aae_notes'),
        ]);

        foreach ($rows as $row) {
            $vehicle = trim(($row->brand ?? '') . ' ' . ($row->model ?? ''));
            if ($vehicle !== '' && ! empty($row->model_year)) {
                $vehicle .= ' (' . $row->model_year . ')';
            }
            if ($vehicle !== '' && ! empty($row->color)) {
                $vehicle .= ' - ' . $row->color;
            }

            fputcsv($output, [
                (int) $row->id,
                $row->datecreated,
                $row->company,
                $row->contact_phone ?: $row->client_phone,
                $vehicle,
                aae_order_stage_label($row->stage),
                $row->supplier_name,
                number_format((float) $row->amount_client, 2, '.', ''),
                number_format((float) $row->amount_paid_client, 2, '.', ''),
                number_format((float) $row->amount_net_received, 2, '.', ''),
                number_format((float) $row->amount_client + (float) $row->extras_cost - (float) $row->amount_net_received, 2, '.', ''),
                number_format((float) $row->amount_supplier, 2, '.', ''),
                number_format((float) $row->amount_paid_supplier, 2, '.', ''),
                number_format((float) $row->amount_supplier - (float) $row->amount_paid_supplier, 2, '.', ''),
                number_format((float) $row->margin, 2, '.', ''),
                $row->container_number,
                $row->bl_number,
                $row->delivery_method,
                $row->notes,
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Build the accounting line items from the order vehicles.
     *
     * @return array [items, total]
     */
    private function _order_line_items($order)
    {
        $items = [];
        $total = 0;
        $index = 1;

        foreach ($this->aae_order_model->get_order_vehicles($order->id) as $line) {
            $line_source = ! empty($line->catalogue_id) ? 'catalogue' : ($order->vehicle_source ?? 'cars');
            $line_id     = ! empty($line->catalogue_id) ? (int) $line->catalogue_id : (int) $line->vehicle_id;
            $car         = $this->aae_model->get_vehicle_by_source($line_id, $line_source);
            $description = $car ? trim($car->brand . ' ' . $car->model) : _l('aae_vehicle') . ' #' . (int) $line->vehicle_id;

            if ($car && ! empty($car->model_year)) {
                $description .= ' (' . $car->model_year . ')';
            }
            if (! empty($line->color)) {
                $description .= ' - ' . $line->color;
            }

            $qty  = max(1, (float) $line->quantity);
            $rate = (float) $line->price_client_eur;
            if ($rate <= 0 && $car) {
                $rate = (float) $car->total_ttc;
            }

            $items[] = [
                'description'      => $description,
                'long_description' => '',
                'qty'              => $qty,
                'rate'             => $rate,
                'order'            => $index++,
                'unit'             => '',
            ];
            $total += $rate * $qty;
        }

        if (empty($items)) {
            $car         = $order->vehicle_id ? $this->aae_model->get_vehicle_by_source($order->vehicle_id, $order->vehicle_source ?? 'cars') : null;
            $description = $car
                ? trim($car->brand . ' ' . $car->model)
                : _l('aae_export_order') . ' #' . (int) $order->id;

            $items[] = [
                'description'      => $description,
                'long_description' => '',
                'qty'              => 1,
                'rate'             => (float) $order->amount_client,
                'order'            => 1,
                'unit'             => '',
            ];
            $total = (float) $order->amount_client;
        }

        // Reconcile with the negotiated order total so the document always
        // matches the amount stored on the dossier.
        $difference = round((float) $order->amount_client - $total, 2);
        if (abs($difference) >= 0.01) {
            $items[] = [
                'description'      => _l('aae_order_price_adjustment'),
                'long_description' => '',
                'qty'              => 1,
                'rate'             => $difference,
                'order'            => $index,
                'unit'             => '',
            ];
            $total = (float) $order->amount_client;
        }

        return [$items, round($total, 2)];
    }

    /**
     * Apply a payment amount to the dossier aggregates.
     */
    private function _apply_payment_aggregates($order, $type, $amount)
    {
        if ($type === 'client_in') {
            $new_received = (float) $order->amount_net_received + (float) $amount;
            $new_paid     = (float) $order->amount_paid_client + (float) $amount;
            $this->aae_order_model->update_order([
                'amount_net_received' => $new_received,
                'amount_paid_client'  => $new_paid,
                'amount_balance'      => (float) $order->amount_client + (float) $order->extras_cost - $new_received,
            ], $order->id);
        } elseif ($type === 'supplier_out') {
            $new_paid = (float) $order->amount_paid_supplier + (float) $amount;
            $this->aae_order_model->update_order([
                'amount_paid_supplier' => $new_paid,
                'amount_supplier_due'  => (float) $order->amount_supplier - $new_paid,
            ], $order->id);
        }
    }

    private function _accounting_event($order_id, $title, $description)
    {
        $this->aae_order_model->add_order_event([
            'order_id'          => $order_id,
            'event_type'        => 'accounting',
            'event_title'       => $title,
            'event_description' => $description,
            'addedfrom'         => get_staff_user_id(),
        ]);
    }
}
