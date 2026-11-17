<?php
$prefilled_oid = $_GET['oid'] ?? '';
$auto_pay = $_GET['pay'] ?? '';
$payment_status = $_GET['status'] ?? '';

require_once __DIR__ . '/../../../inc/PurifyTrade.php';

$order = null;
if (!empty($prefilled_oid)) {
    $order = PurifyTrade::getOrder($prefilled_oid);
}

if ($payment_status === 'success') {
    $title = "İşlem Onaylandı - PurifyCMS";
    $desc = "Siparişiniz başarıyla doğrulandı. Lojistik ve takip detaylarını buradan inceleyin.";
} elseif ($payment_status === 'fail') {
    $title = "Ödeme Hatası - PurifyCMS";
    $desc = "Sipariş ödemesi tamamlanamadı. Lütfen alternatif bir yöntem deneyin.";
} else {
    $title = "Sipariş Takibi - PurifyCMS";
    $desc = "Siparişinizin anlık durumunu ve sevkiyat bilgilerini sorgulayın.";
}
?>

<section class="purifydefaulttrack_section">
    <!-- purify:zone:track_start -->
    <div class="purifydefaulttrack_container">
        
        <header class="purifydefaulttrack_header">
            <div class="purifydefaulttrack_icon_wrap">
                <i class="las la-shipping-fast"></i>
            </div>
            <h1 class="purifydefaulttrack_title">Sipariş Merkezi</h1>
            <p class="purifydefaulttrack_subtitle">256-bit şifreli altyapı ile siparişinizi gerçek zamanlı takip edin.</p>
        </header>

        <div class="purifydefaulttrack_card purifydefaulttrack_search_hub">
            <div class="purifydefaulttrack_input_group">
                <div class="purifydefaulttrack_input_inner">
                    <label class="purifydefaulttrack_label"><i class="las la-hashtag"></i> Protokol Numarası (OID)</label>
                    <input type="text" id="sf-oid-input" 
                           class="purifydefaulttrack_input" 
                           placeholder="PRFY2026..." 
                           value="<?= htmlspecialchars($prefilled_oid) ?>" 
                           spellcheck="false">
                </div>
                <button type="button" class="purifydefaulttrack_btn_primary" onclick="lookupOrder()" id="sf-lookup-btn">
                    <i class="las la-search"></i> <span>Sorgula</span>
                </button>
            </div>
            
            <div id="sf-recent-orders-wrap" class="purifydefaulttrack_history" style="display: none;">
                <span class="purifydefaulttrack_history_label"><i class="las la-history"></i> Son Sorgulananlar</span>
                <div id="sf-recent-orders-list" class="purifydefaulttrack_history_list"></div>
            </div>
        </div>

        <div id="sf-status-feedback">
            <?php if ($payment_status === 'success'): ?>
            <div class="purifydefaulttrack_alert purifydefaulttrack_alert_success animate_fade_up">
                <div class="purifydefaulttrack_alert_icon"><i class="las la-check-circle"></i></div>
                <div class="purifydefaulttrack_alert_content">
                    <h3>Ödeme Doğrulandı</h3>
                    <p>Siparişiniz başarıyla sisteme kaydedildi ve hazırlık aşamasına geçildi.</p>
                    <?php if ($order): ?>
                        <div class="purifydefaulttrack_oid_badge">#<?= htmlspecialchars($order['id']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php elseif ($payment_status === 'fail'): ?>
            <div class="purifydefaulttrack_alert purifydefaulttrack_alert_danger animate_fade_up">
                <div class="purifydefaulttrack_alert_icon"><i class="las la-exclamation-triangle"></i></div>
                <div class="purifydefaulttrack_alert_content">
                    <h3>Ödeme Başarısız</h3>
                    <p>Banka provizyonu sırasında bir sorun oluştu. Lütfen bilgilerinizi kontrol edip tekrar deneyin.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div id="sf-loading" class="purifydefaulttrack_loader" style="display: none;">
            <div class="purifydefaulttrack_spinner"></div>
            <p>Sistem veritabanı sorgulanıyor...</p>
        </div>

        <div id="sf-track-results" class="purifydefaulttrack_results_area" style="display: none;"></div>

        <!-- Instruction: Help & Guidance -->
        <div id="sf-help-section" class="purifydefaulttrack_help">
            <div class="purifydefaulttrack_help_icon"><i class="las la-question-circle"></i></div>
            <h3>Sipariş numaramı nerede bulabilirim?</h3>
            <p>Ödeme sonrası size gönderilen onay e-postasında veya SMS bildiriminde yer alan <strong>#PRFY</strong> ile başlayan kodu kullanabilirsiniz.</p>
        </div>

    </div>
    <!-- purify:zone:track_end -->

    <!-- Modal: Payment Retention Hub -->
    <div id="sf-payment-modal" class="purifydefaulttrack_modal" style="display: none;">
        <div class="purifydefaulttrack_modal_content">
            <div class="purifydefaulttrack_modal_header">
                <h3><i class="las la-shield-alt"></i> Güvenli İşlem Terminali</h3>
                <button onclick="closePaymentModal()" class="purifydefaulttrack_close_btn"><i class="las la-times"></i></button>
            </div>
            <div id="sf-payment-container" class="purifydefaulttrack_modal_body"></div>
        </div>
    </div>
</section>

<style>

.purifydefaulttrack_section {
    min-height: 100vh;
    padding: 80px 0;
    background: #f8fafc;
    background-image: 
        radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.03) 0, transparent 50%),
        radial-gradient(at 50% 0%, rgba(99, 102, 241, 0.02) 0, transparent 50%);
}

.purifydefaulttrack_container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 24px;
}
.purifydefaulttrack_header {
    text-align: center;
    margin-bottom: 50px;
}

.purifydefaulttrack_icon_wrap {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: white;
    font-size: 32px;
    box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
    transform: rotate(-3deg);
}

.purifydefaulttrack_title {
    font-size: 36px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin-bottom: 10px;
}

.purifydefaulttrack_subtitle {
    color: #64748b;
    font-size: 16px;
    font-weight: 500;
}
.purifydefaulttrack_card {
    background: white;
    border-radius: 24px;
    padding: 30px;
    border: 1px solid rgba(226, 232, 240, 0.8);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 20px 25px -5px rgba(0, 0, 0, 0.03);
}

.purifydefaulttrack_search_hub {
    margin-bottom: 30px;
}
.purifydefaulttrack_input_group {
    display: flex;
    gap: 15px;
    align-items: flex-end;
}

.purifydefaulttrack_input_inner {
    flex: 1;
}

.purifydefaulttrack_label {
    display: block;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 8px;
    letter-spacing: 0.05em;
}

.purifydefaulttrack_input {
    width: 100%;
    padding: 16px 20px;
    background: #f1f5f9;
    border: 2px solid transparent;
    border-radius: 14px;
    font-size: 16px;
    font-weight: 700;
    font-family: inherit;
    color: #0f172a;
    transition: all 0.2s;
}

.purifydefaulttrack_input:focus {
    outline: none;
    background: white;
    border-color: #4f46e5;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}

.purifydefaulttrack_btn_primary {
    height: 58px;
    padding: 0 30px;
    background: #0f172a;
    color: white;
    border: none;
    border-radius: 14px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.purifydefaulttrack_btn_primary:hover {
    background: #1e293b;
    transform: translateY(-1px);
}

.purifydefaulttrack_btn_primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.purifydefaulttrack_history {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #f1f5f9;
}

.purifydefaulttrack_history_label {
    font-size: 11px;
    font-weight: 800;
    color: #94a3b8;
    display: block;
    margin-bottom: 12px;
}

.purifydefaulttrack_history_list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.purifydefaulttrack_history_item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
}

.purifydefaulttrack_history_item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #0f172a;
}
.purifydefaulttrack_alert {
    display: flex;
    gap: 20px;
    padding: 24px;
    border-radius: 20px;
    margin-bottom: 30px;
}

.purifydefaulttrack_alert_success {
    background: #f0fdf4;
    border: 1.5px solid #dcfce7;
}

.purifydefaulttrack_alert_danger {
    background: #fef2f2;
    border: 1.5px solid #fee2e2;
}

.purifydefaulttrack_alert_icon {
    font-size: 32px;
}

.purifydefaulttrack_alert_success .purifydefaulttrack_alert_icon { color: #10b981; }
.purifydefaulttrack_alert_danger .purifydefaulttrack_alert_icon { color: #ef4444; }

.purifydefaulttrack_alert_content h3 {
    font-size: 18px;
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
    margin-top: 12px;
    padding: 4px 12px;
    background: white;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 800;
    font-family: monospace;
    color: #065f46;
}
.purifydefaulttrack_loader {
    text-align: center;
    padding: 60px 0;
}

.purifydefaulttrack_spinner {
    width: 40px;
    height: 40px;
    border: 3.5px solid #e2e8f0;
    border-top-color: #4f46e5;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 15px;
}
.purifydefaulttrack_order_panel {
    background: white;
    border-radius: 24px;
    padding: 32px;
    border: 1px solid #eef2ff;
    margin-bottom: 30px;
}

.purifydefaulttrack_status_row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.purifydefaulttrack_status_badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 800;
    font-size: 13px;
}

.purifydefaulttrack_items_list {
    margin: 25px 0;
    border-top: 1px solid #f1f5f9;
}

.purifydefaulttrack_item {
    display: flex;
    justify-content: space-between;
    padding: 18px 0;
    border-bottom: 1px solid #f1f5f9;
}

.purifydefaulttrack_item_title {
    font-weight: 700;
    color: #0f172a;
}

.purifydefaulttrack_item_price {
    font-weight: 800;
    color: #4f46e5;
}
.purifydefaulttrack_help {
    text-align: center;
    padding: 40px;
    background: #f8fafc;
    border: 1px dashed #e2e8f0;
    border-radius: 24px;
}

.purifydefaulttrack_help_icon {
    font-size: 40px;
    color: #cbd5e1;
    margin-bottom: 15px;
}

.purifydefaulttrack_help h3 {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
}

.purifydefaulttrack_help p {
    font-size: 14px;
    color: #64748b;
    max-width: 400px;
    margin: 0 auto;
}

.purifydefaulttrack_modal {
    position: fixed;
    top: 0; left: 0; width: 100vw; height: 100vh;
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(8px);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.purifydefaulttrack_modal_content {
    background: white;
    width: 90%;
    max-width: 850px;
    height: 85vh;
    border-radius: 28px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.purifydefaulttrack_modal_header {
    padding: 24px 30px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.purifydefaulttrack_modal_header h3 {
    margin: 0; font-size: 18px; font-weight: 800; display: flex; align-items: center; gap: 10px;
}

.purifydefaulttrack_close_btn {
    background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; transition: color 0.2s;
}

.purifydefaulttrack_modal_body {
    flex: 1; min-height: 0;
}

@keyframes spin { to { transform: rotate(360deg); } }
@keyframes purifydefaulttrack_fadeUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
.animate_fade_up { animation: purifydefaulttrack_fadeUp 0.4s ease forwards; }

@media (max-width: 640px) {
    .purifydefaulttrack_input_group { flex-direction: column; }
    .purifydefaulttrack_btn_primary { width: 100%; justify-content: center; }
    .purifydefaulttrack_header { margin-bottom: 30px; }
    .purifydefaulttrack_title { font-size: 28px; }
}
</style>

<script>
// Stabilization Layer
const cfg_mainurl = <?= json_encode($cfg_mainurl ?? '') ?>;
const cfg_has_paytr = <?= json_encode(!empty($cfg_paytr_m_id)) ?>;
const cfg_has_bank = <?= json_encode(!empty($cfg_bank_details)) ?>;
const cfg_bank_details = <?= json_encode($cfg_bank_details ?? '') ?>;

// Initialization Logic
document.addEventListener('DOMContentLoaded', () => {
    const prefilled = '<?= $prefilled_oid ?>';
    if (prefilled) {
        setTimeout(() => lookupOrder(), 300);
    }
    renderRecentOrders();
});

function saveToHistory(oid) {
    if (!oid || oid.length < 10) return;
    try {
        let history = JSON.parse(localStorage.getItem('purify_order_history') || '[]');
        oid = oid.trim().toUpperCase();
        if (!history.includes(oid)) {
            history.unshift(oid);
            history = history.slice(0, 4);
            localStorage.setItem('purify_order_history', JSON.stringify(history));
            renderRecentOrders();
        }
    } catch (e) { console.warn("Persistence Error", e); }
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
        <button type="button" onclick="quickLookup('${oid}')" class="purifydefaulttrack_history_item">
            #${oid}
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
        input.classList.add('shake');
        setTimeout(() => input.classList.remove('shake'), 500);
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
        const response = await fetch(`${cfg_mainurl}/api/ecommerce/order-lookup?oid=${oid}`);
        const res = await response.json();

        loading.style.display = 'none';
        
        if (res.success && res.order) {
            saveToHistory(oid);
            renderOrder(res.order);
        } else {
            results.innerHTML = `
                <div class="purifydefaulttrack_card animate_fade_up" style="text-align: center; border-color: #fee2e2;">
                    <i class="las la-search-minus" style="font-size: 60px; color: #ef4444; margin-bottom: 20px;"></i>
                    <h3 style="color: #ef4444; font-weight: 800;">Sipariş Bulunamadı</h3>
                    <p style="color: #64748b; margin-bottom: 25px;">${res.message || 'Girdiğiniz numara ile eşleşen bir kayıt bulunamadı.'}</p>
                    <button onclick="resetSearch()" class="purifydefaulttrack_btn_primary" style="margin: 0 auto; background: #ef4444;">Tekrar Dene</button>
                </div>
            `;
            results.style.display = 'block';
        }
    } catch (e) {
        loading.style.display = 'none';
        results.innerHTML = `<div class="purifydefaulttrack_alert purifydefaulttrack_alert_danger">Bağlantı hatası oluştu.</div>`;
        results.style.display = 'block';
    } finally {
        btn.disabled = false;
    }
}

function renderOrder(order) {
    const results = document.getElementById('sf-track-results');
    results.style.display = 'block';
    
    const statusMap = {
        'pending': { color: '#f59e0b', bg: '#fffbeb', text: 'Ödeme Bekliyor', icon: 'la-clock' },
        'paid': { color: '#10b981', bg: '#f0fdf4', text: 'Ödendi / Onaylandı', icon: 'la-check-circle' },
        'processing': { color: '#3b82f6', bg: '#eff6ff', text: 'Hazırlanıyor', icon: 'la-box' },
        'shipped': { color: '#6366f1', bg: '#eef2ff', text: 'Sevk Edildi', icon: 'la-shipping-fast' },
        'delivered': { color: '#059669', bg: '#ecfdf5', text: 'Teslim Edildi', icon: 'la-home' },
        'failed': { color: '#ef4444', bg: '#fef2f2', text: 'İşlem Başarısız', icon: 'la-times-circle' }
    };
    
    const status = statusMap[order.status] || statusMap['pending'];
    const isPending = order.status === 'pending';
    
    let html = `
        <div class="purifydefaulttrack_order_panel animate_fade_up">
            <div class="purifydefaulttrack_status_row">
                <div>
                    <span style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800;">Sipariş Bilgisi</span>
                    <h3 style="margin: 4px 0; font-size: 24px; font-weight: 800; color: #0f172a;">#${order.id}</h3>
                    <div style="font-size: 13px; color: #64748b;"><i class="las la-calendar"></i> ${order.date}</div>
                </div>
                <div class="purifydefaulttrack_status_badge" style="background: ${status.bg}; color: ${status.color};">
                    <i class="las ${status.icon}"></i> ${status.text}
                </div>
            </div>

            <div class="purifydefaulttrack_items_list">
                ${order.items.map(item => `
                    <div class="purifydefaulttrack_item">
                        <div>
                            <div class="purifydefaulttrack_item_title">${item.title}</div>
                            <div style="font-size: 12px; color: #94a3b8;">Miktar: 1</div>
                        </div>
                        <div class="purifydefaulttrack_item_price">${item.total_gross} ${order.currency}</div>
                    </div>
                `).join('')}
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                <span style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase;">Toplam Tutar</span>
                <span style="font-size: 28px; font-weight: 800; color: #0f172a;">${order.total_gross} ${order.currency}</span>
            </div>
        </div>
    `;

    if (isPending) {
        html += `
            <div class="purifydefaulttrack_card animate_fade_up" style="background: #fffbeb; border-color: #fde68a; margin-bottom: 30px;">
                <div style="display: flex; gap: 20px; align-items: flex-start;">
                    <div style="width: 48px; height: 48px; background: #f59e0b; color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                        <i class="las la-wallet"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 4px; font-weight: 800; color: #92400e;">Ödeme Tamamlanmadı</h4>
                        <p style="margin: 0 0 20px; font-size: 14px; color: #b45309;">Siparişinizi aktifleştirmek için lütfen ödeme işlemini gerçekleştirin.</p>
                        
                        <div style="display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                            ${cfg_has_paytr ? `
                                <button onclick="startPayment('${order.id}', 'paytr')" class="purifydefaulttrack_btn_primary" style="background: #f59e0b; width: 100%; justify-content: space-between;">
                                    <span><i class="las la-credit-card"></i> Kredi Kartı (PayTR)</span>
                                    <i class="las la-arrow-right"></i>
                                </button>
                            ` : ''}
                            ${cfg_has_bank ? `
                                <button onclick="showBankDetails()" class="purifydefaulttrack_btn_primary" style="background: white; color: #92400e; border: 1.5px solid #fde68a; width: 100%; justify-content: space-between;">
                                    <span><i class="las la-university"></i> Havale / EFT</span>
                                    <i class="las la-arrow-right"></i>
                                </button>
                                <div id="bank-details" style="display: none; background: white; border-radius: 12px; padding: 20px; margin-top: 10px; border: 1px solid #fde68a; grid-column: 1 / -1;">
                                    <pre style="overflow-x: auto; font-family: monospace; font-size: 13px; color: #4b2e00; white-space: pre-wrap;">${cfg_bank_details}</pre>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    html += `
        <div style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">
            <button onclick="resetSearch()" class="purifydefaulttrack_btn_primary" style="background: #f1f5f9; color: #475569;">
                <i class="las la-redo"></i> Yeni Sorgulama
            </button>
            <a href="${cfg_mainurl}" class="purifydefaulttrack_btn_primary" style="background: transparent; color: #94a3b8; border: 1px solid #e2e8f0;">
                <i class="las la-home"></i> Ana Sayfa
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
    container.innerHTML = '<div style="text-align: center; padding: 100px;"><div class="purifydefaulttrack_spinner"></div><p>Güvenli ödeme portalı yükleniyor...</p></div>';
    
    try {
        const response = await fetch(`${cfg_mainurl}/api/ecommerce/checkout`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ retrigger_oid: oid, payment_method: method })
        });
        const res = await response.json();
        
        if (res.status === 'gateway') {
            if (res.gateway === 'paytr') {
                const iframe = document.createElement('iframe');
                iframe.style.cssText = 'width: 100%; height: 100%; border: none;';
                iframe.sandbox = 'allow-scripts allow-forms allow-same-origin allow-popups allow-top-navigation allow-top-navigation-by-user-activation';
                iframe.referrerPolicy = 'origin';
                iframe.src = `https://www.paytr.com/odeme/guvenli/${res.token}?_=${Date.now()}`;
                container.innerHTML = '';
                container.appendChild(iframe);
            }
        } else {
            container.innerHTML = `<div style="padding: 50px; text-align: center; color: #ef4444;">${res.msg || 'İşlem başlatılamadı.'}</div>`;
        }
    } catch (e) {
        container.innerHTML = `<div style="padding: 50px; text-align: center; color: #ef4444;">Bağlantı hatası.</div>`;
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
