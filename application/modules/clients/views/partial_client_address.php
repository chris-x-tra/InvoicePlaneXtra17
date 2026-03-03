<div class="address-cards">

    <!-- CLIENT ADDRESS -->
    <div class="address-card">
        <div class="address-card-header">
            <?php _trans('client_address'); ?>
        </div>

        <div class="address-card-body">
            <div><i class="fa fa-address-book" title="<?php _trans('salutation'); ?>"></i> <?php echo get_best_salutation($client); ?>
       	</div>

            <?php if ($client->client_contact_person): ?>
                <div><i class="fa fa-address-book" title="<?php _trans('contact_person'); ?>"></i> <?= htmlsc($client->client_contact_person) ?></div>
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
<?php 
  if (!empty($client->invoice_name) || 
      !empty($client->invoice_address_1) || 
      !empty($client->invoice_city) || 
      !empty($client->invoice_phone) || 
      !empty($client->invoice_email)): 
?>	
    <div class="address-card">
        <div class="address-card-header">
            <?php _trans('invoice_address'); ?>
        </div>

        <div class="address-card-body">

            <?php if ($client->invoice_salutation): ?>
                <div><i class="fa fa-address-book" title="<?php _trans('salutation'); ?>"></i> <?= htmlsc($client->invoice_salutation) ?></div>
            <?php endif; ?>

            <?php if ($client->invoice_contact_person): ?>
                <div><i class="fa fa-address-book" title="<?php _trans('contact_person'); ?>"></i> <?= htmlsc($client->invoice_contact_person) ?></div>
            <?php endif; ?>

            <?php if ($client->invoice_name): ?>
                <div><?= htmlsc($client->invoice_name) ?></div>
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

            <?php if ($client->invoice_phone): ?>
                <div><?= htmlsc($client->invoice_phone) ?></div>
            <?php endif; ?>
            <?php if ($client->invoice_email): ?>
                <div><?= htmlsc($client->invoice_email) ?></div>
            <?php endif; ?>

        </div>
    </div>
<?php endif; ?>

    <!-- DELIVERY ADDRESS -->
<?php 
  if (!empty($client->delivery_name) || 
      !empty($client->delivery_address_1) || 
      !empty($client->delivery_city) || 
      !empty($client->delivery_phone) || 
      !empty($client->delivery_email)): 
?>	

    <div class="address-card">
        <div class="address-card-header">
            <?php _trans('delivery_address'); ?>
        </div>

        <div class="address-card-body">

            <?php if ($client->delivery_salutation): ?>
                <div><i class="fa fa-address-book" title="<?php _trans('salutation'); ?>"></i> <?= htmlsc($client->delivery_salutation) ?></div>
            <?php endif; ?>

            <?php if ($client->delivery_contact_person): ?>
                <div><i class="fa fa-address-book" title="<?php _trans('contact_person'); ?>"></i> <?= htmlsc($client->delivery_contact_person) ?></div>
            <?php endif; ?>

            <?php if ($client->delivery_name): ?>
                <div><?= htmlsc($client->delivery_name) ?></div>
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

            <?php if ($client->delivery_phone): ?>
                <div><?= htmlsc($client->delivery_phone) ?></div>
            <?php endif; ?>
            <?php if ($client->delivery_email): ?>
                <div><?= htmlsc($client->delivery_email) ?></div>
            <?php endif; ?>

        </div>
    </div>
<?php endif; ?>

</div>
