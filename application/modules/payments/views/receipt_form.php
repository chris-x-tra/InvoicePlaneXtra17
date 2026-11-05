<style>
.required {
    position: relative;
    cursor: help;
}
.required::before {
    content: "\f06a"; /* fa-exclamation-circle */
    font-family: "FontAwesome";
    color: black;
}
.required:hover::after {
    content: "Pflichtfeld";
    position: absolute;
    top: -2.5em;
    left: 1.5em;
    background: rgba(0,0,0,0.75);
    color: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    white-space: nowrap;
    font-size: 0.8em;
    z-index: 10;
}
#receipt_client_name {
    cursor: pointer;
    background-color: #fcfcfc;
}
#receipt_client_name:hover {
    background-color: #f5f5f5;
}
</style>

<div id="headerbar">
    <h1 class="headerbar-title"><?php _trans('income_receipt_form'); ?></h1>
</div>

<div id="content">
    <?php $this->layout->load_view('layout/alerts'); ?>
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-5">
            <h3>
                <?php if ($receipt_id) : ?>
                    <?php _trans('edit_income_receipt'); ?>
                <?php else : ?>
                    <?php _trans('add_income_receipt'); ?>
                <?php endif; ?>
            </h3>
            <br/>

            <form method="post" enctype="multipart/form-data"
                  action="<?php echo site_url('payments/receipt_form' . ($receipt_id ? '/' . $receipt_id : '')); ?>">

                <?php _csrf_field(); ?>

                <div class="form-group">
                    <label for="receipt_number"><?php _trans('receipt_number'); ?></label>
                    <input id="receipt_number" name="receipt_number" type="text" class="form-control"
                           value="<?php echo $this->mdl_receipts->form_value('receipt_number', true); ?>">
                </div>

                <div class="form-group">
                    <label for="receipt_description">
                        <?php _trans('description'); ?> <span class="required"></span>
                    </label>
                    <input id="receipt_description" name="receipt_description" type="text" class="form-control" required
                           value="<?php echo $this->mdl_receipts->form_value('receipt_description', true); ?>">
                </div>

                <div class="form-group">
                    <label for="receipt_date">
                        <?php _trans('receipt_date'); ?> <span class="required"></span>
                    </label>
                    <input id="receipt_date" name="receipt_date" type="date" class="form-control" required
                           value="<?php echo $this->mdl_receipts->form_value('receipt_date', true) ?: date('Y-m-d'); ?>">
                </div>

                <!-- Zahler (optional): Hidden-Feld fuer die ID, Readonly-Feld fuer den Namen -->
                <input id="receipt_client_id" name="receipt_client_id" type="hidden"
                       value="<?php echo $this->mdl_receipts->form_value('receipt_client_id', true); ?>">

                <div class="form-group">
                    <label for="receipt_client_name"><?php _trans('receipt_payer'); ?></label>
                    <div class="input-group">
                        <input id="receipt_client_name" type="text" class="form-control"
                               value="<?php echo htmlspecialchars($payer_name ?? '', ENT_QUOTES); ?>" readonly>
                        <span class="input-group-addon" id="receipt_client_search" style="cursor: pointer;"
                              title="<?php _trans('receipt_find_payer'); ?>">
                            <i class="fa fa-search"></i>
                        </span>
                        <span class="input-group-addon" id="receipt_client_clear" style="cursor: pointer;"
                              title="<?php _trans('receipt_clear_payer'); ?>">
                            <i class="fa fa-times"></i>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="receipt_amount">
                        <?php _trans('amount'); ?> <span class="required"></span>
                    </label>
                    <div class="input-group">
                        <input id="receipt_amount" name="receipt_amount" type="text" class="form-control" required
                               value="<?php echo format_amount($this->mdl_receipts->form_value('receipt_amount')); ?>">
                        <span class="input-group-addon"><?php echo get_setting('currency_symbol'); ?></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="receipt_category_id">
                        <?php _trans('expense_category'); ?> <span class="required"></span>
                    </label>
                    <select name="receipt_category_id" id="receipt_category_id" class="form-control simple-select" required>
                        <?php foreach ($receipt_types as $key => $type) { ?>
                            <option value="<?php echo $key; ?>"
                                <?php check_select($this->mdl_receipts->form_value('receipt_category_id'), $key); ?>>
                                <?php _htmlsc($type); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="receipt_bank_book_date"><?php _trans('payment_bank_book_date'); ?> <span class="required"></span></label>
                    <input id="receipt_bank_book_date" name="receipt_bank_book_date" type="date" class="form-control"
                           value="<?php echo $this->mdl_receipts->form_value('receipt_bank_book_date', true); ?>">
                    <p class="help-block"><?php _trans('bank_book_date_hint'); ?></p>
                </div>

                <div class="form-group">
                    <label for="receipt_bank_book_subject"><?php _trans('payment_bank_book_subject'); ?></label>
                    <input id="receipt_bank_book_subject" name="receipt_bank_book_subject" type="text" class="form-control"
                           value="<?php echo $this->mdl_receipts->form_value('receipt_bank_book_subject', true); ?>">
                </div>

                <?php if ( ! empty($receipt_documents)) : ?>
                    <label><?php _trans('documents'); ?></label>
                    <br/>
                    <?php foreach ($receipt_documents as $d) : ?>
                        <span class="document-filename"><?php _htmlsc(Mdl_Receipts_Documents::clean_filename($d->document_filename)); ?></span>
                        <br/>
                    <?php endforeach; ?>
                    <br/>
                <?php endif; ?>

                <div class="form-group">
                    <label for="document"><?php _trans('select_document'); ?></label>
                    <input class="form-control" type="file" id="document" name="document">
                </div>

                <div class="btn-group btn-group-sm index-options">
                    <a href="<?php echo site_url('payments/receipt_index'); ?>" id="btn-cancel" class="btn btn-danger">
                        <i class="fa fa-times"></i> <?php _trans('cancel'); ?>
                    </a>
                    <button type="submit" class="btn btn-success" name="btn_submit" value="1">
                        <i class="fa fa-save"></i> <?php _trans('save'); ?>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    // Bootstraps Focus Trap fuer das Modal deaktivieren, damit select2 Eingaben annimmt
    $.fn.modal.Constructor.prototype.enforceFocus = function () {};

    $(document).on('click', '#receipt_client_name, #receipt_client_search', function () {
        $('#modal-placeholder').load("<?php echo site_url('payments/ajax/modal_find_receipt_client'); ?>");
    });

    $(document).on('click', '#receipt_client_clear', function () {
        $('#receipt_client_id').val('');
        $('#receipt_client_name').val('');
    });

    $(document).on('shown.bs.modal', '#find-receipt-client', function () {
        setTimeout(function () {
            $('#create_receipt_client_id').select2('open');
        }, 200);
    });
</script>
