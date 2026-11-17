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
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die('CSRF failed');
    
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['status'];
        if (PurifyTrade::updateOrderStatus($order_id, $new_status)) {
            $success = 'Order status updated successfully.';
        } else {
            $error = 'Failed to update order status.';
        }
    }
    
    // Handle order deletion
    if (isset($_POST['delete_order']) && isset($_POST['order_id'])) {
        $order_id = $_POST['order_id'];
        $deleted = false;
        
        $single_file = $orders_dir . '/' . $order_id . '.json';
        if (file_exists($single_file)) {
            @unlink($single_file);
            $deleted = true;
        } else {
            // Find and remove order from legacy order files
            $order_files = glob($orders_dir . '/*.json');
            foreach ($order_files as $file) {
                $data = json_decode(file_get_contents($file), true) ?: [];
                if (!isset($data['id']) && isset($data[$order_id])) {
                    unset($data[$order_id]);
                    // Save back to file (or delete if empty)
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
            $success = 'Order deleted successfully.';
        } else {
            $error = 'Failed to delete order.';
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
        foreach($data as $oid => $ord) $all_orders[$oid] = $ord;
    }
}

$admin_page_title = p_lang('order_management', 'Order Management');
$admin_active_nav = 'orders';
require __DIR__ . '/includes/header.php';
?>

<div class="v-panel-container" style="max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; border-bottom: 2px solid #f1f5f9; padding-bottom: 25px;">
        <h2 style="margin:0; font-size: 28px; font-weight: 800; letter-spacing: -0.02em;"><i class="las la-shopping-basket" style="color:var(--primary-color);"></i> <?= p_lang('order_tracking', 'Fulfilment Hub') ?></h2>
        <div class="v-badge" style="background: #f1f5f9; color: #94a3b8;">System v3.4.1</div>
    </div>

    <?php if ($action === 'list'): ?>
        <div class="v-panel" style="padding: 0 !important; overflow: hidden;">
            <table class="v-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align:left; width: 120px;"><?= p_lang('registry_no', 'Registry') ?></th>
                        <th style="text-align:left;"><?= p_lang('client_identity', 'Client Identity') ?></th>
                        <th style="text-align:left;"><?= p_lang('revenue_net', 'Revenue Net') ?></th>
                        <th style="text-align:left;"><?= p_lang('order_state', 'State') ?></th>
                        <th style="text-align:left;"><?= p_lang('chronometry', 'Chronometry') ?></th>
                        <th style="text-align:right;"><?= p_lang('operations', 'Protocol') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sorted_orders = array_values($all_orders);
                    usort($sorted_orders, function($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });
                    foreach ($sorted_orders as $o): 
                    ?>
                        <tr>
                            <td><code style="font-size: 11px; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-weight: 700; color: #1e293b;"><?= $o['id'] ?></code></td>
                            <td>
                                <div style="font-weight: 800;"><?= htmlspecialchars($o['customer']['name']) ?></div>
                                <small style="opacity:0.5; display:block; margin-top: 2px;"><?= htmlspecialchars($o['customer']['email']) ?></small>
                            </td>
                            <td style="font-weight: 800; font-size: 15px; color: var(--primary-color);">
                                <?= number_format($o['total_gross'], 2) ?> <small style="font-weight: 500; opacity: 0.5; font-size: 11px;"><?= $o['currency'] ?></small>
                            </td>
                            <td>
                                <?php
                                    $status_color = '#94a3b8';
                                    if ($o['status'] === 'paid') $status_color = '#10b981';
                                    if ($o['status'] === 'shipped') $status_color = '#4f46e5';
                                    if ($o['status'] === 'pending') $status_color = '#f59e0b';
                                    if ($o['status'] === 'cancelled') $status_color = '#ef4444';
                                ?>
                                <span class="v-badge" style="background: <?= $status_color ?>15; color: <?= $status_color ?>; border: 1px solid <?= $status_color ?>20;">
                                    <?= strtoupper(p_lang($o['status'], $o['status'])) ?>
                                </span>
                            </td>
                            <td style="font-size: 12px; font-weight: 600; opacity: 0.6;"><?= date('d M, Y • H:i', strtotime($o['date'])) ?></td>
                            <td style="text-align:right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <a href="?action=view&id=<?= $o['id'] ?>" class="btn btn-secondary" style="font-size: 11px; padding: 8px 16px; border-radius: 8px;"><?= p_lang('examine_order', 'Examine Order') ?></a>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('<?= p_lang('delete_order_confirm', 'Are you sure you want to delete this order? This action cannot be undone.') ?>')">
                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <button type="submit" name="delete_order" class="btn btn-icon" style="background: rgba(239, 68, 68, 0.05); color:var(--danger-color); width: 38px; height: 38px; padding: 0; display: flex; align-items: center; justify-content: center; border: none; border-radius: 8px; cursor: pointer;" title="<?= p_lang('delete', 'Delete') ?>">
                                            <i class="las la-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; if (empty($all_orders)) echo '<tr><td colspan="6" style="text-align:center; padding: 100px; opacity:0.3;"><h1>Quiet Waters</h1><p>Orders will manifest here once the commerce engine is engaged.</p></td></tr>'; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($action === 'view' && isset($_GET['id'])): 
        $order = $all_orders[$_GET['id']] ?? null;
        if (!$order) { echo '<div class="v-panel"><h2 style="text-align:center; opacity:0.5;">Protocol Corruption: Registry not recovered.</h2></div>'; } else {
    ?>
        <div style="display: grid; grid-template-columns: 1fr 340px; gap: 40px; align-items: start;">
            <div>
                <h3 style="margin: 0 0 20px 0; font-weight: 800; letter-spacing: -0.01em;"><?= p_lang('client_identity', 'Client Portrait') ?></h3>
                <div class="v-panel" style="background:#f8fafc; padding:35px !important;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px;">
                        <div>
                            <small style="text-transform:uppercase; font-weight:800; opacity:0.4; font-size:10px; letter-spacing: 0.05em;">Full Name</small>
                            <p style="margin:5px 0 0 0; font-weight:800; color: #1e293b; font-size: 16px;"><?= htmlspecialchars($order['customer']['name']) ?></p>
                        </div>
                        <div>
                            <small style="text-transform:uppercase; font-weight:800; opacity:0.4; font-size:10px; letter-spacing: 0.05em;">Communications</small>
                            <p style="margin:5px 0 0 0; font-weight:700; color: #475569;"><?= htmlspecialchars($order['customer']['phone']) ?></p>
                        </div>
                        <div style="grid-column: span 2;">
                            <small style="text-transform:uppercase; font-weight:800; opacity:0.4; font-size:10px; letter-spacing: 0.05em;">Registry Address</small>
                            <p style="margin:5px 0 0 0; line-height: 1.6; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($order['customer']['address'] . ', ' . $order['customer']['district'] . '/' . $order['customer']['city'] . ' - ' . $order['customer']['zip']) ?></p>
                        </div>
                        <div style="grid-column: span 2; border-top: 2px dashed #e2e8f0; padding-top:20px; margin-top:5px;">
                            <small style="text-transform:uppercase; font-weight:800; opacity:0.4; font-size:10px; letter-spacing: 0.05em;">Fiscal Metadata</small>
                            <p style="margin:5px 0 0 0; font-weight: 700; color: #64748b;"><?= htmlspecialchars($order['customer']['tax_id']) ?> (<?= htmlspecialchars($order['customer']['tax_office']) ?>) • <?= htmlspecialchars($order['customer']['company']) ?></p>
                        </div>
                    </div>
                </div>

                <h3 style="margin: 40px 0 20px 0; font-weight: 800; letter-spacing: -0.01em;"><?= p_lang('order_items', 'Inventory Itemization') ?></h3>
                <div class="v-panel" style="padding: 0 !important; overflow: hidden; background: #fff;">
                    <table class="v-table" style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="text-align:left;"><?= p_lang('product_title', 'Item Specification') ?></th>
                                <th style="text-align:left;"><?= p_lang('product_price', 'Net Unit') ?></th>
                                <th style="text-align:left;"><?= p_lang('tax_rate', 'Level (Tax)') ?></th>
                                <th style="text-align:right;"><?= p_lang('subtotal', 'Subtotal') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $it): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:800; font-size: 15px; color: #1e293b;"><?= htmlspecialchars($it['title']) ?></div>
                                        <?php if (!empty($it['selected_options'])): ?>
                                            <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:6px;">
                                                <?php foreach ($it['selected_options'] as $opt): ?>
                                                    <span class="v-badge" style="background:rgba(79, 70, 229, 0.06); color:var(--primary-color);">+ <?= htmlspecialchars($opt['name']) ?> (<?= number_format($opt['price'], 2) ?>)</span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight: 600;"><?= number_format($it['price'] ?? 0, 2) ?></td>
                                    <td style="font-weight: 700; color: #94a3b8;">%<?= $it['tax_rate'] ?? 20 ?></td>
                                    <td style="text-align:right; font-weight: 800; color: var(--primary-color);"><?= number_format($it['total_gross'] ?? $it['gross'] ?? 0, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background:rgba(79, 70, 229, 0.02); font-weight:900; border-top:3px solid #f1f5f9;">
                                <td colspan="3" style="text-align:right; vertical-align: middle; padding: 25px !important; font-size: 14px; text-transform: uppercase; letter-spacing: 0.1em; color: #475569;"><?= p_lang('grand_total', 'Grand Financial Total') ?></td>
                                <td style="text-align:right; font-size: 22px; color:var(--primary-color); padding: 25px !important;"><?= number_format($order['total_gross'], 2) ?> <small style="font-size: 12px; font-weight: 500; opacity: 0.6;"><?= $order['currency'] ?></small></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($order['customer_note'])): ?>
                    <div style="margin-top:40px; background:linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border:1px solid #fde68a; padding:30px; border-radius:24px; color:#92400e;">
                        <h4 style="margin: 0 0 10px 0; font-weight: 800; display:flex; align-items:center; gap:8px;"><i class="las la-sticky-note" style="font-size:20px;"></i> Client Special Instructions</h4>
                        <div style="font-style:italic; font-weight: 600; line-height: 1.6; font-size: 15px;">
                            "<?= htmlspecialchars($order['customer_note']) ?>"
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div style="position: sticky; top: 100px;">
                <h3 style="margin: 0 0 20px 0; font-weight: 800; letter-spacing: -0.01em;"><?= p_lang('operations', 'Operations') ?></h3>
                <div class="v-panel" style="background:rgba(79, 70, 229, 0.03) !important; border: 2px solid var(--primary-color) !important;">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <label style="font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;"><?= p_lang('update_status', 'Update Registry Status') ?></label>
                        <select name="status" class="form-control" style="margin:10px 0 20px 0; font-weight: 700; height: 50px; border-radius: 12px;">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>><?= p_lang('awaiting_funds', 'Awaiting Funds') ?></option>
                            <option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>><?= p_lang('payment_verified', 'Payment Verified') ?></option>
                            <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>><?= p_lang('transit_initiated', 'Transit Initiated') ?></option>
                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>><?= p_lang('order_cancelled', 'Registry Void / Refunded') ?></option>
                        </select>
                        <button type="submit" name="update_status" class="v-btn-premium" style="width:100%; height: 55px; font-size: 16px;"><?= p_lang('update_status', 'Execute Change') ?></button>
                    </form>

                    <div style="margin-top:35px; border-top:1px solid #e2e8f0; padding-top:25px; display:flex; flex-direction:column; gap:12px;">
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $order['customer']['phone']) ?>" target="_blank" class="btn" style="background:#22c55e; color:white; border:none; font-weight:700; border-radius: 12px; height: 50px; display:flex; align-items:center; justify-content:center; gap:8px;">
                            <i class="lab la-whatsapp" style="font-size: 20px;"></i> Direct WhatsApp
                        </a>
                        <a href="mailto:<?= htmlspecialchars($order['customer']['email']) ?>" class="btn btn-secondary" style="height: 50px; font-weight: 700; border-radius: 12px; display:flex; align-items:center; justify-content:center; gap:8px;">
                            <i class="las la-envelope" style="font-size: 20px;"></i> Send Official Email
                        </a>
                    </div>
                </div>

                <div style="margin-top: 25px; text-align: center;">
                    <a href="orders" style="font-size: 13px; font-weight: 800; color: #94a3b8; text-decoration: none;"><i class="las la-arrow-left"></i> Hub Overview</a>
                </div>
            </div>
        </div>
    <?php } endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
