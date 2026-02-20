<div id="headerbar">
    <h1 class="headerbar-title">
        Alle Rechnungen als verschickt markieren
    </h1>

</div>

<div id="content">
    <?php echo $this->layout->load_view('layout/alerts'); ?>
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-5">
                    <h3> Alle Rechnungen als verschickt markieren </h3>

<br />
<br />
Es sind <?php echo $count; ?> St&uuml;ck.
<br />
<br />
        <div class="btn-group btn-group-sm index-options">
               <a href="<?php echo site_url('invoices/marksent/1'); ?>"
               class="btn btn-default">
                <?php _trans('mark_all_draft_sent'); ?>
            </a>
        </div>

                </div>
            </div>
    </div>
</div>
