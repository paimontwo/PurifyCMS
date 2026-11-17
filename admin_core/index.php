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

$active_theme = $cfg_active_theme ?? 'purifydefault';
$pages_dir = __DIR__ . '/../themes/' . $active_theme . '/pages';
$page_count = 0;
if (is_dir($pages_dir)) {
    $page_count = count(array_filter(scandir($pages_dir), function($f) {
        return pathinfo($f, PATHINFO_EXTENSION) === 'php' && $f !== '.' && $f !== '..';
    }));
}

$messages_dir = __DIR__ . '/../inc/data/messages';
$recent_messages = [];
$inbox_count = 0;
$unread_count = 0;
if (is_dir($messages_dir)) {
    $msg_files = glob($messages_dir . '/*.json');
    rsort($msg_files);
    foreach ($msg_files as $mf) {
        $mdata = @json_decode(file_get_contents($mf), true);
        if (is_array($mdata)) {
            $inbox_count += count($mdata);
            foreach ($mdata as $m) {
                if (empty($m['read'])) $unread_count++;
                if (count($recent_messages) < 5) {
                    $recent_messages[] = $m;
                }
            }
        }
    }
}

$total_hits_30d = 0;
$stats_file = __DIR__ . '/../inc/data/stats/' . date('Y-m') . '.json';
if (file_exists($stats_file)) {
    $sdata = @json_decode(file_get_contents($stats_file), true);
    if (is_array($sdata)) {
        foreach ($sdata as $day_stat) {
            $total_hits_30d += ($day_stat['hits'] ?? 0);
        }
    }
}

$products_count = 0;
$products_file = __DIR__ . '/../inc/data/ecommerce/products.json';
if (file_exists($products_file)) {
    $pdata = @json_decode(file_get_contents($products_file), true);
    if (is_array($pdata)) $products_count = count($pdata);
}

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
$mem_limit = ini_get('memory_limit') ?: '512M';
$mem_limit_bytes = get_bytes($mem_limit);
$mem_percent = ($mem_limit_bytes > 0) ? round(($mem_usage / $mem_limit_bytes) * 100, 1) : 0;
if ($mem_percent > 100) $mem_percent = 100;

$load_time = round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000, 2);
$cache_status = class_exists('PurifyVolt') ? PurifyVolt::getCacheStatus() : ['ok' => true, 'count' => 0];
?>

<div class="bento-grid" style="margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title"><?= p_lang('monthly_views', 'Monthly Traffic') ?></span>
            <div class="stat-card-icon"><i class="las la-chart-bar"></i></div>
        </div>
        <div class="stat-card-value"><?= number_format($total_hits_30d) ?></div>
        <div class="stat-card-footer">
            <a href="<?= htmlspecialchars($base_url . '/analytics') ?>"><?= p_lang('view_analytics', 'View Analytics') ?> &rarr;</a>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title"><?= p_lang('registered_pages', 'Content Pages') ?></span>
            <div class="stat-card-icon"><i class="las la-file-alt"></i></div>
        </div>
        <div class="stat-card-value"><?= $page_count ?></div>
        <div class="stat-card-footer">
            <a href="<?= htmlspecialchars($base_url . '/pages') ?>"><?= p_lang('view_all_pages', 'Manage Pages') ?> &rarr;</a>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title"><?= p_lang('messages', 'Inbound Messages') ?></span>
            <div class="stat-card-icon"><i class="las la-envelope"></i></div>
        </div>
        <div class="stat-card-value">
            <?= $inbox_count ?>
            <?php if ($unread_count > 0): ?>
                <span class="badge badge-success" style="font-size: 11px; vertical-align: middle; margin-left: 6px;"><?= $unread_count ?> <?= p_lang('new', 'New') ?></span>
            <?php endif; ?>
        </div>
        <div class="stat-card-footer">
            <a href="<?= htmlspecialchars($base_url . '/messages') ?>"><?= p_lang('open_inbox', 'Open Inbox') ?> &rarr;</a>
        </div>
    </div>

    <?php if (!empty($cfg_enable_ecommerce)): ?>
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title"><?= p_lang('products', 'Store Products') ?></span>
            <div class="stat-card-icon"><i class="las la-shopping-bag"></i></div>
        </div>
        <div class="stat-card-value"><?= $products_count ?></div>
        <div class="stat-card-footer">
            <a href="<?= htmlspecialchars($base_url . '/products') ?>"><?= p_lang('manage_products', 'Manage Store') ?> &rarr;</a>
        </div>
    </div>
    <?php else: ?>
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title"><?= p_lang('primary_theme', 'Active Theme') ?></span>
            <div class="stat-card-icon"><i class="las la-palette"></i></div>
        </div>
        <div class="stat-card-value" style="font-size: 20px;"><?= htmlspecialchars($active_theme) ?></div>
        <div class="stat-card-footer">
            <a href="<?= htmlspecialchars($base_url . '/themes') ?>"><?= p_lang('manage_themes', 'Manage Themes') ?> &rarr;</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;" class="dashboard-main-split">
    <div>
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title"><i class="las la-compass"></i> <?= p_lang('quick_actions', 'Quick Actions') ?></h3>
                <a href="<?= htmlspecialchars($cfg_mainurl) ?>" target="_blank" class="btn btn-secondary btn-sm">
                    <i class="las la-external-link-alt"></i> <?= p_lang('view_site', 'Visit Storefront') ?>
                </a>
            </div>

            <div class="quick-actions-grid">
                <a href="<?= htmlspecialchars($base_url . '/pages') ?>" class="quick-action-card">
                    <i class="las la-file-medical"></i>
                    <div>
                        <span><?= p_lang('pages', 'Pages') ?></span>
                        <small style="display:block; margin-top:2px;"><?= p_lang('quick_pages_desc', 'Edit content & layouts') ?></small>
                    </div>
                </a>

                <a href="<?= htmlspecialchars($base_url . '/themes') ?>" class="quick-action-card">
                    <i class="las la-palette"></i>
                    <div>
                        <span><?= p_lang('themes', 'Themes') ?></span>
                        <small style="display:block; margin-top:2px;"><?= p_lang('quick_themes_desc', 'Switch or customize design') ?></small>
                    </div>
                </a>

                <?php if (!empty($cfg_enable_filemanager)): ?>
                <a href="<?= htmlspecialchars($base_url . '/filemanager') ?>" class="quick-action-card">
                    <i class="las la-folder-open"></i>
                    <div>
                        <span><?= p_lang('file_manager', 'File Manager') ?></span>
                        <small style="display:block; margin-top:2px;"><?= p_lang('quick_files_desc', 'Browse & edit files') ?></small>
                    </div>
                </a>
                <?php endif; ?>

                <a href="<?= htmlspecialchars($base_url . '/settings') ?>" class="quick-action-card">
                    <i class="las la-sliders-h"></i>
                    <div>
                        <span><?= p_lang('settings', 'Settings') ?></span>
                        <small style="display:block; margin-top:2px;"><?= p_lang('quick_settings_desc', 'General, SEO & SMTP') ?></small>
                    </div>
                </a>

                <a href="<?= htmlspecialchars($base_url . '/locales') ?>" class="quick-action-card">
                    <i class="las la-language"></i>
                    <div>
                        <span><?= p_lang('locales', 'Translations') ?></span>
                        <small style="display:block; margin-top:2px;"><?= p_lang('quick_locales_desc', 'Manage language strings') ?></small>
                    </div>
                </a>

                <a href="<?= htmlspecialchars($base_url . '/health') ?>" class="quick-action-card">
                    <i class="las la-shield-alt"></i>
                    <div>
                        <span><?= p_lang('health', 'System Health') ?></span>
                        <small style="display:block; margin-top:2px;"><?= p_lang('quick_health_desc', 'Diagnostics & backups') ?></small>
                    </div>
                </a>
            </div>
        </div>

        <div class="panel" style="margin-bottom: 0;">
            <div class="panel-header">
                <h3 class="panel-title"><i class="las la-inbox"></i> <?= p_lang('recent_messages', 'Recent Inbound Messages') ?></h3>
                <a href="<?= htmlspecialchars($base_url . '/messages') ?>" class="btn btn-secondary btn-sm"><?= p_lang('view_all', 'View All') ?></a>
            </div>

            <?php if (empty($recent_messages)): ?>
                <div style="text-align: center; padding: 32px 16px; color: var(--text-muted); font-size: 13.5px;">
                    <i class="las la-envelope-open" style="font-size: 32px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                    <?= p_lang('no_messages_found', 'No messages received yet.') ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?= p_lang('sender', 'Sender') ?></th>
                                <th><?= p_lang('message', 'Message') ?></th>
                                <th><?= p_lang('date', 'Date') ?></th>
                                <th style="text-align: right;"><?= p_lang('status', 'Status') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_messages as $rm): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($rm['name'] ?? '-') ?></div>
                                    <div style="font-size: 11.5px; color: var(--text-muted);"><?= htmlspecialchars($rm['email'] ?? '-') ?></div>
                                </td>
                                <td style="max-width: 260px;">
                                    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-muted); font-size: 13px;">
                                        <?= htmlspecialchars($rm['message'] ?? '') ?>
                                    </div>
                                </td>
                                <td style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">
                                    <?= htmlspecialchars($rm['date'] ?? '') ?>
                                </td>
                                <td style="text-align: right;">
                                    <?php if (empty($rm['read'])): ?>
                                        <span class="badge badge-success"><?= p_lang('new', 'New') ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-neutral"><?= p_lang('read', 'Read') ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="panel" style="margin-bottom: 0;">
            <div class="panel-header">
                <h3 class="panel-title"><i class="las la-server"></i> <?= p_lang('system_telemetry', 'System Status') ?></h3>
                <span class="badge badge-primary">v<?= htmlspecialchars(PURIFYCMS_VERSION) ?></span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="padding: 14px; background: var(--bg-body); border-radius: 10px; border: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-size: 12px; font-weight: 600; color: var(--text-muted);"><?= p_lang('memory_usage', 'Memory Usage') ?></span>
                        <span style="font-size: 12.5px; font-weight: 700; color: var(--text-main);"><?= round($mem_usage / 1024 / 1024, 2) ?> MB / <?= htmlspecialchars($mem_limit) ?></span>
                    </div>
                    <div style="height: 6px; background: var(--border-color); border-radius: 3px; overflow: hidden;">
                        <div style="height: 100%; width: <?= max($mem_percent, 3) ?>%; background: var(--primary-color); border-radius: 3px;"></div>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><?= p_lang('load_time', 'Response Time') ?></span>
                        <span style="font-weight: 600; color: var(--success-color);"><?= $load_time ?> ms</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><?= p_lang('html_cache', 'Static HTML Cache') ?></span>
                        <span style="font-weight: 600; color: var(--text-main);"><?= (int)($cache_status['count'] ?? 0) ?> <?= p_lang('cached_pages', 'pages') ?></span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><?= p_lang('php_version_label', 'PHP Runtime') ?></span>
                        <span style="font-weight: 600; color: var(--text-main);"><?= PHP_VERSION ?> (<?= PHP_SAPI ?>)</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><?= p_lang('server_os', 'Operating System') ?></span>
                        <span style="font-weight: 600; color: var(--text-main);"><?= PHP_OS_FAMILY ?></span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><?= p_lang('maintenance_mode', 'Maintenance Mode') ?></span>
                        <?php if (!empty($cfg_maintenance_mode)): ?>
                            <span class="badge badge-warning"><?= p_lang('active', 'Active') ?></span>
                        <?php else: ?>
                            <span class="badge badge-neutral"><?= p_lang('disabled', 'Off') ?></span>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--text-muted);"><?= p_lang('ecommerce_module', 'E-Commerce') ?></span>
                        <?php if (!empty($cfg_enable_ecommerce)): ?>
                            <span class="badge badge-success"><?= p_lang('enabled', 'Enabled') ?></span>
                        <?php else: ?>
                            <span class="badge badge-neutral"><?= p_lang('disabled', 'Off') ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display: flex; gap: 8px; margin-top: 4px;">
                    <a href="<?= htmlspecialchars($base_url . '/settings?action=flush_cache') ?>" class="btn btn-secondary btn-sm" style="flex: 1;">
                        <i class="las la-bolt" style="color: var(--success-color);"></i> <?= p_lang('clear_cache', 'Clear Cache') ?>
                    </a>
                    <a href="<?= htmlspecialchars($base_url . '/update') ?>" class="btn btn-secondary btn-sm" style="flex: 1;">
                        <i class="las la-sync"></i> <?= p_lang('updates', 'Updates') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 960px) {
    .dashboard-main-split {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
