<?php

defined('BASEPATH') or exit('No direct script access allowed');

$config['migration_enabled'] = true;
$config['migration_type'] = 'sequential';
$config['migration_table'] = 'migrations';
$config['migration_auto_latest'] = true;
$config['migration_version'] = 343; // 3.4.3
$config['migration_path'] = APPPATH . 'migrations/';
