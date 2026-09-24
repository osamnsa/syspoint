</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a href="<?= path() ?>" class="brand" style="color:#fff;margin-bottom:12px;display:inline-flex;">
                    <span class="brand-name">Syspoint</span>
                </a>
                <p style="color:rgba(255,255,255,0.65);font-size:0.9rem;max-width:32ch;">
                    Computers &amp; accessories, a software clinic, a gaming lounge, IT consulting, and internship training — all in one place.
                </p>
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
                    <li><a href="mailto:hello@syspoint.example">hello@syspoint.example</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> Syspoint. All rights reserved.</span>
        </div>
    </div>
</footer>

<script src="<?= versioned_asset('assets/js/main.js') ?>" defer></script>
</body>
</html>
