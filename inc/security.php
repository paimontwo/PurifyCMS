<?php
defined('DIR_ACCESS') or exit;
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    if (ini_get('session.use_cookies') == '1' && session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Lax');
    }
    session_start();
}
function harden_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // 1. Session Fingerprinting: Bind to User-Agent to prevent session stealing
        $fingerprint = md5($_SERVER['HTTP_USER_AGENT'] ?? 'none');
        if (!isset($_SESSION['prfy_fingerprint'])) {
            $_SESSION['prfy_fingerprint'] = $fingerprint;
        } elseif ($_SESSION['prfy_fingerprint'] !== $fingerprint) {
            session_destroy();
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }

        // 2. Inactivity Timeout: 30-minute rule (1800 seconds)
        $now = time();
        if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity'] > 1800)) {
            session_destroy();
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }
        $_SESSION['last_activity'] = $now;

        // 3. Periodic Regeneration: Every hour
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = $now;
        } elseif ($now - $_SESSION['last_regeneration'] > 3600) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = $now;
        }
    }
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Token Validation Failed.');
    }
    return true;
}
function verify_csrf_get($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$token)) {
        http_response_code(403);
        die('CSRF Token Validation Failed.');
    }
    return true;
}
function generate_csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '') . '">';
}
function generate_csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}
function sanitize_html($content) {
    return htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
class PurifySecurity {
    public static function init() {
        if (!class_exists('PurifyKernel')) {
             http_response_code(403);
             die("S_V: C_P_R");
        }
    }


    public static function check_brute_force($ip, $record_failure = false) {
        $cache_dir = __DIR__ . '/data/cache';
        if (!is_dir($cache_dir)) @mkdir($cache_dir, 0755, true);
        $lock_file = $cache_dir . '/.bf_' . md5($ip);
        
        $data = file_exists($lock_file) ? json_decode(file_get_contents($lock_file), true) : ['count' => 0, 'last' => 0];
        $now = time();


        if ($data['count'] >= 5 && ($now - $data['last'] < 900)) {
            return false; // Locked
        }

        if ($record_failure) {

            if ($now - $data['last'] > 900) {
                $data['count'] = 1;
            } else {
                $data['count']++;
            }
            $data['last'] = $now;
            @file_put_contents($lock_file, json_encode($data), LOCK_EX);
        } elseif ($data['count'] > 0 && ($now - $data['last'] > 300)) {

             @unlink($lock_file);
        }

        return true;
    }
}
PurifySecurity::init();

global $cfg_readonly_mode, $cfg_admin_path;
if (!empty($cfg_readonly_mode) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $raw_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $route_path = trim((string)$raw_path, '/');
    $base_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    if ($base_dir !== '' && $base_dir !== '/' && $base_dir !== '\\') {
        $trimmed_base = trim($base_dir, '/');
        if ($route_path === $trimmed_base) {
            $route_path = '';
        } elseif (strpos($route_path, $trimmed_base . '/') === 0) {
            $route_path = substr($route_path, strlen($trimmed_base) + 1);
        }
    }
    $admin_segment = trim($cfg_admin_path ?? 'admin', '/');
    
    // Check if the request is actually targeting the admin core first
    $is_admin_request = ($route_path === $admin_segment || strpos($route_path, $admin_segment . '/') === 0);
    if (!$is_admin_request) return;

    // Whitelist: Allow login process even in hardened mode 
    if ($route_path === $admin_segment . '/login' || empty($_SESSION['admin_logged_in'])) return;


    // Optimized Detection: Only parse JSON if Content-Type indicates it
    $action = $_POST['action'] ?? '';
    if (!$action && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $json_input = json_decode(file_get_contents('php://input'), true);
        $action = $json_input['action'] ?? '';
    }

    $is_safe_read = false;
    if (in_array(strtolower($action), ['list', 'read', 'fetch', 'get'])) {
        $is_safe_read = true;
    }

    if ($is_safe_read) return;

    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => p_lang('readonly_blocked', 'Read-Only Mode is active. All modifications are disabled.')]);
    } else {
        $lang = PurifyKernel::get_active_lang();
        $is_dark = true; // Most users use dark mode for admin
        $bg_color = '#000000';
        $text_color = '#f8fafc';
        $accent_color = '#f59e0b';
        
        echo '<!DOCTYPE html>
        <html lang="'.$lang.'">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>'.p_lang('readonly_page_title', 'Read-Only Mode Active').'</title>
            <style>
                body { font-family: "Inter", -apple-system, sans-serif; background: '.$bg_color.'; color: '.$text_color.'; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
                .card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); padding: 48px; border-radius: 24px; text-align: center; max-width: 480px; width: 90%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); backdrop-filter: blur(10px); }
                .icon { font-size: 64px; margin-bottom: 24px; display: inline-block; background: rgba(245, 158, 11, 0.1); width: 120px; height: 120px; line-height: 120px; border-radius: 50%; color: '.$accent_color.'; }
                h1 { font-size: 28px; font-weight: 700; margin: 0 0 16px 0; color: '.$text_color.'; }
                p { color: #94a3b8; line-height: 1.6; margin: 0 0 32px 0; font-size: 16px; }
                .btn { display: inline-block; background: #6366f1; color: white; padding: 14px 32px; border-radius: 12px; text-decoration: none; font-weight: 600; transition: all 0.2s; }
                .btn:hover { background: #4f46e5; transform: translateY(-2px); }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="icon">🔒</div>
                <h1>'.p_lang('readonly_page_title', 'Read-Only Mode Active').'</h1>
                <p>'.p_lang('readonly_page_desc', 'The system is currently protected. All modifications are disabled by the system administrator.').'</p>
                <a href="javascript:history.back()" class="btn">'.p_lang('go_back', '← Go back').'</a>
            </div>
        </body>
        </html>';
    }
    exit;
}
