<div id="headerbar">
  <h1 class="headerbar-title"><?php _trans('eur_for_the_year'); ?> <?= $year ?> </h1>

    <div class="headerbar-item pull-right">
  <?php for ($y = date('Y'); $y >= date('Y')-10; $y--): ?>
    <span>
    <a href="<?php echo site_url('payments/eur/'.$y); ?>" class="btn btn-default">
      <?= $y ?>
    </a>
    </span>
  <?php endfor; ?>
</div>

</div>

<div id="content" class="table-content">

    <?php $this->layout->load_view('layout/alerts'); ?>

<div class="table-responsive">
   <table class="table table-hover table-striped">

        <thead>
        <tr>
        <th>Monat</th>
        <th>Einnahmen</th>
        <th>Ausgaben</th>
        <th>Überschuss</th>
    </tr>
</thead>
<tbody>
    <?php 
        $all_income = 0;$all_expenses=0; $all_excess=0;
        for ($i = 1; $i <= 12; $i++): 
        $all_income += $income[$i];$all_expenses += $expenses[$i]; $all_excess += ($income[$i] - $expenses[$i]);
        ?>
        <tr>
            <td><?= date('F', mktime(0, 0, 0, $i, 10)) ?></td>
            <td><?= number_format($income[$i], 2, ',', '.') ?> €</td>
            <td><?= number_format($expenses[$i], 2, ',', '.') ?> €</td>
            <td><?= number_format($income[$i] - $expenses[$i], 2, ',', '.') ?> €</td>
        </tr>
    <?php endfor; ?>
    <tr style="border-top: 2px solid black;">
            <td>Summe</td>
            <td><?= number_format($all_income, 2, ',', '.') ?> €</td>
            <td><?= number_format($all_expenses, 2, ',', '.') ?> €</td>
            <td><?= number_format($all_excess, 2, ',', '.') ?> €</td>
    </tr>
</tbody>
</table>
</div>

</div>
