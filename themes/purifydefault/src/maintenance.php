<?php
defined('DIR_ACCESS') or exit;
require_once __DIR__ . '/../../../inc/kernel.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= p_lang('maintenance_mode', 'Maintenance') ?> | <?= htmlspecialchars($cfg_name) ?></title>
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .maintenance-box { border: 1px solid #e2e8f0; padding: 40px; background: #fff; max-width: 550px; width: 90%; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        h1 { margin: 0 0 24px 0; font-size: 22px; color: #6366f1; border-bottom: 2px solid #e0e7ff; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; }
        .content { font-size: 15px; line-height: 1.7; color: #475569; }
        .footer { color: #94a3b8; margin-top: 30px; font-size: 11px; border-top: 1px solid #f1f5f9; padding-top: 15px; text-transform: uppercase; letter-spacing: 0.05em; }
        .dot { display: inline-block; width: 8px; height: 8px; background: #6366f1; border-radius: 50%; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
    </style>
</head>
<body>
    <div class="maintenance-box">
        <h1><span class="dot"></span> <?= p_lang('maintenance_title', 'SYSTEM MAINTENANCE') ?></h1>
        <div class="content">
            <p><strong><?= p_lang('maintenance_subtitle', 'Scheduled Update in Progress') ?></strong></p>
            <p><?= htmlspecialchars($maintenance_msg) ?></p>
            <p><?= p_lang('maintenance_description', 'We are currently updating our systems to provide you with a better experience. We will be back online shortly. Thank you for your patience.') ?></p>
        </div>
        <div class="footer">&copy; <?= date('Y') ?> <?= htmlspecialchars($cfg_name) ?> | <?= p_lang('status_updating', 'Status: Updating') ?></div>
    </div>
</body>
</html>
