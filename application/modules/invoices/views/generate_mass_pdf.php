
<div id="headerbar">
    <h1 class="headerbar-title"><?php _trans('generate_mass_pdf'); ?></h1>
</div>

<div id="content">
    <?php echo $this->layout->load_view('layout/alerts'); ?>
    <div id="invoice_form">
        <div class="invoice">
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-5">
                <form method="post">
                    <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
                        value="<?php echo $this->security->get_csrf_hash() ?>">

                                <div class="form-group">
                                    <label for="from_nr">
                                        <?php _trans('from_nr'); ?>
                                    </label>
                                    <input id="from_invoiceno" name="from_invoiceno" type="text" class="form-control" value="<?= $from_invoiceno; ?>">
                                    <label for="to_nr">
                                        <?php _trans('to_nr'); ?>
                                    </label>
                                    <input id="to_invoiceno" name="to_invoiceno" type="text" class="form-control" value="<?= $to_invoiceno; ?>">
<!-- only if template chooser - TODO -->
<!--
                                    <label for="invoice_template">
                                        <?php _trans('invoice_template'); ?>
                                    </label>

                                        <select name="invoice_template" id="invoice_template"
                                                class="form-control simple-select" data-minimum-results-for-search="Infinity">
                                            <?php
                                            foreach ($invoice_pdf_templates as $key => $val) { ?>
                                                <option value="<?php echo $val; ?>" >
                                                    <?php echo $val; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
-->
                                </div>
                        <br />
                        <input id="do_generate" name="do_generate" value="1" type="hidden"></input>
                        <input type = "submit" value = "Generieren" /> 
                </form>
                </div>
           <div>
        </div>
    </div>
</div>
