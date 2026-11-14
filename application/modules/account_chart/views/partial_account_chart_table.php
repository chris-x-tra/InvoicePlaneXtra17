<div class="table-responsive">
    <table class="table table-hover table-striped" id="account_chart_table">

        <thead>
        <tr>
            <th><?php _trans('account_number'); ?></th>
            <th><?php _trans('account_name'); ?></th>
            <th><?php _trans('account_type'); ?></th>
            <th><?php _trans('account_group'); ?></th>
            <th><?php _trans('options'); ?></th>
        </tr>
        </thead>

        <tbody>
        <?php foreach ($accounts as $account) { ?>
            <tr data-type="<?php echo (int) $account->account_type; ?>"
                data-search="<?php echo htmlsc(mb_strtolower($account->account_number . ' ' . $account->account_name . ' ' . $account->account_group)); ?>">
                <td>
                    <a href="<?php echo site_url('account_chart/form/' . $account->account_id); ?>">
                        <i class="fa fa-edit"></i> <?php _htmlsc($account->account_number); ?>
                    </a>
                </td>
                <td><?php _htmlsc($account->account_name); ?></td>
                <td><?php _htmlsc($account_types[(int) $account->account_type] ?? ''); ?></td>
                <td><?php _htmlsc($account->account_group); ?></td>
                <td>
                    <div class="options btn-group">
                        <a class="btn btn-default btn-sm dropdown-toggle"
                           data-toggle="dropdown" href="#">
                            <i class="fa fa-cog"></i> <?php _trans('options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?php echo site_url('account_chart/form/' . $account->account_id); ?>">
                                    <i class="fa fa-edit fa-margin"></i> <?php _trans('edit'); ?>
                                </a>
                            </li>
                            <li>
                                <form action="<?php echo site_url('account_chart/delete/' . $account->account_id); ?>"
                                      method="POST">
                                    <?php _csrf_field(); ?>
                                    <button type="submit" class="dropdown-button"
                                            onclick="return confirm('<?php _trans('delete_record_warning'); ?>');">
                                        <i class="fa fa-trash-o fa-margin"></i> <?php _trans('delete'); ?>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        <?php } ?>
        </tbody>

    </table>
</div>
