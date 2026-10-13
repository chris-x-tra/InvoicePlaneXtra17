<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Budget - maricare - gemeinsame Logik fuer Clients-Ansicht und Reports-Ansicht.
 *
 * Konfiguration: application/config/budget.php
 *
 * Nutzung:
 *   $this->load->library('budget');
 *   $types  = $this->budget->types();
 *   $report = $this->budget->report($year, $client_id);   // ein Kunde
 *   $report = $this->budget->report($year);               // alle Kunden
 */
class Budget
{
    protected $CI;
    protected $flags;
    protected $types;
    protected $start_month;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('budget');
        $this->CI->config->load('budget', true);
        $cfg = $this->CI->config->item('budget');

        $this->flags       = $cfg['budget_flags'];
        $this->start_month = max(1, min(12, (int) $cfg['budget_start_month']));
        $this->types       = $this->build_types($cfg['budget_types']);
    }

    /** Budget-Positionen inkl. berechneter Bitmasks 'and' / 'result'. */
    public function types()
    {
        return $this->types;
    }

    public function start_month()
    {
        return $this->start_month;
    }

    /** Trifft diese Rechnungsart auf die Budget-Position zu? */
    public function matches($invoice_type, array $def)
    {
        return (((int) $invoice_type) & $def['and']) === $def['result'];
    }

    /** Anzeige-Text des Abrechnungszeitraums, z.B. "2.2026 - 1.2027". */
    public function period_label($year)
    {
        $m = $this->start_month;
        $y = (int) $year;
        return ($m == 1) ? "1.$y - 12.$y" : "$m.$y - " . ($m - 1) . "." . ($y + 1);
    }

    /** Die 12 Monate des Abrechnungszeitraums als 'YYYY-MM'. */
    public function months($year)
    {
        $start  = $this->period_start($year);
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $months[] = date('Y-m', strtotime($start . " +$i month"));
        }
        return $months;
    }

    /**
     * Budgetverbrauch pro Kunde, Position und Monat - in EINER Abfrage.
     *
     * @param int            $year        Startjahr des Abrechnungszeitraums
     * @param int|int[]|null $client_ids  null = alle Kunden mit Rechnungen im Zeitraum
     *                                    sonst: diese Kunden (auch ohne Rechnungen, mit 0)
     * @return array [
     *   'year' => int, 'label' => string, 'months' => ['YYYY-MM', ...],
     *   'clients' => [client_id => ['name' => str,
     *                               'amounts'    => [key => sum],
     *                               'sparklines' => [key => [ym => sum]]]],
     *   'totals'  => [key => sum],
     * ]
     */
    public function report($year, $client_ids = null)
    {
        $db    = $this->CI->db;
        $start = $this->period_start($year);
        $end   = date('Y-m-d', strtotime($start . ' +1 year')); // exklusiv

        $db->select(
            "c.client_id, c.client_name, c.client_surname,
             DATE_FORMAT(i.invoice_date_created, '%Y-%m') AS ym,
             i.invoice_type,
             SUM(a.invoice_total) AS total",
            false
        );
        $db->from('ip_invoices AS i');
        $db->join('ip_invoice_amounts AS a', 'a.invoice_id = i.invoice_id');
        $db->join('ip_clients AS c', 'c.client_id = i.client_id');
        $this->apply_filters($db, $start, $end);
        if ($client_ids !== null) {
            $db->where_in('i.client_id', (array) $client_ids);
        }
        $db->group_by(['c.client_id', 'c.client_name', 'c.client_surname', 'ym', 'i.invoice_type']);
        $db->order_by('c.client_name, c.client_surname');
        $rows = $db->get()->result();

        $keys        = array_keys($this->types);
        $months      = $this->months($year);
        $empty_month = array_fill_keys($months, 0.0);
        $clients     = [];
        $totals      = array_fill_keys($keys, 0.0);

        $new_client = function ($name) use ($keys, $empty_month) {
            return [
                'name'       => $name,
                'amounts'    => array_fill_keys($keys, 0.0),
                'sparklines' => array_fill_keys($keys, $empty_month),
            ];
        };

        if ($client_ids !== null) {
            foreach ((array) $client_ids as $cid) {
                $clients[$cid] = $new_client('');
            }
        }

        foreach ($rows as $r) {
            $cid = $r->client_id;
            if (!isset($clients[$cid])) {
                $clients[$cid] = $new_client('');
            }
            $clients[$cid]['name'] = trim($r->client_name . ' ' . $r->client_surname);

            foreach ($this->types as $key => $def) {
                if ($this->matches($r->invoice_type, $def)) {
                    $amount = (float) $r->total;
                    $clients[$cid]['amounts'][$key]            += $amount;
                    $clients[$cid]['sparklines'][$key][$r->ym] += $amount;
                    $totals[$key]                              += $amount;
                }
            }
        }

        return [
            'year'    => (int) $year,
            'label'   => $this->period_label($year),
            'months'  => $months,
            'clients' => $clients,
            'totals'  => $totals,
        ];
    }

    // ------------------------------------------------------------------

    /**
     * EINZIGE Stelle fuer die Rechnungs-Filter (Datumsfeld, Status, ...).
     * Muss dasselbe tun wie Mdl_reports::invoice_type_client_amount().
     */
    protected function apply_filters($db, $start, $end)
    {
        $db->where('i.invoice_date_created >=', $start);
        $db->where('i.invoice_date_created <', $end);
        // TODO: Status-Filter (Entwuerfe/Stornos) aus invoice_type_client_amount() uebernehmen
    }

    protected function period_start($year)
    {
        return sprintf('%04d-%02d-01', (int) $year, $this->start_month);
    }

    protected function build_types(array $types)
    {
        $out = [];
        foreach ($types as $key => $def) {
            $on  = $this->mask($def['on'] ?? [], $key);
            $off = $this->mask($def['off'] ?? [], $key);
            $out[$key] = $def + ['short' => $def['label']];
            $out[$key]['and']    = $on | $off;
            $out[$key]['result'] = $on;
        }
        return $out;
    }

    protected function mask(array $flag_names, $type_key)
    {
        $mask = 0;
        foreach ($flag_names as $name) {
            if (!isset($this->flags[$name])) {
                show_error("budget config: unbekanntes Flag '$name' in '$type_key'");
            }
            $mask |= (int) $this->flags[$name];
        }
        return $mask;
    }
}
