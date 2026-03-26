<?php

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author      InvoicePlane Developers & Contributors
 * @copyright   Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license     https://invoiceplane.com/license.txt
 * @link        https://invoiceplane.com
 */

#[AllowDynamicProperties]
class Mdl_Clients extends Response_Model
{
    public $table = 'ip_clients';

    public $primary_key = 'ip_clients.client_id';

    public $date_created_field = 'client_date_created';

    public $date_modified_field = 'client_date_modified';

    public function default_select(): void
    {
        $this->db->select(
            'SQL_CALC_FOUND_ROWS ' . $this->table . '.*, ' .
            'CONCAT(' . $this->table . '.client_name, " ", ' . $this->table . '.client_surname) as client_fullname, '
            ." ip_clients.*, "
            ." ip_client_extended.* "
            , false) ;
    }

    public function default_join()
    {
        $this->db->join('ip_client_extended', 'ip_client_extended.client_id = ip_clients.client_id', 'left');
    }

    public function default_order_by(): void
    {
        $this->db->order_by('ip_clients.client_name');
    }

    public function validation_rules()
    {
        return [
            'client_title' => [
                'field' => 'client_title',
                'label' => trans('client_title'),
            ],
            'client_salutation' => [
                'field' => 'client_salutation',
            ],
            'client_contact_person' => [
                'field' => 'client_contact_person',
            ],
            'client_name' => [
                'field' => 'client_name',
                'label' => trans('client_name'),
                'rules' => 'required',
            ],
            'client_surname' => [
                'field' => 'client_surname',
                'label' => trans('client_surname'),
            ],
            'client_active' => [
                'field' => 'client_active',
            ],
            'client_language' => [
                'field' => 'client_language',
                'label' => trans('language'),
                'rules' => 'trim',
            ],
            'client_address_1' => [
                'field' => 'client_address_1',
            ],
            'client_address_2' => [
                'field' => 'client_address_2',
            ],
            'client_city' => [
                'field' => 'client_city',
            ],
            'client_state' => [
                'field' => 'client_state',
            ],
            'client_zip' => [
                'field' => 'client_zip',
            ],
            'client_country' => [
                'field' => 'client_country',
                'rules' => 'trim',
            ],
            'client_phone' => [
                'field' => 'client_phone',
            ],
            'client_fax' => [
                'field' => 'client_fax',
            ],
            'client_mobile' => [
                'field' => 'client_mobile',
            ],
            'client_email' => [
                'field' => 'client_email',
            ],
            'client_web' => [
                'field' => 'client_web',
            ],
            'client_company' => [
                'field' => 'client_company',
            ],
            'client_vat_id' => [
                'field' => 'client_vat_id',
            ],
            'client_tax_code' => [
                'field' => 'client_tax_code',
            ],
            'client_invoicing_contact' => [
                'field' => 'client_invoicing_contact',
                'rules' => 'trim',
            ],
            'client_einvoicing_version' => [
                'field' => 'client_einvoicing_version',
//                'rules' => 'callback_validate_einvoicing_version',    // commented out by chrissie
            ],
            'client_einvoicing_active' => [
                'field' => 'client_einvoicing_active',
            ],
            // SUMEX
            'client_birthdate' => [
                'field' => 'client_birthdate',
                'rules' => 'callback_convert_date',
            ],
            'client_gender' => [
                'field' => 'client_gender',
            ],
            'client_avs' => [
                'field' => 'client_avs',
                'label' => trans('sumex_ssn'),
                'rules' => 'callback_fix_avs',
            ],
            'client_insurednumber' => [
                'field' => 'client_insurednumber',
                'label' => trans('sumex_insurednumber'),
            ],
            'client_veka' => [
                'field' => 'client_veka',
                'label' => trans('sumex_veka'),
            ],
            'delivery_salutation' => [
                'field' => 'delivery_salutation',
            ],
            'delivery_contact_person' => [
                'field' => 'delivery_contact_person',
            ],
            'delivery_name' => [
                'field' => 'delivery_name',
            ],
            'delivery_name2' => [
                'field' => 'delivery_name2',
            ],
            'delivery_address_1' => [
                'field' => 'delivery_address_1',
            ],
            'delivery_address_2' => [
                'field' => 'delivery_address_2',
            ],
            'delivery_city' => [
                'field' => 'delivery_city',
            ],
            'delivery_zip' => [
                'field' => 'delivery_zip',
            ],
            'delivery_state' => [
                'field' => 'delivery_state',
            ],
            'delivery_country' => [
                'field' => 'delivery_country',
            ],
            'delivery_phone' => [
                'field' => 'delivery_phone',
            ],
            'delivery_email' => [
                'field' => 'delivery_email',
            ],
            'invoice_salutation' => [
                'field' => 'invoice_salutation',
            ],
            'invoice_contact_person' => [
                'field' => 'invoice_contact_person',
            ],
            'invoice_name' => [
                'field' => 'invoice_name',
            ],
            'invoice_name2' => [
                'field' => 'invoice_name2',
            ],
            'invoice_address_1' => [
                'field' => 'invoice_address_1',
            ],
            'invoice_address_2' => [
                'field' => 'invoice_address_2',
            ],
            'invoice_city' => [
                'field' => 'invoice_city',
            ],
            'invoice_zip' => [
                'field' => 'invoice_zip',
            ],
            'invoice_state' => [
                'field' => 'invoice_state',
            ],
            'invoice_country' => [
                'field' => 'invoice_country',
            ],
            'invoice_phone' => [
                'field' => 'invoice_phone',
            ],
            'invoice_email' => [
                'field' => 'invoice_email',
            ],
        ];
    }

    /**
     * @param int $amount
     *
     * @return mixed
     */
    public function get_latest($amount = 10)
    {
        return $this->mdl_clients
            ->where('client_active', 1)
            ->order_by('ip_clients.client_id', 'DESC')
            ->limit($amount)
            ->get()
            ->result();
    }

    /**
     * @return string
     */
    public function fix_avs($input)
    {
        if ($input != '') {
            if (preg_match('/(\d{3})\.(\d{4})\.(\d{4})\.(\d{2})/', $input, $matches)) {
                return $matches[1] . $matches[2] . $matches[3] . $matches[4];
            }

            if (preg_match('/^\d{13}$/', $input)) {
                return $input;
            }
        }

        return '';
    }

    public function convert_date($input)
    {
        $this->load->helper('date_helper');

        if ($input == '') {
            return '';
        }

        return date_to_mysql($input);
    }

    /**
     * Validates the e-invoicing version to prevent path traversal attacks.
     *
     * @param string $version The e-invoicing version to validate
     *
     * @return bool
     */
    public function validate_einvoicing_version($version)
    {
        // Empty is allowed (no e-invoicing)
        if (empty($version)) {
            return true;
        }

        // Load helper to access validation function
        $this->load->helper('e-invoice');

        // Validate using the helper function
        if ( ! is_valid_xml_config_id($version)) {
            $this->form_validation->set_message('validate_einvoicing_version', trans('einvoicing_version_invalid'));

            return false;
        }

        return true;
    }

    public function db_array()
    {
        $db_array = parent::db_array();

        if ( ! isset($db_array['client_active'])) {
            $db_array['client_active'] = 0;
        }

        return $db_array;
    }

    /**
     * @param int $id
     */
    public function delete($id): void
    {
        parent::delete($id);

        $this->load->helper('orphan');
        delete_orphans();
    }

    /**
     * Returns client_id of existing client.
     *
     * @param $client_name
     *
     * @return int|null
     */
    public function client_lookup($client_name)
    {
        $client = $this->mdl_clients->where('client_name', $client_name)->get();

        if ($client->num_rows()) {
            $client_id = $client->row()->client_id;
        } else {
            $db_array = [
                'client_name' => $client_name,
            ];

            $client_id = parent::save(null, $db_array);
        }

        return $client_id;
    }

    public function with_total()
    {
        $this->filter_select('IFnull((SELECT SUM(invoice_total) FROM ip_invoice_amounts WHERE invoice_id IN (SELECT invoice_id FROM ip_invoices WHERE ip_invoices.client_id = ip_clients.client_id)), 0) AS client_invoice_total', false);

        return $this;
    }

    public function with_total_paid()
    {
        $this->filter_select('IFnull((SELECT SUM(invoice_paid) FROM ip_invoice_amounts WHERE invoice_id IN (SELECT invoice_id FROM ip_invoices WHERE ip_invoices.client_id = ip_clients.client_id)), 0) AS client_invoice_paid', false);

        return $this;
    }

    public function with_total_balance()
    {
        $this->filter_select('IFnull((SELECT SUM(invoice_balance) FROM ip_invoice_amounts WHERE invoice_id IN (SELECT invoice_id FROM ip_invoices WHERE ip_invoices.client_id = ip_clients.client_id)), 0) AS client_invoice_balance', false);

        return $this;
    }


    /**
     * @param $user_id
     *
     * @return $this
     */
    public function get_not_assigned_to_user($user_id)
    {
        $this->load->model('user_clients/mdl_user_clients');
        $clients = $this->mdl_user_clients->select('ip_user_clients.client_id')
            ->assigned_to($user_id)->get()->result();

        $assigned_clients = [];
        foreach ($clients as $client) {
            $assigned_clients[] = $client->client_id;
        }

        if ($assigned_clients !== []) {
            $this->where_not_in('ip_clients.client_id', $assigned_clients);
        }

        $this->is_active();

        return $this->get()->result();
    }

    public function is_inactive()
    {
        $this->filter_where('client_active', 0);
        $this->filter_where('ip_client_extended.client_type', 1);
        return $this;
    }

    public function is_active()
    {
        $this->filter_where('client_active', 1);
        $this->filter_where('ip_client_extended.client_type', 1);
        return $this;
    }

    public function is_supplier()
    {
        $this->filter_where('ip_client_extended.client_type', 2);
        return $this;
    }

    /* search adresses modal */
    public function search_addresses($q)
    {
        $this->db->select('*');
        $this->db->from('ip_clients');
        $this->db->group_start();
            $this->db->like('invoice_name', $q);
            $this->db->or_like('invoice_name2', $q);
        $this->db->group_end();

        // Gruppieren nach allen adressrelevanten Feldern
        $this->db->group_by([
            'invoice_salutation',
            'invoice_contact_person',
            'invoice_name',
            'invoice_name2',
            'invoice_address_1',
            'invoice_address_2',
            'invoice_zip',
            'invoice_city'
        ]);

        $query = $this->db->get();
        return $query->result();
    }
}
