<?php
// $this->session->userdata('user_name')
// $this->session->userdata('user_email')
// $this->session->userdata('user_type')
$this->load->helper('user_helper');

$time_step = 300;   // Schrittweite der Zeitfelder in Sekunden (300 = 5 Minuten)
$js_flags  = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;

$work_codes = Mdl_Timesheets::work_type_codes();
$dows = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];
?>
<style>
    .error-row td { background-color: #f8d7da !important; }

    .autocomplete-wrapper { position: relative; min-width: 16em; }
    .autocomplete-wrapper .results {
        position: absolute; left: 0; right: 0; top: 100%; z-index: 20;
        max-height: 180px; overflow-y: auto; background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .result-item { padding: 4px; cursor: pointer; }
    .result-item.selected { background-color: #007bff; color: white; }
    .result-item:hover { background-color: #f0f0f0; }
    .customer-unset { outline: 2px solid #d9534f; }

    .btn-ins { background-color: #a0c050; color: white; padding: 5px 10px; border: none; border-radius: 4px; }
    .btn-del { background-color: #ff6060; color: white; padding: 5px 10px; border: none; border-radius: 4px; }
    .button-cell { display: flex; gap: 0.5rem; align-items: center; }

    #x-worktime td.dow-Sa { background-color: #c0ff80; }
    #x-worktime td.dow-So, #x-worktime td.is-holiday { background-color: #ff80c0; }
    #x-worktime td small.holiday-name { font-weight: normal; }
    #x-worktime td.is-today { font-weight: bold; box-shadow: inset 4px 0 0 #337ab7; }
    #x-worktime tr.day-first { border-top: 2px solid #888; }
    #x-worktime td.day-sum { color: green; font-weight: bold; }

    .dur-warn  { border-color: #f0ad4e !important; background-color: #fcf8e3 !important; }
    .dur-night { border-color: #5bc0de !important; }
</style>

<div id="headerbar"><?php _trans('enter_worktime'); ?></div>

<div id="content" class="table-content">
<div>

<?php
$this->load->view('timesheets/nav', [
    'current' => 'form', 'ts_base' => $ts_base, 'user' => $user, 'user_id' => $user_id,
    'month' => $month, 'year' => $year, 'years' => $years, 'all_month' => $all_month, 'users' => $users,
]);
?>

<br>

<table>
<tr><td class="col-md-3">

    <i class="fa fa-calculator"></i>
    <span><?php _trans('sum_month'); ?>: <span id="all_day_result" style="color: green; font-weight:bold;">00:00</span></span>
    <br>
    <span class="text-muted">davon Arbeit (<?= html_escape(implode(', ', $work_codes)) ?>): <span id="work_day_result">00:00</span></span>
    <br><br>

    <a href="#" class="btn btn-sm btn-primary btn_calc_worktime"><i class="fa fa-calendar"></i> <?= trans('calculate') ?></a>
    <a href="#" class="btn btn-sm btn-success btn_save_worktime"><i class="fa fa-check"></i> <?= trans('save') ?></a>
    <span id="saveStatus" style="display:none;color:#d9534f;font-weight:bold;">
        <i class="fa fa-save"></i> Nicht gespeichert
    </span>

</td><td class="col-md-6" id="timesheet-err">
</td>
</tr></table>

<br>

<!-- Eingabetabelle; gespeichert wird per AJAX (kein klassisches Formular-Submit) -->
<table id="x-worktime" class="table table-hover table-bordered table-condensed no-margin">
<thead>
<tr>
    <th style="width: 1em;"><i class="fa fa-tasks"></i></th>
    <th style="width: 2em;"><?= trans('day'); ?></th>
    <th style="width: 10em;"><?= trans('type'); ?></th>
    <th style="width: 5em;"><?= trans('begin'); ?></th>
    <th style="width: 5em;"><?= trans('end'); ?></th>
    <th style="width: 3em;"><?= trans('hours'); ?></th>
    <th style="width: 3em;"><?= trans('sum'); ?></th>
    <th style="width: 10em;"><?= trans('client'); ?></th>
    <th style="width: 7em;"><?= trans('km'); ?></th>
    <th style="width: 10em;"><?= trans('remark'); ?></th>
</tr>
</thead>
<tbody>
<?php
$days_in_month = (int)date('t', mktime(0, 0, 0, (int)$month, 1, (int)$year));

$by_day = [];
foreach ($timesheets as $t) {
    $by_day[(int)$t->timesheet_day][] = $t;
}
$holidays = Mdl_Timesheets::holidays((int)$year);

for ($day = 1; $day <= $days_in_month; $day++) {
    $dow      = $dows[(int)date('N', mktime(0, 0, 0, (int)$month, $day, (int)$year))];
    $is_today = (date('Y-n-j') === (int)$year . '-' . (int)$month . '-' . $day);
    $holiday  = $holidays[sprintf('%04d-%02d-%02d', (int)$year, (int)$month, $day)] ?? '';
    $rows     = $by_day[$day] ?? [null];     // Tag ohne Eintrag: eine leere Zeile

    foreach ($rows as $idx => $t) {
        $this->load->view('timesheets/form_row', [
            'day' => $day, 'dow' => $dow, 'idx' => $idx, 't' => $t, 'is_today' => $is_today, 'holiday' => $holiday,
            'worktypes' => $worktypes, 'client_labels' => $client_labels, 'time_step' => $time_step,
        ]);
    }
}
?>
</tbody>
</table>

<br />
    <a href="#" class="btn btn-sm btn-primary btn_calc_worktime"><i class="fa fa-calendar"></i> <?= trans('calculate') ?></a>
    <a href="#" class="btn btn-sm btn-success btn_save_worktime"><i class="fa fa-check"></i> <?= trans('save') ?></a>

<br /> <br />

<script>
(function () {
    'use strict';

    /* ---------- Daten vom Server (alles per json_encode, nichts per String-Konkatenation) ---------- */
    var TYPES      = <?= json_encode($worktypes, $js_flags) ?: '{}' ?>;       // code => {label, group, needs_time}
    var KUNDEN     = <?= json_encode($kunden_js, $js_flags) ?: '[]' ?>;       // [{id, name}]
    var URL_SAVE   = <?= json_encode(site_url('timesheets/ajax/update_by_uuid')) ?>;
    var URL_DELETE = <?= json_encode(site_url('timesheets/ajax/set_delete')) ?>;
    var CTX        = { userid: <?= (int)$user->user_id ?>, month: <?= (int)$month ?>, year: <?= (int)$year ?> };
    var CSRF_NAME  = <?= json_encode($this->security->get_csrf_token_name()) ?>;
    var CSRF_HASH  = <?= json_encode($this->security->get_csrf_hash()) ?>;
    var AUTOSAVE_MS = 60000;
    var LONG_DAY_MIN = 12 * 60;      // Warnung ab 12 h in einer Zeile

    KUNDEN.forEach(function (k) { k.l = k.name.toLowerCase(); });

    var LABELS = {};                 // Typ-Bezeichnungen, die als "automatische Bemerkung" gelten
    Object.keys(TYPES).forEach(function (c) { LABELS[TYPES[c].label] = true; });

    /* ---------- Zeitrechnung (identisch zur DB: Ende < Beginn = ueber Mitternacht) ---------- */
    function toMinutes(s) {
        var m = /^(\d{1,2}):(\d{2})/.exec(s || '');
        return m ? parseInt(m[1], 10) * 60 + parseInt(m[2], 10) : null;
    }
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function toHM(min) { return pad(Math.floor(min / 60)) + ':' + pad(min % 60); }

    function rowMinutes($row) {
        var s = toMinutes($row.find('.x_from').val());
        var e = toMinutes($row.find('.x_to').val());
        if (s === null || e === null) return 0;
        var d = e - s;
        if (d < 0) d += 1440;
        return d;
    }

    /* Dauer einer Zeile anzeigen + Hinweise (Nacht / sehr lang) */
    function updateRow($row) {
        var d = rowMinutes($row);
        var s = toMinutes($row.find('.x_from').val());
        var e = toMinutes($row.find('.x_to').val());
        var $h = $row.find('.x_hours');
        $h.val(toHM(d)).removeClass('dur-warn dur-night').removeAttr('title');
        if (d > LONG_DAY_MIN) {
            $h.addClass('dur-warn').attr('title', 'Mehr als 12 Stunden - bitte prüfen');
        } else if (s !== null && e !== null && e < s) {
            $h.addClass('dur-night').attr('title', 'Über Mitternacht');
        }
    }

    /* Tages- und Monatssummen aus allen Zeilen neu berechnen */
    function recalcAll() {
        var byDay = {}, total = 0, work = 0;
        $('#x-worktime tr.timestuff').each(function () {
            var $r = $(this);
            var m = rowMinutes($r);
            var day = $r.attr('data-day');
            byDay[day] = (byDay[day] || 0) + m;
            total += m;
            var t = TYPES[$r.find('.x_type').val()];
            if (t && t.group === 'work') work += m;
        });
        $('#x-worktime td.day-sum').text('');
        Object.keys(byDay).forEach(function (day) {
            if (byDay[day] > 0) $('#sum_' + day).text(toHM(byDay[day]));
        });
        $('#all_day_result').text(toHM(total));
        $('#work_day_result').text(toHM(work));
    }

    /* ---------- Aenderungsverfolgung / Autosave ---------- */
    var dirty = false, isSaving = false, timer = null;
    var changeSeq = 0, seqAtSave = 0;      // erkennt Eingaben, die waehrend eines laufenden Speicherns passieren

    function setDirty() {
        dirty = true;
        changeSeq++;
        $('#saveStatus').show();
        $('#timesheet-err').empty();
        clearTimeout(timer);
        timer = setTimeout(function () { if (dirty) save(); }, AUTOSAVE_MS);
    }

    // Warnung beim Verlassen der Seite (Link, Monatswechsel, Tab schliessen)
    window.addEventListener('beforeunload', function (e) {
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });

    /* ---------- Zeilen-Events (delegiert, funktionieren auch fuer eingefuegte Zeilen) ---------- */
    $(document).on('input change', '#x-worktime .x_from, #x-worktime .x_to', function () {
        updateRow($(this).closest('tr'));
        recalcAll();
        setDirty();
    });

    // Dauer direkt aendern -> Ende neu setzen
    $(document).on('change', '#x-worktime .x_hours', function () {
        var $r = $(this).closest('tr');
        var s = toMinutes($r.find('.x_from').val());
        var d = toMinutes($(this).val());
        if (s !== null && d !== null) {
            $r.find('.x_to').val(toHM((s + d) % 1440));
        }
        updateRow($r);
        recalcAll();
        setDirty();
    });

    $(document).on('input change', '#x-worktime .x_km, #x-worktime .x_remark', setDirty);

    // Typ gewaehlt: Bemerkung vorbelegen - aber nur, wenn sie leer ist oder selbst nur eine Typ-Bezeichnung
    $(document).on('change', '#x-worktime .x_type', function () {
        var $r = $(this).closest('tr');
        var code = $(this).val();
        var $rm = $r.find('.x_remark');
        var cur = $.trim($rm.val());
        if (cur === '' || LABELS[cur]) {
            $rm.val(code && code !== 'A' && TYPES[code] ? TYPES[code].label : '');
        }
        recalcAll();
        setDirty();
    });

    function resetRow($r) {
        $r.find('.x_type').val('');
        $r.find('.x_from, .x_to, .x_hours').val('00:00');
        $r.find('.x_remark').val('');
        $r.find('.x_km').val('0');
        $r.find('.searchInput').val('').removeClass('customer-unset').removeAttr('title');
        $r.find('.kundenId').val('');
        $r.find('.results').empty();
        $r.removeClass('error-row').removeAttr('title');
        updateRow($r);
    }

    // Radiergummi: Zeile leeren. Eine geleerte, bereits gespeicherte Zeile wird beim Speichern geloescht.
    $(document).on('click', '#x-worktime .btn-reset', function () {
        resetRow($(this).closest('tr'));
        recalcAll();
        setDirty();
    });

    // Zeile einfuegen: Kopie der ersten Tageszeile, komplett zurueckgesetzt, unter die letzte Zeile des Tages
    $(document).on('click', '#x-worktime .btn-ins', function () {
        var $first = $(this).closest('tr');
        var day = $first.attr('data-day');
        var $last = $('#x-worktime tr.timestuff[data-day="' + day + '"]').last();

        var $n = $first.clone();
        $n.removeClass('day-first').attr('data-persisted', '0');
        $n.find('.x_uuid').val(uuidv4());
        $n.find('td.day-sum').empty().removeAttr('id');
        $n.find('.btn-ins').replaceWith(
            '<button type="button" class="btn-del button" title="Zeile löschen"><i class="fa fa-trash"></i></button>');
        resetRow($n);
        $last.after($n);
        $n.find('.x_type').focus();
    });

    // Zeile loeschen (nur Zusatzzeilen): gespeicherte Zeilen erst nach Erfolg vom Server entfernen
    $(document).on('click', '#x-worktime .btn-del', function () {
        var $r = $(this).closest('tr');
        if (!confirm('Diese Zeile wirklich löschen?')) return;

        if ($r.attr('data-persisted') !== '1') {
            $r.remove(); recalcAll(); return;
        }
        var data = { uuid: $r.find('.x_uuid').val() };
        data[CSRF_NAME] = CSRF_HASH;
        $.ajax({ url: URL_DELETE, method: 'POST', data: data, dataType: 'json', timeout: 30000 })
            .done(function (r) {
                if (r && r.success) { $r.remove(); recalcAll(); }
                else { alert('Löschen fehlgeschlagen.'); }
            })
            .fail(function () {
                alert('Löschen fehlgeschlagen (Netzwerk oder Sitzung abgelaufen?). Die Zeile wurde nicht gelöscht.');
            });
    });

    /* ---------- Kunden-Suche (delegiert) ---------- */
    function pickCustomer($wrap, k) {
        $wrap.find('.searchInput').val(k.name).removeClass('customer-unset').removeAttr('title');
        $wrap.find('.kundenId').val(k.id);
        $wrap.find('.results').empty();
        setDirty();
    }

    function matches(q) {
        var out = [];
        for (var i = 0; i < KUNDEN.length; i++) {
            if (KUNDEN[i].l.indexOf(q) !== -1) out.push(KUNDEN[i]);
        }
        return out;
    }

    $(document).on('input', '#x-worktime .searchInput', function () {
        var $in = $(this), $wrap = $in.closest('.autocomplete-wrapper');
        var $res = $wrap.find('.results').empty();
        $wrap.find('.kundenId').val('');
        $in.removeClass('customer-unset').removeAttr('title');
        var q = $.trim($in.val()).toLowerCase();
        if (!q) return;
        matches(q).slice(0, 50).forEach(function (k) {
            $('<div class="result-item">').text(k.name).data('kunde', k).appendTo($res);
        });
    });

    // mousedown statt click: der Eintrag wird gewaehlt, bevor das Feld den Fokus verliert
    $(document).on('mousedown', '#x-worktime .result-item', function (e) {
        e.preventDefault();
        pickCustomer($(this).closest('.autocomplete-wrapper'), $(this).data('kunde'));
    });

    $(document).on('keydown', '#x-worktime .searchInput', function (e) {
        var $wrap = $(this).closest('.autocomplete-wrapper');
        var $items = $wrap.find('.result-item');
        if (!$items.length) return;
        var $sel = $items.filter('.selected');
        var idx = $sel.length ? $items.index($sel) : -1;

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            idx = e.key === 'ArrowDown' ? (idx + 1) % $items.length : (idx - 1 + $items.length) % $items.length;
            $items.removeClass('selected');
            var el = $items.eq(idx).addClass('selected').get(0);
            if (el && el.scrollIntoView) el.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            e.acHandled = true;                       // Enter hat nur die Auswahl bedient, nicht speichern
            if (idx < 0 && $items.length === 1) idx = 0;
            if (idx >= 0) pickCustomer($wrap, $items.eq(idx).data('kunde'));
        } else if (e.key === 'Escape') {
            $wrap.find('.results').empty();
        }
    });

    // Feld verlassen: Text ohne gewaehlten Kunden -> eindeutigen Treffer uebernehmen, sonst markieren
    $(document).on('focusout', '#x-worktime .searchInput', function () {
        var $in = $(this), $wrap = $in.closest('.autocomplete-wrapper');
        $wrap.find('.results').empty();
        var q = $.trim($in.val()).toLowerCase();
        if (!q || $wrap.find('.kundenId').val() !== '') return;
        var m = matches(q);
        var exact = m.filter(function (k) { return k.l === q; });
        if (exact.length === 1) { pickCustomer($wrap, exact[0]); return; }
        if (m.length === 1)     { pickCustomer($wrap, m[0]);     return; }
        $in.addClass('customer-unset').attr('title', 'Bitte einen Kunden aus der Liste wählen');
    });

    /* ---------- Speichern ---------- */
    function uuidv4() {
        if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    function collectItems() {
        var items = [];
        $('#x-worktime tr.timestuff').each(function () {
            var $r = $(this);
            items.push({
                x_day:         $r.find('.x_day').val(),
                x_uuid:        $r.find('.x_uuid').val(),
                x_type:        $r.find('.x_type').val(),
                x_from:        $r.find('.x_from').val(),
                x_to:          $r.find('.x_to').val(),
                x_customer_id: $r.find('.kundenId').val(),
                x_customer_text: $r.find('.searchInput').val(),   // Server meldet "nicht aus der Liste gewaehlt"
                x_km:         $r.find('.x_km').val(),
                x_remark:      $r.find('.x_remark').val()
            });
        });
        return items;
    }

    function rowByUuid(uuid) {
        return $('#x-worktime .x_uuid').filter(function () { return this.value === uuid; }).closest('tr');
    }

    function showMessage(cls, text, list) {
        var $a = $('<div class="alert" style="padding:4px 8px;margin:0;">').addClass(cls).text(text);
        if (list && list.length) {
            var $ul = $('<ul style="margin:4px 0 0 18px;padding:0;">');
            list.forEach(function (s) { $('<li>').text(s).appendTo($ul); });
            $a.append($ul);
        }
        $('#timesheet-err').empty().append($a);
        return $a;
    }

    function onSaved(r) {
        if (!r || typeof r.saved === 'undefined') {
            showMessage('alert-danger', r && r.message ? r.message : 'Unerwartete Antwort vom Server.');
            return;
        }
        $('#x-worktime tr.timestuff').removeClass('error-row').removeAttr('title');

        (r.saved_uuids || []).forEach(function (u) { rowByUuid(u).attr('data-persisted', '1'); });
        // geloeschte Zeilen bekommen eine neue UUID - der geloeschte Datensatz bleibt geloescht
        (r.deleted_uuids || []).forEach(function (u) {
            rowByUuid(u).attr('data-persisted', '0').find('.x_uuid').val(uuidv4());
        });
        (r.failures || []).forEach(function (f) {
            rowByUuid(f.uuid).addClass('error-row').attr('title', f.reason);
        });

        var msg = 'Zeilen: ' + r.counter + ' | gespeichert: ' + r.saved +
                  (r.deleted ? ' | gelöscht: ' + r.deleted : '') + ' | Fehler: ' + r.failed;
        var list = (r.failures || []).map(function (f) { return 'Tag ' + f.day + ': ' + f.reason; });

        if (r.failed === 0) {
            var $ok = showMessage('alert-success', msg);
            setTimeout(function () { $ok.remove(); }, 15000);
        } else if (r.saved === 0) {
            showMessage('alert-danger', msg, list);
        } else {
            showMessage('alert-warning', msg, list);
        }

        // Fehler oder Eingaben waehrend des Speicherns -> bleibt "ungespeichert" (Warnung beim Verlassen)
        var changedMeanwhile = (changeSeq !== seqAtSave);
        dirty = (r.failed > 0) || changedMeanwhile;
        if (dirty) { $('#saveStatus').show(); } else { $('#saveStatus').hide(); }
        if (changedMeanwhile) {
            clearTimeout(timer);
            timer = setTimeout(function () { if (dirty) save(); }, AUTOSAVE_MS);
        }
    }

    function save() {
        if (isSaving) return;
        isSaving = true;
        clearTimeout(timer);
        seqAtSave = changeSeq;
        recalcAll();

        var $btns = $('.btn_save_worktime').addClass('disabled');
        var data = {
            userid: CTX.userid, month: CTX.month, year: CTX.year,
            items: JSON.stringify(collectItems())
        };
        data[CSRF_NAME] = CSRF_HASH;

        $.ajax({ url: URL_SAVE, method: 'POST', data: data, dataType: 'json', timeout: 30000 })
            .done(onSaved)
            .fail(function (xhr, status) {
                var txt;
                if (status === 'timeout')          txt = 'Zeitüberschreitung beim Speichern.';
                else if (status === 'parsererror') txt = 'Antwort nicht lesbar - ist die Sitzung abgelaufen? Bitte in einem neuen Tab anmelden und erneut speichern.';
                else if (xhr.status === 403)       txt = 'Zugriff verweigert (Sitzung abgelaufen oder CSRF-Token ungültig). Bitte Seite in einem neuen Tab neu laden/anmelden und erneut speichern.';
                else if (xhr.status === 0)         txt = 'Keine Verbindung zum Server.';
                else                               txt = 'Serverfehler (' + xhr.status + ').';
                showMessage('alert-danger', txt + ' Ihre Eingaben sind noch in diesem Fenster vorhanden.');
                dirty = true;
                $('#saveStatus').show();
            })
            .always(function () {          // egal wie es ausgeht: Sperre wieder aufheben
                isSaving = false;
                $btns.removeClass('disabled');
            });
    }

    $(document).on('click', '.btn_save_worktime', function (e) { e.preventDefault(); save(); });
    $(document).on('click', '.btn_calc_worktime', function (e) {
        e.preventDefault();
        $('#x-worktime tr.timestuff').each(function () { updateRow($(this)); });
        recalcAll();
    });

    // Enter in der Eingabetabelle speichert (nicht, wenn Enter nur die Kundenauswahl bedient hat)
    $(document).on('keydown', '#x-worktime input, #x-worktime select', function (e) {
        if (e.key === 'Enter' && !e.acHandled) {
            e.preventDefault();
            save();
        }
    });

    /* ---------- Start ---------- */
    $(function () {
        $('#x-worktime tr.timestuff').each(function () { updateRow($(this)); });
        recalcAll();
    });
})();
</script>

</div>
</div>
