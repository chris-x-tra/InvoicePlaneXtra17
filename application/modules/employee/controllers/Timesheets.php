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

//class Timesheets extends Admin_Controller
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

        //$this->load->model('custom_fields/mdl_custom_fields');
        //$this->load->model('custom_fields/mdl_client_custom');
        $this->load->helper('date_helper');
    }

    public function index($id = null, $month = null, $year = null)
    {

        //if (!$id) $id = $this->session->userdata('user_id');
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
            //if ($this->input->post('my_userid')) $id = $this->input->post('my_userid');
            // normaler user darf keine andere id angeben anderer user!
            $id = $this->session->userdata('user_id');
	    	$do_redir=1;
	}
	if ($year == null) { $year = date('Y'); $do_redir=1;}
	if ($month == null) { $month = intval(date('m')); $do_redir=1;}
	//if ($do_redir==1) redirect('timesheets/index/'.$id."/".$month."/".$year);
        if ($do_redir==1) redirect('employee/timesheets/index/'.$id."/".$month."/".$year);

        $ts_hm  = $this->mdl_timesheets->get_timesheet_hours_month ($id, $year, $month );
        $ts_wnws = $this->mdl_timesheets->get_monthly_work_nonwork_summary($id, $year, $month);
        $ts_km = $this->mdl_timesheets->get_monthly_km ($id, $year, $month);

        $ts_hm_all  = $this->mdl_timesheets->get_timesheet_hours_month (0, $year, $month );
        $ts_wnws_all = $this->mdl_timesheets->get_monthly_work_nonwork_summary(0, $year, $month);
        $ts_km_all = $this->mdl_timesheets->get_monthly_km (0, $year, $month);

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
		'ts_km_all' => $ts_km_all,
		'ts_hm_all' => $ts_hm_all,
		'ts_wnws_all' => $ts_wnws_all,

		'ts_km' => $ts_km,
		'ts_hm' => $ts_hm,
		'ts_wnws' => $ts_wnws,

		'user' => $user,
		'users' => $users,
		'user_id' => $id,
		'year' => $year,
		'month' => $month
	]);

        $this->layout->buffer('content', 'timesheets/index');
        //$this->layout->render();
        $this->layout->render('layout_employee');
    }


    public function view($id = null, $month = null, $year = null)
    {
        // ausgewaehlter user
        //if (!$id) $id = $this->session->userdata('user_id');
        // normaler user darf keine andere id angeben anderer user!
        $id = $this->session->userdata('user_id');

        $user = $this->mdl_users->get_by_id($id);
        $users = $this->mdl_users->where('user_active', 1)->get()->result();

	    $do_redir=0;
        if ($this->input->post('btn_submit_user') ) {
            // date from post override if set
            if ($this->input->post('my_year')) $year = $this->input->post('my_year');
            if ($this->input->post('my_month')) $month = $this->input->post('my_month');
            // normaler user darf keine andere id angeben anderer user!
            //if ($this->input->post('my_userid')) $id = $this->input->post('my_userid');
		$do_redir=1;
	}
	if ($year == null) { $year = date('Y'); $do_redir=1;}
	if ($month == null) { $month = intval(date('m')); $do_redir=1;}
	//if ($do_redir==1) redirect('timesheets/view/'.$id."/".$month."/".$year);
        if ($do_redir==1) redirect('employee/timesheets/view/'.$id."/".$month."/".$year);

        $month_name = $this->month_name($month);

        $uc =  $this->mdl_clients->order_by('ip_clients.client_name', 'ASC')->get()->result();

        // client_id customer_no client_surname client_name
        // speicher sparen, nicht alles an den view uebergeben
        $user_clients=[];
        foreach($uc as $u) {
            if(!empty($u->client_id)) { // there may be defect clients with no id in database - check how this happens
            $o =  new stdClass();
            $o->client_id = $u->client_id;
            $o->customer_no = $u->customer_no;
            $o->client_surname = $u->client_surname;
            $o->client_name = $u->client_name;
            $user_clients[]=$o;
            }
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
                'ts_km' => $ts_km
        ]);

        $this->layout->buffer('content', 'timesheets/view');
        //$this->layout->render();
        $this->layout->render('layout_employee');
    }

	/* print work time, die haelfte am anfang von view kopiert beizeiten fixxen
	*/
    private function __do_one_wt_print($id, $month, $year)
    {
        //$user = $this->mdl_users->get_by_id($id);
        //normaler user darf keine andere id angeben anderer user!
        $id = $this->session->userdata('user_id');

        $user_clients=[];
        $uc =  $this->mdl_clients ->order_by('ip_clients.client_name', 'ASC') ->get() ->result();

        // client_id customer_no client_surname client_name
        // speicher sparen, nicht alles an den view uebergeben
        foreach($uc as $u) {
            if(!empty($u->client_id)) { // there may be defect clients with no id in database - check how this happens
            $o =  new stdClass();
            $o->client_id = $u->client_id;
            $o->customer_no = $u->customer_no;
            $o->client_surname = $u->client_surname;
            $o->client_name = $u->client_name;
            $user_clients[]=$o;
            }
        }

        $ts_hm = $this->mdl_timesheets->get_timesheet_hours_month ($id, $year, $month );
        $ts_wnws = $this->mdl_timesheets->get_monthly_work_nonwork_summary($id, $year, $month);
        $ts_km = $this->mdl_timesheets->get_monthly_km ($id, $year, $month);

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

        //if(!$id || !$month || !$year) redirect('timesheets/index/');
        if(!$id || !$month || !$year) redirect('employee/timesheets/index/');
        $users = $this->mdl_users->where('user_active', 1)->get()->result();

        // normaler user darf nur seine sehen
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
        //redirect('timesheets/index');
        redirect('employee/timesheets/index');
    }



    /* view or enter work time
     * with ajax functions included
     */
    public function form($id = null, $month = null, $year = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('timesheets');
        }

        // ausgewaehlter user
        //if (!$id) $id = $this->session->userdata('user_id');
        // normaler user darf keine andere id angeben anderer user!
        $id = $this->session->userdata('user_id');

        $user = $this->mdl_users->get_by_id($id);
        //if (empty($user)) redirect('timesheets');
        if (empty($user)) redirect('employee/timesheets');
        $users = $this->mdl_users->where('user_active', 1)->get()->result();

        $do_redir=0;
        if ($this->input->post('btn_submit_user') ) {
            // date from post override if set
            if ($this->input->post('my_year')) $year = $this->input->post('my_year');
            if ($this->input->post('my_month')) $month = $this->input->post('my_month');
            // normaler user darf keine andere id angeben anderer user!
            //if ($this->input->post('my_userid')) $id = $this->input->post('my_userid');
            $do_redir=1;
        }
        if ($year == null) { $year = date('Y'); $do_redir=1;}
        if ($month == null) { $month = intval(date('m')); $do_redir=1;}
        if ($do_redir==1) redirect('timesheets/form/'.$id."/".$month."/".$year);

        $month_name = $this->month_name($month);

        // only assigned
        //$uc = $this->mdl_user_clients->assigned_to($id)->get()->result();

        // all
        // auch inaktive, da man alte nachsehen koennen muss
        //$uc =  $this->mdl_clients ->where('client_active', 1) ->order_by('ip_clients.client_name', 'ASC') ->get() ->result();
        $uc =  $this->mdl_clients ->order_by('ip_clients.client_name', 'ASC') ->get() ->result();


        // client_id customer_no client_surname client_name
        // speicher sparen, nicht alles an den view uebergeben
        $user_clients=[];
        foreach($uc as $u) {
            if(!empty($u->client_id)) { // there may be defect clients with no id in database - check how this happens
                $o = new stdClass();
                $o->client_id = $u->client_id;
                $o->customer_no = $u->customer_no;
                $o->client_surname = $u->client_surname;
                $o->client_name = $u->client_name;
                $user_clients[]=$o;
            }
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

        //if ($this->input->post('btn_change_user')) {
        //    $userid = $this->input->post('users');
        //    redirect('timesheets/evidence_print/'.$userid."/0");
        //}
        // normaler user darf keine andere id angeben anderer user!
        $id = $this->session->userdata('user_id');

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

                redirect('timesheets/index');

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
            redirect('timesheets/index');
        }
        // End submit
        //////

        $this->layout->buffer('content', 'timesheets/evidence_print');
        //$this->layout->render();
        $this->layout->render('layout_employee');
    }

}
