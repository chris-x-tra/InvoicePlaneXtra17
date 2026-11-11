<?php
// $this->session->userdata('user_name')
// $this->session->userdata('user_email')
// $this->session->userdata('user_type')
$user_type = $this->session->userdata('user_type');
$is_admin  = ($user_type == 1);
$this->load->helper('user_helper');

// Tabelle "Stunden nach Typ" - Typen kommen aus Mdl_Timesheets::worktypes()
$summary = function ($header_html, $ts_hm, $ts_km) use ($worktypes) {
    echo '<table style="width: 400px;" class="table-hover table-bordered no-margin">';
    echo '<tr><th colspan="2">' . $header_html . '</th></tr>';
    $known = 0;
    foreach ($worktypes as $code => $t) {
        $m = $ts_hm->summary_by_type[$code]['minutes'] ?? 0;
        $known += $m;
        echo '<tr><td>' . html_escape($t['label']) . ' ' . $code . '</td><td>' . Mdl_Timesheets::fmt_hm($m) . '</td></tr>';
    }
    // Eintraege mit unbekanntem/leerem Typ - sonst waeren Summe und Einzelzeilen nicht stimmig
    $other = $ts_hm->total_minutes - $known;
    if ($other > 0) {
        echo '<tr><td>Sonstige (ohne gültigen Typ)</td><td>' . Mdl_Timesheets::fmt_hm($other) . '</td></tr>';
    }
    echo '<tr><td colspan="2">&nbsp;</td></tr>';
    echo '<tr><td>Gesamtzeit diesen Monat</td><td>' . $ts_hm->total_hm . '</td></tr>';
    echo '<tr><td colspan="2">&nbsp;</td></tr>';
    echo '<tr><td>Gesamtkilometer diesen Monat</td><td>' . (int)$ts_km . '</td></tr>';
    echo '</table>';
};
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

    // noch keine Stunden: grauer Ring statt NaN-Winkel
    if (total <= 0) {
        ctx.beginPath();
        ctx.moveTo(size, size);
        ctx.arc(size, size, size - 10, 0, 2 * Math.PI);
        ctx.closePath();
        ctx.fillStyle = '#e0e0e0';
        ctx.fill();
    }

    // Donut zeichnen
    for (var i = 0; total > 0 && i < values.length; i++) {
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

<div id="headerbar"><?php _trans('timesheets'); ?></div>

<div id="content" class="table-content">
<div class="col-xs-12 col-md-8">

<?php
$this->load->view('timesheets/nav', [
    'current' => 'index', 'ts_base' => $ts_base, 'user' => $user, 'user_id' => $user_id,
    'month' => $month, 'year' => $year, 'years' => $years, 'all_month' => $all_month, 'users' => $users,
]);
?>

<br />

<div style="display: flex; gap: 20px;">
<?php
ob_start(); show_user($user->user_name, $user->user_email); $hdr = ob_get_clean();
$summary($hdr, $ts_hm, $ts_km);
if ($is_admin) $summary('Alle Mitarbeiter', $ts_hm_all, $ts_km_all);
?>
</div>


<?php if ($is_admin): ?>

<hr style="background-color: #779977; height: 1px; border: 0;">

<!-- show date -->
 <i class="fa fa-calendar"></i>
 <span> <?= (int)$month ?>.<?= (int)$year ?></span>
<br>
Arbeitsstunden (<?= html_escape(implode(',', Mdl_Timesheets::work_type_codes())) ?>) + Andere Stunden = Gesamtstunden
<br>

<!-- link users -->
<?php $non_work = 0; $work = 0; foreach ($users as $u): ?>
<span style="width:14em; display: block; float: left;">
<a href="<?php echo site_url('timesheets/form/'.(int)$u->user_id.'/'.(int)$month.'/'.(int)$year); ?>" class="btn" >
<i class="fa fa-user"></i> <?= html_escape($u->user_name) ?>
<br />
<?= Mdl_Timesheets::fmt_hm($u->work) ?> +
<?= Mdl_Timesheets::fmt_hm($u->non_work) ?> =
<?= Mdl_Timesheets::fmt_hm($u->work + $u->non_work) ?>
</a>
</span>
<?php $work += $u->work; $non_work += $u->non_work; endforeach; ?>
<div style="clear: both;"></div>

<!-- donut (Summen in Minuten, Anzeige als HH:MM) -->
<table><tr><td>
Arbeitsstunden Monat <span style="color:#c0ff80;">&#11044;</span> gesamt: <?= Mdl_Timesheets::fmt_hm($work) ?><br>
Andere Stunden Monat <span style="color:#ff80c0;">&#11044;</span> gesamt: <?= Mdl_Timesheets::fmt_hm($non_work) ?>
</td><td>
<canvas id="donutChart" width="100" height="100"></canvas>
<script>
drawDonut(<?= (int)$work; ?>, <?= (int)$non_work; ?>);
</script>
</td></tr></table>

<?php endif; ?>

<hr style="background-color: #779977; height: 1px; border: 0;">

<!-- link dates -->
<div>
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

Schnellansicht (Gesamtstunden aller Typen)
<br><br>
<?php for ($y = $fast_datea; $y >= $fast_dateb; $y--): ?>
  <div class="btn-group no-margin" >

  <div class="btn btn-default">
  <span><?= $y ?></span>
  </div>

  <?php for ($m = 1; $m <= 12; $m++): $minutes = $fast_h[$y][$m] ?? 0; ?>
<div class="number-wrapper">
  <a href="<?php echo site_url($ts_base . '/view/' . (int)$user_id . '/' . $m . '/' . $y); ?>"
     class="btn btn-default<?= ($y === (int)$year && $m === (int)$month) ? ' active' : '' ?>">
  <span><?= $m ?></span>
  <?php if ($minutes > 0): ?>
    <div class="overlay"><?= Mdl_Timesheets::fmt_hm($minutes, false) ?></div>
  <?php endif; ?>
  </a>
</div>
  <?php endfor; ?>
  </div>
  <br> <br>
<?php endfor; ?>
</div>

</div>
</div>
