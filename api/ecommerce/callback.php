<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

require_once dirname(__DIR__, 2) . '/inc/PurifyTrade.php';

$action = (string)($_GET['action'] ?? '');
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
        $order = PurifyTrade::getOrder($oid);
        $received_amount = (float)($_POST['total_amount'] ?? 0) / 100;
        $expected_amount = (float)($order['total_gross'] ?? 0);

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

    if (ob_get_level()) ob_clean();
    exit("OK");
}

http_response_code(400);
exit("VERIFICATION_ERROR");
