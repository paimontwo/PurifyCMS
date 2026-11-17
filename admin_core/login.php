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

$active_lang = PurifyKernel::get_active_lang();
$base_site_url = rtrim($cfg_mainurl ?? '', '/');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($active_lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= p_lang('sign_in', 'Sign in') ?> &mdash; <?= htmlspecialchars($cfg_name ?? 'PurifyCMS') ?></title>
    <?php if (!empty($cfg_favicon)): ?>
    <link rel="icon" href="<?= htmlspecialchars($cfg_favicon) ?>">
    <?php else: ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_site_url ?>/adminassets/images/favicon.png">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css" integrity="sha512-vebUliqxrVkBy3gucMhClmyQP9On/HAWQdKDXRaAlb/FKuTbxkjPKUyqVOxAcGwFDka79eTF+YXwfke1h3/wfg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script>
        (function() {
            var theme = localStorage.getItem('admin_theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <style>
        :root {
            --bg: #f8fafc;
            --surface: rgba(255, 255, 255, 0.88);
            --surface-solid: #ffffff;
            --input-bg: #f8fafc;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-glow: rgba(79, 70, 229, 0.12);
            --accent: #6366f1;
            --danger-bg: rgba(239, 68, 68, 0.08);
            --danger-border: rgba(239, 68, 68, 0.25);
            --danger-text: #dc2626;
            --shadow: 0 20px 45px -15px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.03);
        }

        [data-theme="dark"] {
            --bg: #0b0d13;
            --surface: rgba(18, 22, 33, 0.82);
            --surface-solid: #121621;
            --input-bg: rgba(15, 23, 42, 0.6);
            --border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-glow: rgba(99, 102, 241, 0.22);
            --accent: #818cf8;
            --danger-bg: rgba(239, 68, 68, 0.12);
            --danger-border: rgba(239, 68, 68, 0.3);
            --danger-text: #f87171;
            --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.55);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .ambient-blobs {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
            filter: blur(90px);
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            width: 42vw;
            height: 42vw;
            max-width: 520px;
            max-height: 520px;
            opacity: 0.12;
        }

        [data-theme="dark"] .blob {
            opacity: 0.16;
        }

        .blob-1 {
            top: -12%;
            left: -8%;
            background: var(--primary);
        }

        .blob-2 {
            bottom: -12%;
            right: -8%;
            background: #8b5cf6;
        }

        .login-topbar {
            position: fixed;
            top: 20px;
            right: 24px;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .top-pill {
            background: var(--surface);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 10px;
            height: 36px;
            padding: 0 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .top-pill a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .top-pill a.active,
        .top-pill a:hover {
            color: var(--primary);
        }

        .theme-btn {
            width: 36px;
            padding: 0;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            color: var(--text-muted);
            transition: all 0.15s ease;
        }

        .theme-btn:hover {
            color: var(--text-main);
            border-color: var(--primary);
        }

        .login-wrapper {
            width: 100%;
            max-width: 430px;
            position: relative;
            z-index: 2;
            animation: cardAppear 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes cardAppear {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            background: var(--surface);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px 36px;
            box-shadow: var(--shadow);
        }

        .login-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 28px;
        }

        .login-brand-logo {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }

        .login-brand-logo img {
            width: 28px;
            height: 28px;
            object-fit: contain;
        }

        .login-brand h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .login-brand p {
            font-size: 13.5px;
            color: var(--text-muted);
        }

        .login-alert {
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger-text);
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .login-alert i {
            font-size: 18px;
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 7px;
        }

        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-box .lead-icon {
            position: absolute;
            left: 14px;
            color: var(--text-muted);
            font-size: 18px;
            pointer-events: none;
            transition: color 0.15s ease;
        }

        .login-control {
            width: 100%;
            height: 46px;
            padding: 0 42px 0 42px;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: all 0.15s ease;
        }

        .login-control::placeholder {
            color: var(--text-muted);
            opacity: 0.7;
        }

        .login-control:focus {
            border-color: var(--primary);
            background: var(--surface-solid);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        .login-control:focus ~ .lead-icon {
            color: var(--primary);
        }

        .pass-toggle {
            position: absolute;
            right: 10px;
            width: 30px;
            height: 30px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            transition: color 0.15s ease;
        }

        .pass-toggle:hover {
            color: var(--text-main);
        }

        .submit-btn {
            width: 100%;
            height: 46px;
            margin-top: 8px;
            border: none;
            border-radius: 12px;
            background: var(--primary);
            color: #ffffff;
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.18s ease;
        }

        .submit-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px -4px var(--primary-glow);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            transform: none;
            cursor: wait;
        }

        .login-footer {
            margin-top: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12.5px;
            color: var(--text-muted);
            padding: 0 4px;
        }

        .login-footer a {
            color: var(--text-muted);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
            transition: color 0.15s ease;
        }

        .login-footer a:hover {
            color: var(--primary);
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="ambient-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <div class="login-topbar">
        <div class="top-pill">
            <a href="?p_lang=tr" class="<?= $active_lang === 'tr' ? 'active' : '' ?>">TR</a>
            <span style="opacity: 0.3;">|</span>
            <a href="?p_lang=en" class="<?= $active_lang === 'en' ? 'active' : '' ?>">EN</a>
        </div>
        <button type="button" class="top-pill theme-btn" id="themeToggle" aria-label="Toggle Theme">
            <i class="las la-moon"></i>
        </button>
    </div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">
                <div class="login-brand-logo">
                    <img src="<?= $base_site_url ?>/adminassets/images/logo.png" alt="PurifyCMS" draggable="false">
                </div>
                <h1><?= p_lang('welcome_back', 'Welcome back, admin') ?></h1>
                <p><?= p_lang('sign_in_to_account', 'Sign in to your administration panel') ?></p>
            </div>

            <?php if ($error): ?>
                <div class="login-alert">
                    <i class="las la-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="" id="loginForm">
                <?= generate_csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="username"><?= p_lang('username', 'Username') ?></label>
                    <div class="input-box">
                        <input type="text" id="username" name="username" class="login-control" required autofocus autocomplete="username" placeholder="<?= p_lang('username', 'Username') ?>">
                        <i class="las la-user lead-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password"><?= p_lang('password', 'Password') ?></label>
                    <div class="input-box">
                        <input type="password" id="password" name="password" class="login-control" required autocomplete="current-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                        <i class="las la-lock lead-icon"></i>
                        <button type="button" class="pass-toggle" id="passToggle" aria-label="Toggle Password Visibility" tabindex="-1">
                            <i class="las la-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <span><?= p_lang('sign_in', 'Sign in') ?></span>
                    <i class="las la-arrow-right"></i>
                </button>
            </form>
        </div>

        <div class="login-footer">
            <a href="<?= htmlspecialchars($base_site_url ?: '/') ?>">
                <i class="las la-arrow-left"></i> <?= p_lang('view_site', 'Back to Website') ?>
            </a>
            <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($cfg_name ?? 'PurifyCMS') ?></span>
        </div>
    </div>

    <script>
        (function() {
            const htmlEl = document.documentElement;
            const themeBtn = document.getElementById('themeToggle');
            const themeIcon = themeBtn.querySelector('i');

            function syncThemeIcon() {
                const isDark = htmlEl.getAttribute('data-theme') === 'dark';
                themeIcon.className = isDark ? 'las la-sun' : 'las la-moon';
            }
            syncThemeIcon();

            themeBtn.addEventListener('click', function() {
                const next = htmlEl.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                htmlEl.setAttribute('data-theme', next);
                localStorage.setItem('admin_theme', next);
                syncThemeIcon();
            });

            const passInput = document.getElementById('password');
            const passToggle = document.getElementById('passToggle');
            const passIcon = passToggle.querySelector('i');

            passToggle.addEventListener('click', function() {
                const isPwd = passInput.type === 'password';
                passInput.type = isPwd ? 'text' : 'password';
                passIcon.className = isPwd ? 'las la-eye-slash' : 'las la-eye';
                passInput.focus();
            });

            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="las la-circle-notch la-spin"></i> <span><?= addslashes(p_lang('sign_in', 'Sign in')) ?>...</span>';
            });
        })();
    </script>
</body>
</html>
