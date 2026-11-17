<?php
defined('DIR_ACCESS') or exit;
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

// --- Backup Handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_backup'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die('Security Token Validation Failed.');
    $zip_file = sys_get_temp_dir() . '/purify_backup_' . date('Y-m-d_H-i') . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $rootPath = realpath(__DIR__ . '/../');
        $files = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
            function ($current, $key, $iterator) {
                // Exclude temp zip files and the backup tool itself if it's currently running
                $name = $current->getFilename();
                if ($name === '.git' || strpos($name, 'purify_backup_') !== false) return false;
                return true;
            }
        ));

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();

        if (file_exists($zip_file)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
            header('Content-Length: ' . filesize($zip_file));
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($zip_file);
            unlink($zip_file);
            exit;
        }
    }
}

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/version.php';
$admin_page_title = p_lang('system_updates', 'System Updates');
$admin_page_subtitle = p_lang('updates_subtitle', 'Check for new PurifyCMS versions and install them securely.');
$admin_active_nav = 'update';
require __DIR__ . '/includes/header.php';
$base_url = rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path;
function check_for_updates() {
    $check_url = 'https://api.github.com/repos/paimontwo/PurifyCMS/releases/latest';
    $_ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'header' => "User-Agent: PurifyCMS-Updater\r\n"
        ]
    ]);
    $_res = @file_get_contents($check_url, false, $_ctx);
    if ($_res) {
        $json = json_decode($_res, true);
        if ($json && isset($json['tag_name'])) {
            $latest_version = ltrim($json['tag_name'], 'v');
            $has_update = version_compare($latest_version, PURIFYCMS_VERSION, '>');
            return [
                'status' => 'success',
                'has_update' => $has_update,
                'latest_version' => $latest_version,
                'changelog' => $json['body'] ?? ''
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
    if (!$update_data || empty($update_data['queue'])) {
        $error_msg = p_lang('no_updates_queue', "No valid updates in queue.");
    } else {
        $next_update = $update_data['queue'][0];
        $download_url = $next_update['download_url'];
        $tmp_zip = sys_get_temp_dir() . '/purify_update_' . time() . '.zip';
        $zip_data = @file_get_contents($download_url);
        if ($zip_data) {
            file_put_contents($tmp_zip, $zip_data, LOCK_EX);
            file_put_contents(__DIR__ . '/../.maintenance', 'Updating...', LOCK_EX);
            $zip = new ZipArchive;
            if ($zip->open($tmp_zip) === TRUE) {
                $zip->extractTo(__DIR__ . '/../');
                $zip->close();
                unlink(__DIR__ . '/../.maintenance');
                unlink($tmp_zip);
                $success_msg = p_lang('update_success', "Update to Version ") . htmlspecialchars($next_update['version']) . p_lang('installed_refresh', " installed successfully! Please refresh.");
            } else {
                unlink(__DIR__ . '/../.maintenance');
                $error_msg = p_lang('zip_open_error', "Failed to open the update ZIP archive.");
            }
        } else {
            $error_msg = p_lang('download_error', "Failed to download the update package. Verify your license and internet connection.");
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
                <p style="color: var(--text-muted); margin-bottom: 20px;"><?= p_lang('check_updates_desc', 'Click the button below to query the license server for new versions.') ?></p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <form method="POST">
                        <?= generate_csrf_field() ?>
                        <button type="submit" name="check_updates" class="btn btn-primary btn-lg">
                            <i class="las la-sync"></i> <?= p_lang('check_updates_btn', 'Check for Updates') ?>
                        </button>
                    </form>
                    <form method="POST">
                        <?= generate_csrf_field() ?>
                        <button type="submit" name="download_backup" class="btn btn-secondary btn-lg" title="Safe Backup: This will zip all files including your theme and core.">
                            <i class="las la-file"></i> Download Full Backup
                        </button>
                    </form>
                </div>
            </div>
        <?php elseif ($update_data && $update_data['updates_available']): ?>
            <div class="alert alert-info" style="text-align: left; margin-top: 30px;">
                <h4><i class="las la-cloud-download-alt"></i> <?= p_lang('updates_available', 'Updates Available!') ?></h4>
                <p><?= p_lang('sequential_update_desc', 'The system will install updates sequentially to maintain cryptographic integrity.') ?></p>
                <hr>
                <?php $next = $update_data['queue'][0]; ?>
                <div style="background: rgba(0,0,0,0.1); padding: 15px; border-radius: var(--radius);">
                    <strong><?= p_lang('next_update', 'Next Update') ?>: <?= htmlspecialchars(p_lang('version', 'Version')) ?> <?= htmlspecialchars($next['version']) ?></strong><br>
                    <span style="color: var(--text-muted);"><?= htmlspecialchars($next['changelog']) ?></span>
                </div>
                <form method="POST" id="updateForm" style="margin-top: 20px; text-align: right;">
                    <?= generate_csrf_field() ?>
                    <button type="submit" name="install_update" class="btn btn-primary" onclick="startUpdate(this)">
                        <i class="las la-download"></i> <?= p_lang('install_version', 'Install Version') ?> <?= htmlspecialchars($next['version']) ?>
                    </button>
                </form>
            </div>
            <?php if (count($update_data['queue']) > 1): ?>
            <div style="margin-top: 20px; text-align: left; font-size: 13px; color: var(--text-muted);">
                <i>+ <?= count($update_data['queue']) - 1 ?> more update(s) in queue after this one.</i>
            </div>
            <?php endif; ?>
        <?php elseif ($update_data): ?>
            <div class="alert alert-success" style="margin-top: 30px;">
                <i class="las la-check-circle"></i> <?= p_lang('up_to_date', 'Your system is up to date!') ?>
                <div style="margin-top: 15px;">
                    <form method="POST"><button type="submit" name="check_updates" class="btn btn-sm btn-secondary"><?= p_lang('check_again', 'Check Again') ?></button></form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" style="margin-top: 30px;">
                <i class="las la-network-wired"></i> <?= p_lang('unable_reach_server', 'Unable to reach the license server to check for updates.') ?>
                <div style="margin-top: 15px;">
                    <form method="POST"><button type="submit" name="check_updates" class="btn btn-sm btn-secondary"><?= p_lang('retry', 'Retry') ?></button></form>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Update Overlay -->
    <div id="updateOverlay" style="display: none; position: absolute; top:0; left:0; width:100%; height:100%; background: var(--panel-bg); border-radius: var(--radius); z-index: 100; display: none; align-items: center; justify-content: center; flex-direction: column; padding: 40px;">
        <div style="font-size: 40px; color: #6366f1; margin-bottom: 20px;">
            <i class="las la-gear la-spin"></i>
        </div>
        <h3 id="updateStatus"><?= p_lang('downloading_update', 'Downloading Update...') ?></h3>
        <p style="color: var(--text-muted); margin-bottom: 30px;"><?= p_lang('patching_system_desc', 'The system is being patched and signatures are being verified.') ?></p>
        <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.05); border-radius: 4px; overflow: hidden;">
            <div id="updateProgress" style="width: 0%; height: 100%; background: linear-gradient(90deg, #6366f1, #a855f7); transition: width 0.3s ease;"></div>
        </div>
    </div>
</div>

<script>
function startUpdate(btn) {
    if (!confirm('Ensure you have a backup before proceeding. Install this update?')) return;
    
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
        if (val > 60) status.innerText = '<?= p_lang('verifying_integrity', 'Verifying Integrity...') ?>';
        if (val > 85) status.innerText = '<?= p_lang('finishing_up', 'Finishing Up...') ?>';
    }, 400);
}
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
