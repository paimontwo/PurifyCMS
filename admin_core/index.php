<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/version.php';

$base_url = rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path;
$admin_page_title = p_lang('dashboard_overview', 'Dashboard Overview');
$admin_active_nav = 'index';
require __DIR__ . '/includes/header.php';
?>
<?php
// Calculate Stats
$active_theme = $cfg_active_theme ?? 'purifydefault';
$pages_dir = __DIR__ . '/../themes/' . $active_theme . '/pages';
$page_count = 0;
if (is_dir($pages_dir)) {
    $page_count = count(array_filter(scandir($pages_dir), function($f) {
        return pathinfo($f, PATHINFO_EXTENSION) === 'php' && $f !== '.' && $f !== '..';
    }));
}

$lang = PurifyKernel::get_active_lang();
$random_index = rand(1, 5);
$random_tip = p_lang('eco_tip_' . $random_index);


// --- System Pulse Calculations ---
function get_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (float)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

$mem_usage = memory_get_usage(true);
$mem_limit = ini_get('memory_limit');
if (!$mem_limit || $mem_limit == -1) $mem_limit = '512M'; // Standard Fallback

$mem_limit_bytes = get_bytes($mem_limit);
$mem_percent = ($mem_limit_bytes > 0) ? round(($mem_usage / $mem_limit_bytes) * 100, 1) : 0;
if ($mem_percent > 100) $mem_percent = 100;

$load_time = round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000, 2);
?>

<div class="bento-grid dashboard-grid">
    <div class="panel">
        <div style="color: var(--success-color); font-size: 24px; margin-bottom: 16px;"><i class="las la-bolt"></i></div>
        <div style="display: flex; align-items: center; gap: 12px; font-size: 28px; font-weight: 600; line-height: 1; margin-bottom: 12px;">
            <div style="width: 10px; height: 10px; background: var(--success-color); border-radius: 50%;"></div>
            <?= p_lang('active', 'Active') ?>
        </div>
        <div style="color: var(--text-muted); font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 500;"><?= p_lang('core_status', 'Core Engine Status') ?></div>
    </div>

    <div class="panel">
        <div style="color: #6366f1; font-size: 24px; margin-bottom: 16px;"><i class="las la-palette"></i></div>
        <div style="font-size: 24px; font-weight: 600; line-height: 1.2; margin-bottom: 8px;">
            <?= htmlspecialchars($active_theme) ?>
        </div>
        <div style="color: var(--text-muted); font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 500;"><?= p_lang('primary_theme', 'Primary Theme') ?></div>
    </div>

    <div class="panel">
        <div style="color: #f59e0b; font-size: 24px; margin-bottom: 16px;"><i class="las la-images"></i></div>
        <div style="font-size: 28px; font-weight: 600; line-height: 1; margin-bottom: 12px;">
            <?= $page_count ?>
        </div>
        <div style="color: var(--text-muted); font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 500;"><?= p_lang('registered_pages', 'Registered Pages') ?></div>
    </div>
</div>

<div class="panel" style="margin-top: 24px;">
    <h3 style="margin: 0 0 20px 0; font-size: 15px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 10px;">
        <i class="las la-heartbeat" style="color: var(--danger-color);"></i> <?= p_lang('system_pulse', 'System Pulse') ?>
    </h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px;">
        <!-- Memory Gauge -->
        <div style="text-align: center;">
            <div style="position: relative; width: 100px; height: 100px; margin: 0 auto 12px auto;">
                <svg viewBox="0 0 36 36" style="width: 100%; height: 100%; transform: rotate(-90deg);">
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="3" />
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="var(--primary-color)" stroke-width="3" stroke-dasharray="<?= $mem_percent ?>, 100" />
                </svg>
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 18px; font-weight: 700; color: var(--text-main);">
                    <?= $mem_percent ?>%
                </div>
            </div>
            <div style="font-size: 13px; font-weight: 600; color: var(--text-primary);"><?= p_lang('memory_usage', 'Memory Usage') ?></div>
            <div style="font-size: 11px; color: var(--text-muted);"><?= round($mem_usage / 1024 / 1024, 2) ?> MB used</div>
        </div>

        <!-- Latency -->
        <div style="text-align: center;">
            <div style="font-size: 32px; font-weight: 800; color: var(--success-color); margin-top: 15px; margin-bottom: 25px;">
                <?= $load_time ?> <span style="font-size: 14px; font-weight: 500; opacity: 0.6;">ms</span>
            </div>
            <div style="font-size: 13px; font-weight: 600; color: var(--text-primary);"><?= p_lang('load_time', 'Load Time') ?></div>
            <div style="font-size: 11px; color: var(--text-muted);">Real-time kernel check</div>
        </div>

        <!-- OS & PHP Info -->
        <div style="display: flex; flex-direction: column; justify-content: center; gap: 8px;">
            <div style="display: flex; justify-content: space-between; font-size: 13px;">
                <span style="color: var(--text-muted);"><?= p_lang('php_version_label', 'PHP Version') ?></span>
                <span style="font-weight: 600;"><?= PHP_VERSION ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px;">
                <span style="color: var(--text-muted);"><?= p_lang('kernel_version_label', 'Kernel') ?></span>
                <span style="font-weight: 600;">v<?= PURIFYCMS_VERSION ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px;">
                <span style="color: var(--text-muted);"><?= p_lang('server_os', 'Server OS') ?></span>
                <span style="font-weight: 600;"><?= PHP_OS_FAMILY ?></span>
            </div>
        </div>
    </div>
</div>

<div class="panel" style="margin-top: 24px; background: linear-gradient(135deg, var(--panel-bg) 0%, rgba(99, 102, 241, 0.05) 100%);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h2 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600;"><?= p_lang('welcome_back', 'Welcome back to the future of CMS.') ?></h2>
            <p style="color: var(--text-muted); font-size: 14px; margin: 0; max-width: 600px;">
                <?= p_lang('dashboard_desc', 'PurifyCMS is currently running in High Performance File-Based Mode. Frontend pages are served directly from theme files with no database calls.') ?>
            </p>
        </div>
        <div class="badge bg-primary" style="padding: 8px 16px; font-size: 12px;">V<?= htmlspecialchars(PURIFYCMS_VERSION) ?></div>
    </div>
</div>

<div class="panel" style="margin-top: 24px; padding: 24px; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.15); border-left: 6px solid #10b981; border-radius: var(--radius);">
    <div style="font-size: 15px; color: var(--text-primary); line-height: 1.6;">
        <?= $random_tip ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
