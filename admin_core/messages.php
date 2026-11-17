<?php
$admin_active_nav = 'messages';
require_once __DIR__ . '/includes/header.php';

$data_dir = __DIR__ . '/../inc/data/messages';
$available_months = [];
if (is_dir($data_dir)) {
    $files = glob($data_dir . '/*.json');
    rsort($files);
    foreach ($files as $f) {
        $available_months[] = basename($f, '.json');
    }
}

$selected_month = $_GET['month'] ?? ($available_months[0] ?? date('Y-m'));
$month_file = $data_dir . '/' . $selected_month . '.json';
$messages = [];
if (file_exists($month_file)) {
    $json = file_get_contents($month_file);
    if ($json) $messages = json_decode($json, true) ?: [];
}

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    verify_csrf_get($_GET['token'] ?? '');
    $action = $_GET['action'];
    $id = $_GET['id'];
    $modified = false;
    foreach ($messages as &$msg) {
        if ($msg['id'] === $id) {
            if ($action === 'read') {
                $msg['read'] = true;
                $modified = true;
            } elseif ($action === 'delete') {
                $msg = null;
                $modified = true;
            }
            break;
        }
    }
    if ($modified) {
        $messages = array_filter($messages); // remove nulls
        file_put_contents($month_file, json_encode(array_values($messages), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        header('Location: ' . $base_url . '/messages?month=' . $selected_month);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_month') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die('Security Token Validation Failed.');
    $target_month = $_POST['month_to_clear'];
    $target_file = $data_dir . '/' . $target_month . '.json';
    if (file_exists($target_file)) {
        unlink($target_file);
    }
    header('Location: ' . $base_url . '/messages');
    exit;
}
?>

<div class="dashboard-header">
    <div class="header-content">
        <h1><?= p_lang('inbox_archive', 'Inbox Archive') ?></h1>
        <p><?= p_lang('inbox_desc', 'Review and manage all incoming contact form submissions.') ?></p>
    </div>
    <div class="bento-grid">
        <form method="get" action="<?= $base_url ?>/messages" style="display:flex; gap:10px;">
            <select name="month" class="form-control" onchange="this.form.submit()" style="padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border-color); font-weight: 500; background: var(--bg-surface); color: var(--text-main);">
                <?php if(empty($available_months)): ?>
                    <option value=""><?= p_lang('no_archives', 'No Archives') ?></option>
                <?php else: ?>
                    <?php foreach($available_months as $m): ?>
                        <option value="<?= $m ?>" <?= $selected_month === $m ? 'selected' : '' ?>><?= date('F Y', strtotime($m . '-01')) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </form>
    </div>
</div>

<div class="dashboard-grid" style="grid-template-columns: 1fr; margin-bottom: 24px;">
    <?php if (empty($messages)): ?>
        <div class="panel" style="text-align: center; padding: 60px 20px;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--bg-hover); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                <i class="las la-inbox" style="font-size: 32px; color: var(--text-muted);"></i>
            </div>
            <h3><?= p_lang('no_messages_found', 'No Messages Found') ?></h3>
            <p style="color: var(--text-muted);"><?= p_lang('archive_empty_desc', 'The archive for this period is currently empty.') ?></p>
        </div>
    <?php else: ?>
        <div class="panel" style="padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
                <h3 style="margin:0; font-size: 18px; display: flex; align-items: center; gap: 8px; color: var(--text-main);">
                    <i class="las la-folder-open" style="color: var(--primary-color);"></i> 
                    <?= p_lang('messages', 'Messages') ?>: <?= htmlspecialchars($selected_month) ?>
                    <span style="background: var(--bg-hover); color: var(--text-muted); padding: 2px 8px; border-radius: 20px; font-size: 12px; margin-left: 8px; border: 1px solid var(--border-color);"><?= count($messages) ?> <?= p_lang('items', 'items') ?></span>
                </h3>
                <form method="post" action="<?= $base_url ?>/messages" onsubmit="return confirm('<?= p_lang('purge_confirm', 'Are you strictly sure you want to permanently delete all archived messages for this month? This action cannot be undone.') ?>');">
                    <?= generate_csrf_field() ?>
                    <input type="hidden" name="action" value="clear_month">
                    <input type="hidden" name="month_to_clear" value="<?= htmlspecialchars($selected_month) ?>">
                    <button type="submit" class="btn btn-secondary" style="color:var(--danger-color); border-color:var(--danger-color); background: transparent;"><i class="las la-trash-can"></i> <?= p_lang('purge_archive', 'Purge Archive') ?></button>
                </form>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($messages as $msg): ?>
                    <div style="border: 1px solid <?= !(isset($msg['read']) && $msg['read']) ? 'var(--success-color)' : 'var(--border-color)' ?>; border-radius: 12px; padding: 24px; transition: all 0.2s; background: <?= !(isset($msg['read']) && $msg['read']) ? 'var(--success-bg)' : 'var(--bg-surface)' ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
                            <div style="display: flex; gap: 16px; align-items: center; min-width: 200px; flex-wrap: wrap;">
                                <div style="width: 48px; height: 48px; border-radius: 50%; background: <?= !(isset($msg['read']) && $msg['read']) ? 'var(--primary-color)' : 'var(--bg-hover)' ?>; color: <?= !(isset($msg['read']) && $msg['read']) ? '#fff' : 'var(--text-muted)' ?>; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px; box-shadow: var(--shadow-sm);">
                                    <?= strtoupper(substr($msg['name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <div>
                                    <h4 style="margin: 0 0 6px 0; font-size: 17px; color: var(--text-main); <?= !(isset($msg['read']) && $msg['read']) ? 'font-weight: 700;' : 'font-weight: 600;' ?>">
                                        <?= htmlspecialchars($msg['name'] ?? p_lang('unknown_sender', 'Unknown Sender')) ?>
                                        <?php if(!(isset($msg['read']) && $msg['read'])): ?>
                                            <span style="background: var(--success-color); color: white; font-size: 10px; padding: 2px 6px; border-radius: 4px; margin-left: 6px; vertical-align: middle; text-transform: uppercase; font-weight: bold;"><?= p_lang('new', 'New') ?></span>
                                        <?php endif; ?>
                                    </h4>
                                    <div style="display: flex; gap: 12px; font-size: 13px; color: var(--text-muted); align-items: center; flex-wrap: wrap;">
                                        <a href="mailto:<?= htmlspecialchars($msg['email'] ?? '') ?>" style="color: var(--primary-color); text-decoration: none; font-weight: 500;"><i class="lar la-envelope"></i> <?= htmlspecialchars($msg['email'] ?? '-') ?></a>
                                        <span>&bull;</span>
                                        <span><i class="lar la-clock"></i> <?= htmlspecialchars($msg['date'] ?? '') ?></span>
                                        <span>&bull;</span>
                                        <span style="font-family: monospace; font-size: 12px; background: var(--bg-hover); padding: 2px 6px; border-radius: 4px; border: 1px solid var(--border-color);"><i class="las la-globe"></i> <?= htmlspecialchars($msg['ip'] ?? 'Unknown') ?></span>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <?php if (!(isset($msg['read']) && $msg['read'])): ?>
                                    <a href="<?= $base_url ?>/messages?month=<?= $selected_month ?>&action=read&id=<?= urlencode($msg['id']) ?>&token=<?= generate_csrf_token() ?>" class="btn btn-secondary" style="padding: 8px 14px; font-size: 13px; font-weight: 500;"><i class="las la-check"></i> <?= p_lang('mark_read', 'Mark Read') ?></a>
                                <?php endif; ?>
                                <a href="<?= $base_url ?>/messages?month=<?= $selected_month ?>&action=delete&id=<?= urlencode($msg['id']) ?>&token=<?= generate_csrf_token() ?>" class="btn btn-secondary" style="padding: 8px 14px; font-size: 13px; color: var(--danger-color); border-color: var(--danger-color); background: var(--danger-bg);" onclick="return confirm('<?= p_lang('delete_confirm', 'Are you sure you want to delete this specific message permanently?') ?>');"><i class="las la-trash"></i></a>
                            </div>
                        </div>
                        <div style="padding: 20px; background: var(--bg-hover); border-radius: 8px; font-size: 14.5px; line-height: 1.7; color: var(--text-main); white-space: pre-wrap; border: 1px solid var(--border-color);"><?= htmlspecialchars($msg['message'] ?? '') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
