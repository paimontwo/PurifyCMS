<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

require_once __DIR__ . '/../inc/security.php';
require_once __DIR__ . '/../inc/config.php';

if (!isset($cfg_enable_filemanager) || !$cfg_enable_filemanager) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$ALLOWED_EXT = ['php', 'css', 'js', 'json', 'md', 'txt', 'html', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
$MAX_FILE_BYTES = 6 * 1024 * 1024; // 6MB

function json_out($ok, $data = []) {
    echo json_encode(array_merge(['ok' => (bool)$ok], $data));
    exit;
}

function norm_rel($rel) {
    $rel = (string)$rel;
    $rel = str_replace('\\', '/', $rel);
    $rel = preg_replace('#/+#', '/', $rel);
    $rel = trim($rel, '/');
    $rel = str_replace("\0", '', $rel);
    return $rel;
}

function themes_root() {
    static $root = null;
    if ($root === null) {
        $root = realpath(__DIR__ . '/../themes');
        if ($root === false) {
            $root = '';
        }
    }
    return $root;
}

function is_under_root($abs, $root) {
    $absN = str_replace('\\', '/', $abs);
    $rootN = rtrim(str_replace('\\', '/', $root), '/');
    return $rootN !== '' && (strpos($absN, $rootN . '/') === 0 || $absN === $rootN);
}

function resolve_existing($rel) {
    $root = themes_root();
    if ($root === '') {
        return false;
    }
    $rel = norm_rel($rel);
    $abs = realpath($root . '/' . $rel);
    if ($abs === false) {
        return false;
    }
    if (!is_under_root($abs, $root)) {
        return false;
    }
    return $abs;
}

function resolve_dir_existing($rel) {
    $abs = resolve_existing($rel);
    if ($abs === false) {
        return false;
    }
    if (!is_dir($abs)) {
        return false;
    }
    return $abs;
}

function resolve_parent_dir($relDir) {
    $relDir = norm_rel($relDir);
    if ($relDir === '') {
        return themes_root();
    }
    return resolve_dir_existing($relDir);
}

function safe_name($name) {
    $name = (string)$name;
    $name = trim($name);
    if ($name === '' || $name === '.' || $name === '..') {
        return false;
    }
    if (preg_match('#[\\/\0]#', $name)) {
        return false;
    }
    return $name;
}

function ext_allowed($filename, $allowed) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === '') {
        return false;
    }
    return in_array($ext, $allowed, true);
}

function get_request_payload() {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'multipart/form-data') !== false) {
        return ['type' => 'multipart', 'action' => $_POST['action'] ?? '', 'csrf_token' => $_POST['csrf_token'] ?? ''];
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = [];
    }
    return ['type' => 'json', 'data' => $data, 'action' => $data['action'] ?? '', 'csrf_token' => $data['csrf_token'] ?? ''];
}

$req = get_request_payload();
$action = (string)$req['action'];
$csrf = (string)$req['csrf_token'];

if (!verify_csrf_token($csrf)) {
    json_out(false, ['error' => 'CSRF token validation failed.']);
}

$payload = ($req['type'] === 'json') ? ($req['data'] ?? []) : [];

switch ($action) {
    case 'list': {
        $path = norm_rel($payload['path'] ?? '');
        $dirAbs = resolve_parent_dir($path);
        if ($dirAbs === false) {
            json_out(false, ['error' => 'Invalid path']);
        }

        $root = themes_root();
        $items = [];
        $dh = @opendir($dirAbs);
        if ($dh === false) {
            json_out(false, ['error' => 'Unable to open directory']);
        }
        while (($entry = readdir($dh)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if ($entry[0] === '.') {
                continue;
            }
            $full = $dirAbs . DIRECTORY_SEPARATOR . $entry;
            $type = is_dir($full) ? 'dir' : 'file';

            $relPath = trim(str_replace('\\', '/', substr(str_replace('\\', '/', $full), strlen(str_replace('\\', '/', $root)))), '/');
            if ($type === 'file') {
                $size = @filesize($full);
                $meta = ($size !== false) ? ($size . ' bytes') : '';
            } else {
                $meta = 'folder';
            }

            $items[] = [
                'name' => $entry,
                'type' => $type,
                'path' => $relPath,
                'meta' => $meta
            ];
        }
        closedir($dh);

        usort($items, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'dir' ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });

        $parent = null;
        if ($path !== '') {
            $parent = preg_replace('#/[^/]+$#', '', $path);
            if ($parent === $path) {
                $parent = '';
            }
        }

        json_out(true, ['items' => $items, 'parent' => $parent]);
    }

    case 'get_tree': {
        function build_tree($dir) {
            $root = themes_root();
            $items = [];
            $files = @scandir($dir);
            if (!$files) return [];

            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || $file[0] === '.') continue;
                $full = $dir . DIRECTORY_SEPARATOR . $file;
                $rel = trim(str_replace('\\', '/', substr($full, strlen($root))), '/');
                
                if (is_dir($full)) {
                    $items[] = [
                        'name' => $file,
                        'type' => 'dir',
                        'path' => $rel,
                        'children' => build_tree($full)
                    ];
                } else {
                    $items[] = [
                        'name' => $file,
                        'type' => 'file',
                        'path' => $rel
                    ];
                }
            }
            
            usort($items, function($a, $b) {
                if ($a['type'] !== $b['type']) return $a['type'] === 'dir' ? -1 : 1;
                return strcasecmp($a['name'], $b['name']);
            });
            
            return $items;
        }
        
        json_out(true, ['tree' => build_tree(themes_root())]);
    }

    case 'read': {
        $path = norm_rel($payload['path'] ?? '');
        $abs = resolve_existing($path);
        if ($abs === false || !is_file($abs)) {
            json_out(false, ['error' => 'File not found']);
        }

        if (!ext_allowed($path, $ALLOWED_EXT)) {
            json_out(false, ['error' => 'File type not allowed']);
        }

        $size = @filesize($abs);
        if ($size !== false && $size > $MAX_FILE_BYTES) {
            json_out(false, ['error' => 'File too large']);
        }

        $content = @file_get_contents($abs);
        if ($content === false) {
            json_out(false, ['error' => 'Unable to read file']);
        }

        json_out(true, ['content' => $content]);
    }

    case 'save': {
        $path = norm_rel($payload['path'] ?? '');
        $content = (string)($payload['content'] ?? '');

        if (!ext_allowed($path, $ALLOWED_EXT)) {
            json_out(false, ['error' => 'File type not allowed']);
        }
        if (strlen($content) > $MAX_FILE_BYTES) {
            json_out(false, ['error' => 'Content too large']);
        }

        $abs = resolve_existing($path);
        if ($abs === false || !is_file($abs)) {
            json_out(false, ['error' => 'File not found']);
        }

        $bytes = @file_put_contents($abs, $content, LOCK_EX);
        if ($bytes === false) {
            json_out(false, ['error' => 'Write failed (check file permissions)']);
        }

        json_out(true);
    }

    case 'mkdir': {
        $dir = norm_rel($payload['path'] ?? '');
        $name = safe_name($payload['name'] ?? '');
        if ($name === false) {
            json_out(false, ['error' => 'Invalid folder name']);
        }

        $parentAbs = resolve_parent_dir($dir);
        if ($parentAbs === false) {
            json_out(false, ['error' => 'Invalid path']);
        }

        $target = $parentAbs . DIRECTORY_SEPARATOR . $name;
        $root = themes_root();
        if (!is_under_root($target, $root)) {
            json_out(false, ['error' => 'Invalid target']);
        }

        if (file_exists($target)) {
            json_out(false, ['error' => 'Target already exists']);
        }

        if (!@mkdir($target, 0755, false)) {
            json_out(false, ['error' => 'Failed to create folder']);
        }

        json_out(true);
    }

    case 'newfile': {
        $dir = norm_rel($payload['path'] ?? '');
        $name = safe_name($payload['name'] ?? '');
        if ($name === false) {
            json_out(false, ['error' => 'Invalid file name']);
        }
        if (!ext_allowed($name, $ALLOWED_EXT)) {
            json_out(false, ['error' => 'File type not allowed']);
        }

        $parentAbs = resolve_parent_dir($dir);
        if ($parentAbs === false) {
            json_out(false, ['error' => 'Invalid path']);
        }

        $target = $parentAbs . DIRECTORY_SEPARATOR . $name;
        $root = themes_root();
        if (!is_under_root($target, $root)) {
            json_out(false, ['error' => 'Invalid target']);
        }

        if (file_exists($target)) {
            json_out(false, ['error' => 'Target already exists']);
        }

        $bytes = @file_put_contents($target, '', LOCK_EX);
        if ($bytes === false) {
            json_out(false, ['error' => 'Failed to create file']);
        }

        json_out(true);
    }

    case 'delete': {
        $path = norm_rel($payload['path'] ?? '');
        $abs = resolve_existing($path);
        if ($abs === false) {
            json_out(false, ['error' => 'Not found']);
        }

        if (is_file($abs)) {
            if (!@unlink($abs)) {
                json_out(false, ['error' => 'Failed to delete file']);
            }
            json_out(true);
        }

        if (is_dir($abs)) {
            $entries = @scandir($abs);
            if (is_array($entries) && count(array_diff($entries, ['.', '..'])) > 0) {
                json_out(false, ['error' => 'Folder not empty']);
            }
            if (!@rmdir($abs)) {
                json_out(false, ['error' => 'Failed to delete folder']);
            }
            json_out(true);
        }

        json_out(false, ['error' => 'Unsupported target']);
    }

    case 'rename': {
        $from = norm_rel($payload['from'] ?? '');
        $to = norm_rel($payload['to'] ?? '');

        $fromAbs = resolve_existing($from);
        if ($fromAbs === false) {
            json_out(false, ['error' => 'Source not found']);
        }

        if (is_file($fromAbs)) {
            if (!ext_allowed($to, $ALLOWED_EXT)) {
                json_out(false, ['error' => 'Target file type not allowed']);
            }
        }

        $root = themes_root();
        $toAbs = $root . '/' . $to;
        $toAbs = str_replace('/', DIRECTORY_SEPARATOR, $toAbs);
        $toParent = realpath(dirname($toAbs));
        if ($toParent === false || !is_under_root($toParent, $root)) {
            json_out(false, ['error' => 'Invalid target']);
        }

        if (file_exists($toAbs)) {
            json_out(false, ['error' => 'Target already exists']);
        }

        if (!@rename($fromAbs, $toAbs)) {
            json_out(false, ['error' => 'Rename failed']);
        }

        json_out(true);
    }

    case 'webp_convert': {
        $path = norm_rel($payload['path'] ?? '');
        $abs = resolve_existing($path);
        if ($abs === false || !is_file($abs)) {
            json_out(false, ['error' => 'File not found']);
        }

        if (!function_exists('imagewebp')) {
            json_out(false, ['error' => 'PHP GD extension (with webp support) is not installed.']);
        }

        $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
        $img = null;
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $img = @imagecreatefromjpeg($abs);
        } elseif ($ext === 'png') {
            $img = @imagecreatefrompng($abs);
            if ($img) {
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
            }
        } else {
            json_out(false, ['error' => 'Only JPG and PNG can be converted to WebP.']);
        }

        if (!$img) {
            json_out(false, ['error' => 'Failed to process image.']);
        }

        $newPath = preg_replace('/\.[^.]+$/', '.webp', $abs);
        if (file_exists($newPath)) {
            // If already exists, maybe append a random id? 
            // Better to respect user override but for safety:
            $newPath = preg_replace('/\.[^.]+$/', '_' . time() . '.webp', $abs);
        }

        if (!imagewebp($img, $newPath, 80)) {
            imagedestroy($img);
            json_out(false, ['error' => 'Conversion failed during write.']);
        }

        imagedestroy($img);
        
        $relNewPath = trim(str_replace('\\', '/', substr($newPath, strlen(themes_root()))), '/');
        json_out(true, ['new_path' => $relNewPath]);
    }

    case 'upload': {
        $path = norm_rel($_POST['path'] ?? '');
        if (!isset($_FILES['file'])) {
            json_out(false, ['error' => 'No file provided']);
        }

        $f = $_FILES['file'];
        if (!isset($f['error']) || $f['error'] !== UPLOAD_ERR_OK) {
            json_out(false, ['error' => 'Upload error']);
        }

        $name = safe_name($f['name'] ?? '');
        if ($name === false) {
            json_out(false, ['error' => 'Invalid filename']);
        }

        if (!ext_allowed($name, $ALLOWED_EXT)) {
            json_out(false, ['error' => 'File type not allowed']);
        }

        if (($f['size'] ?? 0) > $MAX_FILE_BYTES) {
            json_out(false, ['error' => 'File too large']);
        }

        $parentAbs = resolve_parent_dir($path);
        if ($parentAbs === false) {
            json_out(false, ['error' => 'Invalid target path']);
        }

        $target = $parentAbs . DIRECTORY_SEPARATOR . $name;
        $root = themes_root();
        if (!is_under_root($target, $root)) {
            json_out(false, ['error' => 'Invalid target']);
        }

        if (file_exists($target)) {
            json_out(false, ['error' => 'Target already exists']);
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
        // --- Content Hardening: finfo_file() Strict MIME Verification & Polyglot Prevention ---
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            
            $mime_map = [
                'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 
                'png' => 'image/png', 'gif' => 'image/gif', 
                'webp' => 'image/webp', 'svg' => 'image/svg+xml'
            ];
            
            // Eğer yüklenen dosya görsel uzantılıysa ama MIME tipi farklıysa (text/plain SVG hariç)
            if (isset($mime_map[$ext]) && $mime_type !== $mime_map[$ext] && !($ext === 'svg' && $mime_type === 'text/plain')) {
                json_out(false, ['error' => 'MIME Spoofing Detected: Dosya imzası uzantıyla uyuşmuyor!']);
            }
            
            // Polyglot Attack Koruması: Görsel dosyasının içine gizlenmiş PHP veya XSS payloadlarını engelle
            if (isset($mime_map[$ext])) {
                $content = file_get_contents($f['tmp_name']);
                if (stripos($content, '<?php') !== false || stripos($content, '<script') !== false) {
                    json_out(false, ['error' => 'Polyglot Attack Detected: Görsel dosyası içine gizlenmiş zararlı kod tespit edildi!']);
                }
            }
        } else {
            // Finfo kapalı sunucular için fallback (eski yöntem)
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                if (function_exists('getimagesize') && @getimagesize($f['tmp_name']) === false) {
                    json_out(false, ['error' => 'Dosya içeriği görsel değil (Spoofing).']);
                }
            } elseif ($ext === 'svg') {
                $content = file_get_contents($f['tmp_name']);
                if (stripos($content, '<svg') === false || stripos($content, '<?php') !== false) {
                    json_out(false, ['error' => 'Geçersiz veya zararlı SVG içeriği.']);
                }
            }
        }
        $is_image_convertible = in_array($ext, ['jpg', 'jpeg', 'png']) && function_exists('imagewebp');

        if ($is_image_convertible) {
            $img = null;
            if ($ext === 'jpg' || $ext === 'jpeg') {
                $img = @imagecreatefromjpeg($f['tmp_name']);
            } elseif ($ext === 'png') {
                $img = @imagecreatefrompng($f['tmp_name']);
                if ($img) {
                    imagepalettetotruecolor($img);
                    imagealphablending($img, true);
                    imagesavealpha($img, true);
                }
            }

            if ($img) {
                $width = imagesx($img);
                $height = imagesy($img);

                if ($width > 1920) {
                    $new_width = 1920;
                    $new_height = (int) floor($height * ($new_width / $width));
                    $tmp_img = imagecreatetruecolor($new_width, $new_height);
                    if ($ext === 'png') {
                        imagealphablending($tmp_img, false);
                        imagesavealpha($tmp_img, true);
                        $transparent = imagecolorallocatealpha($tmp_img, 255, 255, 255, 127);
                        imagefilledrectangle($tmp_img, 0, 0, $new_width, $new_height, $transparent);
                    }
                    imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    imagedestroy($img);
                    $img = $tmp_img;
                }

                $target = preg_replace('/\.[^.]+$/', '.webp', $target);
                if (file_exists($target)) {
                    $target = preg_replace('/\.webp$/', '_' . time() . '.webp', $target);
                }

                if (!imagewebp($img, $target, 80)) {
                    imagedestroy($img);
                    json_out(false, ['error' => 'Conversion failed during write']);
                }
                imagedestroy($img);
                
                if (class_exists('PurifyVolt')) PurifyVolt::flushHtml();
                json_out(true);
            }
        }

        // Fallback for non-images or if img read failed
        if (!@move_uploaded_file($f['tmp_name'], $target)) {
            json_out(false, ['error' => 'Failed to move upload']);
        }

        if (class_exists('PurifyVolt')) PurifyVolt::flushHtml();
        json_out(true);
    }

    default:
        json_out(false, ['error' => 'Unknown action']);
}
