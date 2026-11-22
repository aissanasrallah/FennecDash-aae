<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Quickshare_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param integer (optional)
     * @return object
     */
    public function getFileBasedOnKey($fileKey = '')
    {
        $this->db->where('file_key', $fileKey);
        return $this->db->get(db_prefix() . 'quick_share_uploads')->row();
    }

    public function getFileBasedOnId($fileId = '')
    {
        $this->db->where('id', $fileId);
        return $this->db->get(db_prefix() . 'quick_share_uploads')->row();
    }

    /**
     * Add new file
     * @param mixed $data All $_POST dat
     * @return mixed
     */
    public function add($data)
    {
        $this->db->insert(db_prefix() . 'quick_share_uploads', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    /**
     * Delete file
     * @param mixed $file_id file id
     * @return boolean
     */
    public function delete($file_id)
    {
        $id = (int)$file_id;
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'quick_share_uploads');
        $deleted = $this->db->affected_rows() > 0;

        $this->db->where('download_id', $id);
        $this->db->delete(db_prefix() . 'quick_share_downloads');

        $dirs = [
            qs_private_upload_base() . $id . DIRECTORY_SEPARATOR,
            FCPATH . 'modules/quick_share/uploads/' . $id . '/',
        ];
        foreach ($dirs as $directory) {
            if (is_dir($directory)) {
                delete_dir($directory);
            }
        }

        return $deleted;
    }

    public function update_file_content($fileId, $data)
    {
        $this->db->where('id', $fileId);
        $this->db->update(db_prefix() . 'quick_share_uploads', $data);

        $updated = false;

        if ($this->db->affected_rows() > 0) {
            $updated = true;
        }
        return $updated;
    }

    public function add_download($data)
    {
        $this->db->insert(db_prefix() . 'quick_share_downloads', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    public function check_downloads_of_file($fileId)
    {
        return (int)$this->db->where('download_id', (int)$fileId)->count_all_results(db_prefix() . 'quick_share_downloads');
    }

    /**
     * Atomically validate availability and claim a download slot inside a row-locked transaction
     */
    public function consume_download_atomic($fileId, $maxDownloads = 0, $isAutoDestroy = false, $ip = '')
    {
        $id = (int)$fileId;
        if ($id <= 0) {
            return ['ok' => false, 'reason' => 'invalid'];
        }

        $this->db->trans_begin();
        $row = $this->db->query(
            'SELECT id, status, auto_destroy, created_at FROM `' . db_prefix() . 'quick_share_uploads` WHERE id = ? FOR UPDATE',
            [$id]
        )->row();

        if (!$row || (int)$row->status !== 0) {
            $this->db->trans_rollback();
            return ['ok' => false, 'reason' => 'unavailable'];
        }

        $currentCount = (int)$this->db->where('download_id', $id)->count_all_results(db_prefix() . 'quick_share_downloads');

        if ($isAutoDestroy || (int)$row->auto_destroy === 1) {
            if ($currentCount > 0) {
                $this->db->where('id', $id)->update(db_prefix() . 'quick_share_uploads', ['status' => 1]);
                $this->db->trans_commit();
                return ['ok' => false, 'reason' => 'consumed'];
            }
            $this->db->query(
                'UPDATE `' . db_prefix() . 'quick_share_uploads` SET status = 1 WHERE id = ? AND status = 0',
                [$id]
            );
            if ($this->db->affected_rows() !== 1) {
                $this->db->trans_rollback();
                return ['ok' => false, 'reason' => 'race_lost'];
            }
        } elseif ($maxDownloads > 0 && $currentCount >= $maxDownloads) {
            $this->db->where('id', $id)->update(db_prefix() . 'quick_share_uploads', ['status' => 1]);
            $this->db->trans_commit();
            return ['ok' => false, 'reason' => 'limit_reached'];
        }

        $this->db->insert(db_prefix() . 'quick_share_downloads', [
            'download_id' => $id,
            'ip' => (string)$ip,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return ['ok' => false, 'reason' => 'log_failed'];
        }

        if (!$isAutoDestroy && $maxDownloads > 0 && ($currentCount + 1) >= $maxDownloads) {
            $this->db->where('id', $id)->update(db_prefix() . 'quick_share_uploads', ['status' => 1]);
        }

        $this->db->trans_commit();
        return ['ok' => true];
    }

    /**
     * Notify staff members about goal result
     * @param mixed $id goal id
     * @param string $notify_type is success or failed
     * @param mixed $achievement total achievent (Option)
     * @return boolean
     */
    public function notify_staff_members($id, $notify_type, $achievement = '')
    {
        $goal = $this->get($id);
        if ($achievement == '') {
            $achievement = $this->calculate_goal_achievement($id);
        }
        if ($notify_type == 'success') {
            $goal_desc = 'not_goal_message_success';
        } else {
            $goal_desc = 'not_goal_message_failed';
        }

        if ($goal->staff_id == 0) {
            $this->load->model('staff_model');
            $staff = $this->staff_model->get('', ['active' => 1]);
        } else {
            $this->db->where('active', 1)
                ->where('staffid', $goal->staff_id);
            $staff = $this->db->get(db_prefix() . 'staff')->result_array();
        }

        $notifiedUsers = [];
        foreach ($staff as $member) {
            if (is_staff_member($member['staffid'])) {
                $notified = add_notification([
                    'fromcompany' => 1,
                    'touserid' => $member['staffid'],
                    'description' => $goal_desc,
                    'additional_data' => serialize([
                        format_goal_type($goal->goal_type),
                        $goal->achievement,
                        $achievement['total'],
                        _d($goal->start_date),
                        _d($goal->end_date),
                    ]),
                ]);
                if ($notified) {
                    array_push($notifiedUsers, $member['staffid']);
                }
            }
        }

        pusher_trigger_notification($notifiedUsers);
        $this->mark_as_notified($goal->id);

        if (count($staff) > 0 && $this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function mark_as_notified($id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'goals', [
            'notified' => 1,
        ]);
    }
}
