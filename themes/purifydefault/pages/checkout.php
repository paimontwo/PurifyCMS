<?php
if (empty($cfg_enable_ecommerce)) {
    header('HTTP/1.1 404 Not Found');
    require __DIR__ . '/404.php';
    return;
}

$title = "Checkout";
$desc = "Complete your billing details and select a payment method to place your order.";
$base_site_url = rtrim($cfg_mainurl ?? '', '/');
$default_currency = $cfg_ecommerce_currency ?? 'TRY';
?>

<section class="purifydefaultcheckout_section">
    <!-- purify:zone:checkout_start -->
    <div class="purifydefaultcheckout_container" id="sf-checkout-wrap">
        <header class="purifydefaultcheckout_header">
            <span class="section-badge"><i class="las la-lock"></i> Secure Checkout</span>
            <h1 class="purifydefaultcheckout_title">Complete Your Order</h1>
            <p class="purifydefaultcheckout_subtitle">Review your items and provide your billing information below.</p>
        </header>

        <div class="purifydefaultcheckout_grid">
            <!-- Left: Billing & Payment Form -->
            <div class="purifydefaultcheckout_main">
                <form id="sf-checkout-form">
                    <div class="purifydefaultcheckout_panel">
                        <h3 class="purifydefaultcheckout_panel_title">
                            <i class="las la-user-check"></i> 1. Billing &amp; Contact Information
                        </h3>
                        
                        <div class="purifydefaultcheckout_form_row">
                            <div class="purifydefaultcheckout_input_group">
                                <label class="purifydefaultcheckout_label">Full Name</label>
                                <input type="text" name="name" class="purifydefaultcheckout_input" required placeholder="John Doe">
                            </div>
                            <div class="purifydefaultcheckout_input_group">
                                <label class="purifydefaultcheckout_label">Email Address</label>
                                <input type="email" name="email" class="purifydefaultcheckout_input" required placeholder="john@company.com">
                            </div>
                        </div>

                        <div class="purifydefaultcheckout_form_row">
                            <div class="purifydefaultcheckout_input_group">
                                <label class="purifydefaultcheckout_label">Phone Number</label>
                                <input type="text" name="phone" class="purifydefaultcheckout_input" required placeholder="+1 (555) 000-0000">
                            </div>
                            <div class="purifydefaultcheckout_input_group">
                                <label class="purifydefaultcheckout_label">City</label>
                                <input type="text" name="city" class="purifydefaultcheckout_input" required placeholder="City">
                            </div>
                        </div>

                        <div class="purifydefaultcheckout_form_row">
                            <div class="purifydefaultcheckout_input_group">
                                <label class="purifydefaultcheckout_label">State / District</label>
                                <input type="text" name="district" class="purifydefaultcheckout_input" required placeholder="State or District">
                            </div>
                            <div class="purifydefaultcheckout_input_group">
                                <label class="purifydefaultcheckout_label">Postal Code</label>
                                <input type="text" name="zip" class="purifydefaultcheckout_input" placeholder="Postal / ZIP Code">
                            </div>
                        </div>

                        <div class="purifydefaultcheckout_input_group">
                            <label class="purifydefaultcheckout_label">Billing / Delivery Address</label>
                            <textarea name="address" class="purifydefaultcheckout_textarea" required placeholder="Street address, suite, building..."></textarea>
                        </div>

                        <div class="purifydefaultcheckout_corporate_section">
                            <h3 class="purifydefaultcheckout_panel_title" style="font-size: 14.5px; margin-bottom: 18px;">
                                <i class="las la-building"></i> Corporate Invoice Details (Optional)
                            </h3>
                            <div class="purifydefaultcheckout_form_row">
                                <div class="purifydefaultcheckout_input_group">
                                    <label class="purifydefaultcheckout_label">Tax ID / VAT Number</label>
                                    <input type="text" name="tax_id" class="purifydefaultcheckout_input" placeholder="Tax ID or VAT Number">
                                </div>
                                <div class="purifydefaultcheckout_input_group">
                                    <label class="purifydefaultcheckout_label">Tax Office</label>
                                    <input type="text" name="tax_office" class="purifydefaultcheckout_input" placeholder="Tax Office">
                                </div>
                            </div>
                            <div class="purifydefaultcheckout_input_group">
                                <label class="purifydefaultcheckout_label">Company Legal Name</label>
                                <input type="text" name="company" class="purifydefaultcheckout_input" placeholder="Company Name Inc.">
                            </div>
                        </div>
                    </div>

                    <div class="purifydefaultcheckout_panel" style="margin-top: 24px;">
                        <h3 class="purifydefaultcheckout_panel_title">
                            <i class="las la-credit-card"></i> 2. Payment Method
                        </h3>
                        <div class="purifydefaultcheckout_gateway_list">
                            <?php if (!empty($cfg_paytr_m_id)): ?>
                                <label class="purifydefaultcheckout_gateway_option">
                                    <input type="radio" name="payment_method" value="paytr" checked>
                                    <div class="purifydefaultcheckout_gateway_meta">
                                        <div class="purifydefaultcheckout_gateway_title">
                                            <strong>Credit / Debit Card (PayTR)</strong>
                                            <span class="purifydefaultcheckout_badge">INSTANT APPROVAL</span>
                                        </div>
                                        <span class="purifydefaultcheckout_gateway_desc">256-bit SSL encrypted checkout compatible with all major cards.</span>
                                    </div>
                                </label>
                            <?php endif; ?>

                            <?php if (!empty($cfg_bank_details) || empty($cfg_paytr_m_id)): ?>
                                <label class="purifydefaultcheckout_gateway_option">
                                    <input type="radio" name="payment_method" value="iban" <?= (empty($cfg_paytr_m_id)) ? 'checked' : '' ?>>
                                    <div class="purifydefaultcheckout_gateway_meta">
                                        <div class="purifydefaultcheckout_gateway_title">
                                            <strong>Bank Transfer (Wire / IBAN)</strong>
                                        </div>
                                        <span class="purifydefaultcheckout_gateway_desc">Direct bank transfer. Your order is activated once payment is verified.</span>
                                    </div>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right: Order Summary -->
            <aside class="purifydefaultcheckout_registry">
                <div class="purifydefaultcheckout_panel">
                    <h3 class="purifydefaultcheckout_panel_title" style="margin-bottom: 18px;">
                        <i class="las la-receipt"></i> Order Summary
                    </h3>
                    
                    <div id="sf-cart-list" class="purifydefaultcheckout_cart_list"></div>

                    <div class="purifydefaultcheckout_cart_actions">
                        <button type="button" class="purifydefaultcheckout_clear_btn" onclick="clearCart()">
                            <i class="las la-trash-alt"></i> Clear Cart
                        </button>
                    </div>

                    <div class="purifydefaultcheckout_summary">
                        <div class="purifydefaultcheckout_summary_line">
                            <span>Subtotal (Excl. Tax)</span>
                            <span id="sf-subtotal">0.00</span>
                        </div>
                        <div class="purifydefaultcheckout_summary_line">
                            <span>Estimated Tax</span>
                            <span id="sf-tax">0.00</span>
                        </div>
                        <div class="purifydefaultcheckout_summary_total">
                            <span>Total <br><small>(Incl. Tax)</small></span>
                            <span id="sf-total">0.00</span>
                        </div>
                    </div>

                    <p class="purifydefaultcheckout_security_note">
                        <i class="las la-shield-alt"></i> 256-BIT SSL ENCRYPTED CHECKOUT
                    </p>

                    <button type="submit" form="sf-checkout-form" class="purifydefaultcheckout_btn_primary" id="sf-submit-btn">
                        Complete Order
                    </button>
                </div>
            </aside>
        </div>
    </div>
    <!-- purify:zone:checkout_end -->

    <div class="purifydefaultcheckout_container" id="sf-payment-view" style="display: none; padding: 60px 0; text-align: center;">
        <div id="sf-payment-container" style="max-width: 720px; margin: 0 auto;"></div>
    </div>
</section>

<style>
.purifydefaultcheckout_section {
    padding: 56px 0 96px;
    background: #f8fafc;
    min-height: 80vh;
}
.purifydefaultcheckout_container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
}
.purifydefaultcheckout_header {
    margin-bottom: 36px;
}
.purifydefaultcheckout_title {
    font-size: clamp(28px, 4vw, 36px);
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 6px;
}
.purifydefaultcheckout_subtitle {
    color: #64748b;
    font-size: 15.5px;
}
.purifydefaultcheckout_grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 28px;
    align-items: start;
}
@media (max-width: 992px) {
    .purifydefaultcheckout_grid {
        grid-template-columns: 1fr;
    }
}
.purifydefaultcheckout_panel {
    background: #ffffff;
    border-radius: 16px;
    padding: 28px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
}
.purifydefaultcheckout_panel_title {
    font-size: 16.5px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 22px;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.purifydefaultcheckout_panel_title i {
    color: #4f46e5;
    font-size: 20px;
}
.purifydefaultcheckout_form_row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}
@media (max-width: 640px) {
    .purifydefaultcheckout_form_row {
        grid-template-columns: 1fr;
    }
}
.purifydefaultcheckout_input_group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.purifydefaultcheckout_label {
    font-weight: 600;
    font-size: 13px;
    color: #334155;
}
.purifydefaultcheckout_input,
.purifydefaultcheckout_textarea {
    padding: 11px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.15s ease;
    background: #f8fafc;
    color: #0f172a;
    font-family: inherit;
}
.purifydefaultcheckout_input:focus,
.purifydefaultcheckout_textarea:focus {
    outline: none;
    background: #ffffff;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}
.purifydefaultcheckout_textarea {
    resize: vertical;
    min-height: 90px;
}
.purifydefaultcheckout_corporate_section {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px dashed #e2e8f0;
}
.purifydefaultcheckout_gateway_list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.purifydefaultcheckout_gateway_option {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.15s ease;
    background: #f8fafc;
}
.purifydefaultcheckout_gateway_option:hover {
    border-color: #4f46e5;
    background: #ffffff;
}
.purifydefaultcheckout_gateway_option input[type="radio"] {
    width: 18px;
    height: 18px;
    accent-color: #4f46e5;
    margin: 0;
}
.purifydefaultcheckout_gateway_meta {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.purifydefaultcheckout_gateway_title {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.purifydefaultcheckout_gateway_title strong {
    font-size: 14.5px;
    color: #0f172a;
}
.purifydefaultcheckout_badge {
    background: rgba(79, 70, 229, 0.1);
    color: #4f46e5;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
}
.purifydefaultcheckout_gateway_desc {
    font-size: 12.5px;
    color: #64748b;
}
.purifydefaultcheckout_registry .purifydefaultcheckout_panel {
    position: sticky;
    top: 90px;
}
.purifydefaultcheckout_cart_list {
    max-height: 280px;
    overflow-y: auto;
    margin-bottom: 12px;
}
.purifydefaultcheckout_cart_item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}
.purifydefaultcheckout_cart_item_info {
    flex: 1;
}
.purifydefaultcheckout_cart_item_title {
    font-weight: 600;
    font-size: 13.5px;
    margin: 0 0 2px;
    color: #0f172a;
}
.purifydefaultcheckout_cart_item_variants {
    font-size: 12px;
    color: #64748b;
}
.purifydefaultcheckout_cart_item_price {
    font-weight: 700;
    color: #4f46e5;
    font-size: 13.5px;
}
.purifydefaultcheckout_cart_item_actions {
    display: flex;
    gap: 8px;
    align-items: center;
}
.purifydefaultcheckout_icon_btn {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    color: #94a3b8;
    transition: color 0.15s;
}
.purifydefaultcheckout_icon_btn:hover {
    color: #4f46e5;
}
.purifydefaultcheckout_icon_btn_danger:hover {
    color: #ef4444;
}
.purifydefaultcheckout_cart_actions {
    text-align: right;
    margin-bottom: 16px;
}
.purifydefaultcheckout_clear_btn {
    background: #fef2f2;
    border: 1px solid #fecaca;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #dc2626;
    cursor: pointer;
    font-family: inherit;
}
.purifydefaultcheckout_clear_btn:hover {
    background: #fee2e2;
}
.purifydefaultcheckout_summary {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    margin: 16px 0;
}
.purifydefaultcheckout_summary_line {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 13.5px;
    color: #475569;
    font-weight: 500;
}
.purifydefaultcheckout_summary_total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0 4px;
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    border-top: 1px solid #e2e8f0;
    margin-top: 8px;
}
.purifydefaultcheckout_summary_total small {
    font-size: 11.5px;
    font-weight: 500;
    color: #64748b;
}
.purifydefaultcheckout_security_note {
    font-size: 11px;
    color: #64748b;
    font-weight: 700;
    text-align: center;
    margin: 16px 0;
    letter-spacing: 0.04em;
}
.purifydefaultcheckout_btn_primary {
    width: 100%;
    background: #4f46e5;
    border: none;
    padding: 14px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    color: #ffffff;
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
}
.purifydefaultcheckout_btn_primary:hover {
    background: #4338ca;
}
.purifydefaultcheckout_btn_primary:disabled {
    opacity: 0.6;
}
.purifydefaultcheckout_modal {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(8px);
}
.purifydefaultcheckout_modal_content {
    background: #ffffff;
    width: 92%;
    max-width: 860px;
    border-radius: 16px;
    padding: 24px;
    position: relative;
}
.purifydefaultcheckout_modal_header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e2e8f0;
}
.purifydefaultcheckout_modal_title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    font-size: 16px;
}
.purifydefaultcheckout_close_btn {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: #64748b;
}
</style>

<script>
let cart = JSON.parse(localStorage.getItem('purify_cart') || '[]');
const defaultCurrency = <?= json_encode($default_currency) ?>;

if (cart.length === 0) window.location.href = '<?= htmlspecialchars($base_site_url) ?>/shop';

renderCart();

function renderCart() {
    const list = document.getElementById('sf-cart-list');
    let sub = 0;
    let tax = 0;
    let cur = defaultCurrency;
    
    if (cart.length === 0) {
        list.innerHTML = '<div style="text-align:center; padding: 20px; color:#64748b; font-size:13.5px;">Your cart is empty.</div>';
        document.getElementById('sf-subtotal').innerText = '0.00 ' + cur;
        document.getElementById('sf-tax').innerText = '0.00 ' + cur;
        document.getElementById('sf-total').innerText = '0.00 ' + cur;
        return;
    }

    list.innerHTML = cart.map((item, idx) => {
        const itemPrice = parseFloat(item.price || 0);
        const itemTaxRate = parseFloat(item.tax_rate !== undefined ? item.tax_rate : 20);
        const itemTotalIncl = itemPrice * (1 + itemTaxRate / 100);
        sub += itemPrice;
        tax += (itemPrice * itemTaxRate) / 100;
        cur = item.currency || defaultCurrency;
        const variations = (item.selected_options && item.selected_options.length > 0) ? item.selected_options.map(o => o.name).join(', ') : 'Standard';
        
        return `
            <div class="purifydefaultcheckout_cart_item">
                <div class="purifydefaultcheckout_cart_item_info">
                    <div class="purifydefaultcheckout_cart_item_title">${escapeHtml(item.title)}</div>
                    <div class="purifydefaultcheckout_cart_item_variants">${escapeHtml(variations)}</div>
                </div>
                <div class="purifydefaultcheckout_cart_item_actions">
                    <div class="purifydefaultcheckout_cart_item_price">${itemTotalIncl.toFixed(2)} ${escapeHtml(cur)}</div>
                    <button type="button" onclick="editItem(${idx})" class="purifydefaultcheckout_icon_btn" title="Edit"><i class="las la-pen"></i></button>
                    <button type="button" onclick="removeItem(${idx})" class="purifydefaultcheckout_icon_btn purifydefaultcheckout_icon_btn_danger" title="Remove"><i class="las la-times-circle"></i></button>
                </div>
            </div>`;
    }).join('');

    document.getElementById('sf-subtotal').innerText = sub.toFixed(2) + ' ' + cur;
    document.getElementById('sf-tax').innerText = tax.toFixed(2) + ' ' + cur;
    document.getElementById('sf-total').innerText = (sub + tax).toFixed(2) + ' ' + cur;
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

function editItem(idx) {
    const slug = cart[idx].slug || cart[idx].id;
    cart.splice(idx, 1);
    localStorage.setItem('purify_cart', JSON.stringify(cart));
    window.location.href = '<?= htmlspecialchars($base_site_url) ?>/product/' + slug;
}

function removeItem(idx) {
    cart.splice(idx, 1);
    localStorage.setItem('purify_cart', JSON.stringify(cart));
    renderCart();
    if (typeof updateNavCart === 'function') updateNavCart();
    if (cart.length === 0) setTimeout(() => window.location.href = '<?= htmlspecialchars($base_site_url) ?>/shop', 800);
}

function saveOrderToHistory(oid) {
    let history = JSON.parse(localStorage.getItem('purify_order_history') || '[]');
    if (!history.includes(oid)) {
        history.unshift(oid);
        history = history.slice(0, 5);
        localStorage.setItem('purify_order_history', JSON.stringify(history));
    }
}

function clearCart() {
    if (confirm('Are you sure you want to remove all items from your cart?')) {
        cart = [];
        localStorage.setItem('purify_cart', JSON.stringify(cart));
        renderCart();
        if (typeof updateNavCart === 'function') updateNavCart();
        setTimeout(() => window.location.href = '<?= htmlspecialchars($base_site_url) ?>/shop', 600);
    }
}

document.getElementById('sf-checkout-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('sf-submit-btn');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Processing Order...';

    const formData = new FormData(e.target);
    const pMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'iban';
    const orderData = {
        customer: Object.fromEntries(formData.entries()),
        items: cart,
        payment_method: pMethod
    };

    try {
        const response = await fetch('<?= htmlspecialchars($base_site_url) ?>/api/ecommerce/checkout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });

        const res = await response.json();
        
        if (res.status === 'gateway') {
            saveOrderToHistory(res.order_id);
            handleGateway(res);
            return;
        }

        if (res.status === 'success') {
            saveOrderToHistory(res.order_id);
            localStorage.removeItem('purify_cart');
            if (typeof updateNavCart === 'function') updateNavCart();
            document.getElementById('sf-checkout-wrap').style.display = 'none';
            document.getElementById('sf-payment-view').style.display = 'block';
            
            document.getElementById('sf-payment-container').innerHTML = `
                <div class="purifydefaultcheckout_panel" style="border: 1px solid #bbf7d0; background: #ffffff; text-align: center; padding: 48px 32px;">
                    <div style="width: 64px; height: 64px; border-radius: 50%; background: #f0fdf4; color: #10b981; display: inline-flex; align-items: center; justify-content: center; font-size: 36px; margin-bottom: 20px;"><i class="las la-check-circle"></i></div>
                    <h2 style="font-weight: 800; font-size: 28px; color: #0f172a; margin-bottom: 10px;">Order Received</h2>
                    <p style="font-size: 15.5px; color: #475569; margin-bottom: 28px;">
                        Your Order ID: <strong>#${escapeHtml(res.order_id)}</strong><br>
                        Please complete your bank transfer using the account details below:
                    </p>
                    <div style="background: #f8fafc; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; font-family: monospace; text-align: left; font-size: 14px; font-weight: 600; color: #0f172a; white-space: pre-wrap;">${escapeHtml(res.iban || 'Bank details will be provided via email.')}</div>
                    <div style="margin-top: 32px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                        <a href="<?= htmlspecialchars($base_site_url) ?>/order-track?oid=${ encodeURIComponent(res.order_id) }" class="btn btn-primary">Track Order Status</a>
                        <a href="<?= htmlspecialchars($base_site_url ?: '/') ?>" class="btn btn-secondary">Back to Home</a>
                    </div>
                </div>
            `;
        } else {
            alert(res.msg || res.message || "An error occurred while creating your order.");
        }
    } catch (err) {
        console.error(err);
        alert('Unable to connect to the server. Please try again.');
    } finally {
        btn.innerText = originalText;
        btn.disabled = false;
    }
});

function handleGateway(res) {
    if (res.gateway === 'paytr') {
        showModal(`
            <div class="purifydefaultcheckout_modal_content" style="max-width: 850px; height: 88vh; display: flex; flex-direction: column;">
                <div class="purifydefaultcheckout_modal_header">
                    <div class="purifydefaultcheckout_modal_title">
                        <div style="width: 34px; height: 34px; background: #4f46e5; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                            <i class="las la-shield-alt"></i>
                        </div>
                        <span>Secure Card Payment (PayTR)</span>
                    </div>
                    <button class="purifydefaultcheckout_close_btn" onclick="location.reload()"><i class="las la-times"></i></button>
                </div>
                <div style="flex: 1; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;">
                    <iframe id="paytr_iframe" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
            </div>
        `);
        setTimeout(() => {
            const iframe = document.getElementById('paytr_iframe');
            if (iframe) {
                iframe.sandbox = "allow-scripts allow-forms allow-same-origin allow-popups allow-top-navigation allow-top-navigation-by-user-activation";
                iframe.referrerPolicy = "origin";
                iframe.src = `https://www.paytr.com/odeme/guvenli/${res.token}?_=${Date.now()}`;
            }
        }, 300);
    } else if (res.gateway === 'redirect') {
        window.location.href = res.redirect_url;
    }
}

function showModal(html) {
    const modal = document.createElement('div');
    modal.className = 'purifydefaultcheckout_modal';
    modal.innerHTML = html;
    document.body.appendChild(modal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.remove();
    });
}
</script>
