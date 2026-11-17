<?php
define('DIR_ACCESS', true);
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/PurifyTrade.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    exit('Unauthorized access.');
}

$oid = $_GET['oid'] ?? '';
if (!$oid) exit('Order ID required.');

$orders_dir = __DIR__ . '/../inc/data/ecommerce/orders';
$order = null;

$order_files = glob($orders_dir . '/*.json');
foreach ($order_files as $file) {
    $data = json_decode(file_get_contents($file), true) ?: [];
    if (isset($data['id']) && $data['id'] === $oid) {
        $order = $data;
        break;
    } elseif (!isset($data['id']) && isset($data[$oid])) {
        $order = $data[$oid];
        break;
    }
}

if (!$order) exit('Order not found.');

$white_label_name = $cfg_legalname ?: $cfg_name;

$accounting_file = __DIR__ . '/../inc/data/ecommerce/accounting.json';
$accounting_db = file_exists($accounting_file) ? json_decode(file_get_contents($accounting_file), true) : [];
$formal_status = $accounting_db['invoices'][$oid] ?? 'pending';

?>
<!DOCTYPE html>
<html lang="<?= PurifyKernel::get_active_lang() ?>">
<head>
    <meta charset="UTF-8">
    <title><?= p_lang('invoice_header', 'Invoice') ?> - #<?= $order['id'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 40px; color: #1e293b; line-height: 1.5; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #e2e8f0; border-radius: 12px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .brand { font-size: 28px; font-weight: 800; color: #0f172a; }
        .invoice-meta { text-align: right; }
        .invoice-meta h1 { margin: 0; font-size: 24px; color: #64748b; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .section-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.1em; margin-bottom: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .table th { text-align: left; background: #f8fafc; padding: 12px; border-bottom: 2px solid #e2e8f0; font-size: 13px; font-weight: 800; }
        .table td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .totals { margin-left: auto; width: 300px; }
        .total-line { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .total-line.grand { border-bottom: none; font-size: 20px; font-weight: 800; color: #1e293b; padding-top: 20px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .badge-success { background: #dcfce7; color: #166534; }
        @media print { 
            body { padding: 0; } 
            .invoice-box { border: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print" style="max-width: 800px; margin: 0 auto 20px auto; display: flex; justify-content: space-between;">
    <button onclick="window.print()" style="padding: 10px 20px; background: #0f172a; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;"><?= p_lang('print_invoice', 'Print Invoice') ?></button>
    <a href="accounting" style="color: #64748b; text-decoration: none; font-weight: 600;">&larr; <?= p_lang('back_to_accounting', 'Back to Accounting') ?></a>
</div>

<div class="invoice-box">
    <div class="header">
        <div class="brand">
            <?php if (!empty($cfg_logolink)): ?>
                <img src="<?= htmlspecialchars($cfg_logolink) ?>" alt="<?= htmlspecialchars($white_label_name) ?>" style="max-height: 60px; max-width: 250px; object-fit: contain;">
            <?php else: ?>
                <?= htmlspecialchars($white_label_name) ?>
            <?php endif; ?>
        </div>
        <div class="invoice-meta">
            <h1><?= p_lang('invoice_header', 'INVOICE') ?></h1>
            <p style="margin: 5px 0;">#<?= $order['id'] ?></p>
            <p style="margin: 0; font-size: 12px; color: #94a3b8;"><?= date('F d, Y', strtotime($order['date'])) ?></p>
        </div>
    </div>

    <div class="grid">
        <div>
            <div class="section-title"><?= p_lang('invoice_billed_to', 'Billed To') ?></div>
            <p style="font-weight: 800; margin: 0;"><?= $order['customer']['name'] ?></p>
            <p style="margin: 5px 0; color: #64748b;"><?= $order['customer']['email'] ?></p>
            <p style="margin: 0; font-size: 13px;"><?= nl2br(htmlspecialchars($order['customer']['address'])) ?></p>
            <p style="margin: 5px 0; font-size: 13px;"><?= $order['customer']['city'] ?> / <?= $order['customer']['district'] ?> <?= $order['customer']['zip'] ?></p>
            <?php if (!empty($order['customer']['tax_id'])): ?>
                <p style="margin-top: 10px; font-size: 12px; color: #475569;"><strong><?= p_lang('tax_id', 'Tax Registry') ?>:</strong> <?= $order['customer']['tax_id'] ?> (<?= $order['customer']['tax_office'] ?>)</p>
            <?php endif; ?>
        </div>
        <div style="text-align: right;">
            <div class="section-title"><?= p_lang('fiscal_formalization', 'Fiscal Status') ?></div>
            <span class="badge <?= $formal_status === 'formalized' ? 'badge-success' : '' ?>" style="background: <?= $formal_status === 'formalized' ? '#dcfce7' : '#f1f5f9' ?>; color: <?= $formal_status === 'formalized' ? '#166534' : '#94a3b8' ?>;">
                <?= $formal_status === 'formalized' ? p_lang('invoice_fiscal_registered', 'FISCAL REGISTERED (GİB)') : p_lang('invoice_fiscal_pending', 'PENDING FORMALIZATION') ?>
            </span>
            
            <div class="section-title" style="margin-top: 30px;"><?= p_lang('invoice_payment_method', 'Payment Method') ?></div>
            <p style="font-weight: 800; margin: 0; text-transform: uppercase;"><?= $order['payment_method'] ?></p>
            <p style="margin: 5px 0; font-size: 12px; color: #64748b;"><?= p_lang('invoice_status', 'Status') ?>: <?= strtoupper($order['status']) ?></p>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th><?= p_lang('invoice_asset_desc', 'Asset Description') ?></th>
                <th style="text-align: right;"><?= p_lang('invoice_unit_net', 'Unit Net') ?></th>
                <th style="text-align: right;"><?= p_lang('invoice_tax_percent', 'Tax %') ?></th>
                <th style="text-align: right;"><?= p_lang('invoice_gross_price', 'Gross Price') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order['items'] as $item): 
                $tax_r = $item['tax_rate'] ?? 20;
                $gross = $item['price'] * (1 + $tax_r / 100);
            ?>
                <tr>
                    <td>
                        <div style="font-weight: 800;"><?= $item['title'] ?></div>
                        <?php if (!empty($item['selected_options'])): ?>
                            <div style="font-size: 11px; color: #64748b;">
                                <?= implode(', ', array_map(fn($o) => $o['name'], $item['selected_options'])) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;"><?= number_format($item['price'], 2) ?></td>
                    <td style="text-align: right;"><?= $tax_r ?>%</td>
                    <td style="text-align: right; font-weight: 800;"><?= number_format($gross, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="total-line">
            <span style="color: #64748b; font-weight: 600;"><?= p_lang('invoice_net_subtotal', 'Net Subtotal') ?></span>
            <span><?= number_format($order['total_net'], 2) ?> <?= $order['currency'] ?></span>
        </div>
        <div class="total-line">
            <span style="color: #64748b; font-weight: 600;"><?= p_lang('invoice_vat_allocation', 'VAT Allocation') ?></span>
            <span><?= number_format($order['total_tax'], 2) ?> <?= $order['currency'] ?></span>
        </div>
        <div class="total-line grand">
            <span><?= p_lang('invoice_total', 'TOTAL') ?></span>
            <span><?= number_format($order['total_gross'], 2) ?> <?= $order['currency'] ?></span>
        </div>
    </div>

    <div style="margin-top: 80px; padding-top: 20px; border-top: 1px solid #f1f5f9; text-align: center; color: #94a3b8; font-size: 12px; font-weight: 600;">
        <?= p_lang('invoice_footer_note', 'This document represents an official asset registry entry for the specified transaction.') ?><br>
        AES-256 Encrypted Registry • <?= htmlspecialchars($white_label_name) ?>
    </div>
</div>

</body>
</html>
