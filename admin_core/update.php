<?php
defined('DIR_ACCESS') or exit;

require_once __DIR__ . '/../inc/security.php';
require_once __DIR__ . '/../inc/version.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

// Backup Trigger
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_backup'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die('Security Token Validation Failed.');
    
    $backup_file = sys_get_temp_dir() . '/purify_backup_' . date('Ymd_His') . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($backup_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $root_path = realpath(__DIR__ . '/../');
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root_path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $file_path = $file->getRealPath();
                $rel_path = substr($file_path, strlen($root_path) + 1);
                
                // Exclude caches, temporary files, and git history
                if (strpos($rel_path, 'inc/data/cache') === 0 || 
                    strpos($rel_path, 'tmp') === 0 || 
                    strpos($rel_path, '.git') === 0) {
                    continue;
                }
                
                $zip->addFile($file_path, $rel_path);
            }
        }
        $zip->close();

        if (file_exists($backup_file)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="PurifyCMS_Backup_' . date('Ymd_His') . '.zip"');
            header('Content-Length: ' . filesize($backup_file));
            readfile($backup_file);
            unlink($backup_file);
            exit;
        }
    }
}

$admin_page_title = p_lang('system_updates', 'System Updates');
$admin_page_subtitle = p_lang('updates_subtitle', 'Check for new PurifyCMS versions and install them.');
$admin_active_nav = 'update';
require __DIR__ . '/includes/header.php';
$base_url = rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path;

function check_for_updates() {
    $check_url = 'https://api.github.com/repos/paimontwo/PurifyCMS/releases/latest';
    $_ctx = stream_context_create([
        'http' => [
            'timeout' => 6,
            'header' => "User-Agent: PurifyCMS-Updater\r\nAccept: application/vnd.github.v3+json\r\n"
        ]
    ]);
    $_res = @file_get_contents($check_url, false, $_ctx);
    if ($_res) {
        $json = json_decode($_res, true);
        if ($json && isset($json['tag_name'])) {
            $latest_version = ltrim($json['tag_name'], 'v');
            $has_update = version_compare($latest_version, PURIFYCMS_VERSION, '>');
            
            $zip_url = $json['zipball_url'] ?? '';
            if (!empty($json['assets']) && is_array($json['assets'])) {
                foreach ($json['assets'] as $asset) {
                    if (isset($asset['browser_download_url']) && preg_match('/\.zip$/i', $asset['name'])) {
                        $zip_url = $asset['browser_download_url'];
                        break;
                    }
                }
            }
            
            return [
                'status' => 'success',
                'has_update' => $has_update,
                'latest_version' => $latest_version,
                'download_url' => $zip_url,
                'changelog' => $json['body'] ?? 'No release notes provided.'
            ];
        }
    }
    return false;
}

$update_data = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_updates'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die('Security Token Validation Failed.');
    $update_data = check_for_updates();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_update'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die('Security Token Validation Failed.');
    $update_data = check_for_updates();
    
    if (!$update_data || !$update_data['has_update'] || empty($update_data['download_url'])) {
        $error_msg = p_lang('no_updates_queue', "No valid updates available to install.");
    } else {
        $download_url = $update_data['download_url'];
        $tmp_zip = sys_get_temp_dir() . '/purify_update_' . time() . '.zip';
        
        $_ctx = stream_context_create([
            'http' => [
                'timeout' => 30,
                'header' => "User-Agent: PurifyCMS-Updater\r\n"
            ]
        ]);
        
        $zip_data = @file_get_contents($download_url, false, $_ctx);
        if ($zip_data) {
            file_put_contents($tmp_zip, $zip_data, LOCK_EX);
            file_put_contents(__DIR__ . '/../.maintenance', 'Updating...', LOCK_EX);
            
            $zip = new ZipArchive;
            if ($zip->open($tmp_zip) === TRUE) {
                // Determine if archive is wrapped in a top-level directory (standard GitHub release zipball)
                $first_entry = $zip->getNameIndex(0);
                $is_github_tree = false;
                if ($first_entry && substr($first_entry, -1) === '/' && $zip->numFiles > 1) {
                    $prefix = explode('/', $first_entry)[0] . '/';
                    $sample_entry = $zip->getNameIndex(1);
                    if ($sample_entry && strpos($sample_entry, $prefix) === 0) {
                        $is_github_tree = true;
                    }
                }
                
                if ($is_github_tree) {
                    $extract_dir = sys_get_temp_dir() . '/purify_extract_' . time();
                    @mkdir($extract_dir, 0755, true);
                    $zip->extractTo($extract_dir);
                    $zip->close();
                    
                    $subfolders = glob($extract_dir . '/*', GLOB_ONLYDIR);
                    $source_folder = !empty($subfolders) ? $subfolders[0] : $extract_dir;
                    
                    $target_root = realpath(__DIR__ . '/../');
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($source_folder, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );
                    
                    foreach ($iterator as $item) {
                        $rel_path = substr($item->getPathname(), strlen($source_folder) + 1);
                        
                        if ($rel_path === 'inc/config.php' || strpos($rel_path, 'inc/data/') === 0) {
                            continue;
                        }
                        
                        $dest = $target_root . '/' . $rel_path;
                        if ($item->isDir()) {
                            if (!is_dir($dest)) @mkdir($dest, 0755, true);
                        } else {
                            @copy($item->getPathname(), $dest);
                        }
                    }
                    
                    $clean_it = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($extract_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($clean_it as $f) {
                        $f->isDir() ? @rmdir($f->getRealPath()) : @unlink($f->getRealPath());
                    }
                    @rmdir($extract_dir);
                } else {
                    $zip->extractTo(__DIR__ . '/../');
                    $zip->close();
                }
                
                @unlink(__DIR__ . '/../.maintenance');
                @unlink($tmp_zip);
                
                if (class_exists('PurifyVolt')) {
                    PurifyVolt::flushHtml();
                }
                
                $success_msg = p_lang('update_success', "Update to Version ") . htmlspecialchars($update_data['latest_version']) . p_lang('installed_refresh', " installed successfully! Please refresh.");
            } else {
                @unlink(__DIR__ . '/../.maintenance');
                $error_msg = p_lang('zip_open_error', "Failed to open the update ZIP archive.");
            }
        } else {
            $error_msg = p_lang('download_error', "Failed to download the update package from GitHub releases.");
        }
    }
}
?>

<div class="panel" style="max-width: 760px; margin: 0 auto; position: relative; overflow: hidden;">
    <div class="panel-header">
        <h3 class="panel-title">
            <i class="las la-sync"></i> <?= p_lang('system_updates', 'System Updates') ?>
        </h3>
        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 12.5px; color: var(--text-muted); font-weight: 500;"><?= p_lang('current_version', 'Current System Version') ?>:</span>
            <span class="badge badge-primary">v<?= htmlspecialchars(PURIFYCMS_VERSION) ?></span>
        </div>
    </div>

    <?php if (isset($success_msg)): ?>
        <div class="notice notice-success" style="justify-content: space-between; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="las la-check-circle"></i>
                <span><?= $success_msg ?></span>
            </div>
            <a href="<?= htmlspecialchars($base_url . '/update') ?>" class="btn btn-sm btn-primary"><?= p_lang('continue', 'Continue') ?></a>
        </div>
    <?php elseif (isset($error_msg)): ?>
        <div class="notice notice-error">
            <i class="las la-exclamation-triangle"></i>
            <span><?= $error_msg ?></span>
        </div>
    <?php endif; ?>

    <?php if (!isset($success_msg)): ?>
        <?php if (!isset($_POST['check_updates']) && !isset($_POST['install_update'])): ?>
            <div style="padding: 20px 0 8px; text-align: center;">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: var(--primary-glow); color: var(--primary-color); display: inline-flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 14px;">
                    <i class="las la-cloud-download-alt"></i>
                </div>
                <p style="color: var(--text-muted); font-size: 14px; max-width: 480px; margin: 0 auto 24px; line-height: 1.6;">
                    <?= p_lang('check_updates_desc', 'Check the official GitHub repository for new releases and patches.') ?>
                </p>
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    <form method="POST" style="margin: 0;">
                        <?= generate_csrf_field() ?>
                        <button type="submit" name="check_updates" class="btn btn-primary">
                            <i class="las la-sync"></i> <?= p_lang('check_updates_btn', 'Check for Updates') ?>
                        </button>
                    </form>
                    <form method="POST" style="margin: 0;" data-no-preloader="1">
                        <?= generate_csrf_field() ?>
                        <button type="submit" name="download_backup" class="btn btn-secondary" title="Zip all project files excluding caches">
                            <i class="las la-file-archive"></i> <?= p_lang('download_backup', 'Download Full Backup') ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php elseif ($update_data && !empty($update_data['has_update'])): ?>
            <div style="border: 1px solid var(--border-color); border-radius: var(--radius); padding: 20px; background: var(--bg-body);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="badge badge-success"><i class="las la-arrow-up"></i> <?= p_lang('updates_available', 'Update Available!') ?></span>
                        <strong style="font-size: 15px; color: var(--text-main);">v<?= htmlspecialchars($update_data['latest_version']) ?></strong>
                    </div>
                </div>
                <div style="background: var(--bg-surface); border: 1px solid var(--border-color); padding: 14px 16px; border-radius: 8px; font-size: 13px; color: var(--text-muted); white-space: pre-line; line-height: 1.6; margin-bottom: 18px;"><?= htmlspecialchars($update_data['changelog']) ?></div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <form method="POST" id="updateForm" style="margin: 0;">
                        <?= generate_csrf_field() ?>
                        <button type="submit" name="install_update" class="btn btn-primary" onclick="return startUpdate(this)">
                            <i class="las la-download"></i> <?= p_lang('install_version', 'Install Version') ?> <?= htmlspecialchars($update_data['latest_version']) ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php elseif ($update_data): ?>
            <div class="notice notice-success" style="margin-bottom: 0; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="las la-check-circle" style="font-size: 20px;"></i>
                    <span><?= p_lang('up_to_date', 'Your system is up to date!') ?> (v<?= htmlspecialchars(PURIFYCMS_VERSION) ?>)</span>
                </div>
                <form method="POST" style="margin: 0;">
                    <?= generate_csrf_field() ?>
                    <button type="submit" name="check_updates" class="btn btn-sm btn-secondary">
                        <i class="las la-sync"></i> <?= p_lang('check_again', 'Check Again') ?>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="notice notice-warning" style="margin-bottom: 0; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="las la-exclamation-circle" style="font-size: 20px;"></i>
                    <span><?= p_lang('unable_reach_server', 'Unable to reach GitHub to check for updates.') ?></span>
                </div>
                <form method="POST" style="margin: 0;">
                    <?= generate_csrf_field() ?>
                    <button type="submit" name="check_updates" class="btn btn-sm btn-secondary">
                        <i class="las la-redo"></i> <?= p_lang('retry', 'Retry') ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Update Overlay -->
    <div id="updateOverlay" style="display: none; position: absolute; inset: 0; background: var(--bg-surface); border-radius: var(--radius); z-index: 1000; align-items: center; justify-content: center; flex-direction: column; padding: 32px; text-align: center;">
        <div style="font-size: 36px; color: var(--primary-color); margin-bottom: 14px;">
            <i class="las la-sync la-spin"></i>
        </div>
        <h3 id="updateStatus" style="margin: 0 0 6px; font-size: 16px;"><?= p_lang('downloading_update', 'Downloading Update...') ?></h3>
        <p style="color: var(--text-muted); font-size: 13px; margin: 0 0 20px;"><?= p_lang('installing_update_desc', 'Extracting archive and applying system updates...') ?></p>
        <div style="width: 100%; max-width: 360px; height: 6px; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 999px; overflow: hidden;">
            <div id="updateProgress" style="width: 0%; height: 100%; background: var(--primary-color); transition: width 0.3s ease;"></div>
        </div>
    </div>
</div>

<script>
function startUpdate(btn) {
    if (!confirm('Ensure you have downloaded a backup before proceeding. Install this update?')) return false;
    
    // Hide version card contents and cleanly show full overlay
    var formCard = btn.closest('.panel') || document.querySelector('.panel');
    var overlay = document.getElementById('updateOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
        overlay.style.minHeight = '280px';
    }
    
    var progress = document.getElementById('updateProgress');
    var status = document.getElementById('updateStatus');
    
    var val = 0;
    var interval = setInterval(function() {
        val += Math.random() * 5;
        if (val > 95) {
            val = 95;
            clearInterval(interval);
        }
        if (progress) progress.style.width = val + '%';
        
        if (status) {
            if (val > 30) status.innerText = '<?= p_lang('extracting_files', 'Extracting Files...') ?>';
            if (val > 70) status.innerText = '<?= p_lang('updating_core', 'Applying updates...') ?>';
            if (val > 88) status.innerText = '<?= p_lang('finishing_up', 'Finishing Up...') ?>';
        }
    }, 400);
    return true;
}
</script>
<?php
require __DIR__ . '/includes/footer.php';
