<?php
if ($this->config->item('disable_read_only') == true) {
    $invoice->is_read_only = 0;
}
// Little helper
$its_mine = $this->session->__get('user_id') == $invoice->user_id;
$my_class = $its_mine ? 'success' : 'warning'; // visual: work with text-* alert-*
// In change user toggle & After eInvoice (name) when user required field missing
$edit_user_title = trans('edit') . ' ' . trans('user') . ' (' . trans('invoicing') . '): ' . PHP_EOL . htmlsc(format_user($invoice->user_id));
?>

<script>
    $(function () {
        $('.item-task-id').each(function () {
            // Disable client change if at least one item already has a task id assigned
            if ($(this).val().length > 0) {
                $('#invoice_change_client').hide();
                return false;
             }
        });

        $('.btn_add_product').click(function () {
            $('#modal-placeholder').load("<?php echo site_url('products/ajax/modal_product_lookups'); ?>/" + Math.floor(Math.random() * 1000));
        });

        $('.btn_add_task').click(function () {
            $('#modal-placeholder').load("<?php echo site_url('tasks/ajax/modal_task_lookups/' . $invoice_id); ?>/" + Math.floor(Math.random() * 1000));
        });

        $('.btn_add_row').click(function () {
            $('#new_row').clone().appendTo('#item_table').removeAttr('id').addClass('item').show();
            // Legacy:no: check items tax usage is correct (ReLoad on change)
            check_items_tax_usages();
        });

<?php
if ( ! $items) {
?>
        $('#new_row').clone().appendTo('#item_table').removeAttr('id').addClass('item').show();
<?php
}
?>

        // Legacy:no: check items tax usage is correct (Load on change)
        $(document).on('loaded', check_items_tax_usages());

        $('#btn_create_recurring').click(function () {
            $('#modal-placeholder').load("<?php echo site_url('invoices/ajax/modal_create_recurring'); ?>", {
                invoice_id: <?php echo $invoice_id; ?>
            });
        });
<?php
if ($invoice->invoice_status_id == 1 && ! $invoice->creditinvoice_parent_id) {
?>

        $('#invoice_change_client').click(function () {
            $('#modal-placeholder').load("<?php echo site_url('invoices/ajax/modal_change_client'); ?>", {
                invoice_id: <?php echo $invoice_id; ?>,
                client_id: "<?php echo $this->db->escape_str($invoice->client_id); ?>",
            });
        });

        $('#invoice_change_user').click(function () {
            $('#modal-placeholder').load("<?php echo site_url('invoices/ajax/modal_change_user'); ?>", {
                invoice_id: <?php echo $invoice_id; ?>,
                user_id: "<?php echo $this->db->escape_str($invoice->user_id); ?>",
            });
        });
<?php
} // End if
?>

        $('#btn_save_invoice').click(function () {
            var items = [];
            var item_order = 1;
            $('#item_table .item').each(function () {
                var row = {};
                $(this).find('input,select,textarea').each(function () {
                    if ($(this).is(':checkbox')) {
                        row[$(this).attr('name')] = $(this).is(':checked');
                    } else {
                        row[$(this).attr('name')] = $(this).val();
                    }
                });
                row['item_order'] = item_order;
                item_order++;
                items.push(row);
            });
            $.post("<?php echo site_url('invoices/ajax/save'); ?>", {
                    legacy_calculation: <?php echo (int) $legacy_calculation; ?>,
                    invoice_id: <?php echo $invoice_id; ?>,
                    invoice_number: $('#invoice_number').val(),
                    invoice_date_created: $('#invoice_date_created').val(),
                    invoice_date_due: $('#invoice_date_due').val(),
                    invoice_status_id: $('#invoice_status_id').val(),
                    invoice_password: $('#invoice_password').val(),

                    invoice_class: $('#invoice_class').val(),           // class by chrissie
                    flag_39: $('#flag_39').is(':checked') ? 1 : 0,      // type by chrissie
                    flag_45a: $('#flag_45a').is(':checked') ? 1 : 0,    // type by chrissie
                    flag_45b: $('#flag_45b').is(':checked') ? 1 : 0,    // type by chrissie

                    items: JSON.stringify(items),
                    invoice_discount_amount: $('#invoice_discount_amount').val(),
                    invoice_discount_percent: $('#invoice_discount_percent').val(),
                    invoice_terms: $('#invoice_terms').val(),
                    custom: $('input[name^=custom],select[name^=custom]').serializeArray(),
                    payment_method: $('#payment_method').val(),
                },
                function (data) {
                    var response = json_parse(data, <?php echo (int) IP_DEBUG; ?>);
                    if (response.success === 1) {
                        window.location = "<?php echo site_url('invoices/view'); ?>/" + <?php echo $invoice_id; ?>;
                    } else {
                        $('#fullpage-loader').hide();
                        $('.control-group').removeClass('has-error');
                        $('div.alert[class*="alert-"]').remove();
                        var resp_errors = response.validation_errors,
                            all_resp_errors = '';
                        for (var key in resp_errors) {
                            $('#' + key).parent().addClass('has-error');
                            all_resp_errors += resp_errors[key];
                        }
                        $('#invoice_form').prepend('<div class="alert alert-danger">' + all_resp_errors + '</div>');
                    }
                });
        });

<?php if (env_bool('INVOICE_PDF_MULTI') == false) { ?>
        $('#btn_generate_pdf').click(function () {
            window.open('<?php echo site_url('invoices/generate_pdf/' . $invoice_id); ?>', '_blank');
        });
<?php } else { ?>
        // chrissie templ chooser
        $('.btn_generate_pdf').click(function () {
            var template = $(this).attr('data-invoice-template');
            window.open('<?php echo site_url('invoices/generate_pdf/' . $invoice_id . '/true'); ?>/' + template, '_blank');
        });

        $('.dropdown-submenu > a').on("click", function(e){
            $(this).next('ul').toggle();
            e.stopPropagation();
            e.preventDefault();
       });
<?php } ?>

        $('#btn_generate_xml').click(function () {
            window.open('<?php echo site_url('invoices/generate_xml/' . $invoice_id); ?>', '_blank');
        });

        $(document).on('click', '.btn_delete_item', function () {
            var btn = $(this);
            var item_id = btn.data('item-id');

            // Just remove the row if no item ID is set (new row)
            if (typeof item_id === 'undefined') {
                $(this).parents('.item').remove();
                check_items_tax_usages();
            } else {
                $.post("<?php echo site_url('invoices/ajax/delete_item/' . $invoice->invoice_id); ?>", {
                        'item_id': item_id,
                    },
                    function (data) {
                        var response = json_parse(data, <?php echo (int) IP_DEBUG; ?>);
                        if (response.success === 1) {
                            btn.parents('.item').remove();
                        } else {
                            btn.removeClass('btn-link').addClass('btn-danger').prop('disabled', true);
                        }

                        check_items_tax_usages();
                    }
                );
            }
        });

<?php
if ($invoice->is_read_only != 1) {
    if (get_setting('show_responsive_itemlist') == 1) { ?>
             function UpR(k) {
               var parent = k.parents('.item');
               var pos = parent.prev();
               parent.insertBefore(pos);
             }
             function DownR(k) {
               var parent = k.parents('.item');
               var pos = parent.next();
               parent.insertAfter(pos);
             }
             $(document).on('click', '.up', function () {
               UpR($(this));
             });
             $(document).on('click', '.down', function () {
               DownR($(this));
             });
<?php
    } else {
?>
            var fixHelper = function (e, tr) {
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function (index) {
                    $(this).width($originals.eq(index).width());
                });
                return $helper;
            };

            $('#item_table').sortable({
                items: 'tbody',
                helper: fixHelper,
            });
<?php
    }
?>

        if ($('#invoice_discount_percent').val().length > 0) {
            $('#invoice_discount_amount').prop('disabled', true);
        }

        if ($('#invoice_discount_amount').val().length > 0) {
            $('#invoice_discount_percent').prop('disabled', true);
        }

        $('#invoice_discount_amount').keyup(function () {
            if (this.value.length > 0) {
                $('#invoice_discount_percent').prop('disabled', true);
            } else {
                $('#invoice_discount_percent').prop('disabled', false);
            }
        });
        $('#invoice_discount_percent').keyup(function () {
            if (this.value.length > 0) {
                $('#invoice_discount_amount').prop('disabled', true);
            } else {
                $('#invoice_discount_amount').prop('disabled', false);
            }
        });
<?php
}
?>

<?php if ($invoice->invoice_is_recurring) { ?>
        $(document).on('click', '.js-item-recurrence-toggler', function () {
            var itemRecurrenceState = $(this).next('input').val();
            if (itemRecurrenceState === ('1')) {
                $(this).next('input').val('0');
                $(this).removeClass('fa-calendar-check-o text-success');
                $(this).addClass('fa-calendar-o text-muted');
            } else {
                $(this).next('input').val('1');
                $(this).removeClass('fa-calendar-o text-muted');
                $(this).addClass('fa-calendar-check-o text-success');
            }
        });
<?php } ?>

    });


</script>

<?php
echo $modal_delete_invoice;
echo $legacy_calculation ? $modal_add_invoice_tax : ''; // Legacy calculation have global taxes - since v1.6.3
?>
<div id="headerbar">
    <h1 class="headerbar-title">
        <span data-toggle="tooltip" data-placement="bottom" title="<?php _trans('invoicing'); ?>: <?php _htmlsc(PHP_EOL . format_user($invoice->user_id)); ?>">
            <?php echo trans('invoice') . ' ' . ($invoice->invoice_number ? '#' . $invoice->invoice_number : trans('id') . ': ' . $invoice->invoice_id); ?>
        </span>
<?php
// Nb Admins > 1 only
if ($change_user) {
?>
        <a data-toggle="tooltip" data-placement="bottom"
           title="<?php echo $edit_user_title; ?>"
           href="<?php echo site_url('users/form/' . $invoice->user_id); ?>">
            <i class="fa fa-xs fa-user text-<?php echo $my_class; ?>"></i>
                <span class="hidden-xs"><?php _htmlsc($invoice->user_name); ?></span>
        </a>
<?php
    if ($invoice->invoice_status_id == 1 && ! $invoice->creditinvoice_parent_id) {
?>

        <span id="invoice_change_user" class="fa fa-fw fa-edit text-<?php echo $its_mine ? 'muted' : 'danger'; ?> cursor-pointer"
              data-toggle="tooltip" data-placement="bottom"
              title="<?php _trans('change_user'); ?>"></span>
<?php
    } // End if draft
} // End if change_user
?>
    </h1>

    <div class="headerbar-item pull-right<?php echo ($invoice->is_read_only != 1 || $invoice->invoice_status_id != 4) ? ' btn-group' : ''; ?>">

        <div class="options btn-group btn-group-sm">
<?php
// buttons no dropdown
if ( get_setting('invoice_quote_options_buttons')) {
                    if ($invoice->is_read_only != 1) { ?>
                    <!-- Options as Buttons -->
                        <a class="btn btn-sm btn-default" href="#add-invoice-tax" data-toggle="modal">
                            <i class="fa fa-plus fa-margin"></i> <?php _trans('add_invoice_tax'); ?>
                        </a>
                    <?php } ?>
                        <a class="btn btn-sm btn-default" href="#" id="btn_create_credit" data-invoice-id="<?php echo $invoice_id; ?>">
                            <i class="fa fa-minus fa-margin"></i> <?php _trans('create_credit_invoice'); ?>
                        </a>
                    <?php if ($invoice->invoice_balance != 0) : ?>
                        <a href="#" class="btn btn-sm btn-default invoice-add-payment"
                           data-invoice-id="<?php echo $invoice_id; ?>"
                           data-invoice-balance="<?php echo $invoice->invoice_balance; ?>"
                           data-invoice-payment-method="<?php echo $invoice->payment_method; ?>"
                           data-payment-cf-exist="<?php echo $payment_cf_exist ?? ''; ?>">
                            <i class="fa fa-credit-card fa-margin"></i>
                            <?php _trans('enter_payment'); ?>
                        </a>
                    <?php endif; 

    if (env_bool('INVOICE_PDF_MULTI') == false) {
?>
    <!-- original pdf download --->
                    <a class="btn btn-sm btn-default" href="#" id="btn_generate_pdf"
                       data-invoice-id="<?php echo $invoice_id; ?>">
                        <i class="fa fa-print fa-margin"></i>
                        <?php _trans('download_pdf'); ?>
                    </a>
<?php } else { ?>
   <!-- multiple templates by chrissie start -->
                <li class="dropdown-submenu">
                    <a href="#"
                       data-invoice-id="<?php echo $invoice_id; ?>">
                        <i class="fa fa-print fa-margin"></i>
                        <?php _trans('download_pdf'); ?>
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <?php
                        $invoice_default_pdf = get_setting('pdf_invoice_template');
                        foreach ($invoice_pdf_templates as $template) : ?>
                            <li><a href="#" class="btn_generate_pdf"
                                   data-invoice-template="<?php echo $template; ?>">
                                    <i class="fa<?php if($template == $invoice_default_pdf) {
                                        echo ' fa-chevron-right';
                                    }?> fa-margin"></i>
                                    <?php echo $template; ?>
                                </a></li>
                        <?php endforeach; ?>
                    </ul>
</li>
    <!-- END multiple templates -->
<?php } ?>

                        <a class="btn btn-sm btn-default" href="<?php echo site_url('mailer/invoice/' . $invoice->invoice_id); ?>">
                            <i class="fa fa-send fa-margin"></i>
                            <?php _trans('send_email'); ?>
                        </a>
                        <a class="btn btn-sm btn-default" href="#" id="btn_create_recurring"
                               data-invoice-id="<?php echo $invoice_id; ?>">
                                <i class="fa fa-refresh fa-margin"></i>
                                <?php _trans('create_recurring'); ?>
                        </a>
                        <a class="btn btn-sm btn-default" href="#" id="btn_copy_invoice"
                               data-invoice-id="<?php echo $invoice_id; ?>">
                                <i class="fa fa-copy fa-margin"></i>
                                <?php _trans('copy_invoice'); ?>
                        </a>
                    <?php if ($invoice->invoice_status_id == 1 || ($this->config->item('enable_invoice_deletion') === true && $invoice->is_read_only != 1)) { ?>
                        <a class="btn btn-sm btn-default btn-warning" href="#delete-invoice" data-toggle="modal">
                            <i class="fa fa-trash-o fa-margin"></i>
                            <?php _trans('delete'); ?>
                        </a>
                    <?php }
// END if  buttons no dropdown, default else now
                } else { ?>

            <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="fa fa-caret-down no-margin"></i> <?php _trans('options'); ?>
            </a>
            <ul class="dropdown-menu">
<?php
if ($legacy_calculation && $invoice->is_read_only != 1) { // Legacy calculation have global taxes - since v1.6.3
?>
                <li>
                    <a href="#add-invoice-tax" data-toggle="modal">
                        <i class="fa fa-plus fa-margin"></i> <?php _trans('add_invoice_tax'); ?>
                    </a>
                </li>
<?php
} // End if
?>
                <li>
                    <a href="#" id="btn_create_credit"
                       data-invoice-id="<?php echo $invoice_id; ?>">
                        <i class="fa fa-minus fa-margin"></i> <?php _trans('create_credit_invoice'); ?>
                    </a>
                </li>
<?php
if ($invoice->invoice_balance != 0) {
?>
                <li>
                    <a href="#" class="invoice-add-payment"
                       data-invoice-id="<?php echo $invoice_id; ?>"
                       data-invoice-balance="<?php echo $invoice->invoice_balance; ?>"
                       data-invoice-payment-method="<?php echo $invoice->payment_method; ?>"
                       data-payment-cf-exist="<?php echo $payment_cf_exist ?? ''; ?>">
                        <i class="fa fa-credit-card fa-margin"></i>
                        <?php _trans('enter_payment'); ?>
                    </a>
                </li>
<?php
}
    if (env_bool('INVOICE_PDF_MULTI') == false) {
?>
    <!-- original pdf download --->
                <li>
                    <a href="#" id="btn_generate_pdf"
                       data-invoice-id="<?php echo $invoice_id; ?>">
                        <i class="fa fa-print fa-margin"></i>
                        <?php _trans('download_pdf'); ?>
                    </a>
                </li>
<?php } else { ?>
   <!-- multiple templates by chrissie start -->
                <li class="dropdown-submenu">
                    <a href="#"
                       data-invoice-id="<?php echo $invoice_id; ?>">
                        <i class="fa fa-print fa-margin"></i>
                        <?php _trans('download_pdf'); ?>
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <?php
                        $invoice_default_pdf = get_setting('pdf_invoice_template');
                        foreach ($invoice_pdf_templates as $template) : ?>
                            <li><a href="#" class="btn_generate_pdf"
                                   data-invoice-template="<?php echo $template; ?>">
                                    <i class="fa<?php if($template == $invoice_default_pdf) {
                                        echo ' fa-chevron-right';
                                    }?> fa-margin"></i>
                                    <?php echo $template; ?>
                                </a></li>
                        <?php endforeach; ?>
                    </ul>
    <!-- END multiple templates -->
<?php } ?>

<?php
// eInvoice & user fields OK: Show download XML Option
if ($einvoice->user) {
?>
                <li>
                    <a href="#" id="btn_generate_xml"
                       data-invoice-id="<?php echo $invoice_id; ?>">
                        <i class="fa fa-file-code-o fa-margin"></i>
                        <?php _trans('download_xml'); ?>
                    </a>
                </li>
<?php
}
?>
                <li>
                    <a href="<?php echo site_url('mailer/invoice/' . $invoice->invoice_id); ?>">
                        <i class="fa fa-send fa-margin"></i>
                        <?php _trans('send_email'); ?>
                    </a>
                </li>
                <li class="divider"></li>
                <li>
                    <a href="#" id="btn_create_recurring"
                       data-invoice-id="<?php echo $invoice_id; ?>">
                        <i class="fa fa-refresh fa-margin"></i>
                        <?php _trans('create_recurring'); ?>
                    </a>
                </li>
                <li>
                    <a href="#" id="btn_copy_invoice"
                       data-invoice-id="<?php echo $invoice_id; ?>"
                       data-client-id="<?php echo $invoice->client_id; ?>">
                        <i class="fa fa-copy fa-margin"></i>
                        <?php _trans('copy_invoice'); ?>
                    </a>
                </li>
<?php
if ($invoice->invoice_status_id == 1 || ($this->config->item('enable_invoice_deletion') === true && $invoice->is_read_only != 1)) {
?>
                <li>
                    <a href="#delete-invoice" data-toggle="modal">
                        <i class="fa fa-trash-o fa-margin"></i>
                        <?php _trans('delete'); ?>
                    </a>
                </li>
<?php
} // End if
?>
            </ul>
<?php } // End  if else BUTTONS?>
        </div>

<?php
if ($invoice->is_read_only != 1 || $invoice->invoice_status_id != 4) {
?>
        <a href="#" class="btn btn-sm btn-success ajax-loader" id="btn_save_invoice">
            <i class="fa fa-check"></i> <?php _trans('save'); ?>
        </a>
<?php
} //End if
?>
    </div>

    <div class="headerbar-item invoice-labels pull-right">
<?php
if ($invoice->invoice_is_recurring) {
?>
        <span class="label label-info">
            <i class="fa fa-refresh"></i> <?php _trans('recurring'); ?>
        </span>
<?php
}
if ($invoice->is_read_only == 1) {
?>
        <span class="label label-danger">
            <i class="fa fa-read-only"></i> <?php _trans('read_only'); ?>
        </span>
<?php
}
?>
    </div>

</div>

<div id="content">

    <?php echo $this->layout->load_view('layout/alerts'); ?>

    <div id="invoice_form">
        <div class="invoice">

            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-5">

                    <h2>
                        <a href="<?php echo site_url('clients/view/' . $invoice->client_id); ?>"><?php _htmlsc(format_client($invoice)); ?></a>
<?php
if ($invoice->invoice_status_id == 1 && ! $invoice->creditinvoice_parent_id) {
?>
                        <span id="invoice_change_client" class="fa fa-edit cursor-pointer small"
                              data-toggle="tooltip" data-placement="bottom"
                              title="<?php _trans('change_client'); ?>"></span>
<?php
} // End if
?>
                    </h2>
                    <br>
                    <div class="client-address">
                        <?php $this->layout->load_view('clients/partial_client_address', ['client' => $invoice]); ?>
                    </div>
<?php if ($invoice->client_phone) : ?>
                    <div><?php _trans('phone'); ?>:&nbsp;<?php _htmlsc($invoice->client_phone); ?></div>
<?php endif; ?>
<?php if ($invoice->client_email) : ?>
                    <div><?php _trans('email'); ?>:&nbsp;<?php _auto_link($invoice->client_email); ?></div>
<?php endif; ?>

<?php if (ip_mari()): ?>
<div >
<?php _trans('customer_paragraphs'); echo ': '.show_paragraphs($invoice->client_flags);
if ($invoice->client_flags == 0) echo trans('none'); ?>
<br>
<?php _trans('carelevel'); if (isset($invoice->carelevel) && intval($invoice->carelevel) > 0) { echo ': '.$invoice->carelevel; ?>
<input title="carelevel_confirmation" type="checkbox" disabled readonly <?php if ($invoice->client_flags & 128) echo 'checked="checked"' ?> >
<?php } else echo ': --'; ?>
</div>
<?php endif; ?>

                </div>

                <div class="col-xs-12 visible-xs"><br></div>

                <div class="col-xs-12 col-sm-5 col-sm-offset-1 col-md-6 col-md-offset-1">
                    <div class="details-box panel panel-default panel-body">
                        <div class="row">
<?php
if ($invoice->invoice_sign == -1) {
    $parent_invoice_number = $this->mdl_invoices->get_parent_invoice_number($invoice->creditinvoice_parent_id);
    $view_link             = anchor('/invoices/view/' . $invoice->creditinvoice_parent_id, trans('credit_invoice_for_invoice') . ' ' . $parent_invoice_number);
?>
                            <div class="col-xs-12">
                                <div class="alert alert-warning small">
                                    <i class="fa fa-credit-invoice"></i>&nbsp;<?php echo $view_link; ?>
                                </div>
                            </div>
<?php
} // End if
?>

                            <div class="col-xs-12 col-md-6">

                                <div class="invoice-properties">
<?php
if ($einvoice->name) {
?>
                                    <label class="pull-right" id="e_invoice_active"
                                           data-toggle="tooltip" data-placement="bottom"
                                           title="e-<?php echo trans('invoice') . ' ' . ($einvoice->user ? trans('version') . ' ' . $einvoice->name . ' 🗸' : '🚫 ' . trans('einvoicing_user_fields_error')); ?>"
                                    >
                                        <i class="fa fa-file-code-o"></i>
                                        <?php echo $einvoice->name; ?>
<?php
    if ($einvoice->user) {
?>
                                        <i class="fa fa-check-square-o text-success"></i>
<?php
    } else {
?>
                                        <a class="fa fa-user-times text-warning"
                                           href="<?php echo site_url('users/form/' . $invoice->user_id); ?>"
                                           data-toggle="tooltip" data-placement="top"
                                           title="<?php echo $edit_user_title; ?>"
                                        ></a>
<?php
    }
?>

                                    </label>
<?php
}
?>
                                    <label for="invoice_number"><?php _trans('invoice'); ?> #</label>
                                    <input type="text" id="invoice_number" class="form-control"
<?php if ($invoice->invoice_number) : ?>
                                           value="<?php echo $invoice->invoice_number; ?>"
<?php else : ?>
                                           placeholder="<?php _trans('not_set'); ?>"
<?php endif; ?>
                                           <?php echo $invoice->is_read_only ? 'disabled="disabled"' : ''; ?>
                                    >

                                </div>

                                <div class="invoice-properties has-feedback">
                                    <label><?php _trans('date'); ?></label>

                                    <div class="input-group">
                                        <input name="invoice_date_created" id="invoice_date_created"
                                               class="form-control datepicker"
                                               value="<?php echo date_from_mysql($invoice->invoice_date_created); ?>"
                                               <?php echo $invoice->is_read_only ? 'disabled="disabled"' : ''; ?>>
                                        <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                                    </div>
                                </div>

                                <div class="invoice-properties has-feedback">
                                    <label><?php _trans('due_date'); ?></label>

                                    <div class="input-group">
                                        <input name="invoice_date_due" id="invoice_date_due"
                                               class="form-control datepicker"
                                               value="<?php echo date_from_mysql($invoice->invoice_date_due); ?>"
                                               <?php echo $invoice->is_read_only ? 'disabled="disabled"' : ''; ?>>
                                        <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xs-12 col-md-6">

                                <div class="invoice-properties">
                                    <label>
                                        <?php _trans('status');
                                        if ($invoice->is_read_only != 1 || $invoice->invoice_status_id != 4) {
                                            echo ' <span class="small">(' . trans('can_be_changed') . ')</span>';
                                        } ?>
                                    </label>
                                    <select name="invoice_status_id" id="invoice_status_id"
                                            class="form-control simple-select" data-minimum-results-for-search="Infinity"
                                            <?php echo ($invoice->is_read_only == 1 && $invoice->invoice_status_id == 4) ? 'disabled="disabled"' : ''; ?>
                                    >
<?php
foreach ($invoice_statuses as $key => $status) {
    $is_selected = ($key == $invoice->invoice_status_id) ? ' selected="selected"' : '';
?>
                                        <option value="<?php echo $key; ?>"<?php echo $is_selected; ?>>
                                            <?php echo $status['label']; ?>
                                        </option>
<?php
}
?>
                                    </select>
                                </div>

                                <div class="invoice-properties">
                                    <label><?php _trans('payment_method'); ?></label>
                                    <select name="payment_method" id="payment_method"
                                            class="form-control simple-select"
                                            <?php echo ($invoice->is_read_only == 1 && $invoice->invoice_status_id == 4) ? 'disabled="disabled"' : ''; ?>
                                    >
                                        <option value="0"><?php _trans('select_payment_method'); ?></option>
<?php
foreach ($payment_methods as $payment_method) {
?>
                                        <option <?php check_select($invoice->payment_method, $payment_method->payment_method_id) ?>
                                            value="<?php echo $payment_method->payment_method_id; ?>">
                                            <?php echo $payment_method->payment_method_name; ?>
                                        </option>
<?php
} // End foreach
?>
                                    </select>
                                </div>

                                <div class="invoice-properties">
                                    <label><?php _trans('invoice_password'); ?></label>
                                    <input type="text" id="invoice_password" class="form-control"
                                           value="<?php _htmlsc($invoice->invoice_password); ?>"
                                           <?php echo $invoice->is_read_only ? 'disabled="disabled"' : ''; ?>>
                                </div>
                            </div>

<?php
$default_custom = false;
$classes        = ['control-label', 'controls', '', 'col-xs-12 col-md-6'];
foreach ($custom_fields as $custom_field) {
    if ( ! $default_custom && ! $custom_field->custom_field_location) {
        $default_custom = true;
    }

    if ($custom_field->custom_field_location == 1) {
        print_field($this->mdl_invoices, $custom_field, $custom_values, $classes[0], $classes[1], $classes[2], $classes[3]);
    }
}
?>

<?php
if ($invoice->invoice_status_id != 1) {
?>
                            <div class="col-xs-12">
                                <div class="form-group">
                                    <label for="invoice-guest-url"><?php _trans('guest_url'); ?></label>
                                    <div class="input-group">
                                        <input type="text" id="invoice-guest-url" readonly class="form-control"
                                               value="<?php echo site_url('guest/view/invoice/' . $invoice->invoice_url_key) ?>">
                                        <span class="input-group-addon to-clipboard cursor-pointer"
                                              data-clipboard-target="#invoice-guest-url">
                                            <i class="fa fa-clipboard fa-fw"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
<?php
} // End if
?>

                        </div>
                    </div>
                </div>
            </div>

<hr>

<!-- Invoice Class and Type by chrissie -->
<div class="row">
<?php if (env_bool('INVOICE_CLASS')): ?>
<div class="col-xs-12 col-md-3">
<?php
echo form_dropdown(
 'invoice_class',                     // Name
 $available_invoice_classes,          // Array von Key => Label
 $invoice_class_selected,             // ausgewahlter Wert
 'class="form-control simple-select select-auto-width" id="invoice_class"'.
 ($invoice->is_read_only ? 'disabled="disabled"' : '')
);
?>
</div>
<?php endif; ?>

<?php if (ip_mari()):
// invoice type as checkbox, im grunde die gleichen paragraphen wie kunde, aber pro rechnung gespeichert
// privat jedoch nicht extra zum anklicken das sollte eh klar sein
$invoice_type = intval($invoice->invoice_type);
            $items = [
                // 'flag_private' => ['bit' => 1, 'label' => 'Privat'],
                'flag_39'      => ['bit' => 2, 'label' => 'Paragraph 39'],
                'flag_45a'     => ['bit' => 4, 'label' => 'Paragraph 45a'],
                'flag_45b'     => ['bit' => 8, 'label' => 'Paragraph 45b'],
            ];
             foreach ($items as $id => $item): ?>
                <span class="invoice_type">
                    <input
                        id="<?= $id ?>"
                        name="<?= $id ?>"
                        type="checkbox"
                        value="1"
                        <?= ($invoice_type & $item['bit']) ? 'checked' : '' ?>
                        <?= $invoice->is_read_only ? 'disabled="disabled"' : '' ?>
                    >
                    <?= $item['label'] ?>
                </span>
                &nbsp;&nbsp;&nbsp;
            <?php endforeach; ?>
<?php else: 
// default hidden for ajax save if not ip_mari()
?>
<input type="hidden" name="flag_39" value="0">
<input type="hidden" name="flag_45a" value="0">
<input type="hidden" name="flag_45b" value="0">
<?php endif; ?>
</div>
<!-- -->

            <br>

<?php $this->layout->load_view('invoices/partial_itemlist_' . (get_setting('show_responsive_itemlist') ? 'responsive' : 'table')); ?>

            <hr>

            <div class="row">
                <div class="col-xs-12 col-md-6">

                    <div class="panel panel-default no-margin">
                        <div class="panel-heading">
                            <?php _trans('invoice_terms'); ?>
                        </div>
                        <div class="panel-body">
                            <textarea id="invoice_terms" name="invoice_terms" class="form-control" rows="3"
                                      <?php echo $invoice->is_read_only ? 'disabled="disabled"' : ''; ?>
                            ><?php _htmlsc($invoice->invoice_terms); ?></textarea>
                        </div>
                    </div>

                    <div class="col-xs-12 visible-xs visible-sm"><br></div>

                </div>
                <div class="col-xs-12 col-md-6">

                    <?php _dropzone_html($invoice->is_read_only); ?>

                </div>
            </div>

<?php
if ($default_custom) {
?>
            <div class="row">
                <div class="col-xs-12">

                    <hr>

                    <div class="panel panel-default">
                        <div class="panel-heading"><?php _trans('custom_fields'); ?></div>
                        <div class="panel-body">
                            <div class="row">
<?php
    $classes = ['control-label', 'controls', '', 'form-group col-xs-12 col-sm-6'];
    foreach ($custom_fields as $custom_field) {
        if ( ! $custom_field->custom_field_location) { // == 0
            print_field($this->mdl_invoices, $custom_field, $custom_values, $classes[0], $classes[1], $classes[2], $classes[3]);
        }
    }
?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
<?php
} // End if custom_fields
?>

        </div>
    </div>
</div>

<?php
_dropzone_script($invoice->invoice_url_key, $invoice->client_id);
