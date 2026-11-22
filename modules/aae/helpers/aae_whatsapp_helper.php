<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * WhatsApp Business API Integration
 * 
 * Implements Meta's WhatsApp Business Cloud API for sending template messages
 * and interactive messages.
 * 
 * Documentation: https://developers.facebook.com/documentation/business-messaging/whatsapp/
 */

class Aae_whatsapp
{
    private $access_token;
    private $phone_number_id;
    private $business_account_id;
    private $api_version = 'v20.0';
    private $base_url;
    private $last_error = '';

    public function __construct()
    {
        $CI = &get_instance();
        
        $this->access_token = aae_get_option('aae_whatsapp_access_token', '');
        $this->phone_number_id = aae_get_option('aae_whatsapp_phone_number_id', '');
        $this->business_account_id = aae_get_option('aae_whatsapp_business_account_id', '');
        
        $this->base_url = "https://graph.facebook.com/{$this->api_version}";
    }

    /**
     * Check if WhatsApp API is configured
     */
    public function is_configured()
    {
        return !empty($this->access_token) && !empty($this->phone_number_id);
    }

    /**
     * Last error returned by Meta (or by cURL), for diagnostics.
     */
    public function get_last_error()
    {
        return $this->last_error;
    }

    /**
     * TLS options shared by every Graph API request.
     *
     * The bundle ships with the module so the API calls can verify Meta's
     * certificate even when PHP has no curl.cainfo configured.
     */
    private function ssl_options()
    {
        $ca = dirname(__DIR__) . '/assets/cacert.pem';

        if (is_file($ca)) {
            return [
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CAINFO         => $ca,
            ];
        }

        return [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];
    }

    /**
     * Extract a readable message from a Graph API error response.
     */
    private function remember_error($response, $fallback = '')
    {
        $json = json_decode((string) $response, true);

        if (isset($json['error']['message'])) {
            $this->last_error = $json['error']['message'];
            if (! empty($json['error']['code'])) {
                $this->last_error .= ' (code ' . $json['error']['code'] . ')';
            }
        } elseif ($fallback !== '') {
            $this->last_error = $fallback;
        }

        return $this->last_error;
    }

    /**
     * Send a template message via WhatsApp Business API
     * 
     * @param string $to - Recipient phone number in international format (e.g., 213555123456)
     * @param string $template_name - Name of the approved template
     * @param string $language_code - Language code (e.g., 'fr', 'en')
     * @param array $components - Template components (header, body, buttons)
     * @return array|false - Response or false on failure
     */
    public function send_template($to, $template_name, $language_code = 'fr', $components = [])
    {
        if (!$this->is_configured()) {
            log_message('error', 'WhatsApp API not configured');
            return false;
        }

        $url = "{$this->base_url}/{$this->phone_number_id}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->format_phone($to),
            'type' => 'template',
            'template' => [
                'name' => $template_name,
                'language' => [
                    'code' => $language_code,
                    'policy' => 'deterministic'
                ]
            ]
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $this->http_post($url, $payload);
    }

    /**
     * Send a text message (free-form, only within 24h customer care window)
     */
    public function send_text($to, $text, $preview_url = false)
    {
        if (!$this->is_configured()) {
            return false;
        }

        $url = "{$this->base_url}/{$this->phone_number_id}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->format_phone($to),
            'type' => 'text',
            'text' => [
                'body' => $text,
                'preview_url' => $preview_url
            ]
        ];

        return $this->http_post($url, $payload);
    }

    /**
     * Send an interactive message with buttons
     */
    public function send_interactive_buttons($to, $header_text, $body_text, $buttons, $footer_text = '')
    {
        if (!$this->is_configured()) {
            return false;
        }

        $url = "{$this->base_url}/{$this->phone_number_id}/messages";

        $interactive_buttons = [];
        foreach ($buttons as $index => $button) {
            $interactive_buttons[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => $button['id'] ?? "btn_{$index}",
                    'title' => $button['title']
                ]
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->format_phone($to),
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'header' => [
                    'type' => 'text',
                    'text' => $header_text
                ],
                'body' => [
                    'text' => $body_text
                ],
                'footer' => [
                    'text' => $footer_text
                ],
                'action' => [
                    'buttons' => $interactive_buttons
                ]
            ]
        ];

        return $this->http_post($url, $payload);
    }

    /**
     * Send media message (image, document, video)
     */
    public function send_media($to, $type, $media_id, $caption = '')
    {
        if (!$this->is_configured()) {
            return false;
        }

        $url = "{$this->base_url}/{$this->phone_number_id}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->format_phone($to),
            'type' => $type,
            $type => [
                'id' => $media_id,
                'caption' => $caption
            ]
        ];

        return $this->http_post($url, $payload);
    }

    /**
     * Send location message
     */
    public function send_location($to, $latitude, $longitude, $name = '', $address = '')
    {
        if (!$this->is_configured()) {
            return false;
        }

        $url = "{$this->base_url}/{$this->phone_number_id}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->format_phone($to),
            'type' => 'location',
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'name' => $name,
                'address' => $address
            ]
        ];

        return $this->http_post($url, $payload);
    }

    /**
     * Upload media to WhatsApp
     */
    public function upload_media($file_path, $type = 'document')
    {
        if (!$this->is_configured()) {
            return false;
        }

        $url = "{$this->base_url}/{$this->phone_number_id}/media";

        $mime_types = [
            'image' => 'image/jpeg',
            'document' => 'application/pdf',
            'video' => 'video/mp4',
            'audio' => 'audio/mpeg'
        ];

        $cfile = new CURLFile($file_path, $mime_types[$type] ?? 'application/octet-stream');
        
        $post_fields = [
            'messaging_product' => 'whatsapp',
            'file' => $cfile,
            'type' => $type
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, $this->ssl_options() + [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->access_token
            ],
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code >= 200 && $http_code < 300) {
            $result = json_decode($response, true);
            return $result['id'] ?? false;
        }

        $this->remember_error($response, 'Media upload failed');
        log_message('error', 'WhatsApp media upload failed: ' . $response);
        return false;
    }

    /**
     * Get message status
     */
    public function get_message_status($message_id)
    {
        if (!$this->is_configured()) {
            return false;
        }

        $url = "{$this->base_url}/{$message_id}";
        
        return $this->http_get($url);
    }

    /**
     * Get templates list
     */
    public function get_templates()
    {
        if (!$this->is_configured()) {
            return false;
        }

        $url = "{$this->base_url}/{$this->business_account_id}/message_templates";
        
        return $this->http_get($url);
    }

    /**
     * Delete a template
     */
    public function delete_template($template_name)
    {
        if (!$this->is_configured()) {
            return false;
        }

        $url = "{$this->base_url}/{$this->business_account_id}/message_templates";
        
        $ch = curl_init($url);
        curl_setopt_array($ch, $this->ssl_options() + [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_POSTFIELDS => json_encode(['name' => $template_name]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->access_token,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_code >= 200 && $http_code < 300;
    }

    /**
     * Format phone number to international format
     */
    private function format_phone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);
        
        // If starts with 0, remove it (Algeria)
        if (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        }
        
        // Add country code if missing (default Algeria +213)
        if (!preg_match('/^213/', $phone)) {
            $phone = '213' . $phone;
        }
        
        return $phone;
    }

    /**
     * HTTP POST request
     */
    private function http_post($url, $payload)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, $this->ssl_options() + [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->access_token,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->last_error = $error;
            log_message('error', 'WhatsApp API cURL error: ' . $error);
            return false;
        }

        $result = json_decode($response, true);
        
        if ($http_code >= 200 && $http_code < 300) {
            log_message('info', 'WhatsApp API success: ' . $response);
            return $result;
        }

        $this->remember_error($response, 'HTTP ' . $http_code);
        log_message('error', 'WhatsApp API error (' . $http_code . '): ' . $response);
        
        // Check for specific error codes
        if (isset($result['error'])) {
            $error_code = $result['error']['code'] ?? 0;
            $error_msg = $result['error']['message'] ?? 'Unknown error';
            
            // Common errors: 131000 (template not found), 131009 (param format), 
            // 131051 (media upload), 100 (invalid param)
            log_message('error', "WhatsApp API Error {$error_code}: {$error_msg}");
        }

        return false;
    }

    /**
     * HTTP GET request
     */
    private function http_get($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, $this->ssl_options() + [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->access_token
            ],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->last_error = $error;
            log_message('error', 'WhatsApp API cURL error: ' . $error);
            return false;
        }

        if ($http_code >= 200 && $http_code < 300) {
            return json_decode($response, true);
        }

        $this->remember_error($response, 'HTTP ' . $http_code);
        log_message('error', 'WhatsApp API GET error (' . $http_code . '): ' . $response);
        return false;
    }
}

/**
 * Helper function to get WhatsApp instance
 */
function aae_get_whatsapp()
{
    static $instance = null;
    if ($instance === null) {
        $instance = new Aae_whatsapp();
    }
    return $instance;
}

/**
 * Send workflow notification via WhatsApp Business API
 * 
 * @param object $order - Order object
 * @param string $stage - Current stage key
 * @param array $data - Additional data for template
 * @return bool
 */
function aae_send_whatsapp_notification($order, $stage, $data = [])
{
    $whatsapp = aae_get_whatsapp();
    
    if (!$whatsapp->is_configured()) {
        log_message('warning', 'WhatsApp API not configured, skipping notification for order #' . $order->id);
        return false;
    }

    $client = aae_get_client_contact($order->client_id);
    if (!$client || empty($client->phone)) {
        log_message('warning', 'No client phone for WhatsApp notification on order #' . $order->id);
        return false;
    }

    $template_map = [
        'nouveau_contact'      => 'aae_welcome',
        'devis_prete'          => 'aae_quote_ready',
        'devis_envoye'         => 'aae_quote_sent',
        'commande_confirmee'   => 'aae_order_confirmed',
        'paiement_client_recu' => 'aae_payment_received',
        'fournisseur_paye'     => 'aae_supplier_paid',
        'dossier_fournisseur'  => 'aae_supplier_dossier_sent',
        'depart_confirme'      => 'aae_departure_confirmed',
        'expedie'              => 'aae_shipped',
        'dedouanement'         => 'aae_customs_clearance',
        'docs_transitaire'     => 'aae_transitaire_docs_request',
        'arrive'               => 'aae_arrived',
        'livre'                => 'aae_delivered',
        'papiers_definitifs'   => 'aae_final_papers_ready',
        'cloture'              => 'aae_closed',
        'annule'               => 'aae_cancelled',
    ];

    $template_name = $template_map[$stage] ?? null;
    if (!$template_name) {
        log_message('warning', 'No WhatsApp template mapped for stage: ' . $stage);
        return false;
    }

    $context = aae_order_message_context($order, null, null);
    $context = array_merge($context, $data);

    $components = aae_build_whatsapp_components($stage, $context);

    return $whatsapp->send_template(
        $client->phone,
        $template_name,
        $context['lang'] ?? 'fr',
        $components
    );
}

/**
 * Build WhatsApp template components from context
 */
function aae_build_whatsapp_components($stage, $context)
{
    $components = [];

    // Body parameters - map placeholders to template variables
    $body_params = [];
    $placeholder_map = [
        'contact'    => '{{1}}',
        'vehicle'    => '{{2}}',
        'order'      => '{{3}}',
        'amount'     => '{{4}}',
        'paid'       => '{{5}}',
        'balance'    => '{{6}}',
        'container'  => '{{7}}',
        'bl'         => '{{8}}',
        'tracking'   => '{{9}}',
        'company'    => '{{10}}',
    ];

    foreach ($placeholder_map as $key => $placeholder) {
        if (isset($context[$key]) && $context[$key] !== '') {
            $body_params[] = [
                'type' => 'text',
                'text' => (string) $context[$key]
            ];
        }
    }

    if (!empty($body_params)) {
        $components[] = [
            'type' => 'body',
            'parameters' => $body_params
        ];
    }

    return $components;
}

/**
 * Get client contact info for WhatsApp
 */
function aae_get_client_contact($client_id)
{
    $CI = &get_instance();
    
    // Get primary contact
    $contact = $CI->db->select('phonenumber, email, firstname, lastname')
        ->where('userid', (int) $client_id)
        ->where('is_primary', 1)
        ->get(db_prefix() . 'contacts')
        ->row();

    if ($contact && !empty($contact->phonenumber)) {
        return $contact;
    }

    // Fallback to client company phone
    $client = $CI->db->select('phonenumber, company')
        ->where('userid', (int) $client_id)
        ->get(db_prefix() . 'clients')
        ->row();

    return $client;
}