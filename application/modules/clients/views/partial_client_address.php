<div class="address-cards">

    <!-- CLIENT ADDRESS -->
    <div class="address-card">
        <div class="address-card-header">
            <?php _trans('client_address'); ?>
        </div>

        <div class="address-card-body">

            <?php if ($client->client_salutation): ?>
                <div><i class="fa fa-address-book"></i> <?= htmlsc($client->client_salutation) ?></div>
            <?php endif; ?>

            <?php if ($client->client_contact_person): ?>
                <div><i class="fa fa-address-book"></i> <?= htmlsc($client->client_contact_person) ?></div>
            <?php endif; ?>

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

        </div>
    </div>

    <!-- INVOICE ADDRESS -->
    <div class="address-card">
        <div class="address-card-header">
            <?php _trans('invoice_address'); ?>
        </div>

        <div class="address-card-body">

            <?php if ($client->invoice_salutation): ?>
                <div><i class="fa fa-address-book"></i> <?= htmlsc($client->invoice_salutation) ?></div>
            <?php endif; ?>

            <?php if ($client->invoice_contact_person): ?>
                <div><i class="fa fa-address-book"></i> <?= htmlsc($client->invoice_contact_person) ?></div>
            <?php endif; ?>

            <?php if ($client->invoice_address_name): ?>
                <div><?= htmlsc($client->invoice_address_name) ?></div>
            <?php endif; ?>

            <?php if ($client->invoice_address_1): ?>
                <div><?= htmlsc($client->invoice_address_1) ?></div>
            <?php endif; ?>

            <?php if ($client->invoice_address_2): ?>
                <div><?= htmlsc($client->invoice_address_2) ?></div>
            <?php endif; ?>

            <div>
                <?= htmlsc($client->invoice_zip) ?>
                <?= htmlsc($client->invoice_city) ?>
                <?= htmlsc($client->invoice_state) ?>
            </div>

            <?php if ($client->invoice_country && $client->invoice_city): ?>
                <div class="address-country">
                    <?= get_country_name(trans('cldr'), $client->invoice_country) ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- DELIVERY ADDRESS -->
    <div class="address-card">
        <div class="address-card-header">
            <?php _trans('delivery_address'); ?>
        </div>

        <div class="address-card-body">

            <?php if ($client->delivery_salutation): ?>
                <div><i class="fa fa-address-book"></i> <?= htmlsc($client->delivery_salutation) ?></div>
            <?php endif; ?>

            <?php if ($client->delivery_contact_person): ?>
                <div><i class="fa fa-address-book"></i> <?= htmlsc($client->delivery_contact_person) ?></div>
            <?php endif; ?>

            <?php if ($client->delivery_address_name): ?>
                <div><?= htmlsc($client->delivery_address_name) ?></div>
            <?php endif; ?>

            <?php if ($client->delivery_address_1): ?>
                <div><?= htmlsc($client->delivery_address_1) ?></div>
            <?php endif; ?>

            <?php if ($client->delivery_address_2): ?>
                <div><?= htmlsc($client->delivery_address_2) ?></div>
            <?php endif; ?>

            <div>
                <?= htmlsc($client->delivery_zip) ?>
                <?= htmlsc($client->delivery_city) ?>
                <?= htmlsc($client->delivery_state) ?>
            </div>

            <?php if ($client->delivery_country && $client->delivery_city): ?>
                <div class="address-country">
                    <?= get_country_name(trans('cldr'), $client->delivery_country) ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

</div>
