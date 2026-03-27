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
        return $this->monatsnamen[$n];
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

    public function index($id = null, $month = null, $year = null)
    {

        if (!$id) $id = $this->session->userdata('user_id');
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
            if ($this->input->post('my_userid')) $id = $this->input->post('my_userid');
		$do_redir=1;
	}
	if ($year == null) { $year = date('Y'); $do_redir=1;}
	if ($month == null) { $month = intval(date('m')); $do_redir=1;}
	if ($do_redir==1) redirect('timesheets/index/'.$id."/".$month."/".$year);

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
        $this->layout->render();
    }


    public function view($id = null, $month = null, $year = null)
    {
        // ausgewaehlter user
        if (!$id) $id = $this->session->userdata('user_id');
        $user = $this->mdl_users->get_by_id($id);
        $users = $this->mdl_users->where('user_active', 1)->get()->result();

	    $do_redir=0;
        if ($this->input->post('btn_submit_user') ) {
            // date from post override if set
            if ($this->input->post('my_year')) $year = $this->input->post('my_year');
            if ($this->input->post('my_month')) $month = $this->input->post('my_month');
            if ($this->input->post('my_userid')) $id = $this->input->post('my_userid');
		$do_redir=1;
	}
	if ($year == null) { $year = date('Y'); $do_redir=1;}
	if ($month == null) { $month = intval(date('m')); $do_redir=1;}
	if ($do_redir==1) redirect('timesheets/view/'.$id."/".$month."/".$year);

        $month_name = $this->month_name($month);

        $uc =  $this->mdl_clients ->order_by('ip_clients.client_name', 'ASC') ->get() ->result();
        // client_id customer_no client_surname client_name
        // speicher sparen, nicht alles an den view uebergeben
        $user_clients=[];
        foreach($uc as $u) {
            $o =  $res = new stdClass();
            $o->client_id = $u->client_id;
            $o->customer_no = $u->customer_no;
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
                'ts_km' => $ts_km
        ]);

        $this->layout->buffer('content', 'timesheets/view');
        $this->layout->render();
    }

	/* print work time, die haelfte am anfang von view kopiert beizeiten fixxen
	*/
    private function __do_one_wt_print($id, $month, $year)
    {
        $user = $this->mdl_users->get_by_id($id);
        $user_clients=[];
        $uc =  $this->mdl_clients ->order_by('ip_clients.client_name', 'ASC') ->get() ->result();

        // client_id customer_no client_surname client_name
        // speicher sparen, nicht alles an den view uebergeben
        foreach($uc as $u) {
            $o =  $res = new stdClass();
            $o->client_id = $u->client_id;
            $o->customer_no = $u->customer_no;
            $o->client_surname = $u->client_surname;
            $o->client_name = $u->client_name;
            $user_clients[]=$o;
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
        if(!$id || !$month || !$year) redirect('timesheets/index/');
        $users = $this->mdl_users->where('user_active', 1)->get()->result();

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
        redirect('timesheets/index');
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
        if (!$id) $id = $this->session->userdata('user_id');
        $user = $this->mdl_users->get_by_id($id);
        if (empty($user)) redirect('timesheets');
        $users = $this->mdl_users->where('user_active', 1)->get()->result();

        $do_redir=0;
        if ($this->input->post('btn_submit_user') ) {
            // date from post override if set
            if ($this->input->post('my_year')) $year = $this->input->post('my_year');
            if ($this->input->post('my_month')) $month = $this->input->post('my_month');
            if ($this->input->post('my_userid')) $id = $this->input->post('my_userid');
            $do_redir=1;
        }
        if ($year == null) { $year = date('Y'); $do_redir=1;}
        if ($month == null) { $month = intval(date('m')); $do_redir=1;}
        if ($do_redir==1) redirect('timesheets/form/'.$id."/".$month."/".$year);

        $month_name = $this->month_name($month);

        // only assigned
        //$uc = $this->mdl_user_clients->assigned_to($id)->get()->result();

        // all
        // auch inaktive, da man alte nachsehen coennen muss
        //$uc =  $this->mdl_clients ->where('client_active', 1) ->order_by('ip_clients.client_name', 'ASC') ->get() ->result();
        $uc =  $this->mdl_clients ->order_by('ip_clients.client_name', 'ASC') ->get() ->result();

        // client_id customer_no client_surname client_name
        // speicher sparen, nicht alles an den view uebergeben
        $user_clients=[];
        foreach($uc as $u) {
            $o =  $res = new stdClass();
            $o->client_id = $u->client_id;
            $o->customer_no = $u->customer_no;
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
        $this->layout->render();
    }

    /*
    | timesheet_uuid          | varchar(36)   
    | timestamp               | datetime     
    | timesheet_sync_status   | enum('synced','dirty')
    | timesheet_change_status | enum('created','updated','deleted','synced')
    */
    public function api_create() 
    {
        $this->load->helper('cors_helper');
        cors();
        $user_jwt = $this->verifyJWT(); // prueft Token

        $data = json_decode($this->input->raw_input_stream, true);

        $time_data=$data['data'] ?? '';
        $userEmail=$data['userEmail']??'';
        $year=$data['selectedYear']??'';
        $month=$data ['selectedMonth']??'';

        //log_message('debug', "userdata: |".$userEmail."|".$year."|".$month);
        if(empty($time_data) || empty($userEmail) || empty ($year) || empty ($month)) {
            echo json_encode([ 'status' => 'empty']); exit(0);
        }
        $this->load->model('users/mdl_users');
        $this->db->where('user_email', $userEmail);
        $query = $this->db->get('ip_users');
        $user = $query->row();

        //log_message('debug', "userdata|".$user->user_email."|".$user->user_id);
        //Check if the user exists - already done via verifyJWT - todo cleanup
        if (empty($user)) {
            echo json_encode([ 'status' => 'empty']); exit(0);
        }
        $userid=$user->user_id;

        $data = $data['data'];  // !!!

        //log_message('debug', "### inserting timedata data: |".$data['startTime'].$data['endTime'].$data['clientId'].$data['id']);
        list($year, $month, $day)=explode('-', $data['date']);
        $this->mdl_timesheets->insert_timesheet_line
            ($userid, $year, $month, $day, $data['startTime'],
             $data['endTime'], $data['clientId'], $data['remark'], $data['km'], $data['type'], 
             $data['id'],   // uuid
             $data['timestamp'],   // timestamp
             $data['changeStatus']   // status beaucse of sync
        );
        echo json_encode(['success' => true]);
    }

    /* workz */
    public function api_update() 
    {
        $this->load->helper('cors_helper');
        cors();
        $user_jwt = $this->verifyJWT(); // prueft Token

        $data = json_decode($this->input->raw_input_stream, true);

        $time_data=$data['data'] ?? '';
        $userEmail=$data['userEmail']??'';
        $year=$data['selectedYear']??'';
        $month=$data ['selectedMonth']??'';

        //log_message('debug', "userdata: |".$userEmail."|".$year."|".$month);
        if(empty($time_data) || empty($userEmail) || empty ($year) || empty ($month)) {
            echo json_encode([ 'status' => 'empty']); exit(0);
        }
        $this->load->model('users/mdl_users');
        $this->db->where('user_email', $userEmail);
        $query = $this->db->get('ip_users');
        $user = $query->row();

        //log_message('debug', "userdata|".$user->user_email."|".$user->user_id);
        //Check if the user exists - already done via verifyJWT - todo cleanup
        if (empty($user)) {
            echo json_encode([ 'status' => 'empty']); exit(0);
        }
        $userid=$user->user_id;

        $data = $data['data'];  // !!!

        #log_message('debug', "### updating timedata data: |".$data['timestamp']."|".$data['startTime'].$data['endTime'].$data['clientId'].$data['id']);
        list($year, $month, $day)=explode('-', $data['date']);

        $existing = $this->db->get_where('ip_timesheets', ['timesheet_uuid' => $data['id']])->row_array();

        if ($existing) {
                $update = [
                    'timesheet_day' => $day,
                    'timesheet_month' => $month,
                    'timesheet_year' => $year,

                    'timesheet_clientid' => $data['clientId'],
                    'timesheet_start' => $data['startTime'],
                    'timesheet_end' => $data['endTime'],
                    'timesheet_remark' => $data['remark'],
                    'timesheet_type' => $data['type'],

                    'timesheet_timestamp' => $data['timestamp']
                ];

                    $this->db->where('timesheet_uuid', $data['id']);
                    $this->db->update('ip_timesheets', $update);
                    echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'reason' => 'not_found']);
        }
    }


// delete darf nicht im function namen vorkommen sonst 404
public function api_remove()
{
        $this->load->helper('cors_helper');
        cors();
        $user_jwt = $this->verifyJWT(); // prueft Token

        $data = json_decode($this->input->raw_input_stream, true);

        $time_data=$data['data'] ?? '';
        $userEmail=$data['userEmail']??'';
        $year=$data['selectedYear']??'';
        $month=$data ['selectedMonth']??'';

        //log_message('debug', "userdata: |".$userEmail."|".$year."|".$month);
        if(empty($time_data) || empty($userEmail) || empty ($year) || empty ($month)) {
            echo json_encode([ 'status' => 'empty']); exit(0);
        }
        $this->load->model('users/mdl_users');
        $this->db->where('user_email', $userEmail);
        $query = $this->db->get('ip_users');
        $user = $query->row();

        //log_message('debug', "userdata|".$user->user_email."|".$user->user_id);
        //Check if the user exists - already done via verifyJWT - todo cleanup
        if (empty($user)) {
            echo json_encode([ 'status' => 'empty']); exit(0);
        }
        $userid=$user->user_id;

        $data = $data['data'];  // !!!

        //log_message('debug', "### updating timedata data: |".$data['timestamp']."|".$data['startTime'].$data['endTime'].$data['clientId'].$data['id']);
        list($year, $month, $day)=explode('-', $data['date']);

        $existing = $this->db->get_where('ip_timesheets', ['timesheet_uuid' => $data['id']])->row_array();
        if ($existing) {
            $this->db->delete('ip_timesheets', ['timesheet_uuid' => $data['id']]);
        }
        echo json_encode(['success' => true, 'reason' => 'deleted']);
}

public function api_fetch_dirty() 
{
        $this->load->helper('cors_helper');
        cors();
        $user_jwt = $this->verifyJWT(); // prueft Token

        $data = json_decode($this->input->raw_input_stream, true);

        $userEmail=$data['userEmail']??'';
        $year=$data['selectedYear']??'';
        $month=$data ['selectedMonth']??'';
     
        //log_message('debug', "userdata: |".$userEmail."|".$year."|".$month);
        if( empty($userEmail) || empty ($year) || empty ($month)) {
            echo json_encode([ 'status' => 'empty']); exit(0);
        }
        $this->load->model('users/mdl_users');
        $this->db->where('user_email', $userEmail);
        $query = $this->db->get('ip_users');
        $user = $query->row();

        //log_message('debug', "userdata|".$user->user_email."|".$user->user_id);
        //Check if the user exists - already done via verifyJWT - todo cleanup
        if (empty($user)) {
            echo json_encode([ 'status' => 'empty']); exit(0);
        }
        $userid=$user->user_id;

// wichtich hier with deleted!!!
        $timesheets = $this->mdl_timesheets->get_timesheet_month_with_deleted ($userid, $year, $month );

/*
+-------------------------+----------------------------------------------+------+-----+----------+----------------+
| Field                   | Type                                         | Null | Key | Default  | Extra          |
+-------------------------+----------------------------------------------+------+-----+----------+----------------+
| id                      | int(11)                                      | NO   | PRI | NULL     | auto_increment |
| timesheet_userid        | int(11)                                      | YES  |     | NULL     |                |
| timesheet_day           | int(11)                                      | YES  |     | NULL     |                |
| timesheet_month         | int(11)                                      | YES  |     | NULL     |                |
| timesheet_year          | int(11)                                      | YES  |     | NULL     |                |
| timesheet_start         | time                                         | NO   |     | 00:00:00 |                |
| timesheet_end           | time                                         | NO   |     | 00:00:00 |                |
| timesheet_clientid      | int(11)                                      | YES  |     | NULL     |                |
| timesheet_remark        | varchar(255)                                 | YES  |     | NULL     |                |
| timesheet_km ...
| timesheet_type          | varchar(15)                                  | YES  |     | NULL     |                |
| timesheet_uuid          | varchar(36)                                  | YES  |     | NULL     |                |
| timesheet_timestamp     | datetime                                     | YES  |     | NULL     |                |
| timesheet_sync_status   | enum('synced','dirty')                       | YES  |     | dirty    |                |
| timesheet_change_status | enum('created','updated','deleted','synced') | YES  |     | NULL     |                |
+-------------------------+----------------------------------------------+------+-----+----------+----------------+
*/
    $tresult = [];
    foreach ($timesheets as $row) {
        $tresult[] = [
            'id'          => $row->timesheet_uuid,  // deine UUID aus der DB
            'clientId'    => $row->timesheet_clientid,
            'date'        => $row->timesheet_year . "-".$row->timesheet_month."-".$row->timesheet_day,         // z. B. '2025-06-18'
            'startTime'   => $row->timesheet_start,        // '09:00'
            'endTime'     => $row->timesheet_end,          // '17:00'
            'type'        => $row->timesheet_type,         // 'A' o.ä.
            'remark'      => $row->timesheet_remark,
            'km'          => $row->timesheet_km,
            'timestamp'   => $row->timesheet_timestamp,        // z. B. '2025-06-18T08:00:00Z'
            'changeStatus'=> $row->timesheet_change_status  // kann auch delted sein
        ];
        //log_message('debug', "### row: |".$row->timesheet_uuid);
    }

    //log_message('debug', "### tresult: |".implode(" ",$tresult));

    $response = [
        'success' => true,
        'data'    => $tresult
    ];
    echo json_encode($response);
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
            redirect('timesheets/evidence_print/'.$userid."/0");
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
        $this->layout->render();
    }

}
