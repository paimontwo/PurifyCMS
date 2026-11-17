<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

require_once __DIR__ . '/../inc/config.php';
$base_url = rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path;
$lang = PurifyKernel::get_active_lang();
$locales_file = __DIR__ . '/../inc/data/locales/admin_' . $lang . '.json';

$error = '';
$success = '';

// --- AJAX Handlers ---
if (isset($_GET['ajax']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'CSRF Token mismatch.']);
        exit;
    }

    $rawData = file_get_contents($locales_file);
    $data = json_decode($rawData, true);
    
    // Integrity Check: Prevent overwriting if decode failed but file was not empty
    if ($data === null && !empty($rawData)) {
        echo json_encode(['success' => false, 'message' => 'Integrity Error: Translation file is corrupted. Please repair JSON manually.']);
        exit;
    }
    $data = $data ?: [];

    if ($_GET['ajax'] === 'save') {
        $key = $_POST['key'] ?? '';
        $val = $_POST['value'] ?? '';
        if ($key) {
            $data[$key] = $val;
            if (file_put_contents($locales_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX)) {
                PurifyVolt::set('lang_' . $lang, $data, 3600);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Write error.']);
            }
        }
    } elseif ($_GET['ajax'] === 'delete') {
        $key = $_POST['key'] ?? '';
        if (isset($data[$key])) {
            unset($data[$key]);
            if (file_put_contents($locales_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX)) {
                PurifyVolt::set('lang_' . $lang, $data, 3600);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Write error.']);
            }
        }
    }
    exit;
}

$admin_page_title = p_lang('locale_management', 'Locale Management');
$admin_page_subtitle = p_lang('locale_subtitle', 'Master translation registry for your enterprise platform.');
$admin_active_nav = 'locales';
require __DIR__ . '/includes/header.php';

$translations = json_decode(file_get_contents($locales_file), true);
if ($translations === null) {
    echo '<div class="alert alert-danger" style="margin:20px; border-radius:12px; background:rgba(239,68,68,0.1); color:var(--danger-color); padding:20px; border:1px solid rgba(239,68,68,0.2);">
        <h4 style="margin:0 0 10px 0;"><i class="las la-exclamation-triangle"></i> Registry Corruption Detected</h4>
        <p style="margin:0; font-size:14px; opacity:0.8;">The translation registry file (admin_'.$lang.'.json) contains invalid JSON syntax. Direct edits are disabled to prevent data loss. Please contact system administrator.</p>
    </div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
ksort($translations);
?>

<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 16px; flex-wrap: wrap;">
        <div style="position: relative; flex: 1; min-width: 300px;">
            <i class="las la-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            <input type="text" id="localeSearch" placeholder="<?= p_lang('search_keys_placeholder', 'Search translation keys or content...') ?>" class="form-control" style="padding-left: 38px;">
        </div>
        <div style="display: flex; gap: 12px;">
            <button class="btn btn-secondary" onclick="window.location.reload()">
                <i class="las la-sync"></i> <?= p_lang('refresh', 'Refresh') ?>
            </button>
            <button class="btn btn-primary" onclick="showAddModal()">
                <i class="las la-plus"></i> <?= p_lang('add_new_key', 'Add New Key') ?>
            </button>
        </div>
    </div>

    <div class="admin-table-wrapper">
        <div class="table-responsive" style="max-height: 70vh; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius);">
            <table class="table" style="margin-bottom: 0;">
                <thead style="position: sticky; top: 0; background: var(--bg-surface); z-index: 1;">
                    <tr>
                        <th style="width: 30%; border-top: none;"><?= p_lang('registry_key', 'Registry Key') ?></th>
                        <th style="border-top: none;"><?= p_lang('translation_value', 'Translation Value') ?></th>
                        <th style="width: 100px; text-align: right; border-top: none;"><?= p_lang('actions', 'Actions') ?></th>
                    </tr>
                </thead>
                <tbody id="localeTableBody">
                    <?php foreach ($translations as $key => $val): ?>
                    <tr data-key="<?= htmlspecialchars($key) ?>">
                        <td>
                            <code style="font-size: 11px; opacity: 0.8;"><?= htmlspecialchars($key) ?></code>
                        </td>
                        <td>
                            <div class="locale-val-display" onclick="enableEdit(this)" style="padding: 8px; border-radius: 6px; cursor: text; min-height: 38px; border: 1px solid transparent; transition: all 0.2s;">
                                <?= htmlspecialchars($val) ?>
                            </div>
                            <textarea class="form-control locale-edit-input" style="display: none; height: 38px; min-height: 38px;" onblur="saveEdit(this)"><?= htmlspecialchars($val) ?></textarea>
                        </td>
                        <td style="text-align: right;">
                            <button class="btn btn-icon btn-ghost-danger" onclick="deleteKey('<?= addslashes($key) ?>')" title="Delete">
                                <i class="las la-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Key Modal -->
<div id="addLocaleModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(4px); z-index: 10000; justify-content: center; align-items: center;">
    <div class="panel" style="width: 100%; max-width: 500px; box-shadow: var(--shadow-xl);">
        <h3 style="margin-top: 0;"><?= p_lang('add_new_registry', 'Add New Translation Registry') ?></h3>
        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">Ensure the key is lowercase and contains only underscores/letters.</p>
        
        <div class="input-group">
            <label>Key Name</label>
            <input type="text" id="newLocaleKey" class="form-control" placeholder="e.g. system_welcome_msg">
        </div>
        <div class="input-group">
            <label>Default Value</label>
            <textarea id="newLocaleValue" class="form-control" rows="3" placeholder="Enter translation text..."></textarea>
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
            <button class="btn btn-secondary" onclick="hideAddModal()"><?= p_lang('cancel', 'Cancel') ?></button>
            <button class="btn btn-primary" onclick="submitNewLocale()"><?= p_lang('save_key', 'Save Registry') ?></button>
        </div>
    </div>
</div>

<script>
const csrf_token = '<?= generate_csrf_token() ?>';

function enableEdit(el) {
    const td = el.closest('td');
    const input = td.querySelector('.locale-edit-input');
    el.style.display = 'none';
    input.style.display = 'block';
    input.focus();
}

async function saveEdit(input) {
    const td = input.closest('td');
    const display = td.querySelector('.locale-val-display');
    const tr = input.closest('tr');
    const key = tr.dataset.key;
    const value = input.value;

    if (value === display.innerText) {
        display.style.display = 'block';
        input.style.display = 'none';
        return;
    }

    display.innerText = value;
    display.style.display = 'block';
    input.style.display = 'none';
    display.style.opacity = '0.5';

    const formData = new FormData();
    formData.append('csrf_token', csrf_token);
    formData.append('key', key);
    formData.append('value', value);

    try {
        const response = await fetch('?ajax=save', {
            method: 'POST',
            body: formData
        });
        const res = await response.json();
        if (res.success) {
            display.style.opacity = '1';
            display.style.background = 'rgba(34, 197, 94, 0.1)';
            setTimeout(() => display.style.background = 'transparent', 2000);
        } else {
            alert(res.message);
        }
    } catch (e) {
        alert('Network Error');
    }
}

async function deleteKey(key) {
    if (!confirm('Are you sure you want to delete this translation key? This might break parts of the UI.')) return;

    const formData = new FormData();
    formData.append('csrf_token', csrf_token);
    formData.append('key', key);

    try {
        const response = await fetch('?ajax=delete', {
            method: 'POST',
            body: formData
        });
        const res = await response.json();
        if (res.success) {
            document.querySelector(`tr[data-key="${key}"]`).remove();
        } else {
            alert(res.message);
        }
    } catch (e) {
        alert('Network Error');
    }
}

function showAddModal() {
    document.getElementById('addLocaleModal').style.display = 'flex';
}

function hideAddModal() {
    document.getElementById('addLocaleModal').style.display = 'none';
}

async function submitNewLocale() {
    const key = document.getElementById('newLocaleKey').value.trim();
    const val = document.getElementById('newLocaleValue').value.trim();
    
    if (!key || !val) return alert('Key and Value are required.');

    const formData = new FormData();
    formData.append('csrf_token', csrf_token);
    formData.append('key', key);
    formData.append('value', val);

    try {
        const response = await fetch('?ajax=save', {
            method: 'POST',
            body: formData
        });
        const res = await response.json();
        if (res.success) {
            window.location.reload();
        } else {
            alert(res.message);
        }
    } catch (e) {
        alert('Network Error');
    }
}

// Search Logic
document.getElementById('localeSearch').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#localeTableBody tr');
    rows.forEach(row => {
        const key = row.dataset.key.toLowerCase();
        const val = row.querySelector('.locale-val-display').innerText.toLowerCase();
        if (key.includes(search) || val.includes(search)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<style>
.locale-val-display:hover {
    background: var(--bg-hover);
    border-color: var(--border-color);
}
.btn-ghost-danger {
    color: var(--danger-color);
    background: transparent;
    border: none;
}
.btn-ghost-danger:hover {
    background: rgba(239, 68, 68, 0.1);
}
.table td {
    vertical-align: middle;
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
