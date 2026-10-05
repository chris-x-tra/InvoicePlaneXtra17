<?php
$client_active = $this->mdl_clients->form_value('client_active');
$active        = ($client_active == 1 || ! is_numeric($client_active)) ? ' checked="checked"' : '';
$itsCompany    = $this->mdl_clients->form_value('client_company') || $this->mdl_clients->form_value('client_vat_id');
if ($req_einvoicing) {
    // eInvoicing panel
    $nb_users    = count($req_einvoicing->users);
    $me          = $req_einvoicing->users[$_SESSION['user_id']]->show_table;
    $nb          = $req_einvoicing->show_table; // Of users in error
    $ln          = 'user' . (($nb ?: $nb_users) > 1 ? 's' : ''); // tweak 1 on more nb_users no ok
    $user_toggle = ($req_einvoicing->show_table ? ($me ? 'danger' : 'warning') : 'default') . ' ' . ($me ? '" aria-expanded="true' : '" collapsed" aria-expanded="false');
}
// eInvoicing enabled?
$einvoicingTip = $req_einvoicing ? ' data-toggle="tooltip" data-placement="bottom" title="e-' . trans('invoicing') . ' (' : ''; // tootip base
$einvoicingReq = $req_einvoicing ? $einvoicingTip . trans('required_field') . ')"' : '';
$einvoicingB2B = $req_einvoicing ? $einvoicingTip . 'B2B ' . trans('required_field') . ')"' : '';
$einvoicingOpt = $req_einvoicing ? $einvoicingTip . trans('optional') . ')"' : '';
?>
<script type="text/javascript">
    // eInvoicing button panel helper user(s) icon toggle
    const switch_fa_toggle = function (id) {
        const f = $('#'+id);f.toggleClass('fa-user').toggleClass('fa-users');
    }

    $(function () {
        $("#client_country").select2({
            placeholder: "<?php _trans('country'); ?>",
            allowClear: true
        });

<?php $this->layout->load_view('clients/js/script_select_client_title.js'); ?>

    });

</script>

<form method="post">
    <?php _csrf_field(); ?>

    <div id="headerbar">
        <h1 class="headerbar-title"><?php _trans('client_form'); ?></h1>
        <?php $this->layout->load_view('layout/header_buttons'); ?>
    </div>
    <div id="content" >

        <?php $this->layout->load_view('layout/alerts'); ?>

        <input class="hidden" name="is_update" type="hidden" value="<?php echo $this->mdl_clients->form_value('is_update') ? '1' : '0'; ?>">

        <div class="row" >

<!-- PERSONAL -->
            <div class="col-xs-12 col-sm-6">
                <div class="panel panel-default">
                    <div class="panel-heading form-inline clearfix">
                        <?php _trans('personal_information'); ?>
                        <div class="pull-right">
                            <label for="client_active" class="control-label">
                                <?php _trans('active_client'); ?>
                                <input id="client_active" name="client_active" type="checkbox" value="1"<?php echo $active; ?>>
                            </label>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="client_salutation"><?php _trans('client_salutation'); ?></label>
                            <div class="controls">
                                <input type="text" name="client_salutation" id="client_salutation" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_salutation', true); ?>" >
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="client_name">
                                <?php _trans('client_name'); ?>
&nbsp;<i class="fa fa-asterisk" style="color: #e07070;"></i>
                            </label>
                            <input id="client_name" name="client_name" type="text" class="form-control"
                                   autofocus
                                   value="<?php echo $this->mdl_clients->form_value('client_name', true); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="client_surname">
                                <?php _trans('client_surname_optional'); ?>
&nbsp;<i class="fa fa-asterisk" style="color: #e07070;"></i>
                            </label>
                            <input id="client_surname" name="client_surname" type="text" class="form-control"
                                   value="<?php echo $this->mdl_clients->form_value('client_surname', true); ?>">
                        </div>
                        <div class="form-group"<?php echo $itsCompany ? $einvoicingB2B : $einvoicingOpt; ?>>
                            <label for="client_company"><?php _trans('client_company'); ?> (<?php _trans($itsCompany ? 'required_field' : 'optional'); ?>)</label>

                            <div class="controls">
                                <input id="client_company" name="client_company" type="text" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_company', true); ?>">
                            </div>
                        </div>
                        <div class="form-group no-margin">
                            <label for="client_language">
                                <?php _trans('language'); ?>
                            </label>
                            <select name="client_language" id="client_language" class="form-control simple-select">
                                <option value="system">
                                    <?php _trans('use_system_language') ?>
                                </option>
<?php
foreach ($languages as $language) {
    $client_lang = $this->mdl_clients->form_value('client_language');
?>
                                <option value="<?php echo $language; ?>"
                                    <?php check_select($client_lang, $language) ?>>
                                    <?php echo ucfirst($language); ?>
                                </option>
<?php
}
?>
                            </select>
                        </div>
                    </div>
                </div>

            </div>
<!-- // PERSONAL -->


<?php
if ($req_einvoicing) {
?>
            <div class="col-xs-12 col-sm-6"><!-- eInvoicing -->
                <div class="panel panel-default">

                    <div class="panel-heading">
                        e-<?php _trans('invoicing'); ?>
                        <span class="<?php echo $xml_templates && $client_id ? 'pull-right' : 'hidden'; ?> toggle_einvoicing<?php
                              echo $req_einvoicing->show_table
                                   ? ' btn btn-xs btn-default cursor-pointer alert-' . $user_toggle . '"
                              data-toggle="collapse" data-target=".einvoice-user-check-lists"
                              onclick="switch_fa_toggle(\'einvoice_users_check_fa_toggle\')'
                                   : '';
                        ?>">
                            <i class="fa fa-<?php echo $nb ? ($me ? 'ban' : 'warning') : 'check-square-o text-success'; ?>"></i>
                            <span data-toggle="tooltip" data-placement="bottom" title="<?php echo '🗸 ' . ($nb_users - $nb) . '/' . $nb_users . ' ' . trans('user' . ($nb_users > 1 ? 's' : '')); ?>">
                                <?php echo ($nb ?: $nb_users) . ' ' . trans($ln); ?>
                            </span>
                            <i id="einvoice_users_check_fa_toggle" class="fa fa-<?php echo $nb ? 'user' . ($me ? '' : 's') : 'file-code-o'; ?> fa-margin"></i>
                        </span>
                    </div>

                    <div class="panel-body">
<?php
    if ($xml_templates) {
        if ($client_id) {
            $this->layout->load_view('clients/partial_client_einvoicing');
        } else {
?>
                        <div class="alert alert-warning small" style="font-size:medium;">
                            <i class="fa fa-exclamation-triangle fa-2x"></i>&nbsp;
                            <?php _trans('einvoicing_no_enabled_hint'); ?>
                        </div>
<?php
        } // End if client_id
    } else {
?>
                        <div class="alert alert-info small" style="font-size:medium;">
                            <i class="fa fa-info"></i>&nbsp;
                            <?php _trans('einvoicing_how_enable_hint'); ?>
                            <a href="https://github.com/InvoicePlane/InvoicePlane-e-invoices" target="_blank">InvoicePlane-e-invoices</a>
                        </div>
<?php
    } // End if xml_templates
?>
                    </div>
                </div>

            </div>
<?php
} // End if einvoicing
?>



<!-- EXTENDED INFORMATION -->
            <div class="col-xs-12 col-sm-6" >
                <div class="panel panel-default">

                    <div class="panel-heading">
                        <?php _trans('extended_information'); ?>
                    </div>

                    <div class="panel-body">

                        <div class="form-group col-sm-4">
                            <label for="client_type"><?php _trans('type'); ?>
                                <span title="Can ONLY be set on NEW clients. "
                                      style="display: inline-block; padding: 4px; margin-left: 5px; cursor: help;">
                                    <i class="fa fa-question-circle" style="color: #888;"></i>
                                </span>
                            </label>

<?php if ($this->mdl_clients->form_value('is_update')) :
                        // cannot be changed on update
                        $client_type = $this->mdl_client_extended->form_value('client_type'); ?>
                        <select class="form-control simple-select" disabled>
                            <option value="1" <?= $client_type == 1 ? 'selected' : '' ?>>Client</option>
                            <option value="2" <?= $client_type == 2 ? 'selected' : '' ?>>Supplier</option>
                        </select>
                        <input type="hidden" name="client_type" value="<?= $client_type ?>">

<?php else: 
// can only be entered if new customer!
?>
                            <select name="client_type" id="client_type" class="form-control simple-select" required>
                                <?php foreach ($client_types as $key => $type) { ?>
                                    <option value="<?php echo $key; ?>"
                                        <?php check_select($this->mdl_client_extended->form_value('client_type'), $key); ?>>
                                        <?php echo $type; ?>
                                    </option>
                                <?php } ?>
                            </select>
<?php endif;?>
                        </div>

                        <div class="form-group col-sm-4">
                            <label for="customer_no"><?php _trans('customer_no'); ?>
                                <span title="Will automatically be generated from number sequences module."
                                      style="display: inline-block; padding: 4px; margin-left: 5px; cursor: help;">
                                    <i class="fa fa-question-circle" style="color: #888;"></i>
                                </span>
                            </label>
                            <div class="controls">
                                <input type="text" name="customer_no" id="customer_no" class="form-control"
                                       value="<?php echo $this->mdl_client_extended->form_value('customer_no', true); ?>" readonly="readonly">
                            </div>
                        </div>

                        <div style="clear: both;"></div>

                        <div class="form-group">
                            <label for="contract"><?php _trans('contract'); ?></label>
                            <div class="controls">
                                <input type="text" name="contract" id="contract" class="form-control"
                                       value="<?php echo $this->mdl_client_extended->form_value('contract', true); ?>" >
                            </div>
                        </div>

                        <div class="form-group">
<?php if (ip_mari()): ?>
                            <label for="client_flags"><?php _trans('paragraphs'); ?></label>
<?php else: ?>
                            <label for="client_flags"><?php _trans('client_flags'); ?></label>
<?php endif; ?>
                            <div class="controls">

<?php 
$flags = intval($this->mdl_client_extended->form_value('client_flags'));
if (ip_xtra()||ip_hbk()): ?>
<!-- flags as values 1 2 3 with radiobutton smileys -->
<style>
.cs-smileys {
    font-size: 20px;
}
</style>
<fieldset>
    <label for="1" class="cs-smileys">😠</label>
    <input type="radio" id="1" name="client_flags" value="1"
    <?php if ($flags == 1) echo ' checked="checked" '; ?> >
     &nbsp;&nbsp;&nbsp;&nbsp;

    <label for="2" class="cs-smileys">😐</label>
    <input type="radio" id="2" name="client_flags" value="2"
    <?php if ($flags == 2) echo ' checked="checked" '; ?> >
     &nbsp;&nbsp;&nbsp;&nbsp;

    <label for="3" class="cs-smileys">😄</label>
    <input type="radio" id="3" name="client_flags" value="3"
    <?php if ($flags == 3) echo ' checked="checked" '; ?> >
</fieldset>
<!-- -->
<?php elseif (ip_atac()): ?>
<!-- flags as av dropdown -->
                                <select id="client_flags" name="client_flags" class="form-control">
                                    <option value="0" <?php check_select($flags, '0'
); ?>>
                                        <?php _trans('open'); ?>
                                    </option>
                                    <option value="1" <?php check_select($flags, '1'
); ?>>
                                        <?php _trans('no'); ?>
                                    </option>
                                    <option value="2" <?php check_select($flags, '2'
); ?>>
                                        <?php _trans('yes'); ?>
                                    </option>
                                </select>

<?php elseif (ip_mari()): 
// flags as paragrah checkbox 
            $items = [
                'flag_private' => ['bit' => 1, 'label' => 'Privat'],
                'flag_39'      => ['bit' => 2, 'label' => 'Paragraph 39'],
                'flag_45a'     => ['bit' => 4, 'label' => 'Paragraph 45a'],
                'flag_45b'     => ['bit' => 8, 'label' => 'Paragraph 45b'],
                'flag_125'     => ['bit' => 16, 'label' => 'Paragraph 125'],
            ];
             foreach ($items as $id => $item): ?>
                <span class="flags">
                    <input
                        id="<?= $id ?>"
                        name="<?= $id ?>"
                        type="checkbox"
                        value="1"
                        <?= ($flags & $item['bit']) ? 'checked' : '' ?>
                    >
                    <?= $item['label'] ?>
                </span>
                &nbsp;&nbsp;&nbsp;
            <?php endforeach; ?>
<?php else: ?>
                                <input type="text" name="client_flags" id="client_flags" class="form-control"
                                value="<?php echo $this->mdl_client_extended->form_value('client_flags', true); ?>" >
<?php endif; ?>
                            </div>
                        </div>

<?php if (ip_mari()) { ?>
                        <div class="form-group col-sm-3">
                            <label for="carelevel"><?php _trans('carelevel'); ?></label>
                            <div class="controls">

<?php $current = $this->mdl_client_extended->form_value('carelevel', true); ?>
                            <select name="carelevel" id="carelevel" class="form-control">
<?php for ($i = 0; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>" <?= ($current == $i ? 'selected' : '') ?>>
                                    <?= $i ?>
                                </option>
<?php endfor; ?>
                            </select>
                            </div>
                        </div>

                        <div class="form-group col-sm-3">
                            <label for="carelevel_since"><?php _trans('carelevel_since'); ?></label>
                            <div class="input-group">
                                <input type="text" name="carelevel_since" id="carelevel_since"
                                    class="form-control datepicker"
                                    value="<?php _htmlsc(format_date($this->mdl_client_extended->form_value('carelevel_since'))); ?>">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar fa-fw"></i>
                                </span>
                            </div>
                        </div>

                        <div class="form-group col-sm-3">
                        <label for="carelevel_confirmation"><?php _trans('carelevel_confirmation'); ?></label> &nbsp;
                                <input id="flag_carelevel_confirmation" name="flag_carelevel_confirmation" type="checkbox" value="1"
                                <?php if ($flags & 128) echo 'checked="checked"'; ?> >
                        </div>

                        <div style="clear: both;"></div>

                        <div class="form-group" >
                            <label for="health_insurance_number"><?php _trans('health_insurance_number'); ?></label>
                            <div class="controls">
                                <input type="text" name="health_insurance_number" id="health_insurance_number" class="form-control"
                                       value="<?php echo $this->mdl_client_extended->form_value('health_insurance_number', true); ?>" >
                            </div>
                        </div>
<?php } ?>

                        <div class="form-group">
                            <label for="memo"><?php _trans('memo'); ?></label>
                            <div class="controls">
                                <textarea rows="3" name="memo" id="memo" class="form-control" ><?php echo $this->mdl_client_extended->form_value('memo', true); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
<!-- // EXTENDED INFORMATION -->

    </div>


     <div class="row" >

<!-- ADDRESS -->
            <div class="col-xs-12 col-sm-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?php _trans('address'); ?>
                    </div>

                    <div class="panel-body">
                        <div class="form-group"<?php echo $einvoicingReq; ?>>
                            <label for="client_address_1"><?php _trans('street_address'); ?>
&nbsp;<i class="fa fa-asterisk" style="color: #e07070;"></i>
                             </label>

                            <div class="controls">
                                <input type="text" name="client_address_1" id="client_address_1" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_address_1', true); ?>">
                            </div>
                        </div>

                        <div class="form-group"<?php echo $einvoicingOpt; ?>>
                            <label for="client_address_2"><?php _trans('street_address_2'); ?></label>

                            <div class="controls">
                                <input type="text" name="client_address_2" id="client_address_2" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_address_2', true); ?>">
                            </div>
                        </div>

                        <div class="form-group"<?php echo $einvoicingReq; ?>>
                            <label for="client_zip"><?php _trans('zip_code'); ?>
&nbsp;<i class="fa fa-asterisk" style="color: #e07070;"></i>
                                </label>
                            <div class="controls">
                                <input type="text" name="client_zip" id="client_zip" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_zip', true); ?>">
                            </div>
                        </div>


                        <div class="form-group"<?php echo $einvoicingReq; ?>>
                            <label for="client_city"><?php _trans('city'); ?>
&nbsp;<i class="fa fa-asterisk" style="color: #e07070;"></i>
                        </label>
                            <div class="controls">
                                <input type="text" name="client_city" id="client_city" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_city', true); ?>">
                            </div>
                        </div>

                        <div class="form-group"<?php echo $einvoicingOpt; ?>>
                            <label for="client_state"><?php _trans('state'); ?></label>

                            <div class="controls">
                                <input type="text" name="client_state" id="client_state" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_state', true); ?>">
                            </div>
                        </div>

                        <div class="form-group"<?php echo $einvoicingReq; ?>>
                            <label for="client_country"><?php _trans('country'); ?></label>

                            <div class="controls">
                                <select name="client_country" id="client_country" class="form-control">
                                    <option value=""><?php _trans('none'); ?></option>
                                    <?php foreach ($countries as $cldr => $country) { ?>
                                        <option value="<?php echo $cldr; ?>"
                                            <?php check_select($selected_country, $cldr); ?>
                                        ><?php echo $country ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>


<?php
foreach ($custom_fields as $custom_field) {
    if ($custom_field->custom_field_location == 1) {
        print_field($this->mdl_clients, $custom_field, $custom_values);
    }
}
?>
                    </div>

                </div>

            </div>
<!-- // ADDRESS -->


<!-- CONTACT -->
            <div class="col-xs-12 col-sm-6"><!-- Contact -->

                <div class="panel panel-default">

                    <div class="panel-heading">
                        <?php _trans('contact_information'); ?>
                    </div>

                    <div class="panel-body">
<!--
                        <div class="form-group">
                            <label for="client_invoicing_contact"><?php _trans('contact'); ?> (<?php _trans('invoicing'); ?>)</label>
                            <div class="controls">
                                <input type="text" name="client_invoicing_contact" id="client_invoicing_contact" class="form-control"
                                    value="<?php echo htmlsc($this->mdl_clients->form_value('client_invoicing_contact')); ?>">
                            </div>
                        </div>
-->
                        <div class="form-group">
                            <label for="client_contact_person"><?php _trans('client_contact_person'); ?></label>
                            <div class="controls">
                                <input type="text" name="client_contact_person" id="client_contact_person" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_contact_person', true); ?>" >
                            </div>
                        </div>


                        <div class="form-group">
                            <label for="client_phone"><?php _trans('phone_number'); ?></label>

                            <div class="controls">
                                <input type="text" name="client_phone" id="client_phone" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_phone', true); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="client_fax"><?php _trans('fax_number'); ?></label>

                            <div class="controls">
                                <input type="text" name="client_fax" id="client_fax" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_fax', true); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="client_mobile"><?php _trans('mobile_number'); ?></label>

                            <div class="controls">
                                <input type="text" name="client_mobile" id="client_mobile" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_mobile', true); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="client_email"><?php _trans('email_address'); ?></label>

                            <div class="controls">
                                <input type="text" name="client_email" id="client_email" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_email', true); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="client_web"><?php _trans('web_address'); ?></label>

                            <div class="controls">
                                <input type="text" name="client_web" id="client_web" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_web', true); ?>">
                            </div>
                        </div>

<?php
foreach ($custom_fields as $custom_field) {
    if ($custom_field->custom_field_location == 2) {
        print_field($this->mdl_clients, $custom_field, $custom_values);
    }
}
?>
                    </div>

                </div>

            </div>
<!-- //CONTACT -->


</div>
<div class="row" >

<!-- CONDITIONS -->
        <div class="col-xs-12 col-sm-6">
                <div class="panel panel-default">

                    <div class="panel-heading">
                        <?php _trans('terms_conditions'); ?>
                                <span title="Appears on invoices - refers to INCOTERMS. "
                                      style="display: inline-block; padding: 4px; margin-left: 5px; cursor: help;">
                                    <i class="fa fa-question-circle" style="color: #888;"></i>
                                </span>
                    </div>

                    <div class="panel-body">

                        <div class="form-group">
                            <label for="payment_terms"><?php _trans('payment_terms'); ?></label>
                            <div class="controls">
                                <textarea rows="3" name="payment_terms" id="payment_terms" class="form-control" ><?php echo $this->mdl_client_extended->form_value('payment_terms', true); ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="delivery_terms"><?php _trans('delivery_terms'); ?></label>
                            <div class="controls">
                                <textarea rows="3" name="delivery_terms" id="delivery_terms" class="form-control" ><?php echo $this->mdl_client_extended->form_value('delivery_terms', true); ?></textarea>
                            </div>
                        </div>
                    </div>

                </div>
        </div>
<!-- //CONDITIONS -->


<!-- PERSONAL -->
        <div class="col-xs-12 col-sm-6" >

                <div class="panel panel-default">

                    <div class="panel-heading">
                        <?php _trans('personal_information'); ?>
                    </div>

                    <div class="panel-body">
                        <div class="form-group">
                            <label for="client_gender"><?php _trans('gender'); ?>
&nbsp;<i class="fa fa-asterisk" style="color: #e07070;"></i>
                            </label>
                            <div class="controls">
                                <select name="client_gender" id="client_gender"
                                        class="form-control simple-select" data-minimum-results-for-search="Infinity">
<?php
$genders = [
    trans('gender_male'),
    trans('gender_female'),
    trans('gender_other'),
];
$client_gender = $this->mdl_clients->form_value('client_gender');
foreach ($genders as $key => $val) {
?>
                                    <option value="<?php echo $key; ?>" <?php check_select($key, $client_gender) ?>>
                                        <?php echo $val; ?>
                                    </option>
<?php
}
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
<?php
$client_title    = $this->mdl_clients->form_value('client_title');
$is_custom_title = null === ClientTitleEnum::tryFrom($client_title);
?>
                            <label for="client_title"><?php _trans('client_title'); ?></label>
                            <select name="client_title" id="client_title" class="form-control simple-select">
<?php
foreach ($client_title_choices as $client_title_choice) {
?>
                                <option
                                    value="<?php echo $client_title_choice; ?>"
                                    <?php echo $client_title === $client_title_choice ? 'selected' : ''; ?>
                                    <?php echo $is_custom_title && $client_title_choice === ClientTitleEnum::CUSTOM ? 'selected' : ''; ?>
                                >
                                    <?php echo ucfirst(trans($client_title_choice)); ?>
                                </option>
<?php
}
?>
                            </select>
                        </div>

                        <div class="form-group">
                            <input
                                id="client_title_custom"
                                name="client_title_custom"
                                type="text"
                                class="form-control<?php echo $is_custom_title ? '' : ' hidden' ?>"
                                placeholder="<?php _htmlsc(trans('custom_title')); ?>"
                                value="<?php _htmlsc($client_title); ?>"
                            >
                        </div>

                        <div class="form-group has-feedback">
                            <label for="client_birthdate"><?php _trans('birthdate'); ?></label>
                            <div class="input-group">
                                <input type="text" name="client_birthdate" id="client_birthdate"
                                    class="form-control datepicker"
                                    value="<?php _htmlsc(format_date($this->mdl_clients->form_value('client_birthdate'))); ?>">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar fa-fw"></i>
                                </span>
                            </div>
                        </div>

<?php
if ($this->mdl_settings->setting('sumex') == '1') {
    $avs           = format_avs($this->mdl_clients->form_value('client_avs'));
    $insuredNumber = $this->mdl_clients->form_value('client_insurednumber');
    $veka          = $this->mdl_clients->form_value('client_veka');
?>

                        <div class="form-group">
                            <label for="client_avs"><?php _trans('sumex_ssn'); ?></label>
                            <div class="controls">
                                <input type="text" name="client_avs" id="client_avs" class="form-control"
                                       value="<?php _htmlsc($avs); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="client_insurednumber"><?php _trans('sumex_insurednumber'); ?></label>
                            <div class="controls">
                                <input type="text" name="client_insurednumber" id="client_insurednumber" class="form-control"
                                       value="<?php _htmle($insuredNumber); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="client_veka"><?php _trans('sumex_veka'); ?></label>
                            <div class="controls">
                                <input type="text" name="client_veka" id="client_veka" class="form-control"
                                       value="<?php _htmle($veka); ?>">
                            </div>
                        </div>

<?php
} // End if sumex
?>


<?php
$default_custom = false;
foreach ($custom_fields as $custom_field) {
    if ( ! $default_custom && ! $custom_field->custom_field_location) {
        $default_custom = true;
    }

    if ($custom_field->custom_field_location == 3) {
        print_field($this->mdl_clients, $custom_field, $custom_values);
    }
}
?>
                    </div>
                </div>
            </div>
<!-- // PERSONAL -->
  </div>


  <div class="row" >

<!-- ADDRESSES -->
            <div class="col-xs-12" id="client-addresses" data-client-id="<?php echo (int) $client_id; ?>">
<?php if ($client_id): ?>
                <?php $this->load->view('clients/partial_client_address_section', ['client_id' => (int) $client_id]); ?>
<?php else: ?>
                <div class="alert alert-info">
                    Rechnungs- und Lieferadressen können nach dem ersten Speichern des Kunden angelegt werden.
                </div>
<?php endif; ?>
            </div>
<!-- // ADDRESSES -->

  </div>

<div class="row">
<!-- TAX -->
            <div class="col-xs-12 col-sm-6">

                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?php _trans('tax_information'); ?>
                    </div>

                    <div class="panel-body">
                        <div class="form-group"<?php echo $itsCompany ? $einvoicingB2B : $einvoicingOpt; ?>>
                            <label for="client_vat_id"><?php _trans('vat_id'); ?> (<?php _trans($itsCompany ? 'required_field' : 'optional'); ?>)</label>

                            <div class="controls">
                                <input type="text" name="client_vat_id" id="client_vat_id" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_vat_id', true); ?>">
                            </div>
                        </div>

                        <div class="form-group"<?php echo $einvoicingReq; ?>>
                            <label for="client_tax_code"><?php _trans('tax_code'); ?></label>

                            <div class="controls">
                                <input type="text" name="client_tax_code" id="client_tax_code" class="form-control"
                                       value="<?php echo $this->mdl_clients->form_value('client_tax_code', true); ?>">
                            </div>
                        </div>

<?php
foreach ($custom_fields as $custom_field) {
    if ($custom_field->custom_field_location == 4) {
        print_field($this->mdl_clients, $custom_field, $custom_values);
    }
}
?>
                    </div>
                </div>
            </div>
<!-- // TAX -->


<!-- BANK_INFORMATION -->
            <div class="col-xs-12 col-sm-6" >
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?php _trans('bank_information'); ?>
                    </div>

                    <div class="panel-body">
                        <div class="form-group">
                            <label for="direct_debit"><?php _trans('direct_debit'); ?></label>
                            <div class="controls">
                                <input type="text" name="direct_debit" id="direct_debit" class="form-control"
                                       value="<?php echo $this->mdl_client_extended->form_value('direct_debit', true); ?>" >
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="bank_name"><?php _trans('bank_name'); ?></label>
                            <div class="controls">
                                <input type="text" name="bank_name" id="bank_name" class="form-control"
                                       value="<?php echo $this->mdl_client_extended->form_value('bank_name', true); ?>" >
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="bank_bic"><?php _trans('bank_bic'); ?></label>
                            <div class="controls">
                                <input type="text" name="bank_bic" id="bank_bic" class="form-control"
                                       value="<?php echo $this->mdl_client_extended->form_value('bank_bic', true); ?>" >
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="bank_iban"><?php _trans('bank_iban'); ?></label>
                            <div class="controls">
                                <input type="text" name="bank_iban" id="bank_iban" class="form-control"
                                       value="<?php echo $this->mdl_client_extended->form_value('bank_iban', true); ?>" >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<!-- // BANK -->
</div>

<?php
if ($default_custom) {
?>
        <div class="row"><!-- Custom -->
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
        print_field($this->mdl_clients, $custom_field, $custom_values, $classes[0], $classes[1], $classes[2], $classes[3]);
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
</form>


<?php
// Felder des Adress-Modals: Feld => [Label, Pflicht]
$addr_fields = [
    'salutation'     => [trans('salutation'), false],
    'contact_person' => [trans('contact_person'), false],
    'name'           => [trans('name'), true],
    'name2'          => [trans('name2'), false],
    'address_1'      => [trans('street_address'), true],
    'address_2'      => [trans('street_address_2'), false],
    'zip'            => [trans('zip_code'), true],
    'city'           => [trans('city'), true],
    'state'          => [trans('state'), false],
    'country'        => [trans('country'), false],
    'phone'          => [trans('phone'), false],
    'email'          => [trans('email'), false],
];
?>
<!-- Adress-Modal -->
<div id="addressEditModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="addr_address_id">
                <input type="hidden" id="addr_address_type">

<?php if (get_setting('invoice_address_helper')): ?>
                <div id="addr-search-wrap">
                    <input type="text" id="addr-search" class="form-control" placeholder="Vorhandene Adresse suchen (Krankenkasse, Name ...)">
                    <div id="addr-search-results" class="list-group"></div>
                    <hr>
                </div>
<?php endif; ?>

                <div class="row">
<?php foreach ($addr_fields as $key => [$label, $required]): ?>
                    <div class="col-xs-12 col-sm-6">
                        <div class="form-group">
                            <label for="addr_<?php echo $key; ?>">
                                <?php echo htmlsc($label); ?>
                                <?php if ($required): ?>&nbsp;<i class="fa fa-asterisk" style="color: #e07070;"></i><?php endif; ?>
                            </label>
<?php if ($key === 'country'): ?>
                            <select id="addr_country" class="form-control">
                                <option value=""><?php _trans('none'); ?></option>
<?php foreach ($countries as $cldr => $country): ?>
                                <option value="<?php echo $cldr; ?>"><?php echo $country; ?></option>
<?php endforeach; ?>
                            </select>
<?php else: ?>
                            <input type="text" id="addr_<?php echo $key; ?>" class="form-control">
<?php endif; ?>
                        </div>
                    </div>
<?php endforeach; ?>
                </div>

                <div id="addr-error" class="alert alert-danger hidden"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-success" id="addr-save"><i class="fa fa-check"></i> Speichern</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    const FIELDS = ['salutation', 'contact_person', 'name', 'name2', 'address_1', 'address_2', 'zip', 'city', 'state', 'country', 'phone', 'email'];
    const FILL   = ['salutation', 'contact_person', 'name', 'name2', 'address_1', 'address_2', 'zip', 'city'];
    const TITLES = {<?php echo Mdl_Client_Addresses::TYPE_INVOICE; ?>: 'Rechnungsadresse', <?php echo Mdl_Client_Addresses::TYPE_DELIVERY; ?>: 'Lieferadresse'};
    const URL    = {
        section: '<?php echo site_url('clients/client_addresses/section'); ?>',
        save:    '<?php echo site_url('clients/client_addresses/save'); ?>',
        del:     '<?php echo site_url('clients/client_addresses/delete'); ?>',
        search:  '<?php echo site_url('clients/ajax/search_addresses'); ?>'
    };

    const $box     = $('#client-addresses');
    const $modal   = $('#addressEditModal');
    const clientId = $box.data('client-id');
    if (!clientId) return;

    function refresh() {
        $.get(URL.section + '/' + clientId, function (html) { $box.html(html); });
    }

    function openModal(type, a) {
        a = a || {};
        $('#addr_address_id').val(a.address_id || '');
        $('#addr_address_type').val(type);
        FIELDS.forEach(function (f) { $('#addr_' + f).val(a[f] || ''); });
        $('#addr-error').addClass('hidden').text('');
        $('#addr-search').val('');
        $('#addr-search-results').empty();
        // Suche nur beim Neuanlegen einer Rechnungsadresse
        $('#addr-search-wrap').toggle(parseInt(type, 10) === <?php echo Mdl_Client_Addresses::TYPE_INVOICE; ?> && !a.address_id);
        $modal.find('.modal-title').text(TITLES[type] + (a.address_id ? ' bearbeiten' : ' hinzufügen'));
        $modal.modal('show');
    }

    $box.on('click', '.address-add', function (e) {
        e.preventDefault();
        openModal($(this).data('type'));
    });

    $box.on('click', '.address-edit', function (e) {
        e.preventDefault();
        const a = $(this).data('address');
        openModal(a.address_type, a);
    });

    $box.on('click', '.address-delete', function (e) {
        e.preventDefault();
        if (!confirm('Adresse wirklich löschen? Bereits erstellte Rechnungen und Angebote behalten ihre Anschrift.')) return;
        $.post(URL.del, {client_id: clientId, address_id: $(this).data('id')}, null, 'json').always(refresh);
    });

    $('#addr-save').on('click', function () {
        const addr = {};
        FIELDS.forEach(function (f) { addr[f] = $('#addr_' + f).val(); });

        $.post(URL.save, {
            client_id: clientId,
            address_id: $('#addr_address_id').val(),
            address_type: $('#addr_address_type').val(),
            addr: addr
        }, null, 'json').done(function (r) {
            if (r && r.success === 1) {
                $modal.modal('hide');
                refresh();
            } else {
                $('#addr-error').removeClass('hidden').text((r && r.error) || 'Speichern fehlgeschlagen.');
            }
        }).fail(function () {
            $('#addr-error').removeClass('hidden').text('Speichern fehlgeschlagen.');
        });
    });

    $('#addr-search').on('keyup', function () {
        const q = $(this).val(), $res = $('#addr-search-results').empty();
        if (q.length < 2) return;
        $.getJSON(URL.search, {q: q}, function (data) {
            (Array.isArray(data) ? data : []).forEach(function (addr) {
                const text = [addr.name, addr.name2, [addr.zip, addr.city].filter(Boolean).join(' ')].filter(Boolean).join(', ');
                $('<a href="#" class="list-group-item">').text(text).data('address', addr).appendTo($res);
            });
        });
    });

    $('#addr-search-results').on('click', 'a', function (e) {
        e.preventDefault();
        const addr = $(this).data('address');
        FILL.forEach(function (f) { $('#addr_' + f).val(addr[f] || ''); });
        $('#addr-search').val('');
        $('#addr-search-results').empty();
    });
});
</script>
<!-- // Adress-Modal -->
