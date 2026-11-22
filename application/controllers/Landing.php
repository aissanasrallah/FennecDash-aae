<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Landing extends CI_Controller
{
    private $langMap = [
        'en'  => 'english',
        'fr'  => 'french',
        'ar'  => 'arabic',
    ];

    public function index($lang = '')
    {
        // Check if landing page is enabled (default to FALSE / 0 for Algérie Auto Export)
        $landing_enabled = get_option('landing_page_enabled');
        if ($landing_enabled === '' || $landing_enabled === null) {
            $landing_enabled = 0;
        }
        
        if (!$landing_enabled) {
            $this->login_selection();
            return;
        }

        $language = $this->resolveLanguage($lang);

        $this->lang->load('landing_lang', $language);

        $data['language'] = $language;
        $data['lang_code'] = array_search($language, $this->langMap);
        $data['dir']      = ($language == 'arabic') ? 'rtl' : 'ltr';

        $this->load->view('landing/index', $data);
    }

    /**
     * Display login selection page when landing page is disabled
     */
    public function login_selection()
    {
        $language = $this->resolveLanguage('');

        $this->lang->load('landing_lang', $language);

        $data['language'] = $language;
        $data['lang_code'] = array_search($language, $this->langMap);
        $data['dir']      = ($language == 'arabic') ? 'rtl' : 'ltr';
        $data['admin_login_url'] = admin_url('authentication');
        $data['client_login_url'] = site_url('authentication/login');
        // Author content management is handled through the Admin panel
        $data['author_login_url'] = admin_url('authentication');

        $this->load->view('landing/login_selection', $data);
    }

    private function resolveLanguage($input)
    {
        $supported = array_values($this->langMap);

        // Direct match (english/french/arabic)
        if ($input && in_array($input, $supported)) {
            return $input;
        }

        // Short code match (en/fr/ar)
        $input = strtolower($input);
        if ($input && isset($this->langMap[$input])) {
            return $this->langMap[$input];
        }

        // ?lang=en query param
        $query = $this->input->get('lang');
        if ($query) {
            $query = strtolower($query);
            if (in_array($query, $supported)) {
                return $query;
            }
            if (isset($this->langMap[$query])) {
                return $this->langMap[$query];
            }
        }

        // Browser detection
        $detected = $this->detectBrowserLanguage();
        if ($detected) {
            return $detected;
        }

        return 'english';
    }

    private function detectBrowserLanguage()
    {
        $accept = $this->input->server('HTTP_ACCEPT_LANGUAGE');
        if (!$accept) {
            return false;
        }

        preg_match_all('/([a-z]{2}(?=[-_])|[a-z]{2}(?![a-z]))/i', $accept, $matches);
        foreach ($matches[0] as $code) {
            $code = strtolower($code);
            if (isset($this->langMap[$code])) {
                return $this->langMap[$code];
            }
        }

        return false;
    }
}
