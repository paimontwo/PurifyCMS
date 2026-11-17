<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/PurifyTrade.php';

$orders_dir = __DIR__ . '/../inc/data/ecommerce/orders';
if (!is_dir($orders_dir)) @mkdir($orders_dir, 0755, true);

$action = $_GET['action'] ?? 'list';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die('CSRF validation failed');
    
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'] ?? '';
        $new_status = $_POST['status'] ?? 'pending';
        if (PurifyTrade::updateOrderStatus($order_id, $new_status)) {
            $success = p_lang('order_status_updated', 'Order status updated successfully.');
        } else {
            $error = p_lang('order_status_error', 'Failed to update order status.');
        }
    }
    
    if (isset($_POST['delete_order']) && isset($_POST['order_id'])) {
        $order_id = $_POST['order_id'];
        $deleted = false;
        
        $single_file = $orders_dir . '/' . basename($order_id) . '.json';
        if (file_exists($single_file)) {
            @unlink($single_file);
            $deleted = true;
        } else {
            $order_files = glob($orders_dir . '/*.json');
            foreach ($order_files as $file) {
                $data = json_decode(file_get_contents($file), true) ?: [];
                if (!isset($data['id']) && isset($data[$order_id])) {
                    unset($data[$order_id]);
                    if (empty($data)) {
                        @unlink($file);
                    } else {
                        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
                    }
                    $deleted = true;
                    break;
                }
            }
        }
        
        if ($deleted) {
            $success = p_lang('order_deleted_success', 'Order deleted successfully.');
        } else {
            $error = p_lang('order_delete_error', 'Failed to delete order.');
        }
    }
}

$all_orders = [];
$order_files = glob($orders_dir . '/*.json');
rsort($order_files);
foreach ($order_files as $file) {
    $data = json_decode(file_get_contents($file), true) ?: [];
    if (isset($data['id'])) {
        $all_orders[$data['id']] = $data;
    } else {
        foreach ($data as $oid => $ord) {
            $all_orders[$oid] = $ord;
        }
    }
}

$total_orders_count = count($all_orders);
$paid_orders_count = 0;
$pending_orders_count = 0;
$total_revenue = 0;

foreach ($all_orders as $ord) {
    $st = $ord['status'] ?? 'pending';
    if (in_array($st, ['paid', 'shipped', 'completed'])) {
        $paid_orders_count++;
        $total_revenue += (float)($ord['total_gross'] ?? 0);
    } elseif ($st === 'pending') {
        $pending_orders_count++;
    }
}

$admin_page_title = p_lang('order_management', 'Order Management');
$admin_page_subtitle = p_lang('orders_subtitle', 'Track customer orders, payment status, and fulfilment.');
$admin_active_nav = 'orders';
require __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?>
    <div class="notice notice-success"><i class="las la-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="notice notice-error"><i class="las la-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>

    <div class="bento-grid" style="margin-bottom: 24px;">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= p_lang('total_orders', 'Total Orders') ?></span>
                <div class="stat-card-icon"><i class="las la-shopping-bag"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($total_orders_count) ?></div>
            <div class="stat-card-footer"><?= p_lang('all_recorded_orders', 'All recorded orders') ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= p_lang('completed_orders', 'Paid & Shipped') ?></span>
                <div class="stat-card-icon" style="background: var(--success-bg); color: var(--success-color);"><i class="las la-check-circle"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($paid_orders_count) ?></div>
            <div class="stat-card-footer"><?= p_lang('verified_payments', 'Verified payments') ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= p_lang('pending_orders', 'Pending Orders') ?></span>
                <div class="stat-card-icon" style="background: var(--warning-bg); color: var(--warning-color);"><i class="las la-clock"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($pending_orders_count) ?></div>
            <div class="stat-card-footer"><?= p_lang('awaiting_payment', 'Awaiting payment confirmation') ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= p_lang('total_gross_revenue', 'Total Revenue') ?></span>
                <div class="stat-card-icon"><i class="las la-wallet"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($total_revenue, 2) ?> <small style="font-size: 13px; font-weight: 500; color: var(--text-muted);"><?= htmlspecialchars($cfg_ecommerce_currency ?? 'TRY') ?></small></div>
            <div class="stat-card-footer"><a href="accounting"><?= p_lang('accounting', 'View Accounting') ?> &rarr;</a></div>
        </div>
    </div>

    <div class="panel" style="padding: 0; overflow: hidden;">
        <div style="padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h3 class="panel-title"><i class="las la-list-ul"></i> <?= p_lang('orders', 'Orders') ?></h3>
            <a href="accounting" class="btn btn-secondary btn-sm">
                <i class="las la-file-invoice-dollar"></i> <?= p_lang('accounting', 'Accounting & Invoices') ?>
            </a>
        </div>

        <?php if (empty($all_orders)): ?>
            <div style="text-align: center; padding: 64px 24px;">
                <div style="width: 56px; height: 56px; border-radius: 14px; background: var(--bg-body); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; font-size: 26px; color: var(--text-muted); margin-bottom: 14px;">
                    <i class="las la-shopping-basket"></i>
                </div>
                <h3 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 600; color: var(--text-main);"><?= p_lang('no_orders_found', 'No Orders Yet') ?></h3>
                <p style="margin: 0 auto; max-width: 380px; font-size: 13px; color: var(--text-muted);">
                    <?= p_lang('no_orders_desc', 'Customer orders placed through your storefront will appear here.') ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= p_lang('order_id', 'Order ID') ?></th>
                            <th><?= p_lang('customer', 'Customer') ?></th>
                            <th><?= p_lang('amount', 'Total') ?></th>
                            <th><?= p_lang('status', 'Status') ?></th>
                            <th><?= p_lang('date', 'Date') ?></th>
                            <th style="text-align: right;"><?= p_lang('actions', 'Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sorted_orders = array_values($all_orders);
                        usort($sorted_orders, function($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });
                        foreach ($sorted_orders as $o): 
                            $st = $o['status'] ?? 'pending';
                            $badge_class = 'badge-neutral';
                            if ($st === 'paid') $badge_class = 'badge-success';
                            elseif ($st === 'shipped') $badge_class = 'badge-primary';
                            elseif ($st === 'pending') $badge_class = 'badge-warning';
                            elseif ($st === 'cancelled') $badge_class = 'badge-danger';
                        ?>
                            <tr>
                                <td>
                                    <code style="font-size: 12px; background: var(--bg-body); border: 1px solid var(--border-color); padding: 4px 8px; border-radius: 6px; font-weight: 600; color: var(--text-main);">
                                        #<?= htmlspecialchars($o['id']) ?>
                                    </code>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($o['customer']['name'] ?? '-') ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($o['customer']['email'] ?? '-') ?></div>
                                </td>
                                <td style="font-weight: 700; color: var(--text-main);">
                                    <?= number_format($o['total_gross'] ?? 0, 2) ?>
                                    <span style="font-size: 11.5px; font-weight: 500; color: var(--text-muted);"><?= htmlspecialchars($o['currency'] ?? 'TRY') ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $badge_class ?>">
                                        <?= htmlspecialchars(strtoupper(p_lang($st, $st))) ?>
                                    </span>
                                </td>
                                <td style="font-size: 12.5px; color: var(--text-muted); white-space: nowrap;">
                                    <?= date('d M Y, H:i', strtotime($o['date'])) ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                        <a href="?action=view&id=<?= urlencode($o['id']) ?>" class="btn btn-secondary btn-sm">
                                            <i class="las la-eye"></i> <?= p_lang('view_details', 'Details') ?>
                                        </a>
                                        <a href="invoice_view?oid=<?= urlencode($o['id']) ?>" target="_blank" class="btn-icon" title="<?= p_lang('invoice_view', 'View Invoice') ?>">
                                            <i class="las la-file-invoice"></i>
                                        </a>
                                        <form method="post" style="display: inline; margin: 0;" onsubmit="return confirm('<?= p_lang('delete_order_confirm', 'Are you sure you want to delete this order?') ?>')">
                                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($o['id']) ?>">
                                            <button type="submit" name="delete_order" class="btn-icon" style="color: var(--danger-color);" title="<?= p_lang('delete', 'Delete') ?>">
                                                <i class="las la-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'view' && isset($_GET['id'])): 
    $order = $all_orders[$_GET['id']] ?? null;
    if (!$order):
?>
    <div class="panel" style="text-align: center; padding: 48px 24px;">
        <h3 style="margin: 0 0 12px 0;"><?= p_lang('order_not_found', 'Order not found.') ?></h3>
        <a href="orders" class="btn btn-secondary"><i class="las la-arrow-left"></i> <?= p_lang('back_to_orders', 'Back to Orders') ?></a>
    </div>
<?php else: 
    $st = $order['status'] ?? 'pending';
    $badge_class = 'badge-neutral';
    if ($st === 'paid') $badge_class = 'badge-success';
    elseif ($st === 'shipped') $badge_class = 'badge-primary';
    elseif ($st === 'pending') $badge_class = 'badge-warning';
    elseif ($st === 'cancelled') $badge_class = 'badge-danger';
?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="orders" class="btn btn-secondary btn-sm">
                <i class="las la-arrow-left"></i> <?= p_lang('back_to_orders', 'Back to Orders') ?>
            </a>
            <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--text-main);">
                <?= p_lang('order', 'Order') ?> #<?= htmlspecialchars($order['id']) ?>
            </h2>
            <span class="badge <?= $badge_class ?>"><?= htmlspecialchars(strtoupper(p_lang($st, $st))) ?></span>
        </div>

        <a href="invoice_view?oid=<?= urlencode($order['id']) ?>" target="_blank" class="btn btn-secondary btn-sm">
            <i class="las la-print"></i> <?= p_lang('invoice_view', 'Print / View Invoice') ?>
        </a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;" class="order-detail-grid">
        <div>
            <div class="panel" style="padding: 0; overflow: hidden;">
                <div style="padding: 18px 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 class="panel-title"><i class="las la-box"></i> <?= p_lang('order_items', 'Order Items') ?></h3>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?= p_lang('product_title', 'Product') ?></th>
                                <th><?= p_lang('unit_price', 'Unit Price') ?></th>
                                <th><?= p_lang('tax_rate', 'Tax') ?></th>
                                <th style="text-align: right;"><?= p_lang('subtotal', 'Total') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $it): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($it['title']) ?></div>
                                        <?php if (!empty($it['selected_options'])): ?>
                                            <div style="margin-top: 6px; display: flex; flex-wrap: wrap; gap: 6px;">
                                                <?php foreach ($it['selected_options'] as $opt): ?>
                                                    <span class="badge badge-primary">+ <?= htmlspecialchars($opt['name']) ?> (<?= number_format($opt['price'], 2) ?>)</span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($it['price'] ?? 0, 2) ?> <?= htmlspecialchars($order['currency']) ?></td>
                                    <td style="color: var(--text-muted);">%<?= htmlspecialchars($it['tax_rate'] ?? 20) ?></td>
                                    <td style="text-align: right; font-weight: 700; color: var(--text-main);">
                                        <?= number_format($it['total_gross'] ?? $it['gross'] ?? 0, 2) ?> <?= htmlspecialchars($order['currency']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background: var(--bg-body);">
                                <td colspan="3" style="text-align: right; font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 12px;">
                                    <?= p_lang('grand_total', 'Grand Total') ?>
                                </td>
                                <td style="text-align: right; font-size: 18px; font-weight: 700; color: var(--primary-color);">
                                    <?= number_format($order['total_gross'], 2) ?> <small style="font-size: 12px; font-weight: 500; color: var(--text-muted);"><?= htmlspecialchars($order['currency']) ?></small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title"><i class="las la-user"></i> <?= p_lang('customer_information', 'Customer & Billing Information') ?></h3>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label><?= p_lang('full_name', 'Full Name') ?></label>
                        <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($order['customer']['name'] ?? '-') ?></div>
                    </div>
                    <div>
                        <label><?= p_lang('phone_number', 'Phone') ?></label>
                        <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($order['customer']['phone'] ?? '-') ?></div>
                    </div>
                    <div>
                        <label><?= p_lang('email_address', 'Email') ?></label>
                        <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($order['customer']['email'] ?? '-') ?></div>
                    </div>
                    <div>
                        <label><?= p_lang('tax_info', 'Tax / Company Info') ?></label>
                        <div style="font-weight: 500; color: var(--text-main);">
                            <?= htmlspecialchars(($order['customer']['tax_id'] ?? '-') . ' / ' . ($order['customer']['tax_office'] ?? '-') . (!empty($order['customer']['company']) ? ' • ' . $order['customer']['company'] : '')) ?>
                        </div>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label><?= p_lang('shipping_address', 'Address') ?></label>
                        <div style="font-weight: 500; color: var(--text-main); line-height: 1.5;">
                            <?= htmlspecialchars(($order['customer']['address'] ?? '') . ', ' . ($order['customer']['district'] ?? '') . '/' . ($order['customer']['city'] ?? '') . ' ' . ($order['customer']['zip'] ?? '')) ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($order['customer_note'])): ?>
                <div class="panel" style="background: var(--warning-bg); border-color: rgba(245, 158, 11, 0.25);">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 700; color: var(--warning-color); display: flex; align-items: center; gap: 8px;">
                        <i class="las la-sticky-note"></i> <?= p_lang('customer_note', 'Customer Note') ?>
                    </h4>
                    <div style="font-size: 13.5px; color: var(--text-main); line-height: 1.5;">
                        <?= nl2br(htmlspecialchars($order['customer_note'])) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div style="position: sticky; top: 84px;">
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title"><i class="las la-cog"></i> <?= p_lang('order_status', 'Order Status') ?></h3>
                </div>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                    <div class="input-group">
                        <label><?= p_lang('update_status', 'Status') ?></label>
                        <select name="status" class="form-control">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>><?= p_lang('pending', 'Pending Payment') ?></option>
                            <option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>><?= p_lang('paid', 'Paid / Verified') ?></option>
                            <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>><?= p_lang('shipped', 'Shipped / Delivered') ?></option>
                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>><?= p_lang('cancelled', 'Cancelled / Refunded') ?></option>
                        </select>
                    </div>
                    <button type="submit" name="update_status" class="btn btn-primary" style="width: 100%;">
                        <i class="las la-save"></i> <?= p_lang('save_changes', 'Update Status') ?>
                    </button>
                </form>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 10px;">
                    <?php if (!empty($order['customer']['phone'])): ?>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $order['customer']['phone']) ?>" target="_blank" class="btn btn-success" style="width: 100%;">
                            <i class="lab la-whatsapp" style="font-size: 18px;"></i> WhatsApp
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($order['customer']['email'])): ?>
                        <a href="mailto:<?= htmlspecialchars($order['customer']['email']) ?>" class="btn btn-secondary" style="width: 100%;">
                            <i class="las la-envelope"></i> <?= p_lang('send_email', 'Send Email') ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
    @media (max-width: 900px) {
        .order-detail-grid {
            grid-template-columns: 1fr !important;
        }
    }
    </style>
<?php endif; endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
