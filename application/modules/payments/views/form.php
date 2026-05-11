<script>
    $(function () {
        var $invoice_id = $('#invoice_id');
        $invoice_id.focus();

        amounts = json_parse('<?php echo $amounts; ?>', <?php echo (int) IP_DEBUG; ?>);
        invoice_payment_methods = json_parse('<?php echo $invoice_payment_methods; ?>', <?php echo (int) IP_DEBUG; ?>);

        $invoice_id.change(function () {
            var invoice_identifier = "invoice" + $('#invoice_id').val();
            $('#payment_amount').val(amounts[invoice_identifier].replace("&nbsp;", " "));

            $('#payment_method_id').val(invoice_payment_methods[invoice_identifier]).trigger('change');
            if (invoice_payment_methods[invoice_identifier] != 0) {
                $('.payment-method-wrapper').append("<input type='hidden' name='payment_method_id' id='payment-method-id-hidden' class='hidden' value='" + invoice_payment_methods[invoice_identifier] + "'>");
                $('#payment_method_id').prop('disabled', true);
            } else {
                $('#payment-method-id-hidden').remove();
                $('#payment_method_id').prop('disabled', false);
            }
        });
    });
</script>


<form method="post" class="form-horizontal">

    <?php _csrf_field(); ?>

<?php
if ($payment_id) {
?>
    <input type="hidden" name="payment_id" value="<?php echo $payment_id; ?>">
<?php
}
?>

    <div id="headerbar">
        <h1 class="headerbar-title"><?php _trans('payment_form'); ?></h1>
        <?php $this->layout->load_view('layout/header_buttons', ['attribute_cancel' => 'onclick="window.location.href = `' . site_url('payments') . '`;"']); ?>
    </div>

    <div id="content">

        <?php $this->layout->load_view('layout/alerts'); ?>

    <div class="row" >
        <div class="col-xs-12 col-sm-6" >
        <div class="panel panel-default" >

            <div class="panel-heading form-inline clearfix" >
                <?php _trans('enter_payment'); ?>
<?php if ( get_setting('invoice_quote_options_buttons') ) { ?>
<br>
        <button type="button" id="btn-submit-stay" name="btn_submit_stay" class="btn btn-success" value="1">
            <i class="fa fa-check"></i> <?php _trans('save_stay'); ?>
        </button>
<?php } ?>
            </div>
            <div class="panel-body" >

            <div class="form-group" >
                <label for="invoice_id" class="control-label"><?php _trans('invoice'); ?></label>
                <select name="invoice_id" id="invoice_id" class="form-control simple-select" required>
<?php
if ( ! $payment_id) {
    foreach ($open_invoices as $invoice) {
?>
                        <option value="<?php echo $invoice->invoice_id; ?>"
                                <?php check_select($this->mdl_payments->form_value('invoice_id'), $invoice->invoice_id); ?>>
                            <?php echo $invoice->invoice_number . ' - ' . htmlsc(format_client($invoice)) . ' - ' . format_currency($invoice->invoice_balance); ?>
                        </option>
<?php
    } // End foreach
} else {
?>
                    <option value="<?php echo $payment->invoice_id; ?>">
                        <?php echo $payment->invoice_number . ' - ' . htmlsc(format_client($payment)) . ' - ' . format_currency($payment->invoice_balance); ?>
                    </option>
<?php
}
?>
                </select>
            </div>

        <div class="form-group has-feedback">
                <label for="payment_date" class="control-label"><?php _trans('date'); ?></label>
                <div class="input-group">
                    <input name="payment_date" id="payment_date"
                           class="form-control datepicker"
                           value="<?php echo date_from_mysql($this->mdl_payments->form_value('payment_date')); ?>" required>
                    <span class="input-group-addon">
                        <i class="fa fa-calendar fa-fw"></i>
                    </span>
                </div>
        </div>

        <div class="form-group">
                <label for="payment_amount" class="control-label"><?php _trans('amount'); ?></label>
                <input type="text" name="payment_amount" id="payment_amount" class="form-control"
                       value="<?php echo format_amount(standardize_amount($this->mdl_payments->form_value('payment_amount'))); ?>" required>
        </div>


        <div class="form-group">
                <label for="payment_method_id" class="control-label">
                    <?php _trans('payment_method'); ?>
                </label>

<?php
                // Add a hidden input field if a payment method was set to pass the disabled attribute
if ($this->mdl_payments->form_value('payment_method_id')) {
?>
                    <input type="hidden" name="payment_method_id" class="hidden"
                           value="<?php echo $this->mdl_payments->form_value('payment_method_id'); ?>">
<?php
}
?>

                <select id="payment_method_id" name="payment_method_id"
                        class="form-control simple-select" data-minimum-results-for-search="Infinity"
                        <?php echo $this->mdl_payments->form_value('payment_method_id') ? 'disabled="disabled"' : ''; ?>>
<?php
foreach ($payment_methods as $payment_method) {
?>
                    <option value="<?php echo $payment_method->payment_method_id; ?>"
                        <?php echo $this->mdl_payments->form_value('payment_method_id') == $payment_method->payment_method_id ? 'selected="selected"' : ''; ?>>
                        <?php echo $payment_method->payment_method_name; ?>
                    </option>
<?php
}
?>
                </select>
        </div>

        <div class="form-group">
                <label for="payment_bank_book_subject" class="control-label"><?php _trans('bank_book_subject'); ?></label>
                <input type="text" name="payment_bank_book_subject" id="payment_bank_book_subject" class="form-control"
                       value="<?php echo $this->mdl_payments->form_value('payment_bank_book_subject'); ?>" >
        </div>

        <div class="form-group">
                <label for="payment_note" class="control-label"><?php _trans('note'); ?></label>
                <textarea name="payment_note"
                          class="form-control"><?php echo $this->mdl_payments->form_value('payment_note', true); ?></textarea>
        </div>

<?php
$classes = ['col-xs-12 col-sm-2 text-right text-left-xs', 'col-xs-12 col-sm-6', 'control-label', 'form-group'];
foreach ($custom_fields as $custom_field) {
    print_field($this->mdl_payments, $custom_field, $custom_values, $classes[0], $classes[1], $classes[2], $classes[3]);
}
?>

    </div> <!-- // PANEL_BODY -->
    </div> <!-- // PANEL -->
    </div> <!-- // COL_XS -->
    </div> <!-- // ROW -->
    </div> <!-- // CONTENT -->

</form>

<script>
/***
 * new save logic buttons by chrissie for the best user experience 
 * Noch nie konnte man schneller hintereinander viele Zahlungen eingeben
 */
$(document).ready(function () {
 // Value changen beim ersten Laden direkt ausführen - HOTFI plz IP-Devs integrate thisX!!!
  var $invoice_id = $('#invoice_id');
  $invoice_id.trigger('change');

// save and stay for easy multiple payments by chrissie
  $('#btn-submit-stay').on('click', function () {
    var form = $('form')[0];
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    $('#fullpage-loader').show();
    $.ajax({
      url: '<?php echo site_url('payments/form'); ?>',
      method: 'POST',
      data: $('form').serialize() + '&btn_submit_stay=1',
      success: function () {
          $('#fullpage-loader').hide();
          window.location.reload();
      },
      error: function (xhr) {
        $('#fullpage-loader').hide();
        alert('Fehler beim Speichern: ' + xhr.status);
      }
    });
  });

});
</script>

