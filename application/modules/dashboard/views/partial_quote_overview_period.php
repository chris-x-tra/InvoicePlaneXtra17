    <form method="post" action="<?= site_url('dashboard/index'); ?>" id="quote_overview_form">
        <?php _csrf_field(); ?>
        <!-- see settings/views/partial_settings_general.php -->
            <select name="quote_overview_period" id="quote_overview_period"
                class="form-control simple-select" data-minimum-results-for-search="Infinity">
                <option value="this-month" <?php check_select($quote_status_period, 'this_month'); ?>>
                    <?php _trans('this_month'); ?>
                </option>
                <option value="last-month" <?php check_select($quote_status_period, 'last_month'); ?>>
                    <?php _trans('last_month'); ?>
                </option>
                <option value="this-quarter" <?php check_select($quote_status_period, 'this_quarter'); ?>>
                    <?php _trans('this_quarter'); ?>
                </option>
                <option value="last-quarter" <?php check_select($quote_status_period, 'last_quarter'); ?>>
                    <?php _trans('last_quarter'); ?>
                </option>
                <option value="this-year" <?php check_select($quote_status_period, 'this_year'); ?>>
                    <?php _trans('this_year'); ?>
                </option>
                <option value="last-year" <?php check_select($quote_status_period, 'last_year'); ?>>
                    <?php _trans('last_year'); ?>
                </option>
            </select>
    </form>
