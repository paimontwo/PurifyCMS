<?php
if (empty($cfg_enable_ecommerce)) {
    header('HTTP/1.1 404 Not Found');
    require __DIR__ . '/404.php';
    return;
}

$title = "Store Catalog";
$desc = "Browse our catalog of digital products, licenses, and professional services.";

require_once __DIR__ . '/../../../inc/PurifyTrade.php';
$products_file = __DIR__ . '/../../../inc/data/ecommerce/products.json';
$products = file_exists($products_file) ? (json_decode(file_get_contents($products_file), true) ?: []) : [];
$base_site_url = rtrim($cfg_mainurl ?? '', '/');
$default_currency = $cfg_ecommerce_currency ?? 'TRY';
?>

<section class="purifydefaultshop_section">
    <!-- purify:zone:shop_start -->
    <div class="purifydefaultshop_container">
        <div class="purifydefaultshop_page_header">
            <div>
                <span class="section-badge"><i class="las la-shopping-bag"></i> Store</span>
                <h1>Products</h1>
                <p>Browse our available products and digital downloads.</p>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="purifydefaultshop_filter_bar">
            <div class="purifydefaultshop_filter_controls">
                <div class="purifydefaultshop_filter_group" style="flex: 2; min-width: 240px;">
                    <label><i class="las la-search"></i> Search Products</label>
                    <input type="text" id="purify_search_query" class="purifydefaultshop_filter_input" placeholder="Search by product name or description...">
                </div>
                <div class="purifydefaultshop_filter_group">
                    <label><i class="las la-tag"></i> Min Price</label>
                    <input type="number" id="purify_min_price" class="purifydefaultshop_filter_input" placeholder="0" step="0.01">
                </div>
                <div class="purifydefaultshop_filter_group">
                    <label><i class="las la-tag"></i> Max Price</label>
                    <input type="number" id="purify_max_price" class="purifydefaultshop_filter_input" placeholder="Any" step="0.01">
                </div>
                <div class="purifydefaultshop_filter_group">
                    <label><i class="las la-sort-amount-down"></i> Sort By</label>
                    <select id="purify_sort_by" class="purifydefaultshop_filter_select">
                        <option value="default">Featured / Default</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                    </select>
                </div>
                <button id="purify_reset_filters" class="purifydefaultshop_filter_reset" type="button">
                    <i class="las la-undo-alt"></i> Reset
                </button>
            </div>
            <div class="purifydefaultshop_filter_stats" id="purify_filter_stats"></div>
        </div>

        <!-- Product Grid -->
        <div class="purifydefaultshop_grid" id="purifydefaultshop_productGrid">
            <?php foreach ($products as $p): ?>
                <?php 
                $price_incl = (float)($p['price'] ?? 0) * (1 + ((float)($p['tax_rate'] ?? 20) / 100));
                $hasDiscount = !empty($p['old_price']) && $p['old_price'] > $p['price'];
                $discountPercent = $hasDiscount ? round((($p['old_price'] - $p['price']) / $p['old_price']) * 100) : 0;
                $cur = $p['currency'] ?? $default_currency;
                $slug = $p['slug'] ?? $p['id'];
                ?>
                <article class="purifydefaultshop_card" data-product='<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>'>
                    <div class="purifydefaultshop_card_image_wrap">
                        <a href="<?= htmlspecialchars($base_site_url) ?>/product/<?= htmlspecialchars($slug) ?>" style="display:flex;width:100%;height:100%;align-items:center;justify-content:center;">
                            <?php if (!empty($p['image'])): ?>
                                <img src="<?= htmlspecialchars($base_site_url) ?>/inc/data/ecommerce/vault/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="purifydefaultshop_card_image">
                            <?php else: ?>
                                <div class="purifydefaultshop_card_placeholder">
                                    <i class="las la-cube"></i>
                                </div>
                            <?php endif; ?>
                        </a>
                        
                        <?php if ($hasDiscount): ?>
                            <span class="purifydefaultshop_badge_discount">-<?= $discountPercent ?>%</span>
                        <?php endif; ?>
                        
                        <?php if (!empty($p['featured'])): ?>
                            <span class="purifydefaultshop_badge_featured"><i class="las la-star"></i> Featured</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="purifydefaultshop_card_info">
                        <div class="purifydefaultshop_card_rating">
                            <?php 
                            $rating = (float)($p['rating'] ?? 5.0); 
                            $fullStars = floor($rating);
                            $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
                            $emptyStars = 5 - ($fullStars + $halfStar);
                            for($i=0; $i<$fullStars; $i++) echo '<i class="las la-star"></i>';
                            if($halfStar) echo '<i class="las la-star-half-alt"></i>';
                            for($i=0; $i<$emptyStars; $i++) echo '<i class="lar la-star"></i>';
                            ?>
                            <span>(<?= (int)($p['reviews_count'] ?? 24) ?>)</span>
                        </div>

                        <h3 class="purifydefaultshop_card_title">
                            <a href="<?= htmlspecialchars($base_site_url) ?>/product/<?= htmlspecialchars($slug) ?>"><?= htmlspecialchars($p['title']) ?></a>
                        </h3>
                        <p class="purifydefaultshop_card_desc"><?= htmlspecialchars(mb_substr(strip_tags($p['description'] ?? ''), 0, 85)) ?>...</p>
                        
                        <div class="purifydefaultshop_card_footer">
                            <div class="purifydefaultshop_price_wrap">
                                <span class="purifydefaultshop_price_current"><?= number_format($price_incl, 2) ?> <small><?= htmlspecialchars($cur) ?></small></span>
                                <?php if ($hasDiscount): ?>
                                    <span class="purifydefaultshop_price_old"><?= number_format($p['old_price'] * (1 + ($p['tax_rate'] ?? 20) / 100), 2) ?> <?= htmlspecialchars($cur) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <button type="button" class="purifydefaultshop_btn_cart" onclick="purifydefaultshop_addToCart(this)" title="Add to Cart">
                                    <i class="las la-shopping-cart"></i> Add
                                </button>
                                <a href="<?= htmlspecialchars($base_site_url) ?>/product/<?= htmlspecialchars($slug) ?>" class="purifydefaultshop_btn_detail" title="View Details">
                                    <i class="las la-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="purifydefaultshop_empty">
                <i class="las la-box-open"></i>
                <h3>No Products Found</h3>
                <p>Our catalog is currently being updated. Please check back shortly.</p>
            </div>
        <?php endif; ?>
    </div>
    <!-- purify:zone:shop_end -->
</section>

<!-- Floating Cart Button -->
<div class="purifydefaultshop_floating_cart" id="purifydefaultshop_floatingCart" onclick="location.href='<?= htmlspecialchars($base_site_url) ?>/checkout'" style="display: none;">
    <i class="las la-shopping-bag"></i>
    <span id="purifydefaultshop_floatingCount">0</span>
</div>

<style>
.purifydefaultshop_section {
    background: #f8fafc;
    padding: 56px 0 96px;
    min-height: 75vh;
}
.purifydefaultshop_container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
}
.purifydefaultshop_page_header {
    margin-bottom: 32px;
}
.purifydefaultshop_page_header h1 {
    font-size: clamp(28px, 4vw, 38px);
    color: #0f172a;
    margin-bottom: 8px;
}
.purifydefaultshop_page_header p {
    font-size: 16px;
    color: #64748b;
}
.purifydefaultshop_filter_bar {
    background: #ffffff;
    border-radius: 16px;
    padding: 22px 24px;
    margin-bottom: 36px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
}
.purifydefaultshop_filter_controls {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: flex-end;
}
.purifydefaultshop_filter_group {
    flex: 1;
    min-width: 150px;
}
.purifydefaultshop_filter_group label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
}
.purifydefaultshop_filter_group label i {
    color: #4f46e5;
}
.purifydefaultshop_filter_input,
.purifydefaultshop_filter_select {
    width: 100%;
    height: 42px;
    padding: 0 14px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    font-size: 13.5px;
    font-weight: 500;
    color: #0f172a;
    transition: all 0.15s ease;
    outline: none;
    font-family: inherit;
}
.purifydefaultshop_filter_input:focus,
.purifydefaultshop_filter_select:focus {
    background: #ffffff;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}
.purifydefaultshop_filter_reset {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 0 18px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
    color: #334155;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    height: 42px;
    font-family: inherit;
}
.purifydefaultshop_filter_reset:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.purifydefaultshop_filter_stats {
    margin-top: 14px;
    font-size: 12.5px;
    color: #64748b;
    font-weight: 500;
    text-align: right;
    border-top: 1px solid #f1f5f9;
    padding-top: 12px;
}
.purifydefaultshop_grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 24px;
}
.purifydefaultshop_card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
}
.purifydefaultshop_card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 28px -8px rgba(15, 23, 42, 0.08);
    border-color: #cbd5e1;
}
.purifydefaultshop_card_image_wrap {
    position: relative;
    height: 230px;
    overflow: hidden;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
    padding: 20px;
}
.purifydefaultshop_card_image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.3s ease;
}
.purifydefaultshop_card:hover .purifydefaultshop_card_image {
    transform: scale(1.04);
}
.purifydefaultshop_card_placeholder {
    font-size: 56px;
    color: #cbd5e1;
}
.purifydefaultshop_badge_discount,
.purifydefaultshop_badge_featured {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    z-index: 2;
}
.purifydefaultshop_badge_discount {
    background: #ef4444;
    color: #ffffff;
}
.purifydefaultshop_badge_featured {
    background: rgba(245, 158, 11, 0.15);
    color: #b45309;
    border: 1px solid rgba(245, 158, 11, 0.3);
    left: auto;
    right: 12px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.purifydefaultshop_card_info {
    padding: 20px;
    display: flex;
    flex-direction: column;
    flex: 1;
}
.purifydefaultshop_card_rating {
    display: flex;
    align-items: center;
    gap: 3px;
    color: #f59e0b;
    font-size: 13px;
    margin-bottom: 8px;
}
.purifydefaultshop_card_rating span {
    color: #94a3b8;
    font-size: 12px;
    margin-left: 4px;
}
.purifydefaultshop_card_title {
    font-size: 17px;
    font-weight: 700;
    margin-bottom: 6px;
    line-height: 1.35;
}
.purifydefaultshop_card_title a {
    color: #0f172a;
    text-decoration: none;
}
.purifydefaultshop_card_title a:hover {
    color: #4f46e5;
}
.purifydefaultshop_card_desc {
    font-size: 13.5px;
    color: #64748b;
    margin-bottom: 18px;
    line-height: 1.55;
}
.purifydefaultshop_card_footer {
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 14px;
    border-top: 1px solid #f1f5f9;
    gap: 10px;
}
.purifydefaultshop_price_wrap {
    display: flex;
    flex-direction: column;
}
.purifydefaultshop_price_current {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
}
.purifydefaultshop_price_current small {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
}
.purifydefaultshop_price_old {
    font-size: 12px;
    color: #94a3b8;
    text-decoration: line-through;
}
.purifydefaultshop_btn_cart {
    height: 38px;
    padding: 0 14px;
    border-radius: 10px;
    border: none;
    background: #4f46e5;
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
    font-family: inherit;
}
.purifydefaultshop_btn_cart:hover {
    background: #4338ca;
}
.purifydefaultshop_btn_detail {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.15s ease;
}
.purifydefaultshop_btn_detail:hover {
    border-color: #4f46e5;
    color: #4f46e5;
}
.purifydefaultshop_floating_cart {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 56px;
    height: 56px;
    background: #4f46e5;
    color: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 10px 25px rgba(79, 70, 229, 0.35);
    z-index: 1000;
    transition: all 0.2s ease;
}
.purifydefaultshop_floating_cart:hover {
    transform: translateY(-2px);
    background: #4338ca;
}
.purifydefaultshop_floating_cart span {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ef4444;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    min-width: 22px;
    height: 22px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
    border: 2px solid #ffffff;
}
.purifydefaultshop_empty {
    text-align: center;
    padding: 64px 24px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
}
.purifydefaultshop_empty i {
    font-size: 56px;
    color: #cbd5e1;
    margin-bottom: 12px;
}
.purifydefaultshop_empty h3 {
    font-size: 20px;
    margin-bottom: 6px;
}
.purifydefaultshop_empty p {
    color: #64748b;
    font-size: 14.5px;
}
@media (max-width: 768px) {
    .purifydefaultshop_filter_controls {
        flex-direction: column;
        align-items: stretch;
    }
    .purifydefaultshop_filter_reset {
        justify-content: center;
    }
}
</style>

<script>
let purifydefaultshop_cart = JSON.parse(localStorage.getItem('purify_cart') || '[]');

function purifydefaultshop_updateUI() {
    purifydefaultshop_cart = JSON.parse(localStorage.getItem('purify_cart') || '[]');
    const floatingCart = document.getElementById('purifydefaultshop_floatingCart');
    const floatingCount = document.getElementById('purifydefaultshop_floatingCount');
    if (floatingCart && floatingCount) {
        if (purifydefaultshop_cart.length > 0) {
            floatingCart.style.display = 'flex';
            floatingCount.innerText = purifydefaultshop_cart.length;
        } else {
            floatingCart.style.display = 'none';
        }
    }
    if (typeof updateNavCart === 'function') updateNavCart();
}

function purifydefaultshop_addToCart(btnElement) {
    const card = btnElement.closest('.purifydefaultshop_card');
    if (!card) return;
    const productData = JSON.parse(card.dataset.product);
    purifydefaultshop_cart = JSON.parse(localStorage.getItem('purify_cart') || '[]');
    purifydefaultshop_cart.push(productData);
    localStorage.setItem('purify_cart', JSON.stringify(purifydefaultshop_cart));
    purifydefaultshop_updateUI();
    
    const originalHtml = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="las la-check"></i> Added';
    btnElement.style.background = '#10b981';
    setTimeout(() => {
        btnElement.innerHTML = originalHtml;
        btnElement.style.background = '';
    }, 1400);
}

function purifydefaultshop_getProductPrice(productObj) {
    const taxRate = (productObj.tax_rate !== undefined) ? parseFloat(productObj.tax_rate) : 20;
    return parseFloat(productObj.price || 0) * (1 + taxRate / 100);
}

function purifydefaultshop_filterAndSort() {
    const grid = document.getElementById('purifydefaultshop_productGrid');
    if (!grid) return;
    const cards = Array.from(grid.querySelectorAll('.purifydefaultshop_card'));
    if (cards.length === 0) return;
    
    const searchQuery = (document.getElementById('purify_search_query')?.value || '').toLowerCase();
    const minVal = parseFloat(document.getElementById('purify_min_price')?.value);
    const maxVal = parseFloat(document.getElementById('purify_max_price')?.value);
    const sortBy = document.getElementById('purify_sort_by')?.value || 'default';
    
    let visibleCount = 0;
    const priceMap = new Map();
    
    cards.forEach(card => {
        const product = JSON.parse(card.dataset.product);
        const price = purifydefaultshop_getProductPrice(product);
        const title = (product.title || '').toLowerCase();
        const desc = (product.description || '').toLowerCase();
        
        priceMap.set(card, price);
        
        let visible = true;
        if (searchQuery && !title.includes(searchQuery) && !desc.includes(searchQuery)) {
            visible = false;
        }
        if (visible) {
            if (!isNaN(minVal) && price < minVal) visible = false;
            if (!isNaN(maxVal) && price > maxVal) visible = false;
        }
        
        card.style.display = visible ? '' : 'none';
        if (visible) visibleCount++;
    });
    
    const statsSpan = document.getElementById('purify_filter_stats');
    if (statsSpan) {
        statsSpan.innerHTML = `Showing <strong>${visibleCount}</strong> of <strong>${cards.length}</strong> products`;
    }
    
    if (sortBy !== 'default') {
        const sortedCards = [...cards];
        sortedCards.sort((a, b) => {
            const priceA = priceMap.get(a);
            const priceB = priceMap.get(b);
            return sortBy === 'price_asc' ? priceA - priceB : priceB - priceA;
        });
        sortedCards.forEach(card => grid.appendChild(card));
    }
}

let filterTimeout;
function purifydefaultshop_debouncedFilter() {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        purifydefaultshop_filterAndSort();
    }, 250);
}

function purifydefaultshop_resetFilters() {
    const searchInput = document.getElementById('purify_search_query');
    const minInput = document.getElementById('purify_min_price');
    const maxInput = document.getElementById('purify_max_price');
    const sortSelect = document.getElementById('purify_sort_by');
    if (searchInput) searchInput.value = '';
    if (minInput) minInput.value = '';
    if (maxInput) maxInput.value = '';
    if (sortSelect) sortSelect.value = 'default';
    purifydefaultshop_filterAndSort();
}

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('purify_search_query');
    const minInput = document.getElementById('purify_min_price');
    const maxInput = document.getElementById('purify_max_price');
    const sortSelect = document.getElementById('purify_sort_by');
    const resetBtn = document.getElementById('purify_reset_filters');
    
    if (searchInput) {
        searchInput.addEventListener('input', purifydefaultshop_debouncedFilter);
        const urlParams = new URLSearchParams(window.location.search);
        const qParam = urlParams.get('q') || urlParams.get('search') || urlParams.get('s');
        if (qParam) {
            searchInput.value = qParam;
        }
    }
    if (minInput) minInput.addEventListener('input', purifydefaultshop_debouncedFilter);
    if (maxInput) maxInput.addEventListener('input', purifydefaultshop_debouncedFilter);
    if (sortSelect) sortSelect.addEventListener('change', () => purifydefaultshop_filterAndSort());
    if (resetBtn) resetBtn.addEventListener('click', purifydefaultshop_resetFilters);
    
    purifydefaultshop_filterAndSort();
    purifydefaultshop_updateUI();
});
</script>
