<?php
define('DIR_ACCESS', true);

if (!file_exists(__DIR__ . '/inc/config.php')) {
    if (file_exists(__DIR__ . '/install.php')) {
        require __DIR__ . '/install.php';
        exit;
    } else {
        die('Configuration file missing and install.php not found.');
    }
}
require_once __DIR__ . '/inc/config.php';

$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $is_https ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// --- Route Normalization & Cache Interceptor ---
$base_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
if ($base_dir === '\\' || $base_dir === '/') {
    $base_dir = '';
}
$request_uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$request_path = trim($request_uri ?? '', '/');
if (!empty($base_dir) && $base_dir !== '/' && $base_dir !== '\\') {
    $request_path = trim(substr($request_path, strlen(trim($base_dir, '/'))), '/');
}

$request_path_ic = $request_path;
$has_query_string = !empty($_SERVER['QUERY_STRING']);
$is_post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

$admin_p_name = $cfg_admin_path ?? 'admin';
$is_admin_check = ($request_path_ic === $admin_p_name || strpos($request_path_ic, $admin_p_name . '/') === 0);

$ecommerce_routes_list = ['shop', 'product', 'checkout', 'order-track'];
$is_ecommerce_route = in_array($request_path_ic, $ecommerce_routes_list, true) || strpos($request_path_ic, 'product/') === 0 || strpos($request_path_ic, 'api/ecommerce') === 0;

$never_cache_paths = ['checkout', 'cart', 'order-success', 'order-track', 'contact', 'robots.txt', 'sitemap.xml'];
$is_never_cache = in_array($request_path_ic, $never_cache_paths, true) || strpos($request_path_ic, 'api/') === 0;

if (
    !$is_post &&
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' &&
    !$has_query_string &&
    !$is_admin_check &&
    !$is_never_cache &&
    empty($cfg_maintenance_mode) &&
    !($is_ecommerce_route && empty($cfg_enable_ecommerce)) &&
    (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)
) {
    $cache_key_raw = empty($request_path_ic) ? 'index' : preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $request_path_ic);
    $cache_key_ic = str_replace('/', '__', $cache_key_raw);
    $html_cache_file_ic = __DIR__ . '/inc/data/cache/' . $cache_key_ic . '.html';
    if (file_exists($html_cache_file_ic) && is_readable($html_cache_file_ic)) {
        readfile($html_cache_file_ic);
        exit;
    }
}

require_once __DIR__ . '/inc/kernel.php';
if (class_exists('PurifyKernel')) {
    PurifyKernel::boot();
}

if (file_exists(__DIR__ . '/inc/captcha.php')) {
    require_once __DIR__ . '/inc/captcha.php';
}

require_once __DIR__ . '/inc/security.php';

$is_admin = ($request_path === ($cfg_admin_path ?? 'admin') || strpos($request_path, ($cfg_admin_path ?? 'admin') . '/') === 0);

if ($request_path === 'robots.txt') {
    header('Content-Type: text/plain');
    if (isset($cfg_robots) && $cfg_robots) {
        echo "User-agent: *\nAllow: /\n";
        echo "Sitemap: " . rtrim($cfg_mainurl ?? 'http://localhost', '/') . "/sitemap.xml\n";
    } else {
        echo "User-agent: *\nDisallow: /\n";
    }
    exit;
}

if (isset($cfg_enable_analytics) && $cfg_enable_analytics) {
    $admin_p = $cfg_admin_path ?? 'admin';
    if (strpos($request_path, $admin_p . '/') !== 0 && $request_path !== $admin_p && $request_path !== 'api/contact' && $request_path !== 'robots.txt' && $request_path !== 'sitemap.xml') {
        PurifyStats::log($request_path);
    }
}






if ($request_path === 'api/ecommerce/order-lookup') {
    require __DIR__ . '/api/ecommerce/order-lookup.php';
    exit;
}

if ($request_path === 'api/ecommerce/download') {
    if (empty($cfg_enable_ecommerce)) {
        header('HTTP/1.1 404 Not Found');
        echo "Not found.";
        exit;
    }
    $download_file = __DIR__ . '/api/ecommerce/download.php';
    if (file_exists($download_file)) {
        require $download_file;
    } else {
        header('HTTP/1.1 404 Not Found');
        echo "Download handler not found.";
    }
    exit;
}

if ($request_path === 'api/ecommerce/checkout' && !empty($cfg_enable_ecommerce)) {
    require __DIR__ . '/api/ecommerce/checkout.php';
    exit;
}

// Automated Callback Listeners
$action = $_GET['action'] ?? '';
if (strpos($action, '_callback') !== false || strpos($action, '_webhook') !== false) {
    require __DIR__ . '/api/ecommerce/callback.php';
    exit;
}

if ($request_path === 'api/captcha-refresh') {
    require __DIR__ . '/api/captcha-refresh.php';
    exit;
}

if ($request_path === 'api/contact') {
    require __DIR__ . '/api/contact.php';
    exit;
}

if ($request_path === 'sitemap.xml') {
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    $active_theme = $cfg_active_theme ?? 'purifydefault';
    $pages_dir = __DIR__ . '/themes/' . $active_theme . '/pages';
    $base_url = rtrim($cfg_mainurl ?? 'http://localhost', '/');
    
    if (is_dir($pages_dir)) {
        $files = scandir($pages_dir);
        if ($files) {
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                    continue;
                }
                
                $cache_key = pathinfo($file, PATHINFO_FILENAME);
                if ($cache_key === '404' || $cache_key === 'product') {
                    continue;
                }
                if (empty($cfg_enable_ecommerce) && in_array($cache_key, ['shop', 'checkout', 'order-track'], true)) {
                    continue;
                }
                $url_path = ($cache_key === 'index') ? '' : $cache_key;
                $full_url = rtrim($base_url . '/' . $url_path, '/');
                if ($full_url === $base_url . '/') $full_url = $base_url;
                
                $file_path = $pages_dir . '/' . $file;
                $lastmod = date('c', filemtime($file_path));
                $priority = ($cache_key === 'index') ? '1.0' : '0.8';
                
                echo "  <url>\n";
                echo "    <loc>" . htmlspecialchars($full_url) . "</loc>\n";
                echo "    <lastmod>" . $lastmod . "</lastmod>\n";
                echo "    <changefreq>weekly</changefreq>\n";
                echo "    <priority>" . $priority . "</priority>\n";
                echo "  </url>\n";
            }
        }
    }
    echo '</urlset>';
    exit;
}

// (is_admin already defined above)

// --- Maintenance Mode Interceptor ---
if (isset($cfg_maintenance_mode) && $cfg_maintenance_mode === true && !$is_admin && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
    $active_theme = $cfg_active_theme ?? 'purifydefault';
    $maintenance_file = __DIR__ . '/themes/' . $active_theme . '/src/maintenance.php';
    
    // Preparation for the maintenance page
    $maintenance_msg = $cfg_maintenance_msg ?? 'System is currently under maintenance.';
    
    if (file_exists($maintenance_file)) {
        require $maintenance_file;
    } else {
        // High-Quality Minimalist Fallback Maintenance Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - <?= htmlspecialchars($cfg_name ?? 'PurifyCMS') ?></title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .box { border: 1px solid #e2e8f0; padding: 40px; background: #fff; max-width: 500px; width: 90%; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        h1 { margin: 0 0 20px 0; font-size: 20px; color: #6366f1; border-bottom: 2px solid #e0e7ff; padding-bottom: 10px; }
        p { font-size: 15px; line-height: 1.6; color: #475569; }
        .footer { margin-top: 24px; font-size: 11px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 12px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>SYSTEM MAINTENANCE</h1>
        <p><?= htmlspecialchars($maintenance_msg) ?></p>
        <div class="footer">&copy; <?= date('Y') ?> <?= htmlspecialchars($cfg_name ?? 'PurifyCMS') ?></div>
    </div>
</body>
</html>
<?php
    }
    exit;
}

if ($is_admin) {
    if (function_exists('harden_session')) {
        harden_session();
    }

    // IP Whitelisting is now enforced centrally in PurifyKernel::boot()
    $admin_route = trim(str_replace($cfg_admin_path, '', $request_path), '/');

    if (empty($admin_route)) {
        $admin_route = 'index';
    }
    
    $admin_route = preg_replace('/[^a-zA-Z0-9_-]/', '', $admin_route);

    $allowed_admin_routes = [
        'index',
        'themes',
        'settings',
        'security',
        'health',
        'filemanager',
        'filemanager_api',
        'update',
        'pages',
        'messages',
        'invoice_view',
        'products',
        'orders',
        'archive_orders',
        'accounting',
        'page_editor',
        'locales',
        'editor_api',
        'analytics',
        'login',
        'logout'
    ];

    if (!in_array($admin_route, $allowed_admin_routes, true)) {
        $admin_route = 'index';
    }

    // Centralized Admin Authentication Gate (only login/logout are public)
    $public_admin_routes = ['login', 'logout'];
    $api_admin_routes = ['editor_api', 'filemanager_api'];
    if (!in_array($admin_route, $public_admin_routes, true) && (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true)) {
        $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if (in_array($admin_route, $api_admin_routes, true) || $is_ajax) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        http_response_code(401);
        require __DIR__ . '/admin_core/login.php';
        exit;
    }
    
    $admin_file = __DIR__ . '/admin_core/' . $admin_route . '.php';
    if (file_exists($admin_file)) {
        require $admin_file;
    } else {
        require __DIR__ . '/admin_core/index.php';
    }
    exit;
}

$cfg_active_theme = $cfg_active_theme ?? 'purifydefault';
$cache_key_raw = empty($request_path) ? 'index' : preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $request_path);
$cache_key = str_replace('/', '__', $cache_key_raw);

// --- PurifyVolt Cache Registry Management ---
$html_cache_dir = __DIR__ . '/inc/data/cache';
$html_cache_file = $html_cache_dir . '/' . $cache_key . '.html';
if (!is_dir($html_cache_dir)) @mkdir($html_cache_dir, 0755, true);

// --- Dynamic SEO Route Interceptor (Products) ---
$ecommerce_pages = ['shop', 'product', 'checkout', 'order-track'];
if (empty($cfg_enable_ecommerce) && (in_array($cache_key, $ecommerce_pages, true) || strpos($request_path, 'product/') === 0)) {
    header('HTTP/1.1 404 Not Found');
    $theme_pages_file = __DIR__ . '/themes/' . ($cfg_active_theme ?? 'purifydefault') . '/pages/404.php';
} elseif (!empty($cfg_enable_ecommerce) && strpos($request_path, 'product/') === 0) {
    require_once __DIR__ . '/inc/PurifyTrade.php';
    $slug = substr($request_path, 8); // Remove "product/"
    $products_file = __DIR__ . '/inc/data/ecommerce/products.json';
    $products = file_exists($products_file) ? json_decode(file_get_contents($products_file), true) : [];
    
    $found_product = null;
    foreach ($products as $p) {
        if (($p['slug'] ?? '') === $slug || ($p['id'] ?? '') === $slug) {
            $found_product = $p;
            break;
        }
    }
    
    if ($found_product) {
        $cache_key = 'product__' . preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);
        $p = $found_product; // Inject into global scope
        $theme_pages_file = __DIR__ . '/themes/' . ($cfg_active_theme ?? 'purifydefault') . '/pages/product.php';
    } else {
        header('HTTP/1.1 404 Not Found');
        $theme_pages_file = __DIR__ . '/themes/' . ($cfg_active_theme ?? 'purifydefault') . '/pages/404.php';
    }
}

if (!isset($theme_pages_file)) {
    $theme_pages_file = __DIR__ . '/themes/' . ($cfg_active_theme ?? 'purifydefault') . '/pages/' . $cache_key . '.php';
}

if (!file_exists($theme_pages_file)) {
    // 404 Scenario
    header('HTTP/1.1 404 Not Found');
    $theme_pages_file = __DIR__ . '/themes/' . ($cfg_active_theme ?? 'purifydefault') . '/pages/404.php';
    if (!file_exists($theme_pages_file)) {
        echo "<html><head><title>404 Page Not Found</title></head><body><center><h1>404 Page Not Found</h1></center><hr><center>PurifyCMS</center></body></html>";
        exit;
    }
}

// Load configurations and routing helpers
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/url.php';

// Buffer the page content
ob_start();
require $theme_pages_file;
$page_content = ob_get_clean();

// Wrapp in layout if exists
$theme_layout = __DIR__ . '/themes/' . $cfg_active_theme . '/src/layout.php';
if (file_exists($theme_layout)) {
    ob_start();
    require $theme_layout;
    $final_html = ob_get_clean();
}


// --- Security & Dynamic Exclusion Logic ---
$no_cache_routes = ['checkout', 'cart', 'order-success', 'order-track', 'contact', 'api', 'admin'];
$output_to_serve = isset($final_html) ? $final_html : $page_content;

$should_cache = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET')
    && (http_response_code() === 200)
    && empty($_SERVER['QUERY_STRING'])
    && (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true);

if ($should_cache) {
    foreach ($no_cache_routes as $nr) {
        if ($request_path === $nr || strpos($request_path, $nr . '/') === 0) {
            $should_cache = false;
            break;
        }
    }
}

// Never cache responses containing per-visitor tokens or captcha images
if ($should_cache && (
    strpos($output_to_serve, 'name="csrf_token"') !== false ||
    strpos($output_to_serve, 'nywhashCaptchaImg') !== false
)) {
    $should_cache = false;
}

// Final Output & Static Caching Registry
echo $output_to_serve;
if ($should_cache) {
    if (!is_dir($html_cache_dir)) @mkdir($html_cache_dir, 0755, true);
    @file_put_contents($html_cache_file, $output_to_serve, LOCK_EX);
}
exit;