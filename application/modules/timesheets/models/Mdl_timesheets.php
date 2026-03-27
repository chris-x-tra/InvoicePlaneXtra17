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
 */
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
| timesheet_km            | int(11)                                      | YES  |     | NULL     |                |
| timesheet_type          | varchar(15)                                  | YES  |     | NULL     |                |
| timesheet_uuid          | varchar(36)                                  | YES  |     | NULL     |                |
| timesheet_timestamp     | datetime                                     | YES  |     | NULL     |                |
| timesheet_sync_status   | enum('synced','dirty')                       | YES  |     | dirty    |                |
| timesheet_change_status | enum('created','updated','deleted','synced') | YES  |     | created  |                |
+-------------------------+----------------------------------------------+------+-----+----------+----------------+

wichtig: wegen vorgekommenen 2-3fachen gleichen eintraegen
ALTER TABLE ip_timesheets ADD UNIQUE (`timesheet_uuid`);

*/

class Mdl_Timesheets extends Response_Model
{

	public $table = 'ip_timesheets';
	public $primary_key = 'ip_timesheets.id';

	function get_timesheet_month ($userid, $year, $month )
	{
		$t =  $this->mdl_timesheets
			->where('timesheet_userid' , $userid)
			->where('timesheet_month' , $month)
			->where('timesheet_year' , $year)
            ->where('timesheet_change_status !=', 'deleted')
			->order_by('timesheet_day', 'ASC')
			->order_by('timesheet_start', 'ASC')
			->get()
			->result();
		return $t;
	}

	function get_timesheet_month_with_deleted ($userid, $year, $month )
	{
		$t =  $this->mdl_timesheets
			->where('timesheet_userid' , $userid)
			->where('timesheet_month' , $month)
			->where('timesheet_year' , $year)
			->order_by('timesheet_day', 'ASC')
			->order_by('timesheet_start', 'ASC')
			->get()
			->result();
		return $t;
	}

    public function set_delete ($uuid)
    {
        $existing = $this->db->get_where('ip_timesheets', ['timesheet_uuid' => $uuid])->row_array();
        if ($existing) {
                $update = [
                    'timesheet_change_status' => 'deleted'
                ];
                $this->db->where('timesheet_uuid', $uuid);
                $this->db->update('ip_timesheets', $update);
                echo json_encode(['success' => true]);
        } 
    }

	function get_timesheet_hours_month($userid, $year, $month)
	{
		 $this->mdl_timesheets
			->where('timesheet_month', $month)
			->where('timesheet_year', $year)
            ->where('timesheet_change_status !=', 'deleted');

    if ($userid != 0) {
        $this->mdl_timesheets->where('timesheet_userid', $userid);
    }

    $this->mdl_timesheets
        ->order_by('timesheet_day', 'ASC')
        ->order_by('timesheet_start', 'ASC');

    $t= $this->mdl_timesheets
			->get()
			->result();

		$totalMinutes = 0;
		$typeSummary = []; // z. B. [ 'U' => 120, 'A' => 540, 'F' => 0 ]
		$dailySummary = []; // z. B. [ '2025-06-07' => 480 (Minuten), ... ]

		foreach ($t as $entry) {
			$minutes = 0;

			if ($entry->timesheet_start && $entry->timesheet_end) {
				$start = new DateTime($entry->timesheet_start);
				$end = new DateTime($entry->timesheet_end);

				if ($end < $start) {
					$end->modify('+1 day');
				}

				$interval = $start->diff($end);
				$minutes = ($interval->h * 60) + $interval->i;
			}

			// Speichern in Objekt für Einzelanzeige
			$entry->timesheet_duration_minutes = $minutes;
			$entry->timesheet_duration_hm = sprintf('%02d:%02d', floor($minutes / 60), $minutes % 60);
			$entry->timesheet_duration_hours = round($minutes / 60, 2);

			$totalMinutes += $minutes;

			// Typ-Zusammenfassung
			$type = $entry->timesheet_type ?? 'unknown';
			if (!isset($typeSummary[$type])) {
				$typeSummary[$type] = 0;
			}
			$typeSummary[$type] += $minutes;

			// Tages-Summe
			$dayKey = sprintf('%04d-%02d-%02d', $entry->timesheet_year, $entry->timesheet_month, $entry->timesheet_day);
			if (!isset($dailySummary[$dayKey])) {
				$dailySummary[$dayKey] = 0;
			}
			$dailySummary[$dayKey] += $minutes;
		}

		// Formatierte Typen-Zusammenfassung zusätzlich erzeugen
		$formattedTypeSummary = [];
		foreach ($typeSummary as $type => $minutes) {
			$formattedTypeSummary[$type] = [
				'minutes' => $minutes,
				'hours'   => round($minutes / 60, 2),
				'hm'      => sprintf('%02d:%02d', floor($minutes / 60), $minutes % 60)
			];
		}

		$formattedDailySummary = [];
		foreach ($dailySummary as $date => $minutes) {
			$formattedDailySummary[$date] = [
				'minutes' => $minutes,
				'hours'   => round($minutes / 60, 2),
				/*'hm'      => sprintf('%02d:%02d', floor($minutes / 60), $minutes % 60) */
				'hm'      => sprintf('%01d:%02d', floor($minutes / 60), $minutes % 60)
			];
		}

		// Ergebnisobjekt
		$result = new stdClass();
		$result->entries = $t;
		$result->total_minutes = $totalMinutes;
		$result->total_hours = round($totalMinutes / 60, 2);
		$result->total_hm = sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60);
		$result->summary_by_type = $formattedTypeSummary;
		$result->summary_by_day = $formattedDailySummary;

		return $result;
	}

public function get_monthly_km($userid, $year, $month)
{
    $this->db->select("SUM(timesheet_km) AS total_km");
    $this->db->from('ip_timesheets');
    if ($userid != 0) {
        $this->db->where('timesheet_userid', $userid);
    }
    $this->db->where('timesheet_year', $year);
    $this->db->where('timesheet_month', $month);
    $this->db->where('timesheet_change_status !=', 'deleted');
    $this->db->where('timesheet_km IS NOT NULL', null, false);

    $query = $this->db->get();
    $result = $query->row();
    return $result && $result->total_km !== null ? (float) $result->total_km : 0;
}

	public function get_monthly_work_summary($userid, $year, $month)
	{
		$this->db->select("
				timesheet_type,
				SUM(TIMESTAMPDIFF(MINUTE, timesheet_start, timesheet_end)) AS total_minutes,
				ROUND(SUM(TIMESTAMPDIFF(MINUTE, timesheet_start, timesheet_end)) / 60, 2) AS total_hours
				");

		$this->db->from('ip_timesheets');
    if ($userid != 0) {
		$this->db->where('timesheet_userid', $userid);
}
		$this->db->where('timesheet_year', $year);
		$this->db->where('timesheet_month', $month);
        $this->db->where('timesheet_change_status !=', 'deleted');
		$this->db->where('timesheet_start IS NOT NULL', null, false);
		$this->db->where('timesheet_end IS NOT NULL', null, false);
		$this->db->group_by('timesheet_type');

		$query = $this->db->get();
		return $query->result();
	}

	public function get_monthly_work_nonwork_summary($userid, $year, $month)
	{
		$this->db->from('ip_timesheets');
		$this->db->select("
				CASE 
				WHEN timesheet_type IN ('A', 'B', 'D') THEN 'work'
				ELSE 'non_work'
				END AS type_group,
				SUM(TIMESTAMPDIFF(MINUTE, timesheet_start, timesheet_end)) AS total_minutes,
				ROUND(SUM(TIMESTAMPDIFF(MINUTE, timesheet_start, timesheet_end)) / 60, 2) AS total_hours
				");

    if ($userid != 0) {
		$this->db->where('timesheet_userid', $userid);
    }
		$this->db->where('timesheet_month', $month);
		$this->db->where('timesheet_year', $year);
        $this->db->where('timesheet_change_status !=', 'deleted');
		$this->db->group_by('type_group');

		$query = $this->db->get();
		return $query->result();
	}

public function get_monthly_work_total($userid, $year, $month)
{
    $this->db->from('ip_timesheets');
    $this->db->select("
        SUM(TIMESTAMPDIFF(MINUTE, timesheet_start, timesheet_end)) AS total_minutes,
        ROUND(SUM(TIMESTAMPDIFF(MINUTE, timesheet_start, timesheet_end)) / 60, 2) AS total_hours
    ");

    if ($userid != 0) {
        $this->db->where('timesheet_userid', $userid);
    }

    $this->db->where('timesheet_month', $month);
    $this->db->where('timesheet_year', $year);
    $this->db->where('timesheet_change_status !=', 'deleted');
    $this->db->where('timesheet_start IS NOT NULL', null, false);
    $this->db->where('timesheet_end IS NOT NULL', null, false);

    $query = $this->db->get();
    $result = $query->row();

    return $result && $result->total_hours !== null ? (float) $result->total_hours : 0;
}


        public function insert_timesheet_line ($userid, $year, $month, $day, $start, $end, $clientid, $remark,$km, $type,
                $uuid="", $timestamp="", $change_status="" )
        {

            if(empty($timestamp))
                $timestamp = date('Y-m-d H:i:s');

            if (empty($change_status)) $change_status='created';

            $data = array (
                    'timesheet_userid' => $userid,
                    'timesheet_year' => $year,
                    'timesheet_month' => $month,
                    'timesheet_day' => $day,
                    'timesheet_start' => $start,
                    'timesheet_end' => $end,
                    'timesheet_clientid' => $clientid,
                    'timesheet_remark' => $remark,
                    'timesheet_km' => $km,
                    'timesheet_type' => $type,
                    'timesheet_uuid' => $uuid,
                    'timesheet_timestamp' => $timestamp,
                    'timesheet_change_status' => $change_status,
                    );
            $this->db->insert('ip_timesheets', $data);

            //log_message('debug', "***INSERT: " .  $this->db->last_query());

        }

        public function update_timesheetline_by_uuid (
                $userid, $year, $month, $day, $start, $end, $clientid, $remark, $km, $type, $uuid="", $timestamp="", $change_status="")
        {
$this->db->trans_start();
            $existing = $this->db->get_where('ip_timesheets', ['timesheet_uuid' => $uuid])->row_array();

            if(empty($timestamp))
                $timestamp = date('Y-m-d H:i:s');

            if ($existing) {
                $update = [
                    'timesheet_day' => $day,
                    'timesheet_month' => $month,
                    'timesheet_year' => $year,

                    'timesheet_clientid' => $clientid,
                    'timesheet_start' => $start,
                    'timesheet_end' => $end,
                    'timesheet_remark' => $remark,
                    'timesheet_km' => $km,
                    'timesheet_type' => $type,

                    'timesheet_timestamp' => $timestamp,
                    'timesheet_change_status' => 'updated'
                ];

                $this->db->where('timesheet_uuid', $uuid);
                $this->db->update('ip_timesheets', $update);

            }  else {
                // Insert neuer Eintrag
                $insert = [
                    'timesheet_uuid'      => $uuid,
                    'timesheet_userid'    => $userid,
                    'timesheet_day'       => $day,
                    'timesheet_month'     => $month,
                    'timesheet_year'      => $year,
                    'timesheet_clientid'  => $clientid,
                    'timesheet_start'     => $start,
                    'timesheet_end'       => $end,
                    'timesheet_remark'    => $remark,
                    'timesheet_type'      => $type,
                    'timesheet_km' => $km,
                    'timesheet_timestamp' => $timestamp,
                    'timesheet_change_status' => 'created'
                ];

                $this->db->insert('ip_timesheets', $insert);

                //log_message('debug', "###INSERT: " .  $this->db->last_query());
            }
$this->db->trans_complete();
        }

	/*  ganzen Monat lo"schen */
	public function delete_timesheet_month ($userid, $year, $month)
	{
		$this->db->where('timesheet_userid', $userid);
		$this->db->where('timesheet_year', $year);
		$this->db->where('timesheet_month', $month);
		$this->db->delete('ip_timesheets');
	}
}

