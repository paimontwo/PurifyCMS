<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/kernel.php';

function normalize_bcrypt_hash($hash) {
    $hash = trim((string)$hash);
    if ($hash === '') {
        return '';
    }
    if (preg_match('/^\$2[aby]\$.+$/', $hash) === 1) {
        return $hash;
    }
    if (preg_match('/^2[aby]\$.+$/', $hash) === 1) {
        return '$' . $hash;
    }
    if (preg_match('/^[aby]\$.+$/', $hash) === 1) {
        return '$2' . $hash;
    }
    return $hash;
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ' . rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = p_lang('csrf_fail', 'Security Token Validation Failed.');
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        if (!PurifySecurity::check_brute_force($ip)) {
            $error = p_lang('too_many_attempts', 'Too many failed login attempts. Please wait 15 minutes.');
        } else {
            $config_file = __DIR__ . '/../inc/config.php';
            $expected_user = $cfg_admin_user ?? 'admin';
            $passhash = normalize_bcrypt_hash($cfg_admin_passhash ?? '');

            $valid = false;
            if ($username === $expected_user) {
                if (!empty($passhash) && strpos($passhash, '$2') === 0 && password_verify($password, $passhash)) {
                    $valid = true;
                }
            }
        if ($valid) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['last_regeneration'] = time(); 
            $_SESSION['last_activity'] = time();
            header('Location: ' . rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path);
            exit;
        } else {
            if (isset($ip)) PurifySecurity::check_brute_force($ip, true); 
            sleep(1);
            $error = p_lang('invalid_login', 'Invalid username or password.');
        }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= p_lang('secure_access', 'Secure Access') ?> - PurifyCMS</title>
    <link href="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/admin.css?v=<?= @filemtime(dirname(__DIR__) . '/adminassets/admin.css') ?: time() ?>" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css" integrity="sha512-vebUliqxrVkBy3gucMhClmyQP9On/HAWQdKDXRaAlb/FKuTbxkjPKUyqVOxAcGwFDka79eTF+YXwfke1h3/wfg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script>
        (function() {
            var theme = localStorage.getItem('admin_theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .login-canvas {
            width: 100%;
            max-width: 400px;
            padding: 24px;
            box-sizing: border-box;
        }
        
        .login-panel {
            background-color: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 40px 32px;
            box-shadow: var(--shadow-md);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .brand-icon {
            font-size: 32px;
            color: var(--primary-color);
            margin-bottom: 12px;
        }
        
        .brand-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .brand-header p {
            margin: 8px 0 0 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }
        
        .login-input {
            padding-left: 40px;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
        }
        
        .login-input:focus + .input-icon {
            color: var(--primary-color);
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            font-size: 14px;
            margin-top: 8px;
        }
        
        .copyright {
            text-align: center;
            margin-top: 24px;
            color: var(--text-muted);
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="login-canvas">
        <div class="login-panel">
            <div class="brand-header">
                <div class="brand-icon"><i class="las la-layer-group"></i></div>
                <h1><?= p_lang('welcome_back', 'Welcome Back') ?></h1>
                <p><?= p_lang('sign_in_to_account', 'Sign in to your account') ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="notice notice-error">
                    <i class="las la-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?= generate_csrf_field() ?>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" class="form-control login-input" required autofocus placeholder="<?= p_lang('username', 'Username') ?>">
                    <i class="las la-user input-icon"></i>
                </div>
                
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-control login-input" required placeholder="<?= p_lang('password', 'Password') ?>">
                    <i class="las la-lock input-icon"></i>
                </div>
                
                <button type="submit" class="btn btn-primary login-btn">
                    <?= p_lang('sign_in', 'Sign in') ?>
                </button>
            </form>
        </div>
        
        <div class="copyright">
            &copy; <?= date('Y') ?> PurifyCMS.
        </div>
    </div>
</body>
</html>
