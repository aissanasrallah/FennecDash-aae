<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Webhook public pour recevoir automatiquement les demandes de devis
 * depuis le site vitrine Algerie Auto Export vers Fennec Dash Auto Export (Leads).
 *
 * URL : POST /aae_importer/webhook/lead
 */
class Webhook extends App_Controller
{
    public function lead()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = $this->input->post(null, true) ?: [];
        }

        $expectedSecret = getenv('AAE_WEBHOOK_SECRET') ?: (get_option('aae_webhook_secret') ?: 'aae-fennec-dash-2026');
        $providedSecret = $_SERVER['HTTP_X_AAE_WEBHOOK_SECRET'] ?? ($payload['secret'] ?? '');

        if (!hash_equals((string) $expectedSecret, (string) $providedSecret)) {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $channel = trim((string) ($payload['channel'] ?? 'devis_form'));
        $name = trim((string) ($payload['name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? ''));
        $vehicle = trim((string) ($payload['vehicle'] ?? ''));
        $vehicleUrl = trim((string) ($payload['vehicle_url'] ?? ''));
        $vehiclePrice = (float) ($payload['vehicle_price'] ?? 0);
        $message = trim((string) ($payload['message'] ?? ''));

        if ($name === '' && $channel === 'whatsapp') {
            $name = $vehicle !== '' ? 'Client WhatsApp - ' . $vehicle : 'Client WhatsApp';
        }

        if ($name === '') {
            $this->output->set_status_header(422);
            echo json_encode(['success' => false, 'message' => 'Name is required']);
            return;
        }

        $sourceName = $channel === 'whatsapp'
            ? 'WhatsApp - Algérie Auto Export'
            : 'Site Web - Algérie Auto Export';

        $sourceId = $this->resolveSourceId($sourceName);
        $statusId = $this->resolveDefaultStatusId();
        $assignedStaffId = $this->resolveDefaultStaffId();

        $descriptionLines = [];
        if ($vehicle !== '') {
            $descriptionLines[] = 'Véhicule : ' . $vehicle;
        }
        if ($vehiclePrice > 0) {
            $descriptionLines[] = 'Prix affiché : ' . number_format($vehiclePrice, 0, ',', ' ') . ' €';
        }
        if ($vehicleUrl !== '') {
            $descriptionLines[] = 'Lien véhicule : ' . $vehicleUrl;
        }
        $descriptionLines[] = 'Canal : ' . ($channel === 'whatsapp' ? 'WhatsApp Devis' : 'Formulaire Demander un devis');
        if ($message !== '') {
            $descriptionLines[] = '';
            $descriptionLines[] = 'Message client :';
            $descriptionLines[] = $message;
        }

        $now = date('Y-m-d H:i:s');
        $hash = function_exists('app_generate_hash') ? app_generate_hash() : bin2hex(random_bytes(16));

        $leadData = [
            'hash' => $hash,
            'name' => mb_substr($name, 0, 191),
            'title' => $vehicle !== '' ? mb_substr('Devis : ' . $vehicle, 0, 100) : 'Demande de devis',
            'email' => $email !== '' ? mb_substr($email, 0, 100) : null,
            'phonenumber' => $phone !== '' ? mb_substr($phone, 0, 50) : null,
            'description' => implode("\n", $descriptionLines),
            'source' => $sourceId,
            'status' => $statusId,
            'assigned' => $assignedStaffId,
            'addedfrom' => $assignedStaffId,
            'dateadded' => $now,
            'lastcontact' => $now,
            'lead_value' => $vehiclePrice > 0 ? $vehiclePrice : null,
            'is_public' => 1,
            'leadorder' => 1,
        ];

        $this->db->insert(db_prefix() . 'leads', $leadData);
        $leadId = (int) $this->db->insert_id();

        if ($leadId > 0) {
            $this->db->insert(db_prefix() . 'lead_activity_log', [
                'leadid' => $leadId,
                'description' => 'Demande de devis reçue automatiquement depuis Algérie Auto Export (' . $sourceName . ')',
                'date' => $now,
                'staffid' => $assignedStaffId,
                'full_name' => 'Algérie Auto Export',
                'custom_activity' => 1,
            ]);
        }

        echo json_encode([
            'success' => $leadId > 0,
            'lead_id' => $leadId,
        ]);
    }

    private function resolveSourceId(string $sourceName): int
    {
        $row = $this->db->where('name', $sourceName)->get(db_prefix() . 'leads_sources')->row();
        if ($row) {
            return (int) $row->id;
        }
        $this->db->insert(db_prefix() . 'leads_sources', ['name' => $sourceName]);
        return (int) $this->db->insert_id();
    }

    private function resolveDefaultStatusId(): int
    {
        $row = $this->db->order_by('statusorder', 'ASC')->limit(1)->get(db_prefix() . 'leads_status')->row();
        return $row ? (int) $row->id : 1;
    }

    private function resolveDefaultStaffId(): int
    {
        $row = $this->db->where('active', 1)->order_by('admin', 'DESC')->order_by('staffid', 'ASC')->limit(1)->get(db_prefix() . 'staff')->row();
        return $row ? (int) $row->staffid : 1;
    }
}
