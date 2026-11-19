<?php
/**
 * PayTR Gateway Module
 * Step 1: Token Acquisition via Secure API
 * Step 2: Callback Verification with logging
 */
require_once __DIR__ . '/BaseGateway.php';

class PayTR extends PurifyPaymentGateway {
    
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/../data/ecommerce/logs/paytr_' . date('Y-m') . '.log';
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }
    
    private function log($type, $message, $data = []) {
        $entry = [
            'time' => date('Y-m-d H:i:s'),
            'type' => $type,
            'message' => $message,
            'data' => $data
        ];
        file_put_contents($this->logFile, json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);
    }

    public function getToken($order_id, $amount, $customer, $items) {
        global $cfg_paytr_m_id, $cfg_paytr_m_key, $cfg_paytr_m_salt, $cfg_mainurl, $cfg_ecommerce_currency, $cfg_paytr_test;
        
        // Validation Layer
        if (empty($cfg_paytr_m_id) || empty($cfg_paytr_m_key) || empty($cfg_paytr_m_salt)) {
            $this->log('ERROR', 'PayTR credentials missing');
            return ['status' => 'error', 'msg' => 'PayTR Credentials Missing. Update Settings.'];
        }
        
        if ($amount <= 0) {
            $this->log('ERROR', 'Invalid amount', ['amount' => $amount]);
            return ['status' => 'error', 'msg' => 'Invalid payment amount.'];
        }
        
        if (empty($customer['email'])) {
            $this->log('ERROR', 'Customer email missing', ['order_id' => $order_id]);
            return ['status' => 'error', 'msg' => 'Customer email is required.'];
        }

        // Robust IP Detection (PayTR SDK Standards)
        $user_ip = $this->getClientIP();
        
        // Basket validation
        if (empty($items)) {
            $this->log('ERROR', 'Empty basket', ['order_id' => $order_id]);
            return ['status' => 'error', 'msg' => 'Basket cannot be empty.'];
        }

        // Absolute Alphanumeric OID for PayTR (Stripping hyphens)
        $merchant_oid = (string)$order_id . 'T' . time(); 
        $email = (string)($customer['email'] ?? 'no-email@localhost.local');
        $payment_amount = (string)(int)round($amount * 100); 
        $currency = (string)($cfg_ecommerce_currency ?? 'TRY');
        $test_mode = (string)($cfg_paytr_test ?? "0");
        
        $basket_items = [];
        foreach ($items as $it) {
            $price = number_format((float)($it['total_gross'] ?? $it['price'] ?? 0), 2, '.', '');
            $basket_items[] = [$it['title'], $price, 1];
        }
        $user_basket = base64_encode(json_encode($basket_items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        
        $no_installment = 0;
        $max_installment = 0;
        $merchant_ok_url = $cfg_mainurl . "/order-track?oid=$order_id&status=success";
        $merchant_fail_url = $cfg_mainurl . "/order-track?oid=$order_id&status=fail";
        $timeout_limit = 30;
        $debug_on = 0;

        $hash_str = (string)$cfg_paytr_m_id . (string)$user_ip . (string)$merchant_oid . (string)$email . (string)$payment_amount . (string)$user_basket . (string)$no_installment . (string)$max_installment . (string)$currency . (string)$test_mode;
        $paytr_token = base64_encode(hash_hmac('sha256', $hash_str . (string)$cfg_paytr_m_salt, (string)$cfg_paytr_m_key, true));

        $post_vals = [
            'merchant_id' => $cfg_paytr_m_id,
            'user_ip' => $user_ip,
            'merchant_oid' => $merchant_oid,
            'email' => $email,
            'payment_amount' => $payment_amount,
            'paytr_token' => $paytr_token,
            'user_basket' => $user_basket,
            'debug_on' => $debug_on,
            'no_installment' => $no_installment,
            'max_installment' => $max_installment,
            'user_name' => (string)(strlen(trim($customer['name'] ?? '')) >= 3 ? $customer['name'] : 'Guest Customer'),
            'user_address' => (string)(strlen(trim($customer['address'] ?? '')) >= 10 ? $customer['address'] : 'Default Checkout Address, Turkey'),
            'user_phone' => (string)(strlen(trim($customer['phone'] ?? '')) >= 10 ? $customer['phone'] : '0000000000'),
            'merchant_ok_url' => (string)$merchant_ok_url,
            'merchant_fail_url' => (string)$merchant_fail_url,
            'timeout_limit' => (int)$timeout_limit,
            'currency' => (string)$currency,
            'test_mode' => (string)$test_mode,
            'lang' => 'tr'
        ];

        $this->log('INFO', 'Token request initiated', [
            'order_id' => $order_id, 
            'merchant_oid' => $merchant_oid,
            'amount' => $payment_amount,
            'user_basket' => $basket_items,
            'user_name' => $post_vals['user_name'],
            'user_ip' => $user_ip
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_vals));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PurifyCMS/' . (defined('PURIFY_VERSION') ? PURIFY_VERSION : '4.2.2'));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        if ($curlError) {
            $this->log('ERROR', 'CURL error', ['error' => $curlError, 'order_id' => $order_id]);
            curl_close($ch);
            return ['status' => 'error', 'msg' => 'PayTR Connection Error: ' . $curlError];
        }
        
        curl_close($ch);

        $res = json_decode($result, true);
        
        if ($httpCode !== 200) {
            $this->log('ERROR', 'HTTP error', ['code' => $httpCode, 'response' => $result, 'order_id' => $order_id]);
            return ['status' => 'error', 'msg' => 'PayTR API HTTP Error: ' . $httpCode];
        }
        
        if (isset($res['status']) && $res['status'] === 'success' && !empty($res['token'])) {
            $this->log('SUCCESS', 'Token acquired', ['order_id' => $order_id, 'token' => substr($res['token'], 0, 20) . '...']);
            return [
                'status' => 'success', 
                'token' => $res['token'],
                'merchant_oid' => $merchant_oid
            ];
        } else {
            $reason = $res['reason'] ?? 'API_REJECTION';
            $this->log('ERROR', 'Token rejected', ['reason' => $reason, 'order_id' => $order_id, 'response' => $res]);
            return ['status' => 'error', 'msg' => 'PAYTR_TOKEN_ERROR: ' . $reason];
        }
    }

    public function verifyCallback($post_data) {
        global $cfg_paytr_m_key, $cfg_paytr_m_salt;
        
        if (empty($post_data['merchant_oid']) || empty($post_data['status']) || empty($post_data['hash'])) {
            $this->log('ERROR', 'Callback missing required fields', ['data' => array_keys($post_data)]);
            return false;
        }
        
        $merchant_oid = $post_data['merchant_oid'];
        $status = $post_data['status'];
        $total_amount = $post_data['total_amount'] ?? 0;
        $incoming_hash = $post_data['hash'];

        // Doc-Compliant Hash (Reverted to OID+SALT+STATUS+AMT after live log verification)
        $hash_str = $merchant_oid . $cfg_paytr_m_salt . $status . $total_amount;
        $calculated_hash = base64_encode(hash_hmac('sha256', $hash_str, $cfg_paytr_m_key, true));
        
        $isValid = hash_equals($calculated_hash, $incoming_hash);
        
        $this->log($isValid ? 'SUCCESS' : 'ERROR', 'Callback verification', [
            'merchant_oid' => $merchant_oid,
            'status' => $status,
            'total_amount' => $total_amount,
            'valid' => $isValid
        ]);

        return $isValid;
    }
    
    private function getClientIP() {
        global $cfg_trust_proxies;
        $remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';

        if (!empty($cfg_trust_proxies) && is_string($cfg_trust_proxies) && $cfg_trust_proxies !== '1') {
            $proxy_ips = array_filter(array_map('trim', explode(',', $cfg_trust_proxies)));
            if (!empty($proxy_ips) && in_array($remote_addr, $proxy_ips, true)) {
                $forwarded_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR'];
                foreach ($forwarded_keys as $key) {
                    if (!empty($_SERVER[$key])) {
                        $candidates = ($key === 'HTTP_X_FORWARDED_FOR') ? explode(',', $_SERVER[$key]) : [$_SERVER[$key]];
                        foreach ($candidates as $candidate) {
                            $ip = trim($candidate);
                            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                                return $ip;
                            }
                        }
                    }
                }
            }
        }

        if (filter_var($remote_addr, FILTER_VALIDATE_IP)) {
            return $remote_addr;
        }
        return '127.0.0.1';
    }
}
?>
