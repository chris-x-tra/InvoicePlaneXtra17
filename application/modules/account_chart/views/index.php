<div id="headerbar">
    <h1 class="headerbar-title"><?php _trans('account_chart'); ?></h1>

    <div class="headerbar-item pull-right">
        <a class="btn btn-sm btn-primary" href="<?php echo site_url('account_chart/form'); ?>">
            <i class="fa fa-plus"></i> <?php _trans('new'); ?>
        </a>
    </div>
</div>

<div id="content" class="table-content">

    <?php $this->layout->load_view('layout/alerts'); ?>

    <div class="form-inline" style="margin-bottom: 15px;">
        <select id="account_filter_type" class="form-control input-sm">
            <option value=""><?php _trans('account_type_all'); ?></option>
            <?php foreach ($account_types as $type_id => $type_label) { ?>
                <option value="<?php echo (int) $type_id; ?>"><?php _htmlsc($type_label); ?></option>
            <?php } ?>
        </select>
        <input type="text" id="account_filter_text" class="form-control input-sm"
               placeholder="<?php _trans('filter_accounts'); ?>" style="min-width: 260px;">
    </div>

    <div id="filter_results">
        <?php $this->layout->load_view('account_chart/partial_account_chart_table'); ?>
    </div>

</div>

<script>
    (function () {
        var typeSel = document.getElementById('account_filter_type');
        var textInp = document.getElementById('account_filter_text');

        function apply() {
            var type = typeSel.value;
            var text = textInp.value.toLowerCase().trim();
            var rows = document.querySelectorAll('#account_chart_table tbody tr');
            for (var i = 0; i < rows.length; i++) {
                var ok = (type === '' || rows[i].getAttribute('data-type') === type)
                    && (text === '' || rows[i].getAttribute('data-search').indexOf(text) !== -1);
                rows[i].style.display = ok ? '' : 'none';
            }
        }

        typeSel.addEventListener('change', apply);
        textInp.addEventListener('input', apply);
    })();
</script>
