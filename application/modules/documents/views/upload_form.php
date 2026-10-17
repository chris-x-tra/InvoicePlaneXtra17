<div id="headerbar">
    <h1 class="headerbar-title">
        <?php echo $kind === 'avatar' ? trans('upload') . ': Profilbild nur JPEG' : trans('upload_document'); ?>
        (<?php echo htmlspecialchars($owner_type . ' #' . $owner_id); ?>)
    </h1>
</div>

<div id="content">
    <?php echo $this->layout->load_view('layout/alerts'); ?>
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-5">
            <form method="post" enctype="multipart/form-data"
                  action="<?php echo site_url('documents/do_upload/' . $owner_type . '/' . $owner_id . '/' . $kind); ?>">

                <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
                       value="<?php echo $this->security->get_csrf_hash(); ?>">

                <div class="form-group">
                    <label><?php _trans('select_document'); ?></label>
                    <input class="form-control" type="file" name="document"
                           accept="<?php echo htmlspecialchars($accept); ?>" required>
                </div>

                <div class="btn-group btn-group-sm">
                    <button type="submit" class="btn btn-success"><?php _trans('upload'); ?></button>
                    <a href="<?php echo site_url($back_url); ?>" class="btn btn-default"><?php _trans('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>
