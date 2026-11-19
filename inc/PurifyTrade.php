<?php
class PurifyTrade {
    
    private static $data_dir = __DIR__ . '/data/ecommerce';
    private static $vault_dir = __DIR__ . '/data/ecommerce/vault';
    private static $log_file = __DIR__ . '/data/ecommerce/logs/trade_';
    
    public static function boot() {
        if (!is_dir(self::$data_dir)) @mkdir(self::$data_dir, 0755, true);
        if (!is_dir(self::$vault_dir)) @mkdir(self::$vault_dir, 0755, true);
        if (!is_dir(self::$data_dir . '/orders')) @mkdir(self::$data_dir . '/orders', 0755, true);
        if (!is_dir(self::$data_dir . '/logs')) @mkdir(self::$data_dir . '/logs', 0755, true);
    }
    
    public static function log($type, $message, $data = []) {
        $entry = [
            'time' => date('Y-m-d H:i:s'),
            'type' => $type,
            'message' => $message,
            'data' => $data
        ];
        $logFile = self::$log_file . date('Y-m') . '.log';
        @file_put_contents($logFile, json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);
    }

    public static function isValidOrderId($order_id) {
        return is_string($order_id) && preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{4,63}$/', $order_id) === 1;
    }

    /**
     * Gateway Factory: Dynamically loads the payment driver with validation.
     */
    public static function getGateway($method) {
        $method_clean = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$method);
        if (empty($method_clean)) return null;
        
        $method_lower = strtolower($method_clean);
        $method_class = ucfirst($method_lower);
        if ($method_lower === 'paytr') $method_class = 'PayTR';
        
        $file = __DIR__ . "/gateways/{$method_class}.php";
        
        if (file_exists($file)) {
            require_once $file;
            if (class_exists($method_class)) {
                self::log('INFO', 'Gateway loaded', ['method' => $method_class]);
                return new $method_class();
            }
        }
        
        self::log('ERROR', 'Gateway file not found', ['file' => $file]);
        return null;
    }

    public static function calculate($price, $tax_rate, $extra_price = 0) {
        $tax_rate = max(0.0, (float)$tax_rate);
        $net = max(0.0, (float)$price) + max(0.0, (float)$extra_price);
        $tax_amount = ($net * $tax_rate) / 100;
        $gross = $net + $tax_amount;
        
        return [
            'net' => round($net, 2),
            'tax' => round($tax_amount, 2),
            'gross' => round($gross, 2),
            'rate' => $tax_rate,
            'extra' => max(0.0, (float)$extra_price)
        ];
    }

    public static function createOrder($order_data) {
        self::boot();
        
        // Validate required fields - Support both old and new format
        $email = $order_data['email'] ?? ($order_data['customer']['email'] ?? '');
        $amount = $order_data['amount'] ?? ($order_data['total_gross'] ?? 0);
        
        if (empty($email)) {
            self::log('ERROR', 'Order validation failed - email missing', ['data' => $order_data]);
            return ['status' => 'error', 'msg' => 'Customer email is required.'];
        }
        
        // Zero-amount orders are valid (free/voucher), but must be numeric
        if (!is_numeric($amount)) {
            self::log('ERROR', 'Order validation failed - amount invalid', ['amount' => $amount]);
            return ['status' => 'error', 'msg' => 'Invalid order amount.'];
        }
        

        $order_id = 'PRFY' . date('Ymd') . strtoupper(bin2hex(random_bytes(6)));
        $order_data['id'] = $order_id;
        $order_data['date'] = date('Y-m-d H:i:s');
        $order_data['status'] = $order_data['status'] ?? 'pending';
        
        // Micro-File Architecture: 1 Order = 1 File (Zero-Lock Concurrency)
        $order_file = self::$data_dir . '/orders/' . $order_id . '.json';
        
        if (file_put_contents($order_file, json_encode($order_data, JSON_PRETTY_PRINT), LOCK_EX) !== false) {
            self::log('SUCCESS', 'Order created', ['order_id' => $order_id, 'amount' => $amount]);

            // 1. Enrich items before notification
            self::enrichOrderItems($order_data);

            // 2. Trigger Email Notification (Pending State)
            require_once __DIR__ . '/PurifyMailer.php';
            PurifyMailer::sendOrderNotification($order_data);

            return ['status' => 'success', 'order_id' => $order_id];
        } else {
            self::log('ERROR', 'Cannot save order file', ['file' => $order_file]);
            return ['status' => 'error', 'msg' => 'System error: Cannot save order'];
        }
    }

    public static function getOrder($order_id) {
        if (!self::isValidOrderId($order_id)) {
            return null;
        }

        $orders_dir = realpath(self::$data_dir . '/orders');
        if ($orders_dir === false) {
            return null;
        }

        $single_file = $orders_dir . DIRECTORY_SEPARATOR . $order_id . '.json';
        $real_single = realpath($single_file);
        if ($real_single !== false && strpos($real_single, $orders_dir . DIRECTORY_SEPARATOR) === 0 && is_file($real_single)) {
            return json_decode(file_get_contents($real_single), true) ?: null;
        }

        // Legacy Support OID Resolution
        if (strlen($order_id) === 19 && strpos($order_id, '-') !== false) {
            $date_part = substr($order_id, 5, 8);
        } else {
            $date_part = substr($order_id, 4, 8);
        }

        if (!preg_match('/^\d{8}$/', $date_part)) return null;
        $y_m = substr($date_part, 0, 4) . '-' . substr($date_part, 4, 2);
        $month_file = $orders_dir . DIRECTORY_SEPARATOR . $y_m . '.json';
        $real_month = realpath($month_file);
        
        if ($real_month === false || strpos($real_month, $orders_dir . DIRECTORY_SEPARATOR) !== 0 || !is_file($real_month)) return null;
        
        $content = @file_get_contents($real_month);
        if (!$content) return null;
        
        $orders = json_decode($content, true) ?: [];
        
        // Case-Insensitive Canonical Lookup
        foreach ($orders as $key => $data) {
            if (strcasecmp($key, $order_id) === 0) {
                return $data;
            }
        }
        
        return null;
    }

    public static function updateOrderStatus($order_id, $status) {
        if (!self::isValidOrderId($order_id)) {
            self::log('ERROR', 'Invalid order ID for status update', ['order_id' => $order_id]);
            return false;
        }

        $order = self::getOrder($order_id);
        if (!$order) {
            self::log('ERROR', 'Order not found for status update', ['order_id' => $order_id]);
            return false;
        }
        
        // Valid status transitions
        $valid_statuses = ['pending', 'processing', 'paid', 'failed', 'refunded', 'cancelled'];
        if (!in_array($status, $valid_statuses, true)) {
            self::log('ERROR', 'Invalid status', ['order_id' => $order_id, 'status' => $status]);
            return false;
        }
        
        $orders_dir = realpath(self::$data_dir . '/orders');
        if ($orders_dir === false) {
            return false;
        }

        // Determine file path for legacy fallback
        if (strlen($order_id) === 19 && strpos($order_id, '-') !== false) {
            $date_part = substr($order_id, 5, 8);
        } else {
            $date_part = substr($order_id, 4, 8);
        }

        $y_m = preg_match('/^\d{8}$/', $date_part) ? (substr($date_part, 0, 4) . '-' . substr($date_part, 4, 2)) : '';
        $month_file = $y_m ? ($orders_dir . DIRECTORY_SEPARATOR . $y_m . '.json') : '';
        $single_file = $orders_dir . DIRECTORY_SEPARATOR . $order_id . '.json';
        
        // Micro-File Architecture Update
        if (file_exists($single_file)) {
            $fp = fopen($single_file, 'c+');
            if ($fp && flock($fp, LOCK_EX)) {
                $content = '';
                rewind($fp);
                while (!feof($fp)) $content .= fread($fp, 8192);
                $order_data = json_decode($content, true) ?: [];
                
                $old_status = $order_data['status'] ?? '';
                $order_data['status'] = $status;
                
                if ($status === 'paid' && $old_status !== 'paid') {
                    $order_data['paid_at'] = date('Y-m-d H:i:s');
                    self::fulfill($order_data);
                }
                
                $order_data['updated_at'] = date('Y-m-d H:i:s');
                
                rewind($fp);
                ftruncate($fp, 0);
                fwrite($fp, json_encode($order_data, JSON_PRETTY_PRINT));
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);
                
                self::log('SUCCESS', 'Order status updated', [
                    'order_id' => $order_id, 
                    'old_status' => $old_status, 
                    'new_status' => $status
                ]);
                return true;
            }
            return false;
        }

        if (!file_exists($month_file)) {
            self::log('ERROR', 'Order file not found', ['file' => $month_file]);
            return false;
        }
        
        // Atomic update with locking
        $fp = fopen($month_file, 'c+');
        if (!$fp) {
            self::log('ERROR', 'Cannot open order file for update', ['file' => $month_file]);
            return false;
        }
        
        if (flock($fp, LOCK_EX)) {
            $content = '';
            rewind($fp);
            while (!feof($fp)) {
                $content .= fread($fp, 8192);
            }
            
            $orders = json_decode($content, true) ?: [];
            
            // Canonical OID Resolution (Case-Insensitive)
            $canonical_oid = null;
            foreach ($orders as $key => $data) {
                if (strcasecmp($key, $order_id) === 0) {
                    $canonical_oid = $key;
                    break;
                }
            }

            if (!$canonical_oid || !isset($orders[$canonical_oid])) {
                flock($fp, LOCK_UN);
                fclose($fp);
                self::log('ERROR', 'Order not found in file (canonical fail)', ['order_id' => $order_id]);
                return false;
            }
            
            $old_status = $orders[$canonical_oid]['status'];
            $orders[$canonical_oid]['status'] = $status;
            
            if ($status === 'paid' && $old_status !== 'paid') {
                $orders[$canonical_oid]['paid_at'] = date('Y-m-d H:i:s');
                self::fulfill($orders[$canonical_oid]);
            }
            
            $orders[$canonical_oid]['updated_at'] = date('Y-m-d H:i:s');
            
            rewind($fp);
            ftruncate($fp, 0);
            fwrite($fp, json_encode($orders, JSON_PRETTY_PRINT));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            
            self::log('SUCCESS', 'Order status updated', [
                'order_id' => $order_id, 
                'old_status' => $old_status, 
                'new_status' => $status
            ]);
            return true;
        } else {
            fclose($fp);
            self::log('ERROR', 'Cannot lock order file for update', ['file' => $month_file]);
            return false;
        }
    }

    private static function fulfill($order) {
        $order_id = $order['id'] ?? 'unknown';
        self::log('INFO', 'Order fulfillment triggered', ['order_id' => $order_id]);
        
        // 1. Ensure enrichment (safety first)
        self::enrichOrderItems($order);

        // 2. Automated E-mail Dispatch
        require_once __DIR__ . '/PurifyMailer.php';
        $mail_sent = PurifyMailer::sendOrderNotification($order);
        
        if ($mail_sent) {
            self::log('SUCCESS', 'Notification email dispatched', ['order_id' => $order_id]);
        } else {
            self::log('WARNING', 'Notification email failed to dispatch', ['order_id' => $order_id]);
        }

        // 3. Hook for custom fulfillment logic
        $fulfillment_file = self::$data_dir . '/fulfillment_hooks.php';
        if (file_exists($fulfillment_file)) {
            include_once $fulfillment_file;
            if (function_exists('custom_fulfill_order')) {
                custom_fulfill_order($order);
            }
        }
    }

    /**
     * Enrich order items with master product data (type, files, etc)
     */
    public static function enrichOrderItems(&$order) {
        $products_file = __DIR__ . '/data/ecommerce/products.json';
        if (file_exists($products_file)) {
            $products = json_decode(file_get_contents($products_file), true) ?: [];
            
            // Build a slug-to-item map for fallback
            $slug_map = [];
            foreach ($products as $p) {
                if (isset($p['slug'])) $slug_map[$p['slug']] = $p;
            }

            foreach ($order['items'] as &$item) {
                $pid = $item['id'] ?? '';
                $slug = $item['slug'] ?? '';
                
                $found = null;
                if ($pid && isset($products[$pid])) {
                    $found = $products[$pid];
                } elseif ($slug && isset($slug_map[$slug])) {
                    $found = $slug_map[$slug];
                }

                if ($found) {
                    $item['type'] = $found['type'] ?? 'physical';
                    $item['file'] = $found['file'] ?? '';
                }
            }
        }
    }
}
?>
