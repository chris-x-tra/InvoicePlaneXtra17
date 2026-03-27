<?php 
// $this->session->userdata('user_name')
// $this->session->userdata('user_email')
// $this->session->userdata('user_type')
$user_type = $this->session->userdata('user_type');
?>
<script>
function drawDonut(value1, value2) {
    
    var size = 50; //canvas muss 2x size sein

    // Farben
    var colors = ["#c0ff80", "#ff80c0"];
    
    // Canvas Setup
    var canvas = document.getElementById('donutChart');
    var ctx = canvas.getContext('2d');
    var total = value1 + value2;
    var startAngle = 0;

    // Werte Array
    var values = [value1, value2];

    // Donut zeichnen
    for(var i=0; i<values.length; i++) {
        var sliceAngle = 2 * Math.PI * values[i] / total;
        
        // Slice
        ctx.beginPath();
        ctx.moveTo(size, size); // Mittelpunkt 

	// size - 10 ausserer kreis groesse
        ctx.arc(size, size, size-10, startAngle, startAngle + sliceAngle);	
        ctx.closePath();
        ctx.fillStyle = colors[i];
        ctx.fill();
        
        startAngle += sliceAngle;
    }
    
    // Innerer Kreis für Donut
    ctx.beginPath();

    // size / 3 innerer kreis
    ctx.arc(size, size, size/3, 0, 2 * Math.PI);	
    ctx.fillStyle = '#f9f9f9';
    ctx.fill();
}
</script>

<div id="headerbar">
<?php _trans('timesheets');
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

<!-- -->
<?php if ($user_type == 1): ?>

<a href="<?php echo site_url('timesheets/index/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('overview') ?></a>

<a href="<?php echo site_url('timesheets/form/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('timesheet_input') ?></a>

<a href="<?php echo site_url('timesheets/view/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('timesheets_view') ?></a>
<!-- -->

<a href="<?php echo site_url('timesheets/print/'. $user->user_id.'/'.$month.'/'.$year."/all"); ?>"
    class="btn btn-sm btn-primary" target="_blank">
<i class="fa fa-print"></i><?= trans('timesheets_print_view_all') ?></a>

<!-- link timesheets -->
<a href="<?php echo site_url('timesheets/index'); ?>" class="btn btn-sm btn-primary">
<i class="fa fa-binoculars"></i><?= trans('default_overview') ?></a>

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

<a href="<?php echo site_url('employee/timesheets/print/'. $user->user_id.'/'.$month.'/'.$year); ?>"
    class="btn btn-sm btn-primary" target="_blank">
<i class="fa fa-print"></i><?= trans('timesheets_print_view') ?></a>

<?php endif; ?>
<!-- -->


<br> <br>
<?php $this->layout->load_view('layout/alerts'); ?>
<!-- -->

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
<?php show_user($user->user_name, $user->user_email) ?>

<br>

<?php 

if ($user_type == 1): ?>
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

<br />

<div style="display: flex; gap: 20px;">
<table style="width: 400px;" class="table-hover table-bordered no-margin">
<tr> <th colspan="12"><?php show_user($user->user_name, $user->user_email) ?></th> </tr> <tr>
<tr><td>Arbeit A</td><td> <?= $ts_hm->summary_by_type['A']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>B&uuml;rotag B</td><td> <?= $ts_hm->summary_by_type['B']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Kundenfahrt D</td><td> <?= $ts_hm->summary_by_type['D']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>&Uuml;berstunden S</td><td> <?= $ts_hm->summary_by_type['S']['hm'] ?? '00:00' ?></td></tr>
<tr><td>Krank K</td><td> <?= $ts_hm->summary_by_type['K']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Feiertag F</td><td> <?= $ts_hm->summary_by_type['F']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Urlaub U</td><td> <?= $ts_hm->summary_by_type['U']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Unbezahlter Urlaub UU</td><td> <?= $ts_hm->summary_by_type['UU']['hm'] ?? '00:00' ?> </td></tr>
<tr><td></td><td></td></tr>
<tr><td>Gesamtzeit diesen Monat</td><td> <?=  $ts_hm->total_hm; ?></td></tr>
<tr><td></td><td></td></tr>
<tr><td>Gesamtkilometer diesen Monat</td><td> <?=  $ts_km; ?></td></tr>
</table>
<?php if ($user_type == 1): ?>
<table style="width: 400px;" class="table-hover table-bordered no-margin">
<tr> <th colspan="12">Alle Mitarbeiter</th> </tr> <tr>
<tr><td>Arbeit A</td><td> <?= $ts_hm_all->summary_by_type['A']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>B&uuml;rotag B</td><td> <?= $ts_hm_all->summary_by_type['B']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Kundenfahrt D</td><td> <?= $ts_hm_all->summary_by_type['D']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>&Uuml;berstunden S</td><td> <?= $ts_hm_all->summary_by_type['S']['hm'] ?? '00:00' ?></td></tr>
<tr><td>Krank K</td><td> <?= $ts_hm_all->summary_by_type['K']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Feiertag F</td><td> <?= $ts_hm_all->summary_by_type['F']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Urlaub U</td><td> <?= $ts_hm_all->summary_by_type['U']['hm'] ?? '00:00' ?> </td></tr>
<tr><td>Unbezahlter Urlaub UU</td><td> <?= $ts_hm_all->summary_by_type['UU']['hm'] ?? '00:00' ?> </td></tr>
<tr><td></td><td></td></tr>
<tr><td>Gesamtzeit diesen Monat</td><td> <?=  $ts_hm_all->total_hm; ?></td></tr>
<tr><td></td><td></td></tr>
<tr><td>Gesamtkilometer diesen Monat</td><td> <?=  $ts_km_all; ?></td></tr>
</table>
<?php endif; ?>
</div>


<?php if ($user_type == 1): ?>

<hr style="background-color: #779977; height: 1px; border: 0;">

<!-- show date -->
 <i class="fa fa-calendar" title=""></i>
 <span> <?= $month ?>.<?= $year ?></span>
<br>
Arbeitsstunden (A,B,D) + Andere Stunden = Gesamtstunden
<br>

<!-- link users -->
<?php $non_work=0; $work = 0; foreach ($users as $u): ?>
<span style="width:14em; display: block; float: left;">
<a href="<?php echo site_url('timesheets/form/'.$u->user_id.'/'.$month.'/'.$year); ?>" class="btn" >
<i class="fa fa-user"></i> <?= $u->user_name ?>
<br />
<?= $u->work ?? 0 ?> +
<?= $u->non_work ?? 0 ?> =
<?= ($u->non_work ?? 0) + ($u->work ?? 0) ?>
</a>
</span>
<?php $work += $u->work ?? 0; $non_work += $u->non_work ?? 0; endforeach; ?>
<div style="clear: both;"></div>

<!-- donut -->
<table><tr><td>
Arbeitsstunden Monat <span style="color:#c0ff80;">&#11044;</span> gesamt: <?= $work; ?><br>
Andere Stunden Monat <span style="color:#ff80c0;">&#11044;</span> gesamt: <?= $non_work; ?>
</td><td>
<canvas id="donutChart" width="100" height="100"></canvas>
<script>
drawDonut(<?= $work; ?>, <?= $non_work; ?>);
</script>
</td></tr></table>

<?php endif; ?>

<hr style="background-color: #779977; height: 1px; border: 0;">

<!-- link dates -->
<div>
   <!-- show user -->
    <?php show_user($user->user_name, $user->user_email); ?>
<br>


<style>
.number-wrapper {
    position: relative;
    display: inline-block;
    font-size: 2rem;
}

.number-wrapper .overlay {
    position: absolute;
    top: -10px;
    right: -5px;
    background-color: #c080c0;
    color: white;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 1.0rem;
    transform: rotate(-20deg);
    pointer-events: none; /* Optional: klickt nicht durch */
}
</style>

Schnellansicht
<br><br>
<?php for ($y = $fast_datea; $y >= $fast_dateb; $y--): ?>
  <div class="btn-group no-margin" >

  <div class="btn btn-default">
  <span><?= $y ?></span>
  </div>

  <?php for ($m=1; $m<=12; $m++): ?>

<?php if ($user_type == 1): ?>

<div class="number-wrapper">
  <a href="<?php echo site_url('timesheets/view/'.$user_id.'/'.$m.'/'.$y); ?>" class="btn btn-default">
  <span><?= $m ?></span>
  <?php if(!empty($fast_h[$y][$m])) {
    echo '<div class="overlay">';
    echo $fast_h[$y][$m];
    echo '</div>';
  } ?>
  </a>
</div>

<?php else: ?>

<div class="number-wrapper">
  <a href="<?php echo site_url('employee/timesheets/view/'.$user_id.'/'.$m.'/'.$y); ?>" class="btn btn-default">
  <span ><?= $m ?></span>
  <?php if(!empty($fast_h[$y][$m])) {
    echo '<div class="overlay">';
    echo $fast_h[$y][$m];
    echo '</div>';
  } ?>
    </a>
</div>

<?php endif; ?>

  <?php endfor; ?>
  </div>
  <br> <br>
<?php endfor; ?>
</div>

</div>
</div>
