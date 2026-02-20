    <form method="post" action="<?= site_url('dashboard/index'); ?>" id="invoice_overview_form">
        <?php _csrf_field(); ?>
        <!-- see settings/views/partial_settings_general.php -->
            <select name="invoice_overview_period" id="invoice_overview_period"
                class="form-control simple-select" data-minimum-results-for-search="Infinity">
                <option value="this_month" <?php check_select($invoice_status_period, 'this_month'); ?>>
                    <?php _trans('this_month'); ?>
                </option>
                <option value="last_month" <?php check_select($invoice_status_period, 'last_month'); ?>>
                    <?php _trans('last_month'); ?>
                </option>
                <option value="this_quarter" <?php check_select($invoice_status_period, 'this_quarter'); ?>>
                    <?php _trans('this_quarter'); ?>
                </option>
                <option value="last_quarter" <?php check_select($invoice_status_period, 'last_quarter'); ?>>
                    <?php _trans('last_quarter'); ?>
                </option>
                <option value="this_year" <?php check_select($invoice_status_period, 'this_year'); ?>>
                    <?php _trans('this_year'); ?>
                </option>
                <option value="last_year" <?php check_select($invoice_status_period, 'last_year'); ?>>
                    <?php _trans('last_year'); ?>
                </option>
            </select>
    </form>
