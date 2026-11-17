<?php
/**
 * PurifyCMS Default Navigation Header
 */
$base_site_url = rtrim($cfg_mainurl ?? '', '/');
$cur_path = trim($request_path ?? '', '/');
$ecom_active = !empty($cfg_enable_ecommerce);
?>

<header class="purifydefaultnav_header" id="purifydefaultnav_mainHeader" data-theme="<?= htmlspecialchars($cfg_active_theme ?? 'purifydefault') ?>">
    <div class="purifydefaultnav_container">
        <a class="purifydefaultnav_brand" href="<?= htmlspecialchars($base_site_url ?: '/') ?>">
            <?php if (!empty($cfg_logolink)): ?>
                <img src="<?= htmlspecialchars($cfg_logolink) ?>" alt="<?= htmlspecialchars($cfg_name ?? 'PurifyCMS') ?>" class="purifydefaultnav_logo_img">
            <?php else: ?>
                <span class="purifydefaultnav_brand_mark"><i class="las la-cube"></i></span>
                <span class="purifydefaultnav_brand_text"><?= htmlspecialchars($cfg_name ?? 'PurifyCMS') ?></span>
            <?php endif; ?>
        </a>

        <button class="purifydefaultnav_toggler" id="purifydefaultnav_toggler" aria-label="Toggle navigation">
            <i class="las la-bars"></i>
        </button>

        <div class="purifydefaultnav_collapse" id="purifydefaultnav_navbarContent">
            <ul class="purifydefaultnav_navbar_nav">
                <li class="purifydefaultnav_nav_item">
                    <a class="purifydefaultnav_navlink <?= ($cur_path === '' || $cur_path === 'index') ? 'active' : '' ?>" href="<?= htmlspecialchars($base_site_url ?: '/') ?>">Home</a>
                </li>
                <li class="purifydefaultnav_nav_item">
                    <a class="purifydefaultnav_navlink <?= strpos($cur_path, 'about') === 0 ? 'active' : '' ?>" href="<?= htmlspecialchars($base_site_url) ?>/about">About</a>
                </li>
                <?php if ($ecom_active): ?>
                <li class="purifydefaultnav_nav_item">
                    <a class="purifydefaultnav_navlink <?= (strpos($cur_path, 'shop') === 0 || strpos($cur_path, 'product') === 0) ? 'active' : '' ?>" href="<?= htmlspecialchars($base_site_url) ?>/shop">Shop</a>
                </li>
                <li class="purifydefaultnav_nav_item">
                    <a class="purifydefaultnav_navlink <?= $cur_path === 'order-track' ? 'active' : '' ?>" href="<?= htmlspecialchars($base_site_url) ?>/order-track">Track Order</a>
                </li>
                <?php endif; ?>
                <li class="purifydefaultnav_nav_item">
                    <a class="purifydefaultnav_navlink <?= strpos($cur_path, 'contact') === 0 ? 'active' : '' ?>" href="<?= htmlspecialchars($base_site_url) ?>/contact">Contact</a>
                </li>
            </ul>

            <div class="purifydefaultnav_actions">
                <?php if ($ecom_active): ?>
                <div class="purifydefaultnav_cart_wrapper">
                    <button class="purifydefaultnav_cart_btn" id="purifydefaultnav_cartBtn" aria-label="Shopping Cart">
                        <div class="purifydefaultnav_cart_icon">
                            <i class="las la-shopping-bag"></i>
                        </div>
                        <span id="purifydefaultnav_cartCount" class="purifydefaultnav_cart_badge">0</span>
                    </button>
                    <div class="purifydefaultnav_cart_dropdown" id="purifydefaultnav_cartDropdown">
                        <div class="purifydefaultnav_cart_header">
                            <h6>Shopping Bag</h6>
                            <span id="purifydefaultnav_miniCartCount">0 items</span>
                        </div>
                        <div id="purifydefaultnav_miniCartItems" class="purifydefaultnav_cart_items">
                            <p class="purifydefaultnav_cart_empty">Your cart is currently empty.</p>
                        </div>
                        <div id="purifydefaultnav_miniCartFooter" class="purifydefaultnav_cart_footer" style="display: none;">
                            <div class="purifydefaultnav_cart_total">
                                <span>Total (Incl. Tax)</span>
                                <span id="purifydefaultnav_miniCartTotal">0.00 <?= htmlspecialchars($cfg_ecommerce_currency ?? 'TRY') ?></span>
                            </div>
                            <a href="<?= htmlspecialchars($base_site_url) ?>/checkout" class="purifydefaultnav_checkout_btn">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>
                <a href="<?= htmlspecialchars($base_site_url) ?>/shop" class="purifydefaultnav_cta_btn">
                    <span>Visit Store</span>
                    <i class="las la-arrow-right"></i>
                </a>
                <?php else: ?>
                <a href="<?= htmlspecialchars($base_site_url) ?>/contact" class="purifydefaultnav_cta_btn">
                    <span>Get in Touch</span>
                    <i class="las la-arrow-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<style>
.purifydefaultnav_header {
    position: sticky;
    top: 0;
    z-index: 1030;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid #e2e8f0;
    transition: all 0.2s ease;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

.purifydefaultnav_header.scrolled {
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.06);
}

.purifydefaultnav_container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
    min-height: 70px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
}

.purifydefaultnav_brand {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: #0f172a;
    font-size: 19px;
}

.purifydefaultnav_brand_mark {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    background: #4f46e5;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
}

.purifydefaultnav_logo_img {
    height: 34px;
    width: auto;
    max-width: 180px;
    object-fit: contain;
}

.purifydefaultnav_toggler {
    display: none;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    width: 40px;
    height: 40px;
    font-size: 20px;
    color: #0f172a;
    cursor: pointer;
    align-items: center;
    justify-content: center;
}

.purifydefaultnav_collapse {
    display: flex;
    align-items: center;
    gap: 24px;
}

.purifydefaultnav_navbar_nav {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 4px;
    align-items: center;
}

.purifydefaultnav_navlink {
    display: inline-flex;
    align-items: center;
    padding: 8px 14px;
    font-weight: 500;
    font-size: 14px;
    color: #475569;
    text-decoration: none;
    transition: all 0.15s ease;
    border-radius: 8px;
}

.purifydefaultnav_navlink:hover {
    color: #0f172a;
    background: #f1f5f9;
}

.purifydefaultnav_navlink.active {
    color: #4f46e5;
    background: rgba(79, 70, 229, 0.08);
    font-weight: 600;
}

.purifydefaultnav_actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.purifydefaultnav_cta_btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 16px;
    border-radius: 10px;
    background: #0f172a;
    color: #ffffff;
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.15s ease;
}

.purifydefaultnav_cta_btn:hover {
    background: #4f46e5;
    transform: translateY(-1px);
}

.purifydefaultnav_cart_wrapper {
    position: relative;
}

.purifydefaultnav_cart_btn {
    background: none;
    border: none;
    position: relative;
    cursor: pointer;
    padding: 0;
}

.purifydefaultnav_cart_icon {
    width: 40px;
    height: 40px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #0f172a;
    font-size: 19px;
    transition: all 0.15s ease;
}

.purifydefaultnav_cart_btn:hover .purifydefaultnav_cart_icon {
    border-color: #4f46e5;
    color: #4f46e5;
    background: rgba(79, 70, 229, 0.05);
}

.purifydefaultnav_cart_badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #4f46e5;
    color: #ffffff;
    font-size: 10px;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    border-radius: 999px;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
    border: 2px solid #ffffff;
}

.purifydefaultnav_cart_dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 340px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 20px 35px -8px rgba(15, 23, 42, 0.12);
    padding: 18px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-6px);
    transition: all 0.18s ease;
    z-index: 1050;
}

.purifydefaultnav_cart_wrapper.open .purifydefaultnav_cart_dropdown {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.purifydefaultnav_cart_header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 12px;
}

.purifydefaultnav_cart_header h6 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.purifydefaultnav_cart_header span {
    font-size: 12px;
    color: #64748b;
    font-weight: 500;
}

.purifydefaultnav_cart_items {
    max-height: 260px;
    overflow-y: auto;
}

.purifydefaultnav_cart_empty {
    text-align: center;
    padding: 24px 12px;
    color: #64748b;
    font-size: 13.5px;
}

.purifydefaultnav_cart_item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}

.purifydefaultnav_cart_item:last-child {
    border-bottom: none;
}

.purifydefaultnav_cart_item_img {
    width: 42px;
    height: 42px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #4f46e5;
    font-size: 18px;
}

.purifydefaultnav_cart_item_info {
    flex: 1;
    min-width: 0;
}

.purifydefaultnav_cart_item_title {
    font-weight: 600;
    font-size: 13.5px;
    margin: 0 0 2px;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.purifydefaultnav_cart_item_price {
    font-size: 12.5px;
    font-weight: 700;
    color: #4f46e5;
    margin: 0;
}

.purifydefaultnav_cart_item_remove {
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 4px;
    font-size: 16px;
    transition: color 0.15s;
}

.purifydefaultnav_cart_item_remove:hover {
    color: #ef4444;
}

.purifydefaultnav_cart_footer {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
}

.purifydefaultnav_cart_total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 12px;
}

.purifydefaultnav_checkout_btn {
    display: block;
    background: #4f46e5;
    color: #ffffff;
    text-align: center;
    padding: 11px 16px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 600;
    transition: background 0.15s;
}

.purifydefaultnav_checkout_btn:hover {
    background: #4338ca;
}

@media (max-width: 900px) {
    .purifydefaultnav_toggler {
        display: inline-flex;
    }
    .purifydefaultnav_collapse {
        display: none;
        width: 100%;
        flex-direction: column;
        align-items: stretch;
        padding: 12px 0 18px;
        border-top: 1px solid #f1f5f9;
        margin-top: 8px;
        gap: 12px;
    }
    .purifydefaultnav_collapse.open {
        display: flex;
    }
    .purifydefaultnav_navbar_nav {
        flex-direction: column;
        align-items: stretch;
        gap: 4px;
    }
    .purifydefaultnav_navlink {
        width: 100%;
    }
    .purifydefaultnav_actions {
        justify-content: space-between;
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
    }
}
</style>

<script>
(function() {
    const header = document.getElementById('purifydefaultnav_mainHeader');
    const toggler = document.getElementById('purifydefaultnav_toggler');
    const collapseDiv = document.getElementById('purifydefaultnav_navbarContent');
    const cartBtn = document.getElementById('purifydefaultnav_cartBtn');
    const cartWrapper = document.querySelector('.purifydefaultnav_cart_wrapper');
    const cartBadge = document.getElementById('purifydefaultnav_cartCount');
    const miniCartCountSpan = document.getElementById('purifydefaultnav_miniCartCount');
    const miniCartItemsDiv = document.getElementById('purifydefaultnav_miniCartItems');
    const miniCartFooter = document.getElementById('purifydefaultnav_miniCartFooter');
    const miniCartTotalSpan = document.getElementById('purifydefaultnav_miniCartTotal');
    const defaultCurrency = <?= json_encode($cfg_ecommerce_currency ?? 'TRY') ?>;

    function getCart() {
        try {
            return JSON.parse(localStorage.getItem('purify_cart') || '[]');
        } catch(e) { return []; }
    }
    function saveCart(cart) {
        localStorage.setItem('purify_cart', JSON.stringify(cart));
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    window.purifydefaultnav_removeFromCart = function(index) {
        let cart = getCart();
        if (index >= 0 && index < cart.length) {
            cart.splice(index, 1);
            saveCart(cart);
            updateCartUI();
            if (typeof purifydefaultshop_updateUI === 'function') {
                purifydefaultshop_updateUI();
            }
        }
    };

    function updateCartUI() {
        if (!cartBadge) return;
        const cart = getCart();
        const count = cart.length;
        if (count > 0) {
            cartBadge.style.display = 'inline-flex';
            cartBadge.textContent = count;
            miniCartCountSpan.textContent = count + ' item' + (count > 1 ? 's' : '');
            miniCartFooter.style.display = 'block';
            let total = 0;
            let activeCurrency = defaultCurrency;
            const itemsHtml = cart.map((item, idx) => {
                const price = parseFloat(item.price || 0);
                const taxRate = parseFloat(item.tax_rate !== undefined ? item.tax_rate : 20);
                const priceIncl = price * (1 + taxRate / 100);
                total += priceIncl;
                activeCurrency = item.currency || defaultCurrency;
                return `
                    <div class="purifydefaultnav_cart_item">
                        <div class="purifydefaultnav_cart_item_img">
                            <i class="las la-cube"></i>
                        </div>
                        <div class="purifydefaultnav_cart_item_info">
                            <p class="purifydefaultnav_cart_item_title">${escapeHtml(item.title || 'Product')}</p>
                            <p class="purifydefaultnav_cart_item_price">${priceIncl.toFixed(2)} ${escapeHtml(activeCurrency)}</p>
                        </div>
                        <button class="purifydefaultnav_cart_item_remove" onclick="purifydefaultnav_removeFromCart(${idx})" title="Remove">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                `;
            }).join('');
            miniCartItemsDiv.innerHTML = itemsHtml;
            miniCartTotalSpan.textContent = total.toFixed(2) + ' ' + activeCurrency;
        } else {
            cartBadge.style.display = 'none';
            miniCartCountSpan.textContent = '0 items';
            miniCartFooter.style.display = 'none';
            miniCartItemsDiv.innerHTML = '<p class="purifydefaultnav_cart_empty">Your cart is currently empty.</p>';
        }
    }

    window.updateNavCart = updateCartUI;

    if (toggler && collapseDiv) {
        toggler.addEventListener('click', function(e) {
            e.stopPropagation();
            collapseDiv.classList.toggle('open');
        });
    }

    if (cartBtn && cartWrapper) {
        cartBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            cartWrapper.classList.toggle('open');
        });
    }

    document.addEventListener('click', function(e) {
        if (cartWrapper && !cartWrapper.contains(e.target)) {
            cartWrapper.classList.remove('open');
        }
        if (collapseDiv && collapseDiv.classList.contains('open') && !collapseDiv.contains(e.target) && !toggler.contains(e.target)) {
            collapseDiv.classList.remove('open');
        }
    });

    function handleScroll() {
        if (!header) return;
        if (window.scrollY > 20) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    }
    window.addEventListener('scroll', handleScroll);
    handleScroll();

    updateCartUI();
    window.addEventListener('storage', function(e) {
        if (e.key === 'purify_cart') updateCartUI();
    });
})();
</script>
