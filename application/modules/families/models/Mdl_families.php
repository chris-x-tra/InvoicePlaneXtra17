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
class Mdl_Families extends Response_Model
{
    public $table = 'ip_families';

    public $primary_key = 'ip_families.family_id';

    public function default_select()
    {
        // family_account_id = Einnahmekonto (Kontenrahmen, Modul account_chart); Spalte ip_families.family_account_id
        $this->db->select('SQL_CALC_FOUND_ROWS ip_families.*, ip_account_chart.account_number, ip_account_chart.account_name', false);
    }

    public function default_join()
    {
        $this->db->join('ip_account_chart', 'ip_account_chart.account_id = ip_families.family_account_id', 'left');
    }

    /** Einnahmekonten fuer die Auswahlliste, gruppiert: [Gruppe => [account_id => "Nr. - Name"]] */
    public function account_options()
    {
        $this->load->model('account_chart/mdl_account_chart');

        return $this->mdl_account_chart->get_grouped(Mdl_Account_Chart::TYPE_INCOME);
    }

    /** Konto muss ein Einnahmekonto sein, sonst bleibt die Familie ohne Konto */
    public function run_validation($validation_rules = null)
    {
        if (isset($_POST['family_account_id']) && $_POST['family_account_id'] !== '') {
            $this->load->model('account_chart/mdl_account_chart');
            if ( ! $this->mdl_account_chart->is_valid_account($_POST['family_account_id'], Mdl_Account_Chart::TYPE_INCOME)) {
                $_POST['family_account_id'] = '';
            }
        }

        return parent::run_validation($validation_rules);
    }

    /** leeres Konto als NULL speichern */
    public function db_array()
    {
        $db_array = parent::db_array();

        if (array_key_exists('family_account_id', $db_array) && $db_array['family_account_id'] === '') {
            $db_array['family_account_id'] = null;
        }

        return $db_array;
    }

    public function default_order_by()
    {
        $this->db->order_by('ip_families.family_name');
    }

    /**
     * @return array
     */
    public function validation_rules()
    {
        return [
            'family_name' => [
                'field' => 'family_name',
                'label' => trans('family_name'),
                'rules' => 'required',
            ],
            'family_account_id' => [
                'field' => 'family_account_id',
                'label' => trans('family_account'),
            ],
        ];
    }
}
