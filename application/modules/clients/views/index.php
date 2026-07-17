<div id="headerbar">

<?php if (ip_mari()): ?>
<img src="/assets/core/img/marlogoheart02.png"/ >
<?php endif; ?>

    <h1 class="headerbar-title"><?php _trans('clients'); ?></h1>

    <div class="headerbar-item pull-right">
        <button type="button" class="btn btn-default btn-sm submenu-toggle hidden-lg"
                data-toggle="collapse" data-target="#ip-submenu-collapse">
            <i class="fa fa-bars"></i> <?php _trans('submenu'); ?>
        </button>
        <a class="btn btn-primary btn-sm" href="<?php echo site_url('clients/form'); ?>">
            <i class="fa fa-plus"></i> <?php _trans('new'); ?>
        </a>
    </div>

    <div class="headerbar-item pull-right visible-lg">
        <?php echo pager(site_url('clients/status/' . $this->uri->segment(3)), 'mdl_clients'); ?>
    </div>

    <div class="headerbar-item pull-right visible-lg">
        <div class="btn-group btn-group-sm index-options">
            <a href="<?php echo site_url('clients/status/active'); ?>"
               class="btn <?php echo $this->uri->segment(3) == 'active' || ! $this->uri->segment(3) ? 'btn-primary' : 'btn-default' ?>">
                <?php _trans('active'); ?>
            </a>
            <a href="<?php echo site_url('clients/status/inactive'); ?>"
               class="btn  <?php echo $this->uri->segment(3) == 'inactive' ? 'btn-primary' : 'btn-default' ?>">
                <?php _trans('inactive'); ?>
            </a>
            <a href="<?php echo site_url('clients/status/supplier'); ?>"
               class="btn  <?php echo $this->uri->segment(3) == 'supplier' ? 'btn-primary' : 'btn-default' ?>">
                <?php _trans('supplier'); ?>
            </a>
            <a href="<?php echo site_url('clients/status/all'); ?>"
               class="btn  <?php echo $this->uri->segment(3) == 'all' ? 'btn-primary' : 'btn-default' ?>">
                <?php _trans('all'); ?>
            </a>
        </div>
    </div>

<div class="headerbar-item pull-right">
<?php
echo _trans('found').": ".$total_rows." - ";
_trans('showing'); echo " {$current_records} "; _trans('clients');
echo " ";_trans('starting_from');  echo" {$offset} "; ?>
</div>

</div>

<div id="submenu">
    <div class="collapse clearfix" id="ip-submenu-collapse">

        <div class="submenu-row">
            <?php echo pager(site_url('clients/status/' . $this->uri->segment(3)), 'mdl_clients'); ?>
        </div>

        <div class="submenu-row">
            <div class="btn-group btn-group-sm index-options">
                <a href="<?php echo site_url('clients/status/active'); ?>"
                   class="btn <?php echo $this->uri->segment(3) == 'active' || ! $this->uri->segment(3) ? 'btn-primary' : 'btn-default' ?>">
                    <?php _trans('active'); ?>
                </a>
                <a href="<?php echo site_url('clients/status/inactive'); ?>"
                   class="btn  <?php echo $this->uri->segment(3) == 'inactive' ? 'btn-primary' : 'btn-default' ?>">
                    <?php _trans('inactive'); ?>
                </a> 
                <a href="<?php echo site_url('clients/status/supplier'); ?>"
                   class="btn  <?php echo $this->uri->segment(3) == 'supplier' ? 'btn-primary' : 'btn-default' ?>">
                    <?php _trans('supplier'); ?>
                </a>
                <a href="<?php echo site_url('clients/status/all'); ?>"
                   class="btn  <?php echo $this->uri->segment(3) == 'all' ? 'btn-primary' : 'btn-default' ?>">
                    <?php _trans('all'); ?>
                </a>
            </div>
        </div>

    </div>
</div>

<div id="content" class="table-content">

    <?php $this->layout->load_view('layout/alerts'); ?>

    <div id="filter_results">
        <?php $this->layout->load_view('clients/partial_client_table'); ?>
    </div>


<?php if (get_setting('client_infinite_scroll')): ?>
<!-- HTML scaffolding for infinite scroll -->

    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <tbody id="scroll-content">
           </tbody>
        </table>
    </div>
    <div id="loader" style="text-align: center; display: none;">
        <p><b>
            <i class="fa fa-spinner"></i>
            <?= _trans('loading') ?>
        </b></p>
    <br /> <br />
    </div>
    <div id="loader-end" style="text-align: center; display: none;">
        <p><b>
            <i class="fa fa-hand"></i>
            <?= _trans('end_of_data') ?>
        </b></p>
    <br /> <br />
    </div>

<!-- END html infinite scroll -->
<?php endif; ?>

</div>

<!-- Start Javascript infinite scroll -->
<script type="text/javascript">
$(document).ready(function () {
    let sort    = '<?php echo $sort; ?>';
    let order   = '<?php echo $order; ?>';
    let offset  = <?php echo $page; ?> + 15;   // Start : $page + 15 : already there - use constant from ip settings!
    // page ist not page but real amount of displayed clients
    const limit = 5;                // get how much clients per request
    let loading = false;            // state of loading

    // Funktion zum Laden von Clients
    function loadClients() {
        if (loading) return;
        loading = true;
        $("#loader").show();

       $.getJSON("<?php echo site_url('clients/ajax/get_ajax/'.$this->uri->segment(3)); ?>/"+offset+"?sort="+sort+"&order="+order, { }, function (data) {
            if (data.length > 0) {
                data.forEach(client => {        // append data
                    $("#scroll-content").append(`
                        <?php $this->layout->load_view('clients/partial_client_table_ajax'); ?>`);
                });
                offset += limit;    // increase offset for next request
            } else {
                // no further clients - off and show end div
                $(window).off("scroll");
                $("#loader-end").show();
            }
            loading = false;        // prepare for next scroll event
            $("#loader").hide();
        });
    }

    // fetch new content, if the user hits the end of the browser window
    // ToDo: load clients only if more clients exist!
    $(window).on("scroll", function () {
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 100) {
            loadClients();
        }
    });

    // initial load clients - not in this case - just for reference
    //loadClients();
});
</script>


