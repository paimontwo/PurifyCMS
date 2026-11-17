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

// Handle POST actions with CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_invoice'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $oid = $_POST['order_id'];
    $status = $_POST['status'] ?? 'issued';
    $accounting_db['invoices'][$oid] = $status;
    file_put_contents($accounting_file, json_encode($accounting_db, JSON_PRETTY_PRINT), LOCK_EX);
    
    header('Location: accounting?success=1');
    exit;
}

// Filtering Parameters
$f_name = $_GET['f_name'] ?? '';
$f_date_start = $_GET['f_date_start'] ?? '';
$f_date_end = $_GET['f_date_end'] ?? '';
$f_min = $_GET['f_min'] ?? '';
$f_max = $_GET['f_max'] ?? '';
$f_formal = $_GET['f_formal'] ?? '';

// Global Aggregates
$total_net = 0; $total_tax = 0; $total_gross = 0;
$order_count = 0; $pending_count = 0;
$all_orders = [];
$daily_sales = array_fill(0, 7, 0);

$order_files = glob($orders_dir . '/*.json');
foreach ($order_files as $file) {
    $data = json_decode(file_get_contents($file), true) ?: [];
    $monthly_data = isset($data['id']) ? [$data] : $data;
    foreach ($monthly_data as $o) {
        $o['formal_status'] = $accounting_db['invoices'][$o['id']] ?? 'pending';
        
        // Apply Filters
        if ($f_name) {
            $customer_name = $o['customer']['name'] ?? '';
            if (stripos($customer_name, $f_name) === false) continue;
        }
        if ($f_date_start && strtotime($o['date']) < strtotime($f_date_start)) continue;
        if ($f_date_end && strtotime($o['date']) > strtotime($f_date_end . ' 23:59:59')) continue;
        if ($f_min && ($o['total_gross'] ?? 0) < $f_min) continue;
        if ($f_max && ($o['total_gross'] ?? 0) > $f_max) continue;
        if ($f_formal && $o['formal_status'] !== $f_formal) continue;

        $all_orders[] = $o; 
        
        // Count all orders except cancelled
        if (!in_array($o['status'], ['cancelled', 'failed'])) {
            if (in_array($o['status'], ['paid', 'shipped', 'completed'])) {
                $total_net += ($o['total_net'] ?? 0);
                $total_tax += ($o['total_tax'] ?? 0);
                $total_gross += ($o['total_gross'] ?? 0);
                $order_count++;
                
                $day_diff = (time() - strtotime($o['date'])) / 86400;
                if ($day_diff < 7) $daily_sales[(int)$day_diff] += ($o['total_gross'] ?? 0);
            } elseif ($o['status'] === 'pending') {
                $pending_count++;
            }
        }
    }
}
$daily_sales = array_reverse($daily_sales);
usort($all_orders, function($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });

$admin_page_title = p_lang('accounting_analytics', 'Accounting Analytics');
$admin_active_nav = 'accounting';
require __DIR__ . '/includes/header.php';
?>

<div class="v-panel-container" style="max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f1f5f9; padding-bottom: 25px;">
        <h2 style="margin:0; font-size: 28px; font-weight: 800; letter-spacing: -0.02em;"><i class="las la-file-invoice-dollar" style="color:var(--primary-color);"></i> <?= p_lang('fiscal_oracle', 'Fiscal Oracle') ?></h2>
        <div style="display: flex; gap: 15px; align-items: center;">
            <button onclick="document.getElementById('filter-panel').style.display = (document.getElementById('filter-panel').style.display === 'none' ? 'block' : 'none')" class="v-btn v-btn-outline" style="height: 40px; padding: 0 15px;"><i class="las la-filter"></i> <?= p_lang('apply_filters', 'Filters') ?></button>
            <div class="v-badge" style="background: #f1f5f9; color: #94a3b8;"><?= p_lang('revenue_report', 'Revenue Report') ?> v3.4.1</div>
        </div>
    </div>

    <!-- Filter Panel (Collapsible) -->
    <div id="filter-panel" class="v-panel" style="display: <?= ($f_name || $f_date_start || $f_min) ? 'block' : 'none' ?>; margin-bottom: 40px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 30px !important;">
        <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div class="input-group">
                <label class="v-label" style="font-size: 11px;"><?= p_lang('filter_by_name', 'Customer Name') ?></label>
                <input type="text" name="f_name" class="form-control" value="<?= htmlspecialchars($f_name) ?>" placeholder="...">
            </div>
            <div class="input-group">
                <label class="v-label" style="font-size: 11px;"><?= p_lang('date_start', 'Min. Date') ?></label>
                <input type="date" name="f_date_start" class="form-control" value="<?= htmlspecialchars($f_date_start) ?>">
            </div>
            <div class="input-group">
                <label class="v-label" style="font-size: 11px;"><?= p_lang('date_end', 'Max. Date') ?></label>
                <input type="date" name="f_date_end" class="form-control" value="<?= htmlspecialchars($f_date_end) ?>">
            </div>
            <div class="input-group">
                <label class="v-label" style="font-size: 11px;"><?= p_lang('min_amount', 'Min. Amount') ?></label>
                <input type="number" name="f_min" class="form-control" value="<?= htmlspecialchars($f_min) ?>">
            </div>
            <div class="input-group">
                <label class="v-label" style="font-size: 11px;"><?= p_lang('fiscal_formalization', 'Formal Status') ?></label>
                <select name="f_formal" class="form-control" style="appearance: auto;">
                    <option value=""><?= p_lang('status_all', 'All') ?></option>
                    <option value="formalized" <?= $f_formal === 'formalized' ? 'selected' : '' ?>><?= p_lang('formalized', 'Formalized') ?></option>
                    <option value="pending" <?= $f_formal === 'pending' ? 'selected' : '' ?>><?= p_lang('awaiting_verification', 'Pending') ?></option>
                </select>
            </div>
            <div style="display: flex; gap: 10px; align-items: flex-end;">
                <button type="submit" class="v-btn v-btn-primary" style="flex: 1; height: 44px;"><?= p_lang('apply_filters', 'Filter') ?></button>
                <a href="accounting" class="v-btn v-btn-outline" style="flex: 1; height: 44px; display: flex; align-items: center; justify-content: center; text-decoration: none;"><?= p_lang('clear_filters', 'Reset') ?></a>
            </div>
        </form>
    </div>

    <!-- Fiscal Matrix -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 50px;">
        <div class="v-stat-card premium">
            <div style="position: absolute; top: -20px; right: -20px; font-size: 140px; opacity: 0.1; transform: rotate(-15deg);"><i class="las la-wallet"></i></div>
            <small style="text-transform:uppercase; font-weight:800; opacity:0.7; font-size:11px; letter-spacing: 0.1em;"><?= p_lang('total_gross_revenue', 'Total Gross Revenue') ?></small>
            <h3 style="margin:10px 0 0 0; font-size: 36px; font-weight: 900; letter-spacing: -0.01em;"><?= number_format($total_gross, 2) ?> <small style="font-size: 14px; font-weight: 500; opacity: 0.6;">TRY</small></h3>
            <div style="margin-top: 15px; font-size: 11px; font-weight: 800; background: rgba(255,255,255,0.15); display: inline-block; padding: 6px 12px; border-radius: 8px;"><?= p_lang('all_time_verified', 'All Time Verified') ?></div>
        </div>

        <div class="v-stat-card light">
            <small style="text-transform:uppercase; font-weight:800; color: #94a3b8; font-size:11px; letter-spacing: 0.1em;"><?= p_lang('successful_transitions', 'Successful Transitions') ?></small>
            <h3 style="margin:10px 0 0 0; font-size: 36px; font-weight: 800; color: #1e293b;"><?= $order_count ?></h3>
            <div style="margin-top: 15px; display: flex; align-items: center; gap: 8px; color: #10b981; font-weight: 800; font-size: 13px;">
                <i class="las la-arrow-up"></i> <?= p_lang('fulfilment_ratio', 'Fulfilment Ratio') ?>
            </div>
        </div>

        <div class="v-stat-card light">
            <small style="text-transform:uppercase; font-weight:800; color: #94a3b8; font-size:11px; letter-spacing: 0.1em;"><?= p_lang('pending_liabilities', 'Pending Liabilities') ?></small>
            <h3 style="margin:10px 0 0 0; font-size: 36px; font-weight: 800; color: #f59e0b;"><?= $pending_count ?></h3>
            <div style="margin-top: 15px; display: flex; align-items: center; gap: 8px; color: #f59e0b; font-weight: 800; font-size: 13px;">
                <i class="las la-clock"></i> <?= p_lang('awaiting_verification', 'Awaiting Verification') ?>
            </div>
        </div>
    </div>

    <h3 style="margin: 0 0 25px 0; font-weight: 800; letter-spacing: -0.01em;"><?= p_lang('recent_fiscal_activity', 'Recent Fiscal Activity') ?></h3>
    <div class="v-panel" style="padding: 0 !important; overflow: hidden;">
        <table class="v-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="text-align:left;"><?= p_lang('protocol_id', 'Protocol ID') ?></th>
                    <th style="text-align:left;"><?= p_lang('date', 'Date') ?></th>
                    <th style="text-align:left;"><?= p_lang('client_identifier', 'Client Identifier') ?></th>
                    <th style="text-align:left;"><?= p_lang('currency_flow', 'Currency Flow') ?></th>
                    <th style="text-align:left;"><?= p_lang('fiscal_formalization', 'Formal Status') ?></th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($all_orders, 0, 15) as $o): ?>
                    <tr>
                        <td><code style="font-size: 11px; font-weight: 700; color: #475569;"><?= htmlspecialchars($o['id']) ?></code></td>
                        <td style="font-weight: 600; color: #64748b; font-size: 13px;"><?= date('M d, Y • H:i', strtotime($o['date'])) ?></td>
                        <td style="font-weight: 800; color: #1e293b;"><?= htmlspecialchars($o['customer']['name']) ?></td>
                        <td style="font-weight: 900; color: var(--primary-color); font-size: 15px;">+<?= number_format($o['total_gross'], 2) ?> <?= htmlspecialchars($o['currency']) ?></td>
                        <td>
                            <?php if ($o['formal_status'] === 'formalized'): ?>
                                <span class="v-badge" style="background: #10b98120; color: #10b981; border: 1px dashed #10b98150;"><i class="las la-check-double"></i> <?= p_lang('formalized', 'FORMALIZED') ?></span>
                            <?php else: ?>
                                <span class="v-badge" style="background: #f1f5f9; color: #94a3b8;"><?= p_lang('awaiting_verification', 'PENDING') ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right; display: flex; justify-content: flex-end; gap: 8px;">
                            <a href="invoice_view?oid=<?= $o['id'] ?>" target="_blank" class="v-btn v-btn-outline" style="width: 38px; height: 38px; padding: 0; display: flex; align-items: center; justify-content: center;" title="<?= p_lang('invoice_view', 'View Invoice') ?>"><i class="las la-file-pdf"></i></a>
                            <?php if ($o['formal_status'] === 'formalized'): ?>
                                <form method="post" style="display: inline;" onsubmit="return confirm('<?= p_lang('confirm_unformalize', 'Mark as pending?') ?>')">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <input type="hidden" name="status" value="pending">
                                    <button type="submit" name="mark_invoice" class="v-btn v-btn-outline" style="width: 38px; height: 38px; padding: 0; color: #ef4444; border-color: #fee2e2;" title="<?= p_lang('unformalize', 'Un-formalize') ?>">
                                        <i class="las la-undo-alt"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <input type="hidden" name="status" value="formalized">
                                    <button type="submit" name="mark_invoice" class="v-btn v-btn-primary" style="width: 38px; height: 38px; padding: 0;" title="<?= p_lang('formalize', 'Formalize') ?>">
                                        <i class="las la-stamp"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; if (empty($all_orders)) echo '<tr><td colspan="5" style="text-align:center; padding: 100px; opacity:0.3;"><h1>'.p_lang('quiet_fiscality', 'Quiet Fiscality').'</h1><p>'.p_lang('fiscal_empty_p', 'Financial movements will appear as orders proceed.').'</p></td></tr>'; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
