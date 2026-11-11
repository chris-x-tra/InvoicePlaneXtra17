<?php
/*
 * Eine Tabellenzeile der Arbeitszeiteingabe (wird von form.php je Eintrag/Tag geladen).
 *
 * erwartet: $day, $dow ('Mo'..'So'), $idx (0 = erste Zeile des Tages), $t (Eintrag oder null),
 *           $is_today, $holiday (Name oder ''), $worktypes, $client_labels, $time_step
 */
$x_from   = $t ? substr($t->timesheet_start, 0, 5) : '00:00';
$x_to     = $t ? substr($t->timesheet_end, 0, 5)   : '00:00';
$x_type   = $t ? (string)$t->timesheet_type   : '';
$x_remark = $t ? (string)$t->timesheet_remark : '';
$x_km     = $t ? (int)$t->timesheet_km        : 0;
$x_client = $t ? (int)$t->timesheet_clientid  : 0;
$x_uuid   = ($t && $t->timesheet_uuid) ? $t->timesheet_uuid : Mdl_Timesheets::new_uuid();

// Kundenname fuer das Suchfeld (Fallback: nur die ID, falls der Kunde nicht mehr existiert)
$cur_client = '';
if ($x_client > 0) $cur_client = $client_labels[$x_client] ?? ('#' . $x_client);
?>
<tr class="timestuff<?= $idx === 0 ? ' day-first' : '' ?>" data-day="<?= (int)$day ?>" data-persisted="<?= $t ? '1' : '0' ?>">
    <!-- Werkzeuge -->
    <td><div class="button-cell">
        <?php if ($idx === 0): ?>
        <button type="button" class="btn-ins button" title="Zeile einfügen"><i class="fa fa-plus"></i></button>
        <?php else: ?>
        <button type="button" class="btn-del button" title="Zeile löschen"><i class="fa fa-trash"></i></button>
        <?php endif; ?>
        <button type="button" class="btn-reset button" title="Werte zurücksetzen"><i class="fa fa-eraser"></i></button>
    </div></td>

    <!-- Tag -->
    <td class="dow-<?= $dow ?><?= $is_today ? ' is-today' : '' ?><?= !empty($holiday) ? ' is-holiday' : '' ?>"<?= !empty($holiday) ? ' title="' . html_escape($holiday) . '"' : '' ?>>
        <?= $dow ?>,&nbsp;<?= (int)$day ?>.<?php if (!empty($holiday) && $idx === 0): ?> <small class="holiday-name"><?= html_escape($holiday) ?></small><?php endif; ?>
        <input class="x_day" type="hidden" value="<?= (int)$day ?>">
        <input class="x_uuid" type="hidden" value="<?= html_escape($x_uuid) ?>">
    </td>

    <!-- Typ -->
    <td>
        <select class="x_type form-control">
            <option value=""<?= $x_type === '' ? ' selected="selected"' : '' ?>>---</option>
            <?php foreach ($worktypes as $code => $wt): ?>
            <option value="<?= $code ?>"<?= $x_type === $code ? ' selected="selected"' : '' ?>><?= html_escape($wt['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </td>

    <!-- Zeiten (native Zeitfelder) -->
    <td><input class="x_from form-control" type="time" step="<?= (int)$time_step ?>" value="<?= $x_from ?>"></td>
    <td><input class="x_to form-control" type="time" step="<?= (int)$time_step ?>" value="<?= $x_to ?>"></td>
    <td><input class="x_hours form-control" type="time" step="<?= (int)$time_step ?>" value="00:00"></td>

    <!-- Tagessumme (nur erste Zeile des Tages traegt die ID) -->
    <?php if ($idx === 0): ?>
    <td class="day-sum" id="sum_<?= (int)$day ?>"></td>
    <?php else: ?>
    <td class="day-sum"></td>
    <?php endif; ?>

    <!-- Kunde -->
    <td>
        <div class="autocomplete-wrapper">
            <input type="text" class="searchInput form-control" value="<?= html_escape($cur_client) ?>"
                   placeholder="Kundenname..." autocomplete="off">
            <input type="hidden" class="kundenId" value="<?= $x_client > 0 ? $x_client : '' ?>">
            <div class="results"></div>
        </div>
    </td>

    <!-- km / Bemerkung -->
    <td><input type="number" min="0" step="1" class="x_km form-control" value="<?= $x_km ?>"></td>
    <td><input type="text" maxlength="255" class="x_remark form-control" value="<?= html_escape($x_remark) ?>"></td>
</tr>
