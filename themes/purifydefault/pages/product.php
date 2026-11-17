<?php
if (empty($cfg_enable_ecommerce)) {
    header('HTTP/1.1 404 Not Found');
    require __DIR__ . '/404.php';
    return;
}

if (!isset($p)) {
    require_once __DIR__ . '/../../../inc/PurifyTrade.php';
    $p_file = __DIR__ . '/../../../inc/data/ecommerce/products.json';
    $all_p = file_exists($p_file) ? (json_decode(file_get_contents($p_file), true) ?: []) : [];
    $p = $all_p[$_GET['id'] ?? ''] ?? null;
}

if (!$p) {
    header('HTTP/1.1 404 Not Found');
    require __DIR__ . '/404.php';
    return;
}

$title = $p['title'];
$desc = strip_tags(mb_substr($p['description'] ?? '', 0, 160));
$base_site_url = rtrim($cfg_mainurl ?? '', '/');
$cur = $p['currency'] ?? ($cfg_ecommerce_currency ?? 'TRY');

$price_incl = (float)($p['price'] ?? 0) * (1 + ((float)($p['tax_rate'] ?? 20) / 100));
$hasDiscount = !empty($p['old_price']) && $p['old_price'] > $p['price'];
?>

<!-- Breadcrumb -->
<div class="breadcrumb-wrap">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?= htmlspecialchars($base_site_url ?: '/') ?>">Home</a>
            <i class="las la-angle-right"></i>
            <a href="<?= htmlspecialchars($base_site_url) ?>/shop">Shop</a>
            <i class="las la-angle-right"></i>
            <span><?= htmlspecialchars($p['title']) ?></span>
        </nav>
    </div>
</div>

<!-- Product Detail -->
<section class="product-detail-section">
    <div class="container">
        <!-- purify:zone:product_details_start -->
        <div class="product-detail-grid">
            <!-- Image Column -->
            <div class="product-gallery">
                <div class="product-main-image">
                    <?php if (!empty($p['image'])): ?>
                        <img src="<?= htmlspecialchars($base_site_url) ?>/inc/data/ecommerce/vault/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" id="mainImage">
                    <?php else: ?>
                        <div class="product-image-placeholder">
                            <i class="las la-cube"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info Column -->
            <div class="product-info-panel">
                <div class="product-header">
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <?php if (!empty($p['featured'])): ?>
                            <span class="product-tag featured"><i class="las la-star"></i> Featured</span>
                        <?php endif; ?>
                        <?php if ($hasDiscount): ?>
                            <span class="product-tag sale">Special Offer</span>
                        <?php endif; ?>
                        <span class="product-tag type-badge">
                            <i class="las <?= ($p['type'] ?? 'physical') === 'digital' ? 'la-cloud-download-alt' : 'la-box' ?>"></i>
                            <?= ($p['type'] ?? 'physical') === 'digital' ? 'Digital Delivery' : 'Physical Product' ?>
                        </span>
                    </div>
                    
                    <h1 class="product-name"><?= htmlspecialchars($p['title']) ?></h1>
                    
                    <div class="product-meta">
                        <div class="product-rating">
                            <?php 
                            $rating = (float)($p['rating'] ?? 5.0); 
                            $fullStars = floor($rating);
                            $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
                            $emptyStars = 5 - ($fullStars + $halfStar);
                            for($i=0; $i<$fullStars; $i++) echo '<i class="las la-star"></i>';
                            if($halfStar) echo '<i class="las la-star-half-alt"></i>';
                            for($i=0; $i<$emptyStars; $i++) echo '<i class="lar la-star"></i>';
                            ?>
                            <span>(<?= number_format($rating, 1) ?>)</span>
                        </div>
                        <span class="product-stock"><i class="las la-check-circle"></i> In Stock</span>
                    </div>
                </div>

                <div class="product-price-block">
                    <span class="current-price" id="priceDisplay">
                        <?= number_format($price_incl, 2) ?> <small><?= htmlspecialchars($cur) ?></small>
                    </span>
                    <?php if ($hasDiscount): ?>
                        <span class="old-price"><?= number_format($p['old_price'] * (1 + ($p['tax_rate'] ?? 20) / 100), 2) ?> <?= htmlspecialchars($cur) ?></span>
                        <span class="discount-badge">-<?= round((($p['old_price'] - $p['price']) / $p['old_price']) * 100) ?>%</span>
                    <?php endif; ?>
                </div>

                <!-- Variations -->
                <?php if (!empty($p['variation_groups'])): ?>
                    <div class="product-variations" id="variationSection">
                        <?php foreach ($p['variation_groups'] as $vg): ?>
                            <div class="variation-group">
                                <label class="variation-label"><?= htmlspecialchars($vg['title']) ?></label>
                                <div class="variation-options">
                                    <?php if (($vg['type'] ?? 'single') === 'single'): ?>
                                        <select class="variation-select sf-var-control" data-group="<?= htmlspecialchars($vg['title']) ?>">
                                            <?php foreach ($vg['options'] as $opt): ?>
                                                <option value="<?= htmlspecialchars($opt['name']) ?>" data-price="<?= (float)($opt['price'] ?? 0) ?>">
                                                    <?= htmlspecialchars($opt['name']) ?><?= !empty($opt['price']) ? ' (+' . number_format((float)$opt['price'], 2) . ' ' . htmlspecialchars($cur) . ')' : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <?php foreach ($vg['options'] as $opt): ?>
                                            <label class="variation-checkbox">
                                                <input type="checkbox" class="sf-var-control" data-group="<?= htmlspecialchars($vg['title']) ?>" value="<?= htmlspecialchars($opt['name']) ?>" data-price="<?= (float)($opt['price'] ?? 0) ?>">
                                                <span class="check-box"><?= htmlspecialchars($opt['name']) ?></span>
                                                <span class="check-price">+<?= number_format((float)($opt['price'] ?? 0), 2) ?> <?= htmlspecialchars($cur) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="product-actions">
                    <div class="quantity-selector">
                        <button type="button" class="qty-btn" onclick="changeQty(-1)">-</button>
                        <input type="number" id="quantity" value="1" min="1" max="10" readonly>
                        <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
                    </div>
                    
                    <button class="btn-add-to-cart" type="button" onclick="addToCartProduct()">
                        <i class="las la-shopping-cart"></i>
                        <span>Add to Cart</span>
                    </button>
                    
                    <button class="btn-buy-now" type="button" onclick="buyNowProduct()">
                        <i class="las la-bolt"></i>
                        <span>Buy Now</span>
                    </button>
                </div>

                <!-- Trust Badges -->
                <div class="trust-badges">
                    <div class="trust-item">
                        <i class="las la-shield-alt"></i>
                        <span>256-Bit SSL Checkout</span>
                    </div>
                    <div class="trust-item">
                        <i class="las la-bolt"></i>
                        <span>Fast Order Processing</span>
                    </div>
                    <div class="trust-item">
                        <i class="las la-headset"></i>
                        <span>Dedicated Support</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- purify:zone:product_details_end -->

        <!-- Description Tabs -->
        <!-- purify:zone:product_tabs_start -->
        <div class="product-tabs">
            <div class="tab-buttons">
                <button class="tab-btn active" type="button" data-tab="desc">Description</button>
                <button class="tab-btn" type="button" data-tab="details">Specifications</button>
            </div>
            
            <div class="tab-content active" id="desc">
                <div class="product-description">
                    <?= $p['description'] ?? '' ?>
                </div>
            </div>
            
            <div class="tab-content" id="details">
                <table class="details-table">
                    <tr>
                        <td>Product ID</td>
                        <td><code><?= htmlspecialchars($p['id']) ?></code></td>
                    </tr>
                    <tr>
                        <td>Fulfillment Type</td>
                        <td><?= ($p['type'] ?? 'physical') === 'digital' ? 'Digital Download / License' : 'Physical Shipment' ?></td>
                    </tr>
                    <tr>
                        <td>Tax Rate</td>
                        <td><?= (float)($p['tax_rate'] ?? 20) ?>%</td>
                    </tr>
                    <tr>
                        <td>Currency</td>
                        <td><?= htmlspecialchars($cur) ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <!-- purify:zone:product_tabs_end -->
    </div>
</section>

<style>
.breadcrumb-wrap {
    background: #ffffff;
    padding: 14px 0;
    border-bottom: 1px solid #e2e8f0;
}
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13.5px;
    color: #64748b;
}
.breadcrumb a {
    color: #64748b;
    text-decoration: none;
    transition: color 0.15s;
}
.breadcrumb a:hover {
    color: #4f46e5;
}
.breadcrumb i {
    font-size: 11px;
    color: #94a3b8;
}
.breadcrumb span {
    color: #0f172a;
    font-weight: 600;
}
.product-detail-section {
    padding: 48px 0 80px;
    background: #f8fafc;
}
.product-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 48px;
    margin-bottom: 48px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 36px;
}
@media (max-width: 900px) {
    .product-detail-grid {
        grid-template-columns: 1fr;
        gap: 32px;
        padding: 24px;
    }
}
.product-main-image {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}
.product-main-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.product-image-placeholder {
    font-size: 80px;
    color: #cbd5e1;
}
.product-info-panel {
    display: flex;
    flex-direction: column;
    gap: 22px;
}
.product-header {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.product-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}
.product-tag.featured {
    background: rgba(245, 158, 11, 0.12);
    color: #b45309;
}
.product-tag.sale {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
}
.product-tag.type-badge {
    background: rgba(79, 70, 229, 0.08);
    color: #4f46e5;
}
.product-name {
    font-size: clamp(26px, 3.5vw, 34px);
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    margin: 0;
}
.product-meta {
    display: flex;
    align-items: center;
    gap: 18px;
}
.product-rating {
    display: flex;
    align-items: center;
    gap: 3px;
    color: #f59e0b;
    font-size: 14px;
}
.product-rating span {
    color: #64748b;
    font-size: 13px;
    margin-left: 4px;
}
.product-stock {
    color: #10b981;
    font-size: 13.5px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}
.product-price-block {
    display: flex;
    align-items: baseline;
    gap: 14px;
    flex-wrap: wrap;
    padding: 18px 0;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
}
.current-price {
    font-size: 34px;
    font-weight: 800;
    color: #4f46e5;
}
.current-price small {
    font-size: 16px;
    font-weight: 600;
    color: #64748b;
}
.old-price {
    font-size: 17px;
    color: #94a3b8;
    text-decoration: line-through;
}
.discount-badge {
    background: #ef4444;
    color: #ffffff;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}
.product-variations {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.variation-label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
}
.variation-select {
    width: 100%;
    padding: 11px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 14px;
    background: #f8fafc;
    color: #0f172a;
    cursor: pointer;
    font-family: inherit;
}
.variation-select:focus {
    border-color: #4f46e5;
    background: #ffffff;
    outline: none;
}
.variation-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    margin-bottom: 8px;
}
.variation-checkbox:hover {
    border-color: #4f46e5;
}
.check-box {
    font-size: 13.5px;
    font-weight: 600;
    color: #0f172a;
}
.check-price {
    margin-left: auto;
    font-size: 13px;
    color: #4f46e5;
    font-weight: 700;
}
.product-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.quantity-selector {
    display: flex;
    align-items: center;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    overflow: hidden;
    background: #ffffff;
}
.qty-btn {
    width: 40px;
    height: 48px;
    border: none;
    background: #f8fafc;
    font-size: 17px;
    cursor: pointer;
    color: #0f172a;
}
.qty-btn:hover {
    background: #e2e8f0;
}
.quantity-selector input {
    width: 50px;
    height: 48px;
    border: none;
    text-align: center;
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
}
.btn-add-to-cart,
.btn-buy-now {
    flex: 1;
    min-width: 145px;
    height: 48px;
    border: none;
    border-radius: 10px;
    font-size: 14.5px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.15s ease;
    font-family: inherit;
}
.btn-add-to-cart {
    background: #0f172a;
    color: #ffffff;
}
.btn-add-to-cart:hover {
    background: #1e293b;
}
.btn-buy-now {
    background: #4f46e5;
    color: #ffffff;
}
.btn-buy-now:hover {
    background: #4338ca;
}
.trust-badges {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
}
.trust-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
}
.trust-item i {
    font-size: 18px;
    color: #4f46e5;
}
.product-tabs {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 28px;
}
.tab-buttons {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 12px;
}
.tab-btn {
    padding: 8px 18px;
    border: none;
    background: transparent;
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    border-radius: 8px;
    font-family: inherit;
}
.tab-btn.active,
.tab-btn:hover {
    color: #4f46e5;
    background: rgba(79, 70, 229, 0.08);
}
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}
.product-description {
    font-size: 15px;
    line-height: 1.75;
    color: #334155;
}
.details-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
.details-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
}
.details-table td:first-child {
    font-weight: 600;
    color: #0f172a;
    width: 200px;
}
.details-table td:last-child {
    color: #475569;
}
</style>

<script>
let productData = <?= json_encode($p) ?>;
let currentQty = 1;

function changeQty(delta) {
    currentQty = Math.max(1, Math.min(10, currentQty + delta));
    document.getElementById('quantity').value = currentQty;
}

function updatePriceDisplay() {
    let extraPrice = 0;
    document.querySelectorAll('.sf-var-control').forEach(ctrl => {
        if (ctrl.tagName === 'SELECT') {
            const opt = ctrl.options[ctrl.selectedIndex];
            extraPrice += parseFloat(opt.dataset.price || 0);
        } else if (ctrl.type === 'checkbox' && ctrl.checked) {
            extraPrice += parseFloat(ctrl.dataset.price || 0);
        }
    });

    const basePrice = parseFloat(productData.price || 0);
    const totalNet = basePrice + extraPrice;
    const totalIncl = totalNet * (1 + (parseFloat(productData.tax_rate || 20) / 100));
    
    document.getElementById('priceDisplay').innerHTML = totalIncl.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' <small>' + (productData.currency || '<?= htmlspecialchars($cur) ?>') + '</small>';
}

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab).classList.add('active');
    });
});

document.querySelectorAll('.sf-var-control').forEach(ctrl => {
    ctrl.addEventListener('change', updatePriceDisplay);
});

function getSelectedVariations() {
    const options = [];
    let extraPrice = 0;
    
    document.querySelectorAll('.sf-var-control').forEach(ctrl => {
        if (ctrl.tagName === 'SELECT') {
            const opt = ctrl.options[ctrl.selectedIndex];
            options.push({ group: ctrl.dataset.group, name: opt.value, price: parseFloat(opt.dataset.price || 0) });
            extraPrice += parseFloat(opt.dataset.price || 0);
        } else if (ctrl.type === 'checkbox' && ctrl.checked) {
            options.push({ group: ctrl.dataset.group, name: ctrl.value, price: parseFloat(ctrl.dataset.price || 0) });
            extraPrice += parseFloat(ctrl.dataset.price || 0);
        }
    });
    
    return { options, extraPrice };
}

function buildCartItem() {
    const { options, extraPrice } = getSelectedVariations();
    return {
        id: productData.id,
        title: productData.title,
        slug: productData.slug || productData.id,
        price: parseFloat(productData.price || 0) + extraPrice,
        tax_rate: parseFloat(productData.tax_rate || 20),
        currency: productData.currency || '<?= htmlspecialchars($cur) ?>',
        selected_options: options,
        quantity: currentQty,
        image: productData.image
    };
}

function addToCartProduct() {
    const item = buildCartItem();
    let cart = JSON.parse(localStorage.getItem('purify_cart') || '[]');
    for (let i = 0; i < currentQty; i++) {
        cart.push(item);
    }
    localStorage.setItem('purify_cart', JSON.stringify(cart));
    if (typeof updateNavCart === 'function') updateNavCart();
    
    const btn = document.querySelector('.btn-add-to-cart');
    btn.innerHTML = '<i class="las la-check"></i> <span>Added to Cart</span>';
    btn.style.background = '#10b981';
    setTimeout(() => {
        btn.innerHTML = '<i class="las la-shopping-cart"></i> <span>Add to Cart</span>';
        btn.style.background = '';
    }, 1800);
}

function buyNowProduct() {
    const item = buildCartItem();
    const items = [];
    for (let i = 0; i < currentQty; i++) {
        items.push(item);
    }
    localStorage.setItem('purify_cart', JSON.stringify(items));
    window.location.href = '<?= htmlspecialchars($base_site_url) ?>/checkout';
}
</script>
