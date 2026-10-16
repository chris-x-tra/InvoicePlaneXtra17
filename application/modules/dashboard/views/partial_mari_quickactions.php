
    <div class="btn-group no-margin" >
        <a href="<?php echo site_url('clients/status/active'); ?>" class="btn btn-default">
                <i class="fa fa-user fa-margin"></i>
                <span class="hidden-xs"><?php _trans('view_clients'); ?></span>
        </a>
        <a href="<?php echo site_url('clients/form'); ?>" class="btn btn-default">
                <i class="fa fa-user fa-margin"></i>
                <span class="hidden-xs"><?php _trans('add_client'); ?></span>
        </a>

<br>
        <!-- hier kundenfilter mit submit -->
<form class="navbar-form navbar-left" 
style="border: 1px solid; border-color:#ccc; border-radius:4px; padding: 5px;"
method="get" action="<?php echo site_url('clients/status/active'); ?>">

  <div class="btn-group" >
        <?php
        //$paragraphs = array(1 => '§39', 2 => '§45a', 4 => '§45b', 8 => '§125', 16 => 'privat');
        $paragraphs = array(1 => '§39', 2 => '§45a', 4 => '§45b', 8 => '§125');
        $checked    = (array) $this->input->get('flags');
        foreach ($paragraphs as $bit => $label) { ?>
            <label class="checkbox-inline">
                <input type="checkbox" name="flags[]" value="<?php echo $bit; ?>"
                    <?php echo in_array((string) $bit, $checked, true) ? 'checked' : ''; ?>>
                <?php echo $label; ?>
            </label>
        <?php } ?>
    </div>

    <div class="form-group">
        <select name="carelevel" class="form-control input-sm">
            <option value="">PG</option>
            <?php for ($i = 1; $i <= 6; $i++) { ?>
                <option value="<?php echo $i; ?>" <?php echo ((int) $this->input->get('carelevel') === $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="form-group">
        <input id="filter" name="search" type="text" class="search-query form-control input-sm"
               placeholder="Kunde suchen" value="<?php echo htmlspecialchars((string) $this->input->get('search')); ?>">
    </div>

    <button type="submit" id="search" class="button">suchen</button>
</form>
        <!-- /END hier kundenfilter -->

    <!-- hier kunden-nr mit submit -->
    <form class="navbar-form navbar-left" method="post" 
style="border: 1px solid; border-color:#ccc; border-radius:4px; padding: 5px; margin-left: 10px;"
    action="<?php echo site_url('dashboard/customer_no'); ?>">
            <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
               value="<?php echo $this->security->get_csrf_hash() ?>">
        <div class="btn-group" >
            MAR-K <input id="filter" name="customer_no" type="text" class="search-query form-control input-sm"
                    placeholder="Kd-Nr" style="width:5em;">
        </div>
        <button type="submit" id="search" class="button" >
            <i class="fa fa-arrow-right"></i>
        </button>
    </form>
    <!-- /END hier kundenfilter -->

  </div>

  <div class="btn-group no-margin" >
        <a href="<?php echo site_url('invoices/status/all'); ?>" class="btn btn-default">
            <i class="fa fa-file-text fa-margin"></i>
            <span class="hidden-xs"><?php _trans('view_invoices'); ?></span>
        </a>
        <a href="javascript:void(0)" class="create-invoice btn btn-default">
            <i class="fa fa-file-text fa-margin"></i>
            <span class="hidden-xs"><?php _trans('create_invoice'); ?></span>
        </a>
        <a href="<?php echo site_url('payments/form'); ?>" class="btn btn-default">
            <i class="fa fa-credit-card fa-margin"></i>
            <span class="hidden-xs"><?php _trans('enter_payment'); ?></span>
        </a>

        <!-- hier rechnungsfilter mit submit -->
        <form class="navbar-form navbar-left" 
        method="post" action="<?php echo site_url('dashboard/filter_invoices'); ?>">
            <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
               value="<?php echo $this->security->get_csrf_hash() ?>">
            <div class="form-group">
                    <input id="filter" name="search-i" type="text" class="search-query form-control input-sm"
                            placeholder="Rechnungen filtern">
            </div>
            <button type="submit" id="search1" class="button" type="button">
                    <?php echo lang('invoice_search'); ?>
            </button>
       </form>
       <!-- /END hier rechnungsfilter -->
  </div>

  <div class="btn-group no-margin">
        <a href="<?php echo site_url('user_clients/index'); ?>" class="btn btn-default">
                <i class="fa fa-user fa-margin"></i>
                <span class="hidden-xs"><?php _trans('view_my_clients'); ?></span>
        </a>
       <a href="<?php echo site_url('timesheets/form'); ?>" class="btn btn-default">
           <i class="fa fa-briefcase fa-margin"></i>
           <span class="hidden-xs"><?php _trans('enter_my_worktime'); ?></span>
       </a>
  </div>

<span style="float: right;">
<?php
get_greeting(); echo ", ";
show_user($this->session->userdata('user_name'), 
    $this->session->userdata('user_email'), 
    $this->session->userdata('user_type'));
?>
</span>
