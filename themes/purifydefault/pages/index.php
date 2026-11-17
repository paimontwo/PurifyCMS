<?php

$title = $cfg_name . " | Online Alışveriş";
$desc = "En yeni ürünler, özel kampanyalar ve hızlı teslimat ile alışverişin keyfini çıkarın. " . $cfg_name . " ile güvenli ve konforlu alışveriş.";

require_once __DIR__ . '/../../../inc/PurifyTrade.php';
$p_file = __DIR__ . '/../../../inc/data/ecommerce/products.json';
$all_p = file_exists($p_file) ? json_decode(file_get_contents($p_file), true) : [];

// Öne Çıkanlar (Featured)
$featured_p = array_filter($all_p, function($item) {
    return !empty($item['featured']);
});

// Yeni Gelenler (Son 4 ürün)
$new_p = array_slice($all_p, 0, 4);
?>


<style>
.purifydefaultindex_container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
}
.purifydefaultindex_btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}
.purifydefaultindex_btn-primary {
    background: #111827;
    color: #fff;
}
.purifydefaultindex_btn-primary:hover {
    background: #374151;
}
.purifydefaultindex_btn-secondary {
    background: transparent;
    color: #111827;
    border: 1.5px solid #d1d5db;
}
.purifydefaultindex_btn-secondary:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}
.purifydefaultindex_btn-white {
    background: #fff;
    color: #111827;
}
.purifydefaultindex_btn-white:hover {
    background: #f3f4f6;
}

/* HERO */
.purifydefaultindex_hero {
    position: relative;
    background: #f3f4f6;
    min-height: 520px;
    display: flex;
    align-items: center;
    overflow: hidden;
}
.purifydefaultindex_hero-inner {
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-items: center;
    gap: 48px;
    padding: 80px 0;
    width: 100%;
}
.purifydefaultindex_hero-badge {
    display: inline-block;
    background: #111827;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 5px 14px;
    border-radius: 4px;
    margin-bottom: 20px;
}
.purifydefaultindex_hero h1 {
    font-size: clamp(36px, 5vw, 60px);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.08;
    margin-bottom: 20px;
    color: #0f172a;
}
.purifydefaultindex_hero p {
    font-size: 17px;
    color: #4b5563;
    margin-bottom: 32px;
    line-height: 1.6;
    max-width: 420px;
}
.purifydefaultindex_hero-btns {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.purifydefaultindex_hero-visual {
    position: relative;
    height: 420px;
    background: #e5e7eb;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.purifydefaultindex_hero-img-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #e0e7ef 0%, #f3f4f6 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 15px;
}
.purifydefaultindex_hero-img-placeholder i {
    font-size: 64px;
    margin-bottom: 12px;
    color: #d1d5db;
}
.purifydefaultindex_hero-tag {
    position: absolute;
    bottom: 24px;
    left: 24px;
    background: #fff;
    border-radius: 10px;
    padding: 12px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.10);
    font-size: 14px;
    font-weight: 600;
    color: #111827;
}
.purifydefaultindex_hero-tag i {
    color: #22c55e;
    font-size: 18px;
}

/* ANNOUNCEMENT BAR */
.purifydefaultindex_announce {
    background: #111827;
    color: #fff;
    text-align: center;
    padding: 10px 24px;
    font-size: 14px;
    font-weight: 500;
    letter-spacing: 0.01em;
}
.purifydefaultindex_announce a {
    color: #fbbf24;
    text-decoration: underline;
    margin-left: 8px;
}

/* TRUST BAR */
.purifydefaultindex_trust {
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    padding: 18px 0;
}
.purifydefaultindex_trust-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 32px;
}
.purifydefaultindex_trust-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}
.purifydefaultindex_trust-item i {
    font-size: 22px;
    color: #111827;
}

/* SECTION */
.purifydefaultindex_section {
    padding: 72px 0;
}
.purifydefaultindex_section-header {
    text-align: center;
    margin-bottom: 48px;
}
.purifydefaultindex_section-header h2 {
    font-size: 30px;
    font-weight: 800;
    margin-bottom: 10px;
    letter-spacing: -0.02em;
    color: #0f172a;
}
.purifydefaultindex_section-header p {
    color: #6b7280;
    font-size: 16px;
    max-width: 500px;
    margin: 0 auto;
}
.purifydefaultindex_section-link {
    text-align: center;
    margin-top: 40px;
}

/* CATEGORIES */
.purifydefaultindex_cat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}
.purifydefaultindex_cat-card {
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    min-height: 180px;
    display: flex;
    align-items: flex-end;
    text-decoration: none;
    background: #e5e7eb;
    transition: transform 0.2s;
}
.purifydefaultindex_cat-card:hover {
    transform: translateY(-4px);
}
.purifydefaultindex_cat-img {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 52px;
    color: #d1d5db;
}
.purifydefaultindex_cat-label {
    position: relative;
    z-index: 1;
    background: linear-gradient(0deg, rgba(15,23,42,0.85) 0%, transparent 100%);
    width: 100%;
    padding: 32px 16px 16px;
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 0.01em;
}
.purifydefaultindex_cat-count {
    font-size: 12px;
    font-weight: 400;
    opacity: 0.7;
    display: block;
    margin-top: 2px;
}

/* PRODUCTS */
.purifydefaultindex_product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 24px;
}
.purifydefaultindex_product-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
}
.purifydefaultindex_product-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.09);
    transform: translateY(-3px);
}
.purifydefaultindex_product-img {
    background: #f9fafb;
    height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 56px;
    color: #d1d5db;
    position: relative;
}
.purifydefaultindex_product-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #ef4444;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 4px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.purifydefaultindex_product-badge.purifydefaultindex_badge-new {
    background: #111827;
}
.purifydefaultindex_product-body {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
}
.purifydefaultindex_product-name {
    font-size: 15px;
    font-weight: 700;
    color: #111827;
    line-height: 1.3;
}
.purifydefaultindex_product-cat {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
.purifydefaultindex_product-price-row {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-top: 4px;
}
.purifydefaultindex_price-new {
    font-size: 18px;
    font-weight: 800;
    color: #111827;
}
.purifydefaultindex_price-old {
    font-size: 13px;
    color: #9ca3af;
    text-decoration: line-through;
}
.purifydefaultindex_stars {
    color: #f59e0b;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 3px;
}
.purifydefaultindex_stars span {
    font-size: 12px;
    color: #9ca3af;
    font-weight: 400;
}
.purifydefaultindex_product-add {
    margin-top: auto;
    padding-top: 12px;
}

/* BANNER */
.purifydefaultindex_banner {
    background: #0f172a;
    border-radius: 16px;
    padding: 60px 48px;
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: 32px;
    margin: 0 0 0 0;
}
.purifydefaultindex_banner h2 {
    color: #fff;
    font-size: 32px;
    font-weight: 800;
    letter-spacing: -0.02em;
    margin-bottom: 10px;
}
.purifydefaultindex_banner p {
    color: #94a3b8;
    font-size: 16px;
}
.purifydefaultindex_banner-timer {
    display: flex;
    gap: 12px;
}
.purifydefaultindex_timer-block {
    background: rgba(255,255,255,0.08);
    border-radius: 8px;
    padding: 14px 18px;
    text-align: center;
    min-width: 64px;
}
.purifydefaultindex_timer-block .purifydefaultindex_timer-num {
    font-size: 28px;
    font-weight: 800;
    color: #fff;
    line-height: 1;
}
.purifydefaultindex_timer-block .purifydefaultindex_timer-label {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-top: 4px;
}

/* REVIEWS */
.purifydefaultindex_review-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
}
.purifydefaultindex_review-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
}
.purifydefaultindex_review-stars {
    color: #f59e0b;
    font-size: 14px;
    margin-bottom: 12px;
}
.purifydefaultindex_review-text {
    font-size: 15px;
    color: #374151;
    line-height: 1.6;
    margin-bottom: 20px;
}
.purifydefaultindex_review-user {
    display: flex;
    align-items: center;
    gap: 10px;
}
.purifydefaultindex_review-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 15px;
    color: #374151;
    flex-shrink: 0;
}
.purifydefaultindex_review-name {
    font-weight: 700;
    font-size: 14px;
    color: #111827;
}
.purifydefaultindex_review-date {
    font-size: 12px;
    color: #9ca3af;
}
.purifydefaultindex_verified {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #ecfdf5;
    color: #059669;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 4px;
    margin-left: 6px;
}
.purifydefaultindex_verified i {
    font-size: 12px;
}

/* NEWSLETTER */
.purifydefaultindex_newsletter {
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
    padding: 56px 0;
    text-align: center;
}
.purifydefaultindex_newsletter h2 {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 8px;
}
.purifydefaultindex_newsletter p {
    color: #6b7280;
    font-size: 15px;
    margin-bottom: 28px;
}
.purifydefaultindex_newsletter-form {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
    max-width: 480px;
    margin: 0 auto;
}
.purifydefaultindex_newsletter-form input {
    flex: 1;
    min-width: 220px;
    padding: 12px 18px;
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    font-size: 15px;
    outline: none;
    background: #fff;
    color: #111827;
}
.purifydefaultindex_newsletter-form input:focus {
    border-color: #111827;
}

/* FOOTER STRIP */
.purifydefaultindex_footer-strip {
    background: #111827;
    padding: 24px 0;
}
.purifydefaultindex_footer-strip-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    color: #6b7280;
    font-size: 13px;
}
.purifydefaultindex_footer-strip a {
    color: #9ca3af;
    text-decoration: none;
}
.purifydefaultindex_footer-strip a:hover {
    color: #fff;
}
.purifydefaultindex_payment-icons {
    display: flex;
    gap: 8px;
    align-items: center;
}
.purifydefaultindex_payment-icon {
    background: #1f2937;
    border: 1px solid #374151;
    border-radius: 4px;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 700;
    color: #d1d5db;
    letter-spacing: 0.04em;
}

/* BG VARIANTS */
.purifydefaultindex_bg-light { background: #f9fafb; }
.purifydefaultindex_bg-white { background: #fff; }

/* RESPONSIVE */
@media (max-width: 768px) {
    .purifydefaultindex_hero-inner {
        grid-template-columns: 1fr;
        gap: 32px;
        padding: 48px 0;
    }
    .purifydefaultindex_hero-visual {
        height: 260px;
    }
    .purifydefaultindex_banner {
        grid-template-columns: 1fr;
        padding: 36px 28px;
        gap: 24px;
    }
    .purifydefaultindex_section {
        padding: 48px 0;
    }
    .purifydefaultindex_product-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 14px;
    }
    .purifydefaultindex_product-img {
        height: 160px;
        font-size: 38px;
    }
}
</style>

<main>

    <!-- purify:zone:announce_start -->
    <div class="purifydefaultindex_announce">
        🚚 150 ₺ ve üzeri siparişlerde ücretsiz kargo!
        <a href="<?= $cfg_mainurl ?>/shop">Alışverişe Başla</a>
    </div>
    <!-- purify:zone:announce_end -->

    <!-- purify:zone:hero_start -->
    <section class="purifydefaultindex_hero">
        <div class="purifydefaultindex_container">
            <div class="purifydefaultindex_hero-inner">
                <div>
                    <div class="purifydefaultindex_hero-badge">Yeni Sezon</div>
                    <h1>Tarzını Yansıt,<br>Fırsatı Kaçırma.</h1>
                    <p>En yeni koleksiyonlar, özel kampanyalar ve binlerce ürün seni bekliyor. Güvenli alışverişin adresi <?= htmlspecialchars($cfg_name) ?>.</p>
                    <div class="purifydefaultindex_hero-btns">
                        <a href="<?= $cfg_mainurl ?>/shop" class="purifydefaultindex_btn purifydefaultindex_btn-primary">
                            Koleksiyonu Keşfet <i class="las la-arrow-right"></i>
                        </a>
                        <a href="<?= $cfg_mainurl ?>/shop?q=sale" class="purifydefaultindex_btn purifydefaultindex_btn-secondary">
                            İndirimdekiler
                        </a>
                    </div>
                </div>
                <div class="purifydefaultindex_hero-visual">
                    <div class="purifydefaultindex_hero-img-placeholder">
                        <i class="las la-image"></i>
                        <span>Kampanya Görseli</span>
                    </div>
                    <div class="purifydefaultindex_hero-tag">
                        <i class="las la-check-circle"></i>
                        Güvenli &amp; Hızlı Teslimat
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- purify:zone:hero_end -->

    <!-- purify:zone:trust_start -->
    <div class="purifydefaultindex_trust">
        <div class="purifydefaultindex_container">
            <div class="purifydefaultindex_trust-grid">
                <div class="purifydefaultindex_trust-item"><i class="las la-truck"></i> Hızlı Teslimat</div>
                <div class="purifydefaultindex_trust-item"><i class="las la-shield-alt"></i> Güvenli Ödeme</div>
                <div class="purifydefaultindex_trust-item"><i class="las la-undo"></i> Kolay İade</div>
                <div class="purifydefaultindex_trust-item"><i class="las la-headset"></i> 7/24 Destek</div>
                <div class="purifydefaultindex_trust-item"><i class="las la-certificate"></i> Orijinal Ürün</div>
            </div>
        </div>
    </div>
    <!-- purify:zone:trust_end -->

    <!-- purify:zone:featured_start -->
    <section class="purifydefaultindex_section purifydefaultindex_bg-light">
        <div class="purifydefaultindex_container">
            <div class="purifydefaultindex_section-header">
                <h2>Öne Çıkan Ürünler</h2>
                <p>Bu haftanın en çok tercih edilenleri.</p>
            </div>
            <div class="purifydefaultindex_product-grid">

                <?php if (!empty($featured_p)): ?>
                    <?php foreach (array_slice($featured_p, 0, 4) as $p): ?>
                        <a href="<?= $cfg_mainurl ?>/product/<?= $p['slug'] ?? $p['id'] ?>" class="purifydefaultindex_product-card">
                            <div class="purifydefaultindex_product-img">
                                <?php if (!empty($p['image'])): ?>
                                    <img src="<?= $cfg_mainurl ?>/inc/data/ecommerce/vault/<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['title']) ?>" style="max-width:80%; max-height:80%; object-fit:contain;">
                                <?php else: ?>
                                    <i class="las la-image"></i>
                                <?php endif; ?>
                                
                                <?php if (!empty($p['old_price']) && $p['old_price'] > $p['price']): ?>
                                    <?php $discount = round((($p['old_price'] - $p['price']) / $p['old_price']) * 100); ?>
                                    <div class="purifydefaultindex_product-badge">-%<?= $discount ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="purifydefaultindex_product-body">
                                <div class="purifydefaultindex_product-cat"><?= htmlspecialchars($p['category'] ?? 'Genel') ?></div>
                                <div class="purifydefaultindex_product-name"><?= htmlspecialchars($p['title']) ?></div>
                                <div class="purifydefaultindex_stars">
                                    <?php 
                                    $rating = $p['rating'] ?? 5.0; 
                                    $fullStars = floor($rating);
                                    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
                                    $emptyStars = 5 - ($fullStars + $halfStar);
                                    for($i=0; $i<$fullStars; $i++) echo '<i class="las la-star"></i>';
                                    if($halfStar) echo '<i class="las la-star-half-alt"></i>';
                                    for($i=0; $i<$emptyStars; $i++) echo '<i class="lar la-star"></i>';
                                    ?>
                                    <span>(<?= $p['reviews_count'] ?? rand(10, 100) ?>)</span>
                                </div>
                                <div class="purifydefaultindex_product-price-row">
                                    <span class="purifydefaultindex_price-new"><?= number_format($p['price'], 2) ?> <?= $p['currency'] ?? '₺' ?></span>
                                    <?php if (!empty($p['old_price'])): ?>
                                        <span class="purifydefaultindex_price-old"><?= number_format($p['old_price'], 2) ?> <?= $p['currency'] ?? '₺' ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="purifydefaultindex_product-add">
                                    <span class="purifydefaultindex_btn purifydefaultindex_btn-primary" style="width:100%;justify-content:center;font-size:14px;padding:10px 0;">
                                        Sepete Ekle <i class="las la-shopping-cart"></i>
                                    </span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1 / -1; text-align: center; color: #9ca3af; padding: 40px 0;">Henüz öne çıkan ürün eklenmemiş.</p>
                <?php endif; ?>

            </div>
            <div class="purifydefaultindex_section-link">
                <a href="<?= $cfg_mainurl ?>/shop" class="purifydefaultindex_btn purifydefaultindex_btn-secondary">
                    Tüm Ürünleri Gör <i class="las la-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
    <!-- purify:zone:featured_end -->

    <!-- purify:zone:campaign_start -->
    <section class="purifydefaultindex_section purifydefaultindex_bg-white">
        <div class="purifydefaultindex_container">
            <div class="purifydefaultindex_banner">
                <div>
                    <h2>Flash İndirim: %40'a Kadar</h2>
                    <p style="color:#94a3b8;font-size:16px;margin-bottom:28px;">Sınırlı sayıda stok — kaçırmadan sepete ekle.</p>
                    <a href="<?= $cfg_mainurl ?>/shop?q=flash" class="purifydefaultindex_btn purifydefaultindex_btn-white">
                        İndirimleri Keşfet <i class="las la-bolt"></i>
                    </a>
                </div>
                <div>
                    <div class="purifydefaultindex_banner-timer" id="purifydefaultindex_countdown">
                        <div class="purifydefaultindex_timer-block">
                            <div class="purifydefaultindex_timer-num" id="purifydefaultindex_h">00</div>
                            <div class="purifydefaultindex_timer-label">Saat</div>
                        </div>
                        <div class="purifydefaultindex_timer-block">
                            <div class="purifydefaultindex_timer-num" id="purifydefaultindex_m">00</div>
                            <div class="purifydefaultindex_timer-label">Dakika</div>
                        </div>
                        <div class="purifydefaultindex_timer-block">
                            <div class="purifydefaultindex_timer-num" id="purifydefaultindex_s">00</div>
                            <div class="purifydefaultindex_timer-label">Saniye</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- purify:zone:campaign_end -->

    <!-- purify:zone:new_arrivals_start -->
    <section class="purifydefaultindex_section purifydefaultindex_bg-light">
        <div class="purifydefaultindex_container">
            <div class="purifydefaultindex_section-header">
                <h2>Yeni Gelenler</h2>
                <p>Taze stoklar, yeni sezon parçalar.</p>
            </div>
            <div class="purifydefaultindex_product-grid">

                <?php if (!empty($new_p)): ?>
                    <?php foreach ($new_p as $p): ?>
                        <a href="<?= $cfg_mainurl ?>/product/<?= $p['slug'] ?? $p['id'] ?>" class="purifydefaultindex_product-card">
                            <div class="purifydefaultindex_product-img">
                                <?php if (!empty($p['image'])): ?>
                                    <img src="<?= $cfg_mainurl ?>/inc/data/ecommerce/vault/<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['title']) ?>" style="max-width:80%; max-height:80%; object-fit:contain;">
                                <?php else: ?>
                                    <i class="las la-image"></i>
                                <?php endif; ?>
                                <div class="purifydefaultindex_product-badge purifydefaultindex_badge-new">Yeni</div>
                            </div>
                            <div class="purifydefaultindex_product-body">
                                <div class="purifydefaultindex_product-cat"><?= htmlspecialchars($p['category'] ?? 'Genel') ?></div>
                                <div class="purifydefaultindex_product-name"><?= htmlspecialchars($p['title']) ?></div>
                                <div class="purifydefaultindex_stars">
                                    <?php 
                                    $rating = $p['rating'] ?? 5.0; 
                                    $fullStars = floor($rating);
                                    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
                                    $emptyStars = 5 - ($fullStars + $halfStar);
                                    for($i=0; $i<$fullStars; $i++) echo '<i class="las la-star"></i>';
                                    if($halfStar) echo '<i class="las la-star-half-alt"></i>';
                                    for($i=0; $i<$emptyStars; $i++) echo '<i class="lar la-star"></i>';
                                    ?>
                                    <span>(<?= $p['reviews_count'] ?? rand(10, 100) ?>)</span>
                                </div>
                                <div class="purifydefaultindex_product-price-row">
                                    <span class="purifydefaultindex_price-new"><?= number_format($p['price'], 2) ?> <?= $p['currency'] ?? '₺' ?></span>
                                </div>
                                <div class="purifydefaultindex_product-add">
                                    <span class="purifydefaultindex_btn purifydefaultindex_btn-primary" style="width:100%;justify-content:center;font-size:14px;padding:10px 0;">
                                        Sepete Ekle <i class="las la-shopping-cart"></i>
                                    </span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1 / -1; text-align: center; color: #9ca3af; padding: 40px 0;">Yeni ürünler yolda!</p>
                <?php endif; ?>

            </div>
            <div class="purifydefaultindex_section-link">
                <a href="<?= $cfg_mainurl ?>/shop?q=new" class="purifydefaultindex_btn purifydefaultindex_btn-secondary">
                    Tüm Yeni Ürünler <i class="las la-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
    <!-- purify:zone:new_arrivals_end -->

    <!-- purify:zone:reviews_start -->
    <section class="purifydefaultindex_section purifydefaultindex_bg-white">
        <div class="purifydefaultindex_container">
            <div class="purifydefaultindex_section-header">
                <h2>Müşteri Yorumları</h2>
                <p>Gerçek alıcılar, gerçek deneyimler.</p>
            </div>
            <div class="purifydefaultindex_review-grid">
                <div class="purifydefaultindex_review-card">
                    <div class="purifydefaultindex_review-stars">
                        <i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i>
                    </div>
                    <p class="purifydefaultindex_review-text">"Ürünlerin kalitesi beklentimin çok üzerinde. Kargo da süper hızlıydı, iki günde kapımda. Kesinlikle tekrar alışveriş yapacağım."</p>
                    <div class="purifydefaultindex_review-user">
                        <div class="purifydefaultindex_review-avatar">AK</div>
                        <div>
                            <div class="purifydefaultindex_review-name">Ayşe K. <span class="purifydefaultindex_verified"><i class="las la-check-circle"></i> Doğrulanmış</span></div>
                            <div class="purifydefaultindex_review-date">12 Mart 2025</div>
                        </div>
                    </div>
                </div>
                <div class="purifydefaultindex_review-card">
                    <div class="purifydefaultindex_review-stars">
                        <i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i>
                    </div>
                    <p class="purifydefaultindex_review-text">"Müşteri hizmetleri çok ilgili. Bir sorunum olduğunda anında çözüme kavuşturdular. Hem fiyat hem kalite açısından piyasanın en iyisi."</p>
                    <div class="purifydefaultindex_review-user">
                        <div class="purifydefaultindex_review-avatar">MT</div>
                        <div>
                            <div class="purifydefaultindex_review-name">Murat T. <span class="purifydefaultindex_verified"><i class="las la-check-circle"></i> Doğrulanmış</span></div>
                            <div class="purifydefaultindex_review-date">3 Nisan 2025</div>
                        </div>
                    </div>
                </div>
                <div class="purifydefaultindex_review-card">
                    <div class="purifydefaultindex_review-stars">
                        <i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i><i class="las la-star-half-alt"></i>
                    </div>
                    <p class="purifydefaultindex_review-text">"İade süreci son derece kolaydı. Tek tıkla talep oluşturdum, ertesi gün kurye geldi. Bu kadar sorunsuz bir alışveriş deneyimi çok nadir."</p>
                    <div class="purifydefaultindex_review-user">
                        <div class="purifydefaultindex_review-avatar">ZB</div>
                        <div>
                            <div class="purifydefaultindex_review-name">Zeynep B. <span class="purifydefaultindex_verified"><i class="las la-check-circle"></i> Doğrulanmış</span></div>
                            <div class="purifydefaultindex_review-date">28 Mart 2025</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- purify:zone:reviews_end -->


    <!-- purify:zone:newsletter_start -->
    <div class="purifydefaultindex_newsletter">
        <div class="purifydefaultindex_container">
            <h2>Fırsatları İlk Sen Öğren</h2>
            <p>Kampanyalar, yeni ürünler ve özel indirimler için bültenimize abone ol.</p>
            <div class="purifydefaultindex_newsletter-form">
                <input type="email" placeholder="E-posta adresin" />
                <button class="purifydefaultindex_btn purifydefaultindex_btn-primary" onclick="return false;">
                    Abone Ol <i class="las la-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
    <!-- purify:zone:newsletter_end -->

</main>

<script>
(function() {
    var end = new Date();
    end.setHours(end.getHours() + 5, end.getMinutes() + 47, end.getSeconds() + 33);

    function purifydefaultindex_tick() {
        var now = new Date();
        var diff = Math.max(0, Math.floor((end - now) / 1000));
        var h = Math.floor(diff / 3600);
        var m = Math.floor((diff % 3600) / 60);
        var s = diff % 60;
        var pad = function(n) { return n < 10 ? '0' + n : '' + n; };
        var hEl = document.getElementById('purifydefaultindex_h');
        var mEl = document.getElementById('purifydefaultindex_m');
        var sEl = document.getElementById('purifydefaultindex_s');
        if (hEl) hEl.textContent = pad(h);
        if (mEl) mEl.textContent = pad(m);
        if (sEl) sEl.textContent = pad(s);
    }
    purifydefaultindex_tick();
    setInterval(purifydefaultindex_tick, 1000);
})();
</script>