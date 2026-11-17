<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

require_once __DIR__ . '/../inc/config.php';

$active_theme = $cfg_active_theme ?? 'purifydefault';
$pages_dir = __DIR__ . '/../themes/' . $active_theme . '/pages';
$base_url = rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path;

$error = '';
$success = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF validation failed.');
    }

    $action = $_POST['action'] ?? '';
    
    if ($action === 'clone') {
        $source = $_POST['page'] ?? '';
        $source = preg_replace('/[^a-zA-Z0-9_-]/', '', $source);
        $source_path = $pages_dir . '/' . $source . '.php';

        if (file_exists($source_path)) {
            $new_name = $source . '_copy';
            $target_path = $pages_dir . '/' . $new_name . '.php';
            
            // Handle name collisions
            $counter = 1;
            while (file_exists($target_path)) {
                $new_name = $source . '_copy_' . $counter;
                $target_path = $pages_dir . '/' . $new_name . '.php';
                $counter++;
            }

            if (copy($source_path, $target_path)) {
                if (class_exists('PurifyVolt')) PurifyVolt::flushHtml();
                $success = p_lang('clone_success', 'Page cloned successfully as: ') . $new_name . '.php';
            } else {
                $error = p_lang('clone_error', 'Failed to clone page. Check directory permissions.');
            }
        }
    }

    if ($action === 'create_page') {
        $source = $_POST['reference_page'] ?? 'index';
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['slug'] ?? '');
        $title = $_POST['title'] ?? '';
        $desc = $_POST['desc'] ?? '';
        
        $source = preg_replace('/[^a-zA-Z0-9_-]/', '', $source);
        $source_path = $pages_dir . '/' . $source . '.php';
        $target_path = $pages_dir . '/' . $slug . '.php';

        if (empty($slug) || empty($title)) {
            $error = p_lang('error_missing_fields', 'Please fill all required fields.');
        } elseif (file_exists($target_path)) {
            $error = p_lang('error_page_exists', 'A page with this slug already exists.');
        } elseif (file_exists($source_path)) {
            $content = file_get_contents($source_path);
            
            // Update Title
            $content = preg_replace('/\$title\s*=\s*[\'"](.*?)[\'"]\s*;/', "\$title = \"" . addslashes($title) . "\";", $content);
            // Update Description
            $content = preg_replace('/\$desc\s*=\s*[\'"](.*?)[\'"]\s*;/', "\$desc = \"" . addslashes($desc) . "\";", $content);
            
            if (file_put_contents($target_path, $content, LOCK_EX)) {
                if (class_exists('PurifyVolt')) PurifyVolt::flushHtml();
                $success = p_lang('create_success', 'New page created successfully: ') . $slug . '.php';
                // Refresh list
                header("Location: " . $base_url . "/pages?success=1");
                exit;
            } else {
                $error = p_lang('create_error', 'Failed to create page. Check permissions.');
            }
        }
    }
}

if (isset($_GET['success'])) {
    $success = p_lang('operation_success', 'Operation completed successfully.');
}

$admin_page_title = p_lang('manage_pages', 'Manage Pages');
$admin_active_nav = 'pages';

require __DIR__ . '/includes/header.php';

// Scan pages directory
$pages = [];
if (is_dir($pages_dir)) {
    $files = scandir($pages_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
            continue;
        }
        
        $filename = pathinfo($file, PATHINFO_FILENAME);
        $file_path = $pages_dir . '/' . $file;
        
        // Read file to get title
        $content = file_get_contents($file_path);
        $title = $filename;
        if (preg_match('/\$title\s*=\s*[\'"](.*?)[\'"]\s*;/', $content, $matches)) {
            $title = $matches[1];
        }
        
        // Check if has editable zones
        $has_zones = strpos($content, 'purify:zone') !== false;
        
        $pages[] = [
            'key' => $filename,
            'title' => $title,
            'mtime' => filemtime($file_path),
            'has_zones' => $has_zones
        ];
    }
}
?>

<?php if ($success): ?>
    <div class="notice notice-success"><i class="las la-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="notice notice-error"><i class="las la-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php
$editable_count = count(array_filter($pages, function($p) { return $p['has_zones']; }));
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="panel" style="padding: 16px; display: flex; align-items: center; gap: 16px;">
        <div style="width: 40px; height: 40px; background: rgba(99, 102, 241, 0.1); color: #6366f1; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
            <i class="las la-file"></i>
        </div>
        <div>
            <div style="font-size: 20px; font-weight: 700; line-height: 1;"><?= count($pages) ?></div>
            <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; margin-top: 4px;"><?= p_lang('total_pages', 'Total Pages') ?></div>
        </div>
    </div>
    <div class="panel" style="padding: 16px; display: flex; align-items: center; gap: 16px;">
        <div style="width: 40px; height: 40px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
            <i class="las la-magic"></i>
        </div>
        <div>
            <div style="font-size: 20px; font-weight: 700; line-height: 1;"><?= $editable_count ?></div>
            <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; margin-top: 4px;"><?= p_lang('editable_pages', 'Editable Pages') ?></div>
        </div>
    </div>
</div>

<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.05);">
        <div>
            <h2 style="margin:0; font-size: 18px; font-weight: 600;"><?= p_lang('content_architecture', 'Content Architecture') ?></h2>
            <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--text-muted);"><?= p_lang('content_architecture_desc', "Manage your theme's entry points and editable content zones.") ?></p>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="color: var(--text-muted); font-size: 12px;"><?= p_lang('active_theme', 'Active Theme') ?>:</div>
            <div class="badge bg-primary" style="font-size: 11px; padding: 4px 10px; margin-right: 8px;"><?= htmlspecialchars($active_theme) ?></div>
            <button onclick="openNewPageModal()" class="btn btn-primary" style="padding: 8px 16px; border-radius: 8px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class="las la-plus-circle"></i> <?= p_lang('new_page', 'New Page') ?>
            </button>
        </div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 35%;"><?= p_lang('page_identity', 'Page Identity') ?></th>
                    <th><?= p_lang('route', 'Route') ?></th>
                    <th><?= p_lang('last_sync', 'Last Sync') ?></th>
                    <th><?= p_lang('type', 'Type') ?></th>
                    <th style="text-align: right;"><?= p_lang('operations', 'Operations') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                <tr class="table-row-hover">
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 32px; height: 32px; background: var(--bg-body); border-radius: 6px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 14px;">
                                <i class="las <?= $p['key'] === 'index' ? 'la-home' : 'la-file-alt' ?>"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($p['title']) ?></div>
                                <div style="font-size: 11px; color: var(--text-muted);"><?= $p['key'] ?>.php</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 6px; font-family: monospace; font-size: 13px; color: #6366f1;">
                            <span style="opacity: 0.5;">/</span><?= $p['key'] === 'index' ? '' : $p['key'] ?>
                        </div>
                    </td>
                    <td style="color: var(--text-muted); font-size: 12px;">
                        <?= date('M d, H:i', $p['mtime']) ?>
                    </td>
                    <td>
                        <?php if ($p['has_zones']): ?>
                            <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 4px 10px; border-radius: 20px;">
                                <i class="las la-check-circle" style="font-size: 8px;"></i> <?= p_lang('active', 'Active') ?> / <?= p_lang('editable', 'Editable') ?>
                            </span>
                        <?php else: ?>
                            <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #f59e0b; background: rgba(245, 158, 11, 0.1); padding: 4px 10px; border-radius: 20px;">
                                <i class="las la-code" style="font-size: 8px;"></i> <?= p_lang('code_only', 'Code-Only') ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; justify-content: flex-end; gap: 6px; align-items: center;">
                            <a href="<?= rtrim($cfg_mainurl, '/') ?>/<?= $p['key'] === 'index' ? '' : $p['key'] ?>" target="_blank" class="btn btn-sm btn-icon" title="<?= p_lang('preview_page', 'Preview Page') ?>">
                                <i class="las la-external-link-alt"></i>
                            </a>
                            
                            <a href="<?= $base_url ?>/filemanager?edit=<?= $active_theme ?>/pages/<?= $p['key'] ?>.php" class="btn btn-sm btn-icon" title="<?= p_lang('edit_code', 'Edit as Code') ?>" style="color: #6366f1;">
                                <i class="las la-code"></i>
                            </a>

                            <form method="post" style="display: inline;" onsubmit="return confirm('<?= p_lang('confirm_clone', 'Are you sure you want to clone this page?') ?>');">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="action" value="clone">
                                <input type="hidden" name="page" value="<?= $p['key'] ?>">
                                <button type="submit" class="btn btn-sm btn-icon" title="<?= p_lang('clone', 'Clone Page') ?>" style="color: #10b981;">
                                    <i class="las la-copy"></i>
                                </button>
                            </form>

                            <a href="<?= $base_url ?>/page_editor?page=<?= $p['key'] ?>" class="btn btn-primary btn-sm" style="border-radius: 8px;">
                                <i class="las la-pen-nib"></i> <?= p_lang('edit', 'Edit') ?>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top: 24px; background: rgba(99, 102, 241, 0.05); border: 1px dashed rgba(99, 102, 241, 0.2); padding: 20px; border-radius: var(--radius); display: flex; align-items: center; gap: 16px;">
    <div style="font-size: 24px; color: #6366f1; opacity: 0.8;"><i class="las la-folder-plus"></i></div>
    <div style="font-size: 13px; color: var(--text-muted);">
        <strong><?= p_lang('developer_tip', 'Developer Tip') ?>:</strong> <?= p_lang('new_page_tip', "New pages are detected instantly. Just create a .php file in themes/pages/ and it will appear here.") ?>
    </div>
</div>

<style>
.table-row-hover:hover {
    background: rgba(255,255,255,0.02);
}
.btn-icon {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border-radius: 8px;
    background: var(--bg-body);
    border: 1px solid rgba(255,255,255,0.05);
    color: var(--text-muted);
}
.btn-icon:hover {
    color: var(--text-primary);
    border-color: rgba(255,255,255,0.1);
}
</style>

<!-- New Page Modal -->
<div id="newPageModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.8); backdrop-filter:blur(5px); z-index:9999; align-items:center; justify-content:center;">
    <div class="panel" style="width: 100%; max-width: 500px; padding: 32px; box-shadow: var(--shadow-float); position: relative;">
        <button onclick="closeNewPageModal()" style="position:absolute; top:20px; right:20px; background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:20px;"><i class="las la-xmark"></i></button>
        <h2 style="margin:0 0 8px 0; font-size:20px; font-weight:700;"><?= p_lang('create_new_page', 'Create New Page') ?></h2>
        <p style="margin:0 0 24px 0; font-size:14px; color:var(--text-muted);"><?= p_lang('create_page_desc', 'Launch a new content architecture based on a reference page.') ?></p>
        
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="create_page">
            
            <div class="input-group">
                <label for="p_title"><?= p_lang('page_title', 'Page Title') ?></label>
                <input type="text" id="p_title" name="title" class="form-control" placeholder="e.g. Services" required onkeyup="updateSlug(this.value)">
            </div>
            
            <div class="input-group">
                <label for="p_slug"><?= p_lang('slug_filename', 'Slug (Filename)') ?></label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="text" id="p_slug" name="slug" class="form-control" placeholder="services" required>
                    <span style="color:var(--text-muted); font-size:13px;">.php</span>
                </div>
                <div class="help-text"><?= p_lang('slug_help', 'Only letters, numbers, and dashes. No spaces.') ?></div>
            </div>

            <div class="input-group">
                <label for="p_desc"><?= p_lang('meta_description', 'Meta Description') ?></label>
                <input type="text" id="p_desc" name="desc" class="form-control" placeholder="Brief summary for search engines...">
            </div>

            <div class="input-group">
                <label for="p_ref"><?= p_lang('reference_page', 'Reference Page (Template)') ?></label>
                <select id="p_ref" name="reference_page" class="form-control" style="background: var(--bg-body); color: var(--text-primary);">
                    <?php foreach ($pages as $p): ?>
                        <option value="<?= $p['key'] ?>" <?= $p['key'] === 'index' ? 'selected' : '' ?>><?= htmlspecialchars($p['title']) ?> (<?= $p['key'] ?>.php)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin-top:32px; display:flex; gap:12px;">
                <button type="button" onclick="closeNewPageModal()" class="btn btn-secondary" style="flex:1;"><?= p_lang('cancel', 'Cancel') ?></button>
                <button type="submit" class="btn btn-primary" style="flex:1;"><?= p_lang('create_page_btn', 'Create Architecture') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openNewPageModal() {
    document.getElementById('newPageModal').style.display = 'flex';
}
function closeNewPageModal() {
    document.getElementById('newPageModal').style.display = 'none';
}
function updateSlug(val) {
    const slug = val.toLowerCase()
        .replace(/[^a-z0-9_-]/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
    document.getElementById('p_slug').value = slug;
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
