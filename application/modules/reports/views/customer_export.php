
<div id="headerbar">
    <h1 class="headerbar-title"><?php _trans('customer_export'); ?></h1>
</div>

<div id="content">

    <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3">

            <?php $this->layout->load_view('layout/alerts'); ?>

            <div id="report_options" class="panel panel-default">

                <div class="panel-heading">
                    <i class="fa fa-print fa-margin"></i>
                    <?php _trans('customer_export'); ?>
                </div>

                <div class="panel-body">
                <div>
                        Alle Kundendaten kann man als .CSV downloaden.<br />
                        Dieser Export ist kompatibel zu Fortytools und kann dort importiert werden.  <br />
                        <br />
                </div>
		<div>
                    <form method="post" action="<?php echo site_url($this->uri->uri_string()); ?>"
                        <?php echo get_setting('reports_in_new_tab', false) ? 'target="_blank"' : ''; ?>>

                        <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
                               value="<?php echo $this->security->get_csrf_hash() ?>">

                        <input type="submit" class="btn btn-success" name="btn_submit"
                               value="<?php _trans('download_data'); ?>">
                    </form>
                </div>
            </div>

<div>
Vorschau:<br />
<code><nobr>
<?php
    $i=0;
    foreach ($csv_clients as $c) {
        echo $c; echo "<br />";
        $i++;if ($i>10) break;
    }
?>
.......
</nobr>
</code>
</div>

</div>
</div>

