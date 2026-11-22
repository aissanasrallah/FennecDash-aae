<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * WhatsApp Business API Webhook Controller
 * 
 * Handles incoming webhook events from Meta WhatsApp Business API
 * Documentation: https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks
 */
class Whatsapp extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('aae/aae');
        $this->load->helper('aae/aae_whatsapp');
    }

    /**
     * Webhook verification endpoint (GET)
     * 
     * Meta calls this endpoint to verify the webhook URL during setup
     * and periodically to ensure it's still valid.
     */
    public function webhook()
    {
        // GET request - verification
        if ($this->input->server('REQUEST_METHOD') === 'GET') {
            $this->_verify_webhook();
            return;
        }

        // POST request - incoming messages/events
        $this->_handle_webhook_event();
    }

    /**
     * Verify webhook subscription
     */
    private function _verify_webhook()
    {
        $mode = $this->input->get('hub_mode');
        $challenge = $this->input->get('hub_challenge');
        $verify_token = $this->input->get('hub_verify_token');

        $expected_token = aae_get_option('aae_whatsapp_webhook_verify_token', '');

        if ($mode === 'subscribe' && $verify_token === $expected_token && !empty($challenge)) {
            // Valid verification - return challenge
            echo $challenge;
        } else {
            // Invalid verification
            show_error('Webhook verification failed', 403);
        }
    }

    /**
     * Handle incoming webhook events
     */
    private function _handle_webhook_event()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data || !isset($data['entry'])) {
            log_message('error', 'Invalid WhatsApp webhook payload');
            return;
        }

        foreach ($data['entry'] as $entry) {
            if (!isset($entry['changes'])) continue;

            foreach ($entry['changes'] as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                if ($field === 'messages') {
                    $this->_handle_incoming_messages($value);
                } elseif ($field === 'message_template_status_update') {
                    $this->_handle_template_status_update($value);
                }
            }
        }

        // Always return 200 OK to acknowledge receipt
        echo 'OK';
    }

    /**
     * Handle incoming messages
     */
    private function _handle_incoming_messages($value)
    {
        if (!isset($value['messages'])) return;

        foreach ($value['messages'] as $message) {
            $from = $message['from'] ?? '';
            $type = $message['type'] ?? '';
            $message_id = $message['id'] ?? '';
            $timestamp = $message['timestamp'] ?? '';
            $text = $message['text']['body'] ?? '';

            // Log incoming message
            log_message('info', "WhatsApp incoming from {$from}: {$text}");

            // Try to match to an order/client
            $this->_process_incoming_message($from, $message);
        }
    }

    /**
     * Process incoming message and link to order/client
     */
    private function _process_incoming_message($from, $message)
    {
        $CI = &get_instance();
        
        // Try to find client by phone number
        $phone = $this->_normalize_phone($from);
        
        $CI->db->select('userid, company');
        $CI->db->where('phonenumber LIKE', '%' . substr($phone, -9) . '%');
        $CI->db->or_where('phonenumber LIKE', '%' . $phone . '%');
        $client = $CI->db->get(db_prefix() . 'clients')->row();

        if ($client) {
            // Add as note to client or latest order
            $note = "WhatsApp message from {$from}: " . ($message['text']['body'] ?? '[Media/Other]');
            
            $CI->db->insert(db_prefix() . 'notes', [
                'rel_id'         => $client->userid,
                'rel_type'       => 'customer',
                'dateadded'      => date('Y-m-d H:i:s'),
                'addedfrom'      => 0, // System
                'description'    => $note,
            ]);
        }
    }

    /**
     * Handle template status updates
     */
    private function _handle_template_status_update($value)
    {
        $template_name = $value['message_template_name'] ?? '';
        $status = $value['status'] ?? '';
        $reason = $value['status_reason'] ?? '';

        log_message('info', "WhatsApp template {$template_name} status: {$status} - {$reason}");

        // Could update local template status tracking here
    }

    /**
     * Normalize phone number format
     */
    private function _normalize_phone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Remove country code if present (assume 213 for Algeria)
        if (substr($phone, 0, 3) === '213') {
            $phone = substr($phone, 3);
        }
        
        return $phone;
    }

    /**
     * Test WhatsApp API connection (AJAX endpoint)
     */
    public function test_connection()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!is_staff_logged_in() || staff_cant('edit', 'aae')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            return;
        }

        $whatsapp = aae_get_whatsapp();
        
        if (!$whatsapp->is_configured()) {
            echo json_encode([
                'success' => false, 
                'message' => _l('aae_whatsapp_not_configured')
            ]);
            return;
        }

        // Test by getting templates list
        $result = $whatsapp->get_templates();
        
        if ($result !== false) {
            echo json_encode([
                'success' => true, 
                'message' => _l('aae_whatsapp_connection_success'),
                'templates_count' => count($result['data'] ?? [])
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => _l('aae_whatsapp_connection_failed'),
                'error'   => $whatsapp->get_last_error()
            ]);
        }
    }
}