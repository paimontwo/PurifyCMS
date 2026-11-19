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
    header('Content-Type: application/json; charset=utf-8');
    if (empty($cfg_enable_ecommerce)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'E-Commerce module is disabled.']);
        exit;
    }
    $oid = trim((string)($_GET['oid'] ?? ''));
    if (empty($oid)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID is required.']);
        exit;
    }
    
    require_once __DIR__ . '/inc/PurifyTrade.php';
    if (!PurifyTrade::isValidOrderId($oid)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid Order ID format.']);
        exit;
    }
    $order = PurifyTrade::getOrder($oid);
    if ($order) {
        // Strip sensitive data before returning
        unset($order['customer']['email'], $order['customer']['phone'], $order['customer']['address']);
        echo json_encode(['success' => true, 'order' => $order]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found. Please check your Order ID.']);
    }
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

if ($request_path === 'api/ecommerce/checkout' && isset($cfg_enable_ecommerce) && $cfg_enable_ecommerce) {
    header('Content-Type: application/json; charset=utf-8');
    
    // IP-based Rate Limiting (sliding window: max 10 requests per 60 seconds per IP)
    $rl_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rl_dir = __DIR__ . '/inc/data/cache';
    if (!is_dir($rl_dir)) @mkdir($rl_dir, 0755, true);
    $rl_file = $rl_dir . '/.co_rl_' . md5($rl_ip);
    $rl_now = time();
    $rl_hits = [];
    if (file_exists($rl_file)) {
        $rl_decoded = json_decode(@file_get_contents($rl_file), true);
        if (is_array($rl_decoded)) {
            $rl_hits = array_values(array_filter($rl_decoded, function($ts) use ($rl_now) {
                return is_int($ts) && ($rl_now - $ts) < 60;
            }));
        }
    }
    if (count($rl_hits) >= 10) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'msg' => 'Spam Protection: Too many checkout attempts. Please wait a moment before trying again.']);
        exit;
    }
    $rl_hits[] = $rl_now;
    @file_put_contents($rl_file, json_encode($rl_hits), LOCK_EX);

    require_once __DIR__ . '/inc/PurifyTrade.php';
    
    $input_raw = file_get_contents('php://input');
    $input_decoded = json_decode($input_raw, true);
    $data_source = !empty($input_decoded) ? $input_decoded : $_POST;
    
    // Payment Retrigger Handler
    $retrigger_oid = $data_source['retrigger_oid'] ?? null;
    if ($retrigger_oid) {
        if (!PurifyTrade::isValidOrderId($retrigger_oid)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'msg' => 'Invalid Order ID format.']);
            exit;
        }
        $order = PurifyTrade::getOrder($retrigger_oid);
        if (!$order || ($order['status'] !== 'pending' && $order['status'] !== 'unpaid')) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'msg' => 'Registry state (' . ($order['status'] ?? 'null') . ') does not permit re-triggering.']);
            exit;
        }
        $order_id = $retrigger_oid;
        $total_gross = $order['total_gross'];
        $customer = $order['customer'];
        $processed_items = $order['items'];
        
        // Webhook Gateway Override
        $method = $data_source['payment_method'] ?? $order['payment_method'] ?? 'iban';
    } else {
        $items = $data_source['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'success' => false, 'msg' => 'Your cart is empty.', 'message' => 'Your cart is empty.']);
            exit;
        }
        
        $c_raw = $data_source['customer'] ?? $_POST;
        $customer = [
            'name' => trim($c_raw['name'] ?? ''),
            'email' => filter_var($c_raw['email'] ?? '', FILTER_SANITIZE_EMAIL),
            'phone' => trim($c_raw['phone'] ?? ''),
            'address' => trim($c_raw['address'] ?? ''),
            'city' => trim($c_raw['city'] ?? ''),
            'district' => trim($c_raw['district'] ?? ''),
            'zip' => trim($c_raw['zip'] ?? ''),
            'tax_id' => trim($c_raw['tax_id'] ?? ''),
            'tax_office' => trim($c_raw['tax_office'] ?? ''),
            'company' => trim($c_raw['company'] ?? ''),
        ];
        
        $method = $data_source['payment_method'] ?? $c_raw['payment_method'] ?? 'iban';
        
        // Universal Fiscal Harmony
        if ($method === 'iban' && empty($cfg_bank_details)) {
            if (!empty($cfg_paytr_m_id)) $method = 'paytr';
            
        }

        // Security Layer: Load Product Registry for Price & Tax Validation
        $products_file = __DIR__ . '/inc/data/ecommerce/products.json';
        $master_products = file_exists($products_file) ? (json_decode(file_get_contents($products_file), true) ?: []) : [];
        $registry_map = [];
        foreach ($master_products as $mp) {
            if (isset($mp['slug'])) $registry_map[$mp['slug']] = $mp;
            if (isset($mp['id'])) $registry_map[$mp['id']] = $mp;
        }

        // Precision Fiscal Calculation Engine
        $total_net = 0; $total_tax = 0;
        $processed_items = [];
        foreach ($items as $it) {
            $it_data = is_string($it) ? json_decode($it, true) : $it;
            if (!is_array($it_data)) {
                http_response_code(400);
                PurifyTrade::log('SECURITY_ALERT', 'Malformed item payload in checkout');
                echo json_encode(['status' => 'error', 'msg' => 'Security Error: Invalid cart item.']);
                exit;
            }
            $slug = (string)($it_data['slug'] ?? $it_data['id'] ?? '');
            
            if ($slug === '' || !isset($registry_map[$slug])) {
                http_response_code(400);
                PurifyTrade::log('SECURITY_ALERT', 'Unregistered product in checkout', ['slug' => $slug]);
                echo json_encode(['status' => 'error', 'msg' => 'Security Error: Product mismatch.']);
                exit;
            }

            $master = $registry_map[$slug];
            $actual_price = (float)($master['price'] ?? 0);
            if ($actual_price < 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'msg' => 'Security Error: Invalid product price.']);
                exit;
            }

            if (array_key_exists('price', $it_data)) {
                $sent_price = (float)$it_data['price'];
                if (abs($sent_price - $actual_price) > 0.01) {
                    http_response_code(400);
                    PurifyTrade::log('SECURITY_ALERT', 'Price tampering detected', ['slug' => $slug, 'sent' => $sent_price, 'actual' => $actual_price]);
                    echo json_encode(['status' => 'error', 'msg' => 'Security Error: Pricing protocol violation.']);
                    exit;
                }
            }

            $actual_tax_rate = max(0.0, (float)($master['tax_rate'] ?? 20));
            if (array_key_exists('tax_rate', $it_data)) {
                $sent_tax_rate = (float)$it_data['tax_rate'];
                if (abs($sent_tax_rate - $actual_tax_rate) > 0.01) {
                    http_response_code(400);
                    PurifyTrade::log('SECURITY_ALERT', 'Tax rate tampering detected', ['slug' => $slug, 'sent' => $sent_tax_rate, 'actual' => $actual_tax_rate]);
                    echo json_encode(['status' => 'error', 'msg' => 'Security Error: Tax rate protocol violation.']);
                    exit;
                }
            }

            $quantity = isset($it_data['quantity']) ? (int)$it_data['quantity'] : 1;
            if ($quantity < 1 || $quantity > 100) {
                http_response_code(400);
                PurifyTrade::log('SECURITY_ALERT', 'Invalid quantity in checkout', ['slug' => $slug, 'quantity' => $it_data['quantity'] ?? null]);
                echo json_encode(['status' => 'error', 'msg' => 'Security Error: Invalid item quantity.']);
                exit;
            }

            $calc = PurifyTrade::calculate($actual_price * $quantity, $actual_tax_rate);
            $total_net += $calc['net'];
            $total_tax += $calc['tax'];
            
            $processed_items[] = [
                'id' => $master['id'] ?? $slug,
                'slug' => $master['slug'] ?? $slug,
                'title' => $master['title'] ?? ($it_data['title'] ?? $slug),
                'price' => $actual_price,
                'quantity' => $quantity,
                'tax_rate' => $actual_tax_rate,
                'total_net' => $calc['net'],
                'total_tax' => $calc['tax'],
                'total_gross' => $calc['gross']
            ];
        }
        $total_gross = round($total_net + $total_tax, 2);
        if ($total_gross < 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'msg' => 'Security Error: Invalid order total.']);
            exit;
        }

        // Create Order Manifest
        $order_result = PurifyTrade::createOrder([
            'customer' => $customer,
            'items' => $processed_items,
            'total_net' => round($total_net, 2),
            'total_tax' => round($total_tax, 2),
            'total_gross' => round($total_gross, 2),
            'currency' => $cfg_ecommerce_currency ?? 'TRY',
            'payment_method' => $method,
            'status' => 'pending'
        ]);
        
        if ($order_result['status'] === 'error') {
            echo json_encode(['status' => 'error', 'msg' => 'Order creation failed: ' . $order_result['msg']]);
            exit;
        }
        
        $order_id = $order_result['order_id'];
    }

    // Handle Payment Orchestration
    if (empty($order_id) || !is_string($order_id)) {
        echo json_encode(['status' => 'error', 'msg' => 'Order Registry ID Validation Failed.']);
        exit;
    }

    


    // High-Priority Gateway Handler (PayTR etc.)
    if ($method !== 'bank' && $method !== 'iban') {
        $gateway = PurifyTrade::getGateway($method);
        if ($gateway) {
            // Ensure total_gross is passed accurately
            $res = $gateway->getToken($order_id, (float)$total_gross, $customer, $processed_items);
            if ($res['status'] === 'success') {
                // Ensure the dispatcher status ('gateway') is not overwritten by the driver status ('success')
                $gateway_response = array_merge($res, [
                    'status' => 'gateway', 
                    'gateway' => $method, 
                    'order_id' => $order_id
                ]);
                echo json_encode($gateway_response);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'Gateway Failure: ' . $res['msg']]);
            }
            exit;
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Requested Gateway Protocol (' . $method . ') is not initialized.']);
            exit;
        }
    }

    if ($method === 'bank' || $method === 'iban') {
        echo json_encode([
            'status' => 'success',
            'order_id' => $order_id,
            'msg' => 'Order created. Awaiting bank transfer verification.',
            'iban' => $cfg_bank_details ?? 'N/A'
        ]);
        exit;
    }

    echo json_encode(['status' => 'error', 'msg' => 'Unsupported Gateway Protocol.']);
    exit;
}

// Automated Callback Listeners
$action = $_GET['action'] ?? '';

if (strpos($action, '_callback') !== false || strpos($action, '_webhook') !== false) {
    require_once __DIR__ . '/inc/PurifyTrade.php';
    PurifyTrade::log('INFO', 'Callback attempt received', [
        'action' => $action,
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'post_keys' => array_keys($_POST)
    ]);

    $method = str_replace(['_callback', '_webhook'], '', $action);
    $gateway = PurifyTrade::getGateway($method);
    
    if ($gateway && $gateway->verifyCallback($_POST)) {
        $oid_raw = $_POST['merchant_oid'] ?? $_POST['basket_id'] ?? '';
        
        // Strip Gateway Entropy Suffix (e.g. PRFYYYYYMMDDXXXXXT123456789)
        if (strpos($oid_raw, 'T') !== false) {
            $parts = explode('T', $oid_raw);
            $oid = $parts[0];
        } else {
            $oid = $oid_raw;
        }

        if (!PurifyTrade::isValidOrderId($oid)) {
            http_response_code(400);
            exit("INVALID_OID");
        }

        if (isset($_POST['status']) && $_POST['status'] === 'success') {
             // Amount Verification
             $order = PurifyTrade::getOrder($oid);
             $received_amount = (float)($_POST['total_amount'] ?? 0) / 100;
             $expected_amount = (float)($order['total_gross'] ?? 0);
             
             // Allow for rounding tolerance +/- 0.01 or PayTR's installment surcharge
             if (!$order || $received_amount < ($expected_amount - 0.05)) {
                 PurifyTrade::log('SECURITY_ALERT', 'Callback amount mismatch or order missing', [
                     'oid' => $oid, 
                     'received' => $received_amount, 
                     'expected' => $expected_amount
                 ]);
                 http_response_code(400);
                 exit("AMOUNT_MISMATCH");
             }

             PurifyTrade::updateOrderStatus($oid, 'paid');
        } elseif (isset($_POST['status']) && $_POST['status'] === 'failed') {
             PurifyTrade::updateOrderStatus($oid, 'failed');
        }
        
        // Absolute Clean Termination for PayTR/Gateway Verification
        ob_clean();
        exit("OK");
    } else {
        http_response_code(400);
        exit("VERIFICATION_ERROR");
    }
}

if ($request_path === 'api/captcha-refresh') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'img' => NywhashCaptcha::getImageBase64()]);
    exit;
}

if ($request_path === 'api/contact') {
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }
    
    $name = isset($_POST['name']) ? htmlspecialchars(strip_tags(trim($_POST['name']))) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $message = isset($_POST['message']) ? htmlspecialchars(strip_tags(trim($_POST['message']))) : '';
    
    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }
    
    if (isset($cfg_enable_captcha) && $cfg_enable_captcha) {
        $captcha_input = $_POST['captcha_result'] ?? '';
        if (!NywhashCaptcha::verify($captcha_input)) {
            echo json_encode(['success' => false, 'message' => 'Invalid captcha. Please solve the math question.']);
            exit;
        }
    }

    $data_dir = __DIR__ . '/inc/data/messages';
    if (!is_dir($data_dir)) @mkdir($data_dir, 0755, true);
    
    $month_file = $data_dir . '/' . date('Y-m') . '.json';
    $messages = [];
    if (file_exists($month_file)) {
        $json_data = @file_get_contents($month_file);
        if ($json_data) $messages = json_decode($json_data, true) ?: [];
    }
    
    $new_msg = [
        'id' => uniqid('msg_'),
        'name' => $name,
        'email' => $email,
        'message' => $message,
        'date' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'read' => false
    ];
    
    array_unshift($messages, $new_msg);
    @file_put_contents($month_file, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    
    if (empty($cfg_smtp_host) || empty($cfg_contact_email)) {
        echo json_encode(['success' => true, 'message' => 'Your message has been securely sent.']);
        exit;
    }
    
    require_once __DIR__ . '/inc/smtp.php';
    $mailer = new SimpleSMTP($cfg_smtp_host, $cfg_smtp_port, $cfg_smtp_user, $cfg_smtp_pass, $cfg_smtp_secure);
    
    try {
        $body = "<h2>New Contact Message from " . htmlspecialchars($cfg_name) . "</h2>";
        $body .= "<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>";
        $body .= "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
        $body .= "<p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";
        
        $from_email = !empty($cfg_mail_from_email) ? $cfg_mail_from_email : ((!empty($cfg_smtp_user) && strpos($cfg_smtp_user, '@') !== false) ? $cfg_smtp_user : $cfg_contact_email);
        $from_name = !empty($cfg_mail_from_name) ? $cfg_mail_from_name : $cfg_name;

        $mailer->send(
            $cfg_contact_email,
            "New Contact Form Submission - " . $name,
            $body,
            $from_email,
            $from_name
        );
        echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully.']);
    } catch (Exception $e) {
        // We log success true anyway because the message was successfully archived in JSON, even if SMTP failed.
        echo json_encode(['success' => true, 'message' => 'Your message has been securely archived.']);
    }
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