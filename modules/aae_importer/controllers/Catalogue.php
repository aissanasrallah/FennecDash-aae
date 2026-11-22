<?php
defined('BASEPATH') or exit('No direct script access allowed');

use app\services\ValidatesContact;

/**
 * AAE Import Manager - Espace client
 * URL : aae_importer/catalogue
 * Affiche le catalogue des vehicules disponibles a la vente
 * (parametre : Setup > AAE Import Manager > Catalogue client)
 */
class Catalogue extends ClientsController
{
    use ValidatesContact;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('aae_importer/Aae_importer_model', 'aae_model');
    }

    public function index()
    {
        if (get_option('aae_importer_client_portal') != '1') {
            show_404();
        }

        $vehicles = $this->aae_model->get_vehicles('', 'disponible', '', null, 0, 'marque', 'asc');

        $this->data([
            'vehicles' => $vehicles,
        ]);

        $this->title(_l('aae_client_catalogue_title'));
        $this->view('client_catalogue');
        $this->layout();
    }
}
