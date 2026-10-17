<?php
/*
 * Personalfragebogen-Felder (nur die NEUEN Felder).
 * In users/views/form.php einbinden, innerhalb des <form>:
 *     <?php $this->load->view('users/partial_personnel_form'); ?>
 * Die vorhandenen Felder (Name, Straße, Ort, Telefon, Handy, E-Mail) bleiben in deinem bisherigen Formular.
 */
$fv = function (string $key) {
    return $this->mdl_users->form_value($key, true);
};
?>

<div class="panel panel-default">
    <div class="panel-heading">Persönliche Daten</div>
    <div class="panel-body">
        <div class="row">
            <div class="col-xs-12 col-md-6">
                <div class="form-group">
                    <label for="user_firstname">Vorname</label>
                    <input type="text" id="user_firstname" name="user_firstname" class="form-control" maxlength="100"
                           value="<?php echo $fv('user_firstname'); ?>">
                </div>
                <div class="form-group">
                    <label for="user_birthname">Geburtsname</label>
                    <input type="text" id="user_birthname" name="user_birthname" class="form-control" maxlength="100"
                           value="<?php echo $fv('user_birthname'); ?>">
                </div>
                <div class="form-group">
                    <label for="user_birthdate">Geburtsdatum</label>
                    <input type="date" id="user_birthdate" name="user_birthdate" class="form-control"
                           value="<?php echo $fv('user_birthdate'); ?>">
                </div>
                <div class="form-group">
                    <label for="user_birthplace">Geburtsort</label>
                    <input type="text" id="user_birthplace" name="user_birthplace" class="form-control" maxlength="100"
                           value="<?php echo $fv('user_birthplace'); ?>">
                </div>
            </div>
            <div class="col-xs-12 col-md-6">
                <div class="form-group">
                    <label for="user_nationality">Nationalität</label>
                    <input type="text" id="user_nationality" name="user_nationality" class="form-control" maxlength="100"
                           value="<?php echo $fv('user_nationality'); ?>">
                </div>
                <div class="form-group">
                    <label for="user_religion">Konfession</label>
                    <input type="text" id="user_religion" name="user_religion" class="form-control" maxlength="50"
                           placeholder="z. B. ev., rk., keine" value="<?php echo $fv('user_religion'); ?>">
                </div>
                <div class="form-group">
                    <label for="user_marital_status">Familienstand</label>
                    <select id="user_marital_status" name="user_marital_status" class="form-control">
                        <option value=""></option>
                        <?php foreach ($this->mdl_users->marital_statuses() as $key => $label) : ?>
                            <option value="<?php echo $key; ?>"<?php echo $fv('user_marital_status') === $key ? ' selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="user_children">Kinder (Anzahl)</label>
                    <input type="number" min="0" max="30" id="user_children" name="user_children" class="form-control"
                           value="<?php echo $fv('user_children'); ?>">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">Sozialversicherung &amp; Steuern</div>
    <div class="panel-body">
        <div class="row">
            <div class="col-xs-12 col-md-6">
                <div class="form-group">
                    <label>Sozialversicherung</label><br>
                    <label class="radio-inline">
                        <input type="radio" name="user_insurance_type" value="1"
                            <?php echo $fv('user_insurance_type') === '1' ? 'checked' : ''; ?>> gesetzlich
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="user_insurance_type" value="2"
                            <?php echo $fv('user_insurance_type') === '2' ? 'checked' : ''; ?>> privat
                    </label>
                </div>
                <div class="form-group">
                    <label for="user_health_insurance">Krankenkasse</label>
                    <input type="text" id="user_health_insurance" name="user_health_insurance" class="form-control" maxlength="100"
                           value="<?php echo $fv('user_health_insurance'); ?>">
                </div>
                <div class="form-group">
                    <label for="user_social_security_number">Sozialversicherungsnummer</label>
                    <input type="text" id="user_social_security_number" name="user_social_security_number" class="form-control"
                           maxlength="16" autocomplete="off" value="<?php echo $fv('user_social_security_number'); ?>">
                </div>
            </div>
            <div class="col-xs-12 col-md-6">
                <div class="form-group">
                    <label for="user_tax_class">Steuerklasse</label>
                    <select id="user_tax_class" name="user_tax_class" class="form-control">
                        <option value=""></option>
                        <?php for ($i = 1; $i <= 6; $i++) : ?>
                            <option value="<?php echo $i; ?>"<?php echo $fv('user_tax_class') === (string) $i ? ' selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="user_tax_id">Steuer-ID (11 Ziffern)</label>
                    <input type="text" id="user_tax_id" name="user_tax_id" class="form-control" maxlength="11"
                           inputmode="numeric" pattern="\d{11}" autocomplete="off" value="<?php echo $fv('user_tax_id'); ?>">
                </div>
            </div>
        </div>
    </div>
</div>
