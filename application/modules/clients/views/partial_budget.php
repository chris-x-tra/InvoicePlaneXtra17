<style>
.sparkline-months {
    display: inline-flex;
    gap: 2px;
}
.sparkline-block {
    display: inline-block;
    width: 12px;
    height: 18px;
    border-radius: 2px;
    cursor: default;
}
.sparkline-block:hover {
    outline: 1px solid #333;
}
</style>

<div class="panel panel-default no-margin">
    <div class="panel-heading">

        <img src="/assets/core/img/mar-par-money.png"/ >
        <b>Budget</b>

        <span class="pull-right">
        <b>Start-Jahr</b>
        <form method="post" action="<?php echo site_url('clients/view/'.$client->client_id.'#client-details'); ?>">
        <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
          value="<?php echo $this->security->get_csrf_hash() ?>">
        <select name="year" id="year">
        <?php for ($i=date('Y'); $i>=2021; $i--) {
          echo '<option value="'.$i.'" ';
          if($i==$year) echo ' selected="selected" ';
          echo '>'.$i.'</option>'."\n";
        } ?>
        </select>
        <input class="mybtn mybtn-success" type="submit" name="btn_submit" value="<?php _trans('submit'); ?>">
        </form>
        </span>

    </div>
    <div class="panel-body">

    <table class="table table-bordered no-margin">
    <?php foreach ($report as $period_key => $data): ?>
        <tr><td colspan="3" style="border-top: 2px solid #888;">
            Datums-Bereich
            <?php
            $y = $data['year'];
            echo ($month == 1)
                ? "1.$y - 12.$y"
                : "$month.$y - " . ($month - 1) . "." . ($y + 1);
            ?>
        </td></tr>
        <?php foreach ($budget_types as $key => $def): ?>
        <tr>
            <th style="text-align: right;"><?= $def['label'] ?> in <?= $y ?></th>
            <td class="td-amount"><?= format_currency($data['amounts'][$key]) ?></td>
            <td><?= _sparkline_months($data['sparklines'][$key]) ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endforeach; ?>
    </table>

    </div>
</div>
<?php
function _sparkline_months(array $amounts)
{
    if (empty($amounts)) return '';
    $max = max($amounts) ?: 1;

    $html = '<div class="sparkline-months">';
    foreach ($amounts as $ym => $amount) {
        $month_label = date('M Y', strtotime($ym . '-01'));

        if ($amount == 0) {
            $style = 'background-color:#fff; border:1px solid #ccc;';
        } else {
            $intensity = $amount / $max;
            $lightness = 90 - round($intensity * 60);
            $color = "hsl(210, 70%, {$lightness}%)";
            $style = "background-color:{$color};";
        }

        $html .= sprintf(
            '<span class="sparkline-block" style="%s" title="%s: %s"></span>',
            $style, $month_label, format_currency($amount)
        );
    }
    $html .= '</div>';
    return $html;
}
