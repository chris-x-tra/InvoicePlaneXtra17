
<table class="table table-bordered no-margin">

<tr><td style="text-align: right;"><i>Start-Jahr</i></td><td>
<!--
clients/view/2#client-details
-->
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
</td></tr>

<?php  
/*
                'year'           => $year,
                'budget_39'      => $budget_39,
                'budget_45a'     => $budget_45a,
                'budget_45b'     => $budget_45b,
                'budget_45a_45b' => $budget_45a_45b,
                'old_budget_39'  => $old_budget_39,
                'old_budget_45a' => $old_budget_45a,
                'old_budget_45b' => $old_budget_45b,
                'old_budget_45a_45b'=> $old_budget_45a_45b
*/ 
_print_budget($year,   $budget_39,     $budget_45a,     $budget_45b,     $budget_45a_45b,     $budget_125    );
_print_budget($year-1, $old_budget_39, $old_budget_45a, $old_budget_45b, $old_budget_45a_45b, $old_budget_125);

?>

</table>

<?php function _print_budget($year, $budget_39, $budget_45a, $budget_45b, $budget_45a_45b, $budget_125) { ?>
<tr>
<th style="text-align: right;">
Verbrauchtes Budget nach Paragraph in <?php echo $year; ?>
<br />
1) Stundenweise Verhinderungspflege §39b SGb XI in <?php  echo $year; ?>
</th>
<td class="td-amount">
<?php echo format_currency($budget_39); ?>
</td>
</tr>

<tr>
<th style="text-align: right;">
2) Entlastungshilfe nach §45a SGb XI mit Umwidmung in <?php echo $year; ?>
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
4) Rechnungen, in denen beides 45a und 45b kombiniert ist in <?php echo $year; ?>
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

