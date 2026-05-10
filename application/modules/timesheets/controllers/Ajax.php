<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author		InvoicePlane Developers & Contributors
 * @copyright	Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license		https://invoiceplane.com/license.txt
 * @link		https://invoiceplane.com
 */

/**
 * Class Ajax
 */
class Ajax extends Admin_Controller
{

    public $ajax_controller = true;

    public function check() 
    {
        $userid = $this->input->post('userid');
        $month = $this->input->post('month');
        $year = $this->input->post('year');
        $items = json_decode($this->input->post('items'));

        /*
        // debugging file write
        $myfile = fopen("/var/customers/webs/user52/maricare/application/modules/newfile.txt", "w") 
        or die("Unable to open file!");
        fwrite($myfile, "M:".$month .", Y:".$year.", U:".$userid."!\n");
        fwrite($myfile, print_r($items, true));
        fclose($myfile);
         */

        $required = [
            'userid' => $userid ?? null,
            'month' => $month ?? null,
            'year' => $year ?? null,
            'items' => $items ?? null
        ];

        $errors = [];

        foreach ($required as $key => $value) {
            if (empty($value)) {
                $errors[] = "no $key!";
            }
        }

        if (!empty($errors)) {
            echo json_encode([
                    'success' => 0,
                    'validation_errors' => $errors
            ]);
            exit;
        }

        // check each line if it is valid
        $i = 0;
        $correct = 0; 
        $uuids = [];
        foreach ($items as $it) {
            // DYNAMIC-Key-Mapping
            foreach ($it as $key => $value) {
                if (strpos($key, 'x_customer-DYNAMIC') === 0) {
                    $it->x_customer_id = $value;
                }
            }

            $type     = trim($it->x_type ?? '');      
            $from     = $it->x_from ?? '00:00';
            $to       = $it->x_to   ?? '00:00';
            $customer = (int)($it->x_customer_id ?? 0);
            $remark   = trim($it->x_remark ?? ''); 
            $uuid     = $it->x_uuid ?? null;

            if ($from !== '00:00') {  // from ist Pflicht
                $i++;
                if ($type === '' ) {
                    // type fehlt 
                    $uuids[] = $uuid;
                } elseif ($customer !== 0 || $remark !== '') {
                    // type ok + from ok + kunde oder remark correct
                    $correct++;
                } else {
                    // type ok + from ok, aber kein kunde und kein remark
                    if ($uuid !== null) {
                        $uuids[] = $uuid;
                    }
                }
            }
        }


        echo json_encode([
                'success' => $correct == $i,
                'successData' => [
                    'correct' => $correct,
                    'counter' => $i,
                    'message' => "check",
                    'failed_uuids' => $uuids
                ]
        ]);
    }


    public function set_delete()
    {
        $this->load->model('timesheets/mdl_timesheets');

        $userid = $this->input->post('userid');
        $uuid = $this->input->post('uuid');
        if($uuid)
            $this->mdl_timesheets->set_delete ($uuid);
    }


public function update_by_uuid() 
{
        $this->load->model('timesheets/mdl_timesheets');

        $userid = $this->input->post('userid');
        $month = $this->input->post('month');
        $year = $this->input->post('year');

        $items = json_decode($this->input->post('items'));

        $correct = 0;
        $i = 0;
        $uuids = [];
        if($userid && $month && $year) {

            // and then insert everything new because much can have changed
            foreach ($items as $it) {
                // kunde kann 0 sein bei neuem kunden dann steht er in bemerkung ...
                // es kann aber nie kunde und bemerkung leer sein

                // dynamisch erzeugte ids finden und umbauen
                foreach ($it as $key => $value) {
                    if (strpos($key, 'x_customer-DYNAMIC') === 0) {
                        $it->x_customer_id = $value;
                    }
                }

                if ($it->x_type!="" && ($it->x_from != "00:00" || $it->x_to != "00:00" )) {
                    $i++;
                    if($it->x_customer_id != 0 || !empty($it->x_remark)) {
                        $this->mdl_timesheets->update_timesheetline_by_uuid
                            ($userid, $year, $month, $it->x_day, $it->x_from,
                             $it->x_to, $it->x_customer_id, $it->x_remark, $it->x_km, $it->x_type, $it->x_uuid ) ;
                        $correct ++;
                    } else {
                        $uuids[]=$it->x_uuid;
                    }
                }
            }
        }

        echo json_encode([
                'success' => $correct  == $i,
                'successData' => [
                'correct' => $correct,
                'counter' => $i,
                'message' => "save",
                'failed_uuids' => $uuids
                ]
        ]);
}

}
