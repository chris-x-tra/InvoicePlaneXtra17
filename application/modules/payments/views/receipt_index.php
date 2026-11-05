<?php
$this->load->helper('custom_values_helper');  // <- format_date
?>
<div id="headerbar">
    <h1 class="headerbar-title"><?php _trans('income_receipts'); ?></h1>

    <div class="headerbar-item pull-right">
        <a class="btn btn-sm btn-primary" href="<?php echo site_url('payments/receipt_form'); ?>">
            <i class="fa fa-plus"></i> <?php _trans('new'); ?>
        </a>
    </div>

    <div class="headerbar-item pull-right">
        <?php echo pager(site_url('payments/receipt_index'), 'mdl_receipts'); ?>
    </div>
</div>

<div id="content" class="table-content">

    <?php $this->layout->load_view('layout/alerts'); ?>

    <div id="filter_results">
        <?php $this->layout->load_view('payments/partial_receipt_table', [
            'receipts'      => $receipts,
            'receipt_types' => $receipt_types,
        ]); ?>
    </div>
</div>
