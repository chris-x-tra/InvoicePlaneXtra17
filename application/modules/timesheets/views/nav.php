<?php
/*
 * Gemeinsame Navigation fuer timesheets/index und timesheets/form:
 * Link-Leiste, Meldungen, Formular zum Wechseln von Monat/Jahr/Benutzer.
 *
 * erwartet: $current ('index'|'form'), $ts_base, $user, $user_id, $month, $year, $years, $all_month, $users
 */
$is_admin = ($this->session->userdata('user_type') == 1);
$tail = '/' . (int)$user->user_id . '/' . (int)$month . '/' . (int)$year;

$links = [
    ['index', 'fa-binoculars', 'overview'],
    ['form',  'fa-pencil',     'timesheet_input'],
    ['view',  'fa-eye',        'timesheets_view'],
];
foreach ($links as $l) {
    echo '<a href="' . site_url($ts_base . '/' . $l[0] . $tail) . '" class="btn btn-sm btn-primary'
       . ($current === $l[0] ? ' active' : '') . '"><i class="fa ' . $l[1] . '"></i> ' . trans($l[2]) . '</a> ';
}
echo '<a href="' . site_url($ts_base . '/print' . $tail) . '" class="btn btn-sm btn-primary" target="_blank">'
   . '<i class="fa fa-print"></i> ' . trans('timesheets_print_view') . '</a> ';
if ($is_admin) {
    echo '<a href="' . site_url('timesheets/print' . $tail . '/all') . '" class="btn btn-sm btn-primary" target="_blank">'
       . '<i class="fa fa-print"></i> ' . trans('timesheets_print_view_all') . '</a> ';
    echo '<a href="' . site_url('timesheets/index') . '" class="btn btn-sm btn-primary">'
       . '<i class="fa fa-binoculars"></i> ' . trans('default_overview') . '</a>';
}
?>

<br><br>
<?php $this->layout->load_view('layout/alerts'); ?>

<!-- Monat/Jahr und Benutzer wechseln (ein Formular um die ganze Tabelle) -->
<form method="post" action="<?php echo site_url($this->uri->uri_string()); ?>">
    <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
           value="<?php echo $this->security->get_csrf_hash() ?>">
<table><tr><td style="padding: 10px 20px 0 0;">
    <i class="fa fa-calendar"></i>
    <span><?= (int)$month ?>.<?= (int)$year ?></span>
    <br>
    <select id="my_month" name="my_month">
    <?php foreach ($all_month as $i => $name): ?>
        <option value="<?= $i ?>"<?= ((int)$month === (int)$i) ? ' selected="selected"' : '' ?>><?= $i ?> - <?= html_escape($name) ?></option>
    <?php endforeach; ?>
    </select>

    <select id="my_year" name="my_year">
    <?php foreach ($years as $y): ?>
        <option value="<?= $y ?>"<?= ((int)$year === (int)$y) ? ' selected="selected"' : '' ?>><?= $y ?></option>
    <?php endforeach; ?>
    </select>

    <input type="submit" class="btn" name="btn_submit_user" value="<?php _trans('change'); ?>">
</td><td>
    <?php show_user($user->user_name, $user->user_email); ?>
    <br>
    <?php if ($is_admin): ?>
    <select id="my_userid" name="my_userid">
    <?php foreach ($users as $u): ?>
        <option value="<?= (int)$u->user_id ?>"<?= ((int)$user_id === (int)$u->user_id) ? ' selected="selected"' : '' ?>><?= html_escape($u->user_name) ?> (<?= (int)$u->user_id ?>)</option>
    <?php endforeach; ?>
    </select>
    <input type="submit" class="btn" name="btn_submit_user" value="<?php _trans('change'); ?>">
    <?php endif; ?>
</td></tr></table>
</form>
