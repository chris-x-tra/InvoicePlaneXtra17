<?php

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * Kontenrahmen (SKR03) fuer Ausgaben und Einnahme-Belege.
 * Aufbau wie das Modul families. ip_expenses.expense_category_id und
 * ip_receipts.receipt_category_id enthalten account_id.
 */

#[AllowDynamicProperties]
class Mdl_Account_Chart extends Response_Model
{
    public const TYPE_INCOME  = 1;
    public const TYPE_EXPENSE = 2;

    public $table = 'ip_account_chart';

    public $primary_key = 'ip_account_chart.account_id';

    /** @var array|null Cache fuer alle Konten (eine Abfrage je Request) */
    private static $all = null;

    public function default_select()
    {
        $this->db->select('SQL_CALC_FOUND_ROWS *', false);
    }

    public function default_order_by()
    {
        $this->db->order_by('ip_account_chart.account_type');
        $this->db->order_by('ip_account_chart.account_group');
        $this->db->order_by('ip_account_chart.account_number');
        $this->db->order_by('ip_account_chart.account_name');
    }

    /**
     * @return array
     */
    public function validation_rules()
    {
        return [
            'account_type' => [
                'field' => 'account_type',
                'label' => trans('account_type'),
                'rules' => 'required|in_list[1,2]',
            ],
            'account_group' => [
                'field' => 'account_group',
                'label' => trans('account_group'),
                'rules' => 'trim|max_length[64]',
            ],
            'account_number' => [
                'field' => 'account_number',
                'label' => trans('account_number'),
                'rules' => 'trim|required|max_length[10]|regex_match[/^[0-9]+$/]',
            ],
            'account_name' => [
                'field' => 'account_name',
                'label' => trans('account_name'),
                'rules' => 'trim|required|max_length[255]',
            ],
        ];
    }

    /** Art des Kontos fuer Auswahllisten */
    public function account_types(): array
    {
        return [
            self::TYPE_INCOME  => trans('account_type_income'),
            self::TYPE_EXPENSE => trans('account_type_expense'),
        ];
    }

    /** "0420 – Büroeinrichtung" */
    public static function label($account): string
    {
        return $account->account_number . ' – ' . $account->account_name;
    }

    /** Alle Konten, sortiert wie default_order_by (gecacht). Leer, solange die Tabelle fehlt. */
    private function load_all(): array
    {
        if (self::$all === null) {
            self::$all = [];

            if ($this->db->table_exists($this->table)) {
                self::$all = $this->db
                    ->order_by('account_type')
                    ->order_by('account_group')
                    ->order_by('account_number')
                    ->order_by('account_name')
                    ->get($this->table)
                    ->result();
            }
        }

        return self::$all;
    }

    /**
     * Konten als Auswahlliste mit Gruppen: [Gruppe => [account_id => "Nr. – Name"]]
     *
     * @param int $type TYPE_INCOME oder TYPE_EXPENSE
     */
    public function get_grouped(int $type): array
    {
        $out = [];
        foreach ($this->load_all() as $acc) {
            if ((int) $acc->account_type === $type) {
                $group = $acc->account_group !== '' ? $acc->account_group : trans('account_group_none');
                $out[$group][(int) $acc->account_id] = self::label($acc);
            }
        }

        return $out;
    }

    /** account_id => "Nr. – Name" fuer die Anzeige (alle Konten, beide Arten) */
    public function get_labels(): array
    {
        $out = [];
        foreach ($this->load_all() as $acc) {
            $out[(int) $acc->account_id] = self::label($acc);
        }

        return $out;
    }

    /** true, wenn die Konto-ID existiert und die gewuenschte Art hat */
    public function is_valid_account($account_id, int $type): bool
    {
        foreach ($this->load_all() as $acc) {
            if ((int) $acc->account_id === (int) $account_id) {
                return (int) $acc->account_type === $type;
            }
        }

        return false;
    }

    /** vorhandene Gruppen (fuer Vorschlagsliste im Formular) */
    public function get_groups(): array
    {
        $groups = [];
        foreach ($this->load_all() as $acc) {
            if ($acc->account_group !== '') {
                $groups[$acc->account_group] = true;
            }
        }

        return array_keys($groups);
    }

    /**
     * USt-Saetze fuer Ausgaben/Belege: die in InvoicePlane angelegten Steuersaetze (ip_tax_rates, > 0 %).
     * ['19.00' => '19 %', ...]. Leer = Instanz arbeitet ohne Umsatzsteuer (Feld wird dann nicht angezeigt).
     */
    public function vat_rate_options(): array
    {
        $out = [];
        if ($this->db->table_exists('ip_tax_rates')) {
            $rows = $this->db->select('tax_rate_percent')->where('tax_rate_percent >', 0)
                ->group_by('tax_rate_percent')->order_by('tax_rate_percent')->get('ip_tax_rates')->result();
            foreach ($rows as $r) {
                $out[number_format((float) $r->tax_rate_percent, 2, '.', '')] = rtrim(rtrim(number_format((float) $r->tax_rate_percent, 2, ',', ''), '0'), ',') . ' %';
            }
        }

        return $out;
    }

    /** gueltigen USt-Satz als Text ("19.00") liefern, sonst "0.00" */
    public function normalize_vat_rate($posted): string
    {
        $key = is_numeric($posted) ? number_format((float) $posted, 2, '.', '') : '';

        return array_key_exists($key, $this->vat_rate_options()) ? $key : '0.00';
    }

    /** Anzahl Verwendungen (Ausgaben, Einnahme-Belege, Produktfamilien), die das Konto nutzen */
    public function usage_count($account_id): int
    {
        $count = 0;
        if ($this->db->table_exists('ip_expenses')) {
            $count += $this->db->where('expense_category_id', (int) $account_id)->count_all_results('ip_expenses');
        }
        if ($this->db->table_exists('ip_receipts')) {
            $count += $this->db->where('receipt_category_id', (int) $account_id)->count_all_results('ip_receipts');
        }

        // Produktfamilien mit diesem Einnahmekonto
        if ($this->db->table_exists('ip_families') && $this->db->field_exists('family_account_id', 'ip_families')) {
            $count += $this->db->where('family_account_id', (int) $account_id)->count_all_results('ip_families');
        }

        return $count;
    }
}
