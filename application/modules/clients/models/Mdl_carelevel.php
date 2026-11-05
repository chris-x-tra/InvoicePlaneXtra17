<?php

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Pflegegrad-Verlauf (ip_carelevel): ein Eintrag je Aenderung des Pflegegrads eines Kunden.
 *
 * ip_client_extended.carelevel / carelevel_since bleiben als "aktueller Stand" erhalten
 * (Kundenliste, Filter, Rechnungen nutzen sie) und werden aus dem juengsten Eintrag befuellt.
 */
#[AllowDynamicProperties]
class Mdl_carelevel extends CI_Model
{
    public $table = 'ip_carelevel';

    private ?bool $available = null;

    /** false, solange die Migration (_install/carelevel.sql) nicht gelaufen ist */
    public function is_available(): bool
    {
        if ($this->available === null) {
            $this->available = $this->db->table_exists($this->table);
        }

        return $this->available;
    }

    /** Alle Eintraege eines Kunden, neuester Beginn zuerst */
    public function get_by_client($client_id): array
    {
        if ( ! $this->is_available() || ! $client_id) {
            return [];
        }

        return $this->db
            ->where('client_id', (int) $client_id)
            ->order_by('carelevel_since IS NULL', 'ASC', false)   // unbekanntes Datum ans Ende
            ->order_by('carelevel_since', 'DESC')
            ->order_by('carelevel_id', 'DESC')
            ->get($this->table)
            ->result();
    }

    /** Zuletzt erfasster Eintrag (nach carelevel_id, nicht nach Datum - auch rueckdatierte Aenderungen zaehlen) */
    private function get_last($client_id)
    {
        return $this->db
            ->where('client_id', (int) $client_id)
            ->order_by('carelevel_id', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row();
    }

    /**
     * Wertet den im Formular eingegebenen Pflegegrad aus.
     *
     * - gleicher Pflegegrad wie zuletzt erfasst: nichts wird veraendert
     * - anderer Pflegegrad: neuer Eintrag; fehlt das "seit"-Datum oder wurde es nicht
     *   gegenueber dem letzten Eintrag geaendert, gilt heute (defaulted = true)
     *
     * @param int         $client_id
     * @param int         $level      neuer Pflegegrad (0 = keiner)
     * @param string|null $since_mysql "seit"-Datum als Y-m-d oder leer
     *
     * @return array|null ['level' => int, 'since' => ?string, 'defaulted' => bool] = Stand, der nach
     *                    ip_client_extended gehoert; null, wenn die Tabelle noch fehlt
     */
    public function apply($client_id, int $level, ?string $since_mysql): ?array
    {
        if ( ! $this->is_available()) {
            return null;
        }

        $client_id  = (int) $client_id;
        $since      = ($since_mysql === null || $since_mysql === '') ? null : $since_mysql;
        $last       = $this->get_last($client_id);
        $last_level = $last ? (int) $last->carelevel : 0;

        if ($level === $last_level) {
            // keine Aenderung -> Verlauf bleibt unberuehrt; Stand = letzter Eintrag
            return [
                'level'     => $level,
                'since'     => $last ? $last->carelevel_since : $since,
                'defaulted' => false,
            ];
        }

        $defaulted = false;
        if ($since === null || ($last && $last->carelevel_since === $since)) {
            $since     = date('Y-m-d');
            $defaulted = true;
        }

        $this->db->insert($this->table, [
            'client_id'       => $client_id,
            'carelevel'       => $level,
            'carelevel_since' => $since,
        ]);

        return ['level' => $level, 'since' => $since, 'defaulted' => $defaulted];
    }

    public function delete_by_client($client_id): void
    {
        if ($this->is_available()) {
            $this->db->delete($this->table, ['client_id' => (int) $client_id]);
        }
    }
}
