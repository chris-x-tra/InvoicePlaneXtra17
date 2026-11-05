<script>
    $(function () {
        $('#find-receipt-client').modal('show');

        // Enable select2 for all selects
        $('.simple-select').select2();

        <?php $this->layout->load_view('clients/script_select2_client_id.js'); ?>

        // Toggle on/off permissive search on clients names
        $('#toggle_permissive_search_clients').click(function () {
            if ($('input#input_permissive_search_clients').val() == ('1')) {
                $.get("<?php echo site_url('clients/ajax/save_preference_permissive_search_clients'); ?>", {
                    permissive_search_clients: '0'
                });
                $('input#input_permissive_search_clients').val('0');
                $('span#toggle_permissive_search_clients i').removeClass('fa-toggle-on').addClass('fa-toggle-off');
            } else {
                $.get("<?php echo site_url('clients/ajax/save_preference_permissive_search_clients'); ?>", {
                    permissive_search_clients: '1'
                });
                $('input#input_permissive_search_clients').val('1');
                $('span#toggle_permissive_search_clients i').removeClass('fa-toggle-off').addClass('fa-toggle-on');
            }
        });

        // Zahler uebernehmen
        $('#receipt_client_confirm').click(function () {
            var id = $('#create_receipt_client_id').val();
            var name = $('#select2-create_receipt_client_id-container').text().trim();

            $('#receipt_client_id').val(id);
            $('#receipt_client_name').val(name);

            $('#fullpage-loader').hide();
            $('#modal-placeholder').find('.modal').modal('hide');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
        });
    });
</script>

<div id="find-receipt-client" class="modal modal-lg"
     role="dialog" aria-labelledby="modal_find_receipt_client" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-close"></i></button>
            <h4 class="panel-title"><?php _trans('receipt_find_payer'); ?></h4>
        </div>
        <div class="modal-body">

            <input class="hidden" id="input_permissive_search_clients"
                   value="<?php echo get_setting('enable_permissive_search_clients'); ?>">

            <div class="form-group has-feedback">
                <label for="create_receipt_client_id"><?php _trans('receipt_payer'); ?></label>
                <div class="input-group">
                    <select name="create_receipt_client_id" id="create_receipt_client_id"
                            class="client-id-select form-control" autofocus="autofocus" required>
                        <?php if ( ! empty($client)) : ?>
                            <option value="<?php echo $client->client_id; ?>"><?php _htmlsc(format_client($client)); ?></option>
                        <?php endif; ?>
                    </select>
                    <span id="toggle_permissive_search_clients" class="input-group-addon"
                          title="<?php _trans('enable_permissive_search_clients'); ?>" style="cursor:pointer;">
                        <i class="fa fa-toggle-<?php echo get_setting('enable_permissive_search_clients') ? 'on' : 'off'; ?> fa-fw"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <div class="btn-group">
                <button class="btn btn-success" id="receipt_client_confirm" type="button">
                    <i class="fa fa-check"></i> <?php _trans('submit'); ?>
                </button>
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i> <?php _trans('cancel'); ?>
                </button>
            </div>
        </div>
    </form>
</div>
