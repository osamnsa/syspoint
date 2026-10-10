<?php
/** Spam status + actions for a message or request. Set $spamRow and $spamEmail before including. */
$isSpam = (int) ($spamRow['is_spam'] ?? 0) === 1;
$spamDomain = str_contains($spamEmail, '@') ? substr(strrchr($spamEmail, '@'), 1) : '';
$freeMail = in_array(mb_strtolower($spamDomain), ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com'], true);
$blockedNow = blocklist_match_peek($spamEmail, $spamRow['ip'] ?? null);
?>
<section class="dash-card glass-dark spam-box<?= $isSpam ? ' is-spam' : '' ?>">
    <header class="dash-card-head"><div><h2><?= $isSpam ? 'In Spam' : 'Spam & sender' ?></h2></div></header>
    <?php if ($isSpam && $spamRow['spam_reason']): ?><p class="spam-reason"><?= e($spamRow['spam_reason']) ?></p><?php endif; ?>
    <dl class="spam-meta">
        <dt>IP address</dt><dd><?= e($spamRow['ip'] ?? '') ?: '—' ?></dd>
        <?php if (!empty($spamRow['user_agent'])): ?><dt>Browser</dt><dd><small><?= e(mb_strimwidth($spamRow['user_agent'], 0, 90, '…')) ?></small></dd><?php endif; ?>
        <?php if ($blockedNow): ?><dt>Blocked</dt><dd><span class="badge badge-danger"><?= e(BLOCK_TYPES[$blockedNow['type']] . ' ' . $blockedNow['value']) ?></span></dd><?php endif; ?>
    </dl>
    <?php if (!$isSpam): ?>
        <form method="post" data-confirm="Move this to Spam?" data-confirm-button="Mark as spam" data-confirm-danger>
            <?= csrf_field() ?><input type="hidden" name="action" value="spam">
            <label class="admin-choice"><input type="checkbox" name="block_email" value="1" checked> <span>Block <?= e($spamEmail) ?></span></label>
            <?php if ($spamDomain && !$freeMail): ?><label class="admin-choice"><input type="checkbox" name="block_domain" value="1"> <span>Block everyone at <?= e($spamDomain) ?></span></label><?php endif; ?>
            <?php if (!empty($spamRow['ip'])): ?><label class="admin-choice"><input type="checkbox" name="block_ip" value="1"> <span>Block IP <?= e($spamRow['ip']) ?></span></label><?php endif; ?>
            <button type="submit" class="btn btn-sm spam-btn">Mark as spam</button>
        </form>
    <?php else: ?>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="not_spam">
            <label class="admin-choice"><input type="checkbox" name="unblock" value="1" checked> <span>Also unblock this sender</span></label>
            <button type="submit" class="btn btn-primary btn-sm">Not spam</button>
        </form>
    <?php endif; ?>
</section>
