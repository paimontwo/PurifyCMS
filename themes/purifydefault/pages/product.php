<?php
if (!isset($p)) {
    require_once __DIR__ . '/../../../inc/PurifyTrade.php';
    $p_file = __DIR__ . '/../../../inc/data/ecommerce/products.json';
    $all_p = file_exists($p_file) ? json_decode(file_get_contents($p_file), true) : [];
    $p = $all_p[$_GET['id'] ?? ''] ?? null;
}

if (!$p) {
    include '404.php';
    exit;
}

$title = $p['title'];
$desc = strip_tags(substr($p['description'], 0, 160));

$price_incl = $p['price'] * (1 + ($p['tax_rate'] ?? 20) / 100);
$hasDiscount = !empty($p['old_price']) && $p['old_price'] > $p['price'];
?>

<!-- Breadcrumb -->
<div class="breadcrumb-wrap">
    <div class="container">
        <nav class="breadcrumb">
            <a href="<?= $cfg_mainurl ?>">Home</a>
            <i class="las la-angle-right"></i>
            <a href="<?= $cfg_mainurl ?>/shop">Shop</a>
            <i class="las la-angle-right"></i>
            <span><?= $p['title'] ?></span>
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
                        <img src="<?= rtrim($cfg_mainurl, '/') ?>/inc/data/ecommerce/vault/<?= $p['image'] ?>" alt="<?= $p['title'] ?>" id="mainImage">
                    <?php else: ?>
                        <div class="product-image-placeholder">
                            <i class="las la-image"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info Column -->
            <div class="product-info-panel">
                <div class="product-header">
                    <?php if (!empty($p['featured'])): ?>
                        <span class="product-tag featured"><i class="las la-crown"></i> Öne Çıkan</span>
                    <?php endif; ?>
                    <?php if ($hasDiscount): ?>
                        <span class="product-tag sale">İndirim</span>
                    <?php endif; ?>
                    
                    <h1 class="product-name"><?= $p['title'] ?></h1>
                    
                    <div class="product-meta">
                        <div class="product-rating">
                            <?php 
                            $rating = $p['rating'] ?? 5.0; 
                            $fullStars = floor($rating);
                            $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
                            $emptyStars = 5 - ($fullStars + $halfStar);
                            for($i=0; $i<$fullStars; $i++) echo '<i class="las la-star"></i>';
                            if($halfStar) echo '<i class="las la-star-half-alt"></i>';
                            for($i=0; $i<$emptyStars; $i++) echo '<i class="lar la-star"></i>';
                            ?>
                            <span>(<?= number_format($rating, 1) ?>)</span>
                        </div>
                        <span class="product-stock"><i class="las la-check-circle"></i> Stokta</span>
                    </div>
                </div>

                <div class="product-price-block">
                    <span class="current-price" id="priceDisplay">
                        <?= number_format($price_incl, 2) ?> <small><?= $p['currency'] ?? 'TRY' ?></small>
                    </span>
                    <?php if ($hasDiscount): ?>
                        <span class="old-price"><?= number_format($p['old_price'] * (1 + ($p['tax_rate'] ?? 20) / 100), 2) ?></span>
                        <span class="discount-badge">-<?= round((($p['old_price'] - $p['price']) / $p['old_price']) * 100) ?>%</span>
                    <?php endif; ?>
                </div>

                <!-- Variations -->
                <?php if (!empty($p['variation_groups'])): ?>
                    <div class="product-variations" id="variationSection">
                        <?php foreach ($p['variation_groups'] as $vg): ?>
                            <div class="variation-group">
                                <label class="variation-label"><?= $vg['title'] ?></label>
                                <div class="variation-options">
                                    <?php if ($vg['type'] === 'single'): ?>
                                        <select class="variation-select sf-var-control" data-group="<?= $vg['title'] ?>">
                                            <?php foreach ($vg['options'] as $opt): ?>
                                                <option value="<?= $opt['name'] ?>" data-price="<?= $opt['price'] ?>">
                                                    <?= $opt['name'] ?> (+<?= number_format($opt['price'], 2) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <?php foreach ($vg['options'] as $opt): ?>
                                            <label class="variation-checkbox">
                                                <input type="checkbox" class="sf-var-control" data-group="<?= $vg['title'] ?>" value="<?= $opt['name'] ?>" data-price="<?= $opt['price'] ?>">
                                                <span class="check-box"><?= $opt['name'] ?></span>
                                                <span class="check-price">+<?= number_format($opt['price'], 2) ?></span>
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
                    
                    <button class="btn-add-to-cart" onclick="addToCartProduct()">
                        <i class="las la-shopping-cart"></i>
                        <span>Sepete Ekle</span>
                    </button>
                    
                    <button class="btn-buy-now" onclick="buyNowProduct()">
                        <i class="las la-bolt"></i>
                        <span>Hemen Al</span>
                    </button>
                </div>

                <!-- Trust Badges -->
                <div class="trust-badges">
                    <div class="trust-item">
                        <i class="las la-shield-alt"></i>
                        <span>Güvenli Ödeme</span>
                    </div>
                    <div class="trust-item">
                        <i class="las la-truck"></i>
                        <span>Hızlı Teslimat</span>
                    </div>
                    <div class="trust-item">
                        <i class="las la-undo"></i>
                        <span>14-Gün İade</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- purify:zone:product_details_end -->

        <!-- Description Tabs -->
        <!-- purify:zone:product_tabs_start -->
        <div class="product-tabs">
            <div class="tab-buttons">
                <button class="tab-btn active" data-tab="desc">Açıklama</button>
                <button class="tab-btn" data-tab="details">Detaylar</button>
            </div>
            
            <div class="tab-content active" id="desc">
                <div class="product-description">
                    <?= $p['description'] ?>
                </div>
            </div>
            
            <div class="tab-content" id="details">
                <table class="details-table">
                    <tr>
                        <td>Ürün ID</td>
                        <td><?= $p['id'] ?></td>
                    </tr>
                    <tr>
                        <td>Vergi Oranı</td>
                        <td><?= $p['tax_rate'] ?? 20 ?>%</td>
                    </tr>
                    <tr>
                        <td>Para Birimi</td>
                        <td><?= $p['currency'] ?? 'TRY' ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <!-- purify:zone:product_tabs_end -->
    </div>
</section>

<style>
.breadcrumb-wrap {
    background: #f8fafc;
    padding: 16px 0;
    border-bottom: 1px solid #f1f5f9;
}
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #64748b;
}
.breadcrumb a {
    color: #64748b;
    text-decoration: none;
    transition: color 0.3s;
}
.breadcrumb a:hover {
    color: var(--prfy-primary);
}
.breadcrumb i {
    font-size: 12px;
    color: #cbd5e1;
}
.breadcrumb span {
    color: var(--prfy-slate-900);
    font-weight: 600;
}

.product-detail-section {
    padding: 40px 0 80px;
    background: #fff;
}
.product-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    margin-bottom: 60px;
}
@media (max-width: 900px) {
    .product-detail-grid { grid-template-columns: 1fr; gap: 40px; }
}

.product-gallery {
    top: 100px;
    height: fit-content;
}
.product-main-image {
    background: #f8fafc;
    border-radius: 24px;
    overflow: hidden;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
.product-main-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    background: #f8fafc;
}
.product-image-placeholder {
    font-size: 100px;
    color: #cbd5e1;
}

.product-info-panel {
    display: flex;
    flex-direction: column;
    gap: 24px;
}
.product-header {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.product-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    width: fit-content;
}
.product-tag.featured {
    background: #fef3c7;
    color: #d97706;
}
.product-tag.sale {
    background: #fee2e2;
    color: #dc2626;
}
.product-name {
    font-size: 36px;
    font-weight: 800;
    color: var(--prfy-slate-900);
    line-height: 1.2;
    margin: 0;
}
.product-meta {
    display: flex;
    align-items: center;
    gap: 20px;
}
.product-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #fbbf24;
}
.product-rating span {
    color: #64748b;
    font-size: 14px;
    margin-left: 4px;
}
.product-stock {
    color: #10b981;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.product-price-block {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    padding: 20px 0;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
}
.current-price {
    font-size: 42px;
    font-weight: 900;
    color: var(--prfy-primary);
}
.current-price small {
    font-size: 20px;
    opacity: 0.7;
}
.old-price {
    font-size: 20px;
    color: #94a3b8;
    text-decoration: line-through;
}
.discount-badge {
    background: #ef4444;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
}

.product-variations {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.variation-label {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: var(--prfy-slate-900);
    margin-bottom: 10px;
}
.variation-select {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    background: white;
    cursor: pointer;
    transition: border-color 0.3s;
}
.variation-select:focus {
    border-color: var(--prfy-primary);
    outline: none;
}
.variation-checkbox {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: #f8fafc;
    border: 2px solid transparent;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
}
.variation-checkbox:hover {
    border-color: var(--prfy-primary);
    background: white;
}
.variation-checkbox input {
    display: none;
}
.variation-checkbox input:checked + .check-box {
    background: var(--prfy-primary);
    color: white;
    border-color: var(--prfy-primary);
}
.check-box {
    padding: 6px 14px;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s;
}
.check-price {
    margin-left: auto;
    font-size: 13px;
    color: var(--prfy-primary);
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
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}
.qty-btn {
    width: 44px;
    height: 52px;
    border: none;
    background: white;
    font-size: 18px;
    cursor: pointer;
    transition: background 0.3s;
}
.qty-btn:hover {
    background: #f1f5f9;
}
.quantity-selector input {
    width: 60px;
    height: 52px;
    border: none;
    text-align: center;
    font-size: 16px;
    font-weight: 700;
}
.btn-add-to-cart, .btn-buy-now {
    flex: 1;
    min-width: 160px;
    height: 56px;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s;
}
.btn-add-to-cart {
    background: var(--prfy-slate-900);
    color: white;
}
.btn-add-to-cart:hover {
    background: #1e293b;
    transform: translateY(-2px);
}
.btn-buy-now {
    background: var(--prfy-primary);
    color: white;
}
.btn-buy-now:hover {
    background: #4f46e5;
    transform: translateY(-2px);
}

.trust-badges {
    display: flex;
    gap: 30px;
    padding-top: 20px;
    border-top: 1px solid #f1f5f9;
}
.trust-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: #64748b;
}
.trust-item i {
    font-size: 24px;
    color: var(--prfy-primary);
}

.product-tabs {
    background: #f8fafc;
    border-radius: 24px;
    padding: 30px;
}
.tab-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 24px;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 16px;
}
.tab-btn {
    padding: 12px 24px;
    border: none;
    background: transparent;
    font-size: 15px;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.3s;
}
.tab-btn:hover {
    color: var(--prfy-primary);
    background: rgba(99, 102, 241, 0.1);
}
.tab-btn.active {
    color: var(--prfy-primary);
    background: rgba(99, 102, 241, 0.1);
}
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}
.product-description {
    font-size: 16px;
    line-height: 1.8;
    color: #475569;
}
.details-table {
    width: 100%;
    border-collapse: collapse;
}
.details-table td {
    padding: 16px;
    border-bottom: 1px solid #e2e8f0;
}
.details-table td:first-child {
    font-weight: 700;
    color: var(--prfy-slate-900);
    width: 200px;
}
.details-table td:last-child {
    color: #64748b;
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

    const basePrice = parseFloat(productData.price);
    const totalNet = basePrice + extraPrice;
    const totalIncl = totalNet * (1 + (parseFloat(productData.tax_rate || 20) / 100));
    
    document.getElementById('priceDisplay').innerHTML = totalIncl.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' <small>' + (productData.currency || 'TRY') + '</small>';
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
        price: parseFloat(productData.price) + extraPrice,
        tax_rate: parseFloat(productData.tax_rate || 20),
        currency: productData.currency || 'TRY',
        selected_options: options,
        quantity: currentQty,
        image: productData.image
    };
}

function addToCartProduct() {
    const item = buildCartItem();
    let cart = JSON.parse(localStorage.getItem('purify_cart') || '[]');
    
    // quantity times
    for (let i = 0; i < currentQty; i++) {
        cart.push(item);
    }
    
    localStorage.setItem('purify_cart', JSON.stringify(cart));
    
    // navbar cart
    if (typeof updateNavCart === 'function') updateNavCart();
    
    // success
    const btn = document.querySelector('.btn-add-to-cart');
    btn.innerHTML = '<i class="las la-check"></i> <span>Added!</span>';
    btn.style.background = '#10b981';
    setTimeout(() => {
        btn.innerHTML = '<i class="las la-shopping-cart"></i> <span>Add to Cart</span>';
        btn.style.background = '';
    }, 2000);
}

function buyNowProduct() {
    const item = buildCartItem();
    localStorage.setItem('purify_cart', JSON.stringify([item]));
    window.location.href = '<?= $cfg_mainurl ?>/checkout';
}
</script>
