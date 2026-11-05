<?php
$this->load->helper('custom_values_helper');  // <- format_date
?>
<div id="headerbar">
    <h1 class="headerbar-title"><?php _trans('view_income_receipt'); ?></h1>
    <div class="headerbar-item pull-right">
        <div class="btn-group btn-group-sm">
            <a href="<?php echo site_url('payments/receipt_index'); ?>" class="btn btn-default">
                <?php _trans('view_all'); ?>
            </a>
            <a href="<?php echo site_url('payments/receipt_form/' . $receipt->receipt_id); ?>" class="btn btn-default">
                <i class="fa fa-edit"></i> <?php _trans('edit'); ?>
            </a>
        </div>
    </div>
</div>

<style>
    .preview-wrapper { display: flex; align-items: flex-start; gap: 1rem; }
    .preview-text { flex: 1; min-width: 50%; max-width: 60%; flex-shrink: 1; flex-grow: 0; }
    .preview-image { max-width: 40%; }
    .document-box { text-align: left; border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; background-color: #fafafa; }
    .document-caption { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; }
    .document-filename { flex: 1; text-align: left; font-weight: 500; word-break: break-word; }
    .delete-button { padding: 0.3rem 0.5rem; }
</style>

<div id="content">

    <?php $this->layout->load_view('layout/alerts'); ?>

    <div class="preview-wrapper">
        <div class="preview-text">

            <div class="panel-heading"><?php _trans('income_receipt'); ?></div>

            <table class="table no-margin">
                <tr>
                    <th><?php _trans('receipt_number'); ?></th>
                    <td><?php _htmlsc($receipt->receipt_number); ?></td>
                </tr>
                <tr>
                    <th><?php _trans('description'); ?></th>
                    <td><?php _htmlsc($receipt->receipt_description); ?></td>
                </tr>
                <tr>
                    <th><?php _trans('receipt_payer'); ?></th>
                    <td>
                        <?php if ($receipt->receipt_client_id) : ?>
                            <a href="<?php echo site_url('clients/view/' . $receipt->receipt_client_id); ?>">
                                <?php _htmlsc(format_client($receipt)); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _trans('amount'); ?></th>
                    <td><?php echo format_currency($receipt->receipt_amount); ?></td>
                </tr>
                <tr>
                    <th><?php _trans('expense_category'); ?></th>
                    <td>
                        <?php if ($receipt->receipt_category_id && isset($receipt_types[$receipt->receipt_category_id])) {
                            _htmlsc($receipt_types[$receipt->receipt_category_id]);
                        } ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _trans('receipt_date'); ?></th>
                    <td><?php echo format_date($receipt->receipt_date); ?></td>
                </tr>
                <tr>
                    <th><?php _trans('payment_bank_book_date'); ?></th>
                    <td><?php echo format_date($receipt->receipt_bank_book_date); ?></td>
                </tr>
                <tr>
                    <th><?php _trans('payment_bank_book_subject'); ?></th>
                    <td><?php _htmlsc($receipt->receipt_bank_book_subject); ?></td>
                </tr>
            </table>
        </div>

        <div class="preview-image">
            <?php _trans('documents'); ?>
            <br>
            <?php foreach ($receipt_documents as $d) :
                $file_url = site_url('payments/receipt_document/' . $d->document_id);
                ?>
                <div class="document-box">
                    <a href="<?php echo $file_url; ?>" target="_blank">
                        <?php if ($d->has_preview) : ?>
                            <img src="<?php echo $file_url . '/preview'; ?>" style="max-width: 200px" alt="">
                        <?php else : ?>
                            <i class="fa fa-file-o fa-4x"></i>
                        <?php endif; ?>
                    </a>
                    <div class="document-caption">
                        <a href="<?php echo $file_url; ?>" target="_blank" class="document-filename">
                            <?php _htmlsc(Mdl_Receipts_Documents::clean_filename($d->document_filename)); ?>
                        </a>
                        <form action="<?php echo site_url('payments/receipt_delete_document/' . $receipt->receipt_id . '/' . $d->document_id); ?>"
                              method="POST">
                            <?php _csrf_field(); ?>
                            <button type="submit" class="button delete-button"
                                    onclick="return confirm('<?php _trans('delete_record_warning'); ?>');">
                                <i class="fa fa-trash-o fa-margin"></i> <?php _trans('delete'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
