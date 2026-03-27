<?php
// $this->session->userdata('user_name')
// $this->session->userdata('user_email')
// $this->session->userdata('user_type')
$user_type = $this->session->userdata('user_type');

// get numbers of a month in gregorian calendar
if (!function_exists('days_month')) {
	function days_month($month, $year){
		return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
	}
}
if (!function_exists('my_td')) {
	function my_td($dow) {
		echo '<td';
		if ($dow=="Sa") echo ' bgcolor="#c0ff80" ';
		if ($dow=="So") echo ' bgcolor="#ff80c0" ';
		echo '>';
	}
}
if (!function_exists('do_dow')) {
	function do_dow($year, $month, $day) {
		// convert date to day of week
		$date = (string)$year."-".$month."-".$day;
		//Convert the date string into a unix timestamp.
		$unixTimestamp = strtotime($date);
		//Get the day of the week using PHP's date function.
		$dayOfWeek = date("D", $unixTimestamp);
		switch($dayOfWeek){
			case "Mon":
				$dow= "Mo";
				break;
			case "Tue":
				$dow= "Di";
				break;
			case "Wed":
				$dow= "Mi";
				break;
			case "Thu":
				$dow= "Do";
				break;
			case "Fri":
				$dow= "Fr";
				break;
			case "Sat":
				$dow= "Sa";
				break;
			case "Sun":
				$dow= "So";
				break;
			default:
				$dow= "--";
				break;
		}
		return $dow;
	}
}

if (!function_exists('get_worktype_verbose')) {
function get_worktype_verbose($code) {
$worktypes=[
    ['',    '---'],
        ['A',    'Arbeitstag'],
        ['B',    'B&uuml;rotag'],
        ['D',    'Kundenfahrt'],
        ['F',    'Feiertag'],
        ['K',    'Krank'],
        ['S',    '&Uuml;berstunden'],
        ['U',    'Urlaub'],
        ['UU',   'Unbezahlter Urlaub']
];
    foreach ($worktypes as $worktype) {
        if ($worktype[0] === $code) {
            return $worktype[1];
        }
    }
    return 'Unbekannter Typ'; // Falls kein Treffer gefunden wurde
}
}


?>
<div id="headerbar">

<?php _trans('timesheets_view'); 
 $this->load->helper('user_helper');
?>


<!--
    <div class="headerbar-item pull-right">
        <button type="button" class="btn btn-default btn-sm submenu-toggle hidden-lg"
                data-toggle="collapse" data-target="#ip-submenu-collapse">
            <i class="fa fa-bars"></i> <?php _trans('submenu'); ?>
        </button>
    </div>
-->

</div>

<div id="content" class="table-content">
<div class="col-xs-12 col-md-8">

<?php if ($user_type == 1): ?>
<!-- standard links -->
<a href="<?php echo site_url('timesheets/index/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('overview') ?></a>

<a href="<?php echo site_url('timesheets/form/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('timesheet_input') ?></a>

<a href="<?php echo site_url('timesheets/view/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('timesheets_view') ?></a>

<a href="<?php echo site_url('timesheets/print/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary" target="_blank">
<i class="fa fa-print"></i><?= trans('timesheets_print_view') ?></a>

<?php else: ?>

<a href="<?php echo site_url('employee/timesheets/index/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('overview') ?></a>

<a href="<?php echo site_url('employee/timesheets/form/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('timesheet_input') ?></a>

<a href="<?php echo site_url('employee/timesheets/view/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('timesheets_view') ?></a>
<!-- -->

<a href="<?php echo site_url('employee/timesheets/print/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary" target="_blank">
<i class="fa fa-print"></i><?= trans('timesheets_print_view') ?></a>

<?php endif; ?>


<!-- alerts -->
<br> <br>
<?php $this->layout->load_view('layout/alerts'); ?>


<!-- date, userinfo with change -->
<table ><tr><td style="padding: 10px 20px 0 0 ">
    <i class="fa fa-calendar" title=""></i>
    <span> <?= $month ?>.<?= $year ?></span>
<br>
<form method="post" action="<?php echo site_url($this->uri->uri_string()); ?>" >
        <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
        value="<?php echo $this->security->get_csrf_hash() ?>">

<!-- change date -->
        <select id="my_month" name="my_month">
        <?php  for($i=1; $i<=12; $i++) {
                echo '<option value="'.$i.'"';
                if ($month == $i) echo ' selected="selected" ';
                echo ' >'.$i.'</option>';
                echo "\n";
        } ?>
        </select>

        <select id="my_year" name="my_year">
        <?php  for ($y = date("Y"); $y >= date("Y")-5; $y--) {
                echo '<option value="'.$y.'"';
                if ($year == $y) echo ' selected="selected" ';
                echo ' >'.$y.'</option>';
                echo "\n";
        } ?>
        </select>

        <input type="submit" class="btn"  name="btn_submit_user" value="<?php _trans('change'); ?>">
</td><td>
   <!-- show user -->
<?php show_user($user->user_name, $user->user_email); ?>

<br>

<?php if ($user_type == 1): ?>

<!-- change user -->
    <select id="my_userid" name="my_userid">
    <?php 
        foreach ($users as $u) {
                echo '<option value="'.$u->user_id.'"';
                if ($user_id == $u->user_id) echo ' selected="selected" ';
                echo ">$u->user_name ($u->user_id)</option>";
                echo "\n";
        } ?>
        </select>

        <input type="submit" class="btn"  name="btn_submit_user" value="<?php _trans('change'); ?>">

<?php endif; ?>

</form>
</td></tr></table>
<!-- END userinfo with change -->


<br>

<!-- overview calculated times -->
<table style="width: 400px;" class="table-hover table-bordered no-margin">
<tr><td>Arbeit</td><td> <?= $ts_hm->summary_by_type['A']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>B&uuml;rotag</td><td> <?= $ts_hm->summary_by_type['B']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Kundenfahrt</td><td> <?= $ts_hm->summary_by_type['D']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>&Uuml;berstunden</td><td> <?= $ts_hm->summary_by_type['S']['hm'] ?? '00:00' ?></td></tr>
<tr><td>Krank</td><td> <?= $ts_hm->summary_by_type['K']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Feiertag</td><td> <?= $ts_hm->summary_by_type['F']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Urlaub</td><td> <?= $ts_hm->summary_by_type['U']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Unbezahlter Urlaub</td><td> <?= $rts_hm->summary_by_type['UU']['hm'] ?? '00:00' ?> </td></tr>
<tr><td></td><td></td></tr>
<tr><td>Arbeitszeit diesen Monat</td><td> <?=  $ts_hm->total_hm; ?></td></tr>
<tr><td></td><td></td></tr>
<tr><td>Gesamtkilometer diesen Monat</td><td> <?=  $ts_km; ?></td></tr>
</table>

<br>

<!-- detail table -->
<table id="x-worktime" class="table table-hover table-bordered table-condensed no-margin">
<?php
/***
 * main day loop
 */
for ($day_loop = 1; $day_loop <=days_month($month, $year); $day_loop++) {

    // U"berschrift immer am Anfang und sonst vor jedem Montag
    if ($day_loop == 1 || $dow == "Mon") {
        ?>
            <thead>
            <tr style="border-top: 2px solid #888;">
            <th style="width: 2em;"><?= trans('day'); ?></th>
            <th style="width: 10em;"><?= trans('type'); ?></th>
            <th style="width: 5em;"><?= trans('begin'); ?></th>
            <th style="width: 5em;"><?= trans('end'); ?></th>
            <th style="width: 3em;"><?= trans('hours'); ?></th>
            <th style="width: 3em;"><?= trans('sum'); ?></th>
            <th style="width: 25em;"><?= trans('client'); ?></th>
            <th style="width: 5em;"><?= trans('km'); ?></th>
            <th style="width: 15em;"><?= trans('remark'); ?></th>
            </tr>
            </thead>
<?php }
	$did_day = 0;

	// befuellte row mit vorhandenen daten
	//var_dump($ts_hm->entries);
	foreach ($ts_hm->entries as $t) {
		if ($t->timesheet_day == $day_loop) {
			if($did_day==0)
				echo '<tr style="border-top: 2px solid #888;">';
			else
				echo "<tr>";
			$dow = do_dow($day_loop, $month, $year);
			//my_td($dow);
			echo "<td>";
			echo $dow . ",&nbsp;". $day_loop .".";
			echo "</td><td>";
			echo get_worktype_verbose($t->timesheet_type);
			echo "</td><td>";
			echo date("H:i", strtotime($t->timesheet_start));
			echo "</td><td>";
			echo date("H:i", strtotime($t->timesheet_end));
			echo "</td><td>";
			echo date("H:i", strtotime($t->timesheet_duration_hm));
			echo "</td><td>";
			if($did_day==0) {
				foreach ($ts_hm->summary_by_day as $day => $dayData) {
					if ($day_loop == intval((date("d", strtotime($day)))))
						echo $dayData['hm'];
					}
			}
			echo "</td><td>";

	$cur_client = "";
	   foreach($user_clients as $u) {
	   if ($u->client_id == $t->timesheet_clientid) 
		$cur_client = $u->client_name ." ". $u->client_surname." <small>(".$u->customer_no.")</small>";
	}
	if  ($cur_client=="" && $t->timesheet_clientid > 0)
		$cur_client = $t->timesheet_clientid;
		echo $cur_client;

			echo "</td><td>";
			echo $t->timesheet_km;
			echo "</td><td>";
			echo $t->timesheet_remark;
			echo "</td>";

			echo "</tr>";
			$did_day++;
		} 
	}

	if($did_day==0) {
        	echo ' <tr id="tr' . $day_loop . '" style="border-top: 2px solid #888;">';
		$dow = do_dow($day_loop, $month, $year);
		//my_td($dow);
		echo "<td>";
		echo $dow . ",&nbsp;". $day_loop .".";
		echo "</td>";

		echo "<td>";
		echo "</td><td>";
		echo "</td><td>";
		echo "</td><td>";
		echo "</td><td>";
		echo "</td><td>";
		echo "</td><td>";
		echo "</td><td>";
		echo "</td>";
		echo "</tr>";
	}
	echo "\n";

}
?>
</table>
<br><br>
</div></div>
