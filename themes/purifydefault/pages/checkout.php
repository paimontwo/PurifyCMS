<?php
$title = "Satın Alma Protokolünü Doğrula";
$desc = "PurifyCMS varlık kaydı dağıtımlarının güvenli bir şekilde tamamlanması.";
?>

<section class="purifydefaultcheckout_section">
    <!-- purify:zone:checkout_start -->
    <div class="purifydefaultcheckout_container" id="sf-checkout-wrap">
        <header class="purifydefaultcheckout_header">
            <div>
                <h1 class="purifydefaultcheckout_title">Siparişi Tamamla</h1>
                <p class="purifydefaultcheckout_subtitle">Satın alma kaydınızı inceleyin ve ödemeyi tamamlamak için fatura bilgilerinizi girin.</p>
            </div>
        </header>

        <div class="purifydefaultcheckout_grid">
            <!-- Sol: Kimlik Bilgileri -->
            <div class="purifydefaultcheckout_main">
                <form id="sf-checkout-form">
                    <div class="purifydefaultcheckout_panel">
                        <h3 class="purifydefaultcheckout_panel_title">
                            <i class="las la-id-card"></i> 1. Kimlik Bilgileri
                        </h3>
                        
                        <div class="purifydefaultcheckout_form_row">
                            <div class="purifydefaultcheckout_input_group">
                                <label class="purifydefaultcheckout_label">Tam Ad Soyad</label>
                                <input type="text" name="name" class="purifydefaultcheckout_input" required placeholder="Örn: Ahmet Yılmaz">
                            </div>
                            <div class="purifydefaultcheckout_input_group">
                                <label class="purifydefaultcheckout_label">E-posta Adresi</label>
                                <input type="email" name="email" class="purifydefaultcheckout_input" required placeholder="ornek@domain.com">
                            </div>
                        </div>

                        <div class="purifydefaultcheckout_form_row">
                            <div class="purifydefaultcheckout_input_group">
                                <label class="purifydefaultcheckout_label">Telefon Numarası</label>
                                <input type="text" name="phone" class="purifydefaultcheckout_input" required placeholder="+90 5xx xxx xx xx">
                            </div>
                            <div class="purifydefaultcheckout_input_group">
                                <label class="purifydefaultcheckout_label">Şehir</label>
                                <input type="text" name="city" class="purifydefaultcheckout_input" required placeholder="Örn: İstanbul">
                            </div>
                        </div>

                        <div class="purifydefaultcheckout_form_row">
                            <div class="purifydefaultcheckout_input_group">
                                <label class="purifydefaultcheckout_label">İlçe</label>
                                <input type="text" name="district" class="purifydefaultcheckout_input" required placeholder="Örn: Kadıköy">
                            </div>
                            <div class="purifydefaultcheckout_input_group">
                                <label class="purifydefaultcheckout_label">Posta Kodu</label>
                                <input type="text" name="zip" class="purifydefaultcheckout_input" placeholder="34000">
                            </div>
                        </div>

                        <div class="purifydefaultcheckout_input_group">
                            <label class="purifydefaultcheckout_label">Adres (Teslimat Adresi)</label>
                            <textarea name="address" class="purifydefaultcheckout_textarea" required placeholder="Mahalle, Sokak, Bina No, Daire No..."></textarea>
                        </div>

                        <div class="purifydefaultcheckout_corporate_section">
                            <h3 class="purifydefaultcheckout_panel_title" style="font-size: 14px;">
                                <i class="las la-landmark"></i> Kurumsal Fatura Bilgileri (İsteğe Bağlı)
                            </h3>
                            <div class="purifydefaultcheckout_form_row">
                                <div class="purifydefaultcheckout_input_group">
                                    <label class="purifydefaultcheckout_label">Vergi Numarası / TCKN</label>
                                    <input type="text" name="tax_id" class="purifydefaultcheckout_input" placeholder="Vergi No / TCKN">
                                </div>
                                <div class="purifydefaultcheckout_input_group">
                                    <label class="purifydefaultcheckout_label">Vergi Dairesi</label>
                                    <input type="text" name="tax_office" class="purifydefaultcheckout_input" placeholder="Vergi Dairesi">
                                </div>
                            </div>
                            <div class="purifydefaultcheckout_input_group">
                                <label class="purifydefaultcheckout_label">Şirket Unvanı</label>
                                <input type="text" name="company" class="purifydefaultcheckout_input" placeholder="Şirket Tam Adı">
                            </div>
                        </div>
                    </div>

                    <div class="purifydefaultcheckout_panel" style="margin-top: 30px;">
                        <h3 class="purifydefaultcheckout_panel_title">
                            <i class="las la-wallet"></i> 2. Ödeme Yöntemi
                        </h3>
                        <div class="purifydefaultcheckout_gateway_list">
                            <?php if (!empty($cfg_paytr_m_id)): ?>
                                <label class="purifydefaultcheckout_gateway_option">
                                    <input type="radio" name="payment_method" value="paytr" checked>
                                    <div class="purifydefaultcheckout_gateway_meta">
                                        <div class="purifydefaultcheckout_gateway_title">
                                            <strong>Güvenli Ödeme (PayTR)</strong>
                                            <span class="purifydefaultcheckout_badge">KREDİ KARTI</span>
                                        </div>
                                        <span class="purifydefaultcheckout_gateway_desc">256-bit SSL şifreli işlem merkezi, tüm kartlarla uyumlu.</span>
                                    </div>
                                </label>
                            <?php endif; ?>

                            <?php if (!empty($cfg_bank_details)): ?>
                                <label class="purifydefaultcheckout_gateway_option">
                                    <input type="radio" name="payment_method" value="iban" <?= (empty($cfg_paytr_m_id)) ? 'checked' : '' ?>>
                                    <div class="purifydefaultcheckout_gateway_meta">
                                        <div class="purifydefaultcheckout_gateway_title">
                                            <strong>Havale / EFT (IBAN)</strong>
                                        </div>
                                        <span class="purifydefaultcheckout_gateway_desc">Doğrudan banka havalesi. Ödeme kanıtı sonrası kayıt doğrulanır.</span>
                                    </div>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Sağ: Sipariş Özeti -->
            <aside class="purifydefaultcheckout_registry">
                <div class="purifydefaultcheckout_panel">
                    <h3 class="purifydefaultcheckout_panel_title" style="margin-bottom: 20px;">Sipariş Özeti</h3>
                    
                    <div id="sf-cart-list" class="purifydefaultcheckout_cart_list">
                        <!-- Ürünler JS ile doldurulacak -->
                    </div>

                    <div class="purifydefaultcheckout_cart_actions">
                        <button type="button" class="purifydefaultcheckout_clear_btn" onclick="clearCart()">
                            <i class="las la-trash-alt"></i> Sepeti Temizle
                        </button>
                    </div>

                    <div class="purifydefaultcheckout_summary">
                        <div class="purifydefaultcheckout_summary_line">
                            <span>Ara Toplam (KDV Hariç)</span>
                            <span id="sf-subtotal">0.00</span>
                        </div>
                        <div class="purifydefaultcheckout_summary_line">
                            <span>KDV (%20)</span>
                            <span id="sf-tax">0.00</span>
                        </div>
                        <div class="purifydefaultcheckout_summary_total">
                            <span>Genel Toplam <br><small>(KDV Dahil)</small></span>
                            <span id="sf-total">0.00</span>
                        </div>
                    </div>

                    <p class="purifydefaultcheckout_security_note">
                        <i class="las la-shield-alt"></i> AES-256 ŞİFRELİ İŞLEM PROTOKOLÜ
                    </p>

                    <button type="submit" form="sf-checkout-form" class="purifydefaultcheckout_btn_primary" id="sf-submit-btn">
                        Siparişi Onayla ve Ödemeye Geç
                    </button>
                </div>
            </aside>
        </div>
    </div>
    <!-- purify:zone:checkout_end -->

    <div class="purifydefaultcheckout_container" id="sf-payment-view" style="display: none; padding: 100px 0; text-align: center;">
        <div id="sf-payment-container" style="max-width: 800px; margin: 0 auto;"></div>
    </div>
</section>

<style>

.purifydefaultcheckout_section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    min-height: 100vh;
}

.purifydefaultcheckout_container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 24px;
}

.purifydefaultcheckout_header {
    text-align: center;
    margin-bottom: 48px;
}

.purifydefaultcheckout_title {
    font-size: 2.5rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 12px;
}

.purifydefaultcheckout_subtitle {
    color: #475569;
    font-size: 1.1rem;
    font-weight: 500;
}

.purifydefaultcheckout_grid {
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 32px;
}

@media (max-width: 992px) {
    .purifydefaultcheckout_grid {
        grid-template-columns: 1fr;
    }
}

.purifydefaultcheckout_panel {
    background: #ffffff;
    border-radius: 28px;
    padding: 32px;
    box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.08);
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid rgba(226, 232, 240, 0.6);
}

.purifydefaultcheckout_panel_title {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 28px;
    border-bottom: 2px solid #eef2ff;
    padding-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.purifydefaultcheckout_panel_title i {
    color: #4f46e5;
    font-size: 22px;
}

.purifydefaultcheckout_form_row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

@media (max-width: 640px) {
    .purifydefaultcheckout_form_row {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}

.purifydefaultcheckout_input_group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.purifydefaultcheckout_label {
    font-weight: 600;
    font-size: 13px;
    color: #1e293b;
    letter-spacing: 0.3px;
}

.purifydefaultcheckout_input,
.purifydefaultcheckout_textarea {
    padding: 14px 16px;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    font-size: 14px;
    transition: all 0.2s;
    background: #fefefe;
    font-family: inherit;
}

.purifydefaultcheckout_input:focus,
.purifydefaultcheckout_textarea:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.purifydefaultcheckout_textarea {
    resize: vertical;
    min-height: 100px;
}

.purifydefaultcheckout_corporate_section {
    margin-top: 28px;
    padding-top: 24px;
    border-top: 1px dashed #e2e8f0;
}

.purifydefaultcheckout_gateway_list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.purifydefaultcheckout_gateway_option {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px 24px;
    border-radius: 20px;
    border: 2px solid #eef2ff;
    cursor: pointer;
    transition: all 0.25s;
    background: #ffffff;
}

.purifydefaultcheckout_gateway_option:hover {
    border-color: #4f46e5;
    background: #f8fafc;
    transform: translateX(4px);
}

.purifydefaultcheckout_gateway_option input[type="radio"] {
    width: 20px;
    height: 20px;
    accent-color: #4f46e5;
    margin: 0;
}

.purifydefaultcheckout_gateway_meta {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.purifydefaultcheckout_gateway_title {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.purifydefaultcheckout_gateway_title strong {
    font-size: 16px;
    color: #0f172a;
}

.purifydefaultcheckout_badge {
    background: rgba(79, 70, 229, 0.1);
    color: #4f46e5;
    padding: 4px 10px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.purifydefaultcheckout_badge_global {
    background: #e11d4820;
    color: #e11d48;
    padding: 4px 10px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 700;
}

.purifydefaultcheckout_gateway_desc {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}

.purifydefaultcheckout_registry .purifydefaultcheckout_panel {
    position: sticky;
    top: 100px;
}

.purifydefaultcheckout_cart_list {
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 16px;
}

.purifydefaultcheckout_cart_item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid #f1f5f9;
}

.purifydefaultcheckout_cart_item_info {
    flex: 1;
}

.purifydefaultcheckout_cart_item_title {
    font-weight: 700;
    font-size: 14px;
    margin: 0 0 4px;
    color: #0f172a;
}

.purifydefaultcheckout_cart_item_variants {
    font-size: 12px;
    color: #64748b;
}

.purifydefaultcheckout_cart_item_price {
    font-weight: 800;
    color: #4f46e5;
    font-size: 14px;
}

.purifydefaultcheckout_cart_item_actions {
    display: flex;
    gap: 12px;
    align-items: center;
}

.purifydefaultcheckout_icon_btn {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #94a3b8;
    transition: color 0.2s;
}

.purifydefaultcheckout_icon_btn:hover {
    color: #4f46e5;
}

.purifydefaultcheckout_icon_btn_danger:hover {
    color: #ef4444;
}

.purifydefaultcheckout_cart_actions {
    text-align: right;
    margin-bottom: 24px;
}

.purifydefaultcheckout_clear_btn {
    background: #fef2f2;
    border: none;
    padding: 8px 16px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
    color: #ef4444;
    cursor: pointer;
    transition: all 0.2s;
}

.purifydefaultcheckout_clear_btn:hover {
    background: #fee2e2;
    transform: scale(0.98);
}

.purifydefaultcheckout_summary {
    background: #f8fafc;
    border-radius: 20px;
    padding: 20px;
    margin: 20px 0;
}

.purifydefaultcheckout_summary_line {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    font-size: 14px;
    color: #334155;
    font-weight: 500;
}

.purifydefaultcheckout_summary_total {
    display: flex;
    justify-content: space-between;
    padding: 16px 0 8px;
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    border-top: 2px solid #e2e8f0;
    margin-top: 8px;
}

.purifydefaultcheckout_summary_total small {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
}

.purifydefaultcheckout_security_note {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 700;
    text-align: center;
    margin: 20px 0;
    letter-spacing: 0.5px;
}

.purifydefaultcheckout_btn_primary {
    width: 100%;
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    border: none;
    padding: 16px;
    border-radius: 24px;
    font-weight: 700;
    font-size: 16px;
    color: white;
    cursor: pointer;
    transition: all 0.25s;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.purifydefaultcheckout_btn_primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
}

.purifydefaultcheckout_btn_primary:disabled {
    opacity: 0.6;
    transform: none;
}

.purifydefaultcheckout_modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.85);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(12px);
}

.purifydefaultcheckout_modal_content {
    background: white;
    width: 90%;
    max-width: 900px;
    border-radius: 32px;
    padding: 32px;
    position: relative;
    animation: purifydefaultcheckout_fadeUp 0.3s ease;
    box-shadow: 0 40px 60px -20px rgba(0, 0, 0, 0.5);
}

@keyframes purifydefaultcheckout_fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.purifydefaultcheckout_modal_header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #eef2ff;
}

.purifydefaultcheckout_modal_title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 800;
    font-size: 18px;
}

.purifydefaultcheckout_close_btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #94a3b8;
}

.purifydefaultcheckout_spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e2e8f0;
    border-top-color: #4f46e5;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 20px auto;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
let cart = JSON.parse(localStorage.getItem('purify_cart') || '[]');

if (cart.length === 0) window.location.href = '<?= $cfg_mainurl ?>/shop';

renderCart();

function renderCart() {
    const list = document.getElementById('sf-cart-list');
    let sub = 0; let tax = 0;
    
    if (cart.length === 0) {
        list.innerHTML = '<div class="purifydefaultcheckout_cart_empty">Sepetiniz boş</div>';
        document.getElementById('sf-subtotal').innerText = '0.00 TRY';
        document.getElementById('sf-tax').innerText = '0.00 TRY';
        document.getElementById('sf-total').innerText = '0.00 TRY';
        return;
    }

    list.innerHTML = cart.map((item, idx) => {
        const itemPrice = parseFloat(item.price || 0);
        const itemTaxRate = parseFloat(item.tax_rate || 20);
        const itemTotalIncl = itemPrice * (1 + itemTaxRate / 100);
        sub += itemPrice;
        tax += (itemPrice * itemTaxRate) / 100;
        const variations = item.selected_options ? item.selected_options.map(o => o.name).join(', ') : 'Varyasyon yok';
        
        return `
            <div class="purifydefaultcheckout_cart_item">
                <div class="purifydefaultcheckout_cart_item_info">
                    <div class="purifydefaultcheckout_cart_item_title">${escapeHtml(item.title)}</div>
                    <div class="purifydefaultcheckout_cart_item_variants">${escapeHtml(variations)}</div>
                </div>
                <div class="purifydefaultcheckout_cart_item_actions">
                    <div class="purifydefaultcheckout_cart_item_price">${itemTotalIncl.toFixed(2)} TRY</div>
                    <button onclick="editItem(${idx})" class="purifydefaultcheckout_icon_btn" title="Düzenle"><i class="las la-pen"></i></button>
                    <button onclick="removeItem(${idx})" class="purifydefaultcheckout_icon_btn purifydefaultcheckout_icon_btn_danger" title="Kaldır"><i class="las la-times-circle"></i></button>
                </div>
            </div>`;
    }).join('');

    document.getElementById('sf-subtotal').innerText = sub.toFixed(2) + ' TRY';
    document.getElementById('sf-tax').innerText = tax.toFixed(2) + ' TRY';
    document.getElementById('sf-total').innerText = (sub + tax).toFixed(2) + ' TRY';
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

function editItem(idx) {
    const slug = cart[idx].slug || cart[idx].id;
    cart.splice(idx, 1);
    localStorage.setItem('purify_cart', JSON.stringify(cart));
    window.location.href = '<?= $cfg_mainurl ?>/product/' + slug;
}

function removeItem(idx) {
    cart.splice(idx, 1);
    localStorage.setItem('purify_cart', JSON.stringify(cart));
    renderCart();
    if (cart.length === 0) setTimeout(() => window.location.href = '<?= $cfg_mainurl ?>/shop', 1000);
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
    if (confirm('Sepetinizdeki tüm ürünleri silmek istediğinize emin misiniz?')) {
        cart = [];
        localStorage.setItem('purify_cart', JSON.stringify(cart));
        renderCart();
        setTimeout(() => window.location.href = '<?= $cfg_mainurl ?>/shop', 1000);
    }
}

document.getElementById('sf-checkout-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('sf-submit-btn');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'İşleniyor...';

    const formData = new FormData(e.target);
    const pMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'iban';
    const orderData = {
        customer: Object.fromEntries(formData.entries()),
        items: cart,
        payment_method: pMethod
    };

    try {
        const response = await fetch('<?= $cfg_mainurl ?>/api/ecommerce/checkout', {
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
            document.getElementById('sf-checkout-wrap').style.display = 'none';
            document.getElementById('sf-payment-view').style.display = 'block';
            
            document.getElementById('sf-payment-container').innerHTML = `
                <div class="purifydefaultcheckout_panel" style="border: 3px solid #d9f0ec; background: #f0fdf9; text-align: center; padding: 60px;">
                    <div style="font-size: 80px; color: #10b981; margin-bottom: 30px;"><i class="las la-check-circle"></i></div>
                    <h2 style="font-weight: 800; font-size: 32px; color: #0f172a;">Sipariş Alındı</h2>
                    <p style="font-size: 18px; color: #475569; margin-bottom: 40px;">
                        Sipariş numaranız: <strong>#${res.order_id}</strong><br>
                        Lütfen aşağıdaki banka bilgilerine ödemeyi gerçekleştirin:
                    </p>
                    <div style="background: white; padding: 32px; border-radius: 20px; border: 1px solid #ccf0eb; font-family: monospace; text-align: left; font-size: 16px; font-weight: 600; color: #0f3b2c;">${res.iban || 'Banka bilgileri e-posta ile gönderilecektir.'}</div>
                    <div style="margin-top: 40px;">
                        <a href="<?= $cfg_mainurl ?>" class="purifydefaultcheckout_btn_primary" style="display: inline-block; padding: 12px 32px; text-decoration: none;">Ana Sayfaya Dön</a>
                    </div>
                </div>
            `;
        } else {
            alert(res.msg || res.message || "Sipariş oluşturulurken bir hata oluştu.");
        }
    } catch (err) {
        console.error(err);
        alert('Sunucu ile bağlantı kurulamadı.');
    } finally {
        btn.innerText = originalText;
        btn.disabled = false;
    }
});

function handleGateway(res) {
    if (res.gateway === 'paytr') {
        showModal(`
            <div class="purifydefaultcheckout_modal_content" style="max-width: 850px; height: 90vh; display: flex; flex-direction: column;">
                <div class="purifydefaultcheckout_modal_header">
                    <div class="purifydefaultcheckout_modal_title">
                        <div style="width: 36px; height: 36px; background: #4f46e5; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                            <i class="las la-shield-alt"></i>
                        </div>
                        <span>PayTR Güvenli Ödeme</span>
                    </div>
                    <button class="purifydefaultcheckout_close_btn" onclick="location.reload()"><i class="las la-times"></i></button>
                </div>
                <div style="flex: 1; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0;">
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
    // Close modal on backdrop click?
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.remove();
    });
}
</script>