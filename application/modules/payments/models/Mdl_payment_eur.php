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

    /*
     * ------------------------------------------------------------------
     * Kontenaufschluesselung (EUER nach Konten)
     * ------------------------------------------------------------------
     * Struktur: [monat => [kontoschluessel => ['account_id', 'total', 'lines' => [bezeichnung => betrag]]]]
     * kontoschluessel = account_id oder 0 = "nicht zugeordnet".
     */

    private function breakdown_add(array &$tree, int $month, int $account_id, string $line, float $amount): void
    {
        if ( ! isset($tree[$month][$account_id])) {
            $tree[$month][$account_id] = ['account_id' => $account_id, 'total' => 0.0, 'lines' => []];
        }
        $tree[$month][$account_id]['total'] += $amount;
        $tree[$month][$account_id]['lines'][$line] = ($tree[$month][$account_id]['lines'][$line] ?? 0.0) + $amount;
    }

    /**
     * Einnahmen je Monat und Konto.
     *
     * - Rechnungen (EUER = Zufluss): jede Zahlung wird anteilig nach dem Positionsbetrag (item_total) auf die
     *   Positionen der Rechnung verteilt; das Konto kommt aus der Produktfamilie der Position
     *   (ip_invoice_items.item_product_id -> ip_products.family_id -> ip_families.family_account_id).
     *   Positionen ohne Produkt, ohne Familie oder ohne Familienkonto landen unter "nicht zugeordnet".
     *   Die Summe der Anteile ist immer exakt der Zahlungsbetrag (Rundungsrest auf der letzten Position).
     * - Einnahme-Belege: direkt auf dem Konto des Belegs (Bank-Buchungsdatum).
     */
    public function get_income_breakdown($year)
    {
        $year = (int) $year;
        $tree = [];

        $payments = $this->db->select('payment_id, invoice_id, payment_date, payment_amount')
            ->from('ip_payments')
            ->where('YEAR(payment_date)', $year)
            ->get()->result();

        $invoice_ids = array_values(array_unique(array_map(static function ($p) {
            return (int) $p->invoice_id;
        }, $payments)));

        $items = [];   // invoice_id => [[weight, account_id, line], ...]
        $has_account = $this->db->field_exists('family_account_id', 'ip_families');

        foreach (array_chunk($invoice_ids, 500) as $chunk) {
            $rows = $this->db->select(
                'i.invoice_id, i.item_product_id, a.item_total, f.family_id, f.family_name, '
                . ($has_account ? 'f.family_account_id' : 'NULL AS family_account_id'),
                false
            )
                ->from('ip_invoice_items i')
                ->join('ip_invoice_item_amounts a', 'a.item_id = i.item_id', 'left')
                ->join('ip_products p', 'p.product_id = i.item_product_id', 'left')
                ->join('ip_families f', 'f.family_id = p.family_id', 'left')
                ->where_in('i.invoice_id', $chunk)
                ->order_by('i.invoice_id')->order_by('i.item_id')
                ->get()->result();

            foreach ($rows as $r) {
                if ($r->family_id && $r->family_account_id) {
                    $acc  = (int) $r->family_account_id;
                    $line = $r->family_name;
                } elseif ($r->family_id) {
                    $acc  = 0;
                    $line = 'Produktfamilie „' . $r->family_name . '“ (ohne Konto)';
                } else {
                    $acc  = 0;
                    $line = 'Positionen ohne Produktfamilie';
                }
                $items[(int) $r->invoice_id][] = [(float) $r->item_total, $acc, $line];
            }
        }

        foreach ($payments as $p) {
            $month  = (int) date('n', strtotime($p->payment_date));
            $amount = round((float) $p->payment_amount, 2);
            $pos    = $items[(int) $p->invoice_id] ?? [];
            $sum_w  = array_sum(array_column($pos, 0));

            if ( ! $pos || abs($sum_w) < 0.005) {
                $this->breakdown_add($tree, $month, 0, 'Rechnung ohne Positionen', $amount);
                continue;
            }

            $rest = $amount;
            $last = count($pos) - 1;
            foreach ($pos as $n => [$w, $acc, $line]) {
                $share = ($n === $last) ? $rest : round($amount * $w / $sum_w, 2);
                $rest  = round($rest - $share, 2);
                $this->breakdown_add($tree, $month, $acc, $line, $share);
            }
        }

        if ($this->receipts_available()) {
            $rows = $this->db->select(
                'MONTH(receipt_bank_book_date) AS month, receipt_category_id, SUM(receipt_amount) AS total, COUNT(*) AS cnt',
                false
            )
                ->from('ip_receipts')
                ->where('YEAR(receipt_bank_book_date) = ' . $year, null, false)
                ->group_by('MONTH(receipt_bank_book_date), receipt_category_id', false)
                ->get()->result();

            foreach ($rows as $r) {
                $this->breakdown_add(
                    $tree,
                    (int) $r->month,
                    (int) $r->receipt_category_id,
                    'Einnahme-Belege (' . (int) $r->cnt . ')',
                    (float) $r->total
                );
            }
        }

        return $tree;
    }

    /** Ausgaben je Monat und Konto (Bank-Buchungsdatum) */
    public function get_expense_breakdown($year)
    {
        $tree = [];

        $rows = $this->db->select(
            'MONTH(expense_bank_book_date) AS month, expense_category_id, SUM(expense_amount) AS total, COUNT(*) AS cnt',
            false
        )
            ->from('ip_expenses')
            ->where('YEAR(expense_bank_book_date)', (int) $year)
            ->group_by('MONTH(expense_bank_book_date), expense_category_id', false)
            ->get()->result();

        foreach ($rows as $r) {
            $this->breakdown_add(
                $tree,
                (int) $r->month,
                (int) $r->expense_category_id,
                'Ausgabebelege (' . (int) $r->cnt . ')',
                (float) $r->total
            );
        }

        return $tree;
    }

    /** Monatsstruktur zu einer Jahressumme je Konto zusammenfassen (gleiche Struktur ohne Monatsebene) */
    public static function breakdown_year(array $tree): array
    {
        $year = [];
        foreach ($tree as $accounts) {
            foreach ($accounts as $acc_id => $a) {
                if ( ! isset($year[$acc_id])) {
                    $year[$acc_id] = ['account_id' => $acc_id, 'total' => 0.0, 'lines' => []];
                }
                $year[$acc_id]['total'] += $a['total'];
                foreach ($a['lines'] as $line => $amount) {
                    // Zaehler in "(n)" mitsummieren: Beleg-Zeilen heissen je Monat anders -> auf Basisnamen vereinheitlichen
                    $base = preg_replace('/ \(\d+\)$/', '', $line);
                    $year[$acc_id]['lines'][$base] = ($year[$acc_id]['lines'][$base] ?? 0.0) + $amount;
                }
            }
        }

        return $year;
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
