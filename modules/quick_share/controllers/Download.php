<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Download extends App_Controller
{
    public function __construct()
    {
        hooks()->do_action('after_clients_area_init', $this);

        parent::__construct();

        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once(__DIR__ . '/../vendor/autoload.php');
        }

        $this->load->model('quickshare_model');
        $this->load->model('emails_model');
        $this->load->model('staff_model');
        $this->load->helper('quick_share/quick_share');
    }

    /**
     * Enforce status, revocation, expiry, and download limits without information leakage
     * @param  object|null $getFileData
     * @return array ['ok' => bool, 'max_downloads' => int]
     */
    private function check_link_availability($getFileData)
    {
        if (is_null($getFileData) || (int)$getFileData->status !== 0) {
            return ['ok' => false, 'max_downloads' => 0];
        }
        if (isset($getFileData->expires_at) && !empty($getFileData->expires_at) && strtotime($getFileData->expires_at) <= time()) {
            return ['ok' => false, 'max_downloads' => 0];
        }
        $expiry_days = (int)get_option('qs_link_expiry_days');
        if ($expiry_days > 0 && !empty($getFileData->created_at)) {
            $exp_ts = strtotime($getFileData->created_at . ' +' . $expiry_days . ' days');
            if ($exp_ts !== false && $exp_ts <= time()) {
                return ['ok' => false, 'max_downloads' => 0];
            }
        }
        $max_dl = (isset($getFileData->max_downloads) && (int)$getFileData->max_downloads > 0)
            ? (int)$getFileData->max_downloads
            : (int)get_option('qs_max_downloads_per_link');
        if ((int)$getFileData->auto_destroy === 1) {
            $max_dl = 1;
        }
        if ($max_dl > 0 && $this->quickshare_model->check_downloads_of_file($getFileData->id) >= $max_dl) {
            return ['ok' => false, 'max_downloads' => $max_dl];
        }
        return ['ok' => true, 'max_downloads' => $max_dl];
    }

    public function index()
    {
        show_404();
    }

    public function file($file_key = '')
    {
        $this->load->helper('form');

        $file_key = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$file_key);
        $data['title']             = _l('quickshare');
        $data['password_verified'] = '0';
        $data['is_file_destroyed'] = '0';
        $data['verified_password'] = '';

        $getFileData = ($file_key !== '') ? $this->quickshare_model->getFileBasedOnKey($file_key) : null;
        $avail = $this->check_link_availability($getFileData);

        if (!$avail['ok']) {
            show_404();
        }

        if (!empty($getFileData->password)) {
            $data['password_verified'] = '0';
        } else {
            $data['password_verified'] = '1';
        }

        if ($this->input->post('password', false) !== null && $this->input->post('password', false) !== '') {
            $password = (string)$this->input->post('password', false);

            if (!empty($getFileData->password) && password_verify($password, $getFileData->password)) {
                $data['password_verified'] = '1';
                $data['verified_password'] = $password;
            } else {
                $data['password_verified'] = '0';
            }
        }

        $data['file_data'] = $getFileData;
        $this->load->view('download_page/download', $data);
    }

    public function downloadFile($fileKey)
    {
        $fileKey = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$fileKey);
        $getFileData = ($fileKey !== '') ? $this->quickshare_model->getFileBasedOnKey($fileKey) : null;
        $avail = $this->check_link_availability($getFileData);

        if (!$avail['ok']) {
            show_404();
        }

        if (!empty($getFileData->password)) {
            $password = $this->input->post('password', false);
            if (!is_string($password) || $password === '' || !password_verify($password, $getFileData->password)) {
                set_alert('danger', 'Invalid Password');
                redirect(base_url('quick_share/download/file/' . $getFileData->file_key));
            }
        }

        $localFilePath = false;
        if ((int)$getFileData->destination !== 1) {
            $localFilePath = qs_resolve_private_file_path($getFileData->id, $getFileData->file_path);
            if ($localFilePath === false) {
                show_404();
            }
        }

        $consume = $this->quickshare_model->consume_download_atomic(
            $getFileData->id,
            $avail['max_downloads'],
            ((int)$getFileData->auto_destroy === 1),
            $this->input->ip_address()
        );
        if (!$consume['ok']) {
            show_404();
        }

        if ((int)$getFileData->destination !== 1) {
            $payload = @file_get_contents($localFilePath);
            if ($payload === false) {
                show_404();
            }
            if ((int)$getFileData->auto_destroy === 1) {
                @unlink($localFilePath);
                $parentDir = dirname($localFilePath);
                if (is_dir($parentDir) && count(@scandir($parentDir) ?: []) <= 2) {
                    @rmdir($parentDir);
                }
            }
            $staffData = $this->staff_model->get($getFileData->user_id);
            if ($staffData && !empty($staffData->email)) {
                try {
                    $this->emails_model->send_simple_email(
                        $staffData->email,
                        'Someone has downloaded your items !',
                        'Dear,<br>A User has downloaded your file(s).<br><b>File downloaded:</b><br>' . htmlspecialchars(basename((string)$getFileData->file_path), ENT_QUOTES, 'UTF-8') . '<br>Best regards,<br>' . htmlspecialchars((string)get_option('companyname'), ENT_QUOTES, 'UTF-8')
                    );
                } catch (\Throwable $e) {
                }
            }
            get_instance()->load->helper('download');
            force_download(basename((string)$getFileData->file_path), $payload);
        } else {
            $s3 = new Aws\S3\S3Client([
                'region'  => get_option('qs_aws_region'),
                'version' => 'latest',
                'credentials' => [
                    'key'    => get_option('qs_aws_key'),
                    'secret' => get_option('qs_aws_secret'),
                ]
            ]);

            $object = $s3->getObject(array(
                'Bucket' => get_option('qs_aws_bucket'),
                'Key'    => $getFileData->file_path
            ));

            header('Content-Description: File Transfer');
            header('Content-Type: ' . $object->get('ContentType'));
            header('Content-Disposition: attachment; filename="' . basename((string) $getFileData->file_path) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            echo $object['Body']->getContents();
        }

        $staffData = $this->staff_model->get($getFileData->user_id);
        if ($staffData && !empty($staffData->email)) {
            $this->emails_model->send_simple_email(
                $staffData->email,
                'Someone has downloaded your items !',
                'Dear,<br>A User has downloaded your file(s).<br><b>File downloaded:</b><br>' . htmlspecialchars(basename((string) $getFileData->file_path), ENT_QUOTES, 'UTF-8') . '<br>Best regards,<br>' . htmlspecialchars((string) get_option('companyname'), ENT_QUOTES, 'UTF-8')
            );
        }

    }

}
