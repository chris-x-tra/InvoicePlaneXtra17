<?php
$is_update = $this->mdl_account_chart->form_value('is_update');
?>
<form method="post">

    <?php _csrf_field(); ?>

    <div id="headerbar">
        <h1 class="headerbar-title"><?php _trans($is_update ? 'account' : 'add_account'); ?></h1>
        <?php $this->layout->load_view('layout/header_buttons'); ?>
    </div>

    <div id="content">

        <div class="row">
            <div class="col-xs-12 col-md-6 col-md-offset-3">

                <?php $this->layout->load_view('layout/alerts'); ?>

                <input class="hidden" name="is_update" type="hidden" value="<?php echo $is_update ? '1' : '0'; ?>">

                <div class="form-group">
                    <label for="account_type"><?php _trans('account_type'); ?></label>
                    <select name="account_type" id="account_type" class="form-control simple-select"
                        <?php echo ($usage_count > 0) ? 'title="' . htmlsc(trans('account_type_locked')) . '"' : ''; ?>>
                        <?php foreach ($account_types as $type_id => $type_label) { ?>
                            <option value="<?php echo (int) $type_id; ?>"
                                <?php check_select($this->mdl_account_chart->form_value('account_type'), $type_id); ?>>
                                <?php _htmlsc($type_label); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <?php if ($usage_count > 0) { ?>
                        <p class="help-block"><?php echo htmlsc(sprintf(trans('account_used_by'), $usage_count)); ?></p>
                    <?php } ?>
                </div>

                <div class="form-group">
                    <label for="account_group"><?php _trans('account_group'); ?></label>
                    <input type="text" name="account_group" id="account_group" class="form-control"
                           list="account_group_list" maxlength="64"
                           value="<?php echo $this->mdl_account_chart->form_value('account_group', true); ?>">
                    <datalist id="account_group_list">
                        <?php foreach ($account_groups as $group) { ?>
                            <option value="<?php _htmlsc($group); ?>">
                        <?php } ?>
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="account_number"><?php _trans('account_number'); ?></label>
                    <input type="text" name="account_number" id="account_number" class="form-control"
                           maxlength="10" pattern="[0-9]+" inputmode="numeric"
                           value="<?php echo $this->mdl_account_chart->form_value('account_number', true); ?>" required>
                    <p class="help-block"><?php _trans('account_number_hint'); ?></p>
                </div>

                <div class="form-group">
                    <label for="account_name"><?php _trans('account_name'); ?></label>
                    <input type="text" name="account_name" id="account_name" class="form-control"
                           maxlength="255"
                           value="<?php echo $this->mdl_account_chart->form_value('account_name', true); ?>" required>
                </div>

            </div>
        </div>

    </div>

</form>
