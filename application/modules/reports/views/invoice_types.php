<div id="headerbar">
    <h1 class="headerbar-title"><?php _trans('invoice_types'); ?></h1>
</div>

<div id="content">

    <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3">

            <?php $this->layout->load_view('layout/alerts'); ?>

            <div id="report_options" class="panel panel-default">

                <div class="panel-heading">
                    <i class="fa fa-print fa-margin"></i>
                    <?php _trans('report_options'); ?>
                </div>

                <div class="panel-body">

                    <form method="post" action="<?php echo site_url($this->uri->uri_string()); ?>"
                        <?php echo get_setting('reports_in_new_tab', false) ? 'target="_blank"' : ''; ?>>

                        <input type="hidden" name="<?php echo $this->config->item('csrf_token_name'); ?>"
                               value="<?php echo $this->security->get_csrf_hash() ?>">

            <label for="year"><?= _trans('year') ?></label>
            <select id="my_year" name="my_year">
                <?php  foreach ($all_year as $y) {
                    echo '<option value="'.$y.'"';
                    if ($year == $y) echo ' selected="selected" ';
                    echo ' >'.$y.'</option>';
                    echo "\n";
                }
                ?>
            </select>

                        <input type="submit" class="btn btn-success" name="btn_submit"
                               value="<?php _trans('run_report'); ?>">

                    </form>
                </div>
            </div>

        </div>
    <div> <!-- // ROW -->

    <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3">

            Hinweis: bei einer Rechnung k&ouml;nnen mehrere Typen gesetzt sein.
            <div id="report_stuff" class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-print fa-margin"></i>
                    Anzahl <?php _trans('invoice_types'); ?> <?= $year ?>
                </div>

                <table class="table table-bordered table-condensed">
                    <tbody>
                        <tr>
                            <td class="text-right col-md-4"><strong>Gesamt</strong></td>
                            <td class="text-left"><strong><?= $types->total_invoices ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-right">Ohne Typ</td>
                            <td class="text-left"><?= $types->ohne_typ ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">Privat</td>
                            <td class="text-left"><?= $types->privat ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">§39 SGB XI</td>
                            <td class="text-left"><?= $types->par39 ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">§45a SGB XI</td>
                            <td class="text-left"><?= $types->par45a ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">§45b SGB XI</td>
                            <td class="text-left"><?= $types->par45b ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">§125 SGB XI</td>
                            <td class="text-left"><?= $types->par125 ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    <div> <!-- // ROW -->


<!--
    <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3">

            <div id="report_stuff_x" class="panel panel-default" >
                <div class="panel-heading">
                    <i class="fa fa-print fa-margin"></i>
                        Anzahl Rechnungen §45a / §45b nach Pflegegrad <?= $year ?>
                </div>

                <table class="table table-bordered table-condensed">
                    <tbody>
                        <tr>
                            <td class="text-right col-md-4"><strong>Gesamt</strong></td>
                            <td class="text-left"><strong><?= $types_carelevel->total_invoices ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-right">Ohne Pflegegrad (0)</td>
                            <td class="text-left"><?= $types_carelevel->carelevel_0 ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">Pflegegrad 1</td>
                            <td class="text-left"><?= $types_carelevel->carelevel_1 ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">Pflegegrad 2</td>
                            <td class="text-left"><?= $types_carelevel->carelevel_2 ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">Pflegegrad 3</td>
                            <td class="text-left"><?= $types_carelevel->carelevel_3 ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">Pflegegrad 4</td>
                            <td class="text-left"><?= $types_carelevel->carelevel_4 ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">Pflegegrad 5</td>
                            <td class="text-left"><?= $types_carelevel->carelevel_5 ?></td>
                        </tr>
                        <tr>
                            <td class="text-right">Pflegegrad 6</td>
                            <td class="text-left"><?= $types_carelevel->carelevel_6 ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
          </div>
        </div>
    <div> <!-- // ROW -->
-->

    <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3">

            <div id="report_stuff_h" class="panel panel-default" >
                <div class="panel-heading">
                    <i class="fa fa-print fa-margin"></i>
                    Rechnungen nach Pflegestufen Jahr <?= $year ?>:
                    <p>Entlastungshilfe nach §45b SGB XI und Entlastungshilfe nach §45a SGb XI mit Umwidmung</p>
                </div>
                <table class="table table-striped table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>Pflegestufe</th>
                            <th>Anzahl Kunden</th>
                            <th>Anzahl Rechnungen</th>
                            <th>Gesamtstunden</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= $row->carelevel == 0 ? 'ohne (0)' : $row->carelevel ?></td>
                            <td><?= $row->anzahl_kunden ?></td>
                            <td><?= $row->anzahl_rechnungen ?></td>
                            <td><?= $row->stunden ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="4">&nbsp;</td>
                        </tr>
                        <tr class="active">
                            <td><strong>Gesamt</strong></td>
                            <td><strong><?= $total->anzahl_kunden ?></strong></td>
                            <td><strong><?= $total->anzahl_rechnungen ?></strong></td>
                            <td><strong><?= $total->stunden ?></strong></td>
                        </tr>
                    </tbody>
                </table
            </div>
          </div>
        </div>
</div> <!-- // CONTENT -->
