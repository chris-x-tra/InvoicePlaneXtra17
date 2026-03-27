
<!-- modules/clients/views/partial_client_table.php -->

<?php function do_client_caret($cond, $order) {
  if(!$cond) return;
  if ($order == 'desc') echo ' <i class="fa fa-caret-down"></i> ';
  if ($order == 'asc') echo ' <i class="fa fa-caret-up"></i>';
}

// because of search box
if (!isset($sort)) $sort=''; if(!isset($order)) $order='';

$this->load->helper('custom_values_helper'); ?>
<div class="table-responsive">
    <table class="table table-hover table-striped">
        <thead>
        <tr>
<!--
<th>ID</th>
-->

<th></th>
<th> <a href="?sort=id&order=<?= ($sort === 'id' && $order === 'asc') ? 'desc' : 'asc' ?>">
<?= _trans('customerno_short')?><?= do_client_caret($sort === 'id', $order) ?></a></th>


	<th><?php _trans('flags'); ?></th>
	<th> <a href="?sort=name&order=<?= ($sort === 'name' && $order === 'asc') ? 'desc' : 'asc' ?>">
		<?php _trans('client_name'); ?><?= do_client_caret($sort === 'name', $order) ?></a>
	</th>

            <th>
		<a href="?sort=carelevel&order=<?= ($sort === 'carelevel' && $order === 'asc') ? 'desc' : 'asc' ?>">
		<?php _trans('care_level'); ?> <?= do_client_caret($sort === 'carelevel', $order) ?></a> 
	</th>
            <th><?php _trans('care_level_since'); ?></th>
<!--
            <th><?php _trans('memo'); ?></th>
-->
            <th><?php _trans('birthdate'); ?></th>
            <th><?php _trans('phone_number'); ?></th>
            <th><?php _trans('invoice_addr_name'); ?></th>
            <th class="amount">
		<a href="?sort=amount&order=<?= ($sort === 'amount' && $order === 'asc') ? 'desc' : 'asc' ?>">
		<?php _trans('balance'); ?><?= do_client_caret($sort === 'amount', $order) ?></a> 
	    </th>
            <th><?php _trans('options'); ?></th>
        </tr>
        </thead>


        <tbody>
        <?php foreach ($records as $client) : ?>
            <tr>
<!--
<td><?php echo $client->client_id; ?></td>
-->

<td>
<?php
if($client->client_active) 
  echo '<img src="/assets/core/img/green-ball.png" title="Aktiv" >';
else
  echo '<img src="/assets/core/img/red-ball.png" title="Inaktiv" >';
?>
</td>

<td>
  <?php if (isset($client->customerno)) echo  join_dash($client->customerno); ?>
</td>

<td >
<?php echo show_flags($client->flags); ?>
</td>

<td>
<?php echo anchor('clients/view/' . $client->client_id, htmlsc(format_client($client))); 
echo "<br>\n ";
echo $client->client_address_1;
echo " ";
echo $client->client_address_2;
echo "<br>\n ";
echo $client->client_zip;
echo " ";
echo $client->client_city;
?>
</td>

<td>
<?php if (isset($client->carelevel)) echo $client->carelevel; ?>
&nbsp;
<input title="Bestatigung" type="checkbox" disabled readonly <?php if ($client->flags & 128) echo 'checked="checked"' ?> >
</td>

<td><?php if (isset($client->carelevel_since) && $client->carelevel_since) echo format_date($client->carelevel_since); ?></td>

<!--
<td><?php if (isset($client->memo)) echo $client->memo; ?></td>
-->

		<td><?php echo format_date($client->client_birthdate); ?></td>
                <td><?php _htmlsc($client->client_phone ? $client->client_phone : ($client->client_mobile ? $client->client_mobile : '')); ?></td>
		<td><?php echo _htmlsc($client->invoice_addr_name); ?></td>
                <td class="amount"><?php echo format_currency($client->client_invoice_balance); ?></td>
                <td>
                    <div class="options btn-group">
                        <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#">
                            <i class="fa fa-cog"></i> <?php _trans('options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?php echo site_url('employee/clients/view/' . $client->client_id); ?>">
                                    <i class="fa fa-eye fa-margin"></i> <?php _trans('view'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="client-create-quote"
                                   data-client-id="<?php echo $client->client_id; ?>">
                                    <i class="fa fa-file fa-margin"></i> <?php _trans('create_quote'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="client-create-invoice"
                                   data-client-id="<?php echo $client->client_id; ?>">
                                    <i class="fa fa-file-text fa-margin"></i> <?php _trans('create_invoice'); ?>
                                </a>
                            </li>

                        </ul>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
