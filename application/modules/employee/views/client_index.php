<div id="headerbar">

<img src="/assets/core/img/marlogoheart02.png"/ >
<h1 class="headerbar-title"><?php _trans('clients'); ?></h1>

<!-- hier kundenfilter gleich nochmal nicht nur rechts by chrissie -->
                <script>
    var delay = (function () {
        var timer = 0;
        return function (callback, ms) {
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        };
    })();

    $(function () {
        $('#filter').keyup(function () {
            delay(function () {
                $.post('<?php echo base_url(); ?>index.php/filter/ajax/filter_clients',
                    {
                        filter_query: $('#filter').val()
                    }, function (data) {
                                                $('#filter_results').html(data);
                    });
            }, 1000);
        });
    });
</script>                
<form class="navbar-form navbar-left" role="search" onsubmit="return false;">
                    <div class="form-group">
                        <input id="filter" type="text" class="search-query form-control input-sm"
                               placeholder="Kunden filtern"
				value="<?php echo $filter_value; ?>" >
                    </div>
                </form>
<!-- /END hier kundenfilter gleich nochmal nicht nur rechts -->


    <div class="headerbar-item pull-right">
        <button type="button" class="btn btn-default btn-sm submenu-toggle hidden-lg"
                data-toggle="collapse" data-target="#ip-submenu-collapse">
            <i class="fa fa-bars"></i> <?php _trans('submenu'); ?>
        </button>
    </div>


    <div class="headerbar-item pull-right visible-lg">
        <?php echo pager(site_url('employee/clients/status/' . $this->uri->segment(3)), 'mdl_clients' ); ?>
    </div>

    <div class="headerbar-item pull-right visible-lg">
        <div class="btn-group btn-group-sm index-options">
            <a href="<?php echo site_url('employee/clients/status/active'); ?>"
               class="btn <?php echo $this->uri->segment(3) == 'active' || !$this->uri->segment(3) ? 'btn-primary' : 'btn-default' ?>">
                <?php _trans('active'); ?>
            </a>
            <a href="<?php echo site_url('employee/clients/status/inactive'); ?>"
               class="btn  <?php echo $this->uri->segment(3) == 'inactive' ? 'btn-primary' : 'btn-default' ?>">
                <?php _trans('inactive'); ?>
            </a>
            <a href="<?php echo site_url('employee/clients/status/all'); ?>"
               class="btn  <?php echo $this->uri->segment(3) == 'all' ? 'btn-primary' : 'btn-default' ?>">
                <?php _trans('all'); ?>
            </a>
        </div>
    </div>

</div>

<div id="submenu">
    <div class="collapse clearfix" id="ip-submenu-collapse">

        <div class="submenu-row">
            <?php echo pager(site_url('employee/clients/status/' . $this->uri->segment(3)), 'mdl_clients'); ?>
        </div>

        <div class="submenu-row">
            <div class="btn-group btn-group-sm index-options">
                <a href="<?php echo site_url('employee/clients/status/active'); ?>"
                   class="btn <?php echo $this->uri->segment(3) == 'active' || !$this->uri->segment(3) ? 'btn-primary' : 'btn-default' ?>">
                    <?php _trans('active'); ?>
                </a>
                <a href="<?php echo site_url('employee/clients/status/inactive'); ?>"
                   class="btn  <?php echo $this->uri->segment(3) == 'inactive' ? 'btn-primary' : 'btn-default' ?>">
                    <?php _trans('inactive'); ?>
                </a>
                <a href="<?php echo site_url('employee/clients/status/all'); ?>"
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
        <?php $this->layout->load_view('employee/partial_client_table'); ?>
    </div>

<!-- HTML scaffolding for infinite scroll -->
    <div class="table-responsive" >
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
<!-- //END html infinite scroll -->

</div>

<?php //echo site_url('clients/ajax/get_ajax'); ?>
<!-- Start Javascript infinite scroll -->
<script type="text/javascript">
$(document).ready(function () {
  //console.log("Hello world!");
    let sort    = '<?php echo $sort; ?>';
    let order   = '<?php echo $order; ?>';
    let offset  = <?php echo $page; ?> + 15;	// Start : $page + 15 : already there - use constant from ip settings!
    // page ist not page but real amount of displayed clients
    const limit = 5;     			// get how much clients per request
    let loading = false; 			// state of loading

    // Funktion zum Laden von Clients
    function loadClients() {
        if (loading) return;
        loading = true;
        $("#loader").show();

  $.getJSON("<?php echo site_url('employees/ajax/get_ajax'); ?>/"+offset+"?sort="+sort+"&order="+order, { }, function (data) {
    //console.log("getJSON");
            if (data.length > 0) {
                data.forEach(client => {		// append data
                    $("#scroll-content").append(`
                        <?php $this->layout->load_view('employee/partial_client_table_ajax'); ?>`);
                });
                offset += limit; 	// increase offset for next request
            } else {
                // no further clients - off and show end div
                $(window).off("scroll");
                $("#loader-end").show();
            }
            loading = false;		// prepare for next scroll event
            $("#loader").hide();
  });
    }


    // fetch new content, if the user hits the end of the browser window
    $(window).on("scroll", function () {
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 100) {
            loadClients();
        }
    });

    // initial load clients - not in this case - just for reference
    //loadClients();

});
</script>

