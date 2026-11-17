<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/PurifyTrade.php';

$orders_dir = __DIR__ . '/../inc/data/ecommerce/orders';
$accounting_file = __DIR__ . '/../inc/data/ecommerce/accounting.json';
if (!is_dir(dirname($accounting_file))) @mkdir(dirname($accounting_file), 0755, true);

$accounting_db = file_exists($accounting_file) ? json_decode(file_get_contents($accounting_file), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_invoice'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $oid = $_POST['order_id'] ?? '';
    $status = $_POST['status'] ?? 'issued';
    $accounting_db['invoices'][$oid] = $status;
    file_put_contents($accounting_file, json_encode($accounting_db, JSON_PRETTY_PRINT), LOCK_EX);
    
    header('Location: accounting?success=1');
    exit;
}

$f_name = trim($_GET['f_name'] ?? '');
$f_date_start = $_GET['f_date_start'] ?? '';
$f_date_end = $_GET['f_date_end'] ?? '';
$f_min = $_GET['f_min'] ?? '';
$f_max = $_GET['f_max'] ?? '';
$f_formal = $_GET['f_formal'] ?? '';

$total_net = 0;
$total_tax = 0;
$total_gross = 0;
$order_count = 0;
$pending_count = 0;
$all_orders = [];

$order_files = glob($orders_dir . '/*.json');
foreach ($order_files as $file) {
    $data = json_decode(file_get_contents($file), true) ?: [];
    $monthly_data = isset($data['id']) ? [$data] : $data;
    foreach ($monthly_data as $o) {
        $o['formal_status'] = $accounting_db['invoices'][$o['id']] ?? 'pending';
        
        if ($f_name !== '') {
            $customer_name = $o['customer']['name'] ?? '';
            if (stripos($customer_name, $f_name) === false) continue;
        }
        if ($f_date_start && strtotime($o['date']) < strtotime($f_date_start)) continue;
        if ($f_date_end && strtotime($o['date']) > strtotime($f_date_end . ' 23:59:59')) continue;
        if ($f_min !== '' && ($o['total_gross'] ?? 0) < (float)$f_min) continue;
        if ($f_max !== '' && ($o['total_gross'] ?? 0) > (float)$f_max) continue;
        if ($f_formal !== '' && $o['formal_status'] !== $f_formal) continue;

        $all_orders[] = $o;
        
        if (!in_array($o['status'], ['cancelled', 'failed'])) {
            if (in_array($o['status'], ['paid', 'shipped', 'completed'])) {
                $total_net += ($o['total_net'] ?? 0);
                $total_tax += ($o['total_tax'] ?? 0);
                $total_gross += ($o['total_gross'] ?? 0);
                $order_count++;
            } elseif ($o['status'] === 'pending') {
                $pending_count++;
            }
        }
    }
}

usort($all_orders, function($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });

$has_active_filters = ($f_name !== '' || $f_date_start !== '' || $f_date_end !== '' || $f_min !== '' || $f_max !== '' || $f_formal !== '');
$currency_code = htmlspecialchars($cfg_ecommerce_currency ?? 'TRY');

$admin_page_title = p_lang('accounting_analytics', 'Accounting & Invoices');
$admin_page_subtitle = p_lang('accounting_subtitle', 'Track revenue, tax totals, and formal invoice status.');
$admin_active_nav = 'accounting';
require __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['success'])): ?>
    <div class="notice notice-success">
        <i class="las la-check-circle"></i> <?= p_lang('operation_success', 'Operation completed successfully.') ?>
    </div>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 10px;">
        <button type="button" onclick="const fp = document.getElementById('filter-panel'); fp.style.display = (fp.style.display === 'none' ? 'block' : 'none');" class="btn btn-secondary">
            <i class="las la-filter"></i> <?= p_lang('apply_filters', 'Filters') ?>
            <?php if ($has_active_filters): ?>
                <span class="badge badge-primary" style="margin-left: 4px;"><?= p_lang('active', 'Active') ?></span>
            <?php endif; ?>
        </button>
        <?php if ($has_active_filters): ?>
            <a href="accounting" class="btn btn-secondary btn-sm">
                <i class="las la-times"></i> <?= p_lang('clear_filters', 'Reset') ?>
            </a>
        <?php endif; ?>
    </div>

    <a href="orders" class="btn btn-secondary btn-sm">
        <i class="las la-shopping-bag"></i> <?= p_lang('orders', 'View Orders') ?>
    </a>
</div>

<div id="filter-panel" class="panel" style="display: <?= $has_active_filters ? 'block' : 'none' ?>; margin-bottom: 24px;">
    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; align-items: end;">
        <div class="input-group" style="margin-bottom: 0;">
            <label><?= p_lang('filter_by_name', 'Customer Name') ?></label>
            <input type="text" name="f_name" class="form-control" value="<?= htmlspecialchars($f_name) ?>" placeholder="<?= p_lang('search_customer', 'Search name...') ?>">
        </div>
        <div class="input-group" style="margin-bottom: 0;">
            <label><?= p_lang('date_start', 'Start Date') ?></label>
            <input type="date" name="f_date_start" class="form-control" value="<?= htmlspecialchars($f_date_start) ?>">
        </div>
        <div class="input-group" style="margin-bottom: 0;">
            <label><?= p_lang('date_end', 'End Date') ?></label>
            <input type="date" name="f_date_end" class="form-control" value="<?= htmlspecialchars($f_date_end) ?>">
        </div>
        <div class="input-group" style="margin-bottom: 0;">
            <label><?= p_lang('min_amount', 'Min. Amount') ?></label>
            <input type="number" step="0.01" name="f_min" class="form-control" value="<?= htmlspecialchars($f_min) ?>" placeholder="0.00">
        </div>
        <div class="input-group" style="margin-bottom: 0;">
            <label><?= p_lang('fiscal_formalization', 'Invoice Status') ?></label>
            <select name="f_formal" class="form-control">
                <option value=""><?= p_lang('status_all', 'All') ?></option>
                <option value="formalized" <?= $f_formal === 'formalized' ? 'selected' : '' ?>><?= p_lang('formalized', 'Formalized') ?></option>
                <option value="pending" <?= $f_formal === 'pending' ? 'selected' : '' ?>><?= p_lang('awaiting_verification', 'Pending') ?></option>
            </select>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">
                <i class="las la-check"></i> <?= p_lang('apply_filters', 'Apply') ?>
            </button>
            <a href="accounting" class="btn btn-secondary" style="flex: 1;">
                <?= p_lang('clear_filters', 'Reset') ?>
            </a>
        </div>
    </form>
</div>

<div class="bento-grid" style="margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title"><?= p_lang('total_gross_revenue', 'Total Gross Revenue') ?></span>
            <div class="stat-card-icon"><i class="las la-wallet"></i></div>
        </div>
        <div class="stat-card-value"><?= number_format($total_gross, 2) ?> <small style="font-size: 13px; font-weight: 500; color: var(--text-muted);"><?= $currency_code ?></small></div>
        <div class="stat-card-footer"><?= p_lang('all_time_verified', 'Verified paid orders') ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title"><?= p_lang('net_and_tax', 'Net Revenue & Tax') ?></span>
            <div class="stat-card-icon"><i class="las la-percentage"></i></div>
        </div>
        <div class="stat-card-value"><?= number_format($total_net, 2) ?> <small style="font-size: 13px; font-weight: 500; color: var(--text-muted);"><?= $currency_code ?></small></div>
        <div class="stat-card-footer"><?= p_lang('tax_collected', 'Tax') ?>: <?= number_format($total_tax, 2) ?> <?= $currency_code ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title"><?= p_lang('completed_orders', 'Completed Orders') ?></span>
            <div class="stat-card-icon" style="background: var(--success-bg); color: var(--success-color);"><i class="las la-check-circle"></i></div>
        </div>
        <div class="stat-card-value"><?= number_format($order_count) ?></div>
        <div class="stat-card-footer"><?= p_lang('verified_payments', 'Paid & delivered') ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title"><?= p_lang('pending_orders', 'Pending Orders') ?></span>
            <div class="stat-card-icon" style="background: var(--warning-bg); color: var(--warning-color);"><i class="las la-clock"></i></div>
        </div>
        <div class="stat-card-value"><?= number_format($pending_count) ?></div>
        <div class="stat-card-footer"><?= p_lang('awaiting_verification', 'Awaiting verification') ?></div>
    </div>
</div>

<div class="panel" style="padding: 0; overflow: hidden;">
    <div style="padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
        <h3 class="panel-title"><i class="las la-file-invoice-dollar"></i> <?= p_lang('recent_fiscal_activity', 'Financial Transactions & Invoices') ?></h3>
        <span class="badge badge-neutral"><?= count($all_orders) ?> <?= p_lang('records', 'records') ?></span>
    </div>

    <?php if (empty($all_orders)): ?>
        <div style="text-align: center; padding: 64px 24px;">
            <div style="width: 56px; height: 56px; border-radius: 14px; background: var(--bg-body); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; font-size: 26px; color: var(--text-muted); margin-bottom: 14px;">
                <i class="las la-receipt"></i>
            </div>
            <h3 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 600; color: var(--text-main);"><?= p_lang('no_transactions_found', 'No Transactions Found') ?></h3>
            <p style="margin: 0 auto; max-width: 380px; font-size: 13px; color: var(--text-muted);">
                <?= p_lang('fiscal_empty_p', 'Financial records will appear here as orders are processed.') ?>
            </p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= p_lang('order_id', 'Order ID') ?></th>
                        <th><?= p_lang('date', 'Date') ?></th>
                        <th><?= p_lang('customer', 'Customer') ?></th>
                        <th><?= p_lang('amount', 'Amount') ?></th>
                        <th><?= p_lang('fiscal_formalization', 'Invoice Status') ?></th>
                        <th style="text-align: right;"><?= p_lang('actions', 'Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($all_orders, 0, 50) as $o): ?>
                        <tr>
                            <td>
                                <code style="font-size: 12px; background: var(--bg-body); border: 1px solid var(--border-color); padding: 4px 8px; border-radius: 6px; font-weight: 600; color: var(--text-main);">
                                    #<?= htmlspecialchars($o['id']) ?>
                                </code>
                            </td>
                            <td style="font-size: 12.5px; color: var(--text-muted); white-space: nowrap;">
                                <?= date('d M Y, H:i', strtotime($o['date'])) ?>
                            </td>
                            <td style="font-weight: 600; color: var(--text-main);">
                                <?= htmlspecialchars($o['customer']['name'] ?? '-') ?>
                            </td>
                            <td style="font-weight: 700; color: var(--primary-color);">
                                +<?= number_format($o['total_gross'] ?? 0, 2) ?> <span style="font-size: 11.5px; font-weight: 500; color: var(--text-muted);"><?= htmlspecialchars($o['currency'] ?? 'TRY') ?></span>
                            </td>
                            <td>
                                <?php if (($o['formal_status'] ?? 'pending') === 'formalized'): ?>
                                    <span class="badge badge-success"><i class="las la-check-double"></i> <?= p_lang('formalized', 'Formalized') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-neutral"><?= p_lang('awaiting_verification', 'Pending') ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; justify-content: flex-end; gap: 6px; align-items: center;">
                                    <a href="invoice_view?oid=<?= urlencode($o['id']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="<?= p_lang('invoice_view', 'View Invoice') ?>">
                                        <i class="las la-file-pdf"></i> <?= p_lang('invoice', 'Invoice') ?>
                                    </a>
                                    <?php if (($o['formal_status'] ?? 'pending') === 'formalized'): ?>
                                        <form method="post" style="display: inline; margin: 0;" onsubmit="return confirm('<?= p_lang('confirm_unformalize', 'Mark as pending?') ?>')">
                                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($o['id']) ?>">
                                            <input type="hidden" name="status" value="pending">
                                            <button type="submit" name="mark_invoice" class="btn-icon" style="color: var(--danger-color);" title="<?= p_lang('unformalize', 'Mark Pending') ?>">
                                                <i class="las la-undo-alt"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" style="display: inline; margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($o['id']) ?>">
                                            <input type="hidden" name="status" value="formalized">
                                            <button type="submit" name="mark_invoice" class="btn btn-primary btn-sm" title="<?= p_lang('formalize', 'Mark Formalized') ?>">
                                                <i class="las la-check"></i> <?= p_lang('formalize', 'Approve') ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
