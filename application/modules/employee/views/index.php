<div id="headerbar">
    <h1 class="headerbar-title"><?php _trans('dashboard'); ?></h1>
</div>


<div id="content" >
    <?php echo $this->layout->load_view('layout/alerts'); ?>

<div class="col-xs-12">
<?php
get_greeting(); echo ", ";
show_user($this->session->userdata('user_name'),
    $this->session->userdata('user_email'),
    $this->session->userdata('user_type'));
?>
<br> <br>
</div>

<!-- -->
        <div class="col-xs-12">
            <div id="panel-quick-actions" class="panel panel-default quick-actions">
                <div class="panel-heading">
                    <b><?php _trans('quick_actions'); ?></b>
                </div>
                <div class="btn-group btn-group-justified no-margin">

<!--
            <a href="<?php echo site_url('employee/user_clients/index'); ?>" class="btn btn-default">
                <i class="fa fa-user fa-margin"></i>
                <span ><?php _trans('view_my_clients'); ?></span>
            </a>
-->
            <a href="<?php echo site_url('employee/timesheets/index'); ?>" class="btn btn-default">
                <i class="fa fa-briefcase fa-margin"></i>
                <span ><?php _trans('worktime_overview'); ?></span>
            </a>

            <a href="<?php echo site_url('employee/timesheets/form'); ?>" class="btn btn-default">
                <i class="fa fa-briefcase fa-margin"></i>
                <span ><?php _trans('enter_my_worktime'); ?></span>
            </a>

            <a href="<?php echo site_url('employee/timesheets/evidence_print/0/0'); ?>" class="btn btn-default">
                <i class="fa fa-briefcase fa-margin"></i>
                <span ><?php _trans('print_timesheet'); ?></span>
            </a>

                </div>
            </div>
        </div>
<!--
    <div class="btn-group no-margin" >
        <a href="<?php echo site_url('employee/clients/status/active'); ?>" class="btn btn-default">
                        <i class="fa fa-user fa-margin"></i>
                        <span ><?php _trans('view_clients'); ?></span>
        </a>

    <form class="navbar-form navbar-left" method="post" action="<?php echo site_url('employee/dashboard/filter_clients'); ?>">
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

    <br>
  </div>
-->

<!-- -->


</div>
