            <tr>
		<td>
                ${client[0].client_type==1 ?
                "<i class='fa fa-user'></i>" : "<i class='fa fa-truck'></i>"
                }
                ${client[0].client_active == 1 ?
                "<img src='/assets/core/img/green-ball.png' title='Aktiv' >" :
                "<img src='/assets/core/img/red-ball.png' title='Inaktiv' >"
                }
		</td>
                <td>
			<a href="<?php echo site_url('clients/view/'); ?>${client[0].client_id}">${client.htmlsc_name}</a>
		</td>

<?php if (ip_atac() || ip_xtra() || ip_hbk()): ?>
	<td>${client[0].customer_no ? client[0].customer_no : ""}</td>
        <td>
${
    "<div class='cs-smileys' data-code='" + client[0].client_flags + "'>" +
        "<span class='smiley angry "   + (client[0].client_flags == 1 ? "active" : "") + "'>😠</span>" +
        "<span class='smiley neutral " + (client[0].client_flags == 2 ? "active" : "") + "'>😐</span>" +
        "<span class='smiley happy "   + (client[0].client_flags == 3 ? "active" : "") + "'>😄</span>" +
    "</div>"
}
        </td>
<?php endif; ?>

<?php if (ip_atac()): ?>
	<td>${client[0].contract ? client[0].contract : "" }</td>
	<td>${client[0].direct_debit ? client[0].direct_debit : "" }</td>
<?php endif; ?>

                <td>${client[0].client_email ? client[0].client_email : "" }</td>
                <td>${client[0].client_phone ? client[0].client_phone : client[0].client_mobile}</td>
                <td class="amount">${client[0].client_invoice_balance}</td>
                <td>
                    <div class="options btn-group">
                        <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#">
                            <i class="fa fa-cog"></i> <?php _trans('options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?php echo site_url('clients/view/'); ?>${client[0].client_id}">
                                    <i class="fa fa-eye fa-margin"></i> <?php _trans('view'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo site_url('clients/form/');?>${client[0].client_id}">
                                    <i class="fa fa-edit fa-margin"></i> <?php _trans('edit'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="client-create-quote"
                                   data-client-id="${client[0].client_id}">
                                    <i class="fa fa-file fa-margin"></i> <?php _trans('create_quote'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="client-create-invoice"
                                   data-client-id="${client[0].client_id}">
                                    <i class="fa fa-file-text fa-margin"></i> <?php _trans('create_invoice'); ?>
                                </a>
                            </li>
                            <li>
                                <form action="<?php echo site_url('clients/delete/');?>${client[0].client_id}"
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
