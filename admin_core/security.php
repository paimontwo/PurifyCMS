<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

require_once __DIR__ . '/../inc/security.php';
require_once __DIR__ . '/../inc/config.php';

$error = '';
$success = '';

function normalize_bcrypt_hash($hash) {
    $hash = trim((string)$hash);
    if ($hash === '') {
        return '';
    }

    // Valid bcrypt hashes start with $2y$, $2a$, or $2b$
    if (preg_match('/^\$2[aby]\$.+$/', $hash) === 1) {
        return $hash;
    }

    // Common broken formats after manual edits (missing leading '$')
    if (preg_match('/^2[aby]\$.+$/', $hash) === 1) {
        return '$' . $hash;
    }
    if (preg_match('/^[aby]\$.+$/', $hash) === 1) {
        return '$2' . $hash;
    }

    return $hash;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $expected_user = $cfg_admin_user ?? 'admin';
    $passhash = normalize_bcrypt_hash($cfg_admin_passhash ?? '');
    $config_file = __DIR__ . '/../inc/config.php';

    if (($_SESSION['admin_username'] ?? '') !== $expected_user) {
        $error = p_lang('unauthorized', 'Unauthorized.');
    } else {
        $verified = false;

        if ($passhash !== '' && strpos($passhash, '$2') === 0) {
            $verified = password_verify($current_password, $passhash);
        }

        // Recovery paths
        if (!$verified && $current_password === 'admin' && ($passhash === '' || strpos($passhash, '$2') !== 0)) {
            $verified = true;
        }

        if (!$verified) {
            $error = p_lang('incorrect_password', 'The current password you entered is incorrect.');
        }
    }

    if (!$error && strlen($new_password) < 6) {
        $error = p_lang('password_too_short', 'New password must be at least 6 characters long.');
    } elseif (!$error && $new_password !== $confirm_password) {
        $error = p_lang('passwords_not_match', 'New password and confirmation do not match.');
    } elseif (!$error) {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $config_content = file_get_contents($config_file);
        $pattern = '/\$cfg_admin_passhash\s*=\s*([\"\'])(.*?)(\1)\s*;/';
        if (preg_match($pattern, $config_content, $m)) {
            $old_line = $m[0];
            $new_line = "\$cfg_admin_passhash = '" . addslashes($new_hash) . "';";
            $config_content = str_replace($old_line, $new_line, $config_content);
        } else {
            $config_content .= "\n\$cfg_admin_passhash = '" . addslashes($new_hash) . "';";
        }
        if (@file_put_contents($config_file, $config_content, LOCK_EX) !== false) {
            $success = p_lang('password_success', 'Password updated successfully. Your account is now more secure.');
        } else {
            $error = p_lang('config_write_error', 'Failed to update config.php file permissions.');
        }
    }
}

$admin_page_title = p_lang('security_settings_title', 'Security Settings');
$admin_page_subtitle = p_lang('security_subtitle', 'Ensure your access credentials are up to date.');
$admin_active_nav = 'security';
require __DIR__ . '/includes/header.php';
?>

<div class="panel" style="max-width: 600px; margin: 0 auto; position: relative; overflow: hidden;">
    <!-- Aesthetic Lighting Effect -->
    <div style="position: absolute; top: -100px; right: -100px; width: 300px; height: 300px; background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%); opacity: 0.1; pointer-events: none;"></div>

    <?php if ($success): ?>
        <div class="notice notice-success">
            <i class="las la-shield-check"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice notice-error">
            <i class="las la-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <h2 style="margin: 0 0 24px 0; font-size: 18px; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
        <i class="las la-lock-open" style="color: var(--primary-color);"></i> <?= p_lang('change_password', 'Change Master Password') ?>
    </h2>

    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

        <div class="input-group">
            <label for="current_password"><?= p_lang('current_password', 'Current Password') ?></label>
            <input type="password" id="current_password" name="current_password" class="form-control" required placeholder="<?= p_lang('verify_identity_placeholder', 'Verify your current identity') ?>">
        </div>

        <div style="height: 1px; background: var(--border-color); margin: 32px 0; opacity: 0.5;"></div>

        <div class="input-group">
            <label for="new_password"><?= p_lang('new_password', 'New Password') ?></label>
            <input type="password" id="new_password" name="new_password" class="form-control" required placeholder="<?= p_lang('new_password_placeholder', 'Create a strong new password') ?>">
            <div class="help-text"><i class="las la-info-circle"></i> <?= p_lang('pass_min_chars', 'Minimum 6 characters suggested.') ?></div>
        </div>

        <div class="input-group">
            <label for="confirm_password"><?= p_lang('confirm_password', 'Confirm New Password') ?></label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="<?= p_lang('repeat_password_placeholder', 'Repeat the new password') ?>">
        </div>

        <div style="margin-top: 32px; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="padding-left: 32px; padding-right: 32px;">
                <i class="las la-key"></i> <?= p_lang('update_credentials', 'Update Credentials') ?>
            </button>
        </div>
    </form>
</div>

<div class="panel" style="max-width: 600px; margin: 24px auto; background-color: rgba(var(--primary-rgb), 0.03); border-color: rgba(var(--primary-rgb), 0.1);">
    <div style="display: flex; gap: 16px; align-items: center;">
        <div style="font-size: 24px; color: var(--primary-color); opacity: 0.8;"><i class="las la-user-shield"></i></div>
        <div style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
            <strong><?= p_lang('pro_tip', 'Pro Tip') ?>:</strong> <?= p_lang('security_tip', 'Use a combination of letters, numbers, and symbols to ensure your admin panel remains impenetrable. Avoid recycling passwords from other platforms.') ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
