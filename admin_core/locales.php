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
                PurifyVolt::flushHtml();
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
                PurifyVolt::flushHtml();
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Write error.']);
            }
        }
    }
    exit;
}

$admin_page_title = p_lang('locale_management', 'Translations');
$admin_page_subtitle = p_lang('locale_subtitle', 'Manage interface translations for the administration panel.');
$admin_active_nav = 'locales';
require __DIR__ . '/includes/header.php';

$translations = json_decode(file_get_contents($locales_file), true);
if ($translations === null) {
    echo '<div class="notice notice-error">
        <i class="las la-exclamation-triangle"></i>
        <span>The translation file (admin_'.$lang.'.json) contains invalid JSON syntax. Please check the file syntax.</span>
    </div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
ksort($translations);
?>

<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 16px; flex-wrap: wrap;">
        <div style="position: relative; flex: 1; min-width: 260px;">
            <i class="las la-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            <input type="text" id="localeSearch" placeholder="<?= p_lang('search_keys_placeholder', 'Search translation keys or content...') ?>" class="form-control" style="padding-left: 38px;">
        </div>
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span id="localeCountBadge" class="badge badge-primary" style="font-size: 12px; padding: 6px 10px;">
                <?= count($translations) ?> <?= p_lang('keys', 'keys') ?>
            </span>
            <button class="btn btn-secondary" onclick="window.location.reload()">
                <i class="las la-sync"></i> <?= p_lang('refresh', 'Refresh') ?>
            </button>
            <button class="btn btn-primary" onclick="showAddModal()">
                <i class="las la-plus"></i> <?= p_lang('add_new_key', 'Add New Key') ?>
            </button>
        </div>
    </div>

    <div class="admin-table-wrapper">
        <div class="table-responsive" style="max-height: 66vh; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius);">
            <table class="table" style="margin-bottom: 0;">
                <thead style="position: sticky; top: 0; background: var(--bg-surface); z-index: 1;">
                    <tr>
                        <th style="width: 30%; border-top: none;"><?= p_lang('registry_key', 'Key') ?></th>
                        <th style="border-top: none;"><?= p_lang('translation_value', 'Translation Value') ?></th>
                        <th style="width: 80px; text-align: right; border-top: none;"><?= p_lang('actions', 'Actions') ?></th>
                    </tr>
                </thead>
                <tbody id="localeTableBody"></tbody>
            </table>
        </div>
    </div>

    <div id="localePagination" style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; gap: 12px; flex-wrap: wrap; font-size: 13px; color: var(--text-muted);">
        <span id="localePageInfo"></span>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button type="button" id="localePrevBtn" class="btn btn-sm btn-secondary" onclick="changeLocalePage(-1)">
                <i class="las la-angle-left"></i> <?= p_lang('previous', 'Previous') ?>
            </button>
            <button type="button" id="localeNextBtn" class="btn btn-sm btn-secondary" onclick="changeLocalePage(1)">
                <?= p_lang('next', 'Next') ?> <i class="las la-angle-right"></i>
            </button>
        </div>
    </div>
</div>

<!-- Add Key Modal -->
<div id="addLocaleModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 10000; justify-content: center; align-items: center; padding: 20px;">
    <div class="panel" style="width: 100%; max-width: 480px; margin-bottom: 0;">
        <h3 style="margin-top: 0; font-size: 17px;"><?= p_lang('add_new_registry', 'Add Translation Key') ?></h3>
        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">Use lowercase letters and underscores for the key name.</p>
        
        <div class="input-group">
            <label>Key Name</label>
            <input type="text" id="newLocaleKey" class="form-control" placeholder="e.g. welcome_message">
        </div>
        <div class="input-group">
            <label>Translation Text</label>
            <textarea id="newLocaleValue" class="form-control" rows="3" placeholder="Enter translation text..."></textarea>
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
            <button class="btn btn-secondary" onclick="hideAddModal()"><?= p_lang('cancel', 'Cancel') ?></button>
            <button class="btn btn-primary" onclick="submitNewLocale()"><?= p_lang('save_key', 'Save Key') ?></button>
        </div>
    </div>
</div>

<script>
const csrf_token = '<?= generate_csrf_token() ?>';
const allTranslations = <?= json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const PAGE_SIZE = 50;
let filteredKeys = Object.keys(allTranslations);
let currentPage = 1;

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function renderLocaleTable() {
    const tbody = document.getElementById('localeTableBody');
    const total = filteredKeys.length;
    const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    if (currentPage > totalPages) currentPage = totalPages;

    const start = (currentPage - 1) * PAGE_SIZE;
    const end = Math.min(start + PAGE_SIZE, total);
    const slice = filteredKeys.slice(start, end);

    if (slice.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:32px; color:var(--text-muted);">No matching translation keys found.</td></tr>';
    } else {
        let html = '';
        for (let i = 0; i < slice.length; i++) {
            const key = slice[i];
            const val = allTranslations[key] ?? '';
            const safeKey = escapeHtml(key);
            html += `<tr data-key="${safeKey}">
                <td><code style="font-size: 11px; opacity: 0.85;">${safeKey}</code></td>
                <td><div class="locale-val-display" onclick="enableEdit(this)">${escapeHtml(val)}</div></td>
                <td style="text-align: right;">
                    <button type="button" class="btn btn-icon btn-ghost-danger" onclick="deleteKey(this)" title="Delete">
                        <i class="las la-trash"></i>
                    </button>
                </td>
            </tr>`;
        }
        tbody.innerHTML = html;
    }

    document.getElementById('localeCountBadge').textContent = total + ' <?= addslashes(p_lang('keys', 'keys')) ?>';
    document.getElementById('localePageInfo').textContent = total > 0
        ? `Showing ${start + 1}–${end} of ${total} (Page ${currentPage} / ${totalPages})`
        : 'Showing 0 results';
    document.getElementById('localePrevBtn').disabled = currentPage <= 1;
    document.getElementById('localeNextBtn').disabled = currentPage >= totalPages;
}

function changeLocalePage(delta) {
    currentPage += delta;
    renderLocaleTable();
    const scroller = document.querySelector('.admin-table-wrapper .table-responsive');
    if (scroller) scroller.scrollTop = 0;
}

function enableEdit(el) {
    const td = el.closest('td');
    const tr = el.closest('tr');
    const key = tr.dataset.key;
    if (td.querySelector('.locale-edit-input')) return;

    const input = document.createElement('textarea');
    input.className = 'form-control locale-edit-input';
    input.style.minHeight = '38px';
    input.style.height = Math.max(38, el.offsetHeight) + 'px';
    input.value = allTranslations[key] ?? '';
    input.addEventListener('blur', function() { saveEdit(input, el, key); });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            input.remove();
            el.style.display = 'block';
        }
    });

    el.style.display = 'none';
    td.appendChild(input);
    input.focus();
}

async function saveEdit(input, display, key) {
    const value = input.value;
    const prevValue = allTranslations[key] ?? '';

    input.remove();
    display.style.display = 'block';

    if (value === prevValue) return;

    display.textContent = value;
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
            allTranslations[key] = value;
            display.style.opacity = '1';
            display.style.background = 'rgba(34, 197, 94, 0.1)';
            setTimeout(() => display.style.background = 'transparent', 1800);
        } else {
            display.textContent = prevValue;
            display.style.opacity = '1';
            alert(res.message);
        }
    } catch (e) {
        display.textContent = prevValue;
        display.style.opacity = '1';
        alert('Network Error');
    }
}

async function deleteKey(btn) {
    const tr = btn.closest('tr');
    const key = tr.dataset.key;
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
            delete allTranslations[key];
            filteredKeys = filteredKeys.filter(k => k !== key);
            renderLocaleTable();
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
            allTranslations[key] = val;
            document.getElementById('newLocaleKey').value = '';
            document.getElementById('newLocaleValue').value = '';
            hideAddModal();
            applyLocaleFilter(document.getElementById('localeSearch').value);
        } else {
            alert(res.message);
        }
    } catch (e) {
        alert('Network Error');
    }
}

function applyLocaleFilter(query) {
    const search = (query || '').trim().toLowerCase();
    const keys = Object.keys(allTranslations).sort();
    if (!search) {
        filteredKeys = keys;
    } else {
        filteredKeys = keys.filter(k => k.toLowerCase().includes(search) || String(allTranslations[k] ?? '').toLowerCase().includes(search));
    }
    currentPage = 1;
    renderLocaleTable();
}

let searchTimer = null;
document.getElementById('localeSearch').addEventListener('input', function(e) {
    clearTimeout(searchTimer);
    const q = e.target.value;
    searchTimer = setTimeout(() => applyLocaleFilter(q), 80);
});

renderLocaleTable();
</script>

<style>
.locale-val-display {
    padding: 8px;
    border-radius: 6px;
    cursor: text;
    min-height: 38px;
    border: 1px solid transparent;
    transition: background 0.15s ease, border-color 0.15s ease;
}
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
