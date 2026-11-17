<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

require_once __DIR__ . '/../inc/config.php';
$base_url = rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path;

$error = '';
$success = '';

// Session flash messages for PRG pattern
$flash_success = '';
$flash_error = '';
if (isset($_SESSION['flash_success'])) {
    $flash_success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $flash_error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

if (isset($_GET['action']) && $_GET['action'] === 'flush_cache') {
    if (class_exists('PurifyVolt')) PurifyVolt::flushHtml();
    $success = p_lang('cache_cleared', 'HTML Cache System successfully flushed. Next hit will generate fresh static pages.');
}

// AJAX SMTP Test Handler
if (isset($_GET['ajax']) && $_GET['ajax'] === 'test_smtp' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'CSRF Token mismatch.']);
        exit;
    }
    
    require_once __DIR__ . '/../inc/PurifyMailer.php';
    try {
        $res = PurifyMailer::test([
            'host' => $_POST['cfg_smtp_host'] ?? '',
            'port' => $_POST['cfg_smtp_port'] ?? '',
            'user' => $_POST['cfg_smtp_user'] ?? '',
            'pass' => $_POST['cfg_smtp_pass'] ?? '',
            'secure' => $_POST['cfg_smtp_secure'] ?? '',
            'to' => $_POST['cfg_contact_email'] ?? ''
        ]);
        echo json_encode(['success' => true, 'message' => 'Test email sent successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $new_mainurl = $_POST['cfg_mainurl'] ?? '';
    $new_robots = isset($_POST['cfg_robots']) ? 'true' : 'false';
    $new_trust_proxies = isset($_POST['cfg_trust_proxies']) ? 'true' : 'false';
    $new_domain = $_POST['cfg_domain'] ?? '';
    $new_logolink = $_POST['cfg_logolink'] ?? '';
    $new_favicon = $_POST['cfg_favicon'] ?? '';
    $new_support = $_POST['cfg_support'] ?? '';
    $new_name = $_POST['cfg_name'] ?? '';
    $new_legalname = $_POST['cfg_legalname'] ?? '';
    $new_googleanalytics = $_POST['cfg_googleanalytics'] ?? '';
    $new_social_linkedin = $_POST['cfg_social_linkedin'] ?? '';
    $new_social_instagram = $_POST['cfg_social_instagram'] ?? '';
    $new_social_x = $_POST['cfg_social_x'] ?? '';
    $new_social_facebook = $_POST['cfg_social_facebook'] ?? '';
    
    $new_admin_path = trim($_POST['cfg_admin_path'] ?? 'admin');
    $new_admin_path = preg_replace('/[^a-zA-Z0-9_-]/', '', $new_admin_path);
    if (empty($new_admin_path)) $new_admin_path = 'admin';
    $new_admin_ips = trim($_POST['cfg_admin_ips'] ?? '');
    $new_admin_lang = $_POST['cfg_admin_lang'] ?? 'tr';
    if (session_status() !== PHP_SESSION_NONE) {
        $_SESSION['p_admin_lang'] = $new_admin_lang;
    }
    $new_admin_sidebar_color = $_POST['cfg_admin_sidebar_color'] ?? '#0f172a';
    
    $new_smtp_host = $_POST['cfg_smtp_host'] ?? '';
    $new_smtp_port = $_POST['cfg_smtp_port'] ?? '';
    $new_smtp_user = $_POST['cfg_smtp_user'] ?? '';
    $new_smtp_pass = $_POST['cfg_smtp_pass'] ?? '';
    $new_smtp_secure = $_POST['cfg_smtp_secure'] ?? '';
    $new_contact_email = $_POST['cfg_contact_email'] ?? '';

    $new_enable_filemanager = isset($_POST['cfg_enable_filemanager']) ? 'true' : 'false';
    $new_enable_analytics = isset($_POST['cfg_enable_analytics']) ? 'true' : 'false';
    $new_enable_captcha = isset($_POST['cfg_enable_captcha']) ? 'true' : 'false';
    $new_enable_ecommerce = isset($_POST['cfg_enable_ecommerce']) ? 'true' : 'false';
    $new_maintenance_mode = isset($_POST['cfg_maintenance_mode']) ? 'true' : 'false';
    $new_maintenance_msg = $_POST['cfg_maintenance_msg'] ?? '';
    $new_readonly_mode = (isset($_POST['cfg_readonly_mode']) && $_POST['cfg_readonly_mode'] === 'true') ? 'true' : 'false';

    $new_ecommerce_currency = $_POST['cfg_ecommerce_currency'] ?? 'TRY';
    $new_paytr_m_id = $_POST['cfg_paytr_m_id'] ?? '';
    $new_paytr_m_key = $_POST['cfg_paytr_m_key'] ?? '';
    $new_paytr_m_salt = $_POST['cfg_paytr_m_salt'] ?? '';
    $new_paytr_test = isset($_POST['cfg_paytr_test']) ? '1' : '0';
    $new_bank_details = $_POST['cfg_bank_details'] ?? '';

    $config_content = "<?php\n";
    $config_content .= "// PurifyCMS v4.0.0 Configuration Manifest\n";
    $config_content .= "// Generated on: " . date('Y-m-d H:i:s') . "\n";
    $config_content .= "defined('DIR_ACCESS') or die('Absolute access denied.');\n\n";

    $config_content .= "// --- System Latency & Critical Routing ---\n";
    $config_content .= "\$cfg_mainurl = '" . addslashes($new_mainurl) . "';\n";
    $config_content .= "\$cfg_admin_path = '" . addslashes($new_admin_path) . "';\n";
    $config_content .= "\$cfg_admin_ips = '" . addslashes($new_admin_ips) . "';\n";
    $config_content .= "\$cfg_trust_proxies = " . $new_trust_proxies . ";\n";
    $config_content .= "\$cfg_robots = $new_robots;\n";
    $config_content .= "\$cfg_domain = '" . addslashes($new_domain) . "';\n";
    $config_content .= "\$cfg_readonly_mode = $new_readonly_mode;\n";
    $config_content .= "\$cfg_maintenance_mode = $new_maintenance_mode;\n";
    $config_content .= "\$cfg_maintenance_msg = '" . addslashes($new_maintenance_msg) . "';\n\n";

    $config_content .= "// --- Branding & Identity ---\n";
    $config_content .= "\$cfg_name = '" . addslashes($new_name) . "';\n";
    $config_content .= "\$cfg_legalname = '" . addslashes($new_legalname) . "';\n";
    $config_content .= "\$cfg_logolink = '" . addslashes($new_logolink) . "';\n";
    $config_content .= "\$cfg_favicon = '" . addslashes($new_favicon) . "';\n";
    $config_content .= "\$cfg_support = '" . addslashes($new_support) . "';\n";
    $config_content .= "\$cfg_googleanalytics = '" . addslashes($new_googleanalytics) . "';\n\n";

    $config_content .= "// --- Social Graph & Metadata ---\n";
    $config_content .= "\$cfg_social_linkedin = '" . addslashes($new_social_linkedin) . "';\n";
    $config_content .= "\$cfg_social_instagram = '" . addslashes($new_social_instagram) . "';\n";
    $config_content .= "\$cfg_social_x = '" . addslashes($new_social_x) . "';\n";
    $config_content .= "\$cfg_social_facebook = '" . addslashes($new_social_facebook) . "';\n\n";
    
    $config_content .= "// --- Communication (SMTP) ---\n";
    $config_content .= "\$cfg_smtp_host = '" . addslashes($new_smtp_host) . "';\n";
    $config_content .= "\$cfg_smtp_port = '" . addslashes($new_smtp_port) . "';\n";
    $config_content .= "\$cfg_smtp_user = '" . addslashes($new_smtp_user) . "';\n";
    $config_content .= "\$cfg_smtp_pass = '" . addslashes($new_smtp_pass) . "';\n";
    $config_content .= "\$cfg_smtp_secure = '" . addslashes($new_smtp_secure) . "';\n";
    $config_content .= "\$cfg_contact_email = '" . addslashes($new_contact_email) . "';\n\n";
    
    $config_content .= "// --- Theme & Appearance ---\n";
    $config_content .= "\$cfg_active_theme = '" . addslashes($cfg_active_theme ?? 'purifydefault') . "';\n";
    $config_content .= "\$cfg_admin_sidebar_color = '" . addslashes($new_admin_sidebar_color) . "';\n";
    $config_content .= "\$cfg_admin_lang = '" . addslashes($new_admin_lang) . "';\n\n";

    $config_content .= "// --- Admin Orchestration ---\n";
    $config_content .= "\$cfg_admin_user = '" . addslashes($cfg_admin_user ?? 'admin') . "';\n";
    $config_content .= "\$cfg_admin_passhash = '" . addslashes($cfg_admin_passhash ?? '') . "';\n\n";

    $config_content .= "// --- System Controls ---\n";
    $config_content .= "\$cfg_enable_filemanager = $new_enable_filemanager;\n";
    $config_content .= "\$cfg_enable_analytics = $new_enable_analytics;\n";
    $config_content .= "\$cfg_enable_captcha = $new_enable_captcha;\n\n";

    $config_content .= "// --- Fiscal Gateway Manifest (v4.0.0) ---\n";
    $config_content .= "\$cfg_enable_ecommerce = $new_enable_ecommerce;\n";
    $config_content .= "\$cfg_ecommerce_currency = '" . addslashes($new_ecommerce_currency) . "';\n";
    $config_content .= "\$cfg_paytr_m_id = '" . addslashes($new_paytr_m_id) . "';\n";
    $config_content .= "\$cfg_paytr_m_key = '" . addslashes($new_paytr_m_key) . "';\n";
    $config_content .= "\$cfg_paytr_m_salt = '" . addslashes($new_paytr_m_salt) . "';\n";
    $config_content .= "\$cfg_paytr_test = $new_paytr_test;\n";
    $config_content .= "\$cfg_bank_details = '" . addslashes($new_bank_details) . "';\n\n";

    $config_content .= "// --- Kernel & Integrity ---\n";
    $config_content .= "error_reporting(0);\n";
    $config_content .= "ini_set('display_errors', 0);\n";

    if (file_put_contents(__DIR__ . '/../inc/config.php', $config_content, LOCK_EX) !== false) {
        if (class_exists('PurifyVolt')) PurifyVolt::flushHtml();
        $success = p_lang('success', 'Settings updated successfully.');
        
        // Removed blocking SMTP test from save flow (now handled via AJAX)
        
        // PRG Pattern: Redirect with flash message to prevent form resubmission
        $_SESSION['flash_success'] = $success;
        if ($error) {
            $_SESSION['flash_error'] = $error;
        }
        
        // Store active tab to restore after redirect
        $_SESSION['settings_active_tab'] = $_POST['active_tab'] ?? 'general';
        
        $redirect_url = rtrim($new_mainurl, '/') . '/' . $new_admin_path;
        if ($new_admin_path !== $cfg_admin_path) {
            header("Location: " . $redirect_url . '/settings?success=1');
            exit;
        }
        
        // Always redirect to prevent form resubmission
        header("Location: " . $redirect_url . '/settings?saved=1');
        exit;
    } else {
        $error = p_lang('config_write_error', 'Failed to write to inc/config.php. Check directory permissions.');
    }
}

if (isset($_GET['success'])) {
    $success = p_lang('settings_updated_success', 'Settings updated successfully.');
}

$admin_page_title = p_lang('settings', 'General Settings');
$admin_active_nav = 'settings';
require __DIR__ . '/includes/header.php';
?>

<style>
.settings-layout { display: flex; gap: 30px; align-items: start; min-height: 600px; }
.settings-tabs { width: 240px; flex-shrink: 0; background: var(--panel-bg); border: 1px solid var(--panel-border); border-radius: var(--radius); padding: 10px; position: sticky; top: 100px; }
.settings-form { flex-grow: 1; min-width: 0; }
@media (max-width: 992px) {
    .settings-layout { flex-direction: column; gap: 20px; }
    .settings-tabs { width: 100%; position: static; display: flex; overflow-x: auto; gap: 8px; padding: 5px; background: transparent; border: none; }
    .settings-tabs .tab-btn { white-space: nowrap; flex-shrink: 0; padding: 10px 20px; border: 1px solid var(--panel-border); background: var(--panel-bg); }
    .settings-form { width: 100%; }
}
</style>

<?php if ($flash_success || $success): ?>
    <div class="notice notice-success"><i class="las la-check-circle"></i> <?= htmlspecialchars($flash_success ?: $success) ?></div>
<?php endif; ?>
<?php if ($flash_error || $error): ?>
    <div class="notice notice-error"><i class="las la-exclamation-circle"></i> <?= htmlspecialchars($flash_error ?: $error) ?></div>
<?php endif; ?>

<!-- Save Preloader Overlay -->
<div id="savePreloader" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.95); z-index: 10000; justify-content: center; align-items: center; flex-direction: column; gap: 24px;">
    <div style="width: 64px; height: 64px; border: 4px solid rgba(99, 102, 241, 0.2); border-top-color: #6366f1; border-radius: 50%; animation: spin 1s linear infinite;"></div>
    <div style="color: #f8fafc; font-size: 18px; font-weight: 600; letter-spacing: -0.02em;">
        <i class="las la-save" style="margin-right: 12px;"></i><?= p_lang('saving_changes', 'Saving changes...') ?>
    </div>
    <div style="color: #64748b; font-size: 13px;"><?= p_lang('please_wait', 'Please wait while we update your configuration.') ?></div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
    
<div class="settings-layout">
    <aside class="settings-tabs">
        <button type="button" class="tab-btn active" data-tab="general">
            <i class="las la-globe"></i> <span><?= p_lang('general', 'General') ?></span>
        </button>
        <button type="button" class="tab-btn" data-tab="security">
            <i class="las la-shield-alt"></i> <span><?= p_lang('security', 'Security') ?></span>
        </button>
        <button type="button" class="tab-btn" data-tab="ecommerce">
            <i class="las la-shopping-cart"></i> <span><?= p_lang('ecommerce', 'E-Commerce') ?></span>
        </button>
        <button type="button" class="tab-btn" data-tab="adminpanel">
            <i class="las la-palette"></i> <span><?= p_lang('admin_panel', 'Admin Panel') ?></span>
        </button>
    </aside>

    <form method="post" class="settings-form" id="settingsForm">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <input type="hidden" name="active_tab" id="activeTabInput" value="<?= $_SESSION['settings_active_tab'] ?? 'general' ?>">
        
        <!-- General Tab -->
        <div class="tab-content active" id="general">
            <div class="panel">
                <h2 style="margin-top:0;"><?= p_lang('core_config', 'Core Configuration') ?></h2>
                <div class="input-group">
                    <label for="cfg_name"><?= p_lang('site_name', 'Site Name') ?></label>
                    <input type="text" id="cfg_name" name="cfg_name" class="form-control" value="<?= htmlspecialchars($cfg_name) ?>">
                </div>
                <div class="input-group">
                    <label for="cfg_mainurl"><?= p_lang('main_url', 'Main Site URL') ?></label>
                    <input type="text" id="cfg_mainurl" name="cfg_mainurl" class="form-control" value="<?= htmlspecialchars($cfg_mainurl) ?>">
                </div>
                <div class="input-group">
                    <label for="cfg_domain"><?= p_lang('domain_name', 'Domain Name') ?></label>
                    <input type="text" id="cfg_domain" name="cfg_domain" class="form-control" value="<?= htmlspecialchars($cfg_domain) ?>">
                </div>
                <div class="input-group">
                    <label for="cfg_legalname"><?= p_lang('legal_name', 'Legal Entity Name') ?></label>
                    <input type="text" id="cfg_legalname" name="cfg_legalname" class="form-control" value="<?= htmlspecialchars($cfg_legalname) ?>">
                </div>
                <div class="input-group">
                    <label for="cfg_admin_lang"><?= p_lang('default_language', 'Default Admin Language') ?></label>
                    <?php $effective_lang = PurifyKernel::get_active_lang(); ?>
                    <select id="cfg_admin_lang" name="cfg_admin_lang" class="form-control">
                        <option value="tr" <?= $effective_lang === 'tr' ? 'selected' : '' ?>>Türkçe</option>
                        <option value="en" <?= $effective_lang === 'en' ? 'selected' : '' ?>>English</option>
                    </select>
                </div>
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin-top: 20px;">
                    <input type="checkbox" name="cfg_robots" value="true" <?= (isset($cfg_robots) && $cfg_robots) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                    <span><?= p_lang('allow_robots', 'Allow search engine indexing (robots)') ?></span>
                </label>
            </div>

            <div class="panel" style="margin-top: 24px;">
                <h2 style="margin-top:0;"><?= p_lang('assets_integrations', 'Branding & Assets') ?></h2>
                <div class="input-group">
                    <label for="cfg_logolink"><?= p_lang('logo_url', 'Logo Image URL') ?></label>
                    <input type="text" id="cfg_logolink" name="cfg_logolink" class="form-control" value="<?= htmlspecialchars($cfg_logolink) ?>">
                </div>
                <div class="input-group">
                    <label for="cfg_favicon"><?= p_lang('favicon_url', 'Favicon Image URL') ?></label>
                    <input type="text" id="cfg_favicon" name="cfg_favicon" class="form-control" value="<?= htmlspecialchars($cfg_favicon) ?>">
                </div>
                <div class="input-group">
                    <label for="cfg_support"><?= p_lang('support_link', 'Support Email/Link') ?></label>
                    <input type="text" id="cfg_support" name="cfg_support" class="form-control" value="<?= htmlspecialchars($cfg_support) ?>">
                </div>
                <div class="input-group">
                    <label for="cfg_googleanalytics"><?= p_lang('analytics_id', 'Google Analytics ID') ?></label>
                    <input type="text" id="cfg_googleanalytics" name="cfg_googleanalytics" class="form-control" value="<?= htmlspecialchars($cfg_googleanalytics) ?>" placeholder="G-XXXXXXXXXX">
                </div>
            </div>

            <div class="panel" style="margin-top: 24px;">
                <h2 style="margin-top:0;"><?= p_lang('social_links', 'Social Media') ?></h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="input-group">
                        <label for="cfg_social_linkedin"><i class="lab la-linkedin"></i> LinkedIn</label>
                        <input type="text" id="cfg_social_linkedin" name="cfg_social_linkedin" class="form-control" value="<?= htmlspecialchars($cfg_social_linkedin ?? '') ?>" placeholder="https://linkedin.com/...">
                    </div>
                    <div class="input-group">
                        <label for="cfg_social_instagram"><i class="lab la-instagram"></i> Instagram</label>
                        <input type="text" id="cfg_social_instagram" name="cfg_social_instagram" class="form-control" value="<?= htmlspecialchars($cfg_social_instagram ?? '') ?>" placeholder="https://instagram.com/...">
                    </div>
                    <div class="input-group">
                        <label for="cfg_social_x"><i class="lab la-twitter"></i> X (Twitter)</label>
                        <input type="text" id="cfg_social_x" name="cfg_social_x" class="form-control" value="<?= htmlspecialchars($cfg_social_x ?? '') ?>" placeholder="https://x.com/...">
                    </div>
                    <div class="input-group">
                        <label for="cfg_social_facebook"><i class="lab la-facebook"></i> Facebook</label>
                        <input type="text" id="cfg_social_facebook" name="cfg_social_facebook" class="form-control" value="<?= htmlspecialchars($cfg_social_facebook ?? '') ?>" placeholder="https://facebook.com/...">
                    </div>
                </div>
            </div>

            <div class="panel" style="margin-top: 24px;">
                <h2 style="margin-top:0;"><?= p_lang('smtp_settings', 'SMTP Configuration') ?></h2>
                <div style="display: grid; grid-template-columns: 1fr 120px; gap: 16px;">
                    <div class="input-group">
                    <label for="cfg_smtp_host"><?= p_lang('smtp_host', 'SMTP Host') ?></label>
                    <input type="text" id="cfg_smtp_host" name="cfg_smtp_host" class="form-control" value="<?= htmlspecialchars($cfg_smtp_host ?? '') ?>">
                </div>
                <div class="input-group">
                    <label for="cfg_smtp_port"><?= p_lang('smtp_port', 'Port') ?></label>
                    <input type="text" id="cfg_smtp_port" name="cfg_smtp_port" class="form-control" value="<?= htmlspecialchars($cfg_smtp_port ?? '') ?>">
                </div>
            </div>
            <div class="input-group">
                <label for="cfg_smtp_user"><?= p_lang('smtp_user', 'SMTP Username') ?></label>
                <input type="text" id="cfg_smtp_user" name="cfg_smtp_user" class="form-control" value="<?= htmlspecialchars($cfg_smtp_user ?? '') ?>">
            </div>
            <div class="input-group">
                <label for="cfg_smtp_pass"><?= p_lang('smtp_pass', 'SMTP Password') ?></label>
                <input type="password" id="cfg_smtp_pass" name="cfg_smtp_pass" class="form-control" value="<?= htmlspecialchars($cfg_smtp_pass ?? '') ?>">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="input-group">
                    <label for="cfg_smtp_secure"><?= p_lang('encryption', 'Encryption') ?></label>
                    <select id="cfg_smtp_secure" name="cfg_smtp_secure" class="form-control">
                        <option value="" <?= (isset($cfg_smtp_secure) && $cfg_smtp_secure === '') ? 'selected' : '' ?>><?= p_lang('none', 'None') ?></option>
                        <option value="tls" <?= (isset($cfg_smtp_secure) && $cfg_smtp_secure === 'tls') ? 'selected' : '' ?>>TLS (STARTTLS)</option>
                        <option value="ssl" <?= (isset($cfg_smtp_secure) && $cfg_smtp_secure === 'ssl') ? 'selected' : '' ?>>SSL</option>
                    </select>
                </div>
                    <div class="input-group">
                        <label for="cfg_contact_email"><?= p_lang('receiver_email', 'Receiver Email') ?></label>
                        <input type="email" id="cfg_contact_email" name="cfg_contact_email" class="form-control" value="<?= htmlspecialchars($cfg_contact_email ?? '') ?>">
                    </div>
                </div>
                <div id="smtp-test-feedback" style="margin-top: 12px; display: none; padding: 12px; border-radius: 8px;"></div>
                <div style="margin-top: 16px;">
                    <button type="button" id="btnTestSmtp" class="btn btn-secondary">
                        <i class="las la-paper-plane"></i> <span id="btnTestSmtpText"><?= p_lang('test_smtp', 'Test SMTP Connection') ?></span>
                    </button>
                    <div style="margin-top: 8px; font-size: 11px; color: var(--text-muted);"><i class="las la-info-circle"></i> Gönderici adresi otomatik olarak Yetkili SMTP Kullanıcısı yapılacaktır (Sunucu uyumluluğu için).</div>
                </div>
            </div>
        </div>

        <!-- Security Tab -->
        <div class="tab-content" id="security">
            <div class="panel">
                <h2 style="margin-top:0; color: var(--danger-color);"><?= p_lang('security_settings', 'High Security Settings') ?></h2>
                <div class="input-group">
                    <label for="cfg_admin_path"><?= p_lang('admin_path', 'Admin Panel Path (URL Route)') ?></label>
                    <input type="text" id="cfg_admin_path" name="cfg_admin_path" class="form-control" value="<?= htmlspecialchars($cfg_admin_path) ?>">
                </div>
                <div class="input-group">
                    <label for="cfg_admin_ips"><?= p_lang('ip_whitelist', 'IP Whitelisting') ?></label>
                    <input type="text" id="cfg_admin_ips" name="cfg_admin_ips" class="form-control" value="<?= htmlspecialchars($cfg_admin_ips ?? '') ?>" placeholder="e.g. 1.2.3.4, 5.6.7.8">
                    <div class="help-text"><?= p_lang('ip_whitelist_desc', 'Leave blank to allow all IPs. Separate by commas.') ?></div>
                </div>
                <div style="margin: 20px 0;">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="cfg_trust_proxies" value="true" <?= (isset($cfg_trust_proxies) && $cfg_trust_proxies) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                        <span><?= p_lang('trust_proxies', 'Trust Proxy IPs (Cloudflare / Load Balancers)') ?></span>
                    </label>
                    <div class="help-text" style="margin-left: 30px; font-size: 11px;">Enable this if your site is behind a CDN or Proxy to prevent IP blocking.</div>
                </div>
                <div style="margin: 20px 0;">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="cfg_enable_filemanager" value="true" <?= (isset($cfg_enable_filemanager) && $cfg_enable_filemanager) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                        <span><?= p_lang('enable_filemanager', 'Enable File Manager') ?></span>
                    </label>
                </div>
                <div style="margin: 20px 0;">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="cfg_enable_analytics" value="true" <?= (isset($cfg_enable_analytics) && $cfg_enable_analytics) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                        <span><?= p_lang('enable_analytics', 'Enable Built-in Analytics') ?></span>
                    </label>
                </div>
                <div style="margin: 20px 0; padding: 15px; border: 1px solid var(--prfy-primary); border-radius: 12px; background: rgba(99, 102, 241, 0.05);">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="cfg_enable_captcha" value="true" <?= (isset($cfg_enable_captcha) && $cfg_enable_captcha) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                        <span style="font-weight: 700; color: var(--prfy-primary);"><?= p_lang('enable_captcha', 'Enable NywhashCaptcha (Spam Protection)') ?></span>
                    </label>
                    <div style="margin-left: 30px; font-size: 12px; color: var(--text-muted); margin-top: 5px;">
                        <?= p_lang('captcha_desc', 'Modern math-based security for the contact form. No external libraries required.') ?>
                    </div>
                </div>
                
                <hr style="opacity: 0.1; margin: 30px 0;">
                
                <h3 style="color: var(--danger-color);"><?= p_lang('maintenance_mode', 'Maintenance Mode') ?></h3>
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin-bottom: 16px;">
                    <input type="checkbox" name="cfg_maintenance_mode" value="true" <?= (isset($cfg_maintenance_mode) && $cfg_maintenance_mode) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                    <span style="font-weight: 700;"><?= p_lang('activate_maintenance', 'Activate Maintenance Mode') ?></span>
                </label>
                <div style="padding: 15px; border: 1px solid <?= !empty($cfg_readonly_mode) ? 'var(--primary-color)' : 'rgba(0,0,0,0.1)' ?>; border-radius: 12px; background: <?= !empty($cfg_readonly_mode) ? 'rgba(99, 102, 241, 0.05)' : 'transparent' ?>; transition: all 0.3s;">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <input type="radio" value="true" <?= (isset($cfg_readonly_mode) && $cfg_readonly_mode) ? 'checked' : '' ?> <?= !empty($cfg_readonly_mode) ? 'onclick="return false;"' : '' ?> style="width: 18px; height: 18px;">
                        <input type="hidden" name="cfg_readonly_mode" value="<?= !empty($cfg_readonly_mode) ? 'true' : 'false' ?>">
                        <span style="font-weight: 700; color: <?= !empty($cfg_readonly_mode) ? 'var(--primary-color)' : 'inherit' ?>;"><?= p_lang('hardened_mode', 'System Hardened Mode (Demo/Security Mode)') ?></span>
                    </label>
                    <div style="margin-left: 30px; font-size: 12px; color: var(--text-muted); margin-top: 5px;">
                        <?php if(!empty($cfg_readonly_mode)): ?>
                            <i class="las la-shield-alt" style="color: var(--primary-color);"></i> <strong style="color: var(--primary-color);"><?= p_lang('hardened_locked', 'INTEGRITY LOCK ACTIVE') ?></strong>: <?= p_lang('hardened_desc_locked', 'To prevent unauthorized changes, this mode can only be disabled by modifying config.php via FTP/SSH.') ?>
                        <?php else: ?>
                            <?= p_lang('hardened_desc', 'Blocks all admin panel modifications. Ideal for public demos or high-security production environments.') ?>
                            <div style="margin-top: 8px;">
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input type="checkbox" name="cfg_readonly_mode" value="true" style="width: 16px; height: 16px;">
                                    <span style="font-size: 11px; font-weight: 600; color: var(--danger-color);"><?= p_lang('confirm_hardened', 'I understand I will need SSH/FTP to disable this.') ?></span>
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="input-group">
                    <label for="cfg_maintenance_msg"><?= p_lang('maintenance_msg', 'Maintenance Message') ?></label>
                    <input type="text" id="cfg_maintenance_msg" name="cfg_maintenance_msg" class="form-control" value="<?= htmlspecialchars($cfg_maintenance_msg ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Branding & Assets Tab -->
        <div class="tab-content" id="adminpanel_branding">
            <div class="panel">
                <h2 style="margin-top:0;"><?= p_lang('assets_integrations', 'Assets & Integrations') ?></h2>
                <div class="input-group">
                    <label for="cfg_logolink"><?= p_lang('logo_url', 'Logo Image URL') ?></label>
                    <input type="text" id="cfg_logolink" name="cfg_logolink" class="form-control" value="<?= htmlspecialchars($cfg_logolink) ?>">
                </div>
                <div class="input-group">
                    <label for="cfg_favicon"><?= p_lang('favicon_url', 'Favicon Image URL') ?></label>
                    <input type="text" id="cfg_favicon" name="cfg_favicon" class="form-control" value="<?= htmlspecialchars($cfg_favicon) ?>">
                </div>
                <div class="input-group">
                    <label for="cfg_support"><?= p_lang('support_link', 'Support Email/Link') ?></label>
                    <input type="text" id="cfg_support" name="cfg_support" class="form-control" value="<?= htmlspecialchars($cfg_support) ?>">
                </div>
                <div class="input-group">
                    <label for="cfg_googleanalytics"><?= p_lang('analytics_id', 'Google Analytics ID') ?></label>
                    <input type="text" id="cfg_googleanalytics" name="cfg_googleanalytics" class="form-control" value="<?= htmlspecialchars($cfg_googleanalytics) ?>" placeholder="G-XXXXXXXXXX">
                </div>
            </div>
        </div>

        <script>
        // Update sidebar buttons to include these restored tabs
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.settings-tabs');
            if (sidebar && !document.querySelector('[data-tab="adminpanel_branding"]')) {
                const btnBranding = document.createElement('button');
                btnBranding.type = 'button';
                btnBranding.className = 'tab-btn';
                btnBranding.dataset.tab = 'adminpanel_branding';
                btnBranding.innerHTML = '<i class="las la-image"></i> <span><?= p_lang("branding", "Branding") ?></span>';
                sidebar.appendChild(btnBranding);
                
                // Re-init tabs if needed
                const tabs = document.querySelectorAll('.tab-btn');
                const contents = document.querySelectorAll('.tab-content');
                tabs.forEach(tab => {
                    tab.addEventListener('click', () => {
                        const target = tab.dataset.tab;
                        tabs.forEach(t => t.classList.remove('active'));
                        contents.forEach(c => c.classList.remove('active'));
                        tab.classList.add('active');
                        const content = document.getElementById(target);
                        if(content) content.classList.add('active');
                    });
                });
            }
        });
        </script>

        <!-- Redundant Tab Removal (Unified) -->

        <!-- E-Commerce Tab - Combined -->
        <div class="tab-content" id="ecommerce">
            <div class="panel">
                <h2 style="margin-top:0;"><?= p_lang('ecommerce_settings', 'E-Commerce') ?></h2>
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin-bottom: 20px; padding: 15px; background: rgba(99, 102, 241, 0.05); border-radius: 12px; border: 1px solid var(--prfy-primary);">
                    <input type="checkbox" name="cfg_enable_ecommerce" value="true" <?= (isset($cfg_enable_ecommerce) && $cfg_enable_ecommerce) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                    <span style="font-weight: 700; color: var(--prfy-primary);"><?= p_lang('enable_ecommerce', 'Enable E-Commerce System') ?></span>
                </label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="input-group">
                        <label for="cfg_ecommerce_currency"><?= p_lang('currency_iso', 'Currency (ISO)') ?></label>
                        <input type="text" id="cfg_ecommerce_currency" name="cfg_ecommerce_currency" class="form-control" value="<?= htmlspecialchars($cfg_ecommerce_currency ?? 'TRY') ?>" placeholder="TRY, USD, EUR">
                    </div>
                </div>
            </div>

            <div class="panel" style="margin-top: 24px;">
                <h3 style="margin-top:0; margin-bottom: 20px;"><?= p_lang('payment_gateways', 'Payment Gateways') ?></h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- PayTR -->
                    <div style="padding: 16px; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 12px;">
                        <h4 style="margin: 0 0 12px 0; color: #d97706;"><i class="las la-credit-card"></i> PayTR</h4>
                        <div class="input-group">
                            <label style="font-size: 12px;">Merchant ID</label>
                            <input type="text" name="cfg_paytr_m_id" class="form-control" value="<?= htmlspecialchars($cfg_paytr_m_id ?? '') ?>" style="font-size: 13px;">
                        </div>
                        <div class="input-group">
                            <label style="font-size: 12px;">Merchant Key</label>
                            <input type="text" name="cfg_paytr_m_key" class="form-control" value="<?= htmlspecialchars($cfg_paytr_m_key ?? '') ?>" style="font-size: 13px;">
                        </div>
                        <div class="input-group">
                            <label style="font-size: 12px;">Merchant Salt</label>
                            <input type="text" name="cfg_paytr_m_salt" class="form-control" value="<?= htmlspecialchars($cfg_paytr_m_salt ?? '') ?>" style="font-size: 13px;">
                        </div>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; margin-top: 8px;">
                            <input type="checkbox" name="cfg_paytr_test" value="1" <?= (isset($cfg_paytr_test) && $cfg_paytr_test == '1') ? 'checked' : '' ?>>
                            <span style="font-size: 12px; color: #d97706;"><?= p_lang('test_mode', 'Test Mode') ?></span>
                        </label>
                    </div>


                    <!-- Bank Transfer -->
                    <div style="padding: 16px; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 12px;">
                        <h4 style="margin: 0 0 12px 0; color: #7c3aed;"><i class="las la-university"></i> Bank Transfer</h4>
                        <div class="input-group">
                            <label style="font-size: 12px;">IBAN / Bank Details</label>
                            <textarea name="cfg_bank_details" class="form-control" rows="3" placeholder="Bank: ... IBAN: ..." style="font-size: 13px;"><?= htmlspecialchars($cfg_bank_details ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Panel Tab -->
        <div class="tab-content" id="adminpanel">
            <div class="panel">
                <h2 style="margin-top:0;"><?= p_lang('admin_panel_customization', 'Admin Panel Customization') ?></h2>
                <div class="input-group">
                    <label for="cfg_admin_sidebar_color"><?= p_lang('sidebar_color', 'Sidebar Background Color') ?></label>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <input type="color" id="cfg_admin_sidebar_color" name="cfg_admin_sidebar_color" class="form-control" value="<?= htmlspecialchars($cfg_admin_sidebar_color ?? '#0f172a') ?>" style="width: 60px; padding: 4px; height: 48px;">
                        <input type="text" class="form-control" value="<?= htmlspecialchars($cfg_admin_sidebar_color ?? '#0f172a') ?>" readonly style="flex: 1;">
                    </div>
                    <div class="help-text">Choose a custom color for the left navigation sidebar.</div>
                </div>
            </div>
        </div>

        <!-- Sticky Action Bar -->
        <div class="sticky-actions">
            <div style="margin-right: auto; font-size: 13px; color: var(--text-muted);">
                <i class="las la-clock-rotate-left"></i> <?= p_lang('last_modified', 'Last saved') ?>: <?= date('d M Y, H:i') ?>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="las la-save"></i> <?= p_lang('save_changes', 'Save Core Configuration') ?>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');
    const form = document.getElementById('settingsForm');
    const preloader = document.getElementById('savePreloader');
    const activeTabInput = document.getElementById('activeTabInput');
    let isSubmitting = false;

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;
            
            // Remove active states
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            
            // Add active state to clicked tab
            tab.classList.add('active');
            document.getElementById(target).classList.add('active');
            
            // Store active tab in input for form submission
            activeTabInput.value = target;
            localStorage.setItem('settings_active_tab', target);
        });
    });

    // Restore active tab
    const savedTab = localStorage.getItem('settings_active_tab') || activeTabInput.value;
    if (savedTab) {
        const tabToActivate = document.querySelector(`.tab-btn[data-tab="${savedTab}"]`);
        if (tabToActivate) {
            // Trigger click to restore
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));
            tabToActivate.classList.add('active');
            document.getElementById(savedTab).classList.add('active');
        }
    }
    
    // Instant Form Submit (v3.9.0)
    form.addEventListener('submit', function(e) {
        if (isSubmitting) return;
        isSubmitting = true;
        preloader.style.display = 'flex';
        // Form will submit naturally without delay
    });

    // AJAX SMTP Tester (v3.9.0)
    const btnTest = document.getElementById('btnTestSmtp');
    if (btnTest) {
        btnTest.addEventListener('click', async () => {
            const feedback = document.getElementById('smtp-test-feedback');
            const btnText = document.getElementById('btnTestSmtpText');
            
            feedback.style.display = 'block';
            feedback.style.background = '#f1f5f9';
            feedback.style.color = '#475569';
            feedback.innerHTML = '<i class="las la-spinner la-spin"></i> Testing connection...';
            btnTest.disabled = true;

            const formData = new FormData(form);
            try {
                const response = await fetch('?ajax=test_smtp', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                
                if (res.success) {
                    feedback.style.background = '#f0fdf4';
                    feedback.style.color = '#166534';
                    feedback.innerHTML = '<i class="las la-check-circle"></i> ' + res.message;
                } else {
                    feedback.style.background = '#fef2f2';
                    feedback.style.color = '#991b1b';
                    feedback.innerHTML = '<i class="las la-exclamation-circle"></i> ' + res.message;
                }
            } catch (e) {
                feedback.style.background = '#fef2f2';
                feedback.innerHTML = 'An unexpected error occurred.';
            } finally {
                btnTest.disabled = false;
            }
        });
    }
    
    // Clear session flash on page load (after 3 seconds to show messages)
    <?php if ($flash_success || $flash_error): ?>
    setTimeout(function() {
        const notices = document.querySelectorAll('.notice');
        notices.forEach(n => {
            n.style.transition = 'opacity 0.5s ease';
            n.style.opacity = '0';
            setTimeout(() => n.remove(), 500);
        });
    }, 5000);
    <?php endif; ?>
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
