<?php
/**
 * Adressbereich im Kundenformular (wird nach jeder A"nderung per AJAX neu geladen).
 *
 * @var int $client_id
 */
$this->load->model('clients/mdl_client_addresses');
$all = $this->mdl_client_addresses->get_all_by_client((int) $client_id);

$sections = [
    Mdl_Client_Addresses::TYPE_INVOICE  => ['Rechnungsadressen', 'Neue Rechnungsadresse'],
    Mdl_Client_Addresses::TYPE_DELIVERY => ['Lieferadressen', 'Neue Lieferadresse'],
];
?>
<style>
    .address-tile .panel-body { min-height: 140px; }
    .address-tile-add { border: 2px dashed #ccc; background: transparent; box-shadow: none; text-align: center; }
    .address-tile-add .panel-body { display: flex; align-items: center; justify-content: center; min-height: 70px; }
    .address-tile .address-default-badge {
        float: right;
        margin-left: 8px;
        padding: 2px 8px;
        border-radius: 3px;
        background: #2e90d8;
        color: #fff;
        font-size: 11px;
        line-height: 1.5;
    }
</style>

<div class="row">
<?php foreach ($sections as $type => [$heading, $add_label]):
    $rows = $all[$type] ?? [];
?>
    <div class="col-xs-12 col-sm-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?php echo $heading; ?> <span class="badge"><?php echo count($rows); ?></span>
            </div>
            <div class="panel-body">
                <div class="row">

<?php foreach ($rows as $i => $row):
    $text     = static fn (string $key): string => htmlsc((string) ($row[$key] ?? ''));
    $zip_city = trim(($row['zip'] ?? '') . ' ' . ($row['city'] ?? '') . ' ' . ($row['state'] ?? ''));
    $name     = trim(($row['name'] ?? '') . ' ' . ($row['name2'] ?? ''));
?>
                    <div class="col-xs-12 col-lg-6">
                        <div class="panel panel-default address-tile">
                            <div class="panel-body">
                                <?php if ($i === 0): ?><span class="address-default-badge">Standard</span><?php endif; ?>

<?php if ( ! empty($row['salutation'])): ?>
                                <div><i class="fa fa-address-book" title="<?php _trans('salutation'); ?>"></i> <?php echo $text('salutation'); ?></div>
<?php endif; ?>
<?php if ( ! empty($row['contact_person'])): ?>
                                <div><i class="fa fa-user" title="<?php _trans('contact_person'); ?>"></i> <?php echo $text('contact_person'); ?></div>
<?php endif; ?>
<?php if ($name !== ''): ?>
                                <div><strong><?php echo htmlsc($name); ?></strong></div>
<?php endif; ?>
<?php if ( ! empty($row['address_1'])): ?>
                                <div><?php echo $text('address_1'); ?></div>
<?php endif; ?>
<?php if ( ! empty($row['address_2'])): ?>
                                <div><?php echo $text('address_2'); ?></div>
<?php endif; ?>
<?php if ($zip_city !== ''): ?>
                                <div><?php echo htmlsc($zip_city); ?></div>
<?php endif; ?>
<?php if ( ! empty($row['country'])): ?>
                                <div class="text-muted"><?php echo get_country_name(trans('cldr'), $row['country']); ?></div>
<?php endif; ?>
<?php if ( ! empty($row['phone'])): ?>
                                <div><i class="fa fa-phone" title="<?php _trans('phone'); ?>"></i> <?php echo $text('phone'); ?></div>
<?php endif; ?>
<?php if ( ! empty($row['email'])): ?>
                                <div><i class="fa fa-envelope" title="<?php _trans('email'); ?>"></i> <?php echo $text('email'); ?></div>
<?php endif; ?>
                            </div>
                            <div class="panel-footer">
                                <a href="#" class="btn btn-xs btn-default address-edit"
                                   data-address="<?php echo htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fa fa-edit"></i> Bearbeiten
                                </a>
                                <a href="#" class="btn btn-xs btn-danger address-delete"
                                   data-id="<?php echo (int) $row['address_id']; ?>">
                                    <i class="fa fa-trash-o"></i> L&ouml;schen
                                </a>
                            </div>
                        </div>
                    </div>
<?php endforeach; ?>

                    <div class="col-xs-12 col-lg-6">
                        <a href="#" class="address-add" data-type="<?php echo (int) $type; ?>" style="text-decoration:none;">
                            <div class="panel panel-default address-tile address-tile-add">
                                <div class="panel-body">
                                    <span><i class="fa fa-plus"></i> <?php echo $add_label; ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
