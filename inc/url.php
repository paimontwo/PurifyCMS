<?php
defined('DIR_ACCESS') or exit;

include_once 'config.php';
$allowed_host = parse_url($cfg_mainurl, PHP_URL_HOST);
$request_host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? $allowed_host;
if ($allowed_host && $request_host !== $allowed_host) {
    $request_host = $allowed_host;
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$fix_path = preg_replace(['#/\.+/#', '#//+#'], '/', $path);
if (preg_match('/\.(php|html)$/i', $fix_path)) {
    $new_path = preg_replace('/\.(php|html)$/i', '', $fix_path);
    if (empty($new_path)) $new_path = '/';
    $redirect_to = $new_path;
    if (!headers_sent()) {
        header("Location: " . $redirect_to, true, 301);
        exit;
    } else {
        echo '<script>location.replace(' . json_encode($redirect_to) . ');</script>';
        exit;
    }
}
