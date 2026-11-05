<?php

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * Einnahme-Belege: Einnahmen, die nicht als Rechnung in InvoicePlane existieren.
 * Gegenstueck zum Expenses-Modul. Zaehlt in der EUR ueber das Bank-Buchungsdatum
 * (Fallback: Belegdatum).
 *
 * 'receipt_date'            Datum auf dem Beleg
 * 'receipt_client_id'       optional, Zahler (ip_clients)
 * 'receipt_amount'          Betrag brutto
 * 'receipt_bank_book_date'  Datum der Gutschrift auf dem Konto
 * 'receipt_bank_book_subject' Verwendungszweck / Kontoauszug
 */

#[AllowDynamicProperties]
class Mdl_Receipts extends Response_Model
{
    public $table = 'ip_receipts';
    public $primary_key = 'ip_receipts.receipt_id';
    public $date_created_field = 'receipt_date_created';
    public $date_modified_field = 'receipt_date_modified';
    public $validation_rules = 'validation_rules';

    /** true, wenn db_array() das Bank-Buchungsdatum mangels Eingabe auf das Belegdatum gesetzt hat */
    public $bank_date_defaulted = false;

    public function receipt_types()
    {
        return [
            '100' => trans('receipt_type_income'),       // Einnahme ohne Rechnung
            '800' => trans('expenses_bank_statement'),   // Kontoauszug - keine Rechnung
            '811' => trans('expenses_tax_refund'),       // Steuererstattung
        ];
    }

    public function default_join()
    {
        $this->db->join('ip_clients', 'ip_clients.client_id = ip_receipts.receipt_client_id', 'left');
    }

    public function default_select()
    {
        $this->db->select(
            'SQL_CALC_FOUND_ROWS ip_receipts.*,
            ip_clients.client_name,
            ip_clients.client_surname,
            ip_clients.client_id',
            false
        );

        $this->db->select('
        (
            SELECT COUNT(*)
            FROM ip_receipts_documents
            WHERE ip_receipts_documents.receipts_id = ip_receipts.receipt_id
              AND document_deleted = 0
        ) AS has_documents
        ', false);
    }

    public function default_order_by()
    {
        $this->db->order_by('ip_receipts.receipt_date DESC, ip_receipts.receipt_id DESC');
    }

    public function validation_rules()
    {
        return [
            'receipt_number' => [
                'field' => 'receipt_number',
                'label' => trans('receipt_number'),
            ],
            'receipt_description' => [
                'field' => 'receipt_description',
                'label' => trans('description'),
                'rules' => 'required',
            ],
            'receipt_category_id' => [
                'field' => 'receipt_category_id',
            ],
            'receipt_client_id' => [
                'field' => 'receipt_client_id',
            ],
            'receipt_date' => [
                'field' => 'receipt_date',
                'label' => trans('receipt_date'),
                'rules' => 'required',
            ],
            'receipt_amount' => [
                'field' => 'receipt_amount',
                'label' => trans('amount'),
                'rules' => 'required|numeric',
            ],
            'receipt_bank_book_date' => [
                'field' => 'receipt_bank_book_date',
            ],
            'receipt_bank_book_subject' => [
                'field' => 'receipt_bank_book_subject',
            ],
        ];
    }

    /**
     * Betrag VOR der Validierung in Datenbankformat bringen ("1.234,50" -> "1234.50").
     *
     * Bewusst kein CI-Callback (callback_xyz): IPs MY_Model::run_validation() ruft
     * form_validation->run() ohne Model auf, CI sucht Callbacks dann nicht im Model.
     * standardize_amount() (number_helper) richtet sich nach den Systemeinstellungen.
     */
    public function run_validation($validation_rules = null)
    {
        if (isset($_POST['receipt_amount'])) {
            $_POST['receipt_amount'] = standardize_amount(trim($_POST['receipt_amount']));
        }

        return parent::run_validation($validation_rules);
    }

    /**
     * Leere Datums-/ID-Felder als NULL speichern (statt '' bzw. 0000-00-00),
     * damit "kein Bankdatum" sauber unterscheidbar bleibt.
     */
    public function db_array()
    {
        $db_array = parent::db_array();

        foreach (['receipt_date', 'receipt_bank_book_date', 'receipt_client_id'] as $field) {
            if (array_key_exists($field, $db_array) && $db_array[$field] === '') {
                $db_array[$field] = null;
            }
        }

        // Bank-Buchungsdatum ist Pflicht: leer -> Belegdatum (der Controller zeigt eine Warnung)
        if (array_key_exists('receipt_bank_book_date', $db_array)
            && $db_array['receipt_bank_book_date'] === null
            && ! empty($db_array['receipt_date'])) {
            $db_array['receipt_bank_book_date'] = $db_array['receipt_date'];
            $this->bank_date_defaulted = true;
        }

        return $db_array;
    }
}
