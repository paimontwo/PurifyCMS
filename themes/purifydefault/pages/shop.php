<?php
$title = "Premium Shop - Dijital Varlıklar";
$desc = "Modern işletmeler için yüksek performanslı dijital ürünler ve hizmetler.";

require_once __DIR__ . '/../../../inc/PurifyTrade.php';
$products_file = __DIR__ . '/../../../inc/data/ecommerce/products.json';
$products = file_exists($products_file) ? json_decode(file_get_contents($products_file), true) : [];
?>

<!-- Products Grid with Filter -->
<section class="purifydefaultshop_section">
    <!-- purify:zone:shop_start -->
    <div class="purifydefaultshop_container">
        <!-- FILTER BAR -->
        <div class="purifydefaultshop_filter_bar">
            <div class="purifydefaultshop_filter_header">
                <i class="las la-sliders-h"></i>
                <span>Filtrele ve Sırala</span>
            </div>
            <div class="purifydefaultshop_filter_controls">
                <div class="purifydefaultshop_filter_group" style="flex: 2; min-width: 250px;">
                    <label><i class="las la-search"></i> Ürünlerde Ara</label>
                    <input type="text" id="purify_search_query" class="purifydefaultshop_filter_input" placeholder="İsim veya açıklamada ara...">
                </div>
                <div class="purifydefaultshop_filter_group">
                    <label><i class="las la-lira-sign"></i> Min Fiyat</label>
                    <input type="number" id="purify_min_price" class="purifydefaultshop_filter_input" placeholder="0" step="0.01">
                </div>
                <div class="purifydefaultshop_filter_group">
                    <label><i class="las la-lira-sign"></i> Max Fiyat</label>
                    <input type="number" id="purify_max_price" class="purifydefaultshop_filter_input" placeholder="Herhangi" step="0.01">
                </div>
                <div class="purifydefaultshop_filter_group">
                    <label><i class="las la-sort-amount-down"></i> Sırala</label>
                    <select id="purify_sort_by" class="purifydefaultshop_filter_select">
                        <option value="default">Varsayılan</option>
                        <option value="price_asc">Fiyata Göre Artan</option>
                        <option value="price_desc">Fiyata Göre Azalan</option>
                    </select>
                </div>
                <button id="purify_reset_filters" class="purifydefaultshop_filter_reset">
                    <i class="las la-undo-alt"></i> Sıfırla
                </button>
            </div>
            <div class="purifydefaultshop_filter_stats" id="purify_filter_stats"></div>
        </div>

        <!-- Product Grid -->
        <div class="purifydefaultshop_grid" id="purifydefaultshop_productGrid">
            <?php foreach ($products as $p): ?>
                <?php 
                $price_incl = $p['price'] * (1 + ($p['tax_rate'] ?? 20) / 100);
                $hasDiscount = !empty($p['old_price']) && $p['old_price'] > $p['price'];
                $discountPercent = $hasDiscount ? round((($p['old_price'] - $p['price']) / $p['old_price']) * 100) : 0;
                ?>
                <article class="purifydefaultshop_card" data-product='<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>'>
                    <div class="purifydefaultshop_card_image_wrap">
                        <?php if (!empty($p['image'])): ?>
                            <img src="<?= $cfg_mainurl ?>/inc/data/ecommerce/vault/<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="purifydefaultshop_card_image">
                        <?php else: ?>
                            <div class="purifydefaultshop_card_placeholder">
                                <i class="las la-image"></i>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($hasDiscount): ?>
                            <span class="purifydefaultshop_badge_discount">-%<?= $discountPercent ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($p['featured'])): ?>
                            <span class="purifydefaultshop_badge_featured"><i class="las la-crown"></i> Öne Çıkan</span>
                        <?php endif; ?>
                        
                        <div class="purifydefaultshop_card_overlay">
                            <button class="purifydefaultshop_btn_quickview" onclick="purifydefaultshop_quickView('<?= $p['slug'] ?? $p['id'] ?>')">
                                <i class="las la-eye"></i>
                            </button>
                            <button class="purifydefaultshop_btn_addcart" onclick="purifydefaultshop_addToCart(this)">
                                <i class="las la-shopping-cart"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="purifydefaultshop_card_info">
                        <h3 class="purifydefaultshop_card_title"><?= htmlspecialchars($p['title']) ?></h3>
                        <div class="purifydefaultshop_card_rating">
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
                        <p class="purifydefaultshop_card_desc"><?= htmlspecialchars(substr(strip_tags($p['description'] ?? ''), 0, 60)) ?>...</p>
                        
                        <div class="purifydefaultshop_card_footer">
                            <div class="purifydefaultshop_price_wrap">
                                <span class="purifydefaultshop_price_current"><?= number_format($price_incl, 2) ?> <small><?= $p['currency'] ?? 'TRY' ?></small></span>
                                <?php if ($hasDiscount): ?>
                                    <span class="purifydefaultshop_price_old"><?= number_format($p['old_price'] * (1 + ($p['tax_rate'] ?? 20) / 100), 2) ?> <?= $p['currency'] ?? 'TRY' ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <a href="<?= $cfg_mainurl ?>/product/<?= $p['slug'] ?? $p['id'] ?>" class="purifydefaultshop_btn_detail">
                                <i class="las la-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="purifydefaultshop_empty">
                <i class="las la-store"></i>
                <h3>Ürün Bulunamadı</h3>
                <p>Kısa süre sonra tekrar kontrol edin.</p>
            </div>
        <?php endif; ?>
    </div>
    <!-- purify:zone:shop_end -->
</section>

<!-- Floating Cart Button -->
<div class="purifydefaultshop_floating_cart" id="purifydefaultshop_floatingCart" onclick="location.href='<?= $cfg_mainurl ?>/checkout'" style="display: none;">
    <i class="las la-shopping-bag"></i>
    <span id="purifydefaultshop_floatingCount">0</span>
</div>

<style>
.purifydefaultshop_section * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.purifydefaultshop_section {
    font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    background: #f9fafb;
    padding: 60px 0 100px;
}

.purifydefaultshop_container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
}

.purifydefaultshop_filter_bar {
    background: #ffffff;
    border-radius: 32px;
    padding: 24px 28px;
    margin-bottom: 48px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.04);
    border: 1px solid #f0f2f5;
}

.purifydefaultshop_filter_header {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f3f4f6;
}

.purifydefaultshop_filter_header i {
    font-size: 24px;
    color: #4f46e5;
}

.purifydefaultshop_filter_controls {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: flex-end;
}

.purifydefaultshop_filter_group {
    flex: 1;
    min-width: 150px;
}

.purifydefaultshop_filter_group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 8px;
    letter-spacing: 0.3px;
}

.purifydefaultshop_filter_group label i {
    font-size: 14px;
}

.purifydefaultshop_filter_input,
.purifydefaultshop_filter_select {
    width: 100%;
    padding: 12px 16px;
    border-radius: 20px;
    border: 1.5px solid #e5e7eb;
    background: #ffffff;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    outline: none;
}

.purifydefaultshop_filter_input:focus,
.purifydefaultshop_filter_select:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
}

.purifydefaultshop_filter_reset {
    background: #f3f4f6;
    border: none;
    padding: 12px 28px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 13px;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s;
    height: 48px;
}

.purifydefaultshop_filter_reset:hover {
    background: #e5e7eb;
    transform: translateY(-2px);
}

.purifydefaultshop_filter_stats {
    margin-top: 18px;
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
    text-align: right;
    border-top: 1px solid #f3f4f6;
    padding-top: 16px;
}

.purifydefaultshop_grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 32px;
}

.purifydefaultshop_card {
    background: #ffffff;
    border-radius: 28px;
    overflow: hidden;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.02);
    transition: all 0.3s cubic-bezier(0.2, 0, 0, 1);
    position: relative;
}

.purifydefaultshop_card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 35px -12px rgba(0,0,0,0.15);
}

.purifydefaultshop_card_image_wrap {
    position: relative;
    height: 260px;
    overflow: hidden;
    background: #f9fafb;
}

.purifydefaultshop_card_image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    background: #f8fafc;
    transition: transform 0.5s ease;
}

.purifydefaultshop_card:hover .purifydefaultshop_card_image {
    transform: scale(1.05);
}

.purifydefaultshop_card_placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 64px;
    color: #d1d5db;
    background: #f3f4f6;
}

.purifydefaultshop_badge_discount,
.purifydefaultshop_badge_featured {
    position: absolute;
    top: 16px;
    left: 16px;
    padding: 6px 14px;
    border-radius: 40px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.5px;
    backdrop-filter: blur(4px);
    z-index: 2;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.purifydefaultshop_badge_discount {
    background: #ef4444;
    color: white;
}

.purifydefaultshop_badge_featured {
    background: #f59e0b;
    color: white;
    left: auto;
    right: 16px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.purifydefaultshop_card_overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.4);
    backdrop-filter: blur(3px);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    opacity: 0;
    transition: opacity 0.25s;
}

.purifydefaultshop_card:hover .purifydefaultshop_card_overlay {
    opacity: 1;
}

.purifydefaultshop_btn_quickview,
.purifydefaultshop_btn_addcart {
    width: 52px;
    height: 52px;
    border-radius: 60px;
    border: none;
    background: white;
    color: #111827;
    font-size: 22px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.purifydefaultshop_btn_quickview:hover,
.purifydefaultshop_btn_addcart:hover {
    background: #4f46e5;
    color: white;
    transform: scale(1.1);
}

.purifydefaultshop_card_info {
    padding: 24px;
}

.purifydefaultshop_card_title {
    font-size: 18px;
    font-weight: 800;
    color: #111827;
    margin-bottom: 8px;
    line-height: 1.3;
}

.purifydefaultshop_card_desc {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 20px;
    line-height: 1.5;
}

.purifydefaultshop_card_rating {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #fbbf24;
    font-size: 13px;
    margin-bottom: 12px;
}

.purifydefaultshop_card_rating span {
    color: #9ca3af;
    font-size: 12px;
    margin-left: 5px;
}

.purifydefaultshop_card_footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 16px;
    border-top: 1.5px solid #f3f4f6;
}

.purifydefaultshop_price_wrap {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.purifydefaultshop_price_current {
    font-size: 24px;
    font-weight: 900;
    color: #4f46e5;
    letter-spacing: -0.3px;
}

.purifydefaultshop_price_current small {
    font-size: 12px;
    font-weight: 600;
    opacity: 0.7;
}

.purifydefaultshop_price_old {
    font-size: 13px;
    color: #9ca3af;
    text-decoration: line-through;
}

.purifydefaultshop_btn_detail {
    width: 46px;
    height: 46px;
    border-radius: 16px;
    background: #4f46e5;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s;
}

.purifydefaultshop_btn_detail:hover {
    background: #4338ca;
    transform: translateX(4px);
}

.purifydefaultshop_floating_cart {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    cursor: pointer;
    box-shadow: 0 12px 25px rgba(79,70,229,0.4);
    z-index: 1000;
    transition: all 0.25s;
}

.purifydefaultshop_floating_cart:hover {
    transform: scale(1.08);
    box-shadow: 0 18px 30px rgba(79,70,229,0.5);
}

.purifydefaultshop_floating_cart span {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ef4444;
    color: white;
    font-size: 12px;
    font-weight: 800;
    min-width: 24px;
    height: 24px;
    border-radius: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
    border: 2px solid white;
}

/* EMPTY STATE */
.purifydefaultshop_empty {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 32px;
    margin-top: 40px;
}

.purifydefaultshop_empty i {
    font-size: 80px;
    color: #d1d5db;
    margin-bottom: 20px;
}

.purifydefaultshop_empty h3 {
    font-size: 26px;
    font-weight: 800;
    color: #111827;
    margin-bottom: 8px;
}

.purifydefaultshop_empty p {
    color: #6b7280;
}

/* MOBILE */
@media (max-width: 768px) {
    .purifydefaultshop_container {
        padding: 0 16px;
    }
    .purifydefaultshop_grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    .purifydefaultshop_filter_controls {
        flex-direction: column;
        gap: 16px;
    }
    .purifydefaultshop_filter_group {
        width: 100%;
    }
    .purifydefaultshop_filter_reset {
        width: 100%;
        justify-content: center;
    }
    .purifydefaultshop_floating_cart {
        bottom: 20px;
        right: 20px;
        width: 54px;
        height: 54px;
        font-size: 24px;
    }
    .purifydefaultshop_section {
        padding: 40px 0 80px;
    }
}
</style>

<script>
// Cart management
let purifydefaultshop_cart = JSON.parse(localStorage.getItem('purify_cart') || '[]');

function purifydefaultshop_updateUI() {
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
    purifydefaultshop_cart.push(productData);
    localStorage.setItem('purify_cart', JSON.stringify(purifydefaultshop_cart));
    purifydefaultshop_updateUI();
    
    const originalIcon = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="las la-check"></i>';
    btnElement.style.background = '#10b981';
    setTimeout(() => {
        btnElement.innerHTML = originalIcon;
        btnElement.style.background = '';
    }, 1500);
}

function purifydefaultshop_quickView(productId) {
    window.location.href = '<?= $cfg_mainurl ?>/product/' + productId;
}

// Filter & Sort Logic
function purifydefaultshop_getProductPrice(productObj) {
    const taxRate = (productObj.tax_rate !== undefined) ? productObj.tax_rate : 20;
    return productObj.price * (1 + taxRate / 100);
}

function purifydefaultshop_filterAndSort() {
    const grid = document.getElementById('purifydefaultshop_productGrid');
    if (!grid) return;
    const cards = Array.from(grid.querySelectorAll('.purifydefaultshop_card'));
    if (cards.length === 0) return;
    
    const searchQuery = document.getElementById('purify_search_query')?.value.toLowerCase() || '';
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
        
        // Search Filter
        if (searchQuery && !title.includes(searchQuery) && !desc.includes(searchQuery)) {
            visible = false;
        }
        
        // Price Filters
        if (visible) {
            if (!isNaN(minVal) && price < minVal) visible = false;
            if (!isNaN(maxVal) && price > maxVal) visible = false;
        }
        
        card.style.display = visible ? '' : 'none';
        if (visible) visibleCount++;
    });
    
    const statsSpan = document.getElementById('purify_filter_stats');
    if (statsSpan) {
        statsSpan.innerHTML = `<i class="las la-chart-line"></i> ${visibleCount} ürün gösteriliyor (toplam ${cards.length})`;
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
    }, 300);
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
        
        // URL Param Search (Deep Linking)
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