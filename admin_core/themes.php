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
            $config_content .= "\n\$cfg_active_theme = '" . $new_theme . "';";
        }
        
        if (file_put_contents(__DIR__ . '/../inc/config.php', $config_content, LOCK_EX)) {
            $cfg_active_theme = $new_theme;
            if (class_exists('PurifyVolt')) PurifyVolt::flushHtml();

            $theme_pages_dir = $theme_dir . '/pages';
            if (!is_dir($theme_pages_dir)) {
                @mkdir($theme_pages_dir, 0755, true);
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

$admin_page_title = p_lang('themes', 'Themes');
$admin_page_subtitle = p_lang('themes_subtitle', 'Manage installed themes and review the theme development structure.');
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
.theme-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}
.theme-panel {
    background-color: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: all var(--transition);
}
.theme-panel:hover {
    border-color: var(--primary-color);
    box-shadow: var(--shadow-md);
}
.theme-panel.active-theme {
    border-color: var(--primary-color);
}
.theme-cover {
    height: 140px;
    background-color: var(--bg-body);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
    color: var(--primary-color);
    border-bottom: 1px solid var(--border-color);
    position: relative;
}
.theme-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background-color: var(--success-bg);
    color: var(--success-color);
    border: 1px solid rgba(16, 185, 129, 0.25);
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
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
    font-weight: 700;
    color: var(--text-main);
}
.theme-meta {
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 12px;
}
.theme-desc {
    color: var(--text-muted);
    font-size: 13px;
    line-height: 1.5;
    margin: 0 0 20px 0;
    flex-grow: 1;
}
.guide-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
}
.guide-block {
    background: var(--bg-body);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 18px;
}
.guide-block h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
    font-weight: 700;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 8px;
}
.guide-block h4 i {
    color: var(--primary-color);
    font-size: 17px;
}
.guide-block p {
    margin: 0 0 12px 0;
    font-size: 13px;
    color: var(--text-muted);
    line-height: 1.55;
}
.guide-code {
    background: #0f172a;
    color: #e2e8f0;
    padding: 14px;
    border-radius: 8px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    line-height: 1.55;
    overflow-x: auto;
    margin: 0;
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.guide-var-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.guide-var-list li {
    font-size: 12.5px;
    color: var(--text-muted);
    display: flex;
    align-items: baseline;
    gap: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border-color);
}
.guide-var-list li:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.guide-var-list code {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    color: var(--primary-color);
    padding: 2px 6px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}
</style>

<div class="theme-grid">
    <?php foreach ($themes as $theme): ?>
        <?php $isActive = ($cfg_active_theme === $theme['folder']); ?>
        
        <div class="theme-panel <?= $isActive ? 'active-theme' : '' ?>">
            <div class="theme-cover">
                <i class="las la-palette"></i>
                <?php if ($isActive): ?>
                    <div class="theme-badge"><i class="las la-check"></i> <?= p_lang('active', 'Active') ?></div>
                <?php endif; ?>
            </div>
            
            <div class="theme-info">
                <h3 class="theme-title"><?= htmlspecialchars($theme['name'] ?? $theme['folder']) ?></h3>
                <div class="theme-meta">v<?= htmlspecialchars($theme['version'] ?? '1.0') ?> &bull; <?= htmlspecialchars($theme['author'] ?? 'Unknown') ?> &bull; <code>themes/<?= htmlspecialchars($theme['folder']) ?></code></div>
                <p class="theme-desc"><?= htmlspecialchars($theme['description'] ?? 'PurifyCMS theme package.') ?></p>
                
                <div style="display: flex; gap: 8px;">
                    <?php if ($isActive): ?>
                        <a href="<?= htmlspecialchars($base_url . '/pages') ?>" class="btn btn-primary btn-sm" style="flex: 1;">
                            <i class="las la-file-alt"></i> <?= p_lang('pages', 'Pages') ?>
                        </a>
                        <?php if (!empty($cfg_enable_filemanager)): ?>
                        <a href="<?= htmlspecialchars($base_url . '/filemanager') ?>" class="btn btn-secondary btn-sm" style="flex: 1;">
                            <i class="las la-code"></i> <?= p_lang('edit_code', 'Edit Files') ?>
                        </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="post" style="margin:0; width: 100%;">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="activate_theme" value="<?= htmlspecialchars($theme['folder']) ?>">
                            <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%;"><?= p_lang('activate_theme', 'Activate Theme') ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Theme Development Guide -->
<div class="panel">
    <div class="panel-header">
        <div>
            <h3 class="panel-title">
                <i class="las la-book-open"></i> <?= p_lang('theme_dev_guide', 'Theme Development Guide') ?>
            </h3>
            <p style="margin: 4px 0 0; font-size: 13px; color: var(--text-muted);">
                <?= p_lang('theme_dev_subtitle', 'Reference guide for creating or customizing themes in the themes/ directory.') ?>
            </p>
        </div>
    </div>

    <div class="guide-grid">
        <!-- 1. Directory Structure -->
        <div class="guide-block">
            <h4><i class="las la-folder-open"></i> <?= p_lang('guide_dir_structure', '1. Directory Structure') ?></h4>
            <p><?= p_lang('guide_dir_desc', 'Create a new folder inside themes/ with the following layout. Only theme.json and src/layout.php are strictly required.') ?></p>
<pre class="guide-code">themes/my-theme/
├── theme.json          # Theme info
├── src/
│   ├── layout.php      # Main layout wrapper
│   ├── head.php        # HTML head &amp; CSS links
│   ├── navbar.php      # Header navigation
│   ├── footer.php      # Site footer
│   └── maintenance.php # Maintenance screen
├── pages/
│   ├── index.php       # Homepage (/)
│   ├── about.php       # /about route
│   ├── contact.php     # /contact route
│   └── 404.php         # 404 Not Found page
└── assets/
    └── css/main.css    # Theme assets</pre>
        </div>

        <!-- 2. Manifest & Layout -->
        <div class="guide-block">
            <h4><i class="las la-file-code"></i> <?= p_lang('guide_manifest_layout', '2. theme.json & src/layout.php') ?></h4>
            <p><?= p_lang('guide_manifest_desc', 'Define theme metadata in theme.json, and output $page_content inside src/layout.php:') ?></p>
<pre class="guide-code">// theme.json
{
  "name": "My Custom Theme",
  "description": "Custom theme for PurifyCMS.",
  "version": "1.0.0",
  "author": "Your Name"
}

// src/layout.php
&lt;?php
require_once __DIR__ . '/head.php';
require_once __DIR__ . '/navbar.php';
echo $page_content;
require_once __DIR__ . '/footer.php';</pre>
        </div>

        <!-- 3. Visual Editor Zones -->
        <div class="guide-block">
            <h4><i class="las la-edit"></i> <?= p_lang('guide_editable_zones', '3. Editable Page Zones') ?></h4>
            <p><?= p_lang('guide_zones_desc', 'Any .php file inside pages/ becomes a URL route automatically. Wrap HTML blocks in purify:zone comments to make them editable in the Visual Editor:') ?></p>
<pre class="guide-code">&lt;?php
$title = "Services";
$desc = "Page meta description.";
?&gt;

&lt;!-- purify:zone:hero_start --&gt;
&lt;section class="hero"&gt;
    &lt;h1&gt;Our Services&lt;/h1&gt;
    &lt;p&gt;Editable content block.&lt;/p&gt;
&lt;/section&gt;
&lt;!-- purify:zone:hero_end --&gt;</pre>
        </div>

        <!-- 4. Global Template Variables -->
        <div class="guide-block">
            <h4><i class="las la-code"></i> <?= p_lang('guide_global_vars', '4. Available Template Variables') ?></h4>
            <p><?= p_lang('guide_vars_desc', 'These variables from inc/config.php are automatically available in all layout and page files:') ?></p>
            <ul class="guide-var-list">
                <li><code>$cfg_mainurl</code> <span><?= p_lang('var_mainurl', 'Base site URL (without trailing slash)') ?></span></li>
                <li><code>$cfg_name</code> <span><?= p_lang('var_name', 'Site title configured in Settings') ?></span></li>
                <li><code>$cfg_legalname</code> <span><?= p_lang('var_legalname', 'Company / legal entity name') ?></span></li>
                <li><code>$cfg_logolink</code> <span><?= p_lang('var_logolink', 'Logo URL (empty if not set)') ?></span></li>
                <li><code>$cfg_support</code> <span><?= p_lang('var_support', 'Public support email address') ?></span></li>
                <li><code>$cfg_enable_ecommerce</code> <span><?= p_lang('var_ecommerce', 'Boolean: true when store module is enabled') ?></span></li>
                <li><code>$request_path</code> <span><?= p_lang('var_request_path', 'Current URL path (e.g. "about" or "")') ?></span></li>
            </ul>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
