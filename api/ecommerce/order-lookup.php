<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

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

require_once dirname(__DIR__, 2) . '/inc/PurifyTrade.php';
if (!PurifyTrade::isValidOrderId($oid)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Order ID format.']);
    exit;
}

$order = PurifyTrade::getOrder($oid);
if ($order) {
    unset($order['customer']['email'], $order['customer']['phone'], $order['customer']['address']);
    echo json_encode(['success' => true, 'order' => $order]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Order not found. Please check your Order ID.']);
}
exit;
