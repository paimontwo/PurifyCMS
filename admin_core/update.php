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
$admin_page_subtitle = p_lang('updates_subtitle', 'Check for new PurifyCMS versions and install them securely.');
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
            
            // Find release zip asset or default zipball
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
                // If it's a GitHub zipball, files are contained within a root folder (e.g. paimontwo-PurifyCMS-xxxx)
                $first_entry = $zip->getNameIndex(0);
                $is_github_tree = ($first_entry && substr_count($first_entry, '/') >= 1 && is_dir('zip://' . $tmp_zip . '#' . $first_entry));
                
                if ($is_github_tree) {
                    $extract_dir = sys_get_temp_dir() . '/purify_extract_' . time();
                    @mkdir($extract_dir, 0755, true);
                    $zip->extractTo($extract_dir);
                    $zip->close();
                    
                    $subfolders = glob($extract_dir . '/*', GLOB_ONLYDIR);
                    $source_folder = !empty($subfolders) ? $subfolders[0] : $extract_dir;
                    
                    // Copy recursively to root excluding config.php and user data
                    $target_root = realpath(__DIR__ . '/../');
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($source_folder, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );
                    
                    foreach ($iterator as $item) {
                        $rel_path = substr($item->getPathname(), strlen($source_folder) + 1);
                        
                        // Preserve config, user uploaded files and storage
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
                    
                    // Cleanup temp directory
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
                
                // Flush cache after update
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
<div class="panel" style="max-width: 800px; margin: 0 auto; text-align: center; position: relative;">
    <h2><?= p_lang('current_version', 'Current System Version') ?>: <span class="badge bg-primary"><?= htmlspecialchars(PURIFYCMS_VERSION) ?></span></h2>
    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success" style="text-align: left; margin-top: 20px;">
            <i class="las la-check-circle"></i> <?= $success_msg ?>
            <br><br>
            <a href="update.php" class="btn btn-sm btn-primary"><?= p_lang('continue', 'Continue') ?></a>
        </div>
    <?php elseif (isset($error_msg)): ?>
        <div class="alert alert-danger" style="text-align: left; margin-top: 20px;">
            <i class="las la-exclamation-triangle"></i> <?= $error_msg ?>
        </div>
    <?php endif; ?>

    <?php if (!isset($success_msg)): ?>
        <?php if (!isset($_POST['check_updates']) && !isset($_POST['install_update'])): ?>
            <div style="margin-top: 30px; text-align: center;">
                <p style="color: var(--text-muted); margin-bottom: 20px;"><?= p_lang('check_updates_desc', 'Check the official GitHub repository for new releases and patches.') ?></p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <form method="POST">
                        <?= generate_csrf_field() ?>
                        <button type="submit" name="check_updates" class="btn btn-primary btn-lg">
                            <i class="las la-sync"></i> <?= p_lang('check_updates_btn', 'Check for Updates') ?>
                        </button>
                    </form>
                    <form method="POST">
                        <?= generate_csrf_field() ?>
                        <button type="submit" name="download_backup" class="btn btn-secondary btn-lg" title="Safe Backup: This will zip all files excluding caches.">
                            <i class="las la-file-archive"></i> Download Full Backup
                        </button>
                    </form>
                </div>
            </div>
        <?php elseif ($update_data && !empty($update_data['has_update'])): ?>
            <div class="alert alert-info" style="text-align: left; margin-top: 30px;">
                <h4><i class="las la-cloud-download-alt"></i> <?= p_lang('updates_available', 'Update Available!') ?></h4>
                <hr>
                <div style="background: rgba(0,0,0,0.1); padding: 15px; border-radius: var(--radius);">
                    <strong><?= p_lang('latest_version', 'Latest Release') ?>: Version <?= htmlspecialchars($update_data['latest_version']) ?></strong><br>
                    <span style="color: var(--text-muted); white-space: pre-line; display: block; margin-top: 8px;"><?= htmlspecialchars($update_data['changelog']) ?></span>
                </div>
                <form method="POST" id="updateForm" style="margin-top: 20px; text-align: right;">
                    <?= generate_csrf_field() ?>
                    <button type="submit" name="install_update" class="btn btn-primary" onclick="startUpdate(this)">
                        <i class="las la-download"></i> <?= p_lang('install_version', 'Install Version') ?> <?= htmlspecialchars($update_data['latest_version']) ?>
                    </button>
                </form>
            </div>
        <?php elseif ($update_data): ?>
            <div class="alert alert-success" style="margin-top: 30px;">
                <i class="las la-check-circle"></i> <?= p_lang('up_to_date', 'Your system is up to date!') ?>
                <div style="margin-top: 15px;">
                    <form method="POST"><button type="submit" name="check_updates" class="btn btn-sm btn-secondary"><?= p_lang('check_again', 'Check Again') ?></button></form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" style="margin-top: 30px;">
                <i class="las la-network-wired"></i> <?= p_lang('unable_reach_server', 'Unable to reach GitHub to check for updates.') ?>
                <div style="margin-top: 15px;">
                    <form method="POST"><button type="submit" name="check_updates" class="btn btn-sm btn-secondary"><?= p_lang('retry', 'Retry') ?></button></form>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Update Overlay -->
    <div id="updateOverlay" style="display: none; position: absolute; top:0; left:0; width:100%; height:100%; background: var(--panel-bg); border-radius: var(--radius); z-index: 100; align-items: center; justify-content: center; flex-direction: column; padding: 40px;">
        <div style="font-size: 40px; color: #6366f1; margin-bottom: 20px;">
            <i class="las la-cog la-spin"></i>
        </div>
        <h3 id="updateStatus"><?= p_lang('downloading_update', 'Downloading Update...') ?></h3>
        <p style="color: var(--text-muted); margin-bottom: 30px;"><?= p_lang('installing_update_desc', 'Extracting archive and applying system updates...') ?></p>
        <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.05); border-radius: 4px; overflow: hidden;">
            <div id="updateProgress" style="width: 0%; height: 100%; background: linear-gradient(90deg, #6366f1, #a855f7); transition: width 0.3s ease;"></div>
        </div>
    </div>
</div>

<script>
function startUpdate(btn) {
    if (!confirm('Ensure you have downloaded a backup before proceeding. Install this update?')) return;
    
    document.getElementById('updateOverlay').style.display = 'flex';
    var progress = document.getElementById('updateProgress');
    var status = document.getElementById('updateStatus');
    
    var val = 0;
    var interval = setInterval(function() {
        val += Math.random() * 5;
        if (val > 95) {
            val = 95;
            clearInterval(interval);
        }
        progress.style.width = val + '%';
        
        if (val > 30) status.innerText = '<?= p_lang('extracting_files', 'Extracting Files...') ?>';
        if (val > 70) status.innerText = '<?= p_lang('updating_core', 'Applying updates...') ?>';
        if (val > 88) status.innerText = '<?= p_lang('finishing_up', 'Finishing Up...') ?>';
    }, 400);
}
</script>
<?php
require __DIR__ . '/includes/footer.php';
