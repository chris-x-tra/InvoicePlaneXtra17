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

    public function get_income_by_year($year)
    {
        $this->db->select('MONTH(payment_date) as month, SUM(payment_amount) as total')
                 ->from('ip_payments')
                 ->where('YEAR(payment_date)', $year)
                 ->group_by('MONTH(payment_date)')
                 ->order_by('month', 'ASC');

        return $this->db->get()->result();
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

public function get_income_details($year)
{
    $this->db->select('
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
    ->order_by('ip_payments.payment_date', 'ASC');

    return $this->db->get()->result();
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
