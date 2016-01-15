<?php defined('C5_EXECUTE') or die('Access denied.'); ?>

<div class="alert alert-info">
    Obtain the following application information from your Auth0 dashboard.
</div>

<div class='form-group'>
    <?= $form->label('domain', t('Auth0 Domain')) ?>
    <?= $form->text('domain', $domain, ['placeholder' => 'YOURDOMAIN.auth0.com']) ?>
</div>

<div class='form-group'>
    <?= $form->label('client_id', t('Client ID')) ?>
    <?= $form->text('client_id', $client_id) ?>
</div>

<div class='form-group'>
    <?= $form->label('client_secret', t('Client Secret')) ?>
    <div class="input-group">
        <?= $form->password('client_secret', $client_secret, array('autocomplete' => 'off')) ?>
        <span class="input-group-btn">
        <button id="showsecret" class="btn btn-warning" type="button"><?= t('Show secret key') ?></button>
      </span>
    </div>
</div>

<div class='form-group'>
    <div class="input-group">
        <label type="checkbox">
            <input type="checkbox" name="registration_enabled" value="1" <?= \Config::get(
                'auth.auth0.registration_enabled',
                false) ? 'checked' : '' ?>>
            <span style="font-weight:normal"><?= t('Allow automatic registration') ?></span>
        </label>
    </div>
</div>

<!-- Display list of groups -->
<div class='form-group registration-group'>
    <label for="registration_group" class="control-label"><?= t('Group to enter on registration') ?></label>
    <select name="registration_group" class="form-control">
        <option value="0"><?= t("None") ?></option>
        <?php
        /** @var \Group $group */
        foreach ($groups as $group) {
            ?>
            <option value="<?= $group->getGroupID() ?>" <?= intval($group->getGroupID(), 10) === intval(
                \Config::get('auth.auth0.registration_group', false),
                10) ? 'selected' : '' ?>>
                <?= $group->getGroupDisplayName(false) ?>
            </option>
        <?php
        }
        ?>
    </select>
</div>


<script type="text/javascript">(function () {

        (function RegistrationGroup() {

            var input = $('input[name="registration_enabled"]'),
                group_div = $('div.registration-group');

            input.change(function () {
                input.get(0).checked && group_div.show() || group_div.hide();
            }).change();

        }());


        var button = $('#showsecret');
        button.click(function () {
            var client_secret = $('#client_secret');
            if (client_secret.attr('type') == 'password') {
                client_secret.attr('type', 'text');
                button.html('<?= addslashes(t('Hide secret key'))?>');
            } else {
                client_secret.attr('type', 'password');
                button.html('<?= addslashes(t('Show secret key'))?>');
            }
        });
        
}())</script>
