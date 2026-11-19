<?php
/**
 * PurifyCMS HTTP-Level Security & Regression Test Suite
 */

echo "==> Running PurifyCMS HTTP Security Tests..." . PHP_EOL;

$root = dirname(__DIR__);
$config_file = $root . '/inc/config.php';
$sample_file = $root . '/inc/config.sample.php';
$created_temp_config = false;
$created_temp_products = false;
$products_file = $root . '/inc/data/ecommerce/products.json';

if (!file_exists($config_file) && file_exists($sample_file)) {
    $sample = file_get_contents($sample_file);
    $sample = preg_replace('/\$cfg_enable_ecommerce\s*=\s*false;/', '$cfg_enable_ecommerce = true;', $sample);
    file_put_contents($config_file, $sample);
    $created_temp_config = true;
}

if (!file_exists($products_file)) {
    @mkdir(dirname($products_file), 0755, true);
    file_put_contents($products_file, json_encode([
        'prod_test' => [
            'id' => 'prod_test',
            'slug' => 'test-product',
            'title' => 'Test Product',
            'price' => 100,
            'tax_rate' => 20,
            'type' => 'digital'
        ]
    ], JSON_PRETTY_PRINT));
    $created_temp_products = true;
}

// Pick a free port
$sock = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
if (!$sock) {
    echo " FAIL: Could not bind test port: {$errstr}" . PHP_EOL;
    exit(1);
}
$addr = stream_socket_get_name($sock, false);
$port = (int)substr(strrchr($addr, ':'), 1);
fclose($sock);

// Clear any prior checkout rate limit file for 127.0.0.1
$rl_file = $root . '/inc/data/cache/.co_rl_' . md5('127.0.0.1');
if (file_exists($rl_file)) @unlink($rl_file);

$router_file = sys_get_temp_dir() . '/purifycms_test_router_' . getmypid() . '.php';
file_put_contents($router_file, "<?php\n\$_SERVER['SCRIPT_NAME'] = '/index.php';\nrequire " . var_export($root . '/index.php', true) . ";\n");

$cmd = sprintf('php -S 127.0.0.1:%d -t %s %s', $port, escapeshellarg($root), escapeshellarg($router_file));
$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];
$proc = proc_open($cmd, $descriptors, $pipes);
if (!is_resource($proc)) {
    @unlink($router_file);
    echo " FAIL: Could not start PHP test server" . PHP_EOL;
    exit(1);
}

$cleanup = function () use ($proc, $pipes, $created_temp_config, $config_file, $created_temp_products, $products_file, $rl_file, $router_file) {
    foreach ($pipes as $p) {
        if (is_resource($p)) @fclose($p);
    }
    if (is_resource($proc)) {
        proc_terminate($proc);
        proc_close($proc);
    }
    if ($created_temp_config && file_exists($config_file)) @unlink($config_file);
    if ($created_temp_products && file_exists($products_file)) @unlink($products_file);
    if (file_exists($rl_file)) @unlink($rl_file);
    if (file_exists($router_file)) @unlink($router_file);
};

// Wait for server to listen
$ready = false;
for ($i = 0; $i < 30; $i++) {
    $fp = @fsockopen('127.0.0.1', $port, $e1, $e2, 0.2);
    if ($fp) {
        fclose($fp);
        $ready = true;
        break;
    }
    usleep(100000);
}

if (!$ready) {
    $cleanup();
    echo " FAIL: Test server did not start in time" . PHP_EOL;
    exit(1);
}

function http_request($port, $method, $path, $body = null, $headers = []) {
    $ch = curl_init("http://127.0.0.1:{$port}{$path}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_PROXY, '');
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['status' => $status, 'body' => $response, 'error' => $err];
}

// Read admin path & ecommerce flag from config
$cfg_admin_path = 'admin';
$cfg_enable_ecommerce = true;
if (file_exists($config_file)) {
    $cfg_src = file_get_contents($config_file);
    if (preg_match('/\$cfg_admin_path\s*=\s*[\'"]([^\'"]+)[\'"]/', $cfg_src, $m)) {
        $cfg_admin_path = trim($m[1], '/');
    }
    if (preg_match('/\$cfg_enable_ecommerce\s*=\s*(true|false)/i', $cfg_src, $m)) {
        $cfg_enable_ecommerce = (strtolower($m[1]) === 'true');
    }
}

$failed = false;

// Test 1: Unauthenticated requests to admin routes are rejected (S1)
echo "Testing unauthenticated admin route protection...";
$admin_routes = [
    '/' . $cfg_admin_path . '/messages',
    '/' . $cfg_admin_path . '/orders',
    '/' . $cfg_admin_path . '/settings',
    '/' . $cfg_admin_path . '/editor_api?action=read&page=index',
];
foreach ($admin_routes as $route) {
    $res = http_request($port, 'GET', $route);
    if ($res['status'] !== 401) {
        echo " FAIL ({$route} returned HTTP {$res['status']}, expected 401)" . PHP_EOL;
        $failed = true;
        break;
    }
}
if (!$failed) echo " OK" . PHP_EOL;

// Test 2: Malformed / traversal order IDs are rejected (S2)
if (!$failed && $cfg_enable_ecommerce) {
    echo "Testing order lookup input validation...";
    $bad_oids = ['../../messages/2026-11', '../products', '../../config', 'a/b', '2026-11'];
    foreach ($bad_oids as $oid) {
        $res = http_request($port, 'GET', '/api/ecommerce/order-lookup?oid=' . urlencode($oid));
        $json = json_decode($res['body'], true);
        if ($res['status'] !== 400 || !is_array($json) || ($json['success'] ?? true) !== false) {
            echo " FAIL (oid={$oid} returned HTTP {$res['status']})" . PHP_EOL;
            $failed = true;
            break;
        }
    }
    if (!$failed) echo " OK" . PHP_EOL;
}

// Test 3: Tampered checkouts are cleanly rejected without crashing (S3 & S4)
if (!$failed && $cfg_enable_ecommerce) {
    echo "Testing tampered checkout rejection...";
    $products = json_decode(file_get_contents($products_file), true) ?: [];
    $first_product = reset($products);
    $valid_slug = $first_product['slug'] ?? ($first_product['id'] ?? 'test-product');
    $valid_price = (float)($first_product['price'] ?? 100);

    $cases = [
        'unregistered product' => [
            'items' => [['slug' => 'nonexistent-hacked-item', 'price' => 1, 'tax_rate' => 20]],
            'customer' => ['email' => 'test@example.com', 'name' => 'Test User'],
            'payment_method' => 'iban'
        ],
        'tampered price' => [
            'items' => [['slug' => $valid_slug, 'price' => 0.01, 'tax_rate' => 20]],
            'customer' => ['email' => 'test@example.com', 'name' => 'Test User'],
            'payment_method' => 'iban'
        ],
        'negative tax_rate' => [
            'items' => [['slug' => $valid_slug, 'price' => $valid_price, 'tax_rate' => -100]],
            'customer' => ['email' => 'test@example.com', 'name' => 'Test User'],
            'payment_method' => 'iban'
        ],
    ];

    foreach ($cases as $label => $payload) {
        $res = http_request($port, 'POST', '/api/ecommerce/checkout', json_encode($payload), ['Content-Type: application/json']);
        $json = json_decode($res['body'], true);
        if ($res['status'] !== 400 || !is_array($json) || ($json['status'] ?? '') !== 'error') {
            echo " FAIL ({$label}: HTTP {$res['status']} body={$res['body']})" . PHP_EOL;
            $failed = true;
            break;
        }
    }
    if (!$failed) echo " OK" . PHP_EOL;
}

// Test 4: Public cache flush action (?action=revalidate_license) is removed
if (!$failed) {
    echo "Testing public cache flush removal...";
    $sentinel = $root . '/inc/data/cache/ci_sentinel_test.html';
    @mkdir(dirname($sentinel), 0755, true);
    file_put_contents($sentinel, '<!-- sentinel -->');
    http_request($port, 'GET', '/?action=revalidate_license');
    $still_exists = file_exists($sentinel);
    if ($still_exists) @unlink($sentinel);
    if (!$still_exists) {
        echo " FAIL (public ?action=revalidate_license still flushed cache)" . PHP_EOL;
        $failed = true;
    } else {
        echo " OK" . PHP_EOL;
    }
}

$cleanup();

if ($failed) {
    echo "==> HTTP Security Tests Failed!" . PHP_EOL;
    exit(1);
}

echo "==> All HTTP Security Tests Passed Successfully!" . PHP_EOL;
exit(0);
