<div id="headerbar">
    <h1 class="headerbar-title"><?php _trans('eur_details_for_the_year'); ?> <?= $year ?> </h1>

   <div class="headerbar-item pull-right">
  <?php for ($y = date('Y'); $y >= date('Y')-10; $y--): ?>
    <span>
    <a href="<?php echo site_url('payments/eur_details/'.$y); ?>" class="btn btn-default">
      <?= $y ?>
    </a>
    </span>
  <?php endfor; ?>
  </div>

</div>

<div id="content" class="table-content">

    <?php $this->layout->load_view('layout/alerts'); ?>


<div class="table-responsive">


<h3>Einnahmen</h3>
   <table class="table table-hover table-striped">
        <thead>
    <tr>
        <th style="width:15%;">Rechnungs-Datum</th >
        <th>Kunde</th>
        <th>Rechnung</th>
        <th>Zahlungs-Datum Bank</th>
        <th style="text-align: right;">Betrag (brutto)</th>
    </tr>
</thead>
<tbody>
    <?php
    $sum_income = 0;
    foreach ($income as $i): 
        $sum_income += (float)$i->payment_amount;
    ?>
        <tr>
            <td> <?= date('d.m.Y', strtotime($i->invoice_date_created)) ?></td>
            <td>
                <a href="<?php echo site_url('clients/view/' . $i->client_id); ?>" >
                <?php _htmlsc($i->client_name); ?>
                <?php _htmlsc($i->client_surname); ?>
                </a>
            </td>
            <td>
                <a href="<?php echo site_url('invoices/view/' . $i->invoice_id); ?>" >
                <?php _htmlsc($i->invoice_number); ?>
                </a>
            </td>
            <td> <?= date('d.m.Y', strtotime($i->payment_date)) ?></td>
            <td style="text-align: right;"><?= number_format($i->payment_amount, 2, ',', '.') ?> €</td>
        </tr>
    <?php endforeach; ?>
    <tr style="font-weight: bold;">
        <td colspan="4" style="text-align: right;">Summe Einnahmen:</td>
        <td style="text-align: right;"><?= number_format($sum_income, 2, ',', '.') ?> €</td>
    </tr>
</tbody>
</table>


<h3>Ausgaben</h3>

   <table class="table table-hover table-striped">
        <thead>
    <tr>
        <th style="width:15%;">Datum</th>
        <th>Lieferant</th>
        <th>Beleg Betreff</th>
        <th>Bank Book Date</th>
        <th style="text-align: right;">Betrag (brutto)</th>
    </tr>
</thead>
<tbody>
    <?php
    $sum_expenses = 0;
    foreach ($expenses as $e): 
        $sum_expenses += (float)$e->expense_amount;
    ?>
        <tr>
            <td><?= date('d.m.Y', strtotime($e->expense_date)) ?></td>
            <td>
                <a href="<?php echo site_url('clients/view/' . $e->client_id); ?>" >
                <?php _htmlsc($e->client_name); ?>
                <?php _htmlsc($e->client_surname); ?>
                </a>
            </td>
            <td>
                <a href="<?php echo site_url('expenses/view/' . $e->expense_id); ?>" >
                <?= htmlspecialchars($e->expense_description) ?>
                </a>
            </td>
            <td><?= date('d.m.Y', strtotime($e->expense_bank_book_date)) ?></td>
            <td style="text-align: right;"><?= number_format($e->expense_amount, 2, ',', '.') ?> €</td>
        </tr>
    <?php endforeach; ?>
    <tr style="font-weight: bold;">
        <td colspan="4" style="text-align: right;">Summe Ausgaben:</td>
        <td style="text-align: right;"><?= number_format($sum_expenses, 2, ',', '.') ?> €</td>
    </tr>
</tbody>
</table>


<h3>Ergebnis</h3>
<p>
    Gewinn/Verlust: <strong><?= number_format($sum_income - $sum_expenses, 2, ',', '.') ?> €</strong>
</p>

</div>
</div>
