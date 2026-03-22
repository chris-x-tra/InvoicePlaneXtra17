<style>
.required {
    position: relative;
    cursor: help; /* zeigt Hinweis-Cursor */
}
.required::before {
    content: "\f06a"; /* fa-exclamation-circle */
    font-family: "FontAwesome";
    color: black;
}
.required:hover::after {
    content: "This field is required";
    position:absolute;
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

#expense_supplier_name {
    cursor: pointer; /* Zeigt Hand-Cursor */
    background-color: #fcfcfc; /* optional: leicht andere Hintergrundfarbe */
}
#expense_supplier_name:hover {
    background-color: #f5f5f5; /* optional: Hover-Effekt */
}
</style>

<div id="headerbar">
<h1 class="headerbar-title">
<?php _trans('expense_form'); ?>
</h1>

<?php // var_dump($expense); // Debug - workz. ?>

<?php  //$this->layout->load_view('layout/header_buttons'); ?>
</div>

<div id="content">
    <?php echo $this->layout->load_view('layout/alerts'); ?>
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-5">
	<h3>
          <?php if ($this->mdl_expenses->form_value('expense_id')) : ?>
              <?php _trans('edit_expense'); ?>
          <?php else : ?>
              <?php _trans('add_expense'); ?>
          <?php endif; ?>
	</h3>
<br />

    <!-- -->
    <form method="post" enctype="multipart/form-data" action="<?php echo site_url('expenses/form'); ?>">

    <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
           value="<?php echo $this->security->get_csrf_hash() ?>">

	<?php if ($this->mdl_expenses->form_value('expense_id')) : ?>
	<input type="hidden" name="expense_id" value="<?= $this->mdl_expenses->form_value('expense_id') ?>" >
	<?php endif; ?>

    <div class="form-group">
        <label for="expense_description">
            <?php _trans('expense_number'); ?>
        </label>
        <input id="expense_number" name="expense_number" type="text" class="form-control"
            value="<?php echo $this->mdl_expenses->form_value('expense_number', true); ?>">
    </div>

    <div class="form-group">
        <label for="expense_description">
            <?php _trans('expense_description'); ?> <span class="required"></span>
        </label>
        <input id="expense_descripton" name="expense_description" type="text" class="form-control" required
            value="<?php echo $this->mdl_expenses->form_value('expense_description', true); ?>">
    </div>

    <div class="form-group">
        <label for="expense_date">
            <?php _trans('expense_date'); ?> <span class="required"></span>
        </label>
        <input id="expense_date" name="expense_date" type="date" class="form-control" required
            value="<?php echo $this->mdl_expenses->form_value('expense_date', true); ?>">
    </div>

<!-- javascript search as you type -->
<script>
        $(document).on('click', '#expense_supplier_name', function () {
            $('#modal-placeholder').load("<?php echo site_url('expenses/ajax/modal_find_supplier'); ?>");
        });

// trick, um das modal gleich zu aktivieren und ins eingabefeld zu springen
// Deaktiviert Bootstraps Focus Trap fur dieses Modal
$.fn.modal.Constructor.prototype.enforceFocus = function() {};

$(document).on('shown.bs.modal', '#find-supplier', function () {
    setTimeout(function() {
        $('#create_expense_client_id').select2('open');
    }, 200); // kleiner Delay fur sauberes Öffnen
});
</script>

<!-- Hidden Feld fur die ID -->
<input id="expense_supplier_id" name="expense_supplier_id" type="hidden"
       value="<?php echo $this->mdl_expenses->form_value('expense_supplier_id', true); ?>" required>

<!-- Readonly Feld fur den Namen -->
<div class="form-group">
    <label for="expense_supplier_name"><?php _trans('expense_supplier_name'); ?></label> <span class="required"></span>

    <div class="input-group">
        <input id="expense_supplier_name" type="text" class="form-control"
               value="<?php echo htmlspecialchars($supplier_name ?? '', ENT_QUOTES); ?>"
               readonly>
        <span class="input-group-addon" style="cursor: pointer;">
            <i class="fa fa-search"></i>
        </span>
    </div>
</div>

    <div class="form-group">
        <label for="expense_amount">
            <?php _trans('expense_amount'); ?> <span class="required"></span>
        </label>
        <div class="input-group">
            <input id="expense_amount" name="expense_amount" type="text" class="form-control"
                   value="<?php echo format_amount($this->mdl_expenses->form_value('expense_amount')); ?>" required>
            <span class="input-group-addon"><?php echo get_setting('currency_symbol'); ?></span>
        </div>
    </div>

    <div class="form-group">
        <label for="expense_status_id">
            <?php _trans('expense_status'); ?>
        </label>
        <input id="expense_status_id" name="expense_status_id" type="text" class="form-control"
            value="<?php echo $this->mdl_expenses->form_value('expense_status_id', true); ?>">
    </div>

    <div class="form-group">
        <label for="expense_category_id">
            <?php _trans('expense_category'); ?> <span class="required"></span>
        </label>
        <select name="expense_category_id" id="expense_category_id" class="form-control simple-select" required>
            <?php foreach ($expense_types as $key => $type) { ?>
                <option value="<?php echo $key; ?>"
                    <?php check_select($this->mdl_expenses->form_value('expense_category_id'), $key); ?>>
                    <?php echo $type; ?>
                </option>
            <?php } ?>
        </select>
    </div>

<!--
    <div class="form-group">
        <label for="expense_due_date">
            <?php _trans('expense_due_date'); ?>
        </label>
        <input id="expense_due_date" name="expense_due_date" type="date" class="form-control"
            value="<?php echo $this->mdl_expenses->form_value('expense_due_date', true); ?>">
    </div>

    <div class="form-group">
        <label for="expense_paid_date">
            <?php _trans('expense_paid_date'); ?>
        </label>
        <input id="expense_paid_date" name="expense_paid_date" type="date" class="form-control"
            value="<?php echo $this->mdl_expenses->form_value('expense_paid_date', true); ?>">
    </div>
-->

    <div class="form-group">
        <label for="expense_bank_book_date">
            <?php _trans('expense_bank_book_date'); ?>
        </label>
        <input id="expense_bank_book_date" name="expense_bank_book_date" type="date" class="form-control"
            value="<?php echo $this->mdl_expenses->form_value('expense_bank_book_date', true); ?>">
    </div>

    <div class="form-group">
        <label for="expense_bank_book_subject">
            <?php _trans('expense_bank_book_subject'); ?>
        </label>
        <input id="expense_bank_book_subject" name="expense_bank_book_subject" type="text" class="form-control"
            value="<?php echo $this->mdl_expenses->form_value('expense_bank_book_subject', true); ?>">
    </div>

<?php   // from view.php - improve
    if (!empty($expenses_documents)): ?>
        <label for="expense_documents">
            <?php _trans('expenses_documents'); ?>
        </label>
        <br />
        <?php foreach ($expenses_documents as $d): ?>
        <?php
        // Zerlege den String an jedem _
        $parts = explode('_', $d->document_filename);
        // Entferne die ersten 3 Teile
        $remaining = array_slice($parts, 3);
        // Fuge den Rest wieder mit _ zusammen
        $clean_filename = implode('_', $remaining);
        ?>
        <span class="document-filename"><?php echo $clean_filename; ?></span>
       <?php endforeach; 
    endif; 
?>

    <div class="form-group">
        <label><?php _trans('select_document'); ?></label>
        <input class="form-control" type="file" name="document" >
    </div>

    <div class="btn-group btn-group-sm index-options">
        <button type="button" onclick="window.history.back()" id="btn-cancel" name="btn_cancel" class="btn btn-danger" value="1">
        <i class="fa fa-times"></i> 
            <?php _trans('cancel'); ?>
        </button>

        <button type="submit" class="btn btn-success" name="btn_submit" value="1">
            <i class="fa fa-save"></i>
            <?php _trans('save'); ?>
        </button>
    </div>

</form>
            </div>
        </div>
    </div>
</div>
