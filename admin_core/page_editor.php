<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

require_once __DIR__ . '/../inc/config.php';

$page_key = $_GET['page'] ?? '';
$page_key = preg_replace('/[^a-zA-Z0-9_-]/', '', $page_key);

if (empty($page_key)) {
    header('Location: ' . rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path . '/pages');
    exit;
}

$admin_page_title = 'Edit Page: ' . ucfirst($page_key);
$admin_active_nav = 'pages';

require __DIR__ . '/includes/header.php';
?>

<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
        <h2 style="margin:0; font-size: 18px;"><i class="las la-edit"></i> Visual Editor</h2>
        <div style="display: flex; gap: 10px;">
            <a href="<?= htmlspecialchars($base_url . '/pages') ?>" class="btn btn-sm">

                <i class="las la-arrow-left"></i> Back to List
            </a>

            <button id="savePageBtn" class="btn btn-primary btn-sm">
                <i class="las la-save"></i> Save Changes
            </button>
        </div>
    </div>

    <div id="editor-loading" style="text-align: center; padding: 40px;">
        <i class="las la-spinner la-spin" style="font-size: 32px; color: var(--primary-color);"></i>
        <p style="margin-top: 10px; color: var(--text-muted);">Loading page content...</p>
    </div>

    <div id="editor-canvas" style="display: none;">
        <div class="row" style="margin-bottom: 20px;">
            <div class="col-md-6" style="margin-bottom: 15px;">
                <label class="form-label" style="font-weight: 600;"><?= p_lang('page_title', 'Page Title ($title)') ?></label>
                <input type="text" id="pageTitle" class="form-control" placeholder="The title shown in the browser tab">
            </div>
            <div class="col-md-6">
                <label class="form-label" style="font-weight: 600;"><?= p_lang('meta_desc', 'Meta Description ($desc)') ?></label>
                <input type="text" id="pageDesc" class="form-control" placeholder="A brief summary for search engines">
            </div>
        </div>

        <!-- SEO Preview Panel -->
        <div class="panel" style="margin-bottom: 25px; border: 1px dashed var(--border-color); background: rgba(99, 102, 241, 0.05); border-radius: 12px; padding: 20px;">
            <h3 style="margin-top:0; margin-bottom: 15px; font-size: 13px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700;">
                <i class="las la-magnifying-glass"></i> <?= p_lang('seo_preview', 'Search Engine Preview') ?>
            </h3>
            <div id="seo-google-preview" style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e1e1e1; max-width: 600px; font-family: arial, sans-serif; text-align: left;">
                <div id="preview-url" style="color: #202124; font-size: 14px; margin-bottom: 4px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <?= htmlspecialchars(rtrim($cfg_mainurl, '/')) ?> › <?= htmlspecialchars($page_key) ?>
                </div>
                <div id="preview-title" style="color: #1a0dab; font-size: 20px; line-height: 1.3; margin-bottom: 3px; cursor: pointer; text-decoration: none;">
                    Page Title
                </div>
                <div id="preview-desc" style="color: #4d5156; font-size: 14px; line-height: 1.58; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                    Page description will appear here...
                </div>
            </div>
            <div style="margin-top: 10px; font-size: 12px; color: var(--text-muted);">
                <i class="las la-info-circle"></i> <?= p_lang('seo_help', 'This is a simulation of how your page might appear in Google Search results.') ?>
            </div>
        </div>

        <div id="zones-container">
            <!-- Dynamic editors will be injected here -->
        </div>
    </div>
</div>

<!-- TinyMCE Local -->
<script src="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/js/tinymce/tinymce.min.js"></script>

<script>
const PAGE_KEY = '<?= $page_key ?>';
const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?? '' ?>';
const API_URL = 'editor_api';

let activeZones = [];

// Initialize Editors
function initZoneEditors(zones) {
    activeZones = zones;
    const container = document.getElementById('zones-container');
    container.innerHTML = '';

    if (zones.length === 0) {
        container.innerHTML = '<div style="padding: 30px; text-align: center; background: #fff5f5; color: #c53030; border-radius: 8px; border: 1px solid #feb2b2;">' +
                              '<i class="las la-exclamation-triangle"></i> No editable zones found on this page.<br>' +
                              '<small>Make sure to add markers like &lt;!-- purify:zone:name_start --&gt; in your theme.</small></div>';
        document.getElementById('editor-loading').style.display = 'none';
        document.getElementById('editor-canvas').style.display = 'block';
        return;
    }

    zones.forEach((zone, index) => {
        const zoneId = `zone_editor_${index}`;
        const card = document.createElement('div');
        card.className = 'panel';
        card.style.marginBottom = '25px';
        card.style.border = '1px solid var(--border-color)';
        card.style.boxShadow = 'none';
        
        card.innerHTML = `
            <div style="padding: 10px 15px; background: #f8fafc; border-bottom: 1px solid var(--border-color); font-weight: 700; font-size: 12px; color: #64748b; display: flex; align-items: center; justify-content: space-between;">
                <span><i class="las la-pen-nib"></i> ZONE: ${zone.name.toUpperCase().replace(/_/g, ' ')}</span>
                <span style="opacity: 0.5;">#${zone.name}</span>
            </div>
            <textarea id="${zoneId}">${zone.content}</textarea>
        `;
        container.appendChild(card);

        tinymce.init({
            selector: `#${zoneId}`,
            height: 400,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
            toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | image link code',
            content_style: 'body { font-family: -apple-system, sans-serif; font-size: 15px; line-height: 1.6; padding: 20px; }',
            branding: false,
            promotion: false,
            protect: [
                /<\?php[\s\S]*?\?>/g,
                /<\?=[\s\S]*?\?>/g
            ],
            skin: (document.documentElement.getAttribute('data-theme') === 'dark' ? 'oxide-dark' : 'oxide'),
            content_css: (document.documentElement.getAttribute('data-theme') === 'dark' ? '<?= rtrim($cfg_mainurl, '/') ?>/adminassets/css/tinymce-dark.css' : '<?= rtrim($cfg_mainurl, '/') ?>/adminassets/css/tinymce-default.css'),
            setup: function(editor) {
                editor.on('init', function() {
                    if (index === zones.length - 1) {
                        document.getElementById('editor-loading').style.display = 'none';
                        document.getElementById('editor-canvas').style.display = 'block';
                    }
                });
            }
        });
    });
}

// SEO Preview Sync
const pageTitleInput = document.getElementById('pageTitle');
const pageDescInput = document.getElementById('pageDesc');
const previewTitle = document.getElementById('preview-title');
const previewDesc = document.getElementById('preview-desc');

function updateSEOPreview() {
    previewTitle.innerText = pageTitleInput.value || 'Page Title';
    previewDesc.innerText = pageDescInput.value || 'Page description will appear here...';
}

pageTitleInput.addEventListener('input', updateSEOPreview);
pageDescInput.addEventListener('input', updateSEOPreview);

// Load Content
fetch(`${API_URL}?action=read&page=${PAGE_KEY}`)
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('pageTitle').value = res.data.title;
            document.getElementById('pageDesc').value = res.data.desc;
            updateSEOPreview();
            initZoneEditors(res.data.zones);
        } else {
            alert('Error loading page: ' + res.message);
        }
    });

// Save Changes
document.getElementById('savePageBtn').addEventListener('click', function() {
    const btn = this;
    const oldHtml = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="las la-spinner la-spin"></i> Saving...';
    
    // Collect zone data
    const zonesToSave = activeZones.map((z, index) => {
        return {
            name: z.name,
            content: tinymce.get(`zone_editor_${index}`).getContent()
        };
    });

    const formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('title', document.getElementById('pageTitle').value);
    formData.append('desc', document.getElementById('pageDesc').value);
    formData.append('zones', JSON.stringify(zonesToSave));

    fetch(`${API_URL}?action=save&page=${PAGE_KEY}`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            btn.classList.add('btn-success');
            btn.innerHTML = '<i class="las la-check"></i> All Saved!';
            setTimeout(() => {
                btn.classList.remove('btn-success');
                btn.innerHTML = oldHtml;
                btn.disabled = false;
            }, 2000);
        } else {
            alert('Error saving: ' + res.message);
            btn.innerHTML = oldHtml;
            btn.disabled = false;
        }
    });
});
</script>


<?php require __DIR__ . '/includes/footer.php'; ?>
