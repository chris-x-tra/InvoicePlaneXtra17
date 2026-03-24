<?php

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author		InvoicePlane Developers & Contributors
 * @copyright	Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license		https://invoiceplane.com/license.txt
 * @link		https://invoiceplane.com
 */

#[AllowDynamicProperties]
class Mdl_Client_Notes extends Response_Model
{
    public $table = 'ip_client_notes';

    public $primary_key = 'ip_client_notes.client_note_id';

    public function default_order_by()
    {
        $this->db->order_by('ip_client_notes.client_note_date DESC');
    }

    public function validation_rules()
    {
        return [
            'client_id' => [
                'field' => 'client_id',
                'label' => trans('client'),
                'rules' => 'required',
            ],
            'client_note' => [
                'field' => 'client_note',
                'label' => trans('note'),
                'rules' => 'required',
            ],
        ];
    }

    public function db_array()
    {
        $db_array = parent::db_array();

        $db_array['client_note_date'] = date('Y-m-d');
        $db_array['client_note_timestamp'] = date('Y-m-d H-i-s');

        return $db_array;
    }

    /**
     * @param int $id
     */
    public function delete($id): bool
    {
        parent::delete($id);

        // For Ajax Check if deletion was successful
        return true;
    }

    public function get_notes($client_id = null)
    {
        $this->db->from($this->table);

        // Optional: nach Client filtern
        if ($client_id !== null) {
            $this->db->where('client_id', (int)$client_id);
        }
        $this->db->limit(100);
        // Sortierung (neueste zuerst)
        $this->db->order_by('client_note_timestamp', 'DESC');

        return $this->db->get()->result_array();

    }

    public function get_notes_ts($timestamp, $client_id = null)
    {
        $this->db->from($this->table);

        // Optional: nach Client filtern
        if ($client_id !== null) {
            $this->db->where('client_id', (int)$client_id);
        }

        // Nur Notes neuer als Timestamp
        $this->db->where('client_note_timestamp >', $timestamp);

        // Sortierung (neueste zuerst)
        $this->db->order_by('client_note_timestamp', 'DESC');

        return $this->db->get()->result_array();
    }
}
