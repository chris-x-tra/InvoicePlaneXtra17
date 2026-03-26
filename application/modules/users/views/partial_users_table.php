    <div class="table-responsive">
        <table class="table table-hover table-striped">

            <thead>
            <tr>
                <th><?php _trans('name'); ?></th>
                <th><?php _trans('user_type'); ?></th>
                <th><?php _trans('email_address'); ?></th>
                <th><?php _trans('blocked'); ?></th
                <th><?php _trans('options'); ?></th>
            </tr>
            </thead>

            <tbody>
<?php
foreach ($users as $user) {
?>
                <tr>
                    <td><?php _htmlsc($user->user_name); ?></td>
                    <td><?php echo $user_types[$user->user_type]; ?></td>
                    <td><?php echo $user->user_email; ?></td>

                    <td><?php if ($users_blocked[$user->user_email]) : ?>
                        <form class="navbar-form navbar-left" method="post" action="<?php echo site_url('users/unblock'); ?>">
                            <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
                               value="<?php echo $this->security->get_csrf_hash() ?>">
                            <input type="hidden" name="email" value="<?= $user->user_email ?>" >
                            <button type="submit" id="blocked" class="btn btn-default" >
                                    <i class="fa fa-key fa-margin"></i> <?php _trans('blocked'); ?>
                            </button>
                        </form>
                        <?php endif; ?>
                   </td>

                    <td>
                        <div class="options btn-group btn-group-sm">
<?php
// admin, manager, supervisor, employee can have assigned clients
    if ($user->user_type == 1 || $user->user_type == 3 || $user->user_type == 4 || $user->user_type == 5) {
?>
                        <a href="<?php echo site_url('user_clients/user/' . $user->user_id); ?>"
                           class="btn btn-default">
                            <i class="fa fa-list fa-margin"></i> <?php _trans('assigned_clients'); ?>
                        </a>
<?php
    } // Endif
?>
                            <a class="btn btn-default dropdown-toggle"
                               data-toggle="dropdown" href="#">
                                <i class="fa fa-cog"></i> <?php _trans('options'); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="<?php echo site_url('users/form/' . $user->user_id); ?>">
                                        <i class="fa fa-edit fa-margin"></i> <?php _trans('edit'); ?>
                                    </a>
                                </li>
<?php
// delete only from admin
    if ($user->user_id == 1) {
?>
                                    <li>
                                        <form action="<?php echo site_url('users/delete/' . $user->user_id); ?>"
                                              method="POST">
                                            <?php _csrf_field(); ?>
                                            <button type="submit" class="dropdown-button"
                                                    onclick="return confirm('<?php _trans('delete_record_warning'); ?>');">
                                                <i class="fa fa-trash-o fa-margin"></i> <?php _trans('delete'); ?>
                                            </button>
                                        </form>
                                    </li>
<?php
    }
?>
                            </ul>
                        </div>
                    </td>
                </tr>
<?php
} // End foreach
?>
            </tbody>
        </table>
    </div>
