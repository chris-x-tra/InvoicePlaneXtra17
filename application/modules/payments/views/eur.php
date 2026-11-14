<?php
$month_names = [1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
$fmt = static function ($v) {
    return number_format((float) $v, 2, ',', '.') . ' €';
};

// Kontenliste einer Monats-/Jahresstruktur: nach Kontonummer sortiert, "nicht zugeordnet" zuletzt
$sorted = static function (array $accounts) use ($account_labels) {
    uksort($accounts, static function ($a, $b) use ($account_labels) {
        if ($a === 0 || $b === 0) {
            return ($a === 0) <=> ($b === 0);
        }

        return strcmp($account_labels[$a] ?? 'zzz' . $a, $account_labels[$b] ?? 'zzz' . $b);
    });

    return $accounts;
};
$account_title = static function ($id) use ($account_labels) {
    if ($id === 0) {
        return 'Nicht zugeordnet';
    }

    return $account_labels[$id] ?? ('Konto #' . $id . ' (gelöscht)');
};

// Tabelle "Konto / Betrag" mit Unterzeilen (Produktfamilie bzw. Belege); bei USt zusaetzlich Netto / USt / Brutto
$render_accounts = static function (array $accounts, $total, $tax) use ($sorted, $account_title, $fmt, &$show_vat) {
    if ( ! $accounts) {
        echo '<p class="text-muted" style="margin:0">–</p>';

        return;
    }
    $cells = static function ($gross, $t, $bold) use ($fmt, &$show_vat) {
        $o = $bold ? ['<strong>', '</strong>'] : ['', ''];
        if ( ! $show_vat) {
            return '<td class="text-right">' . $o[0] . $fmt($gross) . $o[1] . '</td>';
        }

        return '<td class="text-right">' . $o[0] . $fmt($gross - $t) . $o[1] . '</td>'
            . '<td class="text-right">' . $o[0] . $fmt($t) . $o[1] . '</td>'
            . '<td class="text-right">' . $o[0] . $fmt($gross) . $o[1] . '</td>';
    };
    echo '<table class="table table-condensed" style="margin-bottom:0">';
    if ($show_vat) {
        echo '<thead><tr><th></th><th class="text-right">Netto</th><th class="text-right">USt</th><th class="text-right">Brutto</th></tr></thead>';
    }
    echo '<tbody>';
    foreach ($sorted($accounts) as $id => $a) {
        $cls = $id === 0 ? ' class="text-danger"' : '';
        echo '<tr' . $cls . '><td><strong>' . htmlsc($account_title((int) $id)) . '</strong></td>'
            . $cells($a['total'], $a['tax'], true) . '</tr>';
        $lines = $a['lines'];
        ksort($lines);
        foreach ($lines as $label => $v) {
            echo '<tr class="text-muted' . ($id === 0 ? ' text-danger' : '') . '"><td style="padding-left:25px" class="text-muted">' . htmlsc($label) . '</td>'
                . $cells($v['total'], $v['tax'], false) . '</tr>';
        }
    }
    echo '<tr style="border-top:2px solid #999"><td><strong>Summe</strong></td>' . $cells($total, $tax, true) . '</tr>';
    echo '</tbody></table>';
};

// Warnhinweis: Betraege ohne Konto
$unassigned_income = 0.0;
$unassigned_expense = 0.0;
foreach ($income_breakdown as $accs) {
    $unassigned_income += $accs[0]['total'] ?? 0;
}
foreach ($expense_breakdown as $accs) {
    $unassigned_expense += $accs[0]['total'] ?? 0;
}

$year_income  = Mdl_Payment_Eur::breakdown_year($income_breakdown);
$year_expense = Mdl_Payment_Eur::breakdown_year($expense_breakdown);

// Umsatzsteuer: Spalten nur, wenn im Jahr USt/Vorsteuer vorkommt (Instanzen ohne USt bleiben unveraendert)
$tax_income  = Mdl_Payment_Eur::tax_by_month($income_breakdown);
$tax_expense = Mdl_Payment_Eur::tax_by_month($expense_breakdown);
$show_vat    = abs(array_sum($tax_income)) >= 0.005 || abs(array_sum($tax_expense)) >= 0.005;
$colspan     = $show_vat ? 6 : 4;
?>
<div id="headerbar">
  <h1 class="headerbar-title"><?php _trans('eur_for_the_year'); ?> <?= (int) $year ?> </h1>

    <div class="headerbar-item pull-right">
  <?php for ($y = date('Y'); $y >= date('Y') - 10; $y--): ?>
    <span>
    <a href="<?php echo site_url('payments/eur/' . $y); ?>" class="btn btn-default<?= $y == $year ? ' active' : '' ?>">
      <?= $y ?>
    </a>
    </span>
  <?php endfor; ?>
    </div>

    <div class="headerbar-item pull-right">
        <a href="<?php echo site_url('payments/eur_details/' . (int) $year); ?>" class="btn btn-default">Details</a>
        <button type="button" class="btn btn-default" id="eur_toggle_all">Alle auf-/zuklappen</button>
    </div>
</div>

<div id="content" class="table-content">

    <?php $this->layout->load_view('layout/alerts'); ?>

    <?php if (abs($unassigned_income) >= 0.005 || abs($unassigned_expense) >= 0.005): ?>
    <div class="alert alert-warning">
        <strong>Nicht zugeordnete Beträge:</strong>
        <?php if (abs($unassigned_income) >= 0.005): ?>
            Einnahmen <?= $fmt($unassigned_income) ?>
            (Produktfamilien ein Einnahmekonto zuweisen bzw. Rechnungspositionen mit Produkten erfassen)
        <?php endif; ?>
        <?php if (abs($unassigned_expense) >= 0.005): ?>
            <?= abs($unassigned_income) >= 0.005 ? '·' : '' ?> Ausgaben <?= $fmt($unassigned_expense) ?>
            (Ausgaben ohne Konto)
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <h4>Monatsübersicht</h4>
    <div class="table-responsive">
    <table class="table table-hover" id="eur_months">
        <thead>
        <tr>
            <th style="width:30px"></th>
            <th>Monat</th>
            <th class="text-right">Einnahmen<?= $show_vat ? ' (brutto)' : '' ?></th>
            <?php if ($show_vat): ?><th class="text-right text-muted">darin USt</th><?php endif; ?>
            <th class="text-right">Ausgaben<?= $show_vat ? ' (brutto)' : '' ?></th>
            <?php if ($show_vat): ?><th class="text-right text-muted">darin Vorsteuer</th>
            <th class="text-right" title="Umsatzsteuer minus Vorsteuer; positiv = an das Finanzamt zu zahlen">USt-Saldo</th><?php endif; ?>
            <th class="text-right">Überschuss</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $all_income = 0; $all_expenses = 0; $all_tax_i = 0; $all_tax_e = 0;
        for ($i = 1; $i <= 12; $i++):
            $all_income   += $income[$i];
            $all_expenses += $expenses[$i];
            $all_tax_i    += $tax_income[$i];
            $all_tax_e    += $tax_expense[$i];
            $has_detail = ! empty($income_breakdown[$i]) || ! empty($expense_breakdown[$i]);
            $excess = $income[$i] - $expenses[$i];
            ?>
            <tr class="eur-month<?= $has_detail ? '' : ' text-muted' ?>" data-month="<?= $i ?>"
                <?= $has_detail ? 'style="cursor:pointer"' : '' ?>>
                <td><?php if ($has_detail): ?><i class="fa fa-chevron-right eur-chevron"></i><?php endif; ?></td>
                <td><?= $month_names[$i] ?></td>
                <td class="text-right"><?= $fmt($income[$i]) ?></td>
                <?php if ($show_vat): ?><td class="text-right text-muted"><?= $fmt($tax_income[$i]) ?></td><?php endif; ?>
                <td class="text-right"><?= $fmt($expenses[$i]) ?></td>
                <?php if ($show_vat): ?><td class="text-right text-muted"><?= $fmt($tax_expense[$i]) ?></td>
                <td class="text-right"><?= $fmt($tax_income[$i] - $tax_expense[$i]) ?></td><?php endif; ?>
                <td class="text-right"><?= $fmt($excess) ?></td>
            </tr>
            <?php if ($has_detail): ?>
            <tr class="eur-detail" id="eur_detail_<?= $i ?>" style="display:none">
                <td></td>
                <td colspan="<?= $colspan ?>" style="background:#fafafa">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Einnahmen nach Konten</h5>
                            <?php $render_accounts($income_breakdown[$i] ?? [], $income[$i], $tax_income[$i]); ?>
                        </div>
                        <div class="col-md-6">
                            <h5>Ausgaben nach Konten</h5>
                            <?php $render_accounts($expense_breakdown[$i] ?? [], $expenses[$i], $tax_expense[$i]); ?>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
        <?php endfor; ?>
        <tr style="border-top: 2px solid black;">
            <td></td>
            <td><strong>Summe</strong></td>
            <td class="text-right"><strong><?= $fmt($all_income) ?></strong></td>
            <?php if ($show_vat): ?><td class="text-right text-muted"><strong><?= $fmt($all_tax_i) ?></strong></td><?php endif; ?>
            <td class="text-right"><strong><?= $fmt($all_expenses) ?></strong></td>
            <?php if ($show_vat): ?><td class="text-right text-muted"><strong><?= $fmt($all_tax_e) ?></strong></td>
            <td class="text-right"><strong><?= $fmt($all_tax_i - $all_tax_e) ?></strong></td><?php endif; ?>
            <td class="text-right"><strong><?= $fmt($all_income - $all_expenses) ?></strong></td>
        </tr>
        </tbody>
    </table>
    </div>

    <h4 style="margin-top:30px">Jahresübersicht <?= (int) $year ?> nach Konten</h4>
    <div class="row">
        <div class="col-md-6">
            <h5>Einnahmen</h5>
            <?php $render_accounts($year_income, $all_income, $all_tax_i); ?>
        </div>
        <div class="col-md-6">
            <h5>Ausgaben</h5>
            <?php $render_accounts($year_expense, $all_expenses, $all_tax_e); ?>
        </div>
    </div>
    <p style="margin-top:15px"><strong>Überschuss <?= (int) $year ?>:</strong> <?= $fmt($all_income - $all_expenses) ?>
    <?php if ($show_vat): ?>
        <br><span class="text-muted">Davon ohne Umsatzsteuer (netto):
        <?= $fmt(($all_income - $all_tax_i) - ($all_expenses - $all_tax_e)) ?> ·
        USt-Saldo <?= $fmt($all_tax_i - $all_tax_e) ?>
        (vereinnahmte USt minus Vorsteuer; Zahlungen an das Finanzamt als Ausgabe auf einem Steuerkonto buchen)</span>
    <?php endif; ?>
    </p>

    <p class="text-muted small">
        Zufluss-/Abflussprinzip: Rechnungen zählen zum Zahlungsdatum, Belege und Ausgaben zum Bank-Buchungsdatum.
        Teilzahlungen werden anteilig nach den Positionsbeträgen der Rechnung auf die Produktfamilien und damit auf deren Einnahmekonten verteilt.
        Alle Beträge sind Bruttobeträge (wie gezahlt bzw. bezahlt); die Umsatzsteuer ist darin enthalten und wird getrennt ausgewiesen.
    </p>
</div>

<script>
    (function () {
        var table = document.getElementById('eur_months');
        function setOpen(row, open) {
            var detail = document.getElementById('eur_detail_' + row.getAttribute('data-month'));
            if (!detail) { return; }
            detail.style.display = open ? '' : 'none';
            var ch = row.querySelector('.eur-chevron');
            if (ch) { ch.className = 'fa eur-chevron ' + (open ? 'fa-chevron-down' : 'fa-chevron-right'); }
        }
        function isOpen(row) {
            var d = document.getElementById('eur_detail_' + row.getAttribute('data-month'));
            return d && d.style.display !== 'none';
        }
        table.addEventListener('click', function (e) {
            var row = e.target.closest('tr.eur-month');
            if (row) { setOpen(row, !isOpen(row)); }
        });
        document.getElementById('eur_toggle_all').addEventListener('click', function () {
            var rows = table.querySelectorAll('tr.eur-month');
            var anyClosed = false, i;
            for (i = 0; i < rows.length; i++) {
                if (document.getElementById('eur_detail_' + rows[i].getAttribute('data-month')) && !isOpen(rows[i])) { anyClosed = true; }
            }
            for (i = 0; i < rows.length; i++) { setOpen(rows[i], anyClosed); }
        });
    })();
</script>
