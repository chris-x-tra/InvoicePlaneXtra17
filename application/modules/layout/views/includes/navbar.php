<nav class="navbar navbar-inverse" role="navigation">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#ip-navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <?php echo trans('menu') ?> &nbsp; <i class="fa fa-bars"></i>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="ip-navbar-collapse">
            <ul class="nav navbar-nav">


    <li class="nav-dashboard">
        <a href="<?php echo site_url('dashboard/index'); ?>" class="logo-link" title="<?= _trans('dashboard') ?>" >
<?php if (ENVIRONMENT == 'development') { ?>
            <img src="/assets/core/img/invoiceplane-dev-sm.png" alt="dev" class="logo">
<?php } elseif (ip_atac()) { ?>
            <img src="/assets/core/img/invoiceplane-atac-sm.png" alt="atac" class="logo">
<?php } elseif (ip_mari()) { ?>
            <img src="/assets/core/img/invoiceplane-mar-sm.png" alt="mari" class="logo">
<?php } else { ?>
            <img src="/assets/core/img/invoiceplane-xtra-sm.png" alt="x-tra" class="logo">
<?php } ?>
            <span class="dashboard-text"><?= _trans('dashboard') ?></span>
	</a>
    </li>


                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-caret-down"></i> &nbsp;
                        <span class="hidden-md"><?php _trans('clients'); ?></span>
                        <i class="visible-md-inline fa fa-users"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><?php echo anchor('clients/form', trans('add_client')); ?></li>
                        <li><?php echo anchor('clients/index', trans('view_clients')); ?></li>
                        <li><?php echo anchor('clients/status/supplier', trans('view_suppliers')); ?></li>
<?php if (get_setting('new_notes_read')): ?>
                        <li><?php echo anchor('clients/view_notes/0', trans('view_notes')); ?></li>
<?php endif; ?>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-caret-down"></i> &nbsp;
                        <span class="hidden-md"><?php _trans('quotes'); ?></span>
                        <i class="visible-md-inline fa fa-file"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="#" class="create-quote"><?php _trans('create_quote'); ?></a></li>
                        <li><?php echo anchor('quotes/index', trans('view_quotes')); ?></li>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-caret-down"></i> &nbsp;
                        <span class="hidden-md"><?php _trans('invoices'); ?></span>
                        <i class="visible-md-inline fa fa-file-text"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="#" class="create-invoice"><?php _trans('create_invoice'); ?></a></li>
                        <li><?php echo anchor('invoices/index', trans('view_invoices')); ?></li>
                        <li><?php echo anchor('invoices/recurring/index', trans('view_recurring_invoices')); ?></li>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-caret-down"></i> &nbsp;
                        <span class="hidden-md"><?php _trans('payments'); ?></span>
                        <i class="visible-md-inline fa fa-credit-card"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><?php echo anchor('payments/form', trans('enter_payment')); ?></li>
                        <li><?php echo anchor('payments/index', trans('view_payments')); ?></li>
                        <li><?php echo anchor('payments/online_logs', trans('view_payment_logs')); ?></li>
                        <li role="separator" class="divider"></li>
                        <li><?php echo anchor('payments/receipt_form', trans('create_payment_receipt')); ?></li>
                        <li><?php echo anchor('payments/receipt_index', trans('view_payment_receipts')); ?></li>
                        <li role="separator" class="divider"></li>
                        <li><?php echo anchor('payments/eur', trans('payments_eur')); ?></li>
                        <li><?php echo anchor('payments/eur_details', trans('payments_eur_details')); ?></li>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-caret-down"></i> &nbsp;
                        <span class="hidden-md"><?php _trans('expenses'); ?></span>
                        <i class="visible-md-inline fa fa-credit-card"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><?php echo anchor('expenses/form', trans('create_expense')); ?></li>
                        <li><?php echo anchor('expenses/index', trans('view_expenses')); ?></li>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-caret-down"></i> &nbsp;
                        <span class="hidden-md"><?php _trans('products'); ?></span>
                        <i class="visible-md-inline fa fa-database"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><?php echo anchor('products/form', trans('create_product')); ?></li>
                        <li><?php echo anchor('products/index', trans('view_products')); ?></li>
                        <li><?php echo anchor('families/index', trans('view_product_families')); ?></li>
                        <li><?php echo anchor('units/index', trans('view_product_units')); ?></li>
                    </ul>
                </li>

                <li class="dropdown<?php echo get_setting('projects_enabled') == 1 ? '' : ' hidden'; ?>">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-caret-down"></i> &nbsp;
                        <span class="hidden-md"><?php _trans('tasks'); ?></span>
                        <i class="visible-md-inline fa fa-check-square-o"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><?php echo anchor('tasks/form', trans('create_task')); ?></li>
                        <li><?php echo anchor('tasks/index', trans('view_tasks')); ?></li>
                        <li role="separator" class="divider"></li>
                        <li><?php echo anchor('projects/form', trans('create_project')); ?></li>
                        <li><?php echo anchor('projects/index', trans('view_projects')); ?></li>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-caret-down"></i> &nbsp;
                        <span class="hidden-md"><?php _trans('reports'); ?></span>
                        <i class="visible-md-inline fa fa-bar-chart"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><?php echo anchor('reports/invoice_aging', trans('invoice_aging')); ?></li>
                        <li><?php echo anchor('reports/payment_history', trans('payment_history')); ?></li>
                        <li><?php echo anchor('reports/sales_by_client', trans('sales_by_client')); ?></li>
                        <li><?php echo anchor('reports/sales_by_year', trans('sales_by_date')); ?></li>
                        <li><?php echo anchor('reports/invoices_per_client', trans('invoices_per_client')); ?></li>
                    </ul>
                </li>

            </ul>

            <?php if (isset($filter_display) && $filter_display == true) { ?>
                <?php $this->layout->load_view('filter/jquery_filter'); ?>
                <form class="navbar-form navbar-left" role="search" onsubmit="return false;">
                    <div class="form-group">
                        <input id="filter" type="text" class="search-query form-control"
                               placeholder="<?php echo $filter_placeholder; ?>">
                    </div>
                </form>
            <?php } ?>

            <ul class="nav navbar-nav navbar-right">
                <li>
                    <a href="https://wiki.invoiceplane.com/" target="_blank"
                       class="tip icon" title="<?php _trans('documentation'); ?>"
                       data-placement="bottom">
                        <i class="fa fa-question-circle"></i>
                        <span class="visible-xs">&nbsp;<?php _trans('documentation'); ?></span>
                    </a>
                </li>

                <li class="dropdown">
                    <a href="#" class="tip icon dropdown-toggle" data-toggle="dropdown"
                       title="<?php _trans('settings'); ?>"
                       data-placement="bottom">
                        <i class="fa fa-cogs"></i>
                        <span class="visible-xs">&nbsp;<?php _trans('settings'); ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><?php echo anchor('number_sequences/index', trans('number_sequences')); ?></li>
                        <li><?php echo anchor('custom_fields/index', trans('custom_fields')); ?></li>
                        <li><?php echo anchor('email_templates/index', trans('email_templates')); ?></li>
                        <li><?php echo anchor('invoice_groups/index', trans('invoice_groups')); ?></li>
                        <li><?php echo anchor('invoices/archive', trans('invoice_archive')); ?></li>
                        <!-- // temporarily disabled
                        <li><?php echo anchor('item_lookups/index', trans('item_lookups')); ?></li>
                        -->
                        <li><?php echo anchor('payment_methods/index', trans('payment_methods')); ?></li>
                        <li><?php echo anchor('tax_rates/index', trans('tax_rates')); ?></li>
                        <li><?php echo anchor('users/index', trans('user_accounts')); ?></li>
                        <li class="divider hidden-xs hidden-sm"></li>
                        <li><?php echo anchor('settings', trans('system_settings')); ?></li>
                        <li><?php echo anchor('import', trans('import_data')); ?></li>
                    </ul>
                </li>
                <li>
                    <a href="<?php echo site_url('users/form/'
                        . $this->session->userdata('user_id')); ?>"
                       class="tip icon" data-placement="bottom"
                       title="<?php
                        _htmlsc($this->session->userdata('user_name'));
                        if ($this->session->userdata('user_company')) {
                            echo ' (' . htmlsc($this->session->userdata('user_company')) . ')';
                        }
                        ?>">
                        <i class="fa fa-user"></i>
                        <span class="visible-xs">&nbsp;<?php
                            _htmlsc($this->session->userdata('user_name'));
                        if ($this->session->userdata('user_company')) {
                            echo ' (' . htmlsc($this->session->userdata('user_company')) . ')';
                        }
                        ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo site_url('sessions/logout'); ?>"
                       class="tip icon logout" data-placement="bottom"
                       title="<?php _trans('logout'); ?>">
                        <i class="fa fa-power-off"></i>
                        <span class="visible-xs">&nbsp;<?php _trans('logout'); ?></span>
                    </a>
                </li>
            </ul>
        </div>
<!-- -->
<?php
// check if there are new notes
if (get_setting('new_notes_read')) {
    // if no timestamp in session set yesterday
    if (!$this->session->userdata('last_notes_read_ts')) {
        $this->session->set_userdata('last_notes_read_ts', date('Y-m-d H:i:s', strtotime('-1 day')));
    }
    $last_ts = $this->session->userdata('last_notes_read_ts');
    $n = check_new_notes($last_ts);
    if ($n && count($n) > 0) {
        $this->session->set_flashdata('alert_info',
        'Es gibt '.count($n) . ' <a href="' . site_url('clients/view_notes/1') . '" >neue Notizen</a>.'
    );
    }
}
// end new notes
?>
<!-- -->
    </div>
</nav>
