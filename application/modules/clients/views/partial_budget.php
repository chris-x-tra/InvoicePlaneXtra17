<?php
/**
 * maricare budget
 *
 * clients/views/partial_budget.php
 * Erwartet: $year, $client->client_id, $budget_types, $report['current'|'previous']
 * ($report[...] ist das Ergebnis von Budget::report($year, $client_id))
 */
echo budget_css();
?>
<div class="panel panel-default no-margin">
    <div class="panel-heading">
        <img src="/assets/core/img/mar-par-money.png"/>
        <b>Budget</b>

        <span class="pull-right">
        <b>Start-Jahr</b>
        <form method="post" action="<?php echo site_url('clients/view/' . $client->client_id . '#client-details'); ?>">
        <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
          value="<?php echo $this->security->get_csrf_hash() ?>">
        <select name="year" id="year">
        <?php for ($i = date('Y'); $i >= 2021; $i--) {
            echo '<option value="' . $i . '"' . ($i == $year ? ' selected="selected"' : '') . '>' . $i . "</option>\n";
        } ?>
        </select>
        <input class="mybtn mybtn-success" type="submit" name="btn_submit" value="<?php _trans('submit'); ?>">
        </form>
        </span>

    </div>
    <div class="panel-body">
    <table class="table table-bordered no-margin">
    <?php foreach ($report as $period): ?>
        <?php $c = $period['clients'][$client->client_id]; ?>
        <tr><td colspan="3" style="border-top: 2px solid #888;">
            Datums-Bereich <?= $period['label'] ?>
        </td></tr>
        <?php foreach ($budget_types as $key => $def): ?>
        <tr>
            <th style="text-align: right;"><?= htmlspecialchars($def['label']) ?> in <?= $period['year'] ?></th>
            <td class="td-amount"><?= format_currency($c['amounts'][$key]) ?></td>
            <td><?= budget_sparkline($c['sparklines'][$key]) ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endforeach; ?>
    </table>

    </div>
</div>
