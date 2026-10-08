<?php
/**
 * "Post to Telegram after saving" box for an edit form. Set before including:
 * $tgType (product|course|game|room|event), $tgId (0 when new), $tgDefault (ticked?).
 * The page calls telegram_announce_after_save() once it has saved.
 */
// Not connected: only Website staff get the "connect it" hint.
if (!telegram_can_post() && !admin_can('website')) return;
$tgCfg = telegram_config();
$tgLast = $tgId ? telegram_last_post($tgType, $tgId) : null;
$tgTicked = isset($_POST['tg_announce']) || ($_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($tgDefault));
?>
<div class="tg-box">
    <span class="tg-box-icon" aria-hidden="true"><?= admin_icon('send') ?></span>
    <?php if (telegram_can_post()): ?>
        <div>
            <label class="admin-choice"><input type="checkbox" name="tg_announce" value="1" <?= $tgTicked ? 'checked' : '' ?>> <span>Post to Telegram (<?= e($tgCfg['channel']) ?>) after saving</span></label>
            <p class="form-note">
                <?= $tgLast ? 'Last posted ' . e((new DateTimeImmutable($tgLast['created_at']))->format('j M Y, g:i A')) . '. ' : '' ?>
                Uses its photo, details and a link.
                <?php if ($tgId && admin_can('website')): ?><a href="<?= path('admin/telegram') ?>?<?= e($tgType) ?>=<?= (int) $tgId ?>">Write the post yourself instead</a><?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <p class="form-note">Telegram isn’t connected yet, so this can’t be announced from here. <?= admin_is_admin() ? '<a href="' . path('admin/telegram/settings') . '">Connect it</a>' : 'Ask an administrator to connect it.' ?></p>
    <?php endif; ?>
</div>
