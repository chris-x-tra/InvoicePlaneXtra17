<script>
    $(function () {
        // Display the create invoice modal
        $('#find-supplier').modal('show');

        // Enable select2 for all selects
        $('.simple-select').select2();

        <?php $this->layout->load_view('clients/script_select2_supplier_id.js'); ?>

        // Toggle on/off permissive search on clients names
        $('#toggle_permissive_search_clients').click(function () {
            if ($('input#input_permissive_search_clients').val() == ('1')) {
                $.get("<?php echo site_url('clients/ajax/save_preference_permissive_search_clients'); ?>", {
                    permissive_search_clients: '0'
                });
                $('input#input_permissive_search_clients').val('0');
                $('span#toggle_permissive_search_clients i').removeClass('fa-toggle-on');
                $('span#toggle_permissive_search_clients i').addClass('fa-toggle-off');
            } else {
                $.get("<?php echo site_url('clients/ajax/save_preference_permissive_search_clients'); ?>", {
                    permissive_search_clients: '1'
                });
                $('input#input_permissive_search_clients').val('1');
                $('span#toggle_permissive_search_clients i').removeClass('fa-toggle-off');
                $('span#toggle_permissive_search_clients i').addClass('fa-toggle-on');
            }
        });

        // Fill in supplier id and supplier name
        $('#expense_client_confirm').click(function () {
            var id = $('#create_expense_client_id').val();
            var clientName = $('#select2-create_expense_client_id-container').text().trim();
            console.log('id: ' + id);
            console.log('name: ' + clientName);

    // Hidden Feld füllen
    $('#expense_supplier_id').val(id);

    // Readonly Feld füllen
    $('#expense_supplier_name').val(clientName);

    $('#fullpage-loader').hide();

    // Modal schließen
    $('#modal-placeholder').find('.modal').modal('hide');
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');

        });
    });

</script>

<div id="find-supplier" class="modal modal-lg"
     role="dialog" aria-labelledby="modal_create_invoice" aria-hidden="true">
    <form class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><i class="fa fa-close"></i></button>
            <h4 class="panel-title"><?php _trans('find_supplier'); ?></h4>
        </div>
        <div class="modal-body">

            <input class="hidden" id="input_permissive_search_clients"
                   value="<?php echo get_setting('enable_permissive_search_clients'); ?>">

            <div class="form-group has-feedback">
                <label for="create_expense_client_id"><?php _trans('supplier'); ?></label>
                <div class="input-group">
                    <select name="create_expense_client_id" id="create_expense_client_id" class="client-id-select form-control"
                            autofocus="autofocus" required>
                        <?php if (!empty($client)) : ?>
                            <option value="<?php echo $client->client_id; ?>"><?php _htmlsc(format_client($client)); ?></option>
                        <?php endif; ?>
                    </select>
                    <span id="toggle_permissive_search_clients" class="input-group-addon"
                          title="<?php _trans('enable_permissive_search_clients'); ?>" style="cursor:pointer;">
                        <i class="fa fa-toggle-<?php echo get_setting('enable_permissive_search_clients') ? 'on' : 'off' ?> fa-fw"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <div class="btn-group">
                <button class="btn btn-success" id="expense_client_confirm" type="button">
                    <i class="fa fa-check"></i> <?php _trans('submit'); ?>
                </button>
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i> <?php _trans('cancel'); ?>
                </button>
            </div>
        </div>

    </form>
</div>
