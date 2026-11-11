<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * InvoicePlane Timesheets module
 *
 * @author              Chrissie Brown
 * @copyright   Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license             https://invoiceplane.com/license.txt
 * @link                https://invoiceplane.com
 */

// Hint
// https://codeigniter.com/userguide3/database/query_builder.html

/**
 * Class Timesheets
 */
use mikehaertl\pdftk\Pdf;

class Timesheets extends Admin_Controller
{
    private $monatsnamen = array(
            1=>"Januar",
            2=>"Februar",
            3=>"März",
            4=>"April",
            5=>"Mai",
            6=>"Juni",
            7=>"Juli",
            8=>"August",
            9=>"September",
            10=>"Oktober",
            11=>"November",
            12=>"Dezember");

    private function month_name($n)
    {
        return $this->monatsnamen[(int)$n] ?? '';
    }

    public function __construct()
    {
        parent::__construct();

        $this->load->model('mdl_timesheets');
        $this->load->model('users/mdl_users');
        $this->load->model('clients/mdl_clients');
        $this->load->model('clients/mdl_client_extended');
        $this->load->model('user_clients/mdl_user_clients');

        //$this->load->model('custom_fields/mdl_custom_fields');
        //$this->load->model('custom_fields/mdl_client_custom');
        $this->load->helper('date_helper');
    }

    /* ------------------------------------------------------------------
     * Helfer
     * ------------------------------------------------------------------ */

    private function is_admin()
    {
        return $this->session->userdata('user_type') == 1;
    }

    /** URL-Praefix: Admins 'timesheets', alle anderen 'employee/timesheets' */
    private function ts_base()
    {
        return $this->is_admin() ? 'timesheets' : 'employee/timesheets';
    }

    /**
     * Benutzer / Monat / Jahr aus URL und POST auswerten (gemeinsam fuer index, view, form).
     *
     *  - Nicht-Admins sehen und bearbeiten IMMER nur sich selbst (URL und POST werden ignoriert).
     *  - Fehlende/ungueltige Werte werden auf eine kanonische URL umgeleitet (kein Redirect-Loop,
     *    nach dem Redirect stimmen alle Werte).
     *
     * @return array [user_id, month, year, user]
     */
    private function resolve_context($action, $id, $month, $year)
    {
        $own      = (int)$this->session->userdata('user_id');
        $admin    = $this->is_admin();
        $redirect = false;

        if ($this->input->post('btn_submit_user')) {
            if ($this->input->post('my_year'))  $year  = $this->input->post('my_year');
            if ($this->input->post('my_month')) $month = $this->input->post('my_month');
            if ($admin && $this->input->post('my_userid')) $id = $this->input->post('my_userid');
            $redirect = true;
        }

        $id_n = $admin ? (int)$id : $own;
        if ($id_n <= 0) $id_n = $own;

        $this_year = (int)date('Y');
        $year_n = (int)$year;
        if ($year_n < 2000 || $year_n > $this_year + 2) $year_n = $this_year;
        $month_n = (int)$month;
        if ($month_n < 1 || $month_n > 12) $month_n = (int)date('n');

        if ($redirect
            || (string)$id    !== (string)$id_n
            || (string)$month !== (string)$month_n
            || (string)$year  !== (string)$year_n) {
            redirect($this->ts_base() . '/' . $action . '/' . $id_n . '/' . $month_n . '/' . $year_n);
        }

        $user = $this->mdl_users->get_by_id($id_n);
        if (empty($user)) {
            if ($id_n !== $own) {
                redirect($this->ts_base() . '/' . $action . '/' . $own . '/' . $month_n . '/' . $year_n);
            }
            show_404();
        }

        return [$id_n, $month_n, $year_n, $user];
    }

    /** Jahre fuer die Auswahl: naechstes Jahr bis 5 Jahre zurueck (+ das gerade angezeigte, falls abweichend) */
    private function year_options($year)
    {
        $y = (int)date('Y');
        $years = range($y + 1, $y - 5);
        if (!in_array((int)$year, $years, true)) {
            $years[] = (int)$year;
            rsort($years);
        }
        return $years;
    }

    private function active_users()
    {
        return $this->is_admin() ? $this->mdl_users->where('user_active', 1)->get()->result() : [];
    }

    /**
     * Alle Kunden (auch inaktive, alte Eintraege muss man nachsehen koennen),
     * nur die Felder, die die Views brauchen.
     */
    private function client_list()
    {
        $uc = $this->mdl_clients->order_by('ip_clients.client_name', 'ASC')->get()->result();
        $user_clients = [];
        foreach ($uc as $u) {
            if (!empty($u->client_id)) { // there may be defect clients with no id in database
                $o = new stdClass();
                $o->client_id = $u->client_id;
                $o->customer_no = $u->customer_no;
                $o->client_surname = $u->client_surname;
                $o->client_name = $u->client_name;
                $user_clients[] = $o;
            }
        }
        return $user_clients;
    }

    /* ------------------------------------------------------------------
     * Uebersicht
     * ------------------------------------------------------------------ */
    public function index($id = null, $month = null, $year = null)
    {
        list($id, $month, $year, $user) = $this->resolve_context('index', $id, $month, $year);

        $users = [];
        $ts_hm_all = null;
        $ts_km_all = 0;
        if ($this->is_admin()) {
            $users = $this->active_users();
            $sums  = $this->mdl_timesheets->get_month_summary_by_user($year, $month); // 1 Query statt 1 pro User
            foreach ($users as $u) {
                $u->work     = $sums[(int)$u->user_id]['work'] ?? 0;      // Minuten
                $u->non_work = $sums[(int)$u->user_id]['non_work'] ?? 0;  // Minuten
            }
            $ts_hm_all = $this->mdl_timesheets->get_timesheet_hours_month(0, $year, $month);
            $ts_km_all = $this->mdl_timesheets->get_monthly_km(0, $year, $month);
        }

        $ts_hm = $this->mdl_timesheets->get_timesheet_hours_month($id, $year, $month);
        $ts_km = $this->mdl_timesheets->get_monthly_km($id, $year, $month);

        $fast_datea = (int)date('Y');
        $fast_dateb = $fast_datea - 5;
        $fast_h = $this->mdl_timesheets->get_year_minutes($id, $fast_dateb, $fast_datea); // 1 Query statt 72

        $this->layout->set([
            'ts_base'    => $this->ts_base(),
            'worktypes'  => Mdl_Timesheets::worktypes(),
            'all_month'  => $this->monatsnamen,
            'years'      => $this->year_options($year),
            'fast_h'     => $fast_h,
            'fast_datea' => $fast_datea,
            'fast_dateb' => $fast_dateb,
            'ts_km_all'  => $ts_km_all,
            'ts_hm_all'  => $ts_hm_all,
            'ts_km'      => $ts_km,
            'ts_hm'      => $ts_hm,
            'user'       => $user,
            'users'      => $users,
            'user_id'    => $id,
            'year'       => $year,
            'month'      => $month,
        ]);

        $this->layout->buffer('content', 'timesheets/index');
        $this->layout->render();
    }


    public function view($id = null, $month = null, $year = null)
    {
        list($id, $month, $year, $user) = $this->resolve_context('view', $id, $month, $year);

        $ts_hm = $this->mdl_timesheets->get_timesheet_hours_month($id, $year, $month);
        $ts_km = $this->mdl_timesheets->get_monthly_km($id, $year, $month);

        $this->layout->set([
                'month_name' => $this->month_name($month),
                'month' => $month,
                'all_month' => $this->monatsnamen,
                'year' => $year,
                'user_clients' => $this->client_list(),
                'user' => $user,
                'user_id' => $id,
                'users' => $this->active_users(),
                'ts_hm' => $ts_hm,
                'ts_km' => $ts_km
        ]);

        $this->layout->buffer('content', 'timesheets/view');
        $this->layout->render();
    }

    /* print work time (PDF eines Monats fuer einen Benutzer) */
    private function __do_one_wt_print($id, $month, $year)
    {
        $user = $this->mdl_users->get_by_id($id);
        $ts_hm = $this->mdl_timesheets->get_timesheet_hours_month($id, $year, $month);
        $ts_km = $this->mdl_timesheets->get_monthly_km($id, $year, $month);

        $data = [
            'month_name' => $this->month_name($month),
            'month' => $month,
            'all_month' => $this->monatsnamen,
            'year' => $year,
            'user_clients' => $this->client_list(),
            'user' => $user,
            'user_id' => $id,
            'ts_hm' => $ts_hm,
            'ts_km' => $ts_km
        ];

        $html = $this->load->view('timesheets/print', $data, true);

        $this->load->helper('mpdf');
        $my_r = pdf_create($html, "timesheet-month-".$id."-".$month."-".$year,
                false, null, null, null, false, null, false, false, 1);
        return $my_r;
    }

    public function print($id = null, $month = null, $year = null, $all = false)
    {
        // Nicht-Admins drucken nur sich selbst, "alle" nur fuer Admins
        if (!$this->is_admin()) {
            $id  = $this->session->userdata('user_id');
            $all = false;
        }
        $id = (int)$id;
        $month = (int)$month;
        $year  = (int)$year;
        if (!$id || $month < 1 || $month > 12 || $year < 2000) redirect($this->ts_base() . '/index');

        if ($all == 'all') {
            $users = $this->mdl_users->where('user_active', 1)->get()->result();
            $pdfs = [];
            foreach ($users as $user) {
                $summary = $this->mdl_timesheets->get_monthly_work_nonwork_summary($user->user_id, $year, $month);
                if (!empty($summary)) {
                    $pdfs[] = $this->__do_one_wt_print($user->user_id, $month, $year);
                }
            }
            if (empty($pdfs)) redirect($this->ts_base() . '/index');

            $combined = UPLOADS_TEMP_FOLDER . "timesheet-month-ALL-".$month.'-'.$year.'.pdf';

            $pdf = new Pdf();
            foreach ($pdfs as $f) {
                $pdf->addFile($f);
            }
            $pdf->saveAs($combined);

            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="timesheet-month-ALL-'.$month.'-'.$year.'.pdf"');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
            @readfile($combined);

            // temporaere Dateien aufraeumen
            foreach ($pdfs as $f) @unlink($f);
            @unlink($combined);
        } else {
            $my_r = $this->__do_one_wt_print($id, $month, $year);
            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="timesheet-month-'.$id.'-'.$month.'-'.$year.'.pdf"');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
            @readfile($my_r);
        }
        exit; // PDF ist ausgegeben - ein Redirect waere hier wirkungslos
    }


    /* ------------------------------------------------------------------
     * Arbeitszeiteingabe (speichern/loeschen laeuft ueber timesheets/ajax/...)
     * ------------------------------------------------------------------ */
    public function form($id = null, $month = null, $year = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect($this->ts_base() . '/index');
        }

        list($id, $month, $year, $user) = $this->resolve_context('form', $id, $month, $year);

        // Altbestand ohne UUID absichern, sonst entstehen beim Speichern Dubletten
        $this->mdl_timesheets->ensure_uuids($id, $year, $month);

        $user_clients = $this->client_list();
        $timesheets   = $this->mdl_timesheets->get_timesheet_month($id, $year, $month);

        // Kunden einmal aufbereiten: Label-Map (PHP) + Liste fuer die Suche (JS)
        $client_labels = [];
        $kunden_js = [];
        foreach ($user_clients as $c) {
            $label = trim(trim((string)$c->client_name) . ' ' . trim((string)$c->client_surname))
                   . ' (' . $c->customer_no . ')';
            $client_labels[(int)$c->client_id] = $label;
            $kunden_js[] = ['id' => (int)$c->client_id, 'name' => $label];
        }

        $this->layout->set([
                'ts_base' => $this->ts_base(),
                'worktypes' => Mdl_Timesheets::worktypes(),
                'month_name' => $this->month_name($month),
                'month' => $month,
                'all_month' => $this->monatsnamen,
                'years' => $this->year_options($year),
                'year' => $year,
                'client_labels' => $client_labels,
                'kunden_js' => $kunden_js,
                'user' => $user,
                'user_id' => $id,
                'users' => $this->active_users(),
                'timesheets' => $timesheets,
        ]);

        $this->layout->buffer('content', 'timesheets/form');
        $this->layout->render();
    }

    /* ==================================================================
     * Sync-API fuer die Zeiterfassungs-App
     * ------------------------------------------------------------------
     * Authentifizierung: JWT ueber verifyJWT() (vorerst im Base_Controller).
     * Der Benutzer wird AUSSCHLIESSLICH aus dem Token bestimmt. Ein "userEmail"
     * im Body ist optional und muss, falls vorhanden, zum Token passen.
     *
     * Antworten: immer JSON. HTTP 401 bei Token-Problemen (verifyJWT), sonst
     * HTTP 200 mit success=false + reason (wie bisher), damit die App-Logik
     * unveraendert bleibt.
     *
     | timesheet_uuid          | varchar(36)
     | timesheet_timestamp     | datetime
     | timesheet_sync_status   | enum('synced','dirty')
     | timesheet_change_status | enum('created','updated','deleted','synced')
     * ================================================================== */

    /** Claim(s) im Token, die die E-Mail des Benutzers enthalten (erster Treffer zaehlt). */
    private $api_token_email_claims = ['email', 'userEmail', 'user_email'];

    private function api_respond(array $payload)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit(0);
    }

    /**
     * Gemeinsamer Einstieg aller api_*-Methoden: CORS, Token pruefen, Benutzer aus
     * dem Token laden, JSON-Body lesen. Beendet die Anfrage selbst mit einer
     * JSON-Antwort, wenn etwas nicht stimmt.
     *
     * @return array [int $userid, array $body]
     */
    private function api_begin()
    {
        $this->load->helper('cors_helper');
        x_cors_helper();
        $token = $this->verifyJWT();   // beendet die Anfrage mit 401, wenn das Token ungueltig ist

        $email = '';
        foreach ($this->api_token_email_claims as $claim) {
            if (!empty($token->$claim) && is_string($token->$claim)) {
                $email = trim($token->$claim);
                break;
            }
        }
        if ($email === '') {
            log_message('error', 'timesheets api: Token ohne E-Mail-Claim (' . implode('/', $this->api_token_email_claims) . ')');
            $this->api_respond(['success' => false, 'reason' => 'token_without_email']);
        }

        $body = json_decode((string)$this->input->raw_input_stream, true);
        if (!is_array($body)) {
            $this->api_respond(['success' => false, 'reason' => 'invalid_json']);
        }

        // Sendet die App weiterhin userEmail mit, muss es zum Token passen
        $claimed = trim((string)($body['userEmail'] ?? ''));
        if ($claimed !== '' && strcasecmp($claimed, $email) !== 0) {
            log_message('error', 'timesheets api: userEmail im Body passt nicht zum Token (' . $email . ')');
            $this->api_respond(['success' => false, 'reason' => 'forbidden']);
        }

        $user = $this->db->select('user_id, user_active')->where('user_email', $email)->get('ip_users')->row();
        if (empty($user) || (int)$user->user_active !== 1) {
            $this->api_respond(['success' => false, 'reason' => 'unknown_user']);
        }
        return [(int)$user->user_id, $body];
    }

    /** UUID aus body.data.id (fuer remove), sonst Antwort 'invalid_id' */
    private function api_item_id($body)
    {
        $uuid = trim((string)(is_array($body['data'] ?? null) ? ($body['data']['id'] ?? '') : ''));
        if (!Mdl_Timesheets::valid_uuid($uuid)) {
            $this->api_respond(['success' => false, 'reason' => 'invalid_id']);
        }
        return $uuid;
    }

    /** body.data pruefen und normalisieren; bei Fehler Antwort mit reason */
    private function api_item($body)
    {
        $d = $body['data'] ?? null;
        if (empty($d) || !is_array($d)) {
            $this->api_respond(['status' => 'empty']);
        }
        $uuid = $this->api_item_id($body);

        if (!preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', (string)($d['date'] ?? ''), $m)
            || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            $this->api_respond(['success' => false, 'reason' => 'invalid_date']);
        }
        $from = Mdl_Timesheets::normalize_time($d['startTime'] ?? '');
        $to   = Mdl_Timesheets::normalize_time($d['endTime'] ?? '');
        if ($from === null || $to === null) {
            $this->api_respond(['success' => false, 'reason' => 'invalid_time']);
        }
        $km     = Mdl_Timesheets::normalize_km($d['km'] ?? 0);
        $status = (string)($d['changeStatus'] ?? '');

        return [
            'uuid'      => $uuid,
            'year'      => (int)$m[1],
            'month'     => (int)$m[2],
            'day'       => (int)$m[3],
            'start'     => $from,
            'end'       => $to,
            // Spalten sind NOT NULL (Migration): null aus dem Sync-Client abfangen
            'client'    => max(0, (int)($d['clientId'] ?? 0)),
            'remark'    => mb_substr((string)($d['remark'] ?? ''), 0, 255),
            'km'        => $km === null ? 0 : $km,
            'type'      => mb_substr((string)($d['type'] ?? ''), 0, 15),
            'timestamp' => trim((string)($d['timestamp'] ?? '')),
            'status'    => in_array($status, ['created', 'updated', 'deleted', 'synced'], true) ? $status : '',
        ];
    }

    /** Bestehende Zeile zur UUID; fremde Zeilen beenden die Anfrage mit 'forbidden' */
    private function api_own_row($uuid, $userid)
    {
        $row = $this->db->get_where('ip_timesheets', ['timesheet_uuid' => $uuid])->row_array();
        if ($row && (int)$row['timesheet_userid'] !== (int)$userid) {
            $this->api_respond(['success' => false, 'reason' => 'forbidden']);
        }
        return $row;
    }

    public function api_create()
    {
        list($userid, $body) = $this->api_begin();
        $it = $this->api_item($body);

        // Wiederholter Upload (Retry nach Verbindungsabbruch): nichts doppelt anlegen
        if ($this->api_own_row($it['uuid'], $userid)) {
            $this->api_respond(['success' => true, 'reason' => 'exists']);
        }

        $this->mdl_timesheets->insert_timesheet_line(
            $userid, $it['year'], $it['month'], $it['day'], $it['start'], $it['end'],
            $it['client'], $it['remark'], $it['km'], $it['type'],
            $it['uuid'], $it['timestamp'], $it['status']
        );
        $this->api_respond(['success' => true]);
    }

    public function api_update()
    {
        list($userid, $body) = $this->api_begin();
        $it = $this->api_item($body);

        if (!$this->api_own_row($it['uuid'], $userid)) {
            $this->api_respond(['success' => false, 'reason' => 'not_found']);
        }

        $this->db->where('timesheet_uuid', $it['uuid'])->where('timesheet_userid', $userid);
        $this->db->update('ip_timesheets', [
            'timesheet_day'       => $it['day'],
            'timesheet_month'     => $it['month'],
            'timesheet_year'      => $it['year'],
            'timesheet_clientid'  => $it['client'],
            'timesheet_start'     => $it['start'],
            'timesheet_end'       => $it['end'],
            'timesheet_remark'    => $it['remark'],
            'timesheet_km'        => $it['km'],
            'timesheet_type'      => $it['type'],
            'timesheet_timestamp' => $it['timestamp'] !== '' ? $it['timestamp'] : date('Y-m-d H:i:s'),
        ]);
        $this->api_respond(['success' => true]);
    }

    // delete darf nicht im function namen vorkommen sonst 404
    public function api_remove()
    {
        list($userid, $body) = $this->api_begin();
        $uuid = $this->api_item_id($body);

        if ($this->api_own_row($uuid, $userid)) {
            $this->db->delete('ip_timesheets', ['timesheet_uuid' => $uuid, 'timesheet_userid' => $userid]);
        }
        $this->api_respond(['success' => true, 'reason' => 'deleted']);
    }

    public function api_fetch_dirty()
    {
        list($userid, $body) = $this->api_begin();

        $year  = (int)($body['selectedYear'] ?? 0);
        $month = (int)($body['selectedMonth'] ?? 0);
        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            $this->api_respond(['status' => 'empty']);
        }

        // wichtich hier with deleted!!!
        $timesheets = $this->mdl_timesheets->get_timesheet_month_with_deleted($userid, $year, $month);

        $tresult = [];
        foreach ($timesheets as $row) {
            $tresult[] = [
                'id'           => $row->timesheet_uuid,
                'clientId'     => $row->timesheet_clientid,
                'date'         => $row->timesheet_year . "-" . $row->timesheet_month . "-" . $row->timesheet_day,
                'startTime'    => $row->timesheet_start,
                'endTime'      => $row->timesheet_end,
                'type'         => $row->timesheet_type,
                'remark'       => $row->timesheet_remark,
                'km'           => $row->timesheet_km,
                'timestamp'    => $row->timesheet_timestamp,
                'changeStatus' => $row->timesheet_change_status,   // kann auch deleted sein
            ];
        }
        $this->api_respond(['success' => true, 'data' => $tresult]);
    }

/*
// TODO
public function api_mark_synced() {
    $data = json_decode($this->input->raw_input_stream, true);
    foreach ($data['ids'] as $id) {
        $this->db->update('timesheets', ['sync_status' => 'synced'], ['id' => $id]);
    }
    echo json_encode(['success' => true]);
}
*/

/*
private function generate_uuid_v4() {
    return sprintf('%04x%04x-%04x-%04x-4%03x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
public function set_missing_uuids() {
    $this->load->helper('string'); // für UUID Generation

    // Hole alle Einträge ohne UUID
    $query = $this->db->where('timesheet_uuid', '')->or_where('timesheet_uuid IS NULL', NULL, FALSE)
    ->get('ip_timesheets');
    
    if ($query->num_rows() > 0) {
        foreach ($query->result() as $row) {
            $uuid = $this->generate_uuid_v4(); // eigene UUID Funktion
            $this->db->where('id', $row->id)
                     ->update('ip_timesheets', ['timesheet_uuid' => $uuid]);
        }
        return $query->num_rows() . ' UUIDs wurden gesetzt.';
    } else {
        return 'Keine fehlenden UUIDs gefunden.';
    }
}
*/

    /****
     *
     * print a general pdf timesheet  
     */
    public function evidence_print($userid = 0, $clientid = 0, $immediate_print = 0) 
    {
        $this->load->model('user_clients/mdl_user_clients');

        // get all users
        $users = $this->mdl_users->get()->result();
        $this->layout->set('users', $users);

        if ($this->input->post('btn_change_user')) {
            $userid = $this->input->post('users');
            //redirect('timesheets/evidence_print/'.$userid."/0");
            redirect('employee/timesheets/evidence_print/'.$userid."/0");
        }

        // fetch userdata and client data from database.
        $this->layout->set(['user_id' => 0]);
        if ($userid != 0) {
        $user =  $this->mdl_users->get_by_id($userid);
        $this->layout->set(
                [ 'user_name' => $user->user_name,
                  'user_id' => $user->user_id,
        ]);

            $clients = $this->mdl_user_clients->assigned_to($userid)->get()->result();
            $this->layout->set('clients', $clients);
        } else {
            // get all clients
            $this->mdl_clients->order_by('ip_clients.client_name', 'ASC');
            $clients = $this->mdl_clients->get()->result();
            $this->layout->set('clients', $clients);
        }

        /* 
         * only print for one customer  
         */
        if ($clientid != 0) {
            $client =  $this->mdl_clients->get_by_id($clientid);

            $customer_no = $client->customer_no;

            $this->layout->set(
                    [
                    'customer_no' => $customer_no,
                    'client_fullname' => $client->client_fullname,
                    'client_street' => $client->client_address_1,
                    'client_city' => $client->client_city,
                    'client_zip' => $client->client_zip,
                    ]);
        }


        //var_dump ($customer_no);
        //var_dump ($client->client_fullname);
        //var_dump ($client->client_city);
        //var_dump ($client->client_zip);

        $year = date("Y");
        $month = date("n");
        $to_month = date("n");
        $month_name = $this->month_name($month);

        $this->layout->set(
                [
                'month_name' => $this->month_name($month),
                'month' => $month,
                'all_month' => $this->monatsnamen,
                'to_month' => $to_month,
                'year' => $year,
                'all_year' => 
                [ $year-2, $year-1, $year, $year+1, $year+2, $year+3]
                /*
                   ['2020','2021','2022']
                 */
                ]
                );

        //////
        // Submit !!! Generate PDFs
        if ($this->input->post('btn_submit') || $this->input->post('btn_submit_blank') || $immediate_print > 0) {
            // date from post override if set

            if ($this->input->post('my_month')) $month = $this->input->post('my_month');
            if ($this->input->post('to_month')) $to_month = $this->input->post('to_month');
            if ($this->input->post('my_year')) $year = $this->input->post('my_year');

            ////
            // print multiple selected clients from checkbox
            // coded in a hurry 10.2021 by chrissie - TODO rework / clarify etc.
            if (null !== $this->input->post('clients') && !$this->input->post('blank_generate')) {
                $pdfs=[];
                foreach ($this->input->post('clients') as $c) {

                    $client =  $this->mdl_clients->get_by_id($c);
                    $customer_no = $client->customer_no;		// get custtomer number from custom fields

                    for ($m = $month; $m <= $to_month; $m++) { 
                        $data = array(
                                'customer_no' => $customer_no,
                                'client_fullname' => $client->client_fullname,
                                'client_street' => $client->client_address_1,
                                'client_city' => $client->client_city,
                                'client_zip' => $client->client_zip,
                                'year' => $year,
                                'month' => $m,
                                'month_name' => $this->month_name($m)
                                );
                        $html = $this->load->view('timesheets/timesheet_print', $data, true);
                        $this->load->helper('mpdf');
                        $r = pdf_create($html, "timesheet-".$userid."-".$m."-".$year."-".$customer_no,
                                false, null, null, null, false, null, false, false, 1);
                        $pdfs[]=$r;
                    }
                }

                //var_dump ($pdfs);
                $combined = UPLOADS_TEMP_FOLDER . "timesheet-".$userid."-".$month_name."-all.pdf";

                $pdf = new Pdf();
                foreach ($pdfs as $f) {
                    $pdf->addFile($f);
                }

                $pdf->saveAs($combined);

                // TODO unlink single pdfs

                header('Content-type: application/pdf');
                header('Content-Disposition: inline; filename="' . $combined );
                header('Content-Transfer-Encoding: binary');
                header('Accept-Ranges: bytes');
                @readfile ($combined);

                //redirect('timesheets/index');
                redirect('employee/timesheets/index');

                // end multiple
                ////
            } else {
                //////
                // print one, standard submit, month can be overridden in form
                if ($this->input->post('btn_submit_blank')) {
                    $data = array(
                            'user_name' => $user->user_name,
                            'user_id' => $user->user_id,
                            'personalno' => $personalno,
                            'customer_no' => '____________________',
                            'client_fullname' => '____________________',
                            'client_street' => '____________________',
                            'client_city' => '____________________',
                            'client_zip' => '_________',
                            'year' => $year,
                            'month' => $month,
                            'month_name' => $this->month_name($month)
                            );

                } else {
                    $data = array(
                            'user_name' => $user->user_name,
                            'user_id' => $user->user_id,
                            'personalno' => $personalno,
                            'customer_no' => $customer_no,
                            'client_fullname' => $client->client_fullname,
                            'client_street' => $client->client_address_1,
                            'client_city' => $client->client_city,
                            'client_zip' => $client->client_zip,
                            'year' => $year,
                            'month' => $month,
                            'month_name' => $this->month_name($month)
                            );
                }
                $html = $this->load->view('timesheets/timesheet_print', $data, true);

                $this->load->helper('mpdf');
                pdf_create($html, 'timesheets_print_'.$month_name, true, null, null, null, false, null, false, false, 1);

                // end print one
                ////
            }
            //redirect('timesheets/index');
            redirect('employee/timesheets/index');
        }
        // End submit
        //////

        $this->layout->buffer('content', 'timesheets/evidence_print');
        $this->layout->render();
    }

}
