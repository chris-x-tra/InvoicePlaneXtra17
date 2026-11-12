<?php
/* Excel-Import: Vorschau. erwartet: $ts_base, $token, $data, $target, $clients, $labels, $names, $name_warn, $worktypes */
$rows = $data['rows'];
$cnt = ['ok' => 0, 'skip' => 0, 'error' => 0, 'exists' => 0];
foreach ($rows as $r) {
    $cnt[$r['status']]++;
    if (!empty($r['exists'])) $cnt['exists']++;
}
$mins = 0;
foreach ($rows as $r) if ($r['status'] === 'ok' && empty($r['exists'])) $mins += (int)$r['minutes'];
$hm = function ($m) { return sprintf('%d:%02d', intdiv($m, 60), $m % 60); };
$weekday = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
?>
<a href="<?= site_url($ts_base . '/import') ?>" class="btn btn-sm btn-primary"><i class="fa fa-upload"></i> Neue Datei</a>
<a href="<?= site_url($ts_base . '/index') ?>" class="btn btn-sm btn-primary"><i class="fa fa-binoculars"></i> Übersicht</a>
<br><br>
<?php $this->layout->load_view('layout/alerts'); ?>

<h3>Import prüfen: <?= html_escape($data['file']) ?></h3>
<p>
    Es wird importiert für: <b><?= html_escape($target->user_name) ?></b> (<?= (int)$target->user_id ?>)
    <?php if (!empty($data['owner'])): ?>&nbsp;·&nbsp; Name in der Datei: <b><?= html_escape($data['owner']) ?></b><?php endif; ?>
</p>
<?php if ($name_warn): ?>
    <p class="alert alert-warning"><b>Achtung:</b> Der Name in der Datei („<?= html_escape($data['owner']) ?>“) passt nicht zum gewählten Benutzer
        „<?= html_escape($target->user_name) ?>“. Bitte prüfen. Zum Ändern des Benutzers die Datei neu hochladen.</p>
<?php endif; ?>

<p>
    <b><?= $cnt['ok'] ?></b> gültige Zeilen
    (<?= $hm($mins) ?> h ohne bereits vorhandene)
    <?php if ($cnt['exists']): ?>· <b><?= $cnt['exists'] ?></b> mit gleicher Zeit am gleichen Tag schon vorhanden (vorab abgewählt)<?php endif; ?>
    <?php if ($cnt['skip']): ?>· <b><?= $cnt['skip'] ?></b> übersprungen<?php endif; ?>
    <?php if ($cnt['error']): ?>· <b class="text-danger"><?= $cnt['error'] ?></b> fehlerhaft<?php endif; ?>
</p>

<?php if ($cnt['error']): ?>
    <p class="alert alert-danger"><b><?= $cnt['error'] ?> fehlerhafte Zeile(n)</b> (rot) werden nicht importiert. Bitte im Excel korrigieren und neu hochladen
        – oder die übrigen Zeilen jetzt importieren und die korrigierten später nachladen (bereits vorhandene Einträge werden nicht doppelt angelegt).</p>
<?php endif; ?>

<details>
    <summary>Tabellenblätter (<?= count($data['sheets']) ?>)</summary>
    <table class="table table-condensed table-bordered" style="width:auto;">
        <?php foreach ($data['sheets'] as $s): ?>
        <tr><td><?= html_escape($s['title']) ?></td><td><?= (int)$s['rows'] ?> Zeilen</td><td><?= html_escape($s['note']) ?></td></tr>
        <?php endforeach; ?>
    </table>
</details>
<br>

<form method="post" action="<?= site_url($ts_base . '/import_confirm') ?>">
    <input type="hidden" name="<?= $this->config->item('csrf_token_name') ?>" value="<?= $this->security->get_csrf_hash() ?>">
    <input type="hidden" name="token" value="<?= html_escape($token) ?>">

<?php if ($names): ?>
    <h4>Klienten zuordnen</h4>
    <p>Orange = unsicherer Vorschlag oder nicht gefunden. „ohne Klient“ importiert die Zeile mit dem Namen aus der Datei als Bemerkung.</p>
    <table class="table table-condensed table-bordered" style="width:auto;">
        <tr><th>Name in der Datei</th><th>Zeilen</th><th>Zuordnung</th></tr>
        <?php foreach ($names as $key => $n):
            $m = $n['match'];
            $bg = $m['quality'] === 'exact' ? '' : ' style="background:#fff3cd;"';
            $sel = $m['id'] > 0 ? (string)$m['id'] : 'none';
            $cand = array_flip($m['candidates']);
        ?>
        <tr<?= $bg ?>>
            <td><?= html_escape($n['text']) ?></td>
            <td><?= (int)$n['count'] ?></td>
            <td>
                <select name="map[<?= html_escape($key) ?>]">
                    <option value="none"<?= $sel === 'none' ? ' selected' : '' ?>>– ohne Klient (Name als Bemerkung) –</option>
                    <option value="skip">– Zeilen nicht importieren –</option>
                    <?php if ($m['candidates']): ?>
                    <optgroup label="Vorschläge">
                        <?php foreach ($m['candidates'] as $cid): ?>
                        <option value="<?= (int)$cid ?>"<?= $sel === (string)$cid ? ' selected' : '' ?>><?= html_escape($labels[$cid] ?? ('#' . $cid)) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                    <optgroup label="Alle Klienten">
                        <?php foreach ($clients as $c): if (isset($cand[$c['id']])) continue; ?>
                        <option value="<?= (int)$c['id'] ?>"><?= html_escape($c['label']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
                <?php if ($m['quality'] === 'suggest'): ?><small>Vorschlag (<?= (int)round($m['score'] * 100) ?> %)</small><?php endif; ?>
                <?php if ($m['quality'] === 'none'): ?><small>nicht gefunden</small><?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

    <h4>Zeilen</h4>
    <p><label><input type="checkbox" id="ts_all" checked> alle gültigen Zeilen</label></p>
    <table class="table table-condensed table-bordered table-hover" style="width:auto;">
        <tr><th></th><th>Blatt</th><th>Datum</th><th>Klient / Text</th><th>Typ</th><th>Beginn</th><th>Ende</th><th>Dauer</th><th>Hinweis</th></tr>
        <?php foreach ($rows as $r):
            $bad = $r['status'] !== 'ok';
            $style = $r['status'] === 'error' ? ' style="background:#f8d7da;"' : ($r['status'] === 'skip' ? ' style="background:#eee;color:#777;"' : (!empty($r['exists']) ? ' style="background:#eee;color:#777;"' : ''));
            $ts = $r['date'] ? strtotime($r['date']) : 0;
        ?>
        <tr<?= $style ?>>
            <td><?php if (!$bad): ?><input type="checkbox" class="ts_row" name="rows[]" value="<?= (int)$r['n'] ?>"<?= empty($r['exists']) ? ' checked' : '' ?>><?php endif; ?></td>
            <td><?= html_escape($r['sheet']) ?> <small>Z<?= (int)$r['line'] ?></small></td>
            <td><?= $ts ? $weekday[(int)date('w', $ts)] . ' ' . date('d.m.Y', $ts) : '–' ?></td>
            <td><?= html_escape($r['text']) ?></td>
            <td><?= $r['special'] || !$bad ? html_escape($r['type'] . ' ' . ($worktypes[$r['type']]['label'] ?? '')) : '' ?></td>
            <td><?= html_escape($r['from']) ?></td>
            <td><?= html_escape($r['to']) ?></td>
            <td><?= $r['status'] === 'ok' ? $hm((int)$r['minutes']) : '' ?></td>
            <td><?php
                $h = [];
                if ($r['status'] !== 'ok') $h[] = $r['reason'];
                if (!empty($r['exists'])) $h[] = 'gleiche Zeit am Tag schon vorhanden';
                if ($r['warn']) $h[] = $r['warn'];
                echo html_escape(implode('; ', $h));
            ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <p>
        <input type="submit" class="btn btn-success" value="Ausgewählte Zeilen importieren">
        <a href="<?= site_url($ts_base . '/import') ?>" class="btn btn-default">Abbrechen</a>
    </p>
</form>
<script>
document.getElementById('ts_all').addEventListener('change', function () {
    var on = this.checked;
    document.querySelectorAll('.ts_row').forEach(function (c) { c.checked = on; });
});
</script>
