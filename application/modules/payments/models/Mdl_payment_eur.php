<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author		InvoicePlane Developers & Contributors
 * @copyright	Copyright (c) 2012 - 2025 InvoicePlane.com
 * @license		https://invoiceplane.com/license.txt
 * @link		https://invoiceplane.com
 */

#[AllowDynamicProperties]
class Mdl_Payment_Eur extends Response_Model
{
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
   }

    public function get_expenses_by_year($year)
    {
        $this->db->select('MONTH(expense_bank_book_date) as month, SUM(expense_amount) as total')
                 ->from('ip_expenses')
                 ->where('YEAR(expense_bank_book_date)', $year)
                 ->group_by('MONTH(expense_bank_book_date)')
                 ->order_by('month', 'ASC');

        return $this->db->get()->result();
    }

    /**
     * Einnahmen pro Monat: Zahlungen auf Rechnungen (payment_date)
     * plus Einnahme-Belege ohne Rechnung (Bank-Buchungsdatum, Pflichtfeld).
     */
    public function get_income_by_year($year)
    {
        $year   = (int) $year;
        $months = [];

        $payments = $this->db->select('MONTH(payment_date) as month, SUM(payment_amount) as total', false)
                 ->from('ip_payments')
                 ->where('YEAR(payment_date)', $year)
                 ->group_by('MONTH(payment_date)')
                 ->get()->result();

        foreach ($payments as $row) {
            $months[(int) $row->month] = (float) $row->total;
        }

        if ($this->receipts_available()) {
            $date = 'receipt_bank_book_date';
            $receipts = $this->db->select("MONTH($date) as month, SUM(receipt_amount) as total", false)
                     ->from('ip_receipts')
                     ->where("YEAR($date) = " . $year, null, false)
                     ->group_by("MONTH($date)", false)
                     ->get()->result();

            foreach ($receipts as $row) {
                $months[(int) $row->month] = ($months[(int) $row->month] ?? 0) + (float) $row->total;
            }
        }

        ksort($months);

        $out = [];
        foreach ($months as $month => $total) {
            $out[] = (object) ['month' => $month, 'total' => $total];
        }

        return $out;
    }

    /** true, wenn die Tabelle ip_receipts schon angelegt ist (receipts.sql) */
    private function receipts_available()
    {
        return $this->db->table_exists('ip_receipts');
    }

public function get_expenses_details($year)
{
    $this->db->select('ip_expenses.expense_date,
                        ip_clients.client_id,
                       ip_clients.client_name,
                       ip_clients.client_surname,
                       ip_expenses.expense_id,
                        ip_expenses.expense_bank_book_date,
                       ip_expenses.expense_description,
                       ip_expenses.expense_amount')
             ->from('ip_expenses')
             ->join(
                 'ip_clients',
                 'ip_clients.client_id = ip_expenses.expense_supplier_id',
                 'left' // left join, falls kein Supplier vorhanden
             )
             ->where('YEAR(ip_expenses.expense_bank_book_date)', $year)
             ->order_by('ip_expenses.expense_date', 'ASC');

    return $this->db->get()->result();
}

/**
 * Einnahmen-Details fuer das Jahr: Zahlungen auf Rechnungen + Einnahme-Belege,
 * normalisiert (type, id, label, doc_date, booking_date, client_*, amount) und nach Buchungsdatum sortiert.
 */
public function get_income_details($year)
{
    $year = (int) $year;
    $rows = [];

    $payments = $this->db->select('
        ip_payments.payment_date,
        ip_payments.payment_amount,
        ip_payments.payment_note,
        ip_payments.invoice_id,
        ip_invoices.invoice_number,
        ip_invoices.invoice_date_created,
        ip_invoices.client_id,
        ip_clients.client_name,
        ip_clients.client_surname
    ')
    ->from('ip_payments')
    ->join('ip_invoices', 'ip_invoices.invoice_id = ip_payments.invoice_id', 'left')
    ->join('ip_clients', 'ip_clients.client_id = ip_invoices.client_id', 'left')
    ->where('YEAR(ip_payments.payment_date)', $year)
    ->get()->result();

    foreach ($payments as $p) {
        $rows[] = (object) [
            'type'           => 'invoice',
            'id'             => $p->invoice_id,
            'label'          => $p->invoice_number,
            'doc_date'       => $p->invoice_date_created,
            'booking_date'   => $p->payment_date,
            'client_id'      => $p->client_id,
            'client_name'    => $p->client_name,
            'client_surname' => $p->client_surname,
            'amount'         => $p->payment_amount,
        ];
    }

    if ($this->receipts_available()) {
        $date = 'ip_receipts.receipt_bank_book_date';
        $receipts = $this->db->select("
            ip_receipts.receipt_id,
            ip_receipts.receipt_description,
            ip_receipts.receipt_date,
            ip_receipts.receipt_amount,
            $date AS booking_date,
            ip_clients.client_id,
            ip_clients.client_name,
            ip_clients.client_surname
        ", false)
        ->from('ip_receipts')
        ->join('ip_clients', 'ip_clients.client_id = ip_receipts.receipt_client_id', 'left')
        ->where("YEAR($date) = " . $year, null, false)
        ->get()->result();

        foreach ($receipts as $r) {
            $rows[] = (object) [
                'type'           => 'receipt',
                'id'             => $r->receipt_id,
                'label'          => $r->receipt_description,
                'doc_date'       => $r->receipt_date,
                'booking_date'   => $r->booking_date,
                'client_id'      => $r->client_id,
                'client_name'    => $r->client_name,
                'client_surname' => $r->client_surname,
                'amount'         => $r->receipt_amount,
            ];
        }
    }

    usort($rows, function ($a, $b) {
        return strcmp((string) $a->booking_date, (string) $b->booking_date)
            ?: strcmp((string) $a->doc_date, (string) $b->doc_date);
    });

    return $rows;
}

/*
public function get_income_details($year)
{
    $this->db->select('payment_date, payment_amount, payment_note, ip_payments.invoice_id,
        ip_invoices.invoice_number, ip_invoices.client_id')
             ->from('ip_payments')
             ->join(
                 'ip_invoices',
                 'ip_invoices.invoice_id = ip_payments.invoice_id',
                 'left' 
             )
             ->where('YEAR(payment_date)', $year)
             ->order_by('payment_date', 'ASC');
    return $this->db->get()->result();
}
*/
}
