<?php
define('DIR_ACCESS', true);
require_once __DIR__ . '/../../inc/config.php';
require_once __DIR__ . '/../../inc/PurifyTrade.php';

$oid = $_GET['oid'] ?? '';
$pid = $_GET['pid'] ?? '';

if (empty($oid) || empty($pid)) {
    header('HTTP/1.1 400 Bad Request');
    die('Missing mandatory identification.');
}

$order = PurifyTrade::getOrder($oid);
if (!$order) {
    header('HTTP/1.1 404 Not Found');
    die('Order record not found.');
}

if ($order['status'] !== 'paid') {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied: Payment verification required.');
}

$found_item = null;
foreach ($order['items'] as $item) {
    if ($item['id'] === $pid) {
        $found_item = $item;
        break;
    }
}

if (!$found_item) {
    header('HTTP/1.1 404 Not Found');
    die('Asset not associated with this order.');
}

$products_file = __DIR__ . '/../../inc/data/ecommerce/products.json';
$products = json_decode(file_get_contents($products_file), true) ?: [];
$product = $products[$pid] ?? null;

if (!$product || ($product['type'] ?? '') !== 'digital' || empty($product['file'])) {
    header('HTTP/1.1 403 Forbidden');
    die('This product does not contain a digital asset.');
}

$file_path = __DIR__ . '/../../inc/data/ecommerce/vault/' . basename($product['file']);

if (!file_exists($file_path)) {
    header('HTTP/1.1 500 Internal Server Error');
    die('Asset temporarily unavailable. Please contact support.');
}

if (ob_get_level()) ob_end_clean();

$mime = mime_content_type($file_path) ?: 'application/octet-stream';
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($product['file']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

readfile($file_path);
exit;
