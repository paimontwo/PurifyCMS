<footer class="footer">
    <div class="container footer-grid animate-in">
        <div class="footer-brand">
            <a href="<?= htmlspecialchars($cfg_mainurl) ?>" class="logo" style="margin-bottom: 24px;">
                <span><?= htmlspecialchars($cfg_name ?? 'PurifyCMS') ?></span>
            </a>
            <p></p>
            <div style="display: flex; gap: 12px;">
                <a href="#" class="btn btn-secondary" style="width: 40px; height: 40px; padding: 0; justify-content: center;"><i class="lab la-twitter"></i></a>
                <a href="#" class="btn btn-secondary" style="width: 40px; height: 40px; padding: 0; justify-content: center;"><i class="lab la-github"></i></a>
                <a href="#" class="btn btn-secondary" style="width: 40px; height: 40px; padding: 0; justify-content: center;"><i class="lab la-linkedin"></i></a>
            </div>
        </div>
        
        <div class="footer-links">
            <h4 class="outfit-font">Teknoloji</h4>
            <ul>
                <li><a href="<?= $cfg_mainurl ?>/shop">Mağaza</a></li>
                <li><a href="<?= $cfg_mainurl ?>/order-track">Sipariş Takip</a></li>
            </ul>
        </div>

        <div class="footer-links">
            <h4 class="outfit-font">Kurumsal</h4>
            <ul>
                <li><a href="<?= $cfg_mainurl ?>/about">Hakkımızda</a></li>
                <li><a href="<?= $cfg_mainurl ?>/contact">İletişim</a></li>
                <li><a href="<?= $cfg_mainurl ?>/legal">Sözleşmeler</a></li>
            </ul>
        </div>
    </div>
    
    <div class="container copyright">
        &copy; <?= date('Y') ?> <?= htmlspecialchars($cfg_name ?? 'PurifyCMS') ?>. All rights reserved. 
    </div>
</footer>

<script>
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.animate-in').forEach(el => observer.observe(el));
</script>
</body>
</html>
