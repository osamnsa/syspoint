</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a href="<?= path() ?>" class="brand" style="margin-bottom:14px;" aria-label="Syspoint Hub home">
                    <img class="brand-logo" src="<?= asset('assets/img/logo.png') ?>" alt="" width="40" height="40">
                    <span class="brand-text">
                        <span class="brand-name">Syspoint <em>Hub</em></span>
                        <span class="brand-tagline">...challenging conventions</span>
                    </span>
                </a>
                <p style="color:rgba(255,255,255,0.65);font-size:0.9rem;max-width:34ch;">
                    PS5, VR, board games, IT training, computers &amp; gadgets — all under one roof, with free internet for every gamer.
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
                    <li><a href="mailto:syspointmail@gmail.com">syspointmail@gmail.com</a></li>
                    <li>Open 9am – 10pm</li>
                </ul>
            </div>
            <div>
                <h4>Visit Us</h4>
                <p class="footer-address-label">Syspoint Hub</p>
                <address>Suite C1, Awesome Plaza,<br>Opposite Chicken Republic,<br>Apo Resettlement, Abuja</address>
                <p class="footer-address-label">Office · Syspoint Solutions Consult Limited</p>
                <address>Suite C20, Awesome Plaza,<br>Opposite Chicken Republic,<br>Apo Resettlement, Abuja</address>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> Syspoint Hub. All rights reserved.</span>
            <span>A Syspoint Solutions Consult Limited company</span>
        </div>
    </div>
</footer>

<script src="<?= versioned_asset('assets/js/main.js') ?>" defer></script>
</body>
</html>
