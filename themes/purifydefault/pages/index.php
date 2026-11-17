<?php
$title = "Home";
$desc = "Fast, lightweight website and digital store powered by PurifyCMS.";

$base_site_url = rtrim($cfg_mainurl ?? '', '/');
$ecom_active = !empty($cfg_enable_ecommerce);

$featured_p = [];
if ($ecom_active) {
    require_once __DIR__ . '/../../../inc/PurifyTrade.php';
    $p_file = __DIR__ . '/../../../inc/data/ecommerce/products.json';
    $all_p = file_exists($p_file) ? (json_decode(file_get_contents($p_file), true) ?: []) : [];
    $featured_p = array_filter($all_p, function($item) {
        return !empty($item['featured']);
    });
    if (empty($featured_p)) {
        $featured_p = array_slice($all_p, 0, 4);
    } else {
        $featured_p = array_slice($featured_p, 0, 4);
    }
}
?>

<style>
.home-hero {
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    padding: 80px 0;
    position: relative;
    overflow: hidden;
}
.home-hero-grid {
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    align-items: center;
    gap: 56px;
}
.home-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 5px 12px;
    border-radius: 999px;
    background: rgba(79, 70, 229, 0.08);
    color: #4f46e5;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.03em;
    margin-bottom: 18px;
}
.home-hero h1 {
    font-size: clamp(34px, 4.5vw, 52px);
    font-weight: 800;
    line-height: 1.12;
    letter-spacing: -0.03em;
    color: #0f172a;
    margin-bottom: 18px;
}
.home-hero p {
    font-size: 16.5px;
    color: #475569;
    line-height: 1.65;
    max-width: 520px;
    margin-bottom: 28px;
}
.home-hero-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.home-hero-showcase {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 24px;
}
.home-showcase-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 16px;
    margin-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}
.home-showcase-dots {
    display: flex;
    gap: 6px;
}
.home-showcase-dots span {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: #cbd5e1;
}
.home-showcase-status {
    font-size: 12px;
    font-weight: 600;
    color: #059669;
    background: rgba(16, 185, 129, 0.1);
    padding: 3px 10px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.home-showcase-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.home-showcase-item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
}
.home-showcase-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(79, 70, 229, 0.08);
    color: #4f46e5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    flex-shrink: 0;
}
.home-showcase-item h4 {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 2px;
}
.home-showcase-item p {
    font-size: 12.5px;
    color: #64748b;
    margin: 0;
    line-height: 1.4;
}

/* Highlights Strip */
.home-trust {
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    padding: 18px 0;
}
.home-trust-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}
.home-trust-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 13.5px;
    font-weight: 600;
    color: #334155;
}
.home-trust-item i {
    font-size: 19px;
    color: #4f46e5;
}

/* Product Grid */
.home-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 24px;
}
.home-product-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    transition: all 0.2s ease;
}
.home-product-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 24px -8px rgba(15, 23, 42, 0.08);
    border-color: #cbd5e1;
}
.home-product-img {
    height: 200px;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 20px;
}
.home-product-img img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.home-product-img i {
    font-size: 48px;
    color: #cbd5e1;
}
.home-product-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #ef4444;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 999px;
}
.home-product-body {
    padding: 18px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
}
.home-product-title {
    font-size: 15.5px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.35;
}
.home-product-desc {
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
    margin-bottom: 8px;
}
.home-product-footer {
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.home-product-price {
    font-size: 17px;
    font-weight: 800;
    color: #4f46e5;
}
.home-product-price small {
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
}

/* Steps Grid */
.home-steps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
.home-step-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 28px;
}
.home-step-num {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: rgba(79, 70, 229, 0.08);
    color: #4f46e5;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}
.home-step-card h3 {
    font-size: 17px;
    margin-bottom: 8px;
}
.home-step-card p {
    font-size: 14px;
    color: #64748b;
    line-height: 1.6;
}

/* CTA Box */
.home-cta-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 48px 36px;
    text-align: center;
}
.home-cta-box h2 {
    font-size: clamp(24px, 3vw, 32px);
    margin-bottom: 10px;
}
.home-cta-box p {
    color: #64748b;
    font-size: 15.5px;
    max-width: 500px;
    margin: 0 auto 24px;
}

@media (max-width: 900px) {
    .home-hero-grid {
        grid-template-columns: 1fr;
        gap: 36px;
    }
    .home-trust-grid {
        grid-template-columns: 1fr 1fr;
    }
    .home-steps-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 560px) {
    .home-hero {
        padding: 52px 0;
    }
    .home-trust-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<main>
    <!-- purify:zone:hero_start -->
    <section class="home-hero">
        <div class="container">
            <div class="home-hero-grid">
                <div>
                    <div class="home-hero-badge">
                        <i class="las la-cube"></i> Flat-File CMS
                    </div>
                    <h1>Fast, simple website management.</h1>
                    <p>Publish pages, manage digital or physical products, and serve cached static HTML without setting up or maintaining a database.</p>
                    <div class="home-hero-actions">
                        <a href="<?= htmlspecialchars($base_site_url) ?>/contact" class="btn btn-primary">
                            Contact Us <i class="las la-arrow-right"></i>
                        </a>
                        <a href="<?= htmlspecialchars($base_site_url) ?>/about" class="btn btn-secondary">
                            About Us
                        </a>
                    </div>
                </div>

                <div class="home-hero-showcase">
                    <div class="home-showcase-header">
                        <div class="home-showcase-dots">
                            <span></span><span></span><span></span>
                        </div>
                        <span class="home-showcase-status">
                            <i class="las la-check-circle"></i> Ready
                        </span>
                    </div>
                    <div class="home-showcase-list">
                        <div class="home-showcase-item">
                            <div class="home-showcase-icon"><i class="las la-folder-open"></i></div>
                            <div>
                                <h4>File-Based Storage</h4>
                                <p>Pages, settings, and orders are stored in local JSON and PHP files.</p>
                            </div>
                        </div>
                        <div class="home-showcase-item">
                            <div class="home-showcase-icon"><i class="las la-bolt"></i></div>
                            <div>
                                <h4>Static HTML Cache</h4>
                                <p>Pages are cached as static HTML files and refreshed automatically on update.</p>
                            </div>
                        </div>
                        <div class="home-showcase-item">
                            <div class="home-showcase-icon"><i class="las la-shield-alt"></i></div>
                            <div>
                                <h4>Built-in Security</h4>
                                <p>CSRF validation, session protection, and IP restrictions included.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- purify:zone:hero_end -->

    <!-- purify:zone:trust_start -->
    <div class="home-trust">
        <div class="container">
            <div class="home-trust-grid">
                <div class="home-trust-item"><i class="las la-database"></i> No Database Required</div>
                <div class="home-trust-item"><i class="las la-bolt"></i> Static HTML Caching</div>
                <div class="home-trust-item"><i class="las la-edit"></i> Visual Page Editor</div>
                <div class="home-trust-item"><i class="las la-file-archive"></i> One-Click ZIP Backup</div>
            </div>
        </div>
    </div>
    <!-- purify:zone:trust_end -->

    <?php if ($ecom_active): ?>
    <section class="page-section" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
        <div class="container">
            <div class="section-header">
                <span class="section-badge"><i class="las la-shopping-bag"></i> Store</span>
                <h2>Featured Products</h2>
                <p>Browse our latest products and digital downloads.</p>
            </div>

            <?php if (!empty($featured_p)): ?>
            <div class="home-products-grid">
                <?php foreach ($featured_p as $p): 
                    $price_incl = (float)($p['price'] ?? 0) * (1 + ((float)($p['tax_rate'] ?? 20) / 100));
                    $has_disc = !empty($p['old_price']) && $p['old_price'] > $p['price'];
                    $disc_pct = $has_disc ? round((($p['old_price'] - $p['price']) / $p['old_price']) * 100) : 0;
                    $cur = $p['currency'] ?? ($cfg_ecommerce_currency ?? 'TRY');
                ?>
                <a href="<?= htmlspecialchars($base_site_url) ?>/product/<?= htmlspecialchars($p['slug'] ?? $p['id']) ?>" class="home-product-card">
                    <div class="home-product-img">
                        <?php if (!empty($p['image'])): ?>
                            <img src="<?= htmlspecialchars($base_site_url) ?>/inc/data/ecommerce/vault/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>">
                        <?php else: ?>
                            <i class="las la-cube"></i>
                        <?php endif; ?>
                        <?php if ($has_disc): ?>
                            <span class="home-product-badge">-<?= $disc_pct ?>%</span>
                        <?php endif; ?>
                    </div>
                    <div class="home-product-body">
                        <div class="home-product-title"><?= htmlspecialchars($p['title']) ?></div>
                        <div class="home-product-desc"><?= htmlspecialchars(mb_substr(strip_tags($p['description'] ?? ''), 0, 80)) ?>...</div>
                        <div class="home-product-footer">
                            <span class="home-product-price"><?= number_format($price_incl, 2) ?> <small><?= htmlspecialchars($cur) ?></small></span>
                            <span class="btn btn-secondary" style="padding: 6px 12px; font-size: 12.5px;">View <i class="las la-arrow-right"></i></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 36px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; color: #64748b;">
                <i class="las la-box-open" style="font-size: 36px; color: #94a3b8; margin-bottom: 8px; display: block;"></i>
                <p>No products have been added yet.</p>
            </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 32px;">
                <a href="<?= htmlspecialchars($base_site_url) ?>/shop" class="btn btn-primary">
                    View All Products <i class="las la-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- purify:zone:features_start -->
    <section class="page-section">
        <div class="container">
            <div class="section-header">
                <span class="section-badge"><i class="las la-layer-group"></i> Features</span>
                <h2>Everything Included Out of the Box</h2>
                <p>A clean set of tools for managing pages, themes, and optional e-commerce.</p>
            </div>

            <div class="bento-grid">
                <div class="bento-card wide">
                    <div class="bento-icon"><i class="las la-bolt"></i></div>
                    <h3>Static Page Caching</h3>
                    <p>Generates static HTML files on the first visit so pages load quickly without executing PHP on every request. Any change in the admin panel clears the cache automatically.</p>
                </div>
                <div class="bento-card narrow">
                    <div class="bento-icon"><i class="las la-pen-nib"></i></div>
                    <h3>Visual Zone Editor</h3>
                    <p>Edit page titles, meta descriptions, and content sections directly from the administration panel.</p>
                </div>
                <div class="bento-card narrow">
                    <div class="bento-icon"><i class="las la-chart-bar"></i></div>
                    <h3>Visitor Statistics</h3>
                    <p>Track daily page views, unique visitors, and popular pages stored in monthly JSON files.</p>
                </div>
                <div class="bento-card wide">
                    <div class="bento-icon"><i class="las la-archive"></i></div>
                    <h3>Easy Backups &amp; Updates</h3>
                    <p>Because all content and settings live inside the project folder, you can download a full ZIP backup or install new GitHub releases directly from the control panel.</p>
                </div>
            </div>
        </div>
    </section>
    <!-- purify:zone:features_end -->

    <!-- purify:zone:steps_start -->
    <section class="page-section" style="background: #ffffff; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
        <div class="container">
            <div class="section-header">
                <span class="section-badge"><i class="las la-compass"></i> Workflow</span>
                <h2>How It Works</h2>
                <p>Set up and customize your website in three straightforward steps.</p>
            </div>

            <div class="home-steps-grid">
                <div class="home-step-card">
                    <div class="home-step-num">01</div>
                    <h3>Configure Settings</h3>
                    <p>Set your site title, logo, contact email, SMTP server, and optional store settings from the administration panel.</p>
                </div>
                <div class="home-step-card">
                    <div class="home-step-num">02</div>
                    <h3>Customize Pages &amp; Theme</h3>
                    <p>Edit existing pages using the Visual Editor or create new `.php` templates inside your active theme folder.</p>
                </div>
                <div class="home-step-card">
                    <div class="home-step-num">03</div>
                    <h3>Publish &amp; Manage</h3>
                    <p>Monitor incoming contact messages, view visitor traffic, and keep your installation updated with a single click.</p>
                </div>
            </div>
        </div>
    </section>
    <!-- purify:zone:steps_end -->

    <!-- purify:zone:cta_start -->
    <section class="page-section">
        <div class="container">
            <div class="home-cta-box">
                <h2>Have a Question?</h2>
                <p>Send us a message through our contact page and we will get back to you as soon as possible.</p>
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                    <a href="<?= htmlspecialchars($base_site_url) ?>/contact" class="btn btn-primary">
                        Contact Us <i class="las la-arrow-right"></i>
                    </a>
                    <a href="<?= htmlspecialchars($base_site_url) ?>/about" class="btn btn-secondary">
                        About Us
                    </a>
                </div>
            </div>
        </div>
    </section>
    <!-- purify:zone:cta_end -->
</main>
