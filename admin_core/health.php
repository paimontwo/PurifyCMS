<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

$admin_page_title = p_lang('system_health', 'System Health');
$admin_page_subtitle = p_lang('health_subtitle', 'Runtime environment checks for production readiness.');
$admin_page_suite = p_lang('system_health', 'System Health');
$admin_active_nav = 'health';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_backup'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }
    
    $zip_file = sys_get_temp_dir() . '/purifycms_backup_' . date('Ymd_His') . '.zip';
    $zip = new ZipArchive();
    
    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $rootPath = realpath(__DIR__ . '/../');
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                if (strpos($relativePath, '.git') === 0) continue;
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        
        if (file_exists($zip_file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($zip_file));
            readfile($zip_file);
            unlink($zip_file);
            exit;
        }
    }
    $error = p_lang('backup_error', "Failed to create backup ZIP file. Ensure ZipArchive extension is enabled.");
}

require __DIR__ . '/includes/header.php';
function format_bytes($bytes) {
    $bytes = (float)$bytes;
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return rtrim(rtrim(number_format($bytes, 2, '.', ''), '0'), '.') . ' ' . $units[$i];
}

function bool_badge($ok, $okText = 'OK', $badText = 'Missing') {
    if ($ok) {
        return '<span style="display:inline-block;padding:3px 8px;border-radius:999px;background:rgba(34,197,94,.15);color:var(--success-color);font-size:12px;font-weight:600;">' . htmlspecialchars(p_lang(strtolower($okText), $okText)) . '</span>';
    }
    return '<span style="display:inline-block;padding:3px 8px;border-radius:999px;background:rgba(239,68,68,.15);color:var(--danger-color);font-size:12px;font-weight:600;">' . htmlspecialchars(p_lang(strtolower($badText), $badText)) . '</span>';
}

$php_version = PHP_VERSION;
$php_version_id = defined('PHP_VERSION_ID') ? PHP_VERSION_ID : 0;
$recommended_min = 80200; // 8.2.0
$php_ok = $php_version_id >= $recommended_min;

$extensions_required = [
    'json' => 'Core runtime',
    'openssl' => 'Crypto / security',
    'mbstring' => 'String handling'
];

$extensions_optional = [
    'curl' => 'Outbound HTTP (optional)',
    'intl' => 'Internationalization (optional)',
    'gd' => 'Image processing & WebP Support',
    'fileinfo' => 'MIME detection (optional)',
    'zip' => 'Zip support (optional)',
    'opcache' => 'Performance (highly recommended)'
];

$ini_checks = [
    'display_errors' => ['expected' => '0', 'label' => 'display_errors (prod)'],
    'expose_php' => ['expected' => '0', 'label' => 'expose_php (recommended)'],
    'session.use_strict_mode' => ['expected' => '1', 'label' => 'session.use_strict_mode'],
    'session.use_only_cookies' => ['expected' => '1', 'label' => 'session.use_only_cookies'],
    'session.use_trans_sid' => ['expected' => '0', 'label' => 'session.use_trans_sid'],
];

$disk_path = dirname(__DIR__);
$disk_free = @disk_free_space($disk_path);
$disk_total = @disk_total_space($disk_path);

$opcache_enabled = (extension_loaded('Zend OPcache') || extension_loaded('opcache'));
$opcache_status = null;
if ($opcache_enabled && function_exists('opcache_get_status')) {
    $opcache_status = @opcache_get_status(false);
}

// --- System Core Checks ---
$vault_path = __DIR__ . '/../inc/data/ecommerce/vault';
$vault_exists = is_dir($vault_path);
$vault_writable = $vault_exists && is_writable($vault_path);
$vault_htaccess_path = $vault_path . '/.htaccess';
$vault_htaccess = $vault_exists && file_exists($vault_htaccess_path);
$vault_htaccess_images = $vault_htaccess && (strpos(file_get_contents($vault_htaccess_path), 'jpe?g') !== false);
$mailer_exists = file_exists(__DIR__ . '/../inc/PurifyMailer.php');
$trade_exists = file_exists(__DIR__ . '/../inc/PurifyTrade.php');

// Directory Map
$dirs_to_heal = [
    __DIR__ . '/../inc/data',
    __DIR__ . '/../inc/data/cache',
    __DIR__ . '/../inc/data/ecommerce',
    __DIR__ . '/../inc/data/ecommerce/vault',
    __DIR__ . '/../inc/data/ecommerce/orders',
    __DIR__ . '/../inc/data/ecommerce/logs',
    __DIR__ . '/../inc/data/messages',
    __DIR__ . '/../inc/data/locales',
    __DIR__ . '/../inc/data/logs',
    __DIR__ . '/../inc/data/stats',
    __DIR__ . '/../inc/data/emails'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['heal_config'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }
    
    $required_configs = [
        // Core System
        'cfg_mainurl' => 'http://localhost',
        'cfg_admin_path' => 'admin',
        'cfg_admin_ips' => '',
        'cfg_trust_proxies' => false,
        'cfg_robots' => true,
        'cfg_domain' => 'localhost',
        'cfg_readonly_mode' => false,
        'cfg_maintenance_mode' => false,
        'cfg_maintenance_msg' => 'System is currently under maintenance.',
        
        // Branding & Identity
        'cfg_name' => 'PurifyCMS',
        'cfg_legalname' => 'PurifyCMS',
        'cfg_logolink' => '',
        'cfg_favicon' => '',
        'cfg_support' => 'support@localhost',
        'cfg_googleanalytics' => '',
        
        // Social
        'cfg_social_linkedin' => '',
        'cfg_social_instagram' => '',
        'cfg_social_x' => '',
        'cfg_social_facebook' => '',
        
        // Theme & Admin
        'cfg_active_theme' => 'purifydefault',
        'cfg_admin_user' => 'admin',
        'cfg_admin_passhash' => '',
        'cfg_admin_sidebar_color' => '#0f172a',
        'cfg_admin_lang' => 'tr',
        
        // Features
        'cfg_enable_filemanager' => true,
        'cfg_enable_analytics' => true,
        'cfg_enable_captcha' => false,
        'cfg_enable_ecommerce' => false,
        
        // SMTP
        'cfg_smtp_host' => '',
        'cfg_smtp_port' => '',
        'cfg_smtp_user' => '',
        'cfg_smtp_pass' => '',
        'cfg_smtp_secure' => '',
        'cfg_contact_email' => '',
        
        // E-Commerce
        'cfg_ecommerce_currency' => 'TRY',
        'cfg_paytr_m_id' => '',
        'cfg_paytr_m_key' => '',
        'cfg_paytr_m_salt' => '',
        'cfg_paytr_test' => 0,
        'cfg_bank_details' => ''
    ];

    $config_file = __DIR__ . '/../inc/config.php';
    if (file_exists($config_file)) {
        $current_config = file_get_contents($config_file);
        $append_content = "\n\n// --- Automatically Healed / Restored Variables ---\n";
        $healed_count = 0;

        foreach ($required_configs as $key => $default) {
            if (!isset($GLOBALS[$key])) {
                if (is_bool($default)) {
                    $val_str = $default ? 'true' : 'false';
                } elseif (is_numeric($default)) {
                    $val_str = $default;
                } else {
                    $val_str = "'" . addslashes((string)$default) . "'";
                }
                $append_content .= "\${$key} = {$val_str};\n";
                $healed_count++;
            }
        }

        if ($healed_count > 0) {
            if (file_put_contents($config_file, $append_content, FILE_APPEND | LOCK_EX)) {
                if (class_exists('PurifyVolt')) PurifyVolt::flushHtml();
                $success = p_lang('config_healed', "Successfully restored $healed_count missing configuration variables.");
            } else {
                $error = p_lang('config_heal_error', "Failed to write to inc/config.php. Check permissions.");
            }
        }
    }

    // Directory Auto-Repair
    $dir_healed_count = 0;
    foreach ($dirs_to_heal as $dir) {
        if (!is_dir($dir)) {
            if (@mkdir($dir, 0755, true)) $dir_healed_count++;
        }
    }
    
    // Ensure .htaccess protection
    $htaccess = __DIR__ . '/../inc/data/.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents($htaccess, "Deny from all", LOCK_EX);
    }

    if ($dir_healed_count > 0) {
        $success = ($success ?? '') . " " . p_lang('dirs_healed', "Also restored $dir_healed_count missing system directories.");
    }
}

// Check Directory Integrity
$missing_dirs = [];
foreach ($dirs_to_heal as $d) { if (!is_dir($d)) $missing_dirs[] = basename($d); }

// Calculate Health Score
$health_score = 100;
if (!$php_ok) $health_score -= 15;
foreach ($extensions_required as $ext => $desc) {
    if (!extension_loaded($ext)) $health_score -= 10;
}
foreach ($ini_checks as $key => $cfg) {
    if ((string)ini_get($key) !== (string)$cfg['expected']) $health_score -= 5;
}
if (!$opcache_enabled) $health_score -= 10;
if ($disk_free !== false && $disk_total !== false && $disk_total > 0 && ($disk_free / $disk_total) < 0.05) {
    $health_score -= 20; // Severe penalty for < 5% disk space
}
if (!empty($missing_dirs)) {
    $health_score -= 25; // Severe penalty for broken directory structure
}
$health_score = max(0, min(100, $health_score));

$health_color = '#10b981'; // Green
$health_text = p_lang('health_excellent', 'Excellent! System is fully optimized and secure.');
if ($health_score < 90) { $health_color = '#8b5cf6'; $health_text = p_lang('health_good', 'Good. Some minor configurations could be tweaked.'); }
if ($health_score < 75) { $health_color = '#f59e0b'; $health_text = p_lang('health_fair', 'Fair. Important features or settings are missing.'); }
if ($health_score < 50) { $health_color = '#ef4444'; $health_text = p_lang('health_critical', 'Critical! Immediate action required to ensure stability.'); }
?>

<?php if (isset($error)): ?>
    <div class="notice notice-error" style="max-width:900px;margin:0 auto 24px auto;"><i class="las la-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="panel" style="max-width: 900px; margin: 0 auto 24px auto;">
    <h2 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600;"><i class="las la-server" style="color:#6366f1;"></i> <?= p_lang('system_checks', 'System & Storage Checks') ?></h2>
    <div style="display:grid;grid-template-columns:1fr;gap:12px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
             <span><i class="las la-folder"></i> <?= p_lang('digital_vault', 'Product Files Directory (inc/data/ecommerce/vault)') ?></span>
             <div>
                <?= bool_badge($vault_exists, 'Exists', 'Missing') ?>
                <?= bool_badge($vault_writable, 'Writable', 'ReadOnly') ?>
                <?= bool_badge($vault_htaccess_images, 'ALLOW-IMAGES', $vault_htaccess ? 'FULL-DENIED' : 'UNPROTECTED') ?>
             </div>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;">
             <span><i class="las la-envelope"></i> <?= p_lang('mailer_core', 'SMTP Mailer') ?></span>
             <?= bool_badge($mailer_exists, 'Ready', 'Offline') ?>
        </div>
        <div style="height:1px;background:var(--border-color);margin:8px 0;"></div>
        <div style="display:flex;justify-content:space-between;align-items:center;">
             <span><i class="las la-lock"></i> <?= p_lang('hardened_integrity', 'Read-Only Mode') ?></span>
             <?= bool_badge(!empty($cfg_readonly_mode), 'Active', 'Inactive') ?>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;">
             <span><i class="las la-folder-open"></i> <?= p_lang('directory_integrity', 'Directory Structure') ?></span>
             <?= bool_badge(empty($missing_dirs), 'Intact', 'Broken (' . count($missing_dirs) . ')') ?>
        </div>
    </div>
</div>

<div class="panel" style="max-width: 900px; margin: 0 auto 24px auto; display: flex; align-items: center; justify-content: space-between; gap: 20px;">
    <div>
        <h2 style="margin: 0 0 8px 0; font-size: 22px; font-weight: 700;"><?= p_lang('health_score', 'System Health Score') ?></h2>
        <p style="color: var(--text-main); margin: 0 0 4px 0; font-weight: 500; font-size: 14.5px;"><?= $health_text ?></p>
        <p style="color: var(--text-muted); margin: 0; font-size: 13px;"><?= p_lang('health_score_desc', 'Evaluated based on PHP configuration, required extensions, and directory permissions.') ?></p>
    </div>
    <div style="width: 96px; height: 96px; flex-shrink: 0; border-radius: 50%; border: 6px solid <?= $health_color ?>; display: flex; align-items: center; justify-content: center; font-size: 30px; font-weight: 800; color: <?= $health_color ?>; background: var(--bg-surface);">
        <?= $health_score ?>
    </div>
</div>

<div class="panel" style="max-width: 900px; margin: 0 auto 24px auto;">
    <h2 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;"><?= p_lang('config_integrity', 'Configuration Integrity') ?></h2>
    <?php
    $required_keys = [
        // Core System
        'cfg_mainurl', 'cfg_admin_path', 'cfg_admin_ips', 'cfg_trust_proxies', 'cfg_robots', 'cfg_domain',
        'cfg_readonly_mode', 'cfg_maintenance_mode', 'cfg_maintenance_msg',
        
        // Branding & Identity
        'cfg_name', 'cfg_legalname', 'cfg_logolink', 'cfg_favicon', 'cfg_support', 'cfg_googleanalytics',
        
        // Social
        'cfg_social_linkedin', 'cfg_social_instagram', 'cfg_social_x', 'cfg_social_facebook',
        
        // Theme & Admin
        'cfg_active_theme', 'cfg_enable_filemanager', 'cfg_admin_lang', 'cfg_enable_analytics',
        'cfg_admin_user', 'cfg_admin_passhash', 'cfg_admin_sidebar_color',
        
        // Features
        'cfg_enable_ecommerce', 'cfg_ecommerce_currency', 'cfg_enable_captcha',
        
        // SMTP
        'cfg_smtp_host', 'cfg_smtp_port', 'cfg_smtp_user', 'cfg_smtp_pass', 'cfg_smtp_secure', 'cfg_contact_email',
        
        // Payment Gateways
        'cfg_paytr_m_id', 'cfg_paytr_m_key', 'cfg_paytr_m_salt', 'cfg_paytr_test',
        'cfg_bank_details'
    ];
    $missing_configs = [];
    foreach ($required_keys as $key) {
        if (!isset($GLOBALS[$key])) $missing_configs[] = $key;
    }
    ?>

    <?php if (empty($missing_configs)): ?>
        <div style="display: flex; align-items: center; gap: 12px; color: #10b981; font-size: 14px; font-weight: 500;">
            <i class="las la-check-circle"></i>
            <?= p_lang('config_ok', 'All mandatory configuration variables are present and healthy.') ?>
        </div>
    <?php else: ?>
        <div style="background: rgba(245, 158, 11, 0.05); border-left: 4px solid #f59e0b; padding: 16px; border-radius: 4px;">
            <div style="display: flex; align-items: center; gap: 12px; color: #d97706; font-size: 15px; font-weight: 600; margin-bottom: 8px;">
                <i class="las la-exclamation-triangle"></i>
                <?= p_lang('config_missing_alert', 'Missing Configuration Variables') ?>
            </div>
            <p style="color: var(--text-muted); font-size: 13px; margin: 0 0 16px 0;">
                <?= p_lang('config_missing_desc', "The following variables are missing in your inc/config.php file. This can lead to system instability or missing features.") ?>
            </p>
            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 20px;">
                <?php foreach ($missing_configs as $m): ?>
                    <code style="background: rgba(0,0,0,0.2); padding: 4px 8px; border-radius: 4px; font-size: 11px;"><?= $m ?></code>
                <?php endforeach; ?>
            </div>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="heal_config" value="1">
                <button type="submit" class="btn btn-warning" style="background-color: #f59e0b; color: white; padding: 8px 16px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                    <i class="las la-hand-holding-medical"></i> <?= p_lang('repair_config', 'Repair & Restore Configuration') ?>
                </button>
            </form>
        </div>
    <?php endif; ?>

    <?php
    $active_sandboxes = [];
    if (isset($cfg_paytr_test) && $cfg_paytr_test == '1') $active_sandboxes[] = 'PayTR';
    ?>
    
    <?php if (!empty($active_sandboxes)): ?>
        <div style="margin-top: 20px; background: rgba(59, 130, 246, 0.05); border-left: 4px solid #3b82f6; padding: 16px; border-radius: 4px;">
            <div style="display: flex; align-items: center; gap: 12px; color: #2563eb; font-size: 15px; font-weight: 600; margin-bottom: 8px;">
                <i class="las la-flask"></i>
                <?= p_lang('sandbox_active_alert', 'Sandbox Environments Active') ?>
            </div>
            <p style="color: var(--text-muted); font-size: 13px; margin: 0;">
                <?= p_lang('sandbox_active_desc', "The following gateways are currently in Test Mode: ") ?>
                <strong><?= implode(', ', $active_sandboxes) ?></strong>.
                <?= p_lang('sandbox_warning', "Real payments will NOT be processed while these are active.") ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<div class="panel" style="max-width: 900px; margin: 0 auto 24px auto;">
    <h2 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;"><i class="las la-bolt" style="color:#f59e0b;"></i> <?= p_lang('html_cache', 'Static HTML Cache') ?></h2>
    <?php $cStatus = PurifyVolt::getCacheStatus(); ?>
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="font-size: 14px; font-weight: 600; color: <?= $cStatus['ok'] ? 'var(--success-color)' : 'var(--danger-color)' ?>;">
                Status: <?= $cStatus['ok'] ? 'Operational' : $cStatus['msg'] ?>
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                Currently holding <strong><?= $cStatus['count'] ?></strong> static HTML page(s).
            </div>
        </div>
        <a href="settings?action=flush_cache" class="btn btn-secondary" style="font-size: 12px;">
            <i class="las la-trash-can"></i> Force Purge
        </a>
    </div>
</div>

<div class="panel" style="max-width: 900px; margin: 0 auto 24px auto;">
    <h2 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;"><?= p_lang('system_backup', 'System Backup') ?></h2>
    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 16px;">
        <?= p_lang('backup_desc', 'Download a complete ZIP archive of your website files, including your configurations, themes, and uploaded assets.') ?>
    </p>
    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <input type="hidden" name="download_backup" value="1">
        <button type="submit" class="btn btn-primary" style="background-color: var(--primary-color); color: white; padding: 10px 16px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
            <i class="las la-file-zipper"></i> <?= p_lang('download_backup', 'Download Full Backup') ?>
        </button>
    </form>
</div>

<div class="panel" style="max-width: 900px; margin: 0 auto;">
    <h2 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">PHP Runtime</h2>

    <div style="display:grid;grid-template-columns:1fr;gap:10px;">
        <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
            <div style="color:var(--text-muted);"><?= p_lang('php_version_label', 'PHP Version') ?></div>
            <div style="display:flex;gap:10px;align-items:center;">
                <div style="font-weight:600;"><?= htmlspecialchars($php_version) ?></div>
                <?= bool_badge($php_ok, p_lang('recommended', 'Recommended'), p_lang('upgrade', 'Upgrade')) ?>
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
            <div style="color:var(--text-muted);"><?= p_lang('sapi_label', 'SAPI') ?></div>
            <div style="font-weight:600;"><?= htmlspecialchars(PHP_SAPI) ?></div>
        </div>

        <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
            <div style="color:var(--text-muted);"><?= p_lang('memory_limit_label', 'Memory Limit') ?></div>
            <div style="font-weight:600;"><?= htmlspecialchars((string)ini_get('memory_limit')) ?></div>
        </div>

        <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
            <div style="color:var(--text-muted);">Max Execution Time</div>
            <div style="font-weight:600;"><?= htmlspecialchars((string)ini_get('max_execution_time')) ?>s</div>
        </div>
    </div>
</div>

<div class="panel" style="max-width: 900px; margin: 24px auto 0 auto;">
    <h2 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">Extensions</h2>

    <div style="display:grid;grid-template-columns:1fr;gap:8px;">
        <?php foreach ($extensions_required as $ext => $desc): ?>
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
                <div>
                    <div style="font-weight:600;"><?= htmlspecialchars($ext) ?></div>
                    <div style="color:var(--text-muted);font-size:12px;">Required - <?= htmlspecialchars($desc) ?></div>
                </div>
                <?= bool_badge(extension_loaded($ext), 'Loaded', 'Missing') ?>
            </div>
        <?php endforeach; ?>

        <div style="height:1px;background:rgba(255,255,255,.06);margin:8px 0;"></div>

        <?php foreach ($extensions_optional as $ext => $desc): ?>
            <?php
                $loaded = ($ext === 'opcache')
                    ? (extension_loaded('Zend OPcache') || extension_loaded('opcache'))
                    : extension_loaded($ext);
                
                // Extra check for GD WebP support
                if ($ext === 'gd' && $loaded && !function_exists('imagewebp')) {
                    $loaded = false; // Mark as missing if no WebP support
                }
            ?>
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
                <div>
                    <div style="font-weight:600;"><?= htmlspecialchars($ext) ?></div>
                    <div style="color:var(--text-muted);font-size:12px;"><?= ($ext === 'gd' ? 'Crucial' : 'Optional') ?> - <?= htmlspecialchars($desc) ?></div>
                </div>
                <?= bool_badge($loaded, 'Loaded', ($ext === 'gd' ? 'No WebP Support' : 'Not loaded')) ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="panel" style="max-width: 900px; margin: 24px auto 0 auto;">
    <h2 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">INI / Security</h2>

    <div style="display:grid;grid-template-columns:1fr;gap:8px;">
        <?php foreach ($ini_checks as $key => $cfg): ?>
            <?php $val = (string)ini_get($key); ?>
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
                <div>
                    <div style="font-weight:600;"><?= htmlspecialchars($cfg['label']) ?></div>
                    <div style="color:var(--text-muted);font-size:12px;"><code><?= htmlspecialchars($key) ?></code> = <code><?= htmlspecialchars($val) ?></code></div>
                </div>
                <?= bool_badge($val === (string)$cfg['expected'], 'Good', 'Check') ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="panel" style="max-width: 900px; margin: 24px auto 0 auto;">
    <h2 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">Disk / Process</h2>

    <div style="display:grid;grid-template-columns:1fr;gap:10px;">
        <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
            <div style="color:var(--text-muted);">Disk Path</div>
            <div style="font-weight:600;"><code><?= htmlspecialchars($disk_path) ?></code></div>
        </div>

        <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
            <div style="color:var(--text-muted);">Disk Free</div>
            <div style="font-weight:600;">
                <?= $disk_free !== false ? htmlspecialchars(format_bytes($disk_free)) : 'Unavailable' ?>
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
            <div style="color:var(--text-muted);">Disk Total</div>
            <div style="font-weight:600;">
                <?= $disk_total !== false ? htmlspecialchars(format_bytes($disk_total)) : 'Unavailable' ?>
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
            <div style="color:var(--text-muted);">PHP Memory Usage</div>
            <div style="font-weight:600;">
                <?= htmlspecialchars(format_bytes(memory_get_usage(true))) ?> (peak <?= htmlspecialchars(format_bytes(memory_get_peak_usage(true))) ?>)
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
            <div style="color:var(--text-muted);">Load Average</div>
            <div style="font-weight:600;">
                <?php
                    $load = function_exists('sys_getloadavg') ? @sys_getloadavg() : false;
                    if (is_array($load)) {
                        echo htmlspecialchars(implode(' / ', array_map('strval', $load)));
                    } else {
                        echo 'Unavailable';
                    }
                ?>
            </div>
        </div>
    </div>
</div>

<div class="panel" style="max-width: 900px; margin: 24px auto 0 auto;">
    <h2 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;">OPcache</h2>

    <div style="display:grid;grid-template-columns:1fr;gap:10px;">
        <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
            <div style="color:var(--text-muted);">Extension</div>
            <div style="display:flex;gap:10px;align-items:center;">
                <div style="font-weight:600;">Zend OPcache</div>
                <?= bool_badge($opcache_enabled, 'Loaded', 'Not loaded') ?>
            </div>
        </div>

        <?php if (is_array($opcache_status) && isset($opcache_status['opcache_enabled'])): ?>
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
                <div style="color:var(--text-muted);">opcache_enabled</div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <div style="font-weight:600;"><?= $opcache_status['opcache_enabled'] ? 'true' : 'false' ?></div>
                    <?= bool_badge((bool)$opcache_status['opcache_enabled'], 'Enabled', 'Disabled') ?>
                </div>
            </div>
        <?php endif; ?>

        <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
            <div style="color:var(--text-muted);">opcache.enable (ini)</div>
            <div style="font-weight:600;"><code><?= htmlspecialchars((string)ini_get('opcache.enable')) ?></code></div>
        </div>

        <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;">
            <div style="color:var(--text-muted);">opcache.memory_consumption</div>
            <div style="font-weight:600;"><code><?= htmlspecialchars((string)ini_get('opcache.memory_consumption')) ?></code></div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
