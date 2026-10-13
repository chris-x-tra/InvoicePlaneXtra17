<?php

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

#[AllowDynamicProperties]
class Mdl_Client_Addresses extends CI_Model
{
    public const TYPE_CLIENT  = 1;      // not used at the moment
    public const TYPE_INVOICE  = 2;
    public const TYPE_DELIVERY = 3;

    public $table = 'ip_client_addresses';

    /** Nutzfelder (ohne address_id / client_id / address_type) */
    public $fields = [
        'salutation', 'contact_person', 'name', 'name2',
        'address_1', 'address_2', 'city', 'zip',
        'state', 'country', 'phone', 'email',
    ];

    /** Alle Adressen eines Kunden, indiziert nach address_type */
    public function get_by_client(int $client_id): array
    {
        $rows = $this->db
            ->where('client_id', $client_id)
            ->order_by('address_id')
            ->get($this->table)
            ->result_array();

        $by_type = [];
        foreach ($rows as $row) {
            $type = (int) $row['address_type'];
            if ( ! isset($by_type[$type])) {   // pro Typ za"hlt erstmal die erste
                $by_type[$type] = $row;
            }
        }

        return $by_type;
    }

    /**
     * Speichert die gepostete Struktur addresses[<type>][<feld>].
     * Typen, die nicht gepostet wurden, bleiben unberuhrt.
     * Komplett leere Adresse = Datensatz wird geloscht.
     */
    public function save_for_client(int $client_id, array $posted): void
    {
        foreach ([self::TYPE_INVOICE, self::TYPE_DELIVERY] as $type) {
            if ( ! isset($posted[$type]) || ! is_array($posted[$type])) {
                continue;
            }

            $data  = $this->sanitize($posted[$type]);
            $where = ['client_id' => $client_id, 'address_type' => $type];

            $existing = $this->db->select('address_id')->where($where)
                ->order_by('address_id')->get($this->table)->row();

            if (array_filter($data, static fn ($v) => $v !== null) === []) {
                if ($existing) {
                    $this->db->delete($this->table, $where);
                }
                continue;
            }

            if ($existing) {
                $this->db->update($this->table, $data, ['address_id' => $existing->address_id]);
            } else {
                $this->db->insert($this->table, $data + $where);
            }
        }
    }

    /** Alle Adressen eines Kunden: [address_type => [Zeilen, nach address_id]] */
    public function get_all_by_client(int $client_id): array
    {
        $rows = $this->db->where('client_id', $client_id)->order_by('address_id')
            ->get($this->table)->result_array();

        $by_type = [];
        foreach ($rows as $row) {
            $by_type[(int) $row['address_type']][] = $row;
        }

        return $by_type;
    }

    /** Legt eine Adresse an oder a"ndert sie. Ru"ckgabe: null = ok, sonst Fehlertext */
    public function save_one(int $client_id, int $type, array $posted, ?int $address_id = null): ?string
    {
        if ( ! in_array($type, [self::TYPE_INVOICE, self::TYPE_DELIVERY], true)) {
            return 'Ungültiger Adresstyp.';
        }
        if ($this->db->where('client_id', $client_id)->count_all_results('ip_clients') === 0) {
            return 'Kunde nicht gefunden.';
        }

        $data = $this->sanitize($posted);
        foreach (['name', 'zip', 'city'] as $required) {
            if ($data[$required] === null) {
                return 'Bitte Name, Adresse, PLZ und Ort ausfüllen.';
            }
        }

        if ($address_id) {
            $own = $this->db->where(['address_id' => $address_id, 'client_id' => $client_id, 'address_type' => $type])
                ->count_all_results($this->table) > 0;
            if ( ! $own) {
                return 'Adresse nicht gefunden.';
            }
            $this->db->update($this->table, $data, ['address_id' => $address_id]);
        } else {
            $this->db->insert($this->table, $data + ['client_id' => $client_id, 'address_type' => $type]);
        }

        return null;
    }

    public function delete_one(int $address_id, int $client_id): bool
    {
        $this->db->delete($this->table, ['address_id' => $address_id, 'client_id' => $client_id]);

        return $this->db->affected_rows() > 0;
    }

    /** Suche fur das Adress-Modal (gruppiert, damit Duplikate nicht mehrfach erscheinen) */
    public function search(string $q, int $type = self::TYPE_INVOICE): array
    {
        $cols = ['salutation', 'contact_person', 'name', 'name2',
            'address_1', 'address_2', 'zip', 'city'];

    return $this->db
        ->select(implode(', ', $cols) . ', COUNT(*) AS used', false)
        ->from($this->table)
        ->where('address_type', $type)
        ->group_start()
            ->like('name', $q)
            ->or_like('name2', $q)
        ->group_end()
        ->group_by($cols)
        ->order_by('name ASC, used DESC', '', false)
        ->limit(50)
        ->get()
        ->result();

    }

    /** Nur bekannte Felder, getrimmt, '' => NULL */
    private function sanitize(array $row): array
    {
        $clean = [];
        foreach ($this->fields as $f) {
            $v = trim((string) ($row[$f] ?? ''));
            $clean[$f] = $v === '' ? null : $v;
        }

        return $clean;
    }


    /** Kundenadresse aus ip_clients im Format der Adresstabelle */
    private function client_as_address(int $client_id): array
    {
        $c = $this->db->get_where('ip_clients', ['client_id' => $client_id])->row_array() ?: [];

        return [
            'address_id'     => null,
            'salutation'     => $c['client_salutation'] ?? null,
            'contact_person' => $c['client_contact_person'] ?? null,
            'name'           => trim(($c['client_name'] ?? '') . ' ' . ($c['client_surname'] ?? '')),
            'name2'          => null,
            'address_1'      => $c['client_address_1'] ?? null,
            'address_2'      => $c['client_address_2'] ?? null,
            'city'           => $c['client_city'] ?? null,
            'zip'            => $c['client_zip'] ?? null,
            'state'          => $c['client_state'] ?? null,
            'country'        => $c['client_country'] ?? null,
            'phone'          => $c['client_phone'] ?? null,
            'email'          => $c['client_email'] ?? null,
        ];
    }

    /**
     * billing_*-Felder (inkl. billing_address_id) fur ip_invoices.
     *
     * @param int|string $source 'auto' = erste Rechnungsadresse, sonst Kundenadresse
     *                           'client' = immer Kundenadresse
     *                           int = konkrete address_id dieses Kunden
     */
    public function billing_snapshot(int $client_id, $source = 'auto'): array
    {
        $addr = null;

        if (is_int($source)) {
            $addr = $this->db->where(['address_id' => $source, 'client_id' => $client_id])
                ->get($this->table)->row_array();
        } elseif ($source !== 'client') {
            $addr = $this->get_by_client($client_id)[self::TYPE_INVOICE] ?? null;
            if ($addr && trim(($addr['name'] ?? '') . ($addr['address_1'] ?? '') . ($addr['city'] ?? '')) === '') {
                $addr = null;   // leere Rechnungsadresse zahhlt nicht
            }
        }

        if ( ! $addr) {
            $addr = $this->client_as_address($client_id);
        }

        $out = ['billing_address_id' => $addr['address_id'] ?? null];
        foreach ($this->fields as $f) {
            $v = $addr[$f] ?? null;
            $out['billing_' . $f] = ($v === null || $v === '') ? null : $v;
        }

        return $out;
    }

    /** Eintrage furs Dropdown: value => Label (Rechnungsadressen, zuletzt die Kundenadresse) */
    public function billing_options(int $client_id): array
    {
        $rows = $this->db->where(['client_id' => $client_id, 'address_type' => self::TYPE_INVOICE])
            ->order_by('address_id')->get($this->table)->result_array();

        $options = [];
        foreach ($rows as $i => $r) {
            $title = trans('invoice_address') . (count($rows) > 1 ? ' ' . ($i + 1) : '');
            $options[(string) $r['address_id']] = $title . ': ' . $this->short_label($r);
        }
        $options['client'] = trans('client_address') . ': ' . $this->short_label($this->client_as_address($client_id));

        return $options;
    }

    /** Der Snapshot einer Rechnung im Format der Adresstabelle (fur das Adress-Partial) */
    public function invoice_billing(object $invoice): array
    {
        $out = [];
        foreach ($this->fields as $f) {
            $out[$f] = $invoice->{'billing_' . $f} ?? null;
        }

        return $out;
    }


    /** Belegarten mit Anschrift: [Tabelle, Primärschlüssel] */
    private function doc(string $type): array
    {
        $docs = [
            'invoice' => ['ip_invoices', 'invoice_id'],
            'quote'   => ['ip_quotes', 'quote_id'],
        ];

            return $docs[$type] ?? throw new InvalidArgumentException('Unknown document type: ' . $type);
    }

    /**
     * Wechselt die Anschrift eines Belegs, aber nur wenn die Quelle wirklich geändert wurde.
     *
     * @param string $source 'client' | address_id | 'keep' (= nichts tun)
     */
    public function set_billing(string $type, int $doc_id, string $source): bool
    {
        if ($source === '' || $source === 'keep') {
            return false;
        }

        [$table, $pk] = $this->doc($type);

        $cols = 'client_id, billing_address_id' . ($type === 'invoice' ? ', is_read_only' : '');
        $doc  = $this->db->select($cols)->get_where($table, [$pk => $doc_id])->row();

        if ( ! $doc || ! empty($doc->is_read_only)) {
            return false;
        }

        $stored = $doc->billing_address_id ? (string) $doc->billing_address_id : 'client';
        if ($source === $stored) {
            return false;   // nichts gewechselt: Snapshot bleibt, wie er ist
        }

        $client_id = (int) $doc->client_id;

        if ($source === 'client') {
            $snapshot = $this->billing_snapshot($client_id, 'client');
        } elseif (ctype_digit($source)) {
            $exists = $this->db->where(['address_id' => (int) $source, 'client_id' => $client_id])
                ->count_all_results($this->table) > 0;
            if ( ! $exists) {
                return false;
            }
            $snapshot = $this->billing_snapshot($client_id, (int) $source);
        } else {
            return false;
        }

        $this->db->update($table, $snapshot, [$pk => $doc_id]);

        return true;
    }

    public function set_invoice_billing(int $invoice_id, string $source): bool
    {
        return $this->set_billing('invoice', $invoice_id, $source);
    }

    public function set_quote_billing(int $quote_id, string $source): bool
    {
        return $this->set_billing('quote', $quote_id, $source);
    }



    /* */
    private function short_label(array $a): string
    {
        $name  = trim(($a['name'] ?? '') . ' ' . ($a['name2'] ?? ''));
        $place = trim(($a['zip'] ?? '') . ' ' . ($a['city'] ?? ''));

        return implode(', ', array_filter([$name, $place]));
    }

    /**
     * Anschrift 1:1 von einem Beleg auf einen anderen u"bernehmen
     * (Gutschrift, Angebot -> Rechnung). Nur bei gleichem Kunden, sonst bleibt der Ziel-Beleg unvera"ndert.
     */
    public function copy_billing(int $from_id, int $to_id, string $from_type = 'invoice', string $to_type = 'invoice'): void
    {
        [$from_table, $from_pk] = $this->doc($from_type);
        [$to_table, $to_pk]     = $this->doc($to_type);

        $cols = array_merge(['client_id', 'billing_address_id'], array_map(static fn ($f) => 'billing_' . $f, $this->fields));

        $row = $this->db->select(implode(', ', $cols))->get_where($from_table, [$from_pk => $from_id])->row_array();
        $to  = $this->db->select('client_id')->get_where($to_table, [$to_pk => $to_id])->row();

        if ( ! $row || ! $to || (int) $row['client_id'] !== (int) $to->client_id) {
            return;
        }

        unset($row['client_id']);
        $this->db->update($to_table, $row, [$to_pk => $to_id]);
    }

    /** Kopie: gleiche Quelle wie der Quell-Beleg (bei gleichem Kunden), aber aktueller Adressstand */
    public function snapshot_for_copy(int $source_id, int $target_id, string $type = 'invoice'): void
    {
        [$table, $pk] = $this->doc($type);

        $target = $this->db->select('client_id')->get_where($table, [$pk => $target_id])->row();
        if ( ! $target) {
            return;
        }
        $client_id = (int) $target->client_id;

        $src    = $this->db->select('client_id, billing_address_id')->get_where($table, [$pk => $source_id])->row();
        $source = 'auto';

        if ($src && (int) $src->client_id === $client_id) {
            if ($src->billing_address_id) {
                $exists = $this->db->where(['address_id' => (int) $src->billing_address_id, 'client_id' => $client_id])
                    ->count_all_results($this->table) > 0;
                $source = $exists ? (int) $src->billing_address_id : 'auto';
            } else {
                $source = 'client';
            }
        }

        $this->db->update($table, $this->billing_snapshot($client_id, $source), [$pk => $target_id]);
    }


}
