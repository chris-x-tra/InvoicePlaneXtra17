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

    /** Anzahl Buchungen (Ausgaben + Einnahme-Belege), die das Konto verwenden */
    public function usage_count($account_id): int
    {
        $count = 0;
        if ($this->db->table_exists('ip_expenses')) {
            $count += $this->db->where('expense_category_id', (int) $account_id)->count_all_results('ip_expenses');
        }
        if ($this->db->table_exists('ip_receipts')) {
            $count += $this->db->where('receipt_category_id', (int) $account_id)->count_all_results('ip_receipts');
        }

        return $count;
    }
}
