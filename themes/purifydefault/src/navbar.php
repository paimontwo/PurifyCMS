<?php
/**
 * PurifyCMS Default Navigation Header
 * 
 * Variables:
 * - $cfg_mainurl      : Base URL
 * - $cfg_active_theme : Current active theme (data attribute)
 * - $cfg_logolink     : Logo image URL (optional)
 * - $cfg_favicon      : Favicon URL (optional)
 * - $cfg_name         : Site name
 * - $request_path     : Current request path (for active nav highlighting)
 */
?>
<link rel="stylesheet" href="https://cdn.lineicons.com/4.0/lineicons.css">

<header class="purifydefaultnav_header" id="purifydefaultnav_mainHeader" data-theme="<?= htmlspecialchars($cfg_active_theme ?? 'default') ?>">
    <div class="purifydefaultnav_container">
        <a class="purifydefaultnav_brand" href="<?= htmlspecialchars($cfg_mainurl) ?>">
            <?php if (!empty($cfg_logolink)): ?>
                <img src="<?= htmlspecialchars($cfg_logolink) ?>" alt="<?= htmlspecialchars($cfg_name ?? 'PurifyCMS') ?>" class="purifydefaultnav_logo_img">
            <?php else: ?>
                <span class="purifydefaultnav_brand_text" style="font-size: 20px; font-weight: 800; color: #1e1b4b; text-decoration: none;"><?= htmlspecialchars($cfg_name ?? 'PurifyCMS') ?></span>
            <?php endif; ?>
        </a>

        <button class="purifydefaultnav_toggler" id="purifydefaultnav_toggler" aria-label="Toggle menu">
            <i class="lni lni-menu"></i>
        </button>

        <div class="purifydefaultnav_collapse" id="purifydefaultnav_navbarContent">
            <ul class="purifydefaultnav_navbar_nav">
                <li class="purifydefaultnav_nav_item">
                    <a class="purifydefaultnav_navlink <?= empty($request_path) ? 'active' : '' ?>" href="<?= htmlspecialchars($cfg_mainurl) ?>">Ana Sayfa</a>
                </li>
                <li class="purifydefaultnav_nav_item">
                    <a class="purifydefaultnav_navlink <?= strpos($request_path ?? '', 'shop') === 0 ? 'active' : '' ?>" href="<?= $cfg_mainurl ?>/shop">Mağaza</a>
                </li>
                <li class="purifydefaultnav_nav_item">
                    <a class="purifydefaultnav_navlink <?= ($request_path ?? '') === 'order-track' ? 'active' : '' ?>" href="<?= $cfg_mainurl ?>/order-track">Sipariş Takibi</a>
                </li>
                <li class="purifydefaultnav_nav_item">
                    <a class="purifydefaultnav_navlink <?= strpos($request_path ?? '', 'contact') === 0 ? 'active' : '' ?>" href="<?= $cfg_mainurl ?>/contact">İletişim</a>
                </li>
            </ul>

            <!-- Cart Actions -->
            <div class="purifydefaultnav_actions">
                <div class="purifydefaultnav_cart_wrapper">
                    <button class="purifydefaultnav_cart_btn" id="purifydefaultnav_cartBtn">
                        <div class="purifydefaultnav_cart_icon">
                            <i class="lni lni-cart"></i>
                        </div>
                        <span id="purifydefaultnav_cartCount" class="purifydefaultnav_cart_badge">0</span>
                    </button>
                    <div class="purifydefaultnav_cart_dropdown" id="purifydefaultnav_cartDropdown">
                        <div class="purifydefaultnav_cart_header">
                            <h6>Shopping Bag</h6>
                            <span id="purifydefaultnav_miniCartCount">0 items</span>
                        </div>
                        <div id="purifydefaultnav_miniCartItems" class="purifydefaultnav_cart_items">
                            <p class="purifydefaultnav_cart_empty">Your cart is empty</p>
                        </div>
                        <div id="purifydefaultnav_miniCartFooter" class="purifydefaultnav_cart_footer" style="display: none;">
                            <div class="purifydefaultnav_cart_total">
                                <span>Total</span>
                                <span id="purifydefaultnav_miniCartTotal">0.00 TRY</span>
                            </div>
                            <a href="<?= $cfg_mainurl ?>/checkout" class="purifydefaultnav_checkout_btn">Checkout Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<style>

.purifydefaultnav_header {
    position: sticky;
    top: 0;
    z-index: 1030;
    background: var(--prfy-glass, rgba(255,255,255,0.92));
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    font-family: 'Outfit', system-ui, -apple-system, sans-serif;
}

.purifydefaultnav_container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 12px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
}

.purifydefaultnav_brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--prfy-slate-900, #0f172a);
    font-size: 1.4rem;
}

.purifydefaultnav_icon_wrapper {
    width: 38px;
    height: 38px;
    background: linear-gradient(135deg, var(--prfy-primary, #4f46e5), var(--prfy-accent, #7c3aed));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 4px 12px rgba(79,70,229,0.2);
}

.purifydefaultnav_icon_wrapper i {
    font-size: 22px;
}

.purifydefaultnav_logo_img {
    height: 32px;
    width: auto;
    border-radius: 10px;
}

.purifydefaultnav_favicon_small {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    margin-left: -6px;
}

.purifydefaultnav_site_name {
    font-family: inherit;
    font-weight: 800;
    color: var(--prfy-slate-900, #0f172a);
}

.purifydefaultnav_toggler {
    display: none;
    background: none;
    border: none;
    font-size: 28px;
    color: var(--prfy-primary, #4f46e5);
    cursor: pointer;
    padding: 8px;
}

.purifydefaultnav_collapse {
    display: flex;
    align-items: center;
    gap: 2rem;
    justify-content: space-between;
}

.purifydefaultnav_navbar_nav {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 0.5rem;
    align-items: center;
}

.purifydefaultnav_nav_item {
    position: relative;
}

.purifydefaultnav_navlink {
    display: inline-block;
    padding: 10px 16px;
    font-weight: 600;
    font-size: 15px;
    color: var(--prfy-slate-600, #475569);
    text-decoration: none;
    transition: all 0.3s;
    border-radius: 8px;
}

.purifydefaultnav_navlink:hover,
.purifydefaultnav_navlink.active {
    color: var(--prfy-primary, #4f46e5);
    background: rgba(79,70,229,0.06);
}

.purifydefaultnav_dropdown_toggle {
    background: none;
    border: none;
    font-weight: 600;
    font-size: 15px;
    padding: 10px 16px;
    color: var(--prfy-slate-600, #475569);
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.3s;
}

.purifydefaultnav_dropdown_toggle:hover {
    color: var(--prfy-primary, #4f46e5);
    background: rgba(79,70,229,0.06);
}

.purifydefaultnav_dropdown_toggle i {
    font-size: 12px;
    transition: transform 0.2s;
}

.purifydefaultnav_dropdown.open .purifydefaultnav_dropdown_toggle i {
    transform: rotate(180deg);
}

.purifydefaultnav_dropdown_menu {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
    min-width: 220px;
    padding: 12px;
    list-style: none;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px);
    transition: all 0.2s;
    z-index: 100;
}

.purifydefaultnav_dropdown.open .purifydefaultnav_dropdown_menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.purifydefaultnav_dropdown_menu li a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    color: var(--prfy-slate-700, #334155);
    text-decoration: none;
    font-size: 14px;
    border-radius: 10px;
    transition: all 0.2s;
}

.purifydefaultnav_dropdown_menu li a i {
    font-size: 16px;
    color: var(--prfy-primary, #4f46e5);
}

.purifydefaultnav_dropdown_menu li a:hover {
    background: var(--prfy-slate-50, #f8fafc);
    transform: translateX(4px);
}

.purifydefaultnav_actions {
    display: flex;
    align-items: center;
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
    width: 44px;
    height: 44px;
    background: rgba(79,70,229,0.08);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--prfy-primary, #4f46e5);
    transition: all 0.3s;
}

.purifydefaultnav_cart_icon i {
    font-size: 22px;
}

.purifydefaultnav_cart_btn:hover .purifydefaultnav_cart_icon {
    background: rgba(79,70,229,0.15);
    transform: scale(1.02);
}

.purifydefaultnav_cart_badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    font-size: 10px;
    font-weight: bold;
    min-width: 18px;
    height: 18px;
    border-radius: 30px;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
}

.purifydefaultnav_cart_dropdown {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: 340px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 35px -8px rgba(0,0,0,0.15);
    padding: 20px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px);
    transition: all 0.2s;
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
    padding-bottom: 12px;
    border-bottom: 1px solid #eef2ff;
    margin-bottom: 12px;
}

.purifydefaultnav_cart_header h6 {
    margin: 0;
    font-weight: 700;
}

.purifydefaultnav_cart_items {
    max-height: 300px;
    overflow-y: auto;
}

.purifydefaultnav_cart_empty {
    text-align: center;
    padding: 24px;
    color: #64748b;
    font-size: 14px;
}

.purifydefaultnav_cart_item {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.purifydefaultnav_cart_item_img {
    width: 50px;
    height: 50px;
    background: #f1f5f9;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.purifydefaultnav_cart_item_info {
    flex: 1;
}

.purifydefaultnav_cart_item_title {
    font-weight: 700;
    font-size: 14px;
    margin: 0 0 4px;
    color: #0f172a;
}

.purifydefaultnav_cart_item_price {
    font-size: 13px;
    font-weight: 800;
    color: var(--prfy-primary, #4f46e5);
}

.purifydefaultnav_cart_item_remove {
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    padding: 0 4px;
}

.purifydefaultnav_cart_footer {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #eef2ff;
}

.purifydefaultnav_cart_total {
    display: flex;
    justify-content: space-between;
    font-weight: 700;
    margin-bottom: 16px;
}

.purifydefaultnav_checkout_btn {
    display: block;
    background: var(--prfy-primary, #4f46e5);
    color: white;
    text-align: center;
    padding: 14px;
    border-radius: 14px;
    text-decoration: none;
    font-weight: 600;
    transition: background 0.2s;
}

.purifydefaultnav_checkout_btn:hover {
    background: #4338ca;
}

@media (max-width: 992px) {
    .purifydefaultnav_toggler {
        display: block;
    }
    .purifydefaultnav_collapse {
        display: none;
        width: 100%;
        flex-direction: column;
        align-items: stretch;
        margin-top: 20px;
    }
    .purifydefaultnav_collapse.open {
        display: flex;
    }
    .purifydefaultnav_navbar_nav {
        flex-direction: column;
        align-items: stretch;
        gap: 0;
    }
    .purifydefaultnav_navlink,
    .purifydefaultnav_dropdown_toggle {
        width: 100%;
        text-align: left;
    }
    .purifydefaultnav_dropdown_menu {
        position: static;
        box-shadow: none;
        padding-left: 24px;
        opacity: 1;
        visibility: visible;
        transform: none;
        display: none;
        background: transparent;
    }
    .purifydefaultnav_dropdown.open .purifydefaultnav_dropdown_menu {
        display: block;
    }
    .purifydefaultnav_actions {
        margin-top: 16px;
        justify-content: center;
    }
    .purifydefaultnav_cart_dropdown {
        position: fixed;
        top: auto;
        right: 16px;
        left: 16px;
        width: auto;
        max-width: 340px;
        margin: 0 auto;
    }
}

.purifydefaultnav_header.scrolled {
    background: white;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
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
    const solutionsToggle = document.getElementById('purifydefaultnav_solutionsToggle');
    const solutionsDropdown = document.querySelector('#purifydefaultnav_solutionsToggle')?.closest('.purifydefaultnav_dropdown');

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
        return str.replace(/[&<>]/g, function(m) {
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
        }
    };

    function updateCartUI() {
        const cart = getCart();
        const count = cart.length;
        if (count > 0) {
            cartBadge.style.display = 'flex';
            cartBadge.textContent = count;
            miniCartCountSpan.textContent = count + ' item' + (count > 1 ? 's' : '');
            miniCartFooter.style.display = 'block';
            let total = 0;
            const itemsHtml = cart.map((item, idx) => {
                const price = parseFloat(item.price || 0);
                const taxRate = parseFloat(item.tax_rate || 20);
                const priceIncl = price * (1 + taxRate / 100);
                total += priceIncl;
                const currency = item.currency || 'TRY';
                return `
                    <div class="purifydefaultnav_cart_item">
                        <div class="purifydefaultnav_cart_item_img">
                            <i class="lni lni-shopping-bag"></i>
                        </div>
                        <div class="purifydefaultnav_cart_item_info">
                            <p class="purifydefaultnav_cart_item_title">${escapeHtml(item.title || 'Product')}</p>
                            <p class="purifydefaultnav_cart_item_price">${priceIncl.toFixed(2)} ${currency}</p>
                        </div>
                        <button class="purifydefaultnav_cart_item_remove" onclick="purifydefaultnav_removeFromCart(${idx})">
                            <i class="lni lni-close"></i>
                        </button>
                    </div>
                `;
            }).join('');
            miniCartItemsDiv.innerHTML = itemsHtml;
            miniCartTotalSpan.textContent = total.toFixed(2) + ' TRY';
        } else {
            cartBadge.style.display = 'none';
            miniCartCountSpan.textContent = '0 items';
            miniCartFooter.style.display = 'none';
            miniCartItemsDiv.innerHTML = '<p class="purifydefaultnav_cart_empty">Your cart is empty</p>';
        }
    }

    if (toggler && collapseDiv) {
        toggler.addEventListener('click', function(e) {
            e.stopPropagation();
            collapseDiv.classList.toggle('open');
        });
    }

    if (solutionsToggle && solutionsDropdown) {
        solutionsToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            solutionsDropdown.classList.toggle('open');
        });
    }

    if (cartBtn && cartWrapper) {
        cartBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            cartWrapper.classList.toggle('open');
        });
    }

    document.addEventListener('click', function(e) {
        if (solutionsDropdown && !solutionsDropdown.contains(e.target)) {
            solutionsDropdown.classList.remove('open');
        }
        if (cartWrapper && !cartWrapper.contains(e.target)) {
            cartWrapper.classList.remove('open');
        }
        if (collapseDiv && collapseDiv.classList.contains('open') && !collapseDiv.contains(e.target) && !toggler.contains(e.target)) {
            collapseDiv.classList.remove('open');
        }
    });

    function handleScroll() {
        if (window.scrollY > 50) {
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