
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

    <tr><td colspan="2" style="border-top: 2px solid #888;"> Datums-Bereich
      <?php if($month==1) echo "1.$year - 12.$year";      else echo "$month.$year - " . $month-1 . "." .  $year+1; ?>
    </td></tr>
    <?php _print_budget($year,   $budget_39,     $budget_45a,     $budget_45b,     $budget_45a_45b,     $budget_125    ); ?> 

    <tr><td colspan="2" style="border-top: 2px solid #888;"> Datums-Bereich
      <?php if($month==1) echo "1." . $year-1 . " - 12." . $year-1; else echo $month . "." . $year-1 . " - ".$month-1 . ".". $year; ?>
    </td></tr>
    <?php _print_budget($year-1, $old_budget_39, $old_budget_45a, $old_budget_45b, $old_budget_45a_45b, $old_budget_125); ?>

    </table>

</div>
</div>

<?php 
/* function */
  function _print_budget($year, $budget_39, $budget_45a, $budget_45b, $budget_45a_45b, $budget_125) { ?>
<tr>
<th style="text-align: right;">
1) Stundenweise Verhinderungspflege nach §39b SGb XI in <?php  echo $year; ?>
</th>
<td class="td-amount" >
<?php echo format_currency($budget_39); ?>
</td>
</tr>

<tr>
<th style="text-align: right;">
2) Entlastungshilfe mit Umwidmung nach §45a SGb XI in <?php echo $year; ?>
</th>
<td class="td-amount">
<?php echo format_currency($budget_45a); ?>
</td>
</tr>

<tr>
<th style="text-align: right;">
3) Entlastungshilfe nach §45b SGb XI in <?php  echo $year; ?>
</th>
<td class="td-amount">
<?php echo format_currency($budget_45b); ?>
</td>
</tr>

<tr>
<th style="text-align: right;">
4) Rechnungen, mit Kombination §45a und §45b in <?php echo $year; ?>
</th>
<td class="td-amount">
<?php echo format_currency($budget_45a_45b,); ?>
</td>
</tr>

<tr>
<th style="text-align: right;">
5) Leistungsvereinbarung gem&auml;&szlig; §125 SGB XI in <?php  echo $year; ?>
</th>
<td class="td-amount">
<?php echo format_currency($budget_125); ?>
</td>
</tr>
<?php } 
?>

