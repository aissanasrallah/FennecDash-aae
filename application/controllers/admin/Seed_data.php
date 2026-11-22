<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Seed_data extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        if (!is_admin()) {
            access_denied('Seed Data');
        }
    }

    public function index()
    {
        $data['title'] = 'Seed Demo Data';
        $this->load->view('admin/seed_data/index', $data);
    }

    public function run()
    {
        if (!$this->input->is_ajax_request()) {
            redirect(admin_url('seed_data'));
        }

        $this->load->model('payment_modes_model');
        $this->load->model('currencies_model');
        $this->load->model('contract_types_model');
        $this->load->model('estimate_request_model');
        $this->load->model('client_groups_model');
        $this->load->model('leads_model');
        $this->load->model('departments_model');
        $this->load->model('tickets_model');
        $this->load->model('spam_filters_model');

        $results = [];

        $results['payment_modes'] = $this->_seed_payment_modes();
        $results['currencies'] = $this->_seed_currencies();
        $results['contract_types'] = $this->_seed_contract_types();
        $results['estimate_request_statuses'] = $this->_seed_estimate_request_statuses();
        $results['estimate_request_forms'] = $this->_seed_estimate_request_forms();
        $results['customer_groups'] = $this->_seed_customer_groups();
        $results['lead_sources'] = $this->_seed_lead_sources();
        $results['lead_statuses'] = $this->_seed_lead_statuses();
        $results['departments'] = $this->_seed_departments();
        $results['ticket_priorities'] = $this->_seed_ticket_priorities();
        $results['ticket_statuses'] = $this->_seed_ticket_statuses();
        $results['ticket_services'] = $this->_seed_ticket_services();
        $results['predefined_replies'] = $this->_seed_predefined_replies();
        $results['spam_filters'] = $this->_seed_spam_filters();

        echo json_encode(['success' => true, 'results' => $results]);
    }

    private function _seed_payment_modes()
    {
        $exists = $this->db->where('name', 'Bank Transfer')->get(db_prefix() . 'payment_modes')->num_rows();
        if ($exists > 0) {
            return ['skipped' => 'Data already exists'];
        }

        $modes = [
            ['name' => 'Bank Transfer', 'description' => 'Payment via bank wire transfer', 'active' => 1, 'show_on_pdf' => 1, 'selected_by_default' => 1],
            ['name' => 'Check', 'description' => 'Payment by check', 'active' => 1, 'show_on_pdf' => 1, 'selected_by_default' => 0],
            ['name' => 'Cash', 'description' => 'Payment in cash', 'active' => 1, 'show_on_pdf' => 1, 'selected_by_default' => 0],
            ['name' => 'Credit Card', 'description' => 'Payment via credit or debit card', 'active' => 1, 'show_on_pdf' => 1, 'selected_by_default' => 0],
            ['name' => 'PayPal', 'description' => 'Payment via PayPal', 'active' => 1, 'show_on_pdf' => 1, 'selected_by_default' => 0],
        ];

        $count = 0;
        foreach ($modes as $mode) {
            $this->payment_modes_model->add($mode);
            $count++;
        }

        return ['inserted' => $count];
    }

    private function _seed_currencies()
    {
        $exists = $this->db->where('name', 'GBP')->get(db_prefix() . 'currencies')->num_rows();
        if ($exists > 0) {
            return ['skipped' => 'Data already exists'];
        }

        $currencies = [
            ['name' => 'GBP', 'symbol' => '£', 'decimal_separator' => '.', 'thousand_separator' => ',', 'placement' => 'before'],
            ['name' => 'MAD', 'symbol' => 'د.م.', 'decimal_separator' => '.', 'thousand_separator' => ',', 'placement' => 'before'],
            ['name' => 'TND', 'symbol' => 'د.ت.', 'decimal_separator' => '.', 'thousand_separator' => ',', 'placement' => 'before'],
        ];

        $count = 0;
        foreach ($currencies as $currency) {
            $this->currencies_model->add($currency);
            $count++;
        }

        return ['inserted' => $count];
    }

    private function _seed_contract_types()
    {
        $exists = $this->db->where('name', 'Service Contract')->get(db_prefix() . 'contracts_types')->num_rows();
        if ($exists > 0) {
            return ['skipped' => 'Data already exists'];
        }

        $types = [
            'Service Contract',
            'Employment Contract',
            'Lease Agreement',
            'Non-Disclosure Agreement',
            'Maintenance Contract',
        ];

        $count = 0;
        foreach ($types as $name) {
            $this->contract_types_model->add(['name' => $name]);
            $count++;
        }

        return ['inserted' => $count];
    }

    private function _seed_estimate_request_statuses()
    {
        $exists = $this->db->where('name', 'Pending Review')->get(db_prefix() . 'estimate_request_status')->num_rows();
        if ($exists > 0) {
            return ['skipped' => 'Data already exists'];
        }

        $statuses = [
            ['name' => 'Pending Review', 'color' => '#f59e0b', 'statusorder' => 1],
            ['name' => 'Processing', 'color' => '#3b82f6', 'statusorder' => 2],
            ['name' => 'Completed', 'color' => '#10b981', 'statusorder' => 3],
            ['name' => 'Canceled', 'color' => '#ef4444', 'statusorder' => 4],
        ];

        $count = 0;
        foreach ($statuses as $status) {
            $this->estimate_request_model->add_status($status);
            $count++;
        }

        return ['inserted' => $count];
    }

    private function _seed_estimate_request_forms()
    {
        $exists = $this->db->where('name', 'Quick Quote Request')->get(db_prefix() . 'estimate_request_forms')->num_rows();
        if ($exists > 0) {
            return ['skipped' => 'Data already exists'];
        }

        $forms = [
            [
                'name' => 'Quick Quote Request',
                'language' => 'french',
                'success_submit_msg' => 'Thank you! We will review your request shortly.',
                'submit_btn_name' => 'Submit Request',
                'notify_request_submitted' => 1,
                'notify_type' => 'specific_staff',
                'notify_ids_staff' => [1],
            ],
            [
                'name' => 'Detailed Project Quote',
                'language' => 'french',
                'success_submit_msg' => 'Your detailed quote request has been received.',
                'submit_btn_name' => 'Send Request',
                'notify_request_submitted' => 1,
                'notify_type' => 'specific_staff',
                'notify_ids_staff' => [1],
            ],
        ];

        $count = 0;
        foreach ($forms as $form) {
            $this->estimate_request_model->add_form($form);
            $count++;
        }

        return ['inserted' => $count];
    }

    private function _seed_customer_groups()
    {
        $exists = $this->db->where('name', 'VIP')->get(db_prefix() . 'customers_groups')->num_rows();
        if ($exists > 0) {
            return ['skipped' => 'Data already exists'];
        }

        $groups = [
            'VIP',
            'Regular',
            'Wholesale',
            'Retail',
            'Enterprise',
        ];

        $count = 0;
        foreach ($groups as $name) {
            $this->client_groups_model->add(['name' => $name]);
            $count++;
        }

        return ['inserted' => $count];
    }

    private function _seed_lead_sources()
    {
        $exists = $this->db->where('name', 'Website')->get(db_prefix() . 'leads_sources')->num_rows();
        if ($exists > 0) {
            return ['skipped' => 'Data already exists'];
        }

        $sources = [
            'Website',
            'Facebook',
            'Google Ads',
            'Referral',
            'Phone Inquiry',
            'Email Campaign',
        ];

        $count = 0;
        foreach ($sources as $name) {
            $this->leads_model->add_source(['name' => $name]);
            $count++;
        }

        return ['inserted' => $count];
    }

    private function _seed_lead_statuses()
    {
        $exists = $this->db->where('name', 'New')->get(db_prefix() . 'leads_status')->num_rows();
        if ($exists > 0) {
            return ['skipped' => 'Data already exists'];
        }

        $statuses = [
            ['name' => 'New', 'color' => '#3b82f6', 'statusorder' => 1, 'isdefault' => 1],
            ['name' => 'Contacted', 'color' => '#f59e0b', 'statusorder' => 2],
            ['name' => 'Qualified', 'color' => '#10b981', 'statusorder' => 3],
            ['name' => 'Lost', 'color' => '#ef4444', 'statusorder' => 4],
            ['name' => 'Converted to Customer', 'color' => '#8b5cf6', 'statusorder' => 5],
        ];

        $count = 0;
        foreach ($statuses as $status) {
            $this->leads_model->add_status($status);
            $count++;
        }

        return ['inserted' => $count];
    }

    private function _seed_departments()
    {
        $exists = $this->db->where('name', 'Sales')->get(db_prefix() . 'departments')->num_rows();
        if ($exists > 0) {
            return ['skipped' => 'Data already exists'];
        }

        $departments = [
            ['name' => 'Sales', 'email' => 'sales@example.com', 'hidefromclient' => 0],
            ['name' => 'Support', 'email' => 'support@example.com', 'hidefromclient' => 0],
            ['name' => 'Billing', 'email' => 'billing@example.com', 'hidefromclient' => 0],
            ['name' => 'Development', 'email' => 'dev@example.com', 'hidefromclient' => 1],
            ['name' => 'Marketing', 'email' => 'marketing@example.com', 'hidefromclient' => 1],
        ];

        $count = 0;
        foreach ($departments as $dept) {
            $this->departments_model->add($dept);
            $count++;
        }

        return ['inserted' => $count];
    }

    private function _seed_ticket_priorities()
    {
        $exists = $this->db->where('name', 'Urgent')->get(db_prefix() . 'tickets_priorities')->num_rows();
        if ($exists > 0) {
            return ['skipped' => 'Data already exists'];
        }

        $priorities = ['Urgent'];

        $count = 0;
        foreach ($priorities as $name) {
            $this->tickets_model->add_priority(['name' => $name]);
            $count++;
        }

        return ['inserted' => $count];
    }

    private function _seed_ticket_statuses()
    {
        return ['skipped' => 'Default statuses already exist from install'];
    }

    private function _seed_ticket_services()
    {
        $exists = $this->db->where('name', 'Technical Support')->get(db_prefix() . 'services')->num_rows();
        if ($exists > 0) {
            return ['skipped' => 'Data already exists'];
        }

        $services = [
            'Technical Support',
            'Consultation',
            'Installation',
            'Training',
            'Maintenance',
        ];

        $count = 0;
        foreach ($services as $name) {
            $this->tickets_model->add_service(['name' => $name]);
            $count++;
        }

        return ['inserted' => $count];
    }

    private function _seed_predefined_replies()
    {
        $exists = $this->db->where('name', 'Greeting')->get(db_prefix() . 'tickets_predefined_replies')->num_rows();
        if ($exists > 0) {
            return ['skipped' => 'Data already exists'];
        }

        $replies = [
            [
                'name' => 'Greeting',
                'message' => "Hello,\n\nThank you for contacting our support team. We have received your request and will get back to you shortly.\n\nBest regards,\nSupport Team",
            ],
            [
                'name' => 'Request More Info',
                'message' => "Dear Customer,\n\nCould you please provide us with more details about your issue? Please include any relevant screenshots or error messages.\n\nThank you,\nSupport Team",
            ],
            [
                'name' => 'Issue Resolved',
                'message' => "Hello,\n\nWe believe this issue has been resolved. Please let us know if you have any further questions.\n\nBest regards,\nSupport Team",
            ],
            [
                'name' => 'Apology for Delay',
                'message' => "Dear Customer,\n\nWe apologize for the delay in responding to your request. We are experiencing a high volume of inquiries and appreciate your patience.\n\nBest regards,\nSupport Team",
            ],
        ];

        $count = 0;
        foreach ($replies as $reply) {
            $this->tickets_model->add_predefined_reply($reply);
            $count++;
        }

        return ['inserted' => $count];
    }

    private function _seed_spam_filters()
    {
        $exists = $this->db->where('type', 'sender')->where('value', 'spam@example.com')->get(db_prefix() . 'spam_filters')->num_rows();
        if ($exists > 0) {
            return ['skipped' => 'Data already exists'];
        }

        $filters = [
            ['type' => 'sender', 'value' => 'spam@example.com', 'rel_type' => 'tickets'],
            ['type' => 'sender', 'value' => 'noreply@mailer.com', 'rel_type' => 'tickets'],
            ['type' => 'subject', 'value' => 'Buy cheap', 'rel_type' => 'tickets'],
            ['type' => 'subject', 'value' => 'Viagra', 'rel_type' => 'tickets'],
            ['type' => 'phrase', 'value' => 'Click here to win', 'rel_type' => 'tickets'],
            ['type' => 'sender', 'value' => 'scam@example.com', 'rel_type' => 'leads'],
            ['type' => 'subject', 'value' => 'Make money fast', 'rel_type' => 'leads'],
        ];

        $count = 0;
        foreach ($filters as $filter) {
            $rel_type = $filter['rel_type'];
            unset($filter['rel_type']);
            $this->spam_filters_model->add($filter, $rel_type);
            $count++;
        }

        return ['inserted' => $count];
    }
}
