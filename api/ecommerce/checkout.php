<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

header('Content-Type: application/json; charset=utf-8');

$root_dir = dirname(__DIR__, 2);

// IP-based Rate Limiting (sliding window: max 10 requests per 60 seconds per IP)
$rl_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rl_dir = $root_dir . '/inc/data/cache';
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

require_once $root_dir . '/inc/PurifyTrade.php';

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
    if ($method === 'iban' && empty($cfg_bank_details)) {
        if (!empty($cfg_paytr_m_id)) $method = 'paytr';
    }

    $products_file = $root_dir . '/inc/data/ecommerce/products.json';
    $master_products = file_exists($products_file) ? (json_decode(file_get_contents($products_file), true) ?: []) : [];
    $registry_map = [];
    foreach ($master_products as $mp) {
        if (isset($mp['slug'])) $registry_map[$mp['slug']] = $mp;
        if (isset($mp['id'])) $registry_map[$mp['id']] = $mp;
    }

    $total_net = 0;
    $total_tax = 0;
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
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => 'Order creation failed: ' . $order_result['msg']]);
        exit;
    }

    $order_id = $order_result['order_id'];
}

if (empty($order_id) || !is_string($order_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'msg' => 'Order Registry ID Validation Failed.']);
    exit;
}

if ($method !== 'bank' && $method !== 'iban') {
    $gateway = PurifyTrade::getGateway($method);
    if ($gateway) {
        $res = $gateway->getToken($order_id, (float)$total_gross, $customer, $processed_items);
        if ($res['status'] === 'success') {
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
