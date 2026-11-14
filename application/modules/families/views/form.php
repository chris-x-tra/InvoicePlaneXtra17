<?php
$is_update = $this->mdl_families->form_value('is_update');
?>
<form method="post">

    <?php _csrf_field(); ?>

    <div id="headerbar">
        <h1 class="headerbar-title"><?php _trans($is_update ? 'family' : 'add_family'); ?></h1>
        <?php $this->layout->load_view('layout/header_buttons'); ?>
    </div>

    <div id="content">

        <div class="row">
            <div class="col-xs-12 col-md-6 col-md-offset-3">

                <?php $this->layout->load_view('layout/alerts'); ?>

                <input class="hidden" name="is_update" type="hidden" value="<?php echo $is_update ? '1' : '0'; ?>">

                <div class="form-group">
                    <label for="family_name">
                        <?php _trans('family_name'); ?>
                    </label>
                    <input type="text" name="family_name" id="family_name" class="form-control"
                           value="<?php echo $this->mdl_families->form_value('family_name', true); ?>" required>
                </div>

                <div class="form-group">
                    <label for="family_account_id">
                        <?php _trans('family_account'); ?>
                    </label>
                    <select name="family_account_id" id="family_account_id" class="form-control simple-select">
                        <option value=""><?php _trans('family_account_none'); ?></option>
                        <?php foreach ($account_options as $group => $accounts) { ?>
                            <optgroup label="<?php _htmlsc($group); ?>">
                                <?php foreach ($accounts as $key => $label) { ?>
                                    <option value="<?php echo (int) $key; ?>"
                                        <?php check_select($this->mdl_families->form_value('family_account_id'), $key); ?>>
                                        <?php _htmlsc($label); ?>
                                    </option>
                                <?php } ?>
                            </optgroup>
                        <?php } ?>
                    </select>
                    <p class="help-block"><?php _trans('family_account_hint'); ?></p>
                </div>

            </div>
        </div>

    </div>

</form>
