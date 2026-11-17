<?php
/**
 * PurifyCMS Enterprise - Mailer Engine (v3.6.0)
 * High-performance, HTML-rich order notifications and digital delivery.
 */
defined('DIR_ACCESS') or exit;

require_once __DIR__ . '/smtp.php';

class PurifyMailer {
    
    private static function getSMTP() {
        global $cfg_smtp_host, $cfg_smtp_port, $cfg_smtp_user, $cfg_smtp_pass, $cfg_smtp_secure;
        
        if (empty($cfg_smtp_host)) return null;
        
        return new SimpleSMTP(
            $cfg_smtp_host,
            $cfg_smtp_port,
            $cfg_smtp_user,
            $cfg_smtp_pass,
            $cfg_smtp_secure
        );
    }

    public static function test($config) {
        $smtp = new SimpleSMTP(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['pass'],
            $config['secure']
        );
        
        return $smtp->send(
            $config['to'],
            "PurifyCMS SMTP Test",
            "Bu bir test e-postasıdır. Eğer bunu görüyorsanız SMTP ayarlarınız başarıyla yapılandırılmış demektir.",
            $config['user'], // Always use authenticated user as fromEmail
            "PurifyCMS Test"
        );
    }

    public static function sendOrderNotification($order) {
        global $cfg_name, $cfg_mainurl, $cfg_contact_email, $cfg_smtp_user;
        
        $smtp = self::getSMTP();
        if (!$smtp) {
            self::log('WARNING', 'SMTP not configured, skipping order notification', ['oid' => $order['id']]);
            return false;
        }

        $customer_email = $order['customer']['email'] ?? '';
        if (empty($customer_email)) return false;

        $status = $order['status'] ?? 'pending';
        $is_paid = ($status === 'paid');
        
        $subject = ($is_paid ? "Sipariş Onayı" : "Sipariş Alındı") . ": #" . $order['id'];
        $html = self::generateOrderTemplate($order);

        $from_email = !empty($cfg_smtp_user) ? $cfg_smtp_user : $cfg_contact_email;

        try {
            return $smtp->send(
                $customer_email,
                $subject,
                $html,
                $from_email,
                $cfg_name
            );
        } catch (Exception $e) {
            self::log('ERROR', 'SMTP Send Failed', ['oid' => $order['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    private static function generateOrderTemplate($order) {
        global $cfg_name, $cfg_mainurl, $cfg_contact_email;
        
        $status = $order['status'] ?? 'pending';
        $is_paid = ($status === 'paid');
        
        $items_html = '';
        foreach ($order['items'] as $item) {
            $download_btn = '';
            if (isset($item['type']) && $item['type'] === 'digital') {
                if ($is_paid) {
                    $download_url = $cfg_mainurl . "/api/ecommerce/download?oid=" . $order['id'] . "&pid=" . $item['id'];
                    $download_btn = '<div style="margin-top: 10px;"><a href="'.$download_url.'" style="display: inline-block; background: #6366f1; color: #ffffff; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 800; box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4);">Dosyayı İndir</a></div>';
                } else {
                    $download_btn = '<div style="margin-top: 10px; display: inline-block; background: #f1f5f9; color: #64748b; padding: 8px 16px; border-radius: 10px; font-size: 11px; font-weight: 700; border: 1px dashed #cbd5e1;">Dijital Ürün (Ödeme sonrası indirilebilir)</div>';
                }
            }

            $items_html .= '
            <tr>
                <td style="padding: 15px 0; border-bottom: 1px solid #f1f5f9;">
                    <div style="font-weight: 800; color: #0f172a; margin-bottom: 2px;">'.$item['title'].'</div>
                    <div style="font-size: 12px; color: #94a3b8; font-weight: 600;">#'.$item['id'].' &bull; Miktar: 1</div>
                    '.$download_btn.'
                </td>
                <td style="padding: 15px 0; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 800; color: #6366f1;">
                    '.$item['total_gross'].' '.$order['currency'].'
                </td>
            </tr>';
        }

        $title = $is_paid ? "Siparişiniz Onaylandı!" : "Siparişiniz Alındı!";
        $submsg = $is_paid ? "Ödemeniz başarıyla doğrulandı. Ürünlerinizi aşağıda görebilir ve indirebilirsiniz." : "Siparişiniz sisteme kaydedildi. Ödeme onayı sonrası dijital ürünleriniz aktif edilecektir.";

        // External Templates
        $template_file = __DIR__ . '/data/emails/order_notification.html';
        $html_template = '';
        
        // Auto-Generate if missing
        if (!file_exists($template_file)) {
            $default_html = '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{TITLE}}</title>
    <style>
        body { font-family: \'Inter\', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #334155; margin: 0; padding: 0; background-color: #f8fafc; }
        .container { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
        .card { background: #ffffff; border-radius: 28px; padding: 45px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .header { text-align: center; margin-bottom: 35px; }
        .footer { text-align: center; margin-top: 35px; font-size: 12px; color: #94a3b8; font-weight: 500; }
        .items-table { width: 100%; border-collapse: collapse; }
        .summary-row { margin-top: 30px; border-top: 2px solid #f1f5f9; padding-top: 20px; }
        .btn-track { display: inline-block; background: #0f172a; color: #ffffff; padding: 16px 32px; border-radius: 14px; text-decoration: none; font-weight: 800; font-size: 15px; margin-top: 40px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div style="width: 56px; height: 56px; background: #eff6ff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                    <span style="color: #2563eb; font-size: 28px; line-height: 56px;">✉</span>
                </div>
                <h2 style="margin: 0; color: #0f172a; letter-spacing: -0.04em; font-size: 26px; font-weight: 800;">{{TITLE}}</h2>
                <p style="color: #64748b; margin-top: 10px; font-size: 16px; font-weight: 500;">{{SUBMSG}}</p>
            </div>
            <div style="background: #f8fafc; border-radius: 20px; padding: 24px; margin-bottom: 35px; border: 1px solid #f1f5f9;">
                <table width="100%">
                    <tr>
                        <td>
                            <span style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; letter-spacing: 0.05em;">Sipariş Numarası</span><br>
                            <strong style="color: #0f172a; font-size: 15px;">#{{ORDER_ID}}</strong>
                        </td>
                        <td style="text-align: right;">
                            <span style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800; letter-spacing: 0.05em;">İşlem Tarihi</span><br>
                            <strong style="color: #0f172a; font-size: 15px;">{{ORDER_DATE}}</strong>
                        </td>
                    </tr>
                </table>
            </div>
            <table class="items-table">{{ITEMS_HTML}}</table>
            <div class="summary-row">
                <table width="100%">
                    <tr>
                        <td style="font-weight: 700; color: #64748b; font-size: 16px;">Genel Toplam</td>
                        <td style="text-align: right; font-size: 28px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em;">{{TOTAL}} {{CURRENCY}}</td>
                    </tr>
                </table>
            </div>
            <div style="text-align: center;"><a href="{{ORDER_TRACK_URL}}" class="btn-track">Siparişi Takip Et</a></div>
        </div>
        <div class="footer">&copy; {{YEAR}} {{SITE_NAME}}<br>Bu e-posta işlem güvenliğiniz için otomatik olarak oluşturulmuştur.</div>
    </div>
</body>
</html>';
            if (!is_dir(dirname($template_file))) {
                @mkdir(dirname($template_file), 0755, true);
            }
            @file_put_contents($template_file, $default_html);
            $html_template = $default_html;
        } else {
            $html_template = file_get_contents($template_file);
        }

        $placeholders = [
            '{{TITLE}}' => $title,
            '{{SUBMSG}}' => $submsg,
            '{{ITEMS_HTML}}' => $items_html,
            '{{TOTAL}}' => $order['total_gross'],
            '{{CURRENCY}}' => $order['currency'],
            '{{ORDER_ID}}' => $order['id'],
            '{{ORDER_DATE}}' => date('d.m.Y H:i'),
            '{{ORDER_TRACK_URL}}' => $cfg_mainurl . '/order-track?oid=' . $order['id'],
            '{{SITE_NAME}}' => $cfg_name,
            '{{MAIN_URL}}' => $cfg_mainurl,
            '{{YEAR}}' => date('Y')
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $html_template);
    }

    private static function log($lvl, $msg, $ctx = []) {
        $log_file = __DIR__ . '/data/logs/mail_' . date('Y-m') . '.log';
        if (!is_dir(dirname($log_file))) mkdir(dirname($log_file), 0755, true);
        $entry = sprintf("[%s][%s] %s %s\n", date('Y-m-d H:i:s'), $lvl, $msg, json_encode($ctx));
        @file_put_contents($log_file, $entry, FILE_APPEND);
    }
}
