<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

require_once __DIR__ . '/../inc/config.php';

if (!isset($cfg_enable_filemanager) || !$cfg_enable_filemanager) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$admin_page_title = 'File Manager';
$admin_page_subtitle = 'Browse and edit theme files.';
$admin_active_nav = 'filemanager';
require __DIR__ . '/includes/header.php';

$base_url = rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path;
$api_url = $base_url . '/filemanager_api';
?>

<link rel="stylesheet" href="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/vendor/codemirror/lib/codemirror.css">
<link rel="stylesheet" href="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/vendor/codemirror/theme/material-darker.css">

<style>
    .fm-container {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 0;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        overflow: hidden;
        height: 700px;
    }
    .fm-sidebar {
        background: var(--bg-surface);
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }
    .fm-sidebar-header {
        padding: 16px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .fm-tree-container {
        flex: 1;
        overflow-y: auto;
        padding: 12px 8px;
    }
    .fm-editor-area {
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }
    .fm-editor-header {
        padding: 12px 20px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--bg-surface);
    }
    
    .tree-item {
        user-select: none;
        cursor: pointer;
        font-size: 13px;
        border-radius: 6px;
        transition: all 0.15s;
    }
    .tree-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        color: var(--text-muted);
    }
    .tree-row:hover {
        background: var(--bg-hover);
        color: var(--text-main);
    }
    .tree-row.active {
        background: rgba(99, 102, 241, 0.1);
        color: #6366f1;
        font-weight: 600;
    }
    .tree-indent {
        padding-left: 18px;
        border-left: 1px solid rgba(255,255,255,0.05);
        margin-left: 8px;
        display: none;
    }
    .tree-indent.expanded {
        display: block;
    }
    .folder-toggle {
        width: 16px;
        height: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        transition: transform 0.2s;
    }
    .expanded > .tree-row > .folder-toggle {
        transform: rotate(90deg);
    }
    
    .CodeMirror {
        height: 100% !important;
        background: #0f172a !important;
        color: #f8fafc !important;
        font-family: 'JetBrains Mono', 'Fira Code', monospace;
    }
    .cm-s-material-darker.CodeMirror {
        color: #f8fafc;
    }
    .CodeMirror-gutters {
        background: #1e293b !important;
        border-right: 1px solid rgba(255,255,255,0.05);
    }
</style>

<div class="fm-container">
    <!-- Sidebar -->
    <div class="fm-sidebar">
        <div class="fm-sidebar-header">
            <div style="display:flex; align-items:center; gap:8px; font-weight:700; color:var(--text-main); font-size:14px;">
                <i class="las la-folder-tree" style="color:#6366f1;"></i> Explorer
            </div>
            <button type="button" class="btn btn-secondary btn-sm" id="refreshTreeBtn" style="padding:4px 8px;">
                <i class="las la-rotate"></i>
            </button>
        </div>
        
        <div style="padding: 10px; display:flex; gap:6px;">
             <button class="btn btn-secondary btn-sm" id="newFolderBtn" title="New Folder" style="flex:1;"><i class="las la-folder-plus"></i></button>
             <button class="btn btn-secondary btn-sm" id="newFileBtn" title="New File" style="flex:1;"><i class="las la-file-medical"></i></button>
             <label class="btn btn-secondary btn-sm" style="flex:1; cursor:pointer;" title="Upload">
                  <i class="las la-upload"></i>
                  <input type="file" id="uploadInput" style="display:none;" />
             </label>
        </div>

        <div class="fm-tree-container" id="treeRoot">
            <div style="padding:20px; text-align:center; color:var(--text-muted); font-size:12px;">
                <i class="las la-spinner la-spin"></i> Loading tree...
            </div>
        </div>
    </div>

    <div class="fm-editor-area">
        <div class="fm-editor-header">
            <div id="activeFileTitle">
                <div style="font-weight: 600; font-size: 14px;" id="fileNameDisplay">Select a file</div>
                <div style="font-size: 11px; color: var(--text-muted);" id="filePathDisplay">No file selected</div>
            </div>
            <div style="display:flex; gap: 8px;">
                <button type="button" class="btn btn-secondary btn-sm" id="renameBtn" disabled><i class="las la-i-cursor"></i> Rename</button>
                <button type="button" class="btn btn-secondary btn-sm" id="deleteBtn" disabled style="color: var(--danger-color);"><i class="las la-trash"></i> Delete</button>
                <button type="button" class="btn btn-primary btn-sm" id="saveBtn" disabled style="padding:8px 20px;"><i class="las la-save"></i> Save Changes</button>
            </div>
        </div>
        
        <div style="flex:1; position:relative; overflow:hidden;">
            <textarea id="editor" style="display:none;"></textarea>
            <div id="noFileOverlay" style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:16px; background:var(--bg-surface); z-index:10;">
                <div style="font-size:48px; color:var(--border-color);"><i class="las la-code"></i></div>
                <div style="color:var(--text-muted); font-size:14px;">Open a file from the explorer to begin editing</div>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal (Unchanged but adapted) -->
<div id="imageModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.8); backdrop-filter:blur(5px); z-index:9999; align-items:center; justify-content:center; flex-direction:column; gap:20px;">
    <div style="background:var(--bg-surface); padding:30px; border-radius:var(--radius-lg); box-shadow:var(--shadow-float); text-align:center; position:relative; max-width:90%; max-height:90vh; display:flex; flex-direction:column;">
        <button type="button" onclick="closeImageModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:20px; color:var(--text-muted); cursor:pointer;"><i class="las la-xmark"></i></button>
        <h3 id="modalImgTitle" style="margin:0 0 15px 0; font-size:16px; word-break:break-all;"></h3>
        <div style="flex:1; overflow:hidden; display:flex; align-items:center; justify-content:center; background:#f1f5f9; border-radius:8px; padding:10px;">
            <img id="modalImg" src="" style="max-width:100%; max-height:60vh; border-radius:4px; box-shadow:var(--shadow-sm);">
        </div>
        <div style="margin-top:20px; display:flex; justify-content:center; gap:10px; flex-wrap:wrap;">
            <button type="button" class="btn btn-primary" id="modalConvertWebpBtn"><i class="las la-magic"></i> Convert to WebP</button>
            <button type="button" class="btn btn-secondary" id="modalDeleteBtn" style="color:var(--danger-color); border-color:rgba(239, 68, 68, 0.2);"><i class="las la-trash"></i> Delete</button>
            <button type="button" class="btn btn-secondary" onclick="closeImageModal()">Close</button>
        </div>
    </div>
</div>

<script src="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/vendor/codemirror/lib/codemirror.js"></script>
<script src="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/vendor/codemirror/mode/xml/xml.js"></script>
<script src="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/vendor/codemirror/mode/javascript/javascript.js"></script>
<script src="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/vendor/codemirror/mode/css/css.js"></script>
<script src="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/vendor/codemirror/mode/clike/clike.js"></script>
<script src="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/vendor/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script src="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/vendor/codemirror/mode/php/php.js"></script>
<script src="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/vendor/codemirror/mode/markdown/markdown.js"></script>

<script>
const API_URL = 'filemanager_api';
const CSRF_TOKEN = <?= json_encode(generate_csrf_token()) ?>;
const BASE_DOMAIN = <?= json_encode(rtrim($cfg_mainurl, "/")) ?>;

let activeFile = null;
let currentImageFile = null;
let cm = null;
let expandedFolders = new Set();

async function apiJson(action, payload) {
    const res = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ csrf_token: CSRF_TOKEN, action, ...payload })
    });
    return res.json();
}

function getFileIcon(name, type) {
    if (type === 'dir') return 'la-folder';
    const ext = name.split('.').pop().toLowerCase();
    switch(ext) {
        case 'php': return 'lab la-php';
        case 'json': return 'las la-code';
        case 'css': return 'lab la-css3-alt';
        case 'js': return 'lab la-js';
        case 'md': return 'lar la-file-alt';
        case 'jpg': case 'jpeg': case 'png': case 'webp': case 'svg': return 'las la-image';
        default: return 'lar la-file';
    }
}

function renderTree(items, container, depth = 0) {
    container.innerHTML = '';
    items.forEach(item => {
        const itemEl = document.createElement('div');
        itemEl.className = 'tree-item' + (expandedFolders.has(item.path) ? ' expanded' : '');
        itemEl.dataset.path = item.path;

        const row = document.createElement('div');
        row.className = 'tree-row' + (activeFile === item.path ? ' active' : '');
        
        const icon = getFileIcon(item.name, item.type);
        const iconColor = item.type === 'dir' ? '#f59e0b' : (icon.includes('php') ? '#6366f1' : 'inherit');

        row.innerHTML = `
            ${item.type === 'dir' ? '<i class="las la-chevron-right folder-toggle"></i>' : '<span style="width:16px;"></span>'}
            <i class="${icon}" style="color:${iconColor}; width:16px;"></i>
            <span>${item.name}</span>
        `;

        row.onclick = (e) => {
            e.stopPropagation();
            if (item.type === 'dir') {
                const indent = itemEl.querySelector('.tree-indent');
                if (expandedFolders.has(item.path)) {
                    expandedFolders.delete(item.path);
                    itemEl.classList.remove('expanded');
                    indent.classList.remove('expanded');
                } else {
                    expandedFolders.add(item.path);
                    itemEl.classList.add('expanded');
                    indent.classList.add('expanded');
                }
            } else {
                openFile(item.path);
            }
        };

        itemEl.appendChild(row);

        if (item.type === 'dir' && item.children) {
            const indent = document.createElement('div');
            indent.className = 'tree-indent' + (expandedFolders.has(item.path) ? ' expanded' : '');
            renderTree(item.children, indent, depth + 1);
            itemEl.appendChild(indent);
        }

        container.appendChild(itemEl);
    });
}

async function loadTree() {
    const data = await apiJson('get_tree', {});
    if (data.ok) {
        const root = document.getElementById('treeRoot');
        renderTree(data.tree, root);
        // If we had an active file, highlight it
        if (activeFile) {
            const rows = root.querySelectorAll('.tree-row');
            rows.forEach(r => {
                if (r.parentElement.dataset.path === activeFile) r.classList.add('active');
                else r.classList.remove('active');
            });
        }
    }
}

async function openFile(path) {
    const isImg = /\.(jpg|jpeg|png|webp|gif|svg)$/i.test(path);
    if (isImg) {
        showImageModal(path);
        return;
    }

    const r = await apiJson('read', { path });
    if (!r.ok) {
        alert(r.error || 'Read failed');
        return;
    }

    activeFile = path;
    document.getElementById('noFileOverlay').style.display = 'none';
    document.getElementById('fileNameDisplay').textContent = path.split('/').pop();
    document.getElementById('filePathDisplay').textContent = 'themes/' + path;
    
    document.getElementById('saveBtn').disabled = false;
    document.getElementById('renameBtn').disabled = false;
    document.getElementById('deleteBtn').disabled = false;

    if (cm) {
        cm.setValue(r.content || '');
        const mode = cmModeForPath(path);
        cm.setOption('mode', mode);
        cm.refresh();
        cm.focus();
    }
    
    // Highlight in tree
    const rows = document.querySelectorAll('.tree-row');
    rows.forEach(r => {
        if (r.parentElement.dataset.path === path) r.classList.add('active');
        else r.classList.remove('active');
    });
}

function cmModeForPath(path) {
    const ext = path.split('.').pop().toLowerCase();
    switch (ext) {
        case 'php': return 'application/x-httpd-php';
        case 'html': return 'text/html';
        case 'css': return 'text/css';
        case 'js': return 'text/javascript';
        case 'json': return 'application/json';
        case 'md': return 'text/x-markdown';
        default: return null;
    }
}

// ... Modal Functions ...
function showImageModal(path) {
    currentImageFile = path;
    document.getElementById('modalImgTitle').innerText = path;
    document.getElementById('modalImg').src = BASE_DOMAIN + '/themes/' + path + '?t=' + Date.now();
    document.getElementById('modalConvertWebpBtn').style.display = /\.(jpg|jpeg|png)$/i.test(path) ? 'inline-flex' : 'none';
    document.getElementById('imageModal').style.display = 'flex';
}
function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
    currentImageFile = null;
}

// Events
document.getElementById('refreshTreeBtn').onclick = loadTree;

document.getElementById('saveBtn').onclick = async () => {
    if (!activeFile) return;
    const content = cm.getValue();
    const r = await apiJson('save', { path: activeFile, content });
    if (r.ok) {
        // Show subtle notification instead of alert if possible, but for now:
        const originalText = document.getElementById('saveBtn').innerHTML;
        document.getElementById('saveBtn').innerHTML = '<i class="las la-check"></i> Saved';
        setTimeout(() => document.getElementById('saveBtn').innerHTML = originalText, 2000);
    } else {
        alert(r.error || 'Save failed');
    }
};

document.getElementById('renameBtn').onclick = async () => {
    if (!activeFile) return;
    const base = activeFile.split('/').pop();
    const next = prompt('New name:', base);
    if (!next) return;
    const folder = activeFile.split('/').slice(0, -1).join('/');
    const to = (folder ? (folder + '/') : '') + next;
    const r = await apiJson('rename', { from: activeFile, to });
    if (r.ok) {
        activeFile = to;
        await loadTree();
        await openFile(to);
    } else {
        alert(r.error || 'Rename failed');
    }
};

document.getElementById('deleteBtn').onclick = async () => {
    if (!activeFile) return;
    if (!confirm('Delete this file permanently?')) return;
    const r = await apiJson('delete', { path: activeFile });
    if (r.ok) {
        activeFile = null;
        document.getElementById('noFileOverlay').style.display = 'flex';
        document.getElementById('saveBtn').disabled = true;
        document.getElementById('renameBtn').disabled = true;
        document.getElementById('deleteBtn').disabled = true;
        loadTree();
    } else {
        alert(r.error || 'Delete failed');
    }
};

document.getElementById('newFolderBtn').onclick = async () => {
    const parent = prompt('Folder name:', '');
    if (!parent) return;
    const r = await apiJson('mkdir', { path: '', name: parent });
    if (r.ok) loadTree();
    else alert(r.error || 'Error');
};

document.getElementById('newFileBtn').onclick = async () => {
    const name = prompt('File name (e.g. style.css):', '');
    if (!name) return;
    const r = await apiJson('newfile', { path: '', name: name });
    if (r.ok) loadTree();
    else alert(r.error || 'Error');
};

document.getElementById('uploadInput').onchange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('action', 'upload');
    fd.append('path', '');
    fd.append('file', file);
    const res = await fetch(API_URL, { method: 'POST', body: fd });
    const r = await res.json();
    if (r.ok) loadTree();
    else alert(r.error || 'Upload failed');
};

// Init
cm = CodeMirror.fromTextArea(document.getElementById('editor'), {
    lineNumbers: true,
    lineWrapping: true,
    theme: 'material-darker',
    mode: null,
    indentUnit: 4,
    tabSize: 4
});

loadTree().then(() => {
    const urlParams = new URL(window.location.href).searchParams;
    const editPath = urlParams.get('edit');
    if (editPath) {
        // Expand parents
        const parts = editPath.split('/');
        let current = '';
        for(let i=0; i<parts.length-1; i++) {
            current += (current ? '/' : '') + parts[i];
            expandedFolders.add(current);
        }
        loadTree().then(() => openFile(editPath));
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
