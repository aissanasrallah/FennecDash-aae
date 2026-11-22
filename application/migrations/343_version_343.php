<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_343 extends CI_Migration
{
    public function up(): void
    {
        add_option('auto_update_test_option', '1');
    }
}
