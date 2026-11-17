<?php
$base_site_url = rtrim($cfg_mainurl ?? '', '/');
$ecom_active = !empty($cfg_enable_ecommerce);
$support_email = !empty($cfg_support) ? $cfg_support : (!empty($cfg_contact_email) ? $cfg_contact_email : '');
?>
<footer class="site-footer">
    <div class="container site-footer-grid">
        <div class="site-footer-brand">
            <a href="<?= htmlspecialchars($base_site_url ?: '/') ?>" class="site-footer-logo">
                <?php if (!empty($cfg_logolink)): ?>
                    <img src="<?= htmlspecialchars($cfg_logolink) ?>" alt="<?= htmlspecialchars($cfg_name ?? 'PurifyCMS') ?>" style="height: 30px; width: auto;">
                <?php else: ?>
                    <span class="site-footer-mark"><i class="las la-cube"></i></span>
                    <span><?= htmlspecialchars($cfg_name ?? 'PurifyCMS') ?></span>
                <?php endif; ?>
            </a>
            <p class="site-footer-desc">
                Lightweight flat-file content management system and digital storefront.
            </p>
            <div class="site-footer-socials">
                <?php if (!empty($cfg_social_x)): ?>
                    <a href="<?= htmlspecialchars($cfg_social_x) ?>" target="_blank" rel="noopener" aria-label="X / Twitter"><i class="lab la-twitter"></i></a>
                <?php endif; ?>
                <?php if (!empty($cfg_social_linkedin)): ?>
                    <a href="<?= htmlspecialchars($cfg_social_linkedin) ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="lab la-linkedin-in"></i></a>
                <?php endif; ?>
                <?php if (!empty($cfg_social_instagram)): ?>
                    <a href="<?= htmlspecialchars($cfg_social_instagram) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="lab la-instagram"></i></a>
                <?php endif; ?>
                <?php if (!empty($cfg_social_facebook)): ?>
                    <a href="<?= htmlspecialchars($cfg_social_facebook) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="lab la-facebook-f"></i></a>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($base_site_url) ?>/contact" aria-label="Contact Us"><i class="las la-envelope"></i></a>
            </div>
        </div>

        <div class="site-footer-col">
            <h4>Navigation</h4>
            <ul>
                <li><a href="<?= htmlspecialchars($base_site_url ?: '/') ?>">Home</a></li>
                <li><a href="<?= htmlspecialchars($base_site_url) ?>/about">About Us</a></li>
                <li><a href="<?= htmlspecialchars($base_site_url) ?>/contact">Contact</a></li>
            </ul>
        </div>

        <?php if ($ecom_active): ?>
        <div class="site-footer-col">
            <h4>Storefront</h4>
            <ul>
                <li><a href="<?= htmlspecialchars($base_site_url) ?>/shop">All Products</a></li>
                <li><a href="<?= htmlspecialchars($base_site_url) ?>/order-track">Track Order</a></li>
                <li><a href="<?= htmlspecialchars($base_site_url) ?>/checkout">Checkout</a></li>
            </ul>
        </div>
        <?php else: ?>
        <div class="site-footer-col">
            <h4>Resources</h4>
            <ul>
                <li><a href="<?= htmlspecialchars($base_site_url) ?>/about">Overview</a></li>
                <li><a href="<?= htmlspecialchars($base_site_url) ?>/contact">Support</a></li>
            </ul>
        </div>
        <?php endif; ?>

        <div class="site-footer-col">
            <h4>Get in Touch</h4>
            <ul class="site-footer-contact-list">
                <?php if (!empty($support_email)): ?>
                <li>
                    <i class="las la-envelope"></i>
                    <a href="mailto:<?= htmlspecialchars($support_email) ?>"><?= htmlspecialchars($support_email) ?></a>
                </li>
                <?php endif; ?>
                <li>
                    <i class="las la-building"></i>
                    <span><?= htmlspecialchars(!empty($cfg_legalname) ? $cfg_legalname : ($cfg_name ?? 'PurifyCMS')) ?></span>
                </li>
                <li>
                    <i class="las la-headset"></i>
                    <a href="<?= htmlspecialchars($base_site_url) ?>/contact">Support &amp; Inquiries &rarr;</a>
                </li>
            </ul>
        </div>
    </div>

    <div class="container site-footer-bottom">
        <div>&copy; <?= date('Y') ?> <?= htmlspecialchars(!empty($cfg_legalname) ? $cfg_legalname : ($cfg_name ?? 'PurifyCMS')) ?>. All rights reserved.</div>
        <div class="site-footer-bottom-links">
            <a href="<?= htmlspecialchars($base_site_url) ?>/about">About</a>
            <span>&middot;</span>
            <a href="<?= htmlspecialchars($base_site_url) ?>/contact">Contact</a>
            <?php if ($ecom_active): ?>
            <span>&middot;</span>
            <a href="<?= htmlspecialchars($base_site_url) ?>/order-track">Order Status</a>
            <?php endif; ?>
        </div>
    </div>
</footer>

<script>
document.querySelectorAll('.animate-in').forEach(el => el.classList.add('visible'));
</script>
</body>
</html>
