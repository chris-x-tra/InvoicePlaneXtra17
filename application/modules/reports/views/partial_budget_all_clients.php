<?php
/**
 * maricare budget
 *
 * reports/views/partial_budget_all_clients.php
 * Erwartet: $budget_types, $budget (= Budget::report($year))
 */
echo budget_css();
?>
<style>
.budget-all th, .budget-all td { vertical-align: middle !important; }
.budget-all .amount { text-align: right; font-weight: bold; }
.budget-all tfoot td { border-top: 2px solid #888; font-weight: bold; }
</style>

<div class="panel panel-default">
    <div class="panel-heading">
        <b>Budgetverteilung aller Kunden</b> &ndash; <?= $budget['label'] ?>

        <form method="get" class="pull-right form-inline" style="display:inline;">
            <b>Start-Jahr</b>
            <select name="year" onchange="this.form.submit()">
                <?php for ($i = (int) date('Y'); $i >= 2021; $i--): ?>
                    <option value="<?= $i ?>" <?= $i == $budget['year'] ? 'selected="selected"' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>

    <div class="panel-body table-responsive">
        <table class="table table-bordered table-condensed budget-all no-margin">
            <thead>
            <tr>
                <th>Kunde</th>
                <?php foreach ($budget_types as $def): ?>
                    <th title="<?= htmlspecialchars($def['label']) ?>"><?= htmlspecialchars($def['short']) ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($budget['clients'] as $client_id => $c): ?>
                <tr>
                    <td><a href="<?= site_url('clients/view/' . $client_id) ?>"><?= htmlspecialchars($c['name']) ?></a></td>
                    <?php foreach ($budget_types as $key => $def): ?>
                        <td>
                            <?php if ($c['amounts'][$key] != 0): ?>
                                <div class="amount"><?= format_currency($c['amounts'][$key]) ?></div>
                                <?= budget_sparkline($c['sparklines'][$key]) ?>
                            <?php else: ?>
                                <span class="text-muted">&ndash;</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($budget['clients'])): ?>
                <tr><td colspan="<?= count($budget_types) + 1 ?>" class="text-muted">Keine Rechnungen in diesem Zeitraum.</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <td>Summe</td>
                <?php foreach ($budget_types as $key => $def): ?>
                    <td class="amount"><?= format_currency($budget['totals'][$key]) ?></td>
                <?php endforeach; ?>
            </tr>
            </tfoot>
        </table>
    </div>
</div>
