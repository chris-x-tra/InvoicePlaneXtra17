<?php
/*
 * users/views/view.php – Mitarbeiterprofil (nur Ansicht)
 * Erwartet: $user (Zeile aus ip_users), $documents, $activeTab ('profile'|'company'|'documents')
 */
$val = function ($v) {
    return ($v === null || $v === '') ? '<span class="up-empty">–</span>' : htmlspecialchars((string) $v);
};
$date = function ($v) {
    return ($v && $v !== '0000-00-00') ? (new DateTime($v))->format('d.m.Y') : null;
};

$types      = $this->mdl_users->user_types();
$maritals   = $this->mdl_users->marital_statuses();
$fullname   = trim(($user->user_firstname ?? '') . ' ' . ($user->user_name ?? ''));
$birth      = $date($user->user_birthdate ?? null);
$age        = $birth ? (new DateTime($user->user_birthdate))->diff(new DateTime())->y : null;
$insurance  = [1 => 'gesetzlich', 2 => 'privat'][(int) ($user->user_insurance_type ?? 0)] ?? null;
$street_city = trim(($user->user_zip ?? '') . ' ' . ($user->user_city ?? ''));
$tabs       = ['profile' => 'Profil', 'company' => 'Firma &amp; Bank', 'documents' => 'Dokumente'];
$activeTab  = isset($tabs[$activeTab ?? '']) ? $activeTab : 'profile';

$row = function (string $label, string $html) {
    echo '<div class="col-xs-12 col-sm-6 col-md-3 up-field"><div class="up-label">' . $label . '</div><div class="up-value">' . $html . '</div></div>';
};
?>
<style>
    .up-card { background:#fff; border:1px solid #e3e6ee; border-radius:8px; margin-bottom:18px; }
    .up-card-head { padding:20px 24px 0; display:flex; gap:24px; flex-wrap:wrap; align-items:center; }
    .up-avatar img { width:96px; height:96px; border-radius:50%; object-fit:cover; background:#e9ecf3; }
    .up-name { font-size:24px; font-weight:600; line-height:1.15; margin:0 0 4px; }
    .up-sub { color:#6b7280; }
    .up-boxes { margin-left:auto; display:flex; gap:12px; flex-wrap:wrap; }
    .up-box { background:#f3f4f9; border-radius:6px; padding:10px 14px; min-width:200px; font-size:13px; }
    .up-box i { color:#17a58f; width:18px; }
    .up-tabs { margin:16px 0 0; padding:0 16px; border-bottom:0; }
    .up-tabs > li > a { color:#374151; border:0; border-radius:4px; margin:0 2px 8px 0; padding:5px 14px; font-size:13px; }
    .up-tabs > li.active > a, .up-tabs > li.active > a:hover { background:#17a58f; color:#fff; }
    .up-section { padding:18px 24px 6px; }
    .up-section h4 { margin:0 0 16px; font-size:15px; font-weight:600; }
    .up-field { margin-bottom:16px; }
    .up-label { font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#8a909e; margin-bottom:2px; }
    .up-value { font-size:14px; word-break:break-word; }
    .up-empty { color:#c0c4cf; }
    .up-badge { display:inline-block; font-size:12px; padding:1px 8px; border-radius:10px; background:#e6f6f3; color:#0f7a69; }
    .up-badge.off { background:#f3e8e8; color:#9b2c2c; }
</style>

<div id="headerbar">
    <h1 class="headerbar-title">Mitarbeiterprofil</h1>
    <div class="headerbar-item pull-right">
        <a href="<?php echo site_url('users'); ?>" class="btn btn-sm btn-default"><i class="fa fa-arrow-left"></i> <?php _trans('back'); ?></a>
        <a href="<?php echo site_url('users/form/' . (int) $user->user_id); ?>" class="btn btn-sm btn-primary">
            <i class="fa fa-edit"></i> <?php _trans('edit'); ?>
        </a>
    </div>
</div>

<div id="content">
    <?php echo $this->layout->load_view('layout/alerts'); ?>

    <div class="up-card">
        <div class="up-card-head">
            <div class="up-avatar">
                <img src="<?php echo site_url('documents/avatar/' . (int) $user->user_id); ?>" alt=""
                     onerror="this.style.visibility='hidden'">
            </div>
            <div>
                <h2 class="up-name"><?php echo htmlspecialchars($fullname ?: '–'); ?></h2>
                <div class="up-sub"><?php echo htmlspecialchars($types[$user->user_type] ?? ''); ?>
                    <?php if (!empty($user->user_company)) : ?> · <?php echo htmlspecialchars($user->user_company); ?><?php endif; ?>
                </div>
                <div style="margin-top:6px;">
                    <span class="up-badge<?php echo $user->user_active ? '' : ' off'; ?>">
                        <?php echo $user->user_active ? 'aktiv' : 'inaktiv'; ?>
                    </span>
                    <a href="<?php echo site_url('documents/upload/user/' . (int) $user->user_id . '/avatar'); ?>"
                       class="btn btn-xs btn-link"><i class="fa fa-camera"></i> Profilbild ändern</a>
                </div>
            </div>
            <div class="up-boxes">
                <div class="up-box">
                    <div><i class="fa fa-envelope"></i> <?php echo $val($user->user_email); ?></div>
                    <div><i class="fa fa-phone"></i> <?php echo $val($user->user_phone); ?></div>
                    <div><i class="fa fa-mobile"></i> <?php echo $val($user->user_mobile); ?></div>
                </div>
                <div class="up-box">
                    <div><i class="fa fa-map-marker"></i> <?php echo $val($user->user_address_1); ?></div>
                    <div><i class="fa fa-building-o"></i> <?php echo $val($street_city); ?></div>
                    <div><i class="fa fa-calendar"></i> seit <?php echo $val($date($user->user_date_created)); ?></div>
                </div>
            </div>
        </div>

        <ul class="nav nav-pills up-tabs">
            <?php foreach ($tabs as $key => $label) : ?>
                <li<?php echo $key === $activeTab ? ' class="active"' : ''; ?>>
                    <a href="#tab-<?php echo $key; ?>" data-toggle="tab"><?php echo $label; ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="tab-content">

        <!-- PROFIL -->
        <div class="tab-pane<?php echo $activeTab === 'profile' ? ' active' : ''; ?>" id="tab-profile">
            <div class="up-card up-section">
                <h4>Persönliche Daten</h4>
                <div class="row">
                    <?php
                    $row('Name', $val($user->user_name));
                    $row('Vorname', $val($user->user_firstname ?? null));
                    $row('Geburtsname', $val($user->user_birthname ?? null));
                    $row('Geburtsdatum', $birth ? htmlspecialchars($birth) . ' <span class="up-sub">(' . $age . ' Jahre)</span>' : $val(null));
                    $row('Geburtsort', $val($user->user_birthplace ?? null));
                    $row('Nationalität', $val($user->user_nationality ?? null));
                    $row('Konfession', $val($user->user_religion ?? null));
                    $row('Familienstand', $val($maritals[$user->user_marital_status ?? ''] ?? null));
                    $row('Kinder', $val($user->user_children ?? null));
                    ?>
                </div>
            </div>

            <div class="up-card up-section">
                <h4>Kontakt &amp; Adresse</h4>
                <div class="row">
                    <?php
                    $row('Straße / Nr.', $val($user->user_address_1));
                    $row('Adresszusatz', $val($user->user_address_2));
                    $row('Wohnort', $val($street_city));
                    $row('Telefon', $val($user->user_phone));
                    $row('Handy', $val($user->user_mobile));
                    $row('E-Mail', $val($user->user_email));
                    ?>
                </div>
            </div>

            <div class="up-card up-section">
                <h4>Sozialversicherung &amp; Steuern</h4>
                <div class="row">
                    <?php
                    $row('Sozialversicherung', $val($insurance));
                    $row('Krankenkasse', $val($user->user_health_insurance ?? null));
                    $row('Sozialversicherungsnr.', $val($user->user_social_security_number ?? null));
                    $row('Steuerklasse', $val($user->user_tax_class ?? null));
                    $row('Steuer-ID', $val($user->user_tax_id ?? null));
                    ?>
                </div>
            </div>
        </div>

        <!-- FIRMA & BANK (vorhandene Felder) -->
        <div class="tab-pane<?php echo $activeTab === 'company' ? ' active' : ''; ?>" id="tab-company">
            <div class="up-card up-section">
                <h4>Firma &amp; Rechnungsdaten</h4>
                <div class="row">
                    <?php
                    $row('Firma', $val($user->user_company));
                    $row('Ansprechpartner Rechnung', $val($user->user_invoicing_contact));
                    $row('Web', $val($user->user_web));
                    $row('USt-IdNr.', $val($user->user_vat_id));
                    $row('Steuernummer', $val($user->user_tax_code));
                    $row('Bank', $val($user->user_bank));
                    $row('IBAN', $val($user->user_iban));
                    $row('BIC', $val($user->user_bic));
                    ?>
                </div>
            </div>
        </div>

        <!-- DOKUMENTE -->
        <div class="tab-pane<?php echo $activeTab === 'documents' ? ' active' : ''; ?>" id="tab-documents">
            <div class="up-card up-section">
                <h4>Dokumente &amp; Verträge</h4>
                <?php $this->load->view('documents/partial_document_table', [
                    'documents'  => $documents,
                    'owner_type' => 'user',
                    'owner_id'   => (int) $user->user_id,
                ]); ?>
            </div>
        </div>
    </div>
</div>
