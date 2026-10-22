<?php
declare(strict_types=1);

/*
 * Cookie consent banner, preferences panel and revisit button. Hidden until
 * consent.js decides the visitor needs to choose; without JavaScript nothing
 * optional ever loads, so nothing needs asking.
 */
$ccCats = cookie_categories();
$ccPolicy = path('cookie-policy');
?>
<div class="cc-root" data-cookie-consent='<?= e(cookie_banner_config()) ?>'>
    <section class="cc-banner" role="region" aria-labelledby="cc-banner-title" hidden>
        <div class="cc-banner-body">
            <p class="cc-banner-title" id="cc-banner-title"><svg class="cc-icon" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9 3.5 3.5 0 0 1-4-4 3.5 3.5 0 0 1-5-5z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="8.5" cy="11" r="1.2" fill="currentColor"/><circle cx="12" cy="16" r="1.2" fill="currentColor"/><circle cx="16" cy="13.5" r="1" fill="currentColor"/><circle cx="9" cy="15.8" r=".8" fill="currentColor"/></svg><?= e(site('cookies.title')) ?></p>
            <p class="cc-banner-text"><?= e(site('cookies.text')) ?> <a href="<?= $ccPolicy ?>">Cookie Policy</a></p>
        </div>
        <div class="cc-actions">
            <button type="button" class="cc-btn cc-btn-ghost" data-cc="customise"><?= e(site('cookies.customise')) ?></button>
            <button type="button" class="cc-btn cc-btn-outline" data-cc="reject"><?= e(site('cookies.reject')) ?></button>
            <button type="button" class="cc-btn cc-btn-primary" data-cc="accept"><?= e(site('cookies.accept')) ?></button>
        </div>
    </section>

    <dialog class="cc-modal" aria-labelledby="cc-modal-title">
        <form method="dialog" class="cc-modal-inner">
            <header class="cc-modal-head">
                <h2 id="cc-modal-title"><?= e(site('cookies.panel_title')) ?></h2>
                <button type="button" class="cc-close" data-cc="close" aria-label="Close">&times;</button>
            </header>
            <div class="cc-modal-body">
                <p class="cc-modal-intro"><?= e(site('cookies.panel_text')) ?> <a href="<?= $ccPolicy ?>">Read our Cookie Policy</a>.</p>
                <?php foreach ($ccCats as $key => $cat): $n = count($cat['cookies']); ?>
                    <div class="cc-cat">
                        <div class="cc-cat-head">
                            <button type="button" class="cc-cat-toggle" aria-expanded="false" aria-controls="cc-cat-<?= $key ?>">
                                <svg class="cc-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span><?= e($cat['label']) ?></span>
                            </button>
                            <?php if ($cat['required']): ?>
                                <span class="cc-always">Always active</span>
                            <?php else: ?>
                                <label class="cc-switch"><input type="checkbox" name="<?= $key ?>" data-cc-cat="<?= $key ?>"><span class="cc-slider" aria-hidden="true"></span><span class="cc-sr"><?= e($cat['label']) ?></span></label>
                            <?php endif; ?>
                        </div>
                        <p class="cc-cat-text"><?= e($cat['text']) ?></p>
                        <div class="cc-cat-detail" id="cc-cat-<?= $key ?>" hidden>
                            <?php if (!$n): ?>
                                <p class="cc-none">No cookies in this category at the moment.</p>
                            <?php else: foreach ($cat['cookies'] as $ck): ?>
                                <dl class="cc-cookie">
                                    <div><dt>Cookie</dt><dd><code><?= e($ck['name']) ?></code></dd></div>
                                    <div><dt>Set by</dt><dd><?= e($ck['provider']) ?></dd></div>
                                    <div><dt>Lasts</dt><dd><?= e($ck['duration']) ?></dd></div>
                                    <div><dt>Purpose</dt><dd><?= e($ck['purpose']) ?></dd></div>
                                </dl>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <footer class="cc-modal-foot">
                <button type="button" class="cc-btn cc-btn-outline" data-cc="reject"><?= e(site('cookies.reject')) ?></button>
                <button type="button" class="cc-btn cc-btn-outline" data-cc="save"><?= e(site('cookies.save')) ?></button>
                <button type="button" class="cc-btn cc-btn-primary" data-cc="accept"><?= e(site('cookies.accept')) ?></button>
            </footer>
        </form>
    </dialog>

    <button type="button" class="cc-revisit" data-cookie-settings aria-label="Cookie settings" title="Cookie settings" hidden>
        <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9 3.5 3.5 0 0 1-4-4 3.5 3.5 0 0 1-5-5z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="8.5" cy="11" r="1.2" fill="currentColor"/><circle cx="12" cy="16" r="1.2" fill="currentColor"/><circle cx="16" cy="13.5" r="1" fill="currentColor"/><circle cx="9" cy="15.8" r=".8" fill="currentColor"/></svg>
    </button>
</div>
