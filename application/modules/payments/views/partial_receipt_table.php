<div class="table-responsive">
    <table class="table table-hover table-striped">

        <thead>
        <tr>
            <th><?php _trans('receipt_date'); ?></th>
            <th><?php _trans('description'); ?></th>
            <th><i class="fa fa-file"></i></th>
            <th><?php _trans('receipt_payer'); ?></th>
            <th style="text-align: right;"><?php _trans('amount'); ?></th>
            <th><?php _trans('account'); ?></th>
            <th><?php _trans('payment_bank_book_date'); ?></th>
            <th><?php _trans('payment_bank_book_subject'); ?></th>
            <th><?php _trans('options'); ?></th>
        </tr>
        </thead>

        <tbody>
        <?php
        $receipt_idx = 1;
        $receipt_count = count($receipts);
        $receipt_list_split = $receipt_count > 3 ? $receipt_count / 2 : 9999;
        foreach ($receipts as $receipt) {
            $dropup = $receipt_idx > $receipt_list_split;
            ?>
            <tr>
                <td><?php echo format_date($receipt->receipt_date); ?></td>

                <td>
                    <a href="<?php echo site_url('payments/receipt_view/' . $receipt->receipt_id); ?>"
                       title="<?php _trans('description'); ?>">
                        <?php _htmlsc($receipt->receipt_description); ?>
                    </a>
                </td>

                <td>
                    <?php if ($receipt->has_documents > 0) : ?>
                        <i class="fa fa-file"></i>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if ($receipt->receipt_client_id) : ?>
                        <a href="<?php echo site_url('clients/view/' . $receipt->receipt_client_id); ?>">
                            <?php _htmlsc(format_client($receipt)); ?>
                        </a>
                    <?php endif; ?>
                </td>

                <td class="amount"><?php echo format_currency($receipt->receipt_amount); ?></td>

                <td>
                    <?php if ($receipt->receipt_category_id && isset($receipt_types[$receipt->receipt_category_id])) {
                        _htmlsc($receipt_types[$receipt->receipt_category_id]);
                    } ?>
                </td>

                <td><?php echo format_date($receipt->receipt_bank_book_date); ?></td>

                <td><?php _htmlsc($receipt->receipt_bank_book_subject); ?></td>

                <td>
                    <div class="options btn-group<?php echo $dropup ? ' dropup' : ''; ?>">
                        <a class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" href="#">
                            <i class="fa fa-cog"></i> <?php _trans('options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?php echo site_url('payments/receipt_form/' . $receipt->receipt_id); ?>">
                                    <i class="fa fa-edit fa-margin"></i> <?php _trans('edit'); ?>
                                </a>
                            </li>
                            <li>
                                <form action="<?php echo site_url('payments/receipt_delete/' . $receipt->receipt_id); ?>"
                                      method="POST">
                                    <?php _csrf_field(); ?>
                                    <button type="submit" class="dropdown-button"
                                            onclick="return confirm('<?php _trans('delete_record_warning'); ?>');">
                                        <i class="fa fa-trash-o fa-margin"></i> <?php _trans('delete'); ?>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
            <?php
            $receipt_idx++;
        } ?>
        </tbody>

    </table>
</div>
