<?php
/**
 * PurifyCMS v4.0.0 - Installation Wizard
 */

if (file_exists(__DIR__ . '/inc/config.php')) {
    header('Location: index.php');
    die('Access Denied: System already installed. Please delete install.php for security.');
}

// --- INTERNAL i18n ENGINE ---
$lang = $_GET['lang'] ?? 'tr';
$translations = [
    'tr' => [
        'title' => 'PurifyCMS Kurulum Sihirbazı',
        'subtitle' => 'Profesyonel dijital varlığınız burda başlıyor.',
        'next' => 'İleri',
        'back' => 'Geri',
        'finish' => 'Kurulumu Tamamla',
        'lang_title' => 'Dil Seçimi',
        'lang_desc' => 'Lütfen devam etmek istediğiniz dili seçin.',
        'theme_title' => 'Görünüm',
        'theme_desc' => 'Size en uygun arayüzü seçin.',
        'theme_light' => 'Aydınlık',
        'theme_dark' => 'Karanlık',
        'mode_title' => 'Kurulum Türü',
        'mode_desc' => 'Nasıl bir kurulum yapmak istersiniz?',
        'mode_simple' => 'Basit Kurulum',
        'mode_simple_desc' => 'Sadece temel bilgiler. 1 dakikada hazır!',
        'mode_advanced' => 'Gelişmiş Kurulum',
        'mode_advanced_desc' => 'Tüm detaylara hakim olun (SMTP, E-ticaret, Rotalar).',
        'health_title' => 'Sistem Analizi',
        'health_desc' => 'Sunucunuzun PurifyCMS için hazır olup olmadığını kontrol ediyoruz.',
        'identity_title' => 'Site Kimliği',
        'identity_desc' => 'Sitenizin temel bilgilerini girin.',
        'assets_title' => 'Görsel Varlıklar',
        'assets_desc' => 'Logo ve Favicon ayarlarınız.',
        'admin_title' => 'Yönetici Hesabı',
        'admin_desc' => 'Panel erişimi için ana hesap bilgilerini oluşturun.',
        'advanced_title' => 'Teknik Ayarlar',
        'advanced_desc' => 'Güvenlik, SMTP ve E-ticaret altyapısını yapılandırın.',
        'success_title' => 'Sistem Başlatıldı!',
        'success_desc' => 'PurifyCMS başarıyla kuruldu. Dijital dünyaya hoş geldin.',
        'ready_to_go' => 'Başlayalım!',
        'open_dashboard' => 'Paneli Aç',
        'view_site' => 'Siteyi Görüntüle',
        'requirements_met' => 'Tüm gereksinimler sağlandı.',
        'requirements_fail' => 'Bazı gereksinimler eksik.',
        'site_name' => 'Site Adı',
        'main_url' => 'Kurulum URL (https:// dahil)',
        'admin_user' => 'Yönetici Kullanıcı Adı',
        'admin_pass' => 'Yönetici Şifresi',
        'logo_url' => 'Logo Görsel URL',
        'fav_url' => 'Favicon Görsel URL',
        'smtp_host' => 'SMTP Host',
        'smtp_port' => 'SMTP Port',
        'smtp_user' => 'SMTP Kullanıcı',
        'smtp_pass' => 'SMTP Şifre',
        'contact_email' => 'İletişim E-postası',
        'admin_path' => 'Özel Admin Yolu',
        'ip_whitelist' => 'IP Beyaz Liste (Boş bırakılabilir)',
        'enable_ecommerce' => 'E-ticaret Özelliklerini Aktif Et',
        'currency' => 'Para Birimi',
        'installing_system' => 'Sistem Verileri Yazılıyor...',
        'finish_notice' => 'Yapılandırma dosyanız oluşturulacak ve sistem kullanıma hazır hale gelecektir.',
        'license_notice' => 'Bu proje MIT Lisansı altında açık kaynak olarak dağıtılmaktadır.'
    ],
    'en' => [
        'title' => 'PurifyCMS Setup Wizard',
        'subtitle' => 'Your professional digital presence starts here.',
        'next' => 'Next',
        'back' => 'Back',
        'finish' => 'Complete Setup',
        'lang_title' => 'Language',
        'lang_desc' => 'Please select your preferred language.',
        'theme_title' => 'Appearance',
        'theme_desc' => 'Pick the interface that suits you best.',
        'theme_light' => 'Light',
        'theme_dark' => 'Dark',
        'mode_title' => 'Setup Mode',
        'mode_desc' => 'How would you like to proceed?',
        'mode_simple' => 'Simple Setup',
        'mode_simple_desc' => 'Basic info only. Ready in 1 minute!',
        'mode_advanced' => 'Advanced Setup',
        'mode_advanced_desc' => 'Full control over settings (SMTP, E-commerce, Routes).',
        'health_title' => 'System Analysis',
        'health_desc' => 'Scanning server compatibility for PurifyCMS.',
        'identity_title' => 'Site Identity',
        'identity_desc' => 'Enter basic information about your site.',
        'assets_title' => 'Visual Assets',
        'assets_desc' => 'Configure your Logo and Favicon.',
        'admin_title' => 'Admin Account',
        'admin_desc' => 'Create credentials for dashboard access.',
        'advanced_title' => 'Technical Settings',
        'advanced_desc' => 'Configure Security, SMTP and E-commerce.',
        'success_title' => 'System Initialized!',
        'success_desc' => 'PurifyCMS has been successfully installed. Welcome aboard.',
        'ready_to_go' => 'Ready to Go!',
        'open_dashboard' => 'Open Dashboard',
        'view_site' => 'View Site',
        'requirements_met' => 'All requirements are met.',
        'requirements_fail' => 'Some requirements are missing.',
        'site_name' => 'Site Name',
        'main_url' => 'Installation URL (include https://)',
        'admin_user' => 'Admin Username',
        'admin_pass' => 'Admin Password',
        'logo_url' => 'Logo Image URL',
        'fav_url' => 'Favicon Image URL',
        'smtp_host' => 'SMTP Host',
        'smtp_port' => 'SMTP Port',
        'smtp_user' => 'SMTP User',
        'smtp_pass' => 'SMTP Password',
        'contact_email' => 'Contact Email',
        'admin_path' => 'Custom Admin Path',
        'ip_whitelist' => 'IP Whitelist (Optional)',
        'enable_ecommerce' => 'Enable E-commerce Features',
        'currency' => 'Currency',
        'installing_system' => 'Writing System Data...',
        'finish_notice' => 'Your configuration file will be created and the system will be ready to use.',
        'license_notice' => 'This project is distributed as open-source software under the MIT License.'
    ]
];

function i18n($key) {
    global $translations, $lang;
    return $translations[$lang][$key] ?? $key;
}

// --- SYSTEM REQUIREMENTS ---
$requirements = [
    'php' => ['name' => 'PHP 8.1+', 'met' => version_compare(PHP_VERSION, '8.1.0', '>=')],
    'curl' => ['name' => 'cURL Extension', 'met' => extension_loaded('curl')],
    'json' => ['name' => 'JSON Extension', 'met' => extension_loaded('json')],
    'mbstring' => ['name' => 'MBString Extension', 'met' => extension_loaded('mbstring')],
    'session' => ['name' => 'Session Support', 'met' => extension_loaded('session')],
    'writable_inc' => ['name' => 'inc/ Writable', 'met' => is_writable(__DIR__ . '/inc')],
    'writable_data' => ['name' => 'inc/data/ Writable', 'met' => (is_dir(__DIR__ . '/inc/data') ? is_writable(__DIR__ . '/inc/data') : is_writable(__DIR__ . '/inc'))]
];

// --- POST HANDLING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize'])) {
    $site_name = trim($_POST['site_name'] ?? 'My Site');
    $admin_user = trim($_POST['admin_user'] ?? 'admin');
    $admin_pass = $_POST['admin_pass'] ?? '';
    $main_url = rtrim(trim($_POST['main_url'] ?? ''), '/');
    $admin_lang = $_POST['form_lang'] ?? 'tr';
    
    $passhash = password_hash($admin_pass, PASSWORD_DEFAULT);
    
    $admin_path = trim($_POST['admin_path'] ?? 'admin');
    $logo_url = trim($_POST['logo_url'] ?? '');
    $fav_url = trim($_POST['fav_url'] ?? '');
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = trim($_POST['smtp_port'] ?? '');
    $smtp_user = trim($_POST['smtp_user'] ?? '');
    $smtp_pass = trim($_POST['smtp_pass'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? 'hello@localhost');
    $ip_whitelist = trim($_POST['ip_whitelist'] ?? '');
    $enable_ecommerce = isset($_POST['enable_ecommerce']) ? 'true' : 'false';
    $ecommerce_currency = trim($_POST['ecommerce_currency'] ?? 'TRY');

    $config_content = "<?php\n";
    $config_content .= "// PurifyCMS v4.0.0 Configuration Manifest\n";
    $config_content .= "// Generated on: " . date('Y-m-d H:i:s') . " (Install)\n";
    $config_content .= "defined('DIR_ACCESS') or die('Absolute access denied.');\n\n";

    $config_content .= "// --- System Latency & Critical Routing ---\n";
    $config_content .= "\$cfg_mainurl = '" . addslashes($main_url) . "';\n";
    $config_content .= "\$cfg_admin_path = '" . addslashes($admin_path) . "';\n";
    $config_content .= "\$cfg_admin_ips = '" . addslashes($ip_whitelist) . "';\n";
    $config_content .= "\$cfg_trust_proxies = false;\n";
    $config_content .= "\$cfg_robots = true;\n";
    $config_content .= "\$cfg_domain = '" . parse_url($main_url, PHP_URL_HOST) . "';\n";
    $config_content .= "\$cfg_readonly_mode = false;\n";
    $config_content .= "\$cfg_maintenance_mode = false;\n";
    $config_content .= "\$cfg_maintenance_msg = 'System is currently under maintenance.';\n\n";

    $config_content .= "// --- Branding & Identity ---\n";
    $config_content .= "\$cfg_name = '" . addslashes($site_name) . "';\n";
    $config_content .= "\$cfg_legalname = '" . addslashes($site_name) . "';\n";
    $config_content .= "\$cfg_logolink = '" . addslashes($logo_url) . "';\n";
    $config_content .= "\$cfg_favicon = '" . addslashes($fav_url) . "';\n";
    $config_content .= "\$cfg_support = '" . addslashes($contact_email) . "';\n\n";

    $config_content .= "// --- Social Graph & Metadata ---\n";
    $config_content .= "\$cfg_googleanalytics = '';\n";
    $config_content .= "\$cfg_social_linkedin = '';\n";
    $config_content .= "\$cfg_social_instagram = '';\n";
    $config_content .= "\$cfg_social_x = '';\n";
    $config_content .= "\$cfg_social_facebook = '';\n\n";

    $config_content .= "// --- Theme & Appearance ---\n";
    $config_content .= "\$cfg_active_theme = 'purifydefault';\n";
    $config_content .= "\$cfg_admin_sidebar_color = '#0f172a';\n";
    $config_content .= "\$cfg_admin_lang = '" . addslashes($admin_lang) . "';\n\n";

    $config_content .= "// --- System Controls ---\n";
    $config_content .= "\$cfg_enable_filemanager = true;\n";
    $config_content .= "\$cfg_enable_analytics = true;\n";
    $config_content .= "\$cfg_enable_captcha = false;\n\n";

    $config_content .= "// --- Communication (SMTP) ---\n";
    $config_content .= "\$cfg_smtp_host = '" . addslashes($smtp_host) . "';\n";
    $config_content .= "\$cfg_smtp_port = '" . addslashes($smtp_port) . "';\n";
    $config_content .= "\$cfg_smtp_user = '" . addslashes($smtp_user) . "';\n";
    $config_content .= "\$cfg_smtp_pass = '" . addslashes($smtp_pass) . "';\n";
    $config_content .= "\$cfg_smtp_secure = '';\n";
    $config_content .= "\$cfg_contact_email = '" . addslashes($contact_email) . "';\n\n";

    $config_content .= "// --- Admin Orchestration ---\n";
    $config_content .= "\$cfg_admin_user = '" . addslashes($admin_user) . "';\n";
    $config_content .= "\$cfg_admin_passhash = '" . addslashes($passhash) . "';\n\n";

    $config_content .= "// --- Fiscal Gateway Manifest (v4.0.0) ---\n";
    $config_content .= "\$cfg_enable_ecommerce = " . $enable_ecommerce . ";\n";
    $config_content .= "\$cfg_ecommerce_currency = '" . addslashes($ecommerce_currency) . "';\n";
    $config_content .= "\$cfg_paytr_m_id = '';\n";
    $config_content .= "\$cfg_paytr_m_key = '';\n";
    $config_content .= "\$cfg_paytr_m_salt = '';\n";
    $config_content .= "\$cfg_paytr_test = 0;\n";
    $config_content .= "\$cfg_bank_details = '';\n\n";

    $config_content .= "// --- Kernel & Integrity ---\n";
    $config_content .= "error_reporting(0);\n";
    $config_content .= "ini_set('display_errors', 0);\n";

    // --- Directory Initialization Logic (v3.6.0) ---
    $dirs_to_create = [
        __DIR__ . '/inc/data',
        __DIR__ . '/inc/data/cache',
        __DIR__ . '/inc/data/ecommerce',
        __DIR__ . '/inc/data/ecommerce/vault',
        __DIR__ . '/inc/data/ecommerce/orders',
        __DIR__ . '/inc/data/ecommerce/logs',
        __DIR__ . '/inc/data/messages',
        __DIR__ . '/inc/data/locales',
        __DIR__ . '/inc/data/logs',
        __DIR__ . '/inc/data/stats',
        __DIR__ . '/inc/data/emails'
    ];

    foreach ($dirs_to_create as $dir) {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Critical directory creation failed: ' . basename($dir)]);
                exit;
            }
        }
    }

    // Ensure data protection .htaccess exists
    $htaccess_file = __DIR__ . '/inc/data/.htaccess';
    if (!file_exists($htaccess_file)) {
        @file_put_contents($htaccess_file, "Deny from all", LOCK_EX);
    }

    if (file_put_contents(__DIR__ . '/inc/config.php', $config_content, LOCK_EX)) {
        // Self-Destruct Protocol (v3.5.5)
        @unlink(__FILE__);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Config write error.']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= i18n('title') ?></title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="adminassets/images/favicon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="adminassets/images/favicon-16x16.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg: #0b0b0f;
            --surface: rgba(20, 20, 25, 0.7);
            --border: rgba(255, 255, 255, 0.08);
            --text-main: #ffffff;
            --text-muted: #94a3b8;
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.3);
            --accent: #f59e0b;
            --radius: 20px;
            --font: 'Outfit', sans-serif;
            --blur: 20px;
        }

        [data-theme="light"] {
            --bg: #f8fafc;
            --surface: rgba(255, 255, 255, 0.8);
            --border: rgba(0, 0, 0, 0.05);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary: #4f46e5;
            --primary-glow: rgba(79, 70, 229, 0.1);
        }

        * { box-sizing: border-box; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }

        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text-main);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
        }

        .blobs { position: absolute; width: 100%; height: 100%; overflow: hidden; z-index: -1; filter: blur(100px); }
        .blob { position: absolute; border-radius: 50%; width: 50vw; height: 50vw; opacity: 0.15; }
        .blob-1 { top: -10%; left: -10%; background: var(--primary); animation: drift 20s infinite alternate; }
        .blob-2 { bottom: -10%; right: -10%; background: var(--accent); animation: drift 25s infinite alternate-reverse; }

        @keyframes drift { 
            from { transform: translate(0, 0) scale(1); }
            to { transform: translate(100px, 50px) scale(1.1); }
        }

        .installer-card {
            width: 100%;
            max-width: 540px;
            background: var(--surface);
            backdrop-filter: blur(var(--blur));
            -webkit-backdrop-filter: blur(var(--blur));
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 40px 48px 48px 48px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        .installer-header {
            text-align: center;
            margin-bottom: 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        .installer-header img {
            height: 42px;
            width: auto;
        }
        .installer-header span {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: var(--text-main);
        }

        .step { display: none; }
        .step.active { display: block; animation: slideIn 0.5s forwards; }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 { font-size: 28px; font-weight: 700; margin: 0 0 8px 0; letter-spacing: -0.02em; }
        p.subtitle { color: var(--text-muted); font-size: 15px; margin: 0 0 32px 0; line-height: 1.6; }

        .option-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 32px; }
        .option-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            cursor: pointer;
            text-align: center;
            position: relative;
        }
        .option-card:hover { border-color: var(--primary); background: rgba(99, 102, 241, 0.05); }
        .option-card.selected { border-color: var(--primary); background: var(--primary-glow); }
        .option-card i { font-size: 32px; margin-bottom: 12px; display: block; }
        .option-card span { font-weight: 600; font-size: 15px; display: block; margin-bottom: 4px; }
        .option-card small { font-size: 11px; color: var(--text-muted); display: block; line-height: 1.3; }

        .req-item {
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(0, 0, 0, 0.1); padding: 12px 16px; border-radius: 12px; margin-bottom: 8px;
        }
        .req-name { font-size: 14px; font-weight: 500; }
        .req-status-ok { color: #10b981; }
        .req-status-fail { color: #ef4444; }

        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px; }
        input {
            width: 100%; padding: 14px 18px; background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border); border-radius: 12px; color: var(--text-main);
            font-family: var(--font); font-size: 14px; outline: none;
        }
        input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-glow); }

        .btn-row { display: flex; gap: 12px; margin-top: 32px; }
        .btn {
            flex: 1; padding: 16px; border-radius: 12px; font-family: var(--font);
            font-size: 15px; font-weight: 600; cursor: pointer; border: none;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -5px var(--primary-glow); }
        .btn-secondary { background: rgba(255, 255, 255, 0.05); color: var(--text-main); }
        .btn-secondary:hover { background: rgba(255, 255, 255, 0.1); }

        .progress-bar {
            position: absolute; bottom: 40px; left: 0; width: 100%;
            display: flex; justify-content: center; gap: 8px; pointer-events: none;
        }
        .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--border); }
        .dot.active { background: var(--primary); width: 24px; border-radius: 3px; }

        .success-icon {
            width: 80px; height: 80px; background: #10b981; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 40px; color: white; margin: 0 auto 24px auto;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
        }
    </style>
</head>
<body>
    <div class="blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <div class="installer-card">
        <div class="installer-header">
            <img src="https://static.nywhash.com/partners/nywhash/nywhashsoftware_icon.png" alt="Nywhash Software Logo">
            <span>PurifyCMS</span>
        </div>
        <form id="setupForm">
            <!-- STEP 1: Language -->
            <div class="step active" data-step="1">
                <h1><?= i18n('lang_title') ?></h1>
                <p class="subtitle"><?= i18n('lang_desc') ?></p>
                <div class="option-grid">
                    <div class="option-card <?= $lang === 'tr' ? 'selected' : '' ?>" onclick="setLang('tr')">
                        <i class="fa-solid fa-language"></i>
                        <span>Türkçe</span>
                    </div>
                    <div class="option-card <?= $lang === 'en' ? 'selected' : '' ?>" onclick="setLang('en')">
                        <i class="fa-solid fa-globe"></i>
                        <span>English</span>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" onclick="nextStep()"><?= i18n('next') ?> <i class="fa-solid fa-arrow-right"></i></button>
            </div>

            <!-- STEP 2: Theme -->
            <div class="step" data-step="2">
                <h1><?= i18n('theme_title') ?></h1>
                <p class="subtitle"><?= i18n('theme_desc') ?></p>
                <div class="option-grid">
                    <div class="option-card" data-val="light" onclick="setTheme('light')">
                        <i class="fa-solid fa-sun"></i>
                        <span><?= i18n('theme_light') ?></span>
                    </div>
                    <div class="option-card selected" data-val="dark" onclick="setTheme('dark')">
                        <i class="fa-solid fa-moon"></i>
                        <span><?= i18n('theme_dark') ?></span>
                    </div>
                </div>
                <div class="btn-row">
                    <button type="button" class="btn btn-secondary" onclick="prevStep()"><?= i18n('back') ?></button>
                    <button type="button" class="btn btn-primary" onclick="nextStep()"><?= i18n('next') ?></button>
                </div>
            </div>

            <!-- STEP 3: Mode -->
            <div class="step" data-step="3">
                <h1><?= i18n('mode_title') ?></h1>
                <p class="subtitle"><?= i18n('mode_desc') ?></p>
                <div class="option-grid" style="grid-template-columns: 1fr;">
                    <div class="option-card selected" onclick="setMode('simple')" id="mode-simple">
                        <i class="fa-solid fa-bolt"></i>
                        <span><?= i18n('mode_simple') ?></span>
                        <small><?= i18n('mode_simple_desc') ?></small>
                    </div>
                    <div class="option-card" onclick="setMode('advanced')" id="mode-advanced">
                        <i class="fa-solid fa-gears"></i>
                        <span><?= i18n('mode_advanced') ?></span>
                        <small><?= i18n('mode_advanced_desc') ?></small>
                    </div>
                </div>
                <div class="btn-row">
                    <button type="button" class="btn btn-secondary" onclick="prevStep()"><?= i18n('back') ?></button>
                    <button type="button" class="btn btn-primary" onclick="nextStep()"><?= i18n('next') ?></button>
                </div>
            </div>

            <!-- STEP 4: Health Check -->
            <div class="step" data-step="4">
                <h1><?= i18n('health_title') ?></h1>
                <p class="subtitle"><?= i18n('health_desc') ?></p>
                <div style="margin-bottom: 32px;">
                    <?php 
                    foreach($requirements as $r): 
                    ?>
                    <div class="req-item">
                        <span class="req-name"><?= $r['name'] ?></span>
                        <i class="fa-solid <?= $r['met'] ? 'fa-check req-status-ok' : 'fa-xmark req-status-fail' ?>"></i>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="btn-row">
                    <button type="button" class="btn btn-secondary" onclick="prevStep()"><?= i18n('back') ?></button>
                    <button type="button" class="btn btn-primary" onclick="nextStep()"><?= i18n('next') ?></button>
                </div>
            </div>

            <!-- STEP 5: Core Settings -->
            <div class="step" data-step="5">
                <h1><?= i18n('identity_title') ?></h1>
                <p class="subtitle"><?= i18n('identity_desc') ?></p>
                <div class="form-group">
                    <label><?= i18n('site_name') ?></label>
                    <input type="text" name="site_name" required value="My Awesome Site">
                </div>
                <div class="form-group">
                    <label><?= i18n('main_url') ?></label>
                    <?php
                        $detected_proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https://' : 'http://';
                        $detected_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $detected_path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
                        $detected_url = $detected_proto . $detected_host . ($detected_path === '' || $detected_path === '/' ? '' : $detected_path);
                    ?>
                    <input type="url" name="main_url" required value="<?= htmlspecialchars($detected_url) ?>">
                </div>
                <div class="btn-row">
                    <button type="button" class="btn btn-secondary" onclick="prevStep()"><?= i18n('back') ?></button>
                    <button type="button" class="btn btn-primary" onclick="nextStep()"><?= i18n('next') ?></button>
                </div>
            </div>

            <!-- STEP 6: Admin Credentials -->
            <div class="step" data-step="6">
                <h1><?= i18n('admin_title') ?></h1>
                <p class="subtitle"><?= i18n('admin_desc') ?></p>
                <div class="form-group">
                    <label><?= i18n('admin_user') ?></label>
                    <input type="text" name="admin_user" required value="admin">
                </div>
                <div class="form-group">
                    <label><?= i18n('admin_pass') ?></label>
                    <input type="password" name="admin_pass" required>
                </div>
                <div class="btn-row">
                    <button type="button" class="btn btn-secondary" onclick="prevStep()"><?= i18n('back') ?></button>
                    <button type="button" class="btn btn-primary" onclick="goToModeStep()"><?= i18n('next') ?></button>
                </div>
            </div>

            <!-- STEP 7: Advanced Only -->
            <div class="step" data-step="7">
                <h1><?= i18n('advanced_title') ?></h1>
                <p class="subtitle"><?= i18n('advanced_desc') ?></p>
                <div style="max-height: 300px; overflow-y: auto; padding-right: 10px;">
                    <div class="form-group">
                        <label><?= i18n('admin_path') ?></label>
                        <input type="text" name="admin_path" value="admin">
                    </div>
                    <div class="form-group">
                        <label><?= i18n('logo_url') ?></label>
                        <input type="url" name="logo_url" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label><?= i18n('fav_url') ?></label>
                        <input type="url" name="fav_url" placeholder="https://..." value="<?= htmlspecialchars($detected_url . '/favicon.ico') ?>">
                    </div>
                    <div class="form-group">
                        <label><?= i18n('smtp_host') ?></label>
                        <input type="text" name="smtp_host">
                    </div>
                    <div class="form-group">
                        <label><?= i18n('smtp_user') ?></label>
                        <input type="text" name="smtp_user">
                    </div>
                    <div class="form-group">
                        <label><?= i18n('smtp_pass') ?></label>
                        <input type="password" name="smtp_pass">
                    </div>
                    <div class="form-group">
                        <label><?= i18n('contact_email') ?></label>
                        <input type="email" name="contact_email" value="hello@localhost">
                    </div>
                    <div class="form-group">
                        <label><?= i18n('ip_whitelist') ?></label>
                        <input type="text" name="ip_whitelist">
                    </div>
                    <div class="form-group" style="display: flex; align-items: center; gap: 12px; margin-top: 10px;">
                        <input type="checkbox" name="enable_ecommerce" id="en_eco" style="width: 20px; height: 20px;">
                        <label for="en_eco" style="margin-bottom: 0; cursor: pointer;"><?= i18n('enable_ecommerce') ?></label>
                    </div>
                    <div class="form-group">
                        <label><?= i18n('currency') ?></label>
                        <select name="ecommerce_currency" style="width: 100%; padding: 12px; background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 12px; color: var(--text-main); font-family: var(--font);">
                            <option value="TRY">TRY - Turkish Lira</option>
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="GBP">GBP - British Pound</option>
                        </select>
                    </div>
                </div>
                <div class="btn-row">
                    <button type="button" class="btn btn-secondary" onclick="prevStepFromAdvanced()"><?= i18n('back') ?></button>
                    <button type="button" class="btn btn-primary" onclick="nextStep()"><?= i18n('next') ?></button>
                </div>
            </div>

            <!-- STEP 8: Final Review -->
            <div class="step" data-step="8">
                <h1><?= i18n('ready_to_go') ?></h1>
                <p class="subtitle"><?= i18n('finish_notice') ?></p>
                <div style="background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.2); padding: 16px; border-radius: 12px; margin-bottom: 24px; font-size: 13px; color: var(--text-main);">
                    <i class="fa-solid fa-circle-info" style="color: var(--primary);"></i> <?= i18n('license_notice') ?>
                </div>
                
                <div class="btn-row">
                    <button type="button" class="btn btn-secondary" onclick="prevStepFromFinal()"><?= i18n('back') ?></button>
                    <button type="button" id="finalBtn" class="btn btn-primary" onclick="finalizeInstallation()"><?= i18n('finish') ?> <i class="fa-solid fa-check"></i></button>
                </div>
            </div>

            <!-- SUCCESS STEP -->
            <div class="step" id="successStep">
                <div class="success-icon"><i class="fa-solid fa-check"></i></div>
                <h1 style="text-align:center;"><?= i18n('success_title') ?></h1>
                <p class="subtitle" style="text-align:center;"><?= i18n('success_desc') ?></p>
                
                <div class="btn-row">
                    <a href="index" class="btn btn-secondary"><?= i18n('view_site') ?></a>
                    <a href="admin" id="adminLink" class="btn btn-primary"><?= i18n('open_dashboard') ?></a>
                </div>
            </div>

            <input type="hidden" name="form_lang" value="<?= $lang ?>">
            <input type="hidden" name="finalize" value="1">
        </form>

        <div class="progress-bar" id="progressBar">
            <div class="dot active"></div>
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        let mode = 'simple';

        function setLang(l) {
            window.location.href = '?lang=' + l;
        }

        function setTheme(t) {
            document.documentElement.setAttribute('data-theme', t);
            document.querySelectorAll('[data-step="2"] .option-card').forEach(c => c.classList.remove('selected'));
            document.querySelector('[data-val="'+t+'"]').classList.add('selected');
        }

        function setMode(m) {
            mode = m;
            document.querySelectorAll('[data-step="3"] .option-card').forEach(c => c.classList.remove('selected'));
            document.getElementById('mode-' + m).classList.add('selected');
        }

        function updateProgress() {
            const dots = document.querySelectorAll('.dot');
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === (currentStep - 1));
            });
            if (currentStep > 8) {
                document.getElementById('progressBar').style.display = 'none';
            } else {
                document.getElementById('progressBar').style.display = 'flex';
            }
        }

        function nextStep() {
            document.querySelector('[data-step="'+currentStep+'"]').classList.remove('active');
            currentStep++;
            document.querySelector('[data-step="'+currentStep+'"]').classList.add('active');
            updateProgress();
        }

        function prevStep() {
            document.querySelector('[data-step="'+currentStep+'"]').classList.remove('active');
            currentStep--;
            document.querySelector('[data-step="'+currentStep+'"]').classList.add('active');
            updateProgress();
        }

        function goToModeStep() {
            document.querySelector('[data-step="6"]').classList.remove('active');
            if (mode === 'simple') {
                currentStep = 8;
            } else {
                currentStep = 7;
            }
            document.querySelector('[data-step="'+currentStep+'"]').classList.add('active');
            updateProgress();
        }

        function prevStepFromAdvanced() {
            document.querySelector('[data-step="7"]').classList.remove('active');
            currentStep = 6;
            document.querySelector('[data-step="6"]').classList.add('active');
            updateProgress();
        }

        function prevStepFromFinal() {
            document.querySelector('[data-step="8"]').classList.remove('active');
            if (mode === 'simple') {
                currentStep = 6;
            } else {
                currentStep = 7;
            }
            document.querySelector('[data-step="'+currentStep+'"]').classList.add('active');
            updateProgress();
        }

        function finalizeInstallation() {
            const btn = document.getElementById('finalBtn');
            btn.disabled = true;
            btn.innerText = '<?= i18n('installing_system') ?>';

            const formData = new FormData(document.getElementById('setupForm'));
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('[data-step="8"]').classList.remove('active');
                    document.getElementById('successStep').classList.add('active');
                    const adminPath = formData.get('admin_path') || 'admin';
                    document.getElementById('adminLink').href = adminPath;
                } else {
                    alert(data.message);
                    btn.disabled = false;
                    btn.innerText = '<?= i18n('finish') ?>';
                }
            })
            .catch(err => {
                alert('Connection error');
                btn.disabled = false;
                btn.innerText = '<?= i18n('finish') ?>';
            });
        }
    </script>
</body>
</html>
