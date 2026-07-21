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
 */
class Ajax extends Admin_Controller
{
    public $ajax_controller = true;

    public function set_delete()
    {
        $this->load->model('timesheets/mdl_timesheets');

        $userid = $this->input->post('userid');
        $uuid = $this->input->post('uuid');
        if($uuid)
            $this->mdl_timesheets->set_delete ($uuid);
    }


    /*
     * Save-Check: Ist eine Zeile gultig oder fehler
     */
    private function validate_timesheet_item($it)
    {
        /*
         * Rückgabe:
         * [
         *   'valid'  => true/false,
         *   'empty'  => true/false,
         *   'reason' => ...
         * ]
         */

        $type     = trim($it->x_type ?? '');
        $from     = $it->x_from ?? '00:00';
        $to       = $it->x_to ?? '00:00';
        $customer = (int)($it->x_customer_id ?? 0);
        $remark   = trim($it->x_remark ?? '');

        $typeSet     = ($type !== '');
        $fromSet     = ($from !== '00:00');
        $toSet       = ($to !== '00:00');
        $customerSet = ($customer > 0);
        $remarkSet   = ($remark !== '');

        /*
         * Prüfen, ob überhaupt etwas eingegeben wurde.
         * Leere Zeilen ignorieren.
         */
        $started =
            $typeSet ||
            $customerSet ||
            $remarkSet ||
            $fromSet ||
            $toSet;

        if (!$started) {
            return [
                'valid'  => true,
                'empty'  => true,
                'reason' => 'empty'
            ];
        }


        /*
         * Ab hier ist eine Eingabe vorhanden.
         * Dann müssen Typ und Zeiten vorhanden sein.
         */
        if (!$typeSet) {
            return [
                'valid'  => false,
                'empty'  => false,
                'reason' => 'missing type'
            ];
        }

        // Zeit nur pru�fen, wenn Typ vorhanden
        if (!$fromSet && !$toSet && $type !== 'U' && $type !=='UU' ) {
            return [
                'valid'  => false,
                'empty'  => false,
                'reason' => 'missing time'
            ];
        }

        // wenn Typ vorhanden, muss from anders als to sein, es muss mind 1 min gearbeitet werden
        if ( $type !== 'U' && $type !=='UU' && ($from == $to) ) {
            return [
                'valid'  => false,
                'empty'  => false,
                'reason' => 'missing time'
            ];
        }

        /* Kunde ist optional, aber dann muss Bemerkung vorhanden sein.
         * Beispiel:
         * Kunde nicht im System -> Bemerkung "Firma Müller"
         */
        if (!$customerSet && !$remarkSet) {
            return [
                'valid'  => false,
                'empty'  => false,
                'reason' => 'missing customer or remark'
            ];
        }


        return [
            'valid'  => true,
            'empty'  => false,
            'reason' => 'ok'
        ];
    }

    public function update_by_uuid() 
    {
        $this->load->model('timesheets/mdl_timesheets');

        $userid = $this->input->post('userid');
        $month = $this->input->post('month');
        $year = $this->input->post('year');
        $items = json_decode($this->input->post('items'));

        // debugging file write
        $dbgfile = UPLOADS_TEMP_FOLDER . 'time-dbg.txt';
        $myfile  = fopen($dbgfile, "w") or die("Unable to open file!");
        fwrite($myfile, "M:".$month .", Y:".$year.", U:".$userid."!\n");
        fwrite($myfile, print_r($items, true));
        fclose($myfile);
        // end debug

        $saved = 0;
        $i = 0;
        $uuids = [];
        if($userid && $month && $year) {
            foreach ($items as $it) {
                // dynamisch erzeugte kundenfelder ubernehmen
                foreach ($it as $key => $value) {
                    if (strpos($key, 'x_customer-DYNAMIC') === 0) {
                        $it->x_customer_id = $value;
                    }
                }

                $check = $this->validate_timesheet_item($it);

                // komplett leere Zeile -> ignorieren
                if ($check['empty']) {
                    continue;
                }
                $i++;
                // Fehler?
                if (!$check['valid']) {
                    $uuids[] = $it->x_uuid;
                    continue;
                }

                // speichern!
                $this->mdl_timesheets->update_timesheetline_by_uuid(
                        $userid,    $year,       $month, 
                        $it->x_day, $it->x_from, $it->x_to, 
                        $it->x_customer_id, $it->x_remark, $it->x_km, 
                        $it->x_type, $it->x_uuid
                        );
                $saved++;
            }
        }

        echo json_encode([
            'counter'      => $i,
            'saved'        => $saved,
            'failed'       => count($uuids),
            'failed_uuids' => $uuids,
            'message'      => 'save'
        ]);
    }
}
