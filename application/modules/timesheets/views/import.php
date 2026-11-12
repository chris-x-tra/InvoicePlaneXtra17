<?php
/* Excel-Import: Upload-Formular. erwartet: $ts_base, $is_admin, $users, $own_id, $lib_ok */
?>
<a href="<?= site_url($ts_base . '/index') ?>" class="btn btn-sm btn-primary"><i class="fa fa-binoculars"></i> Übersicht</a>
<br><br>
<?php $this->layout->load_view('layout/alerts'); ?>

<h3>Arbeitszeiten aus Excel importieren</h3>
<p><a href="<?= site_url($ts_base . '/import_template') ?>"><i class="fa fa-download"></i> Leere Excel-Vorlage herunterladen</a> (passt genau zum Import, mit Prüfspalte)</p>

<?php if (!$lib_ok): ?>
    <p class="alert alert-danger">PhpSpreadsheet ist nicht installiert. Der Import kann nicht verwendet werden.</p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" action="<?= site_url($ts_base . '/import_upload') ?>">
    <input type="hidden" name="<?= $this->config->item('csrf_token_name') ?>" value="<?= $this->security->get_csrf_hash() ?>">

<?php if ($is_admin): ?>
    <p>
        <b>Importieren für Benutzer</b><br>
        <select name="userid" required>
            <option value="">– bitte wählen –</option>
            <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u->user_id ?>"<?= ((int)$u->user_id === (int)$own_id) ? '' : '' ?>><?= html_escape($u->user_name) ?> (<?= (int)$u->user_id ?>)</option>
            <?php endforeach; ?>
        </select>
    </p>
<?php else: ?>
    <input type="hidden" name="userid" value="<?= (int)$own_id ?>">
<?php endif; ?>

    <p>
        <b>Excel-Datei (.xls / .xlsx, max. 5 MB)</b><br>
        <input type="file" name="userfile" accept=".xls,.xlsx" required>
    </p>
    <p><input type="submit" class="btn btn-success" value="Hochladen und prüfen"<?= $lib_ok ? '' : ' disabled' ?>></p>
</form>

<h4>Format</h4>
<ul>
    <li>Ein oder mehrere Tabellenblätter (z. B. je Monat). In jedem Blatt muss in den ersten 15 Zeilen die Kopfzeile
        <b>Datum | Klient | Beginn | Ende</b> in den Spalten A bis D stehen. Blätter ohne diese Kopfzeile (z. B. ein Wochenplan) werden übersprungen.</li>
    <li>Spalte E „Stunden“ wird nur für Urlaub/Krank/Feiertag/Überstunden/Unbezahlter Urlaub gelesen (siehe unten); alle anderen Spalten werden ignoriert, die Dauer berechnet die Datenbank.</li>
    <li>Steht in der Spalte „Klient“ <i>Urlaub</i>, <i>Krank</i>, <i>Feiertag</i> oder <i>Überstunden</i>, wird der passende Typ gesetzt
        (der Text kommt in die Bemerkung). Alles andere ist ein Arbeitstag beim genannten Klienten.</li>
    <li><b>Arbeitseinsätze:</b> Beginn und Ende sind Pflicht.</li>
    <li><b>Urlaub / Krank / Feiertag / Überstunden / Unbezahlter Urlaub:</b> es genügen die Stunden in Spalte E als Dezimalzahl (z. B. 4 oder 7,7, 0,25 bis 16).
        Der Import rechnet ab 08:00 (4 Stunden = 08:00 – 12:00). Sind Beginn und Ende eingetragen, gelten diese.</li>
    <li>Fehlerhafte Zeilen werden nicht importiert; bitte im Excel korrigieren und neu hochladen.</li>
    <li>Nach dem Hochladen wird nichts gespeichert: Zuerst gibt es eine Vorschau, in der die Namen den Klienten zugeordnet werden.</li>
</ul>
