<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/kernel.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ' . rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path . '/login');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'toggle_analytics') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    $new_status = ($_POST['status'] === '1');
    $config_file = __DIR__ . '/../inc/config.php';
    $config_content = file_get_contents($config_file);
    
    if (strpos($config_content, '$cfg_enable_analytics') !== false) {
        $config_content = preg_replace('/\$cfg_enable_analytics\s*=\s*(true|false);/', '$cfg_enable_analytics = ' . ($new_status ? 'true' : 'false') . ';', $config_content);
    } else {
        $config_content = rtrim($config_content);
        $config_content .= "\n" . '$cfg_enable_analytics = ' . ($new_status ? 'true' : 'false') . ';' . "\n";
    }
    
    if (file_put_contents($config_file, $config_content, LOCK_EX) !== false) {
        header('Location: analytics?saved=1');
        exit;
    }
}

$admin_page_title = p_lang('analytics', 'Analytics');
$admin_page_subtitle = p_lang('analytics_subtitle', 'Track visitor traffic and page popularity.');
$admin_active_nav = 'analytics';

$stats_enabled = $cfg_enable_analytics ?? true;
if ($stats_enabled) {
    PurifyStats::compile();
}

require __DIR__ . '/includes/header.php';

$stats_dir = __DIR__ . '/../inc/data/stats';
$monthly_file = $stats_dir . '/' . date('Y-m') . '.json';
$stats_data = [];
if ($stats_enabled && file_exists($monthly_file)) {
    $stats_data = json_decode(file_get_contents($monthly_file), true) ?: [];
}

$total_hits = 0;
$unique_visitors_set = [];
$top_pages = [];
$chart_days = [];
$peak_hits = 0;
$peak_date = '';

for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $hits = $stats_data[$d]['hits'] ?? 0;
    $chart_days[$d] = $hits;
    $total_hits += $hits;

    if ($hits > $peak_hits) {
        $peak_hits = $hits;
        $peak_date = $d;
    }
    
    if (isset($stats_data[$d]['sessions'])) {
        foreach ($stats_data[$d]['sessions'] as $sess) {
            $unique_visitors_set[$sess] = true;
        }
    }
    
    if (isset($stats_data[$d]['pages'])) {
        foreach ($stats_data[$d]['pages'] as $page => $count) {
            $top_pages[$page] = ($top_pages[$page] ?? 0) + $count;
        }
    }
}

$unique_visitors_count = count($unique_visitors_set);
arsort($top_pages);
$top_pages_slice = array_slice($top_pages, 0, 7);

$daily_avg = round($total_hits / 30, 1);
$top_page_name = !empty($top_pages) ? array_key_first($top_pages) : '-';
$top_page_hits = !empty($top_pages) ? reset($top_pages) : 0;
$stats_file_size_kb = file_exists($monthly_file) ? round(filesize($monthly_file) / 1024, 2) : 0;
?>

<?php if (isset($_GET['saved'])): ?>
    <div class="notice notice-success" style="margin-bottom: 20px;">
        <i class="las la-check-circle"></i> <?= p_lang('settings_updated_success', 'Settings updated successfully.') ?>
    </div>
<?php endif; ?>

<div class="analytics-top-bar">
    <div class="analytics-status-pill">
        <?php if ($stats_enabled): ?>
            <span class="status-dot-pulse"></span>
            <span><?= p_lang('tracking_active', 'Tracking Active') ?></span>
        <?php else: ?>
            <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--text-muted); display: inline-block;"></span>
            <span style="color: var(--text-muted);"><?= p_lang('tracking_paused', 'Tracking Paused') ?></span>
        <?php endif; ?>
    </div>

    <div style="display: flex; align-items: center; gap: 10px;">
        <form method="POST" style="margin: 0;">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="toggle_analytics">
            <input type="hidden" name="status" value="<?= $stats_enabled ? '0' : '1' ?>">
            <button type="submit" class="btn btn-secondary" style="font-size: 13px; padding: 7px 14px;">
                <i class="las <?= $stats_enabled ? 'la-pause' : 'la-play' ?>"></i>
                <span><?= $stats_enabled ? p_lang('pause_tracking', 'Pause Tracking') : p_lang('enable_tracking', 'Enable Tracking') ?></span>
            </button>
        </form>

        <a href="settings" class="btn btn-secondary" style="font-size: 13px; padding: 7px 14px;">
            <i class="las la-sliders-h"></i> <span><?= p_lang('settings', 'Settings') ?></span>
        </a>
    </div>
</div>

<?php if (!$stats_enabled): ?>
    <div class="panel" style="text-align: center; padding: 60px 24px;">
        <div style="width: 56px; height: 56px; border-radius: var(--radius); background: var(--bg-body); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; font-size: 24px; color: var(--text-muted); margin-bottom: 16px;">
            <i class="las la-chart-bar"></i>
        </div>
        <h3 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: var(--text-main);"><?= p_lang('analytics_disabled', 'Analytics System Disabled') ?></h3>
        <p style="margin: 0 auto 20px auto; max-width: 440px; font-size: 13px; color: var(--text-muted); line-height: 1.5;">
            <?= p_lang('stats_empty', 'Enable the system to start collecting traffic data and generate visual insights.') ?>
        </p>
        <form method="POST" style="margin: 0;">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="toggle_analytics">
            <input type="hidden" name="status" value="1">
            <button type="submit" class="btn btn-primary" style="font-size: 13px; padding: 9px 20px;">
                <i class="las la-play"></i> <?= p_lang('enable_tracking', 'Enable Tracking') ?>
            </button>
        </form>
    </div>
<?php else: ?>

    <div class="bento-grid" style="margin-bottom: 20px;">
        <div class="panel stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= p_lang('page_views_30d', 'Total Page Views (30 Days)') ?></span>
                <i class="las la-eye stat-card-icon"></i>
            </div>
            <div class="stat-card-value"><?= number_format($total_hits) ?></div>
            <div class="stat-card-footer"><?= $daily_avg ?> <?= p_lang('daily_average', 'daily average') ?></div>
        </div>

        <div class="panel stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= p_lang('unique_visitors', 'Unique Visitors') ?></span>
                <i class="las la-users stat-card-icon"></i>
            </div>
            <div class="stat-card-value"><?= number_format($unique_visitors_count) ?></div>
            <div class="stat-card-footer"><?= p_lang('last_30_days', 'Last 30 days') ?></div>
        </div>

        <div class="panel stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= p_lang('most_visited_page', 'Top Visited Page') ?></span>
                <i class="las la-file-alt stat-card-icon"></i>
            </div>
            <div class="stat-card-value" style="font-size: 18px; line-height: 1.4;">/<?= htmlspecialchars($top_page_name) ?></div>
            <div class="stat-card-footer"><?= number_format($top_page_hits) ?> <?= p_lang('views', 'views') ?></div>
        </div>

        <div class="panel stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= p_lang('storage_engine', 'Storage Engine') ?></span>
                <i class="las la-database stat-card-icon"></i>
            </div>
            <div class="stat-card-value" style="font-size: 18px; line-height: 1.4;">Flat-File JSON</div>
            <div class="stat-card-footer"><?= $stats_file_size_kb ?> KB <?= p_lang('file_footprint', 'file footprint') ?></div>
        </div>
    </div>

    <!-- 30-Day Traffic Visualizer -->
    <div class="panel" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="margin: 0; font-size: 14px; font-weight: 600; color: var(--text-main);">
                    <?= p_lang('traffic_trend', 'Traffic Activity (Last 30 Days)') ?>
                </h3>
                <span style="font-size: 12px; color: var(--text-muted);">
                    <?= date('d M Y', strtotime('-29 days')) ?> &ndash; <?= date('d M Y') ?>
                </span>
            </div>
            <?php if ($peak_hits > 0): ?>
                <div style="font-size: 12px; color: var(--text-muted); background: var(--bg-body); border: 1px solid var(--border-color); padding: 4px 10px; border-radius: var(--radius);">
                    <i class="las la-chart-area" style="color: var(--primary-color);"></i> <?= p_lang('peak', 'Peak') ?>: <strong><?= number_format($peak_hits) ?></strong> (<?= date('d M', strtotime($peak_date)) ?>)
                </div>
            <?php endif; ?>
        </div>

        <div class="chart-bars-wrap">
            <?php 
            $max_val = max($chart_days) ?: 1;
            foreach ($chart_days as $date => $val): 
                $height = ($val / $max_val) * 100;
                $is_today = ($date === date('Y-m-d'));
            ?>
                <div class="chart-bar-col <?= $is_today ? 'is-today' : '' ?>">
                    <div class="chart-tooltip">
                        <?= date('d M', strtotime($date)) ?>: <?= number_format($val) ?>
                    </div>
                    <div class="chart-bar-fill" style="height: <?= max($height, 4) ?>%;"></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="chart-axis-labels">
            <span><?= date('d M', strtotime('-29 days')) ?></span>
            <span><?= date('d M', strtotime('-15 days')) ?></span>
            <span style="font-weight: 600; color: var(--primary-color);"><?= p_lang('today', 'Today') ?></span>
        </div>
    </div>

    <!-- Detailed Breakdown Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
        <!-- Top Performing Pages -->
        <div class="panel">
            <h3 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                <i class="las la-list-ol" style="color: var(--primary-color);"></i> <?= p_lang('popular_pages', 'Most Visited Pages') ?>
            </h3>

            <?php if (empty($top_pages_slice)): ?>
                <div style="padding: 24px 0; text-align: center; font-size: 13px; color: var(--text-muted);">
                    <?= p_lang('no_data', 'No traffic data recorded yet.') ?>
                </div>
            <?php else: ?>
                <?php 
                $max_page_hits = reset($top_pages_slice) ?: 1;
                foreach ($top_pages_slice as $page => $count): 
                    $pct = $total_hits > 0 ? round(($count / $total_hits) * 100, 1) : 0;
                    $bar_pct = round(($count / $max_page_hits) * 100, 1);
                ?>
                    <div class="page-stats-row">
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                            <span style="font-weight: 500; color: var(--text-main); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 70%;">
                                /<?= htmlspecialchars($page) ?>
                            </span>
                            <span style="font-weight: 600; color: var(--text-main);">
                                <?= number_format($count) ?> <span style="font-size: 11px; font-weight: 400; color: var(--text-muted);">(<?= $pct ?>%)</span>
                            </span>
                        </div>
                        <div style="height: 4px; background: var(--bg-body); border-radius: 2px; overflow: hidden;">
                            <div style="width: <?= $bar_pct ?>%; height: 100%; background: var(--primary-color); border-radius: 2px;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- System Architecture & Integrations -->
        <div class="panel" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h3 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                    <i class="las la-shield-alt" style="color: var(--primary-color);"></i> <?= p_lang('privacy_friendly', 'Privacy & Performance') ?>
                </h3>

                <p style="margin: 0 0 16px 0; font-size: 13px; color: var(--text-muted); line-height: 1.5;">
                    <?= p_lang('analytics_privacy_desc', 'PurifyCMS logs visit counts and anonymous session hashes directly into monthly flat-file JSON storage with zero SQL queries.') ?>
                </p>

                <div style="border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; margin-bottom: 16px; background: var(--bg-body);">
                    <div style="font-size: 12px; font-weight: 600; color: var(--text-main); margin-bottom: 4px;">
                        <?= p_lang('external_integration', 'External Integrations') ?>
                    </div>
                    <?php if (!empty($cfg_googleanalytics)): ?>
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--success-color);">
                            <i class="las la-check-circle"></i>
                            <span>Google Analytics (<?= htmlspecialchars($cfg_googleanalytics) ?>)</span>
                        </div>
                    <?php else: ?>
                        <div style="font-size: 12px; color: var(--text-muted); line-height: 1.4;">
                            <?= p_lang('google_analytics_unlinked', 'Google Analytics is not linked. You can configure your tracking ID in Settings.') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <a href="settings" class="btn btn-secondary" style="font-size: 12.5px; padding: 6px 12px;">
                    <i class="las la-external-link-alt"></i> <?= p_lang('configure_settings', 'Configure in Settings') ?>
                </a>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
