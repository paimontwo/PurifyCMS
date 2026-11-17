<?php
defined('DIR_ACCESS') or exit;

class PurifyKernel
{
    private static $_mt = false;
    private static $_lang_cache = [];
    private static $_active_lang = 'en';

    public static function boot()
    {
        global $cfg_admin_ips, $cfg_admin_path;
        
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $admin_p = $cfg_admin_path ?? 'admin';
        $request_uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $request_path = trim($request_uri, '/');
        $is_admin = ($request_path === $admin_p || strpos($request_path, $admin_p . '/') === 0 || strpos($_SERVER['REQUEST_URI'] ?? '', '/' . $admin_p) !== false);

        if ($is_admin && !empty($cfg_admin_ips)) {
            global $cfg_trust_proxies;
            $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
            
            if (!empty($cfg_trust_proxies)) {
                $proxy_ips = is_string($cfg_trust_proxies) && $cfg_trust_proxies !== '1' ? array_map('trim', explode(',', $cfg_trust_proxies)) : [];
                $is_trusted_proxy = empty($proxy_ips) || in_array($_SERVER['REMOTE_ADDR'] ?? '', $proxy_ips);
                
                if ($is_trusted_proxy) {
                    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
                        $client_ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
                    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                        foreach ($ips as $ip) {
                            $ip = trim($ip);
                            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                                $client_ip = $ip;
                                break;
                            }
                        }
                    }
                }
            }

            $allowed_ips = array_map('trim', explode(',', $cfg_admin_ips));
            if (!in_array($client_ip, $allowed_ips)) {
                header('HTTP/1.1 403 Forbidden');
                die('Unauthorized Access: IP restricted.');
            }
        }

        if (isset($_GET['p_lang']) && in_array($_GET['p_lang'], ['tr', 'en'])) {
            $_SESSION['p_admin_lang'] = $_GET['p_lang'];
            $clean_url = strtok($_SERVER['REQUEST_URI'], '?');
            header('Location: ' . $clean_url);
            exit;
        }

        if (file_exists(__DIR__ . '/../.maintenance')) {
            self::$_mt = true;
        }

        if (!defined('LIC_STATUS')) {
            define('LIC_STATUS', 'valid');
        }
    }

    public static function get_active_lang() {
        if (session_status() !== PHP_SESSION_NONE && isset($_SESSION['p_admin_lang'])) {
            return $_SESSION['p_admin_lang'];
        }
        global $cfg_admin_lang;
        return $cfg_admin_lang ?? self::$_active_lang;
    }

    public static function p_lang($key, $default = '') {
        if (empty(self::$_lang_cache)) {
            $lang = self::get_active_lang();
            $_cached = PurifyVolt::get('lang_' . $lang);
            if ($_cached !== null) {
                self::$_lang_cache = $_cached;
            } else {
                $file = __DIR__ . '/data/locales/admin_' . $lang . '.json';
                if (file_exists($file)) {
                    self::$_lang_cache = json_decode(file_get_contents($file), true) ?: [];
                    PurifyVolt::set('lang_' . $lang, self::$_lang_cache, 3600);
                }
            }
        }
        
        if ($key && !isset(self::$_lang_cache[$key])) {
            self::$_lang_cache[$key] = $default;
            $lang = self::get_active_lang();
            $file = __DIR__ . '/data/locales/admin_' . $lang . '.json';
            
            if (is_writable($file) || !file_exists($file)) {
                @file_put_contents($file, json_encode(self::$_lang_cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
                PurifyVolt::set('lang_' . $lang, self::$_lang_cache, 3600);
            }
        }

        return self::$_lang_cache[$key] ?? $default;
    }
}

class PurifyVolt
{
    private static $_cache = [];
    private static $_prefix = null;
    
    private static function _gP() {
        if (self::$_prefix !== null) return self::$_prefix;
        self::$_prefix = 'pv_' . substr(md5(__DIR__), 0, 8) . '_';
        return self::$_prefix;
    }
    
    public static function flushHtml() {
        self::$_cache = [];
        clearstatcache();

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate(__DIR__ . '/config.php', true);
        }
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        if (function_exists('apcu_clear_cache')) {
            @apcu_clear_cache();
        }

        $dir = __DIR__ . '/data/cache';
        if (!is_dir($dir)) return;
        
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                @rmdir($file->getRealPath());
            } else {
                if (strpos($file->getFilename(), '.') === 0) continue;
                @unlink($file->getRealPath());
            }
        }
    }

    public static function getCacheStatus() {
        $dir = __DIR__ . '/data/cache';
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) return ['ok' => false, 'msg' => 'Directory missing and not creatable'];
        }
        if (!is_writable($dir)) return ['ok' => false, 'msg' => 'Directory not writable'];
        
        $count = count(glob($dir . '/*.html'));
        return ['ok' => true, 'count' => $count, 'path' => realpath($dir)];
    }

    public static function get($key) {
        if (function_exists('apcu_fetch')) {
            $val = apcu_fetch(self::_gP() . $key, $success);
            if ($success) return $val;
        }
        return self::$_cache[$key] ?? null;
    }
    
    public static function set($key, $val, $ttl = 3600) {
        if (function_exists('apcu_store')) {
            apcu_store('pv_' . $key, $val, $ttl);
        }
        self::$_cache[$key] = $val;
    }
}

class PurifyStats
{
    public static function log($path)
    {
        $dir = __DIR__ . '/data/stats';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        
        $today = date('Y-m-d');
        $sess_id = md5(($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? '') . $today);
        $path = (string)$path;
        if ($path === '') $path = 'index';
        
        $log_file = $dir . '/hits.log';
        $log_entry = json_encode(['t' => time(), 'p' => $path, 's' => $sess_id]) . PHP_EOL;
        @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    public static function compile()
    {
        $dir = __DIR__ . '/data/stats';
        $log_file = $dir . '/hits.log';
        if (!file_exists($log_file)) return;

        $work_file = $dir . '/hits_' . time() . '_' . rand(100,999) . '.tmp';
        if (!@rename($log_file, $work_file)) return;

        $month_file = $dir . '/' . date('Y-m') . '.json';
        $data = file_exists($month_file) ? (json_decode(file_get_contents($month_file), true) ?: []) : [];

        $handle = @fopen($work_file, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $hit = @json_decode(trim($line), true);
                if (!$hit) continue;
                
                $day = date('Y-m-d', $hit['t']);
                if (!isset($data[$day])) {
                    $data[$day] = ['hits' => 0, 'sessions' => [], 'pages' => []];
                }
                
                $data[$day]['hits']++;
                
                if (!in_array($hit['s'], $data[$day]['sessions'])) {
                    $data[$day]['sessions'][] = $hit['s'];
                }
                
                if (!isset($data[$day]['pages'][$hit['p']])) {
                    $data[$day]['pages'][$hit['p']] = 0;
                }
                $data[$day]['pages'][$hit['p']]++;
            }
            fclose($handle);
        }
        
        @file_put_contents($month_file, json_encode($data), LOCK_EX);
        @unlink($work_file);
    }
}

function p_lang($key, $default = '') {
    return PurifyKernel::p_lang($key, $default);
}
