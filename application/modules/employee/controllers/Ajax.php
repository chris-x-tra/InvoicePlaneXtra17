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
class Ajax extends Employee_Controller
{

    public $ajax_controller = true;

    public function check() 
    {
        $userid = $this->input->post('userid');
        $month = $this->input->post('month');
        $year = $this->input->post('year');
        $items = json_decode($this->input->post('items'));

        // debugging file write
        /*
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

        $i = 0;
        $correct = 0; 
        foreach ($items as $it) {

            // dynamisch erzeugte ids finden und umbauen
            foreach ($it as $key => $value) {
                if (strpos($key, 'x_customer-DYNAMIC') === 0) {
                    $it->x_customer_id = $value;
                }
            }

            if($it->x_customer_id != 0 || !empty($it->x_remark)) {
                $i++;
                // zeiten muessen beide was drin stehen
                if ( $it->x_from != "00:00" && $it->x_to != "00:00" ) {
                    $correct ++;
                }
            }
        }

        echo json_encode([
                'success' => $correct == $i,
                'successData' => [
                'correct' => $correct,
                'counter' => $i,
                'message' => "check"
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
                if($it->x_customer_id != 0 || !empty($it->x_remark)) {
                    // zeiten muessen beide was drin stehen
                    $i++;
                    if ( $it->x_from != "00:00" && $it->x_to != "00:00" ) {
                        $this->mdl_timesheets->update_timesheetline_by_uuid
                            ($userid, $year, $month, $it->x_day, $it->x_from,
                             $it->x_to, $it->x_customer_id, $it->x_remark,$it->x_km, $it->x_type, $it->x_uuid ) ;

                        $correct ++;
                    }
                }
            }
        }

        echo json_encode([
                'success' => $correct  == $i,
                'successData' => [
                'correct' => $correct,
                'counter' => $i,
                'message' => "save"
                ]
        ]);
}

// loescht kompletten monat und erstellt neu - use with caution
    public function save()
    {

        $this->load->model('timesheets/mdl_timesheets');

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

        $correct = 0;
        $i = 0;
        if($userid && $month && $year) {
            // at first delete all of current month
            $this->mdl_timesheets->delete_timesheet_month ($userid, $year, $month);

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
                if($it->x_customer_id != 0 || !empty($it->x_remark)) {
                    // zeiten muessen beide was drin stehen
                    $i++;
                    if ( $it->x_from != "00:00" && $it->x_to != "00:00" ) {
                        $this->mdl_timesheets->insert_timesheet_line 
                            ($userid, $year, $month, $it->x_day, $it->x_from, 
                             $it->x_to, $it->x_customer_id, $it->x_remark,$it->x_km, $it->x_type, $it->x_uuid ) ;
                        $correct ++;
                    }
                }
            }
        }

        echo json_encode([
                'success' => $correct  == $i,
                'successData' => [
                'correct' => $correct,
                'counter' => $i,
                'message' => "save"
                ]
        ]);
    }
}
