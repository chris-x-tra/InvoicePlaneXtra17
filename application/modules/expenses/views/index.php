<?php
  $this->load->helper('custom_values_helper');  // <- format_date
?>
<div id="headerbar">

    <h1 class="headerbar-title"><?php _trans('expenses'); ?></h1>

    <div class="headerbar-item pull-right">
        <button type="button" class="btn btn-default btn-sm submenu-toggle hidden-lg"
                data-toggle="collapse" data-target="#ip-submenu-collapse">
            <i class="fa fa-bars"></i> <?php _trans('submenu'); ?>
        </button>
        <a class="create-expense btn btn-sm btn-primary" href="<?php echo site_url('expenses/form'); ?>">
            <i class="fa fa-plus"></i> <?php _trans('new'); ?>
        </a>
    </div>

    <div class="headerbar-item pull-right visible-lg">
        <?php echo pager(site_url('expenses/status/' . $this->uri->segment(3)), 'mdl_expenses'); ?>
    </div>

    <div class="headerbar-item pull-right visible-lg">
        <div class="btn-group btn-group-sm index-options">
            <a href="<?php echo site_url('expenses/status/all'); ?>"
               class="btn <?php echo $status == 'all' ? 'btn-primary' : 'btn-default' ?>">
                <?php _trans('all'); ?>
            </a>
            <a href="<?php echo site_url('expenses/status/paid'); ?>"
               class="btn  <?php echo $status == 'paid' ? 'btn-primary' : 'btn-default' ?>">
                <?php _trans('paid'); ?>
            </a>
            <a href="<?php echo site_url('expenses/status/open'); ?>"
               class="btn  <?php echo $status == 'open' ? 'btn-primary' : 'btn-default' ?>">
                <?php _trans('open'); ?>
            </a>
            <a href="<?php echo site_url('expenses/status/overdue'); ?>"
               class="btn  <?php echo $status == 'overdue' ? 'btn-primary' : 'btn-default' ?>">
                <?php _trans('overdue'); ?>
            </a>
        </div>
    </div>

</div>

<div id="submenu">
    <div class="collapse clearfix" id="ip-submenu-collapse">

        <div class="submenu-row">
            <?php echo pager(site_url('expenses/status/' . $this->uri->segment(3)), 'mdl_expenses'); ?>
        </div>

        <div class="submenu-row">
            <div class="btn-group btn-group-sm index-options">
		    <a href="<?php echo site_url('expenses/status/all'); ?>"
		       class="btn <?php echo $status == 'all' ? 'btn-primary' : 'btn-default' ?>">
			<?php _trans('all'); ?>
		    </a>
		    <a href="<?php echo site_url('expenses/status/paid'); ?>"
		       class="btn  <?php echo $status == 'paid' ? 'btn-primary' : 'btn-default' ?>">
			<?php _trans('paid'); ?>
		    </a>
		    <a href="<?php echo site_url('expenses/status/open'); ?>"
		       class="btn  <?php echo $status == 'open' ? 'btn-primary' : 'btn-default' ?>">
			<?php _trans('open'); ?>
		    </a>
		    <a href="<?php echo site_url('expenses/status/overdue'); ?>"
		       class="btn  <?php echo $status == 'overdue' ? 'btn-primary' : 'btn-default' ?>">
			<?php _trans('overdue'); ?>
		    </a>
            </div>
        </div>

    </div>
</div>

<div id="content" class="table-content">
    <div id="filter_results">
        <?php $this->layout->load_view('expenses/partial_expense_table', array('expenses' => $expenses)); ?>
    </div>
</div>
