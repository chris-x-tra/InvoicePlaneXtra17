<?php die("check_if_needed!"); ?>
<!DOCTYPE html>
<html lang="<?php echo trans('cldr'); ?>">
<head>
    <title><?php echo trans('thimesheet'); ?></title>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/<?php echo get_setting('system_theme', 'invoiceplane'); ?>/css/timesheet.css" type="text/css">
</head>
<body>

<?php
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
	['A',    'Arbeitstag'],
	['B',    'B&uuml;rotag'],
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

    <h3 class="headerbar-title"><?php _trans('worktime'); ?> <?= $month ?>.<?= $year ?>
    </h3>
    <?= $this->session->userdata('user_name') ?> (<?= $this->session->userdata('user_email') ?>

<br>

<br>

<!-- overview calculated times -->
<table style="width: 400px;" class="asdf-solid">
<tr><td>Arbeit</td><td> <?= $ts_hm->summary_by_type['A']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>B&uuml;rotag</td><td> <?= $ts_hm->summary_by_type['B']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>&Uuml;berstunden</td><td> <?= $ts_hm->summary_by_type['S']['hm'] ?? '00:00' ?></td></tr>
<tr><td>Krank</td><td> <?= $ts_hm->summary_by_type['K']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Feiertag</td><td> <?= $ts_hm->summary_by_type['F']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Urlaub</td><td> <?= $ts_hm->summary_by_type['U']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Unbezahlter Urlaub</td><td> <?= $rts_hm->summary_by_type['UU']['hm'] ?? '00:00' ?> </td></tr>
<tr><td></td><td></td></tr>
<tr><td>Arbeitszeit diesen Monat</td><td> <?=  $ts_hm->total_hm; ?></td></tr>
</table>

<br>

<style>
 table.xsolid tr td {border: 1px dotted #888;}
</style>

<!-- detail table -->
<table  class="xsolid">
<?php
/***
 * main day loop
 */
for ($day_loop = 1; $day_loop <=days_month($month, $year); $day_loop++) {

    // U"berschrift immer am Anfang und sonst vor jedem Montag
    if ($day_loop == 1 || $dow == "Mon") {
        ?>
            <thead>
            <tr style="border-top: 2px solid;">
            <th style="width: 2em;"><?= trans('day'); ?></th>
            <th style="width: 10em;"><?= trans('type'); ?></th>
            <th style="width: 5em;"><?= trans('begin'); ?></th>
            <th style="width: 5em;"><?= trans('end'); ?></th>
            <th style="width: 3em;"><?= trans('hours'); ?></th>
            <th style="width: 3em;"><?= trans('sum'); ?></th>
            <th style="width: 25em;"><?= trans('client'); ?></th>
            <th style="width: 15em;"><?= trans('remark'); ?></th>
            </tr>
            </thead>
<?php }
	$did_day = 0;

	// befuellte row mit vorhandenen daten
	//var_dump($ts_hm->entries);
	foreach ($ts_hm->entries as $t) {
		if ($t->timesheet_day == $day_loop) {
echo "\n";
			if($did_day==0)
				echo '<tr style="border-top: 2px solid;">';
			else
				echo '<tr >';
			$dow = do_dow($day_loop, $month, $year);
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
		$cur_client = $u->client_name ." ". $u->client_surname." <small>(".$u->customerno.")</small>";
	}
	if  ($cur_client=="" && $t->timesheet_clientid > 0)
		$cur_client = $t->timesheet_clientid;
		echo $cur_client;

			echo "</td><td>";
			echo $t->timesheet_remark;
			echo "</td>";

			echo "</tr>";
			$did_day++;
		} 
	}

	if($did_day==0) {
        	echo ' <tr id="tr' . $day_loop . '" style="border-top: 2px solid;">';
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
		echo "</td>";
		echo "</tr>";
	}
	echo "\n";

}
?>
</table>
</body></html>
