<!DOCTYPE html>
<html lang="<?php echo trans('cldr'); ?>">
<head>
    <title><?php echo trans('thimesheet'); ?></title>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/<?php echo get_setting('system_theme', 'invoiceplane'); ?>/css/timesheet.css" type="text/css">
</head>
<body>
<h3 class="report_title"><?php echo trans('leistungsnachweis'); ?></h3>
<small><?= _trans('client'); ?>:&nbsp;<?= $customer_no; ?> <br /></small>
<?= $client_fullname; ?> <br />
<?= $client_street; ?> <br />
<?= $client_zip; ?> <?= $client_city; ?> <br />
<br />
<?= _trans('month'); ?>: <?= $month_name; ?> <?= $year; ?><br />
<br />
<table class="table-bordered">
    <tr>
        <th><?= trans('day'); ?></th>
        <th style="width: 5em;"><?= trans('begin'); ?></th>
        <th style="width: 5em;"><?= trans('end'); ?></th>
        <th style="width: 3em;"><?= trans('hours'); ?></th>
        <th style="width: 15em;"><?= trans('user_signature'); ?></th>
        <th style="width: 15em;"><?= trans('client_signature'); ?></th>
    </tr>
<?php 
// get numbers of a month in gregorian calendar
if (!function_exists('days_month')) {
function days_month($month, $year){
    return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
} 
}

if (!function_exists('my_td')) {
function my_td($bold=0) {
	echo '<td'; 
	if ($bold != 0) { echo ' style="border-bottom: #000 2px solid ;"' ;} 
	echo '>';
}
}

$number = days_month($month, $year); 

for ($i=1; $i<=$number; $i++) { 
// convert date to day of week
$date = (string)$year."-".$month."-".$i;
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
?>
		<tr>
		<?php my_td($dow=="So"); ?>
			<?php echo $dow . ",&nbsp;". $i ."."; ?>
		</td>
		<?php my_td($dow=="So"); ?>
			&nbsp;
		</td>
		<?php my_td($dow=="So"); ?>
			&nbsp;
		</td>
		<?php my_td($dow=="So"); ?>
			&nbsp;
		</td>
		<?php my_td($dow=="So"); ?>
			&nbsp;
		</td>
		<?php my_td($dow=="So"); ?>
			&nbsp;
		</td>
        </tr>
<?php } 
?>
</table>

<br />

<small>
Marina Walter 
&middot;
Marishine Alltagshilfe
&middot;
Gartenstra&szlig;e 3
&middot;
86498 Kettershausen
&middot;
0160/8520439
&middot;
info@marishine.de
</small>
</body>
</html>
