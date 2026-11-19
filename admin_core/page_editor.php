<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

require_once __DIR__ . '/../inc/config.php';

$page_key = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['page'] ?? '');

if (empty($page_key)) {
    header('Location: ' . rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path . '/pages');
    exit;
}

$admin_page_title = 'PurifyVE';
$admin_page_subtitle = p_lang('page_editor_subtitle', 'Purify Visual Editor');
$admin_active_nav = 'pages';

$base_site_url = rtrim($cfg_mainurl, '/');
$preview_url = $base_site_url . ($page_key === 'index' ? '/' : '/' . $page_key);

require __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="<?= $base_site_url ?>/adminassets/vendor/codemirror/lib/codemirror.css">
<link rel="stylesheet" href="<?= $base_site_url ?>/adminassets/vendor/codemirror/theme/dracula.css">
<link rel="stylesheet" href="<?= $base_site_url ?>/adminassets/purifyve.css?v=<?= @filemtime(dirname(__DIR__) . '/adminassets/purifyve.css') ?: time() ?>">

<div id="purifyve_toast" class="purifyve_toast" role="status" aria-live="polite"></div>

<div id="purifyve_loading" class="panel" style="text-align: center; padding: 64px 24px;">
    <i class="las la-sync la-spin" style="font-size: 30px; color: var(--primary-color);"></i>
    <p style="margin: 12px 0 0; color: var(--text-muted); font-size: 13.5px;">Loading PurifyVE...</p>
</div>

<div id="purifyve_shell" class="purifyve_shell" style="display: none;">
    <div class="purifyve_topbar">
        <div class="purifyve_topbar_group">
            <a href="<?= htmlspecialchars($base_url . '/pages') ?>" class="btn btn-sm btn-secondary">
                <i class="las la-arrow-left"></i> <?= p_lang('back_to_list', 'Pages') ?>
            </a>
            <span class="purifyve_file_pill">
                <i class="las la-file-code"></i> <?= htmlspecialchars($page_key) ?>.php
            </span>
            <span id="purifyve_unsaved" class="badge badge-warning" style="display: none; font-size: 11px;">
                <?= p_lang('unsaved_changes', 'Unsaved') ?>
            </span>
        </div>

        <div class="purifyve_topbar_group">
            <div id="purifyve_vp_switch" class="purifyve_segmented">
                <button type="button" class="purifyve_seg_btn active" data-purifyve-vp="desktop" title="Desktop"><i class="las la-desktop"></i></button>
                <button type="button" class="purifyve_seg_btn" data-purifyve-vp="tablet" title="Tablet"><i class="las la-tablet"></i></button>
                <button type="button" class="purifyve_seg_btn" data-purifyve-vp="mobile" title="Mobile"><i class="las la-mobile"></i></button>
            </div>

            <div id="purifyve_mode_switch" class="purifyve_segmented">
                <button type="button" id="purifyve_mode_visual" class="purifyve_seg_btn active">
                    <i class="las la-cubes"></i> Visual
                </button>
                <button type="button" id="purifyve_mode_code" class="purifyve_seg_btn">
                    <i class="las la-code"></i> Code
                </button>
            </div>

            <button type="button" id="purifyve_undo" class="purifyve_icon_btn" title="Undo" disabled><i class="las la-undo"></i></button>
            <button type="button" id="purifyve_redo" class="purifyve_icon_btn" title="Redo" disabled><i class="las la-redo"></i></button>
        </div>

        <div class="purifyve_topbar_group">
            <a href="<?= htmlspecialchars($preview_url) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-secondary">
                <i class="las la-external-link-alt"></i> <?= p_lang('view_page', 'View Live') ?>
            </a>
            <button type="button" id="purifyve_save" class="btn btn-sm btn-primary">
                <i class="las la-save"></i> <?= p_lang('save_page', 'Save Page') ?>
            </button>
        </div>
    </div>

    <div id="purifyve_zonebar" class="purifyve_zonebar" style="display: none;"></div>

    <div class="purifyve_workspace">
        <aside id="purifyve_palette" class="purifyve_palette">
            <div style="position: relative; margin-bottom: 10px;">
                <i class="las la-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--purifyve_muted); font-size: 14px;"></i>
                <input type="text" id="purifyve_block_search" class="purifyve_input" placeholder="Search blocks..." style="padding-left: 32px; font-size: 12.5px;">
            </div>
            <div id="purifyve_palette_body"></div>
        </aside>

        <section id="purifyve_canvas_area" class="purifyve_canvas_area"></section>

        <aside class="purifyve_inspector">
            <div class="purifyve_insp_tabs">
                <button type="button" id="purifyve_tab_block" class="purifyve_insp_tab active">
                    <i class="las la-sliders-h"></i> Block
                </button>
                <button type="button" id="purifyve_tab_seo" class="purifyve_insp_tab">
                    <i class="las la-search"></i> Page &amp; SEO
                </button>
            </div>
            <div id="purifyve_insp_body" class="purifyve_insp_body"></div>
        </aside>
    </div>
</div>

<script src="<?= $base_site_url ?>/adminassets/vendor/codemirror/lib/codemirror.js"></script>
<script src="<?= $base_site_url ?>/adminassets/vendor/codemirror/mode/xml/xml.js"></script>
<script src="<?= $base_site_url ?>/adminassets/vendor/codemirror/mode/javascript/javascript.js"></script>
<script src="<?= $base_site_url ?>/adminassets/vendor/codemirror/mode/css/css.js"></script>
<script src="<?= $base_site_url ?>/adminassets/vendor/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script src="<?= $base_site_url ?>/adminassets/vendor/codemirror/mode/clike/clike.js"></script>
<script src="<?= $base_site_url ?>/adminassets/vendor/codemirror/mode/php/php.js"></script>

<script>
window.PURIFYVE_CONFIG = {
    apiUrl: 'editor_api',
    pageKey: <?= json_encode($page_key) ?>,
    csrfToken: <?= json_encode(generate_csrf_token()) ?>,
    previewUrl: <?= json_encode($preview_url) ?>,
    siteName: <?= json_encode($cfg_name ?? 'PurifyCMS') ?>,
    baseUrl: <?= json_encode($base_site_url) ?>
};
</script>
<script src="<?= $base_site_url ?>/adminassets/purifyve.js?v=<?= @filemtime(dirname(__DIR__) . '/adminassets/purifyve.js') ?: time() ?>"></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
