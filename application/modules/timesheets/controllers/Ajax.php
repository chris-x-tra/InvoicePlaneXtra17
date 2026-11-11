<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author		InvoicePlane Developers & Contributors
 * @copyright	Copyright (c) 2012 - ... InvoicePlane.com & chrissie
 * @license		https://invoiceplane.com/license.txt
 * @link		https://invoiceplane.com
 */

/**
 * Class Ajax
 *
 * Speichern / Loeschen der Arbeitszeiteingabe.
 * Rechte: Admins (user_type 1) duerfen fuer jeden Benutzer speichern, alle anderen
 * ausschliesslich fuer sich selbst - die userid aus dem POST wird bei Nicht-Admins ignoriert.
 */
class Ajax extends Admin_Controller
{
    public $ajax_controller = true;

    private function json($data, $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data));
    }

    private function is_admin()
    {
        return $this->session->userdata('user_type') == 1;
    }

    /** Benutzer, fuer den gespeichert wird (0 = ungueltig) */
    private function target_userid()
    {
        $own = (int)$this->session->userdata('user_id');
        if (!$this->is_admin()) return $own;

        $id = (int)$this->input->post('userid');
        if ($id <= 0) return 0;
        $this->load->model('users/mdl_users');
        return $this->mdl_users->get_by_id($id) ? $id : 0;
    }

    /* Eine Zeile (Zusatzzeile oder bereits gespeicherte Zeile) loeschen = Soft-Delete */
    public function set_delete()
    {
        $this->load->model('timesheets/mdl_timesheets');

        $uuid = trim((string)$this->input->post('uuid'));
        if (!Mdl_Timesheets::valid_uuid($uuid)) {
            return $this->json(['success' => false, 'reason' => 'invalid uuid'], 400);
        }

        $row = $this->mdl_timesheets->find_by_uuid($uuid);
        if (!$row) {
            // nie gespeichert - es gibt nichts zu loeschen
            return $this->json(['success' => true, 'reason' => 'not_found']);
        }
        if (!$this->is_admin() && (int)$row->timesheet_userid !== (int)$this->session->userdata('user_id')) {
            return $this->json(['success' => false, 'reason' => 'forbidden'], 403);
        }

        $this->mdl_timesheets->set_delete($uuid);
        return $this->json(['success' => true]);
    }

    /*
     * Monat speichern. POST: userid, month, year, items (JSON-Array von Zeilen)
     *
     * Pro Zeile:
     *  - komplett leer + UUID existiert in der DB  -> Soft-Delete (Zeile wurde im Formular geleert)
     *  - komplett leer + unbekannt                 -> ignorieren
     *  - ungueltig                                 -> nicht speichern, Grund zurueckmelden
     *  - gueltig                                   -> Upsert ueber die UUID
     */
    public function update_by_uuid()
    {
        $this->load->model('timesheets/mdl_timesheets');

        $userid = $this->target_userid();
        $month  = (int)$this->input->post('month');
        $year   = (int)$this->input->post('year');
        $items  = json_decode((string)$this->input->post('items'), true);

        if ($userid <= 0 || $month < 1 || $month > 12 || $year < 2000 || $year > (int)date('Y') + 2 || !is_array($items)) {
            return $this->json(['success' => false, 'message' => 'Ungültige Anfrage'], 400);
        }

        $days = (int)date('t', mktime(0, 0, 0, $month, 1, $year));

        // gueltige Kunden einmal laden
        $valid_clients = array_flip(array_map('intval',
            array_column($this->db->select('client_id')->get('ip_clients')->result_array(), 'client_id')));

        $counter = 0;
        $saved_uuids = [];
        $deleted_uuids = [];
        $failures = [];

        foreach ($items as $raw) {
            if (!is_array($raw)) continue;

            $res  = Mdl_Timesheets::validate_item($raw, $days);
            $d    = $res['data'];
            $uuid = $d['uuid'];

            // --- leere Zeile: vorhandenen Eintrag loeschen, sonst ignorieren
            if ($res['empty']) {
                if (Mdl_Timesheets::valid_uuid($uuid)) {
                    $existing = $this->mdl_timesheets->find_by_uuid($uuid);
                    if ($existing && (int)$existing->timesheet_userid === $userid
                        && $existing->timesheet_change_status !== 'deleted') {
                        $this->mdl_timesheets->set_delete($uuid, $userid);
                        $deleted_uuids[] = $uuid;
                    }
                }
                continue;
            }

            $counter++;
            $reason = $res['valid'] ? null : $res['reason'];

            if ($reason === null) {
                $existing = $this->mdl_timesheets->find_by_uuid($uuid);
                if ($existing && (int)$existing->timesheet_userid !== $userid) {
                    $reason = 'Eintrag gehört einem anderen Benutzer';
                } elseif ($d['client'] > 0 && !isset($valid_clients[$d['client']])
                          && !($existing && (int)$existing->timesheet_clientid === $d['client'])) {
                    // (ein bereits gespeicherter, inzwischen geloeschter Kunde bleibt erlaubt)
                    $reason = 'Unbekannter Kunde';
                }
            }

            if ($reason !== null) {
                $failures[] = ['uuid' => $uuid, 'day' => $d['day'], 'reason' => $reason];
                continue;
            }

            $this->mdl_timesheets->save_line(
                $userid, $year, $month, $d['day'], $d['from'], $d['to'],
                $d['client'], $d['remark'], $d['km'], $d['type'], $uuid
            );
            $saved_uuids[] = $uuid;
        }

        return $this->json([
            'success'       => true,
            'counter'       => $counter,
            'saved'         => count($saved_uuids),
            'deleted'       => count($deleted_uuids),
            'failed'        => count($failures),
            'failed_uuids'  => array_column($failures, 'uuid'),
            'failures'      => $failures,
            'saved_uuids'   => $saved_uuids,
            'deleted_uuids' => $deleted_uuids,
            'message'       => 'save',
        ]);
    }
}
