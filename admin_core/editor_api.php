<?php
defined('DIR_ACCESS') or exit;

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../inc/config.php';

$action = $_GET['action'] ?? '';
$page = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['page'] ?? '');

if (empty($page)) {
    echo json_encode(['success' => false, 'message' => 'Page not specified']);
    exit;
}

$theme = $cfg_active_theme ?? 'purifydefault';
$page_path = __DIR__ . '/../themes/' . $theme . '/pages/' . $page . '.php';

if (!file_exists($page_path)) {
    echo json_encode(['success' => false, 'message' => "Page not found: $page"]);
    exit;
}

if ($action === 'read') {
    $content = file_get_contents($page_path);
    $title = '';
    $desc = '';

    if (preg_match('/\$title\s*=\s*([\'"])(.*?)\1\s*;/s', $content, $matches)) {
        $title = stripslashes($matches[2]);
    }
    if (preg_match('/\$desc\s*=\s*([\'"])(.*?)\1\s*;/s', $content, $matches)) {
        $desc = stripslashes($matches[2]);
    }

    $zones = [];
    $pattern = '/<!--\s*purify:zone:(.*?)_start\s*-->(.*?)<!--\s*purify:zone:\1_end\s*-->/is';
    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $zone_content = trim($match[2]);
            $blocks = null;
            if (preg_match('/<!--\s*purify:blocks:([A-Za-z0-9+\/=]+)\s*-->/i', $zone_content, $bm)) {
                $decoded = json_decode(base64_decode($bm[1]), true);
                if (is_array($decoded)) {
                    $blocks = $decoded;
                }
                $zone_content = trim(preg_replace('/<!--\s*purify:blocks:[A-Za-z0-9+\/=]+\s*-->\s*/i', '', $zone_content, 1));
            }
            $has_php = (bool)preg_match('/<\?(?:php|=|\s)/i', $zone_content);
            $has_interactive = (bool)preg_match('/<(?:form|input|textarea|select|script|style)\b/i', $zone_content);
            $zones[] = [
                'name' => $match[1],
                'content' => $zone_content,
                'blocks' => $blocks,
                'is_dynamic' => ($blocks === null && ($has_php || $has_interactive)),
                'has_php' => $has_php,
                'has_interactive' => $has_interactive
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'page' => $page,
            'theme' => $theme,
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
        echo json_encode(['success' => false, 'message' => 'CSRF validation failed. Please refresh the page and try again.']);
        exit;
    }

    $new_title = $_POST['title'] ?? '';
    $new_desc = $_POST['desc'] ?? '';
    $zones_json = $_POST['zones'] ?? '[]';
    $new_zones = json_decode($zones_json, true);

    if (!is_array($new_zones)) {
        echo json_encode(['success' => false, 'message' => 'Invalid zones data format']);
        exit;
    }

    $file_content = file_get_contents($page_path);

    if (preg_match('/\$title\s*=\s*([\'"])(.*?)\1\s*;/s', $file_content)) {
        $safe_title = '$title = ' . var_export($new_title, true) . ';';
        $file_content = preg_replace_callback('/\$title\s*=\s*([\'"])(.*?)\1\s*;/s', function() use ($safe_title) {
            return $safe_title;
        }, $file_content, 1);
    }

    if (preg_match('/\$desc\s*=\s*([\'"])(.*?)\1\s*;/s', $file_content)) {
        $safe_desc = '$desc = ' . var_export($new_desc, true) . ';';
        $file_content = preg_replace_callback('/\$desc\s*=\s*([\'"])(.*?)\1\s*;/s', function() use ($safe_desc) {
            return $safe_desc;
        }, $file_content, 1);
    }

    foreach ($new_zones as $zone) {
        if (!isset($zone['name']) || !isset($zone['content'])) continue;
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $zone['name']);
        $zone_body = trim((string)$zone['content']);
        $mode = $zone['mode'] ?? 'blocks';

        $zone_body = trim(preg_replace('/<!--\s*purify:blocks:[A-Za-z0-9+\/=]+\s*-->\s*/i', '', $zone_body));

        if ($mode === 'blocks' && isset($zone['blocks']) && is_array($zone['blocks'])) {
            $encoded = base64_encode(json_encode($zone['blocks'], JSON_UNESCAPED_UNICODE));
            $zone_body = "<!-- purify:blocks:{$encoded} -->\n" . $zone_body;
        }

        $pattern = '/(<!--\s*purify:zone:' . preg_quote($name, '/') . '_start\s*-->)(.*?)(<!--\s*purify:zone:' . preg_quote($name, '/') . '_end\s*-->)/is';
        if (preg_match($pattern, $file_content)) {
            $file_content = preg_replace_callback($pattern, function($m) use ($zone_body) {
                return $m[1] . "\n" . $zone_body . "\n" . $m[3];
            }, $file_content, 1);
        }
    }

    $tmp_check = sys_get_temp_dir() . '/purify_lint_' . md5($page_path . microtime(true)) . '.php';
    if (@file_put_contents($tmp_check, $file_content) !== false) {
        if (function_exists('exec')) {
            $lint_out = [];
            $lint_code = 0;
            @exec('php -l ' . escapeshellarg($tmp_check) . ' 2>&1', $lint_out, $lint_code);
            @unlink($tmp_check);
            if ($lint_code !== 0) {
                $err_detail = implode(' ', $lint_out);
                $err_detail = str_replace($tmp_check, $page . '.php', $err_detail);
                echo json_encode([
                    'success' => false,
                    'message' => 'PHP syntax error detected. Save cancelled to protect your site: ' . $err_detail
                ]);
                exit;
            }
        } else {
            @unlink($tmp_check);
        }
    }

    if (@file_put_contents($page_path, $file_content, LOCK_EX) !== false) {
        if (function_exists('purify_clear_page_cache')) {
            purify_clear_page_cache();
        } elseif (class_exists('PurifyVolt')) {
            PurifyVolt::flushHtml();
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => p_lang('editor_api_save_error', 'Unable to save changes. Please verify write permissions on the theme directory.')
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit;
