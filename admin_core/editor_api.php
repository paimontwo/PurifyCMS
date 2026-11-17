<?php
defined('DIR_ACCESS') or exit;
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
require_once __DIR__ . '/../inc/config.php';
$action = $_GET['action'] ?? '';
$page = $_GET['page'] ?? '';
$page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);
if (empty($page)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Page not specified']);
    exit;
}
$theme = $cfg_active_theme ?? 'purifydefault';
$page_path = __DIR__ . '/../themes/' . $theme . '/pages/' . $page . '.php';
if (!file_exists($page_path)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => "Page not found: $page"]);
    exit;
}
if ($action === 'read') {
    $content = file_get_contents($page_path);
    $title = '';
    $desc = '';
    if (preg_match('/\$title\s*=\s*[\'"](.*?)[\'"]\s*;/', $content, $matches)) {
        $title = $matches[1];
    }
    if (preg_match('/\$desc\s*=\s*[\'"](.*?)[\'"]\s*;/', $content, $matches)) {
        $desc = $matches[1];
    }
    $zones = [];
    $pattern = '/<!--\s*purify:zone:(.*?)_start\s*-->(.*?)<!--\s*purify:zone:\1_end\s*-->/is';
    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $zones[] = [
                'name' => $match[1],
                'content' => trim($match[2])
            ];
        }
    }
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'title' => $title,
            'desc' => $desc,
            'zones' => $zones,
            'has_zones' => !empty($zones)
        ]
    ]);
    exit;
}
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'CSRF validation failed']);
        exit;
    }
    $new_title = $_POST['title'] ?? '';
    $new_desc = $_POST['desc'] ?? '';
    $zones_json = $_POST['zones'] ?? '[]';
    $new_zones = json_decode($zones_json, true);
    if (!is_array($new_zones)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid zones data format']);
        exit;
    }
    $file_content = file_get_contents($page_path);
    if (preg_match('/\$title\s*=\s*[\'"](.*?)[\'"]\s*;/', $file_content, $m)) {
        $file_content = str_replace($m[0], "\$title = \"" . addslashes($new_title) . "\";", $file_content);
    }
    if (preg_match('/\$desc\s*=\s*[\'"](.*?)[\'"]\s*;/', $file_content, $m)) {
        $file_content = str_replace($m[0], "\$desc = \"" . addslashes($new_desc) . "\";", $file_content);
    }
    foreach ($new_zones as $zone) {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $zone['name']);
        $content = $zone['content'];
        
        // Fix 3.5.3 WYSIWYG editor escaping PHP tags and object operators
        $content = str_replace(
            ['&lt;?=', '&lt;?php', '?&gt;', '-&gt;'], 
            ['<?=', '<?php', '?>', '->'], 
            $content
        );
        
        $pattern = '/(<!--\s*purify:zone:' . preg_quote($name) . '_start\s*-->)(.*?)(<!--\s*purify:zone:' . preg_quote($name) . '_end\s*-->)/is';
        if (preg_match($pattern, $file_content)) {
            $file_content = preg_replace(
                $pattern,
                "$1\n" . $content . "\n$3",
                $file_content
            );
        }
    }
    if (@file_put_contents($page_path, $file_content, LOCK_EX)) {
        if (class_exists('PurifyVolt')) PurifyVolt::flushHtml();
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => p_lang('editor_api_save_error', 'Görsel düzenleyici değişiklikleri kaydedemedi. Parantez içi bölümleri tam kontrol edin.')]);
    }
    exit;
}
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit;
