<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/kernel.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login');
    exit;
}

// Handle Analytics Toggle
if (isset($_POST['action']) && $_POST['action'] === 'toggle_analytics') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    $new_status = ($_POST['status'] === '1');
    $config_file = __DIR__ . '/../inc/config.php';
    $config_content = file_get_contents($config_file);
    
    // Replace $cfg_enable_analytics = ...;
    if (strpos($config_content, '$cfg_enable_analytics') !== false) {
        $config_content = preg_replace('/\$cfg_enable_analytics\s*=\s*(true|false);/', '$cfg_enable_analytics = ' . ($new_status ? 'true' : 'false') . ';', $config_content);
    } else {
        $config_content = rtrim($config_content);
        $config_content .= "\n" . '$cfg_enable_analytics = ' . ($new_status ? 'true' : 'false') . ';' . "\n";
    }
    
    if (file_contents_safe_write($config_file, $config_content)) {
        header('Location: analytics?saved=1');
        exit;
    }
}

function file_contents_safe_write($path, $content) {
    return file_put_contents($path, $content, LOCK_EX) !== false;
}

$admin_page_title = p_lang('analytics', 'Analytics');
$admin_page_subtitle = p_lang('analytics_subtitle', 'Track visitor traffic and page popularity.');
$admin_active_nav = 'analytics';

// Load Stats
$stats_enabled = $cfg_enable_analytics ?? true;
if ($stats_enabled) {
    PurifyStats::compile();
}

require __DIR__ . '/includes/header.php';
?>

<div class="panel" style="border-left: 4px solid var(--danger-color); margin-bottom: 24px; background: rgba(220, 38, 38, 0.05);">
    <div style="display: flex; gap: 16px; align-items: flex-start;">
        <div style="font-size: 24px; color: var(--danger-color);"><i class="las la-exclamation-triangle"></i></div>
        <div>
            <h4 style="margin: 0 0 4px 0; color: var(--danger-color);"><?= p_lang('performance_notice', 'Performance & High Traffic Notice') ?></h4>
            <p style="margin: 0; font-size: 13.5px; line-height: 1.5; color: var(--text-main);">
                <?= p_lang('analytics_performance_desc', 'Local analytics tracking can increase disk I/O on extremely high-traffic sites. For enterprise-grade performance, we recommend using Google Analytics integration (Settings -> Google Analytics ID).') ?>
            </p>
        </div>
    </div>
</div>

<?php
$stats_data = [];
$monthly_file = __DIR__ . '/../inc/data/stats/' . date('Y-m') . '.json';
if ($stats_enabled && file_exists($monthly_file)) {
    $stats_data = json_decode(file_get_contents($monthly_file), true) ?: [];
}

// Prepare Data for Charts
$total_hits = 0;
$unique_visitors_set = [];
$top_pages = [];
$chart_days = [];

// Get last 30 days
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $hits = $stats_data[$d]['hits'] ?? 0;
    $chart_days[$d] = $hits;
    $total_hits += $hits;
    
    if (isset($stats_data[$d]['sessions'])) {
        foreach ($stats_data[$d]['sessions'] as $sess) $unique_visitors_set[$sess] = true;
    }
    
    if (isset($stats_data[$d]['pages'])) {
        foreach ($stats_data[$d]['pages'] as $page => $count) {
            $top_pages[$page] = ($top_pages[$page] ?? 0) + $count;
        }
    }
}
$unique_visitors_count = count($unique_visitors_set);
arsort($top_pages);
$top_pages = array_slice($top_pages, 0, 10);
?>

<?php if (isset($_GET['saved'])): ?>
    <div style="background: var(--success-color); color: white; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; animation: slideDown 0.3s ease;">
        <i class="las la-check-circle"></i>
        <span><?= p_lang('success', 'Success') ?>!</span>
    </div>
<?php endif; ?>

<div class="analytics-wrapper">
    
    <!-- Premium Header Section -->
    <div class="analytics-header glass-card mb-4" style="padding: 24px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-surface);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div class="title-group">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="icon-badge" style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary-color); width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 20px;">
                        <i class="las la-chart-line"></i>
                    </div>
                    <div>
                        <h2 style="margin: 0; font-size: 22px; font-weight: 700; color: var(--text-main);"><?= p_lang('analytics', 'Analytics') ?></h2>
                        <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--text-muted);"><?= p_lang('analytics_subtitle', 'Track visitor traffic and page popularity.') ?></p>
                    </div>
                </div>
            </div>
            
            <div class="controls-group" style="display: flex; align-items: center; gap: 16px;">
                <?php if ($stats_enabled): ?>
                    <div class="live-indicator" style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: var(--success-color); background: var(--success-bg); padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(16, 185, 129, 0.2);">
                        <span class="pulse-dot" style="width: 8px; height: 8px; background: currentColor; border-radius: 50%; display: inline-block;"></span>
                        LIVE TRACKING
                    </div>
                <?php endif; ?>

                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="action" value="toggle_analytics">
                    <input type="hidden" name="status" value="<?= $stats_enabled ? '0' : '1' ?>">
                    <button type="submit" class="btn <?= $stats_enabled ? 'btn-secondary' : 'btn-primary' ?>" style="height: 40px; padding: 0 20px; border-radius: 10px; font-weight: 600; letter-spacing: 0.5px; box-shadow: <?= $stats_enabled ? 'none' : '0 4px 12px rgba(var(--primary-rgb), 0.2)' ?>;">
                        <?= $stats_enabled ? '<i class="las la-power-off" style="margin-right: 8px;"></i> ' . p_lang('disable_analytics') : '<i class="las la-play" style="margin-right: 8px;"></i> ' . p_lang('enable_analytics') ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- TIP BOX - Professional Look -->
        <div class="stats-tip" style="margin-top: 24px; background: var(--bg-hover); border-radius: 10px; padding: 12px 16px; border-left: 4px solid var(--warning-color); display: flex; align-items: center; gap: 12px;">
            <i class="las la-bolt" style="color: var(--warning-color); font-size: 16px;"></i>
            <span style="font-size: 13px; color: var(--text-main); line-height: 1.4;">
                <strong style="color: var(--text-main);"><?= p_lang('pro_tip', 'Pro Tip') ?>:</strong> <?= p_lang('analytics_tip', 'Minimal system resource usage.') ?>
            </span>
        </div>
    </div>

    <?php if (!$stats_enabled): ?>
        <div class="card" style="text-align: center; padding: 100px 40px; border: 2px dashed var(--border-color); background: transparent; border-radius: 16px;">
            <div class="big-icon" style="background: var(--bg-hover); width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin: 0 auto 24px; color: var(--text-muted); font-size: 42px;">
                <i class="las la-shield-slash"></i>
            </div>
            <h3 style="font-weight: 700; color: var(--text-main); margin-bottom: 8px;"><?= p_lang('analytics_disabled', 'Analytics System Disabled') ?></h3>
            <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto; line-height: 1.6;"><?= p_lang('stats_empty', 'Enable the system to start collecting traffic data and generate visual insights.') ?></p>
            <form method="POST" style="margin-top: 32px;">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="toggle_analytics">
                <input type="hidden" name="status" value="1">
                <button type="submit" class="btn btn-primary" style="padding: 12px 32px; border-radius: 12px; font-weight: 600; box-shadow: 0 10px 20px rgba(var(--primary-rgb), 0.2);"><?= p_lang('enable_analytics') ?></button>
            </form>
        </div>
    <?php else: ?>

        <!-- Dashboard Hero: Stats Sidebar + Chart -->
        <div style="display: grid; grid-template-columns: 280px 1fr; gap: 24px; margin-bottom: 24px;">
            <!-- Compact Metrics Sidebar -->
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="premium-stat-card card" style="padding: 16px;">
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Hits</div>
                    <div style="font-size: 24px; font-weight: 800; color: var(--text-main); margin-top: 4px;"><?= number_format($total_hits) ?></div>
                </div>
                <div class="premium-stat-card card" style="padding: 16px;">
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Unique Visitors</div>
                    <div style="font-size: 24px; font-weight: 800; color: var(--text-main); margin-top: 4px;"><?= number_format($unique_visitors_count) ?></div>
                </div>
                <div class="premium-stat-card card" style="padding: 16px; background: linear-gradient(135deg, var(--primary-color), var(--primary-hover)); color: white; border: none;">
                    <div style="font-size: 10px; font-weight: 700; opacity: 0.8; text-transform: uppercase;">Active Theme</div>
                    <div style="font-size: 16px; font-weight: 700; margin-top: 4px; text-transform: capitalize;"><?= htmlspecialchars($cfg_active_theme) ?></div>
                </div>
            </div>

            <!-- Performance Chart -->
            <div class="card" style="border-radius: 16px; border: 1px solid var(--border-color); background: var(--bg-surface); overflow: hidden; display: flex; flex-direction: column;">
                <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--text-main);">Traffic Overview (Last 30 Days)</h4>
                </div>
                <div style="padding: 24px; flex: 1; display: flex; flex-direction: column;">
                    <div class="chart-container" style="flex: 1; min-height: 180px; width: 100%; display: flex; align-items: flex-end; gap: 4px;">
                        <?php 
                        $max_val = max($chart_days) ?: 1;
                        foreach ($chart_days as $date => $val): 
                            $height = ($val / $max_val) * 100;
                            $is_today = ($date === date('Y-m-d'));
                        ?>
                            <div class="chart-col" style="flex: 1; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; position: relative;" title="<?= $date ?>: <?= $val ?>">
                                <div class="chart-val-popup"><?= $val ?></div>
                                <div class="bar-fill <?= $is_today ? 'current' : '' ?>" style="height: <?= max($height, 4) ?>%;"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-top: 12px; font-size: 10px; font-weight: 700; color: var(--text-muted); opacity: 0.6;">
                        <span><?= date('d M', strtotime('-29 days')) ?></span>
                        <span style="color: var(--primary-color);"><?= p_lang('today', 'Today') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bento Bottom Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <!-- Top Pages -->
            <div class="card" style="border-radius: 16px; border: 1px solid var(--border-color);">
                <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color);">
                    <h4 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--text-main);">Popular Architecture</h4>
                </div>
                <div style="padding: 16px 20px;">
                    <?php if (empty($top_pages)): ?>
                        <div style="text-align: center; padding: 20px 0; color: var(--text-muted); font-size: 13px;">No data collected.</div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php 
                            $max_hits = reset($top_pages) ?: 1;
                            $rank = 1;
                            foreach ($top_pages as $page => $count): 
                                $percent = ($count / $max_hits) * 100;
                            ?>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                                        <span style="font-weight: 600; color: var(--text-main);">/<?= htmlspecialchars($page) ?></span>
                                        <span style="color: var(--primary-color); font-weight: 700;"><?= number_format($count) ?></span>
                                    </div>
                                    <div style="height: 4px; background: var(--bg-hover); border-radius: 10px; overflow: hidden;">
                                        <div style="width: <?= $percent ?>%; height: 100%; background: var(--primary-color); border-radius: 10px;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Health & Optimization -->
            <div class="card" style="border-radius: 16px; border: 1px solid var(--border-color); background: rgba(var(--primary-rgb), 0.03); display: flex; flex-direction: column; justify-content: center; padding: 30px; position: relative; overflow: hidden;">
                <div style="position: relative; z-index: 2;">
                    <div style="font-weight: 800; font-size: 18px; color: var(--text-main); margin-bottom: 10px;">Zero-Bloat Engine.</div>
                    <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 20px;">Your site is optimized. Traffic tracking logic executes in <strong>~1ms</strong> using flat-file architecture, keeping server load at nearly 0%.</p>
                    <div style="display: inline-flex; align-items: center; gap: 8px; background: var(--bg-surface); padding: 8px 16px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid var(--border-color);">
                        <i class="las la-microchip" style="color:var(--primary-color);"></i> JSON KERNEL: ACTIVE
                    </div>
                </div>
                <i class="las la-bolt" style="position: absolute; right: -20px; bottom: -20px; font-size: 120px; opacity: 0.05; transform: rotate(-15deg); color: var(--primary-color);"></i>
            </div>
        </div>

    <?php endif; ?>
</div>

<style>
:root {
    --primary-rgb: 37, 99, 235;
}
[data-theme="dark"] {
    --primary-rgb: 59, 130, 246;
}

@keyframes slideDown {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.4); opacity: 0.5; }
    100% { transform: scale(1); opacity: 1; }
}

.pulse-dot {
    animation: pulse 2s infinite;
}

.premium-stat-card {
    border: 1px solid var(--border-color);
    border-radius: 16px !important;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.premium-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.bar-fill {
    width: 100%;
    background: rgba(var(--primary-rgb), 0.15);
    border-radius: 6px 6px 0 0;
    transition: height 0.8s cubic-bezier(0.4, 0, 0.2, 1), background 0.3s ease;
    border: 1px solid rgba(var(--primary-rgb), 0.1);
}

.bar-fill.current {
    background: linear-gradient(to top, var(--primary-color), var(--primary-hover));
    box-shadow: 0 0 10px rgba(var(--primary-rgb), 0.3);
}

.chart-col:hover .bar-fill {
    background: var(--primary-color);
}

.chart-val-popup {
    position: absolute;
    top: -30px;
    background: var(--text-main);
    color: var(--bg-surface);
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.2s ease;
    pointer-events: none;
    z-index: 5;
}

.chart-col:hover .chart-val-popup {
    opacity: 1;
    transform: translateY(0);
}

.progress-bar {
    transition: width 1s ease-out;
}

.grid-2 { display: grid; grid-template-columns: 1fr 1fr; }

@media (max-width: 991px) {
    .grid-2 { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .grid-3 { grid-template-columns: 1fr; }
    .analytics-header { padding: 16px !important; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
