<?php
if (empty($cfg_enable_ecommerce)) {
    header('HTTP/1.1 404 Not Found');
    require __DIR__ . '/404.php';
    return;
}

$prefilled_oid = $_GET['oid'] ?? '';
$auto_pay = $_GET['pay'] ?? '';
$payment_status = $_GET['status'] ?? '';

require_once __DIR__ . '/../../../inc/PurifyTrade.php';

$order = null;
if (!empty($prefilled_oid)) {
    $order = PurifyTrade::getOrder($prefilled_oid);
}

if ($payment_status === 'success') {
    $title = "Payment Confirmed";
    $desc = "Your order payment has been confirmed. View your order status and delivery details.";
} elseif ($payment_status === 'fail') {
    $title = "Payment Failed";
    $desc = "Your payment could not be completed. Please try again or choose another payment method.";
} else {
    $title = "Track Your Order";
    $desc = "Check real-time order status, payment verification, and fulfillment details.";
}
$base_site_url = rtrim($cfg_mainurl ?? '', '/');
?>

<section class="purifydefaulttrack_section">
    <!-- purify:zone:track_start -->
    <div class="purifydefaulttrack_container">
        
        <header class="purifydefaulttrack_header">
            <span class="section-badge"><i class="las la-shipping-fast"></i> Order Tracking</span>
            <h1 class="purifydefaulttrack_title">Track Your Order</h1>
            <p class="purifydefaulttrack_subtitle">Enter your Order ID below to check real-time status and fulfillment details.</p>
        </header>

        <div class="purifydefaulttrack_card purifydefaulttrack_search_hub">
            <div class="purifydefaulttrack_input_group">
                <div class="purifydefaulttrack_input_inner">
                    <label class="purifydefaulttrack_label"><i class="las la-hashtag"></i> Order ID</label>
                    <input type="text" id="sf-oid-input" 
                           class="purifydefaulttrack_input" 
                           placeholder="e.g. PRFY2026..." 
                           value="<?= htmlspecialchars($prefilled_oid) ?>" 
                           spellcheck="false">
                </div>
                <button type="button" class="purifydefaulttrack_btn_primary" onclick="lookupOrder()" id="sf-lookup-btn">
                    <i class="las la-search"></i> <span>Track Order</span>
                </button>
            </div>
            
            <div id="sf-recent-orders-wrap" class="purifydefaulttrack_history" style="display: none;">
                <span class="purifydefaulttrack_history_label"><i class="las la-history"></i> Recent Lookups</span>
                <div id="sf-recent-orders-list" class="purifydefaulttrack_history_list"></div>
            </div>
        </div>

        <div id="sf-status-feedback">
            <?php if ($payment_status === 'success'): ?>
            <div class="purifydefaulttrack_alert purifydefaulttrack_alert_success animate_fade_up">
                <div class="purifydefaulttrack_alert_icon"><i class="las la-check-circle"></i></div>
                <div class="purifydefaulttrack_alert_content">
                    <h3>Payment Confirmed</h3>
                    <p>Your payment has been verified and your order is now being processed.</p>
                    <?php if ($order): ?>
                        <div class="purifydefaulttrack_oid_badge">#<?= htmlspecialchars($order['id']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php elseif ($payment_status === 'fail'): ?>
            <div class="purifydefaulttrack_alert purifydefaulttrack_alert_danger animate_fade_up">
                <div class="purifydefaulttrack_alert_icon"><i class="las la-exclamation-triangle"></i></div>
                <div class="purifydefaulttrack_alert_content">
                    <h3>Payment Could Not Be Completed</h3>
                    <p>An error occurred during payment authorization. Please verify your card details and try again.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div id="sf-loading" class="purifydefaulttrack_loader" style="display: none;">
            <div class="purifydefaulttrack_spinner"></div>
            <p>Looking up your order...</p>
        </div>

        <div id="sf-track-results" class="purifydefaulttrack_results_area" style="display: none;"></div>

        <!-- Help & Guidance -->
        <div id="sf-help-section" class="purifydefaulttrack_help">
            <div class="purifydefaulttrack_help_icon"><i class="las la-question-circle"></i></div>
            <h3>Where can I find my Order ID?</h3>
            <p>Your Order ID (starting with <strong>#PRFY</strong>) is displayed on the order confirmation screen and sent to your email address after checkout.</p>
        </div>

    </div>
    <!-- purify:zone:track_end -->

    <!-- Modal: Payment Terminal -->
    <div id="sf-payment-modal" class="purifydefaulttrack_modal" style="display: none;">
        <div class="purifydefaulttrack_modal_content">
            <div class="purifydefaulttrack_modal_header">
                <h3><i class="las la-shield-alt" style="color: #4f46e5;"></i> Secure Payment Portal</h3>
                <button onclick="closePaymentModal()" class="purifydefaulttrack_close_btn"><i class="las la-times"></i></button>
            </div>
            <div id="sf-payment-container" class="purifydefaulttrack_modal_body"></div>
        </div>
    </div>
</section>

<style>
.purifydefaulttrack_section {
    min-height: 78vh;
    padding: 64px 0 96px;
    background: #f8fafc;
}
.purifydefaulttrack_container {
    max-width: 780px;
    margin: 0 auto;
    padding: 0 24px;
}
.purifydefaulttrack_header {
    text-align: center;
    margin-bottom: 36px;
}
.purifydefaulttrack_title {
    font-size: clamp(28px, 4vw, 36px);
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 8px;
}
.purifydefaulttrack_subtitle {
    color: #64748b;
    font-size: 15.5px;
}
.purifydefaulttrack_card {
    background: #ffffff;
    border-radius: 16px;
    padding: 28px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
}
.purifydefaulttrack_search_hub {
    margin-bottom: 24px;
}
.purifydefaulttrack_input_group {
    display: flex;
    gap: 12px;
    align-items: flex-end;
}
.purifydefaulttrack_input_inner {
    flex: 1;
}
.purifydefaulttrack_label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    color: #475569;
    margin-bottom: 6px;
    letter-spacing: 0.04em;
}
.purifydefaulttrack_input {
    width: 100%;
    height: 48px;
    padding: 0 16px;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    font-family: inherit;
    color: #0f172a;
    transition: all 0.15s ease;
}
.purifydefaulttrack_input:focus {
    outline: none;
    background: #ffffff;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}
.purifydefaulttrack_btn_primary {
    height: 48px;
    padding: 0 24px;
    background: #4f46e5;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14.5px;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: inherit;
    text-decoration: none;
}
.purifydefaulttrack_btn_primary:hover {
    background: #4338ca;
}
.purifydefaulttrack_btn_primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.purifydefaulttrack_history {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
}
.purifydefaulttrack_history_label {
    font-size: 11.5px;
    font-weight: 700;
    color: #64748b;
    display: block;
    margin-bottom: 10px;
    text-transform: uppercase;
}
.purifydefaulttrack_history_list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.purifydefaulttrack_history_item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    font-family: inherit;
}
.purifydefaulttrack_history_item:hover {
    border-color: #4f46e5;
    color: #4f46e5;
}
.purifydefaulttrack_alert {
    display: flex;
    gap: 16px;
    padding: 20px;
    border-radius: 14px;
    margin-bottom: 24px;
}
.purifydefaulttrack_alert_success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}
.purifydefaulttrack_alert_danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
}
.purifydefaulttrack_alert_icon {
    font-size: 28px;
}
.purifydefaulttrack_alert_success .purifydefaulttrack_alert_icon { color: #10b981; }
.purifydefaulttrack_alert_danger .purifydefaulttrack_alert_icon { color: #ef4444; }
.purifydefaulttrack_alert_content h3 {
    font-size: 16.5px;
    font-weight: 700;
    margin: 0 0 4px;
    color: #0f172a;
}
.purifydefaulttrack_alert_content p {
    font-size: 14px;
    color: #475569;
    margin: 0;
}
.purifydefaulttrack_oid_badge {
    display: inline-block;
    margin-top: 10px;
    padding: 4px 10px;
    background: #ffffff;
    border: 1px solid #bbf7d0;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 700;
    font-family: monospace;
    color: #065f46;
}
.purifydefaulttrack_loader {
    text-align: center;
    padding: 48px 0;
    color: #64748b;
}
.purifydefaulttrack_spinner {
    width: 36px;
    height: 36px;
    border: 3px solid #e2e8f0;
    border-top-color: #4f46e5;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 12px;
}
.purifydefaulttrack_order_panel {
    background: #ffffff;
    border-radius: 16px;
    padding: 28px;
    border: 1px solid #e2e8f0;
    margin-bottom: 24px;
}
.purifydefaulttrack_status_row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
}
.purifydefaulttrack_status_badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 12.5px;
}
.purifydefaulttrack_items_list {
    margin: 20px 0;
    border-top: 1px solid #f1f5f9;
}
.purifydefaulttrack_item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid #f1f5f9;
}
.purifydefaulttrack_item_title {
    font-weight: 600;
    font-size: 14.5px;
    color: #0f172a;
}
.purifydefaulttrack_item_price {
    font-weight: 700;
    color: #4f46e5;
    font-size: 14.5px;
}
.purifydefaulttrack_help {
    text-align: center;
    padding: 32px;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
}
.purifydefaulttrack_help_icon {
    font-size: 32px;
    color: #64748b;
    margin-bottom: 10px;
}
.purifydefaulttrack_help h3 {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 6px;
}
.purifydefaulttrack_help p {
    font-size: 13.5px;
    color: #64748b;
    max-width: 440px;
    margin: 0 auto;
}
.purifydefaulttrack_modal {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(8px);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.purifydefaulttrack_modal_content {
    background: #ffffff;
    width: 92%;
    max-width: 850px;
    height: 85vh;
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.purifydefaulttrack_modal_header {
    padding: 18px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.purifydefaulttrack_modal_header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}
.purifydefaulttrack_close_btn {
    background: none;
    border: none;
    font-size: 22px;
    color: #64748b;
    cursor: pointer;
}
.purifydefaulttrack_modal_body {
    flex: 1;
    min-height: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 640px) {
    .purifydefaulttrack_input_group { flex-direction: column; align-items: stretch; }
    .purifydefaulttrack_btn_primary { width: 100%; justify-content: center; }
}
</style>

<script>
const cfg_mainurl = <?= json_encode($base_site_url) ?>;
const cfg_has_paytr = <?= json_encode(!empty($cfg_paytr_m_id)) ?>;
const cfg_has_bank = <?= json_encode(!empty($cfg_bank_details)) ?>;
const cfg_bank_details = <?= json_encode($cfg_bank_details ?? '') ?>;

document.addEventListener('DOMContentLoaded', () => {
    const prefilled = <?= json_encode($prefilled_oid) ?>;
    if (prefilled) {
        setTimeout(() => lookupOrder(), 250);
    }
    renderRecentOrders();
});

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function saveToHistory(oid) {
    if (!oid || oid.length < 6) return;
    try {
        let history = JSON.parse(localStorage.getItem('purify_order_history') || '[]');
        oid = oid.trim().toUpperCase();
        if (!history.includes(oid)) {
            history.unshift(oid);
            history = history.slice(0, 4);
            localStorage.setItem('purify_order_history', JSON.stringify(history));
            renderRecentOrders();
        }
    } catch (e) {}
}

function renderRecentOrders() {
    const history = JSON.parse(localStorage.getItem('purify_order_history') || '[]');
    const wrap = document.getElementById('sf-recent-orders-wrap');
    const container = document.getElementById('sf-recent-orders-list');
    
    if (history.length === 0) {
        wrap.style.display = 'none';
        return;
    }
    
    wrap.style.display = 'block';
    container.innerHTML = history.map(oid => `
        <button type="button" onclick="quickLookup('${escapeHtml(oid)}')" class="purifydefaulttrack_history_item">
            #${escapeHtml(oid)}
        </button>
    `).join('');
}

function quickLookup(oid) {
    document.getElementById('sf-oid-input').value = oid;
    lookupOrder();
}

async function lookupOrder() {
    const input = document.getElementById('sf-oid-input');
    const oid = input.value.trim().toUpperCase();
    
    if (!oid) {
        input.focus();
        return;
    }

    const btn = document.getElementById('sf-lookup-btn');
    const loading = document.getElementById('sf-loading');
    const results = document.getElementById('sf-track-results');
    const statusFeedback = document.getElementById('sf-status-feedback');
    const helpSection = document.getElementById('sf-help-section');
    
    btn.disabled = true;
    loading.style.display = 'block';
    results.style.display = 'none';
    statusFeedback.style.display = 'none';
    helpSection.style.display = 'none';

    try {
        const response = await fetch(`${cfg_mainurl}/api/ecommerce/order-lookup?oid=${encodeURIComponent(oid)}`);
        const res = await response.json();

        loading.style.display = 'none';
        
        if (res.success && res.order) {
            saveToHistory(oid);
            renderOrder(res.order);
        } else {
            results.innerHTML = `
                <div class="purifydefaulttrack_card" style="text-align: center; border-color: #fecaca;">
                    <i class="las la-search-minus" style="font-size: 48px; color: #ef4444; margin-bottom: 12px;"></i>
                    <h3 style="color: #0f172a; font-weight: 700; margin-bottom: 6px;">Order Not Found</h3>
                    <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">${escapeHtml(res.message || 'No order matching the provided Order ID was found.')}</p>
                    <button type="button" onclick="resetSearch()" class="purifydefaulttrack_btn_primary" style="margin: 0 auto;">Try Again</button>
                </div>
            `;
            results.style.display = 'block';
        }
    } catch (e) {
        loading.style.display = 'none';
        results.innerHTML = `<div class="purifydefaulttrack_alert purifydefaulttrack_alert_danger">Unable to reach server. Please try again.</div>`;
        results.style.display = 'block';
    } finally {
        btn.disabled = false;
    }
}

function renderOrder(order) {
    const results = document.getElementById('sf-track-results');
    results.style.display = 'block';
    
    const statusMap = {
        'pending': { color: '#b45309', bg: '#fef3c7', text: 'Pending Payment', icon: 'la-clock' },
        'paid': { color: '#065f46', bg: '#d1fae5', text: 'Paid & Confirmed', icon: 'la-check-circle' },
        'processing': { color: '#1d4ed8', bg: '#dbeafe', text: 'Processing', icon: 'la-box' },
        'shipped': { color: '#4338ca', bg: '#e0e7ff', text: 'Shipped', icon: 'la-shipping-fast' },
        'delivered': { color: '#065f46', bg: '#d1fae5', text: 'Completed / Delivered', icon: 'la-check-double' },
        'failed': { color: '#991b1b', bg: '#fee2e2', text: 'Payment Failed', icon: 'la-times-circle' }
    };
    
    const status = statusMap[order.status] || statusMap['pending'];
    const isPending = order.status === 'pending';
    
    let html = `
        <div class="purifydefaulttrack_order_panel">
            <div class="purifydefaulttrack_status_row">
                <div>
                    <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Order Summary</span>
                    <h3 style="margin: 4px 0; font-size: 22px; font-weight: 800; color: #0f172a;">#${escapeHtml(order.id)}</h3>
                    <div style="font-size: 13px; color: #64748b;"><i class="las la-calendar"></i> ${escapeHtml(order.date || order.created_at || '')}</div>
                </div>
                <div class="purifydefaulttrack_status_badge" style="background: ${status.bg}; color: ${status.color};">
                    <i class="las ${status.icon}"></i> ${status.text}
                </div>
            </div>

            <div class="purifydefaulttrack_items_list">
                ${(order.items || []).map(item => `
                    <div class="purifydefaulttrack_item">
                        <div>
                            <div class="purifydefaulttrack_item_title">${escapeHtml(item.title)}</div>
                            <div style="font-size: 12px; color: #64748b;">Qty: ${escapeHtml(item.quantity || 1)}</div>
                        </div>
                        <div class="purifydefaulttrack_item_price">${escapeHtml(item.total_gross)} ${escapeHtml(order.currency || '')}</div>
                    </div>
                `).join('')}
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 18px;">
                <span style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Amount</span>
                <span style="font-size: 24px; font-weight: 800; color: #0f172a;">${escapeHtml(order.total_gross)} ${escapeHtml(order.currency || '')}</span>
            </div>
        </div>
    `;

    if (isPending) {
        html += `
            <div class="purifydefaulttrack_card" style="background: #fffbeb; border-color: #fde68a; margin-bottom: 24px;">
                <div style="display: flex; gap: 16px; align-items: flex-start;">
                    <div style="width: 42px; height: 42px; background: #f59e0b; color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                        <i class="las la-wallet"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 4px; font-weight: 700; color: #92400e;">Payment Required</h4>
                        <p style="margin: 0 0 16px; font-size: 13.5px; color: #b45309;">Please complete your payment to activate this order.</p>
                        
                        <div style="display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                            ${cfg_has_paytr ? `
                                <button type="button" onclick="startPayment('${escapeHtml(order.id)}', 'paytr')" class="purifydefaulttrack_btn_primary" style="background: #f59e0b; width: 100%; justify-content: space-between;">
                                    <span><i class="las la-credit-card"></i> Pay with Card (PayTR)</span>
                                    <i class="las la-arrow-right"></i>
                                </button>
                            ` : ''}
                            ${cfg_has_bank ? `
                                <button type="button" onclick="showBankDetails()" class="purifydefaulttrack_btn_primary" style="background: white; color: #92400e; border: 1px solid #fde68a; width: 100%; justify-content: space-between;">
                                    <span><i class="las la-university"></i> Bank Transfer Details</span>
                                    <i class="las la-arrow-right"></i>
                                </button>
                                <div id="bank-details" style="display: none; background: white; border-radius: 10px; padding: 16px; margin-top: 8px; border: 1px solid #fde68a; grid-column: 1 / -1;">
                                    <pre style="overflow-x: auto; font-family: monospace; font-size: 13px; color: #4b2e00; white-space: pre-wrap;">${escapeHtml(cfg_bank_details)}</pre>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    html += `
        <div style="display: flex; justify-content: center; gap: 12px; margin-top: 16px;">
            <button type="button" onclick="resetSearch()" class="btn btn-secondary">
                <i class="las la-redo"></i> New Lookup
            </button>
            <a href="${cfg_mainurl || '/'}" class="btn btn-secondary">
                <i class="las la-home"></i> Back to Home
            </a>
        </div>
    `;
    
    results.innerHTML = html;
}

function showBankDetails() {
    const el = document.getElementById('bank-details');
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

async function startPayment(oid, method) {
    const modal = document.getElementById('sf-payment-modal');
    const container = document.getElementById('sf-payment-container');
    
    modal.style.display = 'flex';
    container.innerHTML = '<div style="text-align: center; padding: 80px;"><div class="purifydefaulttrack_spinner"></div><p>Loading secure payment gateway...</p></div>';
    
    try {
        const response = await fetch(`${cfg_mainurl}/api/ecommerce/checkout`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ retrigger_oid: oid, payment_method: method })
        });
        const res = await response.json();
        
        if (res.status === 'gateway' && res.gateway === 'paytr') {
            const iframe = document.createElement('iframe');
            iframe.style.cssText = 'width: 100%; height: 100%; border: none;';
            iframe.sandbox = 'allow-scripts allow-forms allow-same-origin allow-popups allow-top-navigation allow-top-navigation-by-user-activation';
            iframe.referrerPolicy = 'origin';
            iframe.src = `https://www.paytr.com/odeme/guvenli/${res.token}?_=${Date.now()}`;
            container.innerHTML = '';
            container.appendChild(iframe);
        } else {
            container.innerHTML = `<div style="padding: 40px; text-align: center; color: #ef4444;">${escapeHtml(res.msg || 'Unable to initialize payment.')}</div>`;
        }
    } catch (e) {
        container.innerHTML = `<div style="padding: 40px; text-align: center; color: #ef4444;">Connection error. Please try again.</div>`;
    }
}

function closePaymentModal() {
    document.getElementById('sf-payment-modal').style.display = 'none';
    document.getElementById('sf-payment-container').innerHTML = '';
}

function resetSearch() {
    const input = document.getElementById('sf-oid-input');
    input.value = '';
    document.getElementById('sf-track-results').style.display = 'none';
    document.getElementById('sf-help-section').style.display = 'block';
    document.getElementById('sf-status-feedback').style.display = 'none';
    input.focus();
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePaymentModal();
});
</script>
