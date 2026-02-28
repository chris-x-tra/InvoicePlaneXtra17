<?php
  // because of search box
  if (!isset($sort)) $sort=''; if(!isset($order)) $order='';
?>

<div class="table-responsive">
    <table class="table table-hover table-striped">
        <thead>
        <tr>
            <th><?php _trans('active'); ?></th>
            <th><a href="?sort=name&order=<?= ($sort === 'name' && $order === 'asc') ? 'desc' : 'asc' ?>">
                <?php _trans('client_name'); ?><?= do_sort_caret($sort === 'name', $order) ?></a>
            </th>

<?php if (ip_xtra() || ip_hbk()): ?>
            <th><a href="?sort=id&order=<?= ($sort === 'id' && $order === 'asc') ? 'desc' : 'asc' ?>">
                <?= _trans('customerno_short')?><?= do_sort_caret($sort === 'id', $order) ?></a>
            </th>
            <th><?= _trans('client_flags')?></th>
<?php endif; ?>

<?php if (ip_atac()): ?>
            <th><a href="?sort=id&order=<?= ($sort === 'id' && $order === 'asc') ? 'desc' : 'asc' ?>">
                <?= _trans('customerno_short')?><?= do_sort_caret($sort === 'id', $order) ?></a>
            </th>
            <th><?= _trans('hosting') ?></th>
            <th><?= _trans('ls_mandat') ?></th>
            <th><?= _trans('client_flags') ?></th>
<?php endif; ?>

            <th><?php _trans('email_address'); ?></th>

<?php
if ($einvoicing) {
?>
            <th><?php echo ' e-' . trans('invoicing') . ' ' . ucfirst(trans('version')); ?></th>
            <th><?php echo ' e-' . trans('invoicing') . ' ' . trans('active'); ?></th>
<?php
}
?>
            <th><?php _trans('phone_number'); ?></th>

            <th class="amount">
                <a href="?sort=amount&order=<?= ($sort === 'amount' && $order === 'asc') ? 'desc' : 'asc' ?>">
                <?php _trans('balance'); ?><?= do_sort_caret($sort === 'amount', $order) ?></a>
            </th>

            <th><?php _trans('options'); ?></th>
        </tr>
        </thead>
        <tbody>
<?php
$class_checks = ['fa fa-lg fa-check-square-o text-success', 'fa fa-lg fa-edit text-warning']; // e-invoice
foreach ($records as $client) {
?>
            <tr>
                <td>
<span class="user-status">
<?php
/*
// original show active code
echo ($client->client_active) ? '<span class="label active">' . trans('yes') . '</span>' : '<span class="label inactive">' . trans('no') . '</span>';
*/
?>

<?php
if($client->client_type == 1) echo '<i class="fa fa-user"></i>';
if($client->client_type == 2) echo '<i class="fa fa-truck"></i>';
echo "&nbsp;&nbsp;";
if($client->client_active)
  echo '<img src="/assets/core/img/green-ball.png" title="Aktiv" >';
else
  echo '<img src="/assets/core/img/red-ball.png" title="Inaktiv" >';
?>
</span>


                </td>

                <td><?php echo anchor('clients/view/' . $client->client_id, htmlsc(format_client($client))); ?></td>

<?php if (ip_xtra() || ip_hbk()): ?>
        <td><?php if (isset($client->customer_no)) echo $client->customer_no; ?></td>
        <td><?php if (isset($client->client_flags)) echo customer_satisfaction_smileys($client->client_flags); 
                else echo customer_satisfaction_smileys(0); ?></td>
<?php endif; ?>

<?php if (ip_atac()): ?>
        <td><?php if (isset($client->customer_no)) echo $client->customer_no; ?></td>
        <td><?php if (isset($client->contract)) echo $client->contract; ?></td>
        <td><?php if (isset($client->direct_debit)) echo $client->direct_debit; ?></td>
        <td><?php if (isset($client->client_flags)) echo client_data_processing_agreement($client->client_flags); ?></td>
<?php endif; ?>


                <td><?php _htmlsc($client->client_email); ?></td>
<?php
if ($einvoicing) {
?>
                <td><?php _htmlsc($client->client_einvoicing_version ?? ''); ?></td>
                <td>
<?php
    if (($client->client_einvoicing_active ?? 0) == 1) {
?>
                    <i class="<?php echo $class_checks[0] ?>"></i>
<?php
    } elseif (($client->client_einvoicing_version ?? '') != '') {
?>
                    <i class="<?php echo $class_checks[1] ?>"></i>
<?php
    }
?>
                </td>
<?php
}
?>
                <td><?php _htmlsc($client->client_phone ? $client->client_phone : ($client->client_mobile ? $client->client_mobile : '')); ?></td>
                <td class="amount last"><?php echo format_currency($client->client_invoice_balance); ?></td>
                <td>
                    <div class="options btn-group">
                        <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#">
                            <i class="fa fa-cog"></i> <?php _trans('options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?php echo site_url('clients/view/' . $client->client_id); ?>">
                                    <i class="fa fa-eye fa-margin"></i> <?php _trans('view'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo site_url('clients/form/' . $client->client_id); ?>">
                                    <i class="fa fa-edit fa-margin"></i> <?php _trans('edit'); ?>
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
                            <li>
                                <form action="<?php echo site_url('clients/delete/' . $client->client_id); ?>"
                                      method="POST">
                                    <?php _csrf_field(); ?>
                                    <button type="submit" class="dropdown-button"
                                            onclick="return confirm('<?php _trans('delete_client_warning'); ?>');">
                                        <i class="fa fa-trash-o fa-margin"></i> <?php _trans('delete'); ?>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
<?php
} // End foreach
?>
        </tbody>
    </table>
</div>
