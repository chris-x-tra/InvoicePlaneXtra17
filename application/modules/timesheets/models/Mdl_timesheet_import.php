<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Import von Arbeitszeiten aus Excel (.xls/.xlsx) ins Timesheets-Modul.
 *
 * Erwartetes Format (siehe Stundenzettel-Vorlage): ein oder mehrere Tabellenblaetter (z. B. je Monat),
 * in jedem Blatt eine Kopfzeile mit den Spalten  Datum | Klient | Beginn | Ende  (in den ersten 15 Zeilen,
 * Spalte A-D, optional E = Stunden). Blaetter ohne diese Kopfzeile (z. B. Wochenplan) werden uebersprungen.
 * Spalte E ("Stunden", Dezimalstunden) gilt nur fuer Urlaub/Krank/Feiertag/Ueberstunden/unbezahlten Urlaub ohne Beginn/Ende:
 * dann wird ab 08:00 gerechnet. Alle weiteren Spalten werden ignoriert: die Dauer berechnet die Datenbank.
 *
 * Aufbau:
 *   read_workbook()  - einziger Teil, der PhpSpreadsheet braucht (liefert reine Arrays)
 *   parse_sheets()   - Kopfzeile finden, Datum/Zeit/Typ pruefen   (ohne DB, ohne Bibliothek)
 *   match_client()   - Namen aus Excel den Kunden der Datenbank zuordnen (ohne DB)
 *   import_rows()    - in die Datenbank schreiben
 */
class Mdl_Timesheet_Import extends CI_Model
{
    /** Zeilen pro Blatt, die maximal gelesen werden (Vorlage hat Formeln weit nach unten) */
    const MAX_ROWS = 2000;

    /** Beginn, wenn bei Urlaub/Krank/Feiertag/Ueberstunden nur Stunden angegeben sind */
    const START = '08:00';

    /* ------------------------------------------------------------------
     * 1. Datei lesen (PhpSpreadsheet)
     * ------------------------------------------------------------------ */

    /**
     * @return array Liste von ['title'=>string, 'rows'=>[Zeilennr => [A,B,C,D,E]]]
     *               Rohwerte: Datum/Zeit als Excel-Zahl (float) oder Text.
     * @throws Exception wenn die Datei nicht lesbar ist
     */
    public static function read_workbook($path)
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);   // keine Formeln auswerten, keine Formate laden
        $book = $reader->load($path);

        $out = [];
        foreach ($book->getAllSheets() as $ws) {
            $rows = [];
            $last = min((int)$ws->getHighestDataRow(), self::MAX_ROWS);
            for ($r = 1; $r <= $last; $r++) {
                $line = [];
                foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
                    $cell = $ws->getCell($col . $r);
                    $v = $cell->getValue();
                    // Spalte E (Stunden) nur auswerten, wenn Beginn und Ende leer sind (spart Rechenzeit bei Vorlagen mit Formeln)
                    $need = ($col !== 'E') || (($line[2] ?? '') === null || trim((string)($line[2] ?? '')) === '')
                                          && (($line[3] ?? '') === null || trim((string)($line[3] ?? '')) === '');
                    if (is_string($v) && isset($v[0]) && $v[0] === '=') {   // Formel: berechneten Wert nehmen
                        if ($need) {
                            try { $v = $cell->getCalculatedValue(); } catch (\Throwable $e) { $v = null; }
                        } else {
                            $v = null;
                        }
                    }
                    $line[] = $v;
                }
                $rows[$r] = $line;
            }
            $out[] = ['title' => (string)$ws->getTitle(), 'rows' => $rows];
        }
        $book->disconnectWorksheets();
        return $out;
    }

    /* ------------------------------------------------------------------
     * 2. Parsen / Pruefen
     * ------------------------------------------------------------------ */

    /** Kleinschreibung, Umlaute aufgeloest, nur Buchstaben/Ziffern, einfache Leerzeichen */
    public static function norm($s)
    {
        $s = mb_strtolower(trim((string)$s), 'UTF-8');
        $s = strtr($s, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'é' => 'e', 'è' => 'e', 'á' => 'a', 'à' => 'a']);
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        return trim($s);
    }

    /** Excel-Seriennummer -> 'Y-m-d' (nur Datumsteil), sonst null */
    public static function parse_date($v)
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) {
            $n = (float)$v;
            if ($n < 20000 || $n > 80000) return null;      // ca. 1954 - 2118
            return gmdate('Y-m-d', (int)round(($n - 25569) * 86400));
        }
        $s = trim((string)$v);
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4}|\d{2})$/', $s, $m)) {
            $y = (int)$m[3];
            if ($y < 100) $y += 2000;
            return checkdate((int)$m[2], (int)$m[1], $y) ? sprintf('%04d-%02d-%02d', $y, $m[2], $m[1]) : null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
            return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]) : null;
        }
        return null;
    }

    /**
     * Uhrzeit -> 'HH:MM', '' wenn leer, false wenn nicht lesbar.
     * Excel-Zeit ist ein Tagesbruchteil (0.3333 = 08:00); auf Minuten gerundet.
     */
    public static function parse_time($v)
    {
        if ($v === null) return '';
        if (is_string($v) && trim($v) === '') return '';
        if (is_numeric($v)) {
            $n = (float)$v;
            if ($n < 0) return false;
            $min = (int)round(($n - floor($n)) * 1440);
            if ($min >= 1440) $min = 0;
            return sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
        }
        $s = trim((string)$v);
        if (preg_match('/^(\d{1,2})[:.](\d{2})(?::\d{2})?(?:\s*uhr)?$/i', $s, $m)) {
            if ((int)$m[1] > 23 || (int)$m[2] > 59) return false;
            return sprintf('%02d:%02d', $m[1], $m[2]);
        }
        return false;
    }

    /** Dezimalstunden (4, 7.7, "3,5") -> float, '' wenn leer, false wenn nicht lesbar */
    public static function parse_hours($v)
    {
        if ($v === null) return '';
        if (is_string($v)) {
            $v = trim(str_replace(',', '.', $v));
            if ($v === '') return '';
        }
        return is_numeric($v) ? (float)$v : false;
    }

    /** Ende = Beginn + Minuten (ueber Mitternacht erlaubt) */
    public static function add_minutes($from, $min)
    {
        $m = ((int)substr($from, 0, 2) * 60 + (int)substr($from, 3, 2) + $min) % 1440;
        return sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    }

    /**
     * Text der Spalte "Klient" -> Typ. Urlaub/Krank/Feiertag/Ueberstunden sind keine Kunden.
     * @return array [typ-Kuerzel, ist_kein_kunde]
     */
    public static function classify($text)
    {
        $n = self::norm($text);
        if ($n === '') return ['A', false];
        if (strpos($n, 'unbezahlt') !== false && strpos($n, 'urlaub') !== false) return ['UU', true];
        if (strpos($n, 'urlaub') !== false)       return ['U', true];
        if (strpos($n, 'krank') !== false)        return ['K', true];
        if (strpos($n, 'feiertag') !== false)     return ['F', true];
        if (strpos($n, 'ueberstunden') !== false || strpos($n, 'ueberstd') !== false) return ['S', true];
        return ['A', false];
    }

    /**
     * @param array $sheets Ergebnis von read_workbook()
     * @return array ['rows'=>[...], 'sheets'=>[...], 'owner'=>string]
     *   rows[]: n, sheet, line, date, text, type, special, from, to, minutes, status ('ok'|'skip'|'error'), reason, warn
     */
    public static function parse_sheets(array $sheets)
    {
        $types = Mdl_Timesheets::worktypes();
        $rows = [];
        $info = [];
        $owner = '';
        $n = 0;

        foreach ($sheets as $sh) {
            $title = $sh['title'];
            $head = null;
            $r = 0;
            foreach ($sh['rows'] as $r => $c) {
                if ($r > 15) break;
                if (self::norm($c[0]) === 'datum' && preg_match('/^(klient|kunde)/', self::norm($c[1]))
                    && in_array(self::norm($c[2]), ['beginn', 'von', 'start'], true)
                    && in_array(self::norm($c[3]), ['ende', 'bis'], true)) {
                    $head = $r;
                    break;
                }
            }
            if ($head === null) {
                $info[] = ['title' => $title, 'rows' => 0, 'note' => 'übersprungen: keine Kopfzeile "Datum | Klient | Beginn | Ende" gefunden'];
                continue;
            }
            // Name des Mitarbeiters steht ueblicherweise in A1 ("Angelova Daniela")
            $a1 = trim((string)($sh['rows'][1][0] ?? ''));
            if ($owner === '' && $a1 !== '' && $a1[0] !== '=' && !is_numeric($a1) && self::norm($a1) !== 'name') $owner = $a1;

            $cnt = 0;
            foreach ($sh['rows'] as $line => $c) {
                if ($line <= $head) continue;
                $text = trim(preg_replace('/\s+/u', ' ', (string)$c[1]));
                $date = self::parse_date($c[0]);
                $from = self::parse_time($c[2]);
                $to   = self::parse_time($c[3]);
                $hrs  = self::parse_hours($c[4] ?? null);
                $has_any = ($text !== '') || ($from !== '' && $from !== false) || ($to !== '' && $to !== false)
                        || ($hrs !== '' && $hrs !== false && $hrs != 0);

                if ($date === null) {
                    // Zeile ohne Datum: leere Formelzeilen ignorieren, Zeilen mit Inhalt melden
                    if (trim((string)$c[0]) !== '' || $has_any) {
                        $n++;
                        $rows[] = self::make_row($n, $title, $line, null, $text, 'A', false, '', '', 'error',
                                            'Datum fehlt oder ist nicht lesbar');
                        $cnt++;
                    }
                    continue;
                }
                if (!$has_any) continue;   // nur ein Datum, sonst nichts

                list($type, $special) = self::classify($text);
                $n++;
                $cnt++;
                $status = 'ok';
                $reason = '';
                $warn = '';
                $from_hours = 0;
                $year = (int)substr($date, 0, 4);

                if ($from === false || $to === false) {
                    $status = 'error'; $reason = 'Uhrzeit nicht lesbar';
                } elseif ($year < 2000 || $year > 2100) {
                    $status = 'error'; $reason = 'Jahr unplausibel';
                } elseif ($from === '' && $to === '' && $special) {
                    // Urlaub/Krank/Feiertag/Ueberstunden: Stunden genuegen, gerechnet wird ab 08:00
                    if ($hrs === '' || $hrs === false) {
                        $status = 'error'; $reason = $hrs === false ? 'Stunden nicht lesbar' : 'Stunden oder Beginn/Ende eintragen';
                    } elseif ($hrs < 0.25 || $hrs > 16 || abs($hrs * 20 - round($hrs * 20)) > 0.001) {
                        $status = 'error'; $reason = 'Stunden dezimal eintragen (z. B. 4 oder 7,7; 0,25 bis 16), nicht als Uhrzeit';
                    } else {
                        $from = self::START;
                        $to = self::add_minutes($from, (int)round($hrs * 60));
                        $from_hours = (int)round($hrs * 60);
                    }
                } elseif ($from === '' || $to === '') {
                    $status = 'error';
                    $reason = ($from === '' && $to === '') ? 'Beginn und Ende müssen eingetragen werden'
                                                           : 'Beginn und Ende müssen beide eingetragen werden';
                } elseif ($from === $to) {
                    $status = 'error'; $reason = 'Beginn und Ende sind gleich';
                } elseif ($text === '') {
                    $status = 'error'; $reason = 'Klient fehlt';
                }

                $row = self::make_row($n, $title, $line, $date, $text, $type, $special, $from, $to, $status, $reason);
                if ($status === 'ok') {
                    $row['minutes'] = self::minutes($from, $to);
                    if ($from_hours > 0)            $row['warn'] = 'aus Stunden berechnet (ab ' . self::START . ')';
                    elseif ($from > $to)            $row['warn'] = 'über Mitternacht';
                    elseif ($row['minutes'] > 720)  $row['warn'] = 'mehr als 12 Stunden';
                }
                $rows[] = $row;
            }
            $info[] = ['title' => $title, 'rows' => $cnt, 'note' => ''];
        }
        return ['rows' => $rows, 'sheets' => $info, 'owner' => $owner];
    }

    private static function make_row($n, $sheet, $line, $date, $text, $type, $special, $from, $to, $status, $reason)
    {
        return [
            'n' => $n, 'sheet' => $sheet, 'line' => $line, 'date' => $date, 'text' => $text,
            'type' => $type, 'special' => (bool)$special,
            'from' => ($from === false ? '' : $from), 'to' => ($to === false ? '' : $to),
            'minutes' => 0, 'status' => $status, 'reason' => $reason, 'warn' => '',
        ];
    }

    /** Minuten zwischen zwei 'HH:MM' (Ende < Beginn = ueber Mitternacht), wie die DB-Spalte timesheet_minutes */
    public static function minutes($from, $to)
    {
        $a = (int)substr($from, 0, 2) * 60 + (int)substr($from, 3, 2);
        $b = (int)substr($to, 0, 2) * 60 + (int)substr($to, 3, 2);
        return $b >= $a ? $b - $a : $b + 1440 - $a;
    }

    /* ------------------------------------------------------------------
     * 3. Kunden zuordnen
     * ------------------------------------------------------------------ */

    private static function tokens($s)
    {
        $n = self::norm($s);
        return $n === '' ? [] : explode(' ', $n);
    }

    private static function token_sim($a, $b)
    {
        if ($a === $b) return 1.0;
        $l = max(strlen($a), strlen($b));
        return $l === 0 ? 0.0 : max(0.0, 1 - levenshtein($a, $b) / $l);
    }

    /** Aehnlichkeit zweier Namen 0..1, unabhaengig von der Reihenfolge der Namensteile, tolerant bei Tippfehlern */
    public static function name_score($a, $b)
    {
        $ta = self::tokens($a);
        $tb = self::tokens($b);
        if (!$ta || !$tb) return 0.0;
        $sa = $ta; sort($sa);
        $sb = $tb; sort($sb);
        if ($sa === $sb) return 1.0;
        $dir = function ($x, $y) {
            $sum = 0.0;
            foreach ($x as $t) {
                $best = 0.0;
                foreach ($y as $u) $best = max($best, self::token_sim($t, $u));
                $sum += $best;
            }
            return $sum / count($x);
        };
        return min(0.99, ($dir($ta, $tb) + $dir($tb, $ta)) / 2);
    }

    /**
     * @param string $text    Name aus der Excel-Datei
     * @param array  $clients Liste von ['id'=>int, 'label'=>string]
     * @return array ['id'=>int, 'quality'=>'exact'|'suggest'|'none', 'score'=>float, 'candidates'=>[id,...] (beste zuerst, max 5)]
     */
    public static function match_client($text, array $clients)
    {
        $scored = [];
        foreach ($clients as $c) {
            $s = self::name_score($text, $c['label']);
            if ($s > 0.5) $scored[] = ['id' => (int)$c['id'], 'score' => $s];
        }
        usort($scored, function ($x, $y) {
            return $y['score'] <=> $x['score'] ?: $x['id'] <=> $y['id'];
        });
        $cand = array_slice(array_column($scored, 'id'), 0, 5);
        if (!$scored) return ['id' => 0, 'quality' => 'none', 'score' => 0.0, 'candidates' => []];

        $best = $scored[0]['score'];
        $second = $scored[1]['score'] ?? 0.0;
        if ($best >= 1.0)  return ['id' => $scored[0]['id'], 'quality' => 'exact', 'score' => $best, 'candidates' => $cand];
        if ($best >= 0.8 && ($best - $second) >= 0.05) {
            return ['id' => $scored[0]['id'], 'quality' => 'suggest', 'score' => $best, 'candidates' => $cand];
        }
        return ['id' => 0, 'quality' => 'none', 'score' => $best, 'candidates' => $cand];
    }

    /** Schluessel fuer die Namenszuordnung im Formular */
    public static function map_key($text)
    {
        return substr(md5(self::norm($text)), 0, 12);
    }

    /* ------------------------------------------------------------------
     * 4. Datenbank
     * ------------------------------------------------------------------ */

    /** vorhandene (nicht geloeschte) Eintraege des Benutzers an den angegebenen Tagen: ['Y-m-d|HH:MM|HH:MM|kunde|typ' => true] */
    public function existing_keys($userid, array $dates)
    {
        $dates = array_values(array_unique($dates));
        if (!$dates) return [];
        $ym = [];
        foreach ($dates as $d) $ym[substr($d, 0, 7)] = [(int)substr($d, 0, 4), (int)substr($d, 5, 2)];

        $keys = [];
        foreach ($ym as $p) {
            $this->db->from('ip_timesheets');
            $this->db->where('timesheet_userid', (int)$userid);
            $this->db->where('timesheet_year', $p[0]);
            $this->db->where('timesheet_month', $p[1]);
            $this->db->where("(timesheet_change_status IS NULL OR timesheet_change_status <> 'deleted')", null, false);
            foreach ($this->db->get()->result() as $r) {
                $d = sprintf('%04d-%02d-%02d', $r->timesheet_year, $r->timesheet_month, $r->timesheet_day);
                $keys[self::dup_key($d, substr($r->timesheet_start, 0, 5), substr($r->timesheet_end, 0, 5), $r->timesheet_clientid, $r->timesheet_type)] = true;
            }
        }
        return $keys;
    }

    public static function dup_key($date, $from, $to, $client, $type)
    {
        return $date . '|' . $from . '|' . $to . '|' . (int)$client . '|' . $type;
    }

    /**
     * Zeilen speichern.
     *
     * @param int   $userid  Zielbenutzer
     * @param array $rows    ausgewaehlte, gepruefte Zeilen aus parse_sheets() (status ok)
     * @param array $map     map_key(text) => Kunden-ID | 'none' (ohne Kunde, Name als Bemerkung) | 'skip'
     * @return array ['imported'=>int, 'duplicates'=>int, 'skipped'=>int, 'failed'=>[[line,reason]], 'first_date'=>?string]
     */
    public function import_rows($userid, array $rows, array $map)
    {
        $res = ['imported' => 0, 'duplicates' => 0, 'skipped' => 0, 'failed' => [], 'first_date' => null];
        $have = $this->existing_keys($userid, array_column($rows, 'date'));
        $now = date('Y-m-d H:i:s');

        foreach ($rows as $r) {
            if ($r['status'] !== 'ok') { $res['skipped']++; continue; }

            $client = 0;
            $remark = '';
            if ($r['special']) {
                $remark = $r['text'];                     // z. B. "Urlaub", "Neujahr/ Feiertag"
            } else {
                $m = $map[self::map_key($r['text'])] ?? 'skip';
                if ($m === 'skip') { $res['skipped']++; continue; }
                if ($m === 'none') $remark = $r['text'];
                else $client = (int)$m;
                if ($m !== 'none' && $client <= 0) { $res['skipped']++; continue; }
            }

            $k = self::dup_key($r['date'], $r['from'], $r['to'], $client, $r['type']);
            if (isset($have[$k])) { $res['duplicates']++; continue; }

            $y = (int)substr($r['date'], 0, 4);
            $mo = (int)substr($r['date'], 5, 2);
            $d = (int)substr($r['date'], 8, 2);
            try {
                $this->db->insert('ip_timesheets', [
                    'timesheet_userid'        => (int)$userid,
                    'timesheet_year'          => $y,
                    'timesheet_month'         => $mo,
                    'timesheet_day'           => $d,
                    'timesheet_start'         => $r['from'],
                    'timesheet_end'           => $r['to'],
                    'timesheet_clientid'      => $client,
                    'timesheet_remark'        => mb_substr($remark, 0, 255),
                    'timesheet_km'            => 0,
                    'timesheet_type'          => $r['type'],
                    'timesheet_uuid'          => Mdl_Timesheets::new_uuid(),
                    'timesheet_timestamp'     => $now,
                    'timesheet_change_status' => 'created',
                ]);
                $have[$k] = true;
                $res['imported']++;
                if ($res['first_date'] === null || $r['date'] < $res['first_date']) $res['first_date'] = $r['date'];
            } catch (Exception $e) {
                $res['failed'][] = [$r['sheet'] . ' Zeile ' . $r['line'], $e->getMessage()];
            }
        }
        return $res;
    }
}
