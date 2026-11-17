<?php
define('DIR_ACCESS', true);
require_once __DIR__ . '/../inc/config.php';

// Güvenlik kontrolü - Sadece cron veya admin yetkisi
$is_cli = (php_sapi_name() === 'cli');
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$is_cli && !$is_admin) {
    http_response_code(403);
    exit('Unauthorized access.');
}

$orders_dir = __DIR__ . '/../inc/data/ecommerce/orders';
$archived_count = 0;
$freed_inodes = 0;

// Tüm tekil mikro-dosyaları bul (PRFY... formatında olanlar)
$order_files = glob($orders_dir . '/PRFY*.json');
$months_data = [];

// Mevcut ayın verilerini arşivlememek daha güvenlidir, aktif siparişler olabilir.
$current_month = date('Y-m');

foreach ($order_files as $file) {
    $data = json_decode(file_get_contents($file), true) ?: [];
    if (!isset($data['id'])) continue; // Zaten arşiv dosyasıysa atla
    
    // Siparişin tarihinden ayını bul
    $date_part = isset($data['date']) ? substr($data['date'], 0, 7) : ''; // YYYY-MM
    if (empty($date_part)) continue;
    
    // Sadece geçmiş ayları arşivle (Aktif ayı elleme)
    if ($date_part === $current_month) continue;

    if (!isset($months_data[$date_part])) {
        $months_data[$date_part] = [];
        
        // Eğer o ayın zaten bir arşiv dosyası varsa, onu yükle
        $archive_file = $orders_dir . '/' . $date_part . '.json';
        if (file_exists($archive_file)) {
            $existing_archive = json_decode(file_get_contents($archive_file), true) ?: [];
            if (!isset($existing_archive['id'])) { // Array of orders
                $months_data[$date_part] = $existing_archive;
            }
        }
    }
    
    // Siparişi arşiv dizisine ekle
    $months_data[$date_part][$data['id']] = $data;
    $archived_count++;
}

// Toplanan verileri aylık dosyalara yaz
foreach ($months_data as $month => $orders) {
    $archive_file = $orders_dir . '/' . $month . '.json';
    
    $fp = fopen($archive_file, 'c+');
    if ($fp && flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($orders, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

// Başarıyla arşivlenen tekil mikro-dosyaları sil (Inode temizliği)
foreach ($order_files as $file) {
    $data = json_decode(file_get_contents($file), true) ?: [];
    if (isset($data['id']) && isset($data['date'])) {
        $date_part = substr($data['date'], 0, 7);
        if ($date_part !== $current_month && isset($months_data[$date_part][$data['id']])) {
            @unlink($file);
            $freed_inodes++;
        }
    }
}

echo json_encode([
    'status' => 'success',
    'message' => "Order archiving complete.",
    'archived_orders' => $archived_count,
    'freed_inodes' => $freed_inodes
]);
exit;
