<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

require_once __DIR__ . '/../inc/config.php';
$base_url = rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate_theme'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $new_theme = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['activate_theme']);
    $theme_dir = __DIR__ . '/../themes/' . $new_theme;
    
    if (is_dir($theme_dir) && file_exists($theme_dir . '/src/layout.php')) {
        $config_content = file_get_contents(__DIR__ . '/../inc/config.php');
        $pattern = '/\$cfg_active_theme\s*=\s*(["\'])(.*?)\1\s*;/';
        
        if (preg_match($pattern, $config_content)) {
            $config_content = preg_replace($pattern, "\$cfg_active_theme = '" . $new_theme . "';", $config_content);
        } else {
            // If the variable is missing entirely, append it to the end
            $config_content .= "\n\$cfg_active_theme = '" . $new_theme . "';";
        }
        
            if (file_put_contents(__DIR__ . '/../inc/config.php', $config_content, LOCK_EX)) {
                $cfg_active_theme = $new_theme;

                $theme_pages_dir = $theme_dir . '/pages';
                if (!is_dir($theme_pages_dir)) {
                    mkdir($theme_pages_dir, 0777, true);
                }

                $success = p_lang('activate_success', 'Successfully activated the theme: ') . $new_theme . '.';
            } else {
                $error = p_lang('config_write_error', 'Failed to update config.php file permissions.');
            }
        } else {
            $error = p_lang('invalid_theme_error', 'Invalid theme package. Make sure src/layout.php exists!');
        }
}

$themes = [];
$themes_dir = __DIR__ . '/../themes';
if (is_dir($themes_dir)) {
    $dirs = array_diff(scandir($themes_dir), ['..', '.']);
    foreach ($dirs as $dir) {
        if (is_dir($themes_dir . '/' . $dir)) {
            $json_path = $themes_dir . '/' . $dir . '/theme.json';
            if (file_exists($json_path)) {
                $meta = json_decode(file_get_contents($json_path), true);
                if (is_array($meta)) {
                    $meta['folder'] = $dir;
                    $themes[] = $meta;
                }
            }
        }
    }
}

$admin_page_title = p_lang('theme_engine', 'Theme Engine');
$admin_active_nav = 'themes';
require __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?>
    <div class="notice notice-success"><i class="las la-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="notice notice-error"><i class="las la-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<style>
    .theme-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
    
    .theme-panel {
        background-color: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: all var(--transition);
        position: relative;
    }
    
    .theme-panel:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: var(--text-muted);
    }
    
    .theme-panel.active-theme {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 1px var(--primary-color);
    }
    
    .theme-cover {
        height: 160px;
        background-color: var(--bg-hover);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border-color);
        position: relative;
        overflow: hidden;
        transition: color var(--transition);
    }
    
    .theme-panel:hover .theme-cover {
        color: var(--text-main);
    }
    
    .theme-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        background-color: var(--primary-color);
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 100px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .theme-info {
        padding: 20px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }
    
    .theme-title {
        margin: 0 0 4px 0;
        font-size: 16px;
        font-weight: 600;
        color: var(--text-main);
    }
    
    .theme-meta {
        font-size: 12px;
        color: var(--text-muted);
        font-family: inherit;
        margin-bottom: 12px;
    }
    
    .theme-desc {
        color: var(--text-muted);
        font-size: 13px;
        line-height: 1.5;
        margin: 0 0 20px 0;
        flex-grow: 1;
    }
</style>

<div class="theme-grid">
    <?php foreach ($themes as $theme): ?>
        <?php $isActive = ($cfg_active_theme === $theme['folder']); ?>
        
        <div class="theme-panel <?= $isActive ? 'active-theme' : '' ?>">
            <div class="theme-cover">
                <i class="las la-layer-group"></i>
                <?php if ($isActive): ?>
                    <div class="theme-badge"><?= p_lang('active', 'Active') ?></div>
                <?php endif; ?>
            </div>
            
            <div class="theme-info">
                <h3 class="theme-title"><?= htmlspecialchars($theme['name'] ?? $theme['folder']) ?></h3>
                <div class="theme-meta">v<?= htmlspecialchars($theme['version'] ?? '1.0') ?> &bull; <?= htmlspecialchars($theme['author'] ?? 'Unknown') ?></div>
                <p class="theme-desc"><?= htmlspecialchars($theme['description'] ?? 'A template for PurifyCMS.') ?></p>
                
                <div>
                    <?php if ($isActive): ?>
                        <button class="btn" style="width: 100%; background-color: var(--success-bg); color: var(--success-color); cursor: default; border: 1px solid rgba(16, 185, 129, 0.2);">
                            <i class="las la-check"></i> <?= p_lang('currently_active', 'Currently Active') ?>
                        </button>
                    <?php else: ?>
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="activate_theme" value="<?= htmlspecialchars($theme['folder']) ?>">
                            <button type="submit" class="btn btn-secondary" style="width: 100%;"><?= p_lang('activate_theme', 'Activate Theme') ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Premium Themes Store Card -->
    <div class="theme-panel" style="border: 2px dashed var(--border-color); background: rgba(0,0,0,0.01); display: flex; flex-direction: column;">
        <div class="theme-cover" style="background: transparent;">
            <i class="las la-store" style="color: var(--primary-color);"></i>
        </div>
        <div class="theme-info" style="align-items: center; text-align: center; justify-content: center;">
            <h3 class="theme-title"><?= p_lang('discover_premium_themes', 'Discover Premium Themes') ?></h3>
            <p class="theme-desc" style="margin-bottom: 15px;"><?= p_lang('premium_themes_desc', 'Explore professional and premium themes designed specifically for PurifyCMS in our official store.') ?></p>
            <div style="width: 100%; margin-top: auto;">
                <a href="https://www.nywhash.com/themes?cat=PurifyCMS" target="_blank" class="btn btn-primary" style="width: 100%; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="las la-external-link-alt"></i> <?= p_lang('go_to_store', 'Go to Store') ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
