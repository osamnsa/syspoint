</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a href="<?= path() ?>" class="brand" style="margin-bottom:14px;" aria-label="<?= e(site('site.brand_first') . ' ' . site('site.brand_second')) ?> home">
                    <img class="brand-logo" src="<?= asset('assets/img/logo.png') ?>" alt="" width="40" height="40">
                    <span class="brand-text">
                        <span class="brand-name"><?= e(site('site.brand_first')) ?> <em><?= e(site('site.brand_second')) ?></em></span>
                        <span class="brand-tagline"><?= e(site('site.tagline')) ?></span>
                    </span>
                </a>
                <p style="color:rgba(255,255,255,0.65);font-size:0.9rem;max-width:34ch;"><?= e(site('site.footer_blurb')) ?></p>
                <?php
                $socials = array_filter(['Instagram' => site('site.instagram'), 'Facebook' => site('site.facebook'), 'X' => site('site.x'),
                    'LinkedIn' => site('site.linkedin'), 'TikTok' => site('site.tiktok'), 'YouTube' => site('site.youtube')]);
                if ($socials): ?>
                    <ul class="footer-social">
                        <?php foreach ($socials as $label => $url): ?><li><a href="<?= e($url) ?>" target="_blank" rel="noopener"><?= e($label) ?></a></li><?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div>
                <h4>Company</h4>
                <ul>
                    <li><a href="<?= path('about') ?>">About</a></li>
                    <li><a href="<?= path('contact') ?>">Contact</a></li>
                </ul>
            </div>
            <div>
                <h4>Get in Touch</h4>
                <ul>
                    <?php if (site('site.phone')): ?><li><a href="tel:<?= e(preg_replace('/[^\d+]/', '', site('site.phone'))) ?>"><?= e(site('site.phone')) ?></a></li><?php endif; ?>
                    <?php if ($wa = site_whatsapp_url()): ?><li><a href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp us</a></li><?php endif; ?>
                    <li><a href="mailto:<?= e(site('site.email')) ?>"><?= e(site('site.email')) ?></a></li>
                    <li>Open <?= e(site('site.hours')) ?></li>
                </ul>
            </div>
            <?php if (empty($hideFooterAddress)): /* the home page shows both addresses in its own Visit Us section */ ?>
            <div>
                <h4>Visit Us</h4>
                <p class="footer-address-label"><?= e(site('site.hub_name')) ?> · <?= e(site('site.hub_short')) ?></p>
                <address><?= site_address_lines(site('site.hub_suite')) ?></address>
                <p class="footer-address-label"><?= e(site('site.store_name')) ?> · <?= e(site('site.store_short')) ?></p>
                <address><?= site_address_lines(site('site.store_suite')) ?></address>
            </div>
            <?php endif; ?>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= e(site('site.copyright_name')) ?>. All rights reserved.</span>
            <span><?= e(site('site.footer_company')) ?></span>
        </div>
    </div>
</footer>

<script src="<?= versioned_asset('assets/js/main.js') ?>" defer></script>
</body>
</html>
