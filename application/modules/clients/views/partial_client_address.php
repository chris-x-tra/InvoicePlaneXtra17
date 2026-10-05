
<style>
.client-address {
    display: flex;
    flex-wrap: wrap;
    gap: 20px; /* Abstand zwischen den Karten */
}

.address-card {
    flex: 1 1 200px; /* wachsen, schrumpfen, Basisbreite 300px */
    min-width: 180px; /* darunter wird umgebrochen */
    box-sizing: border-box;
}
</style>

<div class="client-address">

    <!-- CLIENT ADDRESS -->
    <div class="address-card">
        <div class="address-card-header">
            <?php _trans('client_address'); ?>
        </div>

        <div class="address-card-body">
            <div><i class="fa fa-address-book" title="<?php _trans('salutation'); ?>"></i> <?php echo get_best_salutation($client); ?>
       	</div>
            <div><?= _htmlsc(format_client($client)) ?></div>

            <?php if ($client->client_address_1): ?>
                <div><?= htmlsc($client->client_address_1) ?></div>
            <?php endif; ?>

            <?php if ($client->client_address_2): ?>
                <div><?= htmlsc($client->client_address_2) ?></div>
            <?php endif; ?>

            <div>
                <?= htmlsc($client->client_zip) ?>
                <?= htmlsc($client->client_city) ?>
                <?= htmlsc($client->client_state) ?>
            </div>

            <?php if ($client->client_country): ?>
                <div class="address-country">
                    <?= get_country_name(trans('cldr'), $client->client_country) ?>
                </div>
            <?php endif; ?>

            <?php if ($client->client_phone) : ?>
                    <div><?php _trans('phone'); ?>:&nbsp;<?php _htmlsc($client->client_phone); ?></div>
            <?php endif; ?>
            <?php if ($client->client_email) : ?>
                    <div><?php _trans('email'); ?>:&nbsp;<?php _auto_link($client->client_email); ?></div>
            <?php endif; ?>


<?php if (ip_mari()): ?>
<div >
<br>
<?php _trans('customer_paragraphs'); echo ': '.show_paragraphs($client->client_flags ?? 0);
if (($client->client_flags ?? 0) == 0) echo trans('none'); ?>
<br>
<?php _trans('carelevel'); if (isset($client->carelevel) && intval($client->carelevel) > 0) { echo ': '.$client->carelevel; ?>
&nbsp;&nbsp;
<input title="carelevel_confirmation" type="checkbox" disabled readonly <?php if ($client->client_flags & 128) echo 'checked="checked"' ?> >
<?php } else echo ': --'; ?>
</div>
<?php endif; ?>   

        </div>

    </div>

    <!-- INVOICE / DELIVERY ADDRESSES -->

<?php
$this->load->model('clients/mdl_client_addresses');

$titles = [
    Mdl_Client_Addresses::TYPE_INVOICE  => trans('invoice_address'),
    Mdl_Client_Addresses::TYPE_DELIVERY => trans('delivery_address'),
];

// Karten sammeln: [Titel, Adresszeile]
$cards = [];
if (isset($addresses)) {
    // Übergeben (Rechnungs-/Angebotsansicht): genau ein Snapshot pro Typ
    foreach ($titles as $type => $title) {
        if ( ! empty($addresses[$type])) {
            $cards[] = [$title, $addresses[$type]];
        }
    }
} else {
    // Kundenansicht: alle Adressen, nummeriert wenn es mehrere eines Typs gibt
    $all = $this->mdl_client_addresses->get_all_by_client((int) $client->client_id);
    ksort($all);   // Rechnungsadressen vor Lieferadressen
    foreach ($all as $type => $rows) {
        foreach ($rows as $i => $row) {
            $cards[] = [($titles[$type] ?? '') . (count($rows) > 1 ? ' ' . ($i + 1) : ''), $row];
        }
    }
}

foreach ($cards as [$title, $a]):
    // Karte nur anzeigen, wenn irgendetwas Relevantes drinsteht
    $relevant = array_intersect_key($a, array_flip(['name', 'contact_person', 'address_1', 'city', 'phone', 'email']));
    if (array_filter($relevant) === []) {
        continue;
    }
    $name = trim(($a['name'] ?? '') . ' ' . ($a['name2'] ?? ''));
?>
    <div class="address-card">
        <div class="address-card-header">
            <?= htmlsc($title) ?>
        </div>

        <div class="address-card-body">

            <?php if ( ! empty($a['salutation'])): ?>
                <div><i class="fa fa-address-book" title="<?php _trans('salutation'); ?>"></i> <?= htmlsc($a['salutation']) ?></div>
            <?php endif; ?>

            <?php if ( ! empty($a['contact_person'])): ?>
                <div><i class="fa fa-address-book" title="<?php _trans('contact_person'); ?>"></i> <?= htmlsc($a['contact_person']) ?></div>
            <?php endif; ?>

            <?php if ($name !== ''): ?>
                <div><?= htmlsc($name) ?></div>
            <?php endif; ?>

            <?php if ( ! empty($a['address_1'])): ?>
                <div><?= htmlsc($a['address_1']) ?></div>
            <?php endif; ?>

            <?php if ( ! empty($a['address_2'])): ?>
                <div><?= htmlsc($a['address_2']) ?></div>
            <?php endif; ?>

            <div>
                <?= htmlsc($a['zip'] ?? '') ?>
                <?= htmlsc($a['city'] ?? '') ?>
                <?= htmlsc($a['state'] ?? '') ?>
            </div>

            <?php if ( ! empty($a['country'])): ?>
                <div class="address-country">
                    <?= get_country_name(trans('cldr'), $a['country']) ?>
                </div>
            <?php endif; ?>

            <?php if ( ! empty($a['phone'])): ?>
                <div><?= htmlsc($a['phone']) ?></div>
            <?php endif; ?>
            <?php if ( ! empty($a['email'])): ?>
                <div><?= htmlsc($a['email']) ?></div>
            <?php endif; ?>

        </div>
    </div>
<?php endforeach; ?>


</div>
