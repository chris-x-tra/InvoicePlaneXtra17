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
class Mdl_Users extends Response_Model
{
    public $table = 'ip_users';

    public $primary_key = 'ip_users.user_id';

    public $date_created_field = 'user_date_created';

    public $date_modified_field = 'user_date_modified';

    /**
     * @return array
     */
    public function user_types()
    {
        return [
            '1' => trans('administrator'),
            '2' => trans('guest_read_only'),
            '3' => trans('employee'),
            '4' => trans('supervisor'),
            '5' => trans('manager')
        ];
    }

    /**
     * Familienstand (Personalfragebogen)
     *
     * @return array
     */
    public function marital_statuses(): array
    {
        return [
            'single'    => 'ledig',
            'married'   => 'verheiratet',
            'partner'   => 'eingetragene Lebenspartnerschaft',
            'separated' => 'getrennt lebend',
            'divorced'  => 'geschieden',
            'widowed'   => 'verwitwet',
        ];
    }

    /**
     * Zusätzliche Felder aus dem Personalfragebogen.
     * Nur Felder in den validation_rules werden gespeichert (siehe validation_rules*()).
     *
     * @return array
     */
    private function personnel_rules(): array
    {
        $text = static function (string $field, string $label, int $max): array {
            return ['field' => $field, 'label' => $label, 'rules' => 'trim|max_length[' . $max . ']'];
        };

        return [
            'user_firstname'              => $text('user_firstname', 'Vorname', 100),
            'user_birthname'              => $text('user_birthname', 'Geburtsname', 100),
            'user_birthplace'             => $text('user_birthplace', 'Geburtsort', 100),
            'user_birthdate'              => [
                'field' => 'user_birthdate',
                'label' => 'Geburtsdatum',
                'rules' => 'trim|regex_match[/^\d{4}-\d{2}-\d{2}$/]',
            ],
            'user_religion'               => $text('user_religion', 'Konfession', 50),
            'user_marital_status'         => $text('user_marital_status', 'Familienstand', 30),
            'user_children'               => ['field' => 'user_children', 'label' => 'Kinder', 'rules' => 'trim|is_natural|less_than[31]'],
            'user_nationality'            => $text('user_nationality', 'Nationalität', 100),
            'user_insurance_type'         => ['field' => 'user_insurance_type', 'label' => 'Sozialversicherung', 'rules' => 'trim|in_list[1,2]'],
            'user_health_insurance'       => $text('user_health_insurance', 'Krankenkasse', 100),
            'user_tax_class'              => ['field' => 'user_tax_class', 'label' => 'Steuerklasse', 'rules' => 'trim|in_list[1,2,3,4,5,6]'],
            'user_tax_id'                 => ['field' => 'user_tax_id', 'label' => 'Steuer-ID', 'rules' => 'trim|exact_length[11]|numeric'],
            'user_social_security_number' => $text('user_social_security_number', 'Sozialversicherungsnummer', 16),
        ];
    }

    public function default_select(): void
    {
        $this->db->select('SQL_CALC_FOUND_ROWS ip_users.*', false);
    }

    public function default_order_by(): void
    {
        $this->db->order_by('ip_users.user_name');
    }

    /**
     * @return array
     */
    public function validation_rules()
    {
        return [
            'user_type' => [
                'field' => 'user_type',
                'label' => trans('user_type'),
                'rules' => 'required',
            ],
            'user_email' => [
                'field' => 'user_email',
                'label' => trans('email'),
                'rules' => 'required|valid_email|is_unique[ip_users.user_email]',
            ],
            'user_name' => [
                'field' => 'user_name',
                'label' => trans('name'),
                'rules' => 'required',
            ],
            'user_password' => [
                'field' => 'user_password',
                'label' => trans('password'),
                'rules' => 'required|min_length[8]',
            ],
            'user_passwordv' => [
                'field' => 'user_passwordv',
                'label' => trans('verify_password'),
                'rules' => 'required|matches[user_password]',
            ],
            'user_language' => [
                'field' => 'user_language',
                'label' => trans('language'),
                'rules' => 'required',
            ],
            'user_company' => [
                'field' => 'user_company',
            ],
            'user_address_1' => [
                'field' => 'user_address_1',
            ],
            'user_address_2' => [
                'field' => 'user_address_2',
            ],
            'user_city' => [
                'field' => 'user_city',
            ],
            'user_state' => [
                'field' => 'user_state',
            ],
            'user_zip' => [
                'field' => 'user_zip',
            ],
            'user_country' => [
                'field' => 'user_country',
                'label' => trans('country'),
            ],
            'user_phone' => [
                'field' => 'user_phone',
            ],
            'user_fax' => [
                'field' => 'user_fax',
            ],
            'user_mobile' => [
                'field' => 'user_mobile',
            ],
            'user_web' => [
                'field' => 'user_web',
            ],
            'user_vat_id' => [
                'field' => 'user_vat_id',
            ],
            'user_tax_code' => [
                'field' => 'user_tax_code',
            ],
            'user_invoicing_contact' => [
                'field' => 'user_invoicing_contact',
                'rules' => 'trim',
            ],
            'user_bank' => [
                'field' => 'user_bank',
                'rules' => 'trim',
            ],
            'user_iban' => [
                'field' => 'user_iban',
            ],
            'user_bic' => [
                'field' => 'user_bic',
                'rules' => 'trim|xss_clean',
            ],
            'user_remittance_text' => [
                'field' => 'user_remittance',
                'rules' => 'trim|xss_clean',
            ],
            // SUMEX
            'user_subscribernumber' => [
                'field' => 'user_subscribernumber',
            ],
            'user_gln' => [
                'field' => 'user_gln',
            ],
            'user_rcc' => [
                'field' => 'user_rcc',
            ],
        ] + $this->personnel_rules();
    }

    /**
     * @param int $amount
     *
     * @return mixed
     */
    public function get_latest($amount = 20)
    {
        return $this->mdl_users
            ->where('user_active', 1)
            ->order_by('user_id', 'DESC')
            ->limit($amount)
            ->get()
            ->result();
    }

    /**
     * @return array
     */
    public function validation_rules_existing()
    {
        return [
            'user_type' => [
                'field' => 'user_type',
                'label' => trans('user_type'),
                'rules' => 'required',
            ],
            'user_email' => [
                'field' => 'user_email',
                'label' => trans('email'),
                'rules' => 'required|valid_email',
            ],
            'user_name' => [
                'field' => 'user_name',
                'label' => trans('name'),
                'rules' => 'required',
            ],
            'user_language' => [
                'field' => 'user_language',
                'label' => trans('language'),
                'rules' => 'required',
            ],
            'user_company' => [
                'field' => 'user_company',
            ],
            'user_address_1' => [
                'field' => 'user_address_1',
            ],
            'user_address_2' => [
                'field' => 'user_address_2',
            ],
            'user_city' => [
                'field' => 'user_city',
            ],
            'user_state' => [
                'field' => 'user_state',
            ],
            'user_zip' => [
                'field' => 'user_zip',
            ],
            'user_country' => [
                'field' => 'user_country',
                'label' => trans('country'),
            ],
            'user_phone' => [
                'field' => 'user_phone',
            ],
            'user_fax' => [
                'field' => 'user_fax',
            ],
            'user_mobile' => [
                'field' => 'user_mobile',
            ],
            'user_web' => [
                'field' => 'user_web',
            ],
            'user_vat_id' => [
                'field' => 'user_vat_id',
            ],
            'user_tax_code' => [
                'field' => 'user_tax_code',
            ],
            'user_invoicing_contact' => [
                'field' => 'user_invoicing_contact',
            ],
            'user_bank' => [
                'field' => 'user_bank',
            ],
            'user_iban' => [
                'field' => 'user_iban',
            ],
            'user_bic' => [
                'field' => 'user_bic',
            ],
            'user_remittance_text' => [
                'field' => 'user_remittance',
            ],
            // SUMEX
            'user_subscribernumber' => [
                'field' => 'user_subscribernumber',
            ],
            'user_gln' => [
                'field' => 'user_gln',
            ],
            'user_rcc' => [
                'field' => 'user_rcc',
            ],
        ] + $this->personnel_rules();
    }

    /**
     * @return array
     */
    public function validation_rules_change_password()
    {
        return [
            'user_password' => [
                'field' => 'user_password',
                'label' => trans('password'),
                'rules' => 'required',
            ],
            'user_passwordv' => [
                'field' => 'user_passwordv',
                'label' => trans('verify_password'),
                'rules' => 'required|matches[user_password]',
            ],
        ];
    }

    /**
     * @return array
     */
    public function db_array()
    {
        $db_array = parent::db_array();

        // Leere Eingaben für DATE/INT-Spalten als NULL speichern (sonst Fehler im Strict-Mode)
        foreach (['user_birthdate', 'user_children', 'user_tax_class', 'user_insurance_type'] as $nullable) {
            if (array_key_exists($nullable, $db_array) && $db_array[$nullable] === '') {
                $db_array[$nullable] = null;
            }
        }

        if (isset($db_array['user_password'])) {
            unset($db_array['user_passwordv']);

            $this->load->library('crypt');

            $user_psalt = $this->crypt->salt();

            $db_array['user_psalt']    = $user_psalt;
            $db_array['user_password'] = $this->crypt->generate_password($db_array['user_password'], $user_psalt);
        }

        return $db_array;
    }

    /**
     * @param $user_id
     * @param $password
     */
    public function save_change_password($user_id, $password): void
    {
        $this->load->library('crypt');

        $user_psalt    = $this->crypt->salt();
        $user_password = $this->crypt->generate_password($password, $user_psalt);

        $db_array = [
            'user_psalt'    => $user_psalt,
            'user_password' => $user_password,
        ];

        $this->db->where('user_id', $user_id);
        $this->db->update('ip_users', $db_array);

        $this->session->set_flashdata('alert_success', trans('password_changed'));
    }

    /**
     * @return int|null
     */
    public function save($id = null, $db_array = null)
    {
        $id = parent::save($id, $db_array);

        if ($user_clients = $this->session->userdata('user_clients')) {
            $this->load->model('users/mdl_user_clients');

            foreach ($user_clients as $user_client) {
                $this->mdl_user_clients->save(null, ['user_id' => $id, 'client_id' => $user_client]);
            }

            $this->session->unset_userdata('user_clients');
        }

        return $id;
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
}
