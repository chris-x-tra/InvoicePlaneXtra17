    <div class="btn-group no-margin" >
        <a href="<?php echo site_url('clients/status/active'); ?>" class="btn btn-default">
                <i class="fa fa-user fa-margin"></i>
                <span class="hidden-xs"><?php _trans('view_clients'); ?></span>
        </a>
        <a href="<?php echo site_url('clients/form'); ?>" class="btn btn-default">
                <i class="fa fa-user fa-margin"></i>
                <span class="hidden-xs"><?php _trans('add_client'); ?></span>
        </a>

        <!-- hier kundenfilter mit submit -->
        <form class="navbar-form navbar-left" method="post" action="<?php echo site_url('dashboard/filter_clients'); ?>">
            <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
               value="<?php echo $this->security->get_csrf_hash() ?>">
            <div class="form-group">
                    <input id="filter" name="search" type="text" class="search-query form-control input-sm"
                    placeholder="Kunde suchen">
            </div>
            <button type="submit" id="search" class="button" >
                    <?php echo lang('customer_search'); ?>
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
        <form class="navbar-form navbar-left" method="post" action="<?php echo site_url('dashboard/filter_invoices'); ?>">
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
