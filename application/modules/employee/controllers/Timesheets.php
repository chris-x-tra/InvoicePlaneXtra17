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

// https://codeigniter.com/userguide3/database/query_builder.html
/**
 * Class Timesheets
 */


use mikehaertl\pdftk\Pdf;

class Timesheets extends Employee_Controller
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
        return $this->monatsnamen[$n];
    }

    public function __construct()
    {
        parent::__construct();

        $this->load->model('timesheets/mdl_timesheets');
        $this->load->model('users/mdl_users');
        $this->load->model('clients/mdl_clients');
        $this->load->model('clients/mdl_client_extended');
        $this->load->model('user_clients/mdl_user_clients');

        $this->load->helper('date_helper');
    }

    public function index($id = null, $month = null, $year = null)
    {

    // normaler user darf keine andere id angeben anderer user!
        $id = $this->session->userdata('user_id');
        $user = $this->mdl_users->get_by_id($id);
        $u1 = $this->mdl_users->where('user_active', 1)->get()->result();

        // add work nonwork to users objects
        $users = [];
        foreach($u1 as $u) {
            $t1 = $this->mdl_timesheets->
                get_monthly_work_nonwork_summary($u->user_id, $year, $month);
            foreach($t1 as $t) {
                if($t->type_group=="non_work")
                    $u->non_work = $t->total_hours;
                if($t->type_group=="work")
                    $u->work = $t->total_hours;
            }
            $users[] = $u;
        }

	$do_redir=0;
        if ($this->input->post('btn_submit_user') ) {
            // date from post override if set
            if ($this->input->post('my_year')) $year = $this->input->post('my_year');
            if ($this->input->post('my_month')) $month = $this->input->post('my_month');
// normaler usr darf keine andere id angeben anderer user!
		$do_redir=1;
	}

	if ($year == null) { $year = date('Y'); $do_redir=1;}
	if ($month == null) { $month = intval(date('m')); $do_redir=1;}
	if ($do_redir==1) redirect('employee/timesheets/index/'.$id."/".$month."/".$year);

        $ts_hm  = $this->mdl_timesheets->get_timesheet_hours_month ($id, $year, $month );
        $ts_wnws = $this->mdl_timesheets->get_monthly_work_nonwork_summary($id, $year, $month);
$ts_km = $this->mdl_timesheets->get_monthly_km ($id, $year, $month);


    $fast_datea = date("Y");
    $fast_dateb = date("Y")-5;

for ($y = $fast_datea; $y >= $fast_dateb; $y--) {
for ($m=1; $m<=12; $m++) {
$fast_h[$y][$m] = $this->mdl_timesheets->get_monthly_work_total ($id, $y, $m );
}
}

        $this->layout->set([
    'fast_h' => $fast_h,
    'fast_datea' => $fast_datea,
  'fast_dateb' => $fast_dateb,

		'ts_km' => $ts_km,
		'ts_hm' => $ts_hm,
		'ts_wnws' => $ts_wnws,
		'user' => $user,
		'users' => $users,
		'user_id' => $id,
		'year' => $year,
		'month' => $month,
	]);

        //$this->layout->buffer('content', 'employee/timesheets_index');
        $this->layout->buffer('content', 'timesheets/index');
        $this->layout->render('layout_employee');
    }


    public function view($id = null, $month = null, $year = null)
    {

    // normaler user darf keine andere id angeben anderer user!
        $id = $this->session->userdata('user_id');
        $user = $this->mdl_users->get_by_id($id);
        $users = $this->mdl_users->where('user_active', 1)->get()->result();

	$do_redir=0;
        if ($this->input->post('btn_submit_user') ) {
            // date from post override if set
            if ($this->input->post('my_year')) $year = $this->input->post('my_year');
            if ($this->input->post('my_month')) $month = $this->input->post('my_month');
		$do_redir=1;
	}
	if ($year == null) { $year = date('Y'); $do_redir=1;}
	if ($month == null) { $month = intval(date('m')); $do_redir=1;}
	if ($do_redir==1) redirect('employee/timesheets/view/'.$id."/".$month."/".$year);

        $month_name = $this->month_name($month);

        $uc =  $this->mdl_clients ->order_by('ip_clients.client_name', 'ASC') ->get() ->result();
        // client_id customerno client_surname client_name
        // speicher sparen, nicht alles an den view uebergeben
        $user_clients=[];
        foreach($uc as $u) {
            $o =  $res = new stdClass();
            $o->client_id = $u->client_id;
            $o->customerno = $u->customerno;
            $o->client_surname = $u->client_surname;
            $o->client_name = $u->client_name;
            $user_clients[]=$o;
        }

        $ts_hm = $this->mdl_timesheets->get_timesheet_hours_month ($id, $year, $month );
        $ts_wnws = $this->mdl_timesheets->get_monthly_work_nonwork_summary($id, $year, $month);
$ts_km = $this->mdl_timesheets->get_monthly_km ($id, $year, $month);

        $this->layout->set([
                'month_name' => $this->month_name($month),
                'month' => $month,
                'all_month' => $this->monatsnamen,
                'year' => $year,
                'user_clients' => $user_clients,
                'user' => $user,
                'user_id' => $id,
                'users' => $users,
                'ts_hm' => $ts_hm,
                'ts_km' => $ts_km,
        ]);

        $this->layout->buffer('content', 'timesheets/view');
    $this->layout->render('layout_employee');
    }

	/* print work time, die haelfte am anfang von view kopiert beizeiten fixxen
	*/
    private function __do_one_wt_print($id, $month, $year)
    {
        // normaler user darf keine andere id angeben anderer user!
        $id = $this->session->userdata('user_id');
        $user = $this->mdl_users->get_by_id($id);
        $user_clients=[];
        $uc =  $this->mdl_clients ->order_by('ip_clients.client_name', 'ASC') ->get() ->result();

        // client_id customerno client_surname client_name
        // speicher sparen, nicht alles an den view uebergeben
        foreach($uc as $u) {
            $o =  $res = new stdClass();
            $o->client_id = $u->client_id;
            $o->customerno = $u->customerno;
            $o->client_surname = $u->client_surname;
            $o->client_name = $u->client_name;
            $user_clients[]=$o;
        }

        $ts_hm = $this->mdl_timesheets->get_timesheet_hours_month ($id, $year, $month );
$ts_km = $this->mdl_timesheets->get_monthly_km ($id, $year, $month);
        $ts_wnws = $this->mdl_timesheets->get_monthly_work_nonwork_summary($id, $year, $month);

        $data = [
            'month_name' => $this->month_name($month),
            'month' => $month,
            'all_month' => $this->monatsnamen,
            'year' => $year,
            'user_clients' => $user_clients,
            'user' => $user,
            'user_id' => $id,
            'ts_wnws' => $ts_wnws,
            'ts_hm' => $ts_hm,
            'ts_km' => $ts_km
        ];

        $html = $this->load->view('timesheets/print', $data, true);

        //var_dump($html);die();
        $this->load->helper('mpdf');
        $my_r = pdf_create($html, "timesheet-month-".$id."-".$month."-".$year,
                false, null, null, null, false, null, false, false, 1);
        return $my_r;
    }

    public function print($id = null, $month = null, $year = null, $all=false)
    {
        // normaler user darf keine andere id angeben anderer user!
        $id = $this->session->userdata('user_id');
        if(!$id || !$month || !$year) redirect('employee/timesheets/index/');
        $users = $this->mdl_users->where('user_active', 1)->get()->result();

        // normaler user darf nur seie sehen
    $all = false;
        if ($all=='all') {
            $pdfs=[];
            foreach($users as $user) {
                $summary = $this->mdl_timesheets->get_monthly_work_nonwork_summary($user->user_id, $year, $month);
                if(!empty($summary)) {
                    $my_r = $this->__do_one_wt_print($user->user_id, $month, $year);
                    $pdfs[]=$my_r;
                }
            }

            $combined = UPLOADS_TEMP_FOLDER . "timesheet-month-ALL-".$month.'-'.$year.'"';

            $pdf = new Pdf();
            foreach ($pdfs as $f) {
                $pdf->addFile($f);
            }

            $pdf->saveAs($combined);

            // TODO unlink single pdfs

            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="timesheet-month-ALL-'.$month.'-'.$year.'"');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
            @readfile ($combined);

        } else {
            $my_r = $this->__do_one_wt_print($id, $month, $year);
            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="timesheet-month-'.$id.'-'.$month.'-'.$year.'"');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
            @readfile ($my_r);
        }
        redirect('employee/timesheets/index');
    }



    /* view or enter work time
    * with ajax functions included
    */
    public function form($id = null, $month = null, $year = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('employee/timesheets');
        }


        // normaler user darf keine andere id angeben anderer user!
        $id = $this->session->userdata('user_id');
        $user = $this->mdl_users->get_by_id($id);
        if (empty($user)) redirect('employee/timesheets');
        $users = $this->mdl_users->where('user_active', 1)->get()->result();

        $do_redir=0;
        if ($this->input->post('btn_submit_user') ) {
            // date from post override if set
            if ($this->input->post('my_year')) $year = $this->input->post('my_year');
            if ($this->input->post('my_month')) $month = $this->input->post('my_month');
            $do_redir=1;
        }
        if ($year == null) { $year = date('Y'); $do_redir=1;}
        if ($month == null) { $month = intval(date('m')); $do_redir=1;}
        if ($do_redir==1) redirect('employee/timesheets/form/'.$id."/".$month."/".$year);

        $month_name = $this->month_name($month);

        // only assigned
        //$uc = $this->mdl_user_clients->assigned_to($id)->get()->result();

        // all
        // auch inaktive, da man alte nachsehen coennen muss
        //$uc =  $this->mdl_clients ->where('client_active', 1) ->order_by('ip_clients.client_name', 'ASC') ->get() ->result();
        $uc =  $this->mdl_clients ->order_by('ip_clients.client_name', 'ASC') ->get() ->result();

        // client_id customerno client_surname client_name
        // speicher sparen, nicht alles an den view uebergeben
        $user_clients=[];
        foreach($uc as $u) {
            $o =  $res = new stdClass();
            $o->client_id = $u->client_id;
            $o->customerno = $u->customerno;
            $o->client_surname = $u->client_surname;
            $o->client_name = $u->client_name;
            $user_clients[]=$o;
        }

        $timesheets = $this->mdl_timesheets->get_timesheet_month ($id, $year, $month );

        $this->layout->set([
                'month_name' => $this->month_name($month),
                'month' => $month,
                'all_month' => $this->monatsnamen,
                'year' => $year,
                'user_clients' => $user_clients,
                'user' => $user,
                'user_id' => $id,
                'users' => $users,
                'timesheets' => $timesheets,
        ]);

        $this->layout->buffer('content', 'timesheets/form');
    $this->layout->render('layout_employee');
    }

    public function ajax_upload() 
    {
        $this->load->helper('cors_helper');
        cors();
        $user = $this->verifyJWT(); // prueft Token

        // JSON-POST-Daten manuell lesen, axios schickt als json:
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        // Zugriff:
        $time_data=$data['data'] ?? '';
        $userEmail=$data['userEmail']??'';
        $year=$data['selectedYear']??'';
        $month=$data ['selectedMonth']??'';

        log_message('debug', "userdata: |".$userEmail."|".$year."|".$month);
        if(empty($time_data) || empty($userEmail) || empty ($year) || empty ($month)) {
            echo json_encode([ 'status' => 'empty']); exit(0);
        }
        $this->load->model('users/mdl_users');
        $this->db->where('user_email', $userEmail);
        $query = $this->db->get('ip_users');
        $user = $query->row();

        log_message('debug', "userdata|".$user->user_email."|".$user->user_id);
        // Check if the user exists
        if (empty($user)) {
            echo json_encode([ 'status' => 'empty']); exit(0);
        }
        $userid=$user->user_id;

        //
        // los gehts alte deleten neue speichern - siehe auch Ajax Controller - da ist fast das gleiche
        $this->mdl_timesheets->delete_timesheet_month ($userid, $year, $month);

        // and then insert everything new because much can have changed
        /*
           log_message('debug', 'JSON POST DATA: ' . print_r($data, true));
           [data] => Array (
           [0] => Array (
           [clientId] => 1
           [clientName] => Bräunlich Christian
           [date] => 2025-06-19
           [startTime] => 14:00
           [endTime] => 15:00
           [type] => A
           [remark] => 
           [timestamp] => 2025-06-19T12:04:09.958Z
           [id] => a7e94a79-e705-482c-a4a3-70ddef710f7c
           )
           )
         */

        $i=0; $correct=0;
        foreach ($data['data'] as $key => $it) {
            // kunde kann 0 sein bei neuem kunden dann steht er in bemerkung ...
            // es kann aber nie kunde und bemerkung leer sein

           log_message('debug', 'it: ' . print_r($it, true));
           log_message('debug', '----');

            if($it['clientId'] != 0 || !empty($it['remark'])) {
                // zeiten muessen beide was drin stehen
                $i++;
                if ( $it['startTime'] != "00:00" && $it['endTime'] != "00:00" ) {

                list($year, $month, $day)=explode('-', $it['date']);

                    $this->mdl_timesheets->insert_timesheet_line
                        ($userid, $year, $month, $day, $it['startTime'],
                         $it['endTime'], $it['clientId'], $it['remark'], $it['type'] ) ;
                    $correct ++;
                }
            }
        }

        echo json_encode([
                'success' => $correct == $i,
                'successData' => [
                'correct' => $correct,
                'counter' => $i,
                'message' => "save"
                ]
        ]);
    }

}
