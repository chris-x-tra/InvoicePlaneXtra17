<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * InvoicePlane Timesheets Module
 *
 * @author              Chrissie Brown
 * @copyright   Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license             https://invoiceplane.com/license.txt
 * @link                https://invoiceplane.com
 */

/**
 * Class Mdl_Timesheets
 *
 * Tabelle ip_timesheets (Stand nach migration_2026-11.sql):
 *
 *  id, timesheet_userid, timesheet_day, timesheet_month, timesheet_year,
 *  timesheet_start TIME, timesheet_end TIME,
 *  timesheet_minutes  <- GENERATED (STORED): Dauer in Minuten, Ende < Beginn = ueber Mitternacht
 *  timesheet_clientid, timesheet_remark, timesheet_km, timesheet_type,
 *  timesheet_uuid (UNIQUE), timesheet_timestamp,
 *  timesheet_sync_status, timesheet_change_status ('created','updated','deleted','synced')
 *
 * Die Dauer wird NUR von der Datenbank berechnet (timesheet_minutes). Alle
 * Summen im Modul laufen ueber SUM(timesheet_minutes) - dadurch gibt es genau
 * eine Rechenregel, egal ob Uebersicht, Ansicht, Druck oder API.
 */
class Mdl_Timesheets extends Response_Model
{
    public $table = 'ip_timesheets';
    public $primary_key = 'ip_timesheets.id';

    /** nicht geloeschte Zeilen (NULL-sicher) */
    const NOT_DELETED = "(timesheet_change_status IS NULL OR timesheet_change_status <> 'deleted')";

    /* ------------------------------------------------------------------
     * Zentrale Typenliste (Formular, Uebersicht, SQL, Validierung)
     *  group      'work' = Arbeitsstunden, 'non_work' = andere Stunden
     *  needs_time Beginn/Ende muessen unterschiedlich sein
     * ------------------------------------------------------------------ */
    public static function worktypes()
    {
        return [
            'A'  => ['label' => 'Arbeitstag',         'group' => 'work',     'needs_time' => true],
            'B'  => ['label' => 'Bürotag',            'group' => 'work',     'needs_time' => true],
            'D'  => ['label' => 'Kundenfahrt',        'group' => 'work',     'needs_time' => true],
            'F'  => ['label' => 'Feiertag',           'group' => 'non_work', 'needs_time' => true],
            'K'  => ['label' => 'Krank',              'group' => 'non_work', 'needs_time' => true],
            'S'  => ['label' => 'Überstunden',        'group' => 'non_work', 'needs_time' => true],
            'U'  => ['label' => 'Urlaub',             'group' => 'non_work', 'needs_time' => false],
            'UU' => ['label' => 'Unbezahlter Urlaub', 'group' => 'non_work', 'needs_time' => false],
        ];
    }

    public static function work_type_codes()
    {
        $r = [];
        foreach (self::worktypes() as $code => $t) {
            if ($t['group'] === 'work') $r[] = $code;
        }
        return $r;
    }

    /* ------------------------------------------------------------------
     * Reine Hilfsfunktionen (ohne DB, gut testbar)
     * ------------------------------------------------------------------ */

    /** '8:5' ungueltig, '08:05' / '08:05:00' ok, '' => '00:00', ungueltig => null */
    public static function normalize_time($s)
    {
        $s = trim((string)$s);
        if ($s === '') return '00:00';
        if (!preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $s, $m)) return null;
        if ((int)$m[1] > 23 || (int)$m[2] > 59) return null;
        return sprintf('%02d:%02d', $m[1], $m[2]);
    }

    /** ganze Kilometer; '12,5' => 13; '' => 0; negativ/Unsinn => null */
    public static function normalize_km($v)
    {
        $v = str_replace(',', '.', trim((string)$v));
        if ($v === '') return 0;
        if (!is_numeric($v) || $v < 0 || $v > 99999) return null;
        return (int)round($v);
    }

    public static function valid_uuid($s)
    {
        return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string)$s);
    }

    public static function new_uuid()
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        $h = bin2hex($b);
        return substr($h, 0, 8) . '-' . substr($h, 8, 4) . '-' . substr($h, 12, 4) . '-' .
               substr($h, 16, 4) . '-' . substr($h, 20, 12);
    }

    /** Minuten => 'HH:MM' (pad) bzw. 'H:MM' */
    public static function fmt_hm($minutes, $pad = true)
    {
        $minutes = max(0, (int)$minutes);
        return sprintf($pad ? '%02d:%02d' : '%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * Eine Eingabezeile pruefen und normalisieren.
     *
     * @param array $in            Rohdaten (x_day, x_uuid, x_type, x_from, x_to, x_customer_id, x_km, x_remark)
     * @param int   $days_in_month Tage des Zielmonats
     * @return array ['empty'=>bool, 'valid'=>bool, 'reason'=>string, 'data'=>array]
     */
    public static function validate_item($in, $days_in_month)
    {
        $types  = self::worktypes();
        $type   = trim((string)($in['x_type'] ?? ''));
        $from   = self::normalize_time($in['x_from'] ?? '');
        $to     = self::normalize_time($in['x_to'] ?? '');
        $km     = self::normalize_km($in['x_km'] ?? 0);
        $client = (int)($in['x_customer_id'] ?? 0);
        $remark = trim((string)($in['x_remark'] ?? ''));
        $remark = function_exists('mb_substr') ? mb_substr($remark, 0, 255) : substr($remark, 0, 255);
        // Text im Kundenfeld (ohne gewaehlte ID) - darf nicht still verschwinden
        $ctext  = trim((string)($in['x_customer_text'] ?? ''));
        $day    = (int)($in['x_day'] ?? 0);
        $uuid   = trim((string)($in['x_uuid'] ?? ''));

        $data = [
            'uuid' => $uuid, 'day' => $day, 'type' => $type, 'from' => $from, 'to' => $to,
            'client' => max(0, $client), 'km' => $km, 'remark' => $remark,
        ];
        $res = function ($valid, $reason, $empty = false) use ($data) {
            return ['empty' => $empty, 'valid' => $valid, 'reason' => $reason, 'data' => $data];
        };

        // komplett leer? (Typ, Zeiten, Kunde, Bemerkung, km)
        $started = ($type !== '') || ($client > 0) || ($remark !== '') || ($ctext !== '')
                || ($from !== '00:00') || ($to !== '00:00') || ($km !== 0);
        if (!$started) return $res(true, 'empty', true);

        if (!self::valid_uuid($uuid))                      return $res(false, 'Ungültige Zeilen-ID');
        if ($day < 1 || $day > (int)$days_in_month)        return $res(false, 'Ungültiger Tag');
        if ($type === '')                                  return $res(false, 'Typ fehlt');
        if (!isset($types[$type]))                         return $res(false, 'Ungültiger Typ');
        if ($from === null || $to === null)                return $res(false, 'Ungültige Uhrzeit');
        if ($km === null)                                  return $res(false, 'Ungültige Kilometerangabe');
        if ($types[$type]['needs_time'] && $from === $to)  return $res(false, 'Beginn und Ende dürfen nicht gleich sein');
        if ($client <= 0 && $ctext !== '') {
            $short = function_exists('mb_substr') ? mb_substr($ctext, 0, 40) : substr($ctext, 0, 40);
            return $res(false, 'Kunde „' . $short . '“ wurde nicht aus der Liste gewählt');
        }
        if ($client <= 0 && $remark === '')                return $res(false, 'Kunde oder Bemerkung fehlt');

        return $res(true, 'ok');
    }

    /* ------------------------------------------------------------------
     * Feiertage (nur zur Anzeige im Formular, es werden keine Eintraege erzeugt)
     * ------------------------------------------------------------------ */

    /** Regionale Zusatz-Feiertage 'MM-TT' => Name, z.B. ['10-31' => 'Reformationstag'] */
    public static $extra_holidays = [];

    /** Ostersonntag (Gregorianisch) als 'Y-m-d' */
    public static function easter_date_str($year)
    {
        $y = (int)$year;
        $a = $y % 19;
        $b = intdiv($y, 100);
        $c = $y % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $n = $h + $l - 7 * $m + 114;
        return sprintf('%04d-%02d-%02d', $y, intdiv($n, 31), ($n % 31) + 1);
    }

    /** Bundesweite gesetzliche Feiertage: 'YYYY-MM-DD' => Name (plus self::$extra_holidays) */
    public static function holidays($year)
    {
        $y = (int)$year;
        $easter = new DateTime(self::easter_date_str($y));
        $off = function ($days) use ($easter) {
            $d = clone $easter;
            $d->modify(($days >= 0 ? '+' : '') . $days . ' day');
            return $d->format('Y-m-d');
        };
        $h = [
            "$y-01-01" => 'Neujahr',
            $off(-2)   => 'Karfreitag',
            $off(1)    => 'Ostermontag',
            "$y-05-01" => 'Tag der Arbeit',
            $off(39)   => 'Christi Himmelfahrt',
            $off(50)   => 'Pfingstmontag',
            "$y-10-03" => 'Tag der Deutschen Einheit',
            "$y-12-25" => '1. Weihnachtstag',
            "$y-12-26" => '2. Weihnachtstag',
        ];
        foreach (self::$extra_holidays as $md => $name) {
            $h["$y-$md"] = $name;
        }
        return $h;
    }

    /* ------------------------------------------------------------------
     * Lesen
     * ------------------------------------------------------------------ */

    /** gemeinsamer WHERE-Teil; $userid 0 = alle Benutzer */
    private function scope($userid, $year, $month, $with_deleted = false)
    {
        $this->db->from('ip_timesheets');
        if ($userid != 0) $this->db->where('timesheet_userid', (int)$userid);
        $this->db->where('timesheet_year', (int)$year);
        $this->db->where('timesheet_month', (int)$month);
        if (!$with_deleted) $this->db->where(self::NOT_DELETED, null, false);
    }

    private function group_case_sql()
    {
        $codes = array_map([$this->db, 'escape'], self::work_type_codes());
        return "CASE WHEN timesheet_type IN (" . implode(',', $codes) . ") THEN 'work' ELSE 'non_work' END";
    }

    public function find_by_uuid($uuid)
    {
        return $this->db->get_where('ip_timesheets', ['timesheet_uuid' => $uuid])->row();
    }

    function get_timesheet_month($userid, $year, $month)
    {
        $this->scope($userid, $year, $month);
        $this->db->order_by('timesheet_day', 'ASC')->order_by('timesheet_start', 'ASC')->order_by('id', 'ASC');
        return $this->db->get()->result();
    }

    function get_timesheet_month_with_deleted($userid, $year, $month)
    {
        $this->scope($userid, $year, $month, true);
        $this->db->order_by('timesheet_day', 'ASC')->order_by('timesheet_start', 'ASC')->order_by('id', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Eintraege eines Monats + Summen (nach Typ, nach Tag, gesamt).
     * Struktur wird von view.php / print.php / index.php benutzt.
     */
    function get_timesheet_hours_month($userid, $year, $month)
    {
        $t = $this->get_timesheet_month($userid, $year, $month);

        $totalMinutes = 0;
        $typeSummary  = [];
        $dailySummary = [];

        foreach ($t as $entry) {
            $minutes = (int)$entry->timesheet_minutes;

            $entry->timesheet_duration_minutes = $minutes;
            $entry->timesheet_duration_hm      = self::fmt_hm($minutes);
            $entry->timesheet_duration_hours   = round($minutes / 60, 2);

            $totalMinutes += $minutes;

            $type = ($entry->timesheet_type !== null && $entry->timesheet_type !== '') ? $entry->timesheet_type : 'unknown';
            $typeSummary[$type] = ($typeSummary[$type] ?? 0) + $minutes;

            $dayKey = sprintf('%04d-%02d-%02d', $entry->timesheet_year, $entry->timesheet_month, $entry->timesheet_day);
            $dailySummary[$dayKey] = ($dailySummary[$dayKey] ?? 0) + $minutes;
        }

        $formattedTypeSummary = [];
        foreach ($typeSummary as $type => $minutes) {
            $formattedTypeSummary[$type] = [
                'minutes' => $minutes,
                'hours'   => round($minutes / 60, 2),
                'hm'      => self::fmt_hm($minutes),
            ];
        }

        $formattedDailySummary = [];
        foreach ($dailySummary as $date => $minutes) {
            $formattedDailySummary[$date] = [
                'minutes' => $minutes,
                'hours'   => round($minutes / 60, 2),
                'hm'      => self::fmt_hm($minutes, false),
            ];
        }

        $result = new stdClass();
        $result->entries         = $t;
        $result->total_minutes   = $totalMinutes;
        $result->total_hours     = round($totalMinutes / 60, 2);
        $result->total_hm        = self::fmt_hm($totalMinutes);
        $result->summary_by_type = $formattedTypeSummary;
        $result->summary_by_day  = $formattedDailySummary;
        return $result;
    }

    public function get_monthly_km($userid, $year, $month)
    {
        $this->db->select('SUM(timesheet_km) AS total_km', false);
        $this->scope($userid, $year, $month);
        $row = $this->db->get()->row();
        return ($row && $row->total_km !== null) ? (int)$row->total_km : 0;
    }

    public function get_monthly_work_summary($userid, $year, $month)
    {
        $this->db->select('timesheet_type, SUM(timesheet_minutes) AS total_minutes, ROUND(SUM(timesheet_minutes) / 60, 2) AS total_hours', false);
        $this->scope($userid, $year, $month);
        $this->db->group_by('timesheet_type');
        return $this->db->get()->result();
    }

    /** Arbeit (A,B,D) / andere Stunden fuer EINEN Benutzer (0 = alle) */
    public function get_monthly_work_nonwork_summary($userid, $year, $month)
    {
        $this->db->select($this->group_case_sql() . ' AS type_group, SUM(timesheet_minutes) AS total_minutes, ROUND(SUM(timesheet_minutes) / 60, 2) AS total_hours', false);
        $this->scope($userid, $year, $month);
        $this->db->group_by('type_group');
        return $this->db->get()->result();
    }

    /** Eine Query fuer alle Benutzer: [userid => ['work'=>min, 'non_work'=>min]] */
    public function get_month_summary_by_user($year, $month)
    {
        $this->db->select('timesheet_userid, ' . $this->group_case_sql() . ' AS type_group, SUM(timesheet_minutes) AS total_minutes', false);
        $this->scope(0, $year, $month);
        $this->db->group_by('timesheet_userid')->group_by('type_group');
        $out = [];
        foreach ($this->db->get()->result() as $r) {
            $out[(int)$r->timesheet_userid][$r->type_group] = (int)$r->total_minutes;
        }
        return $out;
    }

    /** Eine Query statt 72: [jahr][monat] => Minuten (alle Typen) */
    public function get_year_minutes($userid, $year_from, $year_to)
    {
        $this->db->select('timesheet_year, timesheet_month, SUM(timesheet_minutes) AS total_minutes', false);
        $this->db->from('ip_timesheets');
        if ($userid != 0) $this->db->where('timesheet_userid', (int)$userid);
        $this->db->where('timesheet_year >=', (int)$year_from);
        $this->db->where('timesheet_year <=', (int)$year_to);
        $this->db->where(self::NOT_DELETED, null, false);
        $this->db->group_by('timesheet_year')->group_by('timesheet_month');

        $out = [];
        for ($y = (int)$year_to; $y >= (int)$year_from; $y--) {
            for ($m = 1; $m <= 12; $m++) $out[$y][$m] = 0;
        }
        foreach ($this->db->get()->result() as $r) {
            $out[(int)$r->timesheet_year][(int)$r->timesheet_month] = (int)$r->total_minutes;
        }
        return $out;
    }

    /* ------------------------------------------------------------------
     * Schreiben
     * ------------------------------------------------------------------ */

    /** Zeilen ohne UUID (Altbestand) bekommen eine - sonst entstehen beim Speichern Dubletten. */
    public function ensure_uuids($userid, $year, $month)
    {
        $this->db->select('id')->from('ip_timesheets');
        $this->db->where('timesheet_userid', (int)$userid)
                 ->where('timesheet_year', (int)$year)
                 ->where('timesheet_month', (int)$month);
        $this->db->where("(timesheet_uuid IS NULL OR timesheet_uuid = '')", null, false);
        $rows = $this->db->get()->result();
        foreach ($rows as $r) {
            $this->db->where('id', $r->id)->update('ip_timesheets', ['timesheet_uuid' => self::new_uuid()]);
        }
        return count($rows);
    }

    /**
     * Upsert ueber die UUID (UNIQUE-Index): kein Select-dann-Insert mehr,
     * damit gibt es auch bei Doppelklick/Parallel-Requests keine Dubletten.
     * Die Berechtigung (Zeile gehoert dem Benutzer) prueft der Aufrufer.
     */
    public function save_line($userid, $year, $month, $day, $start, $end, $clientid, $remark, $km, $type, $uuid, $timestamp = '')
    {
        if ($timestamp === '' || $timestamp === null) $timestamp = date('Y-m-d H:i:s');

        $t = $this->db->dbprefix('timesheets');
        $sql = "INSERT INTO {$t}
                  (timesheet_uuid, timesheet_userid, timesheet_day, timesheet_month, timesheet_year,
                   timesheet_start, timesheet_end, timesheet_clientid, timesheet_remark, timesheet_km,
                   timesheet_type, timesheet_timestamp, timesheet_change_status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?, 'created')
                ON DUPLICATE KEY UPDATE
                  timesheet_day = VALUES(timesheet_day),
                  timesheet_month = VALUES(timesheet_month),
                  timesheet_year = VALUES(timesheet_year),
                  timesheet_start = VALUES(timesheet_start),
                  timesheet_end = VALUES(timesheet_end),
                  timesheet_clientid = VALUES(timesheet_clientid),
                  timesheet_remark = VALUES(timesheet_remark),
                  timesheet_km = VALUES(timesheet_km),
                  timesheet_type = VALUES(timesheet_type),
                  timesheet_timestamp = VALUES(timesheet_timestamp),
                  timesheet_change_status = 'updated'";
        return (bool)$this->db->query($sql, [
            $uuid, (int)$userid, (int)$day, (int)$month, (int)$year,
            $start, $end, (int)$clientid, (string)$remark, (int)$km,
            $type, $timestamp,
        ]);
    }

    /** Kompatibilitaet: alter Name/alte Signatur */
    public function update_timesheetline_by_uuid($userid, $year, $month, $day, $start, $end, $clientid, $remark, $km, $type, $uuid = "", $timestamp = "", $change_status = "")
    {
        return $this->save_line($userid, $year, $month, $day, $start, $end, $clientid, $remark, $km, $type, $uuid, $timestamp);
    }

    /** API-Insert (Sync-Client) - unveraenderte Semantik, km wird normalisiert */
    public function insert_timesheet_line($userid, $year, $month, $day, $start, $end, $clientid, $remark, $km, $type,
                                          $uuid = "", $timestamp = "", $change_status = "")
    {
        if (empty($timestamp)) $timestamp = date('Y-m-d H:i:s');
        if (empty($change_status)) $change_status = 'created';
        $km = self::normalize_km($km);

        $this->db->insert('ip_timesheets', [
            'timesheet_userid'        => $userid,
            'timesheet_year'          => $year,
            'timesheet_month'         => $month,
            'timesheet_day'           => $day,
            'timesheet_start'         => $start,
            'timesheet_end'           => $end,
            // Spalten sind NOT NULL (Migration): null aus dem Sync-Client abfangen
            'timesheet_clientid'      => (int)$clientid,
            'timesheet_remark'        => (string)$remark,
            'timesheet_km'            => $km === null ? 0 : $km,
            'timesheet_type'          => (string)$type,
            'timesheet_uuid'          => $uuid,
            'timesheet_timestamp'     => $timestamp,
            'timesheet_change_status' => $change_status,
        ]);
    }

    /** Soft-Delete; liefert Anzahl geaenderter Zeilen. Zeitstempel wird gesetzt, damit Sync-Clients die Loeschung sehen. */
    public function set_delete($uuid, $userid = null)
    {
        $this->db->where('timesheet_uuid', $uuid);
        if ($userid !== null) $this->db->where('timesheet_userid', (int)$userid);
        $this->db->update('ip_timesheets', [
            'timesheet_change_status' => 'deleted',
            'timesheet_timestamp'     => date('Y-m-d H:i:s'),
        ]);
        return $this->db->affected_rows();
    }

    /* ganzen Monat loeschen */
    public function delete_timesheet_month($userid, $year, $month)
    {
        $this->db->where('timesheet_userid', $userid);
        $this->db->where('timesheet_year', $year);
        $this->db->where('timesheet_month', $month);
        $this->db->delete('ip_timesheets');
    }
}
