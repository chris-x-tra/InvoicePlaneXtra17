<?php
/**
 * @var array  $addr         Adressdaten (Spaltennamen ohne Präfix)
 * @var int    $type         address_type
 * @var string $panel_id     DOM-ID des Collapse, z. B. 'invoiceAddress'
 * @var string $panel_title  bereits übersetzter Titel
 * @var string $label_prefix 'invoice' | 'delivery' (für die Übersetzungs-Keys)
 * @var bool   $helper       Adress-Suche-Button anzeigen
 * @var array  $countries
 */
$addr   = $addr ?? [];
$val    = static fn (string $k): string => htmlspecialchars((string) ($addr[$k] ?? ''), ENT_QUOTES, 'UTF-8');
$open   = ! empty($addr['name']) || ! empty($addr['address_1']) || ! empty($addr['city'])
       || ! empty($addr['email']) || ! empty($addr['phone']);

// feld => [Übersetzungs-Key, Pflicht-Sternchen]
$layout = [
    'salutation'     => [$label_prefix . '_salutation', false],
    'contact_person' => [$label_prefix . '_contact_person', false],
    'name'           => ['name', true],
    'name2'          => ['name2', false],
    'address_1'      => ['street_address', true],
    'address_2'      => ['street_address_2', false],
    'zip'            => ['zip_code', true],
    'city'           => ['city', true],
    'state'          => ['state', false],
    'country'        => ['country', false],
    'phone'          => [$label_prefix . '_phone', false],
    'email'          => [$label_prefix . '_email', false],
];
?>
<div class="col-xs-12 col-sm-6">
    <div class="panel panel-default">

        <div class="panel-heading">
            <h4 class="panel-title">
                <a data-toggle="collapse" href="#<?php echo $panel_id; ?>"
                   aria-expanded="<?php echo $open ? 'true' : 'false'; ?>">
                    <?php echo $panel_title; ?>
                    <span><i class="fa fa-angle-down fa-fw collapse-icon<?php echo $open ? ' rotated' : ''; ?>"></i></span>
                </a>
            </h4>
        </div>

        <div id="<?php echo $panel_id; ?>" class="panel-collapse collapse address-collapse<?php echo $open ? ' in' : ''; ?>">

<?php if ( ! empty($helper)): ?>
            <button type="button" class="btn btn-secondary" id="open-address-search">Adresse auswählen</button>
            <br>
<?php endif; ?>

            <div class="panel-body">
<?php foreach ($layout as $key => [$label, $required]):
    $name = "addresses[{$type}][{$key}]";
    $id   = "{$panel_id}_{$key}";
?>
                <div class="form-group">
                    <label for="<?php echo $id; ?>"><?php _trans($label); ?>
                        <?php if ($required): ?>&nbsp;<i class="fa fa-asterisk" style="color: #e07070;"></i><?php endif; ?>
                    </label>
                    <div class="controls">
<?php if ($key === 'country'): ?>
                        <select name="<?php echo $name; ?>" id="<?php echo $id; ?>" class="form-control">
                            <option value=""><?php _trans('none'); ?></option>
<?php foreach ($countries as $cldr => $country): ?>
                            <option value="<?php echo $cldr; ?>" <?php check_select($addr['country'] ?? '', $cldr); ?>><?php echo $country; ?></option>
<?php endforeach; ?>
                        </select>
<?php else: ?>
                        <input type="text" name="<?php echo $name; ?>" id="<?php echo $id; ?>"
                               class="form-control" value="<?php echo $val($key); ?>">
<?php endif; ?>
                    </div>
                </div>
<?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
