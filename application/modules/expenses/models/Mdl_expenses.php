<?php

if (! defined('BASEPATH')) {
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

/* 

// expense-module done by chrissie ^ x-tra-designs in 12.2024
// descriptions of fieldata     // example value
'expense_id',                   // 0			<- primary key
'expense_number',               // XTD-B-0001           // my number number in the ip program - ??? not used atm - rethink
'expense_description',         	// Netzteil schnell besorgt wegen notfall // my personal desription
'expense_status_id',            // paid, ???
'expense_category_id',          // ? <- buchhaltungskategorie for later use
'expense_supplier_id',          // 1			// id in clients_table for clients which are marked as suppliers

'expense_date',                 // 01.07.2024		// date on suppliere expense
'expense_due_date',             // 14.07.2024
'expense_paid_date',            // 18.07.2024
'expense_amount',               // 39.95 [EUR]
'expense_bank_book_date',        // 19.07.2024
'expense_bank_book_subject',    // Zahlung an ARLT
'expense_date_created',         // 01.07.2024   <- auto fields erstellt
'expense_date_modified'         // 19.07.2024   <- auto fields modified

*/


#[AllowDynamicProperties]
class Mdl_Expenses extends Response_Model
{
    public $table = 'ip_expenses';
    public $primary_key = 'ip_expenses.expense_id';
    public $date_created_field = 'expense_date_created';
    public $date_modified_field = 'expense_date_modified';


    public function expense_types()
    {
        return array(
            // zeilen-nummer anlage eur
            '26' => trans('goods_receipt'),          // bezogene waren, dienstleistungen
            '27' => trans('third_party_services'),   // bezogene Fremdleistungen
            '57' => trans('work_equipment'),         // Arbeitsmittel
            '66' => trans('operating_expenses'),     // Betriebsausgaben

            // sonstiges by chrissie
            '800' => trans('expenses_bank_statement'),         // Kontoauszug - keine Rechnung
            '810' => trans('expenses_tax'),                    // Steuern
            '811' => trans('expenses_tax_refund')              // Steuern ru"ckzahlung
        );
    }

    public function default_join()
    {
        $this->db->join(
            'ip_clients',
            'ip_clients.client_id = ip_expenses.expense_supplier_id',
            'left' // falls es Expenses ohne Supplier gibt
        );
    }

    public function default_select()
    {
        // welche felder von ip_clients wg left join
        $this->db->select(
        'SQL_CALC_FOUND_ROWS ' . $this->table . '.*,  
        ip_clients.client_name,
        ip_clients.client_surname,
        ip_clients.client_id,
        client_address_1,
        client_address_2,
        client_city,
        client_state,
        client_zip'
        , false) ;

        // sind auch dokumente da
        $this->db->select('
        (
            SELECT COUNT(*) 
            FROM ip_expenses_documents 
            WHERE ip_expenses_documents.expenses_id = ip_expenses.expense_id 
              AND document_deleted = 0
        ) AS has_documents
        ', false);
    }

    public function default_order_by()
    {
        $this->db->order_by('ip_expenses.expense_id');
    }

    public function validation_rules()
    {
	    return [
		    'expense_number' => [
		    	'field' => 'expense_number',
		    	'label' => trans('expense_number'),
		    ],
		    'expense_description' => [
			'field' => 'expense_description',
		    	'label' => trans('expense_description'),
		    	'rules' => 'required',
		    ],
		    'expense_status_id' => [
			'field' => 'expense_status_id',
		    ],
		    'expense_category_id' => [
			'field' => 'expense_category_id',
		    ],
		    'expense_supplier_id' => [
			'field' => 'expense_supplier_id',
		    	'rules' => 'required',
		    ],
		    'expense_date' => [
			'field' => 'expense_date',
		    	'rules' => 'required',
		    ],
		    'expense_due_date' => [
			'field' => 'expense_due_date' 
		    ],
		    'expense_paid_date' => [
			'field' => 'expense_paid_date',
		    ],
		    'expense_amount' => [
			'field' => 'expense_amount',
            'rules' => 'callback_convert_amount',
		    ],
		    'expense_bank_book_date' => [
			'field' => 'expense_bank_book_date',
		    ],
		    'expense_bank_book_subject' => [
			'field' => 'expense_bank_book_subject',
		    ],
		    'expense_date_created' => [
			'rules' => 'required',
		    ],
	    ];
    }
                
    public function convert_amount($input)
    {
        if ($input == '') {
            return '';
        }
        return standardize_amount($input);
    }

    public function is_overdue()
    {
    }

    public function is_open()
    {
    }

    /**
     * @param int $amount
     * @return mixed
     */
    public function get_latest($amount = 10)
    {
        return $this->mdl_expenses
            ->order_by('expense_id', 'DESC')
            ->limit($amount)
            ->get()
            ->result();
    }

    public function delete($id)
    {
        parent::delete($id);
        $this->load->helper('orphan');
        delete_orphans();
    }

}
