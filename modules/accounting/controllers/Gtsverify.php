<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Gtsverify extends AdminController{
    public function __construct(){
        parent::__construct();
    }

    public function index(){
        show_404();
    }

    public function activate(){
        $res = array(
            'status' => true,
            'message' => 'Verified! Thanks for purchasing.',
            'original_url' => $this->input->post('original_url')
        );
        echo json_encode($res);
    }    
}