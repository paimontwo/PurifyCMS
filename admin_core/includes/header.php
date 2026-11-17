<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

// The kernel has already booted in index.php

$base_url = rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path;
$admin_page_title = $admin_page_title ?? 'Overview';
$admin_page_subtitle = $admin_page_subtitle ?? 'Manage your content and settings.';
$admin_active_nav = $admin_active_nav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars(p_lang(strtolower(str_replace(' ', '_', $admin_page_title)), $admin_page_title)) ?> - PurifyCMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <?php if($cfg_favicon): ?>
    <link rel="icon" href="<?= htmlspecialchars($cfg_favicon) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css" integrity="sha512-vebUliqxrVkBy3gucMhClmyQP9On/HAWQdKDXRaAlb/FKuTbxkjPKUyqVOxAcGwFDka79eTF+YXwfke1h3/wfg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        (function() {
            var theme = localStorage.getItem('admin_theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
</head>
<body>
<?php if (!empty($cfg_readonly_mode)): ?>
<style>:root { --readonly-offset: 41px; }</style>
<div style="width:100%;background:#0f172a;border-bottom:2px solid var(--primary-color);color:#fff;padding:10px 20px;font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:10px;box-sizing:border-box;position:sticky;top:0;z-index:9999;height: 41px;backdrop-filter:blur(10px);background:rgba(15,23,42,0.9);">
    <i class="las la-shield-alt" style="color:var(--primary-color);font-size:18px;"></i>
    <?= p_lang('hardened_mode_active', 'System Hardened Mode Active (Demo) — All modifications are locked for integrity. Disable via SSH/Config.') ?>
</div>
<?php endif; ?>
    <div class="admin-wrapper">
        <aside class="admin-sidebar" id="adminSidebar" style="background-color: <?= $cfg_admin_sidebar_color ?? 'var(--bg-sidebar)' ?>;">
            <div class="sidebar-brand">
                <img draggable="false" src="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/images/logo.png" alt="PurifyCMS" width="32"> PurifyCMS

            </div>

            
            <ul class="sidebar-nav">
                <li class="nav-section-title"><span><?= p_lang('main_menu', 'Main Menu') ?></span></li>
                <li>
                    <a href="<?= htmlspecialchars($base_url) ?>" class="<?= $admin_active_nav === 'index' ? 'active' : '' ?>">
                        <i class="las la-chart-pie"></i> <?= p_lang('dashboard', 'Dashboard') ?>
                    </a>
                </li>
                <li>
                    <a href="<?= htmlspecialchars($base_url . '/messages') ?>" class="<?= $admin_active_nav === 'messages' ? 'active' : '' ?>">
                        <i class="las la-inbox"></i> <?= p_lang('messages', 'Messages') ?>
                    </a>
                </li>
                <li>
                    <a href="<?= htmlspecialchars($base_url . '/analytics') ?>" class="<?= $admin_active_nav === 'analytics' ? 'active' : '' ?>">
                        <i class="las la-chart-line"></i> <?= p_lang('analytics', 'Analytics') ?>
                    </a>
                </li>

                <li class="nav-section-title"><span><?= p_lang('management', 'Management') ?></span></li>
                
                <?php if (isset($cfg_enable_ecommerce) && $cfg_enable_ecommerce): ?>
                <li class="nav-item has-dropdown <?= in_array($admin_active_nav, ['products', 'orders', 'accounting']) ? 'open' : '' ?>" data-id="shop">
                    <a class="dropdown-trigger">
                        <i class="las la-shopping-cart"></i> <?= p_lang('ecommerce_suite', 'Store Management') ?> <i class="las la-angle-down nav-chevron"></i>
                    </a>
                    <ul class="sub-nav">
                        <li>
                            <a href="<?= htmlspecialchars($base_url . '/products') ?>" class="<?= $admin_active_nav === 'products' ? 'active' : '' ?>">
                                <i class="las la-boxes"></i> <?= p_lang('products', 'Products') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($base_url . '/orders') ?>" class="<?= $admin_active_nav === 'orders' ? 'active' : '' ?>">
                                <i class="las la-file-invoice-dollar"></i> <?= p_lang('orders', 'Orders') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($base_url . '/accounting') ?>" class="<?= $admin_active_nav === 'accounting' ? 'active' : '' ?>">
                                <i class="las la-university"></i> <?= p_lang('accounting', 'Accounting') ?>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <li class="nav-item has-dropdown <?= in_array($admin_active_nav, ['pages', 'themes', 'filemanager']) ? 'open' : '' ?>" data-id="content">
                    <a class="dropdown-trigger">
                        <i class="las la-cubes"></i> <?= p_lang('content_mgmt', 'Content Engine') ?> <i class="las la-angle-down nav-chevron"></i>
                    </a>
                    <ul class="sub-nav">
                        <li>
                            <a href="<?= htmlspecialchars($base_url . '/pages') ?>" class="<?= $admin_active_nav === 'pages' ? 'active' : '' ?>">
                                <i class="las la-file-alt"></i> <?= p_lang('pages', 'Pages') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($base_url . '/themes') ?>" class="<?= $admin_active_nav === 'themes' ? 'active' : '' ?>">
                                <i class="las la-palette"></i> <?= p_lang('themes', 'Themes') ?>
                            </a>
                        </li>
                        <?php if (isset($cfg_enable_filemanager) && $cfg_enable_filemanager): ?>
                        <li>
                            <a href="<?= htmlspecialchars($base_url . '/filemanager') ?>" class="<?= $admin_active_nav === 'filemanager' ? 'active' : '' ?>">
                                <i class="las la-folder-open"></i> <?= p_lang('file_manager', 'File Manager') ?>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>

                <li class="nav-section-title"><span><?= p_lang('infrastructure', 'Infrastructure') ?></span></li>

                <li class="nav-item has-dropdown <?= in_array($admin_active_nav, ['settings', 'locales', 'security', 'health', 'update']) ? 'open' : '' ?>" data-id="system">
                    <a class="dropdown-trigger">
                        <i class="las la-tools"></i> <?= p_lang('system_tools', 'System & Core') ?> <i class="las la-angle-down nav-chevron"></i>
                    </a>
                    <ul class="sub-nav">
                        <li>
                            <a href="<?= htmlspecialchars($base_url . '/settings') ?>" class="<?= $admin_active_nav === 'settings' ? 'active' : '' ?>">
                                <i class="las la-sliders-h"></i> <?= p_lang('settings', 'Settings') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($base_url . '/locales') ?>" class="<?= $admin_active_nav === 'locales' ? 'active' : '' ?>">
                                <i class="las la-language"></i> <?= p_lang('locales', 'Locales') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($base_url . '/security') ?>" class="<?= $admin_active_nav === 'security' ? 'active' : '' ?>">
                                <i class="las la-shield-alt"></i> <?= p_lang('security', 'Security') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($base_url . '/health') ?>" class="<?= $admin_active_nav === 'health' ? 'active' : '' ?>">
                                <i class="las la-heartbeat"></i> <?= p_lang('health', 'System Health') ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?= htmlspecialchars($base_url . '/update') ?>" class="<?= $admin_active_nav === 'update' ? 'active' : '' ?>">
                                <i class="las la-cloud-download-alt"></i> <?= p_lang('updates', 'Updates') ?>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            
            <div class="sidebar-user">
                <div class="avatar">
                    <?= strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)) ?>
                </div>
                <span><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
                <a href="<?= htmlspecialchars($base_url . '/logout') ?>" class="logout" title="<?= p_lang('logout', 'Logout') ?>">
                    <i class="las la-power-off"></i>
                </a>
            </div>
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <main class="admin-main">
            <header class="top-header" style="background: var(--glass); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid var(--glass-border);">
                <div class="header-left">
                    <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle Sidebar">
                        <i class="las la-bars"></i>
                    </button>
                    <div class="page-title-group">
                        <h1 class="page-title" style="font-weight: 700; letter-spacing: -0.02em;"><?= htmlspecialchars(p_lang(strtolower(str_replace(' ', '_', $admin_page_title)), $admin_page_title)) ?></h1>
                        <div class="page-subtitle" style="display: block; opacity: 0.6; font-size: 12px;"><?= htmlspecialchars($admin_page_subtitle) ?></div>
                    </div>
                </div>
                <div class="header-actions" style="display: flex; align-items: center; gap: 20px;">
                    <div class="lang-switcher" style="display: flex; gap: 8px; font-size: 12px; font-weight: 700; background: var(--bg-hover); padding: 6px 12px; border-radius: 8px;">
                        <a href="?p_lang=tr" style="color: <?= PurifyKernel::get_active_lang() === 'tr' ? 'var(--primary-color)' : 'var(--text-muted)' ?>; text-decoration: none;">TR</a>
                        <span style="opacity: 0.2;">|</span>
                        <a href="?p_lang=en" style="color: <?= PurifyKernel::get_active_lang() === 'en' ? 'var(--primary-color)' : 'var(--text-muted)' ?>; text-decoration: none;">EN</a>
                    </div>
                    <a href="<?= htmlspecialchars($base_url . '/settings?action=flush_cache') ?>" style="font-size: 12px; font-weight:700; color: var(--success-color); text-decoration: none; display: flex; align-items: center; gap: 6px;" title="Clear Static HTML Cache">
                        <i class="las la-bolt"></i> <span><?= p_lang('clear_cache', 'Clear Cache') ?></span>
                    </a>
                    <button class="theme-toggle" id="themeToggle" aria-label="Toggle Dark Mode" style="background: var(--bg-hover); border-radius: 10px;">
                        <i class="las la-moon"></i>
                    </button>
                </div>
            </header>
            
            <div class="content-canvas">
