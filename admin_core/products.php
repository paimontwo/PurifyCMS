<?php
defined('DIR_ACCESS') or exit('No direct script access allowed');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    require __DIR__ . '/login.php';
    exit;
}

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/PurifyTrade.php';

$products_file = __DIR__ . '/../inc/data/ecommerce/products.json';
$vault_dir = __DIR__ . '/../inc/data/ecommerce/vault';
if (!is_dir(dirname($products_file))) @mkdir(dirname($products_file), 0755, true);
if (!is_dir($vault_dir)) @mkdir($vault_dir, 0755, true);

$products = file_exists($products_file) ? json_decode(file_get_contents($products_file), true) : [];
$success = '';
$error = '';

// Handle Actions
$action = $_GET['action'] ?? 'list';

// Wizard Step Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die('CSRF failed');
    
    // Wizard Final Submit
    if ($action === 'wizard' && isset($_POST['wizard_complete'])) {
        $id = $_POST['id'] ?? uniqid('prod_');
        $type = $_POST['type'] ?? 'physical';
        $title = htmlspecialchars($_POST['title'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $tax_rate = (float)($_POST['tax_rate'] ?? 20);
        $currency = strtoupper($_POST['currency'] ?? 'TRY');
        $stock = (int)($_POST['stock'] ?? 999);
        $description = $_POST['description'] ?? '';
        $featured = isset($_POST['featured']) ? true : false;
        $old_price = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
        
        // Slug Logic
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $_POST['slug'] ?? '')));
        if (empty($slug)) $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $title)));
        if (empty($slug)) $slug = $id;

        // Check for duplicate slugs
        foreach ($products as $p_id => $p_data) {
            if ($p_id !== $id && ($p_data['slug'] ?? '') === $slug) {
                $slug .= '-' . substr(uniqid(), -3);
                break;
            }
        }
        
        // Handle Variations
        $variation_groups = [];
        if (isset($_POST['group_title'])) {
            foreach ($_POST['group_title'] as $g_idx => $g_title) {
                if (!empty($g_title)) {
                    $group_options = [];
                    if (isset($_POST['opt_name'][$g_idx])) {
                        foreach ($_POST['opt_name'][$g_idx] as $o_idx => $o_name) {
                            if (!empty($o_name)) {
                                $group_options[] = [
                                    'name' => htmlspecialchars($o_name),
                                    'price' => (float)$_POST['opt_price'][$g_idx][$o_idx]
                                ];
                            }
                        }
                    }
                    $variation_groups[] = [
                        'title' => htmlspecialchars($g_title),
                        'type' => $_POST['group_type'][$g_idx] ?? 'single',
                        'options' => $group_options
                    ];
                }
            }
        }

        // Handle File Upload (Digital Products)
        $file_name = '';
        if ($type === 'digital' && isset($_FILES['delivery_file']) && $_FILES['delivery_file']['error'] === UPLOAD_ERR_OK) {
            $file_name = time() . '_dl_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['delivery_file']['name']);
            move_uploaded_file($_FILES['delivery_file']['tmp_name'], $vault_dir . '/' . $file_name);
        } elseif (isset($_POST['existing_file'])) {
            $file_name = $_POST['existing_file'];
        }

        // Handle Product Image
        $prod_image = '';
        if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] === UPLOAD_ERR_OK) {
            $prod_image = time() . '_img_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['prod_image']['name']);
            move_uploaded_file($_FILES['prod_image']['tmp_name'], $vault_dir . '/' . $prod_image);
        } elseif (isset($_POST['existing_image'])) {
            $prod_image = $_POST['existing_image'];
        }

        $products[$id] = [
            'id' => $id,
            'type' => $type,
            'slug' => $slug,
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'old_price' => $old_price,
            'tax_rate' => $tax_rate,
            'currency' => $currency,
            'stock' => $stock,
            'featured' => $featured,
            'rating' => (float)($_POST['rating'] ?? 5.0),
            'reviews_count' => (int)($_POST['reviews_count'] ?? rand(5, 50)),
            'file' => $file_name,
            'image' => $prod_image,
            'variation_groups' => $variation_groups,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($products_file, json_encode($products, JSON_PRETTY_PRINT), LOCK_EX);
        header('Location: ' . rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path . '/products?success=1');
        exit;
    } elseif ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? uniqid('prod_');
        $type = $_POST['type'] ?? 'physical';
        $title = htmlspecialchars($_POST['title']);
        $price = (float)$_POST['price'];
        $tax_rate = (float)($_POST['tax_rate'] ?? 20);
        $currency = strtoupper($_POST['currency'] ?: 'TRY');
        $stock = (int)$_POST['stock'];
        $description = $_POST['description'] ?? '';
        $featured = isset($_POST['featured']) ? true : false;
        $old_price = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
        
        // Slug Logic
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $_POST['slug'] ?: $title)));
        if (empty($slug)) $slug = $id;

        // Check for duplicate slugs (except current product)
        foreach ($products as $p_id => $p_data) {
            if ($p_id !== $id && ($p_data['slug'] ?? '') === $slug) {
                $slug .= '-' . substr(uniqid(), -3);
                break;
            }
        }
        
        // Handle Grouped Variations
        $variation_groups = [];
        if (isset($_POST['group_title'])) {
            foreach ($_POST['group_title'] as $g_idx => $g_title) {
                if (!empty($g_title)) {
                    $group_options = [];
                    if (isset($_POST['opt_name'][$g_idx])) {
                        foreach ($_POST['opt_name'][$g_idx] as $o_idx => $o_name) {
                            if (!empty($o_name)) {
                                $group_options[] = [
                                    'name' => htmlspecialchars($o_name),
                                    'price' => (float)$_POST['opt_price'][$g_idx][$o_idx]
                                ];
                            }
                        }
                    }
                    $variation_groups[] = [
                        'title' => htmlspecialchars($g_title),
                        'type' => $_POST['group_type'][$g_idx] ?? 'single',
                        'options' => $group_options
                    ];
                }
            }
        }

        $file_name = $_POST['existing_file'] ?? '';
        if (isset($_FILES['delivery_file']) && $_FILES['delivery_file']['error'] === UPLOAD_ERR_OK) {
            $file_name = time() . '_dl_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['delivery_file']['name']);
            move_uploaded_file($_FILES['delivery_file']['tmp_name'], $vault_dir . '/' . $file_name);
        }

        $prod_image = $_POST['existing_image'] ?? '';
        if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] === UPLOAD_ERR_OK) {
            $prod_image = time() . '_img_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['prod_image']['name']);
            move_uploaded_file($_FILES['prod_image']['tmp_name'], $vault_dir . '/' . $prod_image);
        }

        $products[$id] = [
            'id' => $id,
            'type' => $type,
            'slug' => $slug,
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'old_price' => $old_price,
            'tax_rate' => $tax_rate,
            'currency' => $currency,
            'stock' => $stock,
            'featured' => $featured,
            'rating' => (float)($_POST['rating'] ?? 5.0),
            'reviews_count' => (int)($_POST['reviews_count'] ?? rand(5, 50)),
            'file' => $file_name,
            'image' => $prod_image,
            'variation_groups' => $variation_groups,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($products_file, json_encode($products, JSON_PRETTY_PRINT), LOCK_EX);
        header('Location: ' . rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path . '/products?success=1');
        exit;
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    unset($products[$_GET['id']]);
    file_put_contents($products_file, json_encode($products, JSON_PRETTY_PRINT), LOCK_EX);
    header('Location: ' . rtrim($cfg_mainurl, '/') . '/' . $cfg_admin_path . '/products?success=deleted');
    exit;
}

$admin_page_title = p_lang('product_management', 'Product Management');
$admin_active_nav = 'products';
require __DIR__ . '/includes/header.php';
?>

<script src="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/js/tinymce/tinymce.min.js"></script>

<div class="panel" style="max-width: 1200px; margin: 0 auto; border-top: 6px solid var(--primary-color);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; border-bottom: 1px solid #f1f5f9; padding-bottom: 25px;">
        <div>
            <h2 style="margin:0; font-size: 28px; font-weight: 800; letter-spacing: -0.02em;"><i class="las la-box-open" style="color: var(--primary-color);"></i> <?= ($action === 'edit') ? p_lang('edit_product', 'Edit Product') : p_lang('add_product', 'Add New Product') ?></h2>
            <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.6;"><?= p_lang('product_meta_desc', 'Deploy premium configurations and SEO identifiers.') ?></p>
        </div>
        <?php if ($action === 'list'): ?>
            <div style="display: flex; gap: 12px;">
                <a href="?action=wizard" class="btn btn-primary" style="padding: 12px 28px; font-weight: 800;">
                    <i class="las la-magic"></i> <?= p_lang('wizard_add', 'Wizard') ?>
                </a>
                <a href="?action=add" class="btn btn-secondary" style="padding: 12px 28px; font-weight: 700;">
                    <i class="las la-plus"></i> <?= p_lang('add_product', 'Quick Add') ?>
                </a>
            </div>
        <?php else: ?>
            <a href="products" class="btn btn-secondary" style="padding: 12px 28px; font-weight: 700;"><?= p_lang('product_list', 'Inventory Overview') ?></a>
        <?php endif; ?>
    </div>

    <?php if ($action === 'list'): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="80"><?= p_lang('media', 'Media') ?></th>
                        <th><?= p_lang('product_title', 'Hierarchy & Identity') ?></th>
                        <th><?= p_lang('product_price', 'Price Net') ?></th>
                        <th><?= p_lang('seo_slug', 'SEO Slug') ?></th>
                        <th style="text-align:right;"><?= p_lang('operations', 'Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr style="vertical-align: middle;">
                            <td>
                                <div style="width: 60px; height: 60px; border-radius: 12px; background: #f8fafc; overflow: hidden; display: flex; align-items: center; justify-content: center; font-size: 28px; color: #cbd5e1; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 2px solid #fff;">
                                    <?php if (!empty($p['image'])): ?>
                                        <img src="<?= rtrim($cfg_mainurl, '/') ?>/inc/data/ecommerce/vault/<?= $p['image'] ?>" style="width:100%; height:100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="las la-image"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 800; color: #1e293b; font-size: 15px;"><?= $p['title'] ?></div>
                                <span class="badge" style="background: <?= $p['type'] === 'digital' ? 'rgba(99, 102, 241, 0.1); color: #4f46e5;' : 'rgba(16, 185, 129, 0.1); color: #059669;' ?>; margin-top: 5px;"><?= strtoupper($p['type']) ?></span>
                            </td>
                            <td style="font-weight: 800; font-size: 16px; color: var(--primary-color);">
                                <?= number_format($p['price'], 2) ?> <small style="font-weight: 500; opacity: 0.5; font-size: 12px;"><?= $p['currency'] ?></small>
                            </td>
                            <td>
                                <code style="font-size: 11px; background: #f1f5f9; padding: 4px 8px; border-radius: 6px;">/product/<?= $p['slug'] ?? $p['id'] ?></code>
                            </td>
                            <td style="text-align:right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <button type="button" class="btn-icon" style="background: rgba(79, 70, 229, 0.05); color:var(--primary-color); display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: none; border-radius: 8px; cursor: pointer;" title="<?= p_lang('edit', 'Edit') ?>" onclick="window.location.href='?action=edit&id=<?= $p['id'] ?>'">
                                        <i class="las la-pen-nib"></i> <?= p_lang('edit', 'Edit') ?>
                                    </button>
                                    <button type="button" class="btn-icon" style="background: rgba(239, 68, 68, 0.05); color:var(--danger-color); display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: none; border-radius: 8px; cursor: pointer;" onclick="if(confirm('<?= p_lang('delete_confirm', 'Archive product permanently?') ?>')) { window.location.href='?action=delete&id=<?= $p['id'] ?>'; }" title="<?= p_lang('delete', 'Delete') ?>">
                                        <i class="las la-trash-alt"></i> <?= p_lang('delete', 'Delete') ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; if (empty($products)) echo '<tr><td colspan="6" style="text-align:center; padding: 120px; opacity:0.2;"><h1>Grand Opening</h1><p>The marketplace awaits its first elite product.</p></td></tr>'; ?>
                </tbody>
            </table>
        </div>
    <?php else: 
        $edit = ($action === 'edit' && isset($_GET['id'])) ? ($products[$_GET['id']] ?? null) : null;
        
        // WIZARD MODE
        if ($action === 'wizard'):
    ?>
        <!-- PRODUCT WIZARD -->
        <form method="post" enctype="multipart/form-data" id="wizardForm">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="wizard_complete" value="1">
            
            <!-- Progress Bar -->
            <div class="wizard-progress">
                <div class="progress-line"><div class="progress-fill" id="progressFill" style="width: 20%;"></div></div>
                <div class="progress-steps">
                    <div class="step active" data-step="1"><div class="step-number">1</div><div class="step-label"><?= p_lang('step_type', 'Type') ?></div></div>
                    <div class="step" data-step="2"><div class="step-number">2</div><div class="step-label"><?= p_lang('step_basic', 'Basic Info') ?></div></div>
                    <div class="step" data-step="3"><div class="step-number">3</div><div class="step-label"><?= p_lang('step_delivery', 'Delivery') ?></div></div>
                    <div class="step" data-step="4"><div class="step-number">4</div><div class="step-label"><?= p_lang('step_options', 'Options') ?></div></div>
                    <div class="step" data-step="5"><div class="step-number">5</div><div class="step-label"><?= p_lang('step_preview', 'Preview') ?></div></div>
                </div>
            </div>

            <!-- STEP 1: Product Type -->
            <div class="wizard-step active" data-step="1">
                <div class="step-header">
                    <h3><i class="las la-question-circle"></i> <?= p_lang('choose_type', 'What type of product?') ?></h3>
                    <p><?= p_lang('type_desc', 'Select the type that best describes your product') ?></p>
                </div>
                <div class="type-selector">
                    <label class="type-card" onclick="selectType('digital')">
                        <input type="radio" name="type" value="digital" id="typeDigital">
                        <div class="type-content">
                            <div class="type-icon digital"><i class="las la-cloud-download-alt"></i></div>
                            <h4><?= p_lang('digital_product', 'Digital Product') ?></h4>
                            <p><?= p_lang('digital_desc', 'Software, e-books, courses, or any downloadable file.') ?></p>
                        </div>
                        <div class="type-selected"><i class="las la-check-circle"></i></div>
                    </label>
                    <label class="type-card" onclick="selectType('physical')">
                        <input type="radio" name="type" value="physical" id="typePhysical">
                        <div class="type-content">
                            <div class="type-icon physical"><i class="las la-box"></i></div>
                            <h4><?= p_lang('physical_product', 'Physical Product') ?></h4>
                            <p><?= p_lang('physical_desc', 'Items that need shipping. Books, merchandise, hardware.') ?></p>
                        </div>
                        <div class="type-selected"><i class="las la-check-circle"></i></div>
                    </label>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn btn-primary btn-lg" onclick="nextStep(2)" id="btnStep1" disabled><?= p_lang('continue', 'Continue') ?> <i class="las la-arrow-right"></i></button>
                </div>
            </div>

            <!-- STEP 2: Basic Information -->
            <div class="wizard-step" data-step="2">
                <div class="step-header">
                    <h3><i class="las la-info-circle"></i> <?= p_lang('basic_info', 'Basic Information') ?></h3>
                </div>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label><?= p_lang('product_name', 'Product Name') ?> *</label>
                        <input type="text" name="title" id="prodTitle" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?= p_lang('price', 'Price') ?> *</label>
                        <div class="price-input">
                            <input type="number" step="0.01" name="price" id="prodPrice" class="form-control" required>
                            <select name="currency" class="form-control currency-select"><option value="TRY">TRY</option><option value="USD">USD</option><option value="EUR">EUR</option></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= p_lang('old_price', 'Old Price (for discount)') ?></label>
                        <input type="number" step="0.01" name="old_price" class="form-control" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label><?= p_lang('tax_rate', 'Tax Rate (%)') ?></label>
                        <input type="number" step="0.1" name="tax_rate" class="form-control" value="20">
                    </div>
                    <div class="form-group">
                        <label><?= p_lang('stock', 'Stock Quantity') ?></label>
                        <input type="number" name="stock" class="form-control" value="999">
                    </div>
                    <div class="form-group full-width">
                        <label class="checkbox-label"><input type="checkbox" name="featured" value="1"><span><?= p_lang('featured', 'Featured Product') ?></span></label>
                    </div>
                    <div class="form-group">
                        <label><?= p_lang('product_rating', 'Star Rating (1-5)') ?></label>
                        <input type="number" step="0.1" min="1" max="5" name="rating" class="form-control" value="5.0">
                    </div>
                    <div class="form-group">
                        <label><?= p_lang('reviews_count', 'Reviews Count (Display only)') ?></label>
                        <input type="number" name="reviews_count" class="form-control" value="<?= rand(15, 85) ?>">
                    </div>
                    <div class="form-group full-width">
                        <label><?= p_lang('description', 'Description') ?></label>
                        <textarea name="description" id="prodDescription" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label><?= p_lang('product_image', 'Product Image') ?></label>
                        <div class="image-upload" id="imageUpload">
                            <div class="upload-preview" id="imagePreview"><i class="las la-cloud-upload-alt"></i><span><?= p_lang('drag_drop', 'Click to upload image') ?></span></div>
                            <input type="file" name="prod_image" id="prodImageInput" accept="image/*" style="display: none;">
                        </div>
                    </div>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn btn-secondary" onclick="prevStep(1)"><i class="las la-arrow-left"></i> <?= p_lang('back', 'Back') ?></button>
                    <button type="button" class="btn btn-primary" onclick="nextStep(3)"><?= p_lang('continue', 'Continue') ?> <i class="las la-arrow-right"></i></button>
                </div>
            </div>

            <!-- STEP 3: Delivery / File Upload -->
            <div class="wizard-step" data-step="3">
                <div class="step-header">
                    <h3><i class="las la-shipping-fast"></i> <?= p_lang('delivery_setup', 'Delivery Setup') ?></h3>
                    <p id="deliveryDesc"><?= p_lang('delivery_digital_desc', 'Upload the file customers will receive') ?></p>
                </div>
                <div id="digitalUpload" class="upload-section" style="display: none;">
                    <div class="file-upload-area" id="fileUploadArea">
                        <div class="upload-icon"><i class="las la-file-archive"></i></div>
                        <h4><?= p_lang('upload_file', 'Upload Delivery File') ?></h4>
                        <p><?= p_lang('upload_desc', 'ZIP, PDF, or any file type.') ?></p>
                        <input type="file" name="delivery_file" id="deliveryFileInput" style="display: none;">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('deliveryFileInput').click()"><i class="las la-folder-open"></i> <?= p_lang('choose_file', 'Choose File') ?></button>
                        <div class="file-info" id="fileInfo" style="display: none;"><i class="las la-check-circle"></i><span id="fileName"></span><small id="fileSize"></small></div>
                    </div>
                </div>
                <div id="physicalSettings" class="upload-section" style="display: none;">
                    <div class="info-card"><div class="info-icon"><i class="las la-info-circle"></i></div><div class="info-content"><h4><?= p_lang('physical_setup', 'Physical Product Setup') ?></h4><p><?= p_lang('physical_info', 'Physical products require shipping. Customer will provide delivery address during checkout.') ?></p></div></div>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn btn-secondary" onclick="prevStep(2)"><i class="las la-arrow-left"></i> <?= p_lang('back', 'Back') ?></button>
                    <button type="button" class="btn btn-primary" onclick="nextStep(4)"><?= p_lang('continue', 'Continue') ?> <i class="las la-arrow-right"></i></button>
                </div>
            </div>

            <!-- STEP 4: Variations -->
            <div class="wizard-step" data-step="4">
                <div class="step-header">
                    <h3><i class="las la-cogs"></i> <?= p_lang('variations', 'Variations & Options') ?></h3>
                    <p><?= p_lang('variations_desc', 'Add options like size, color (optional)') ?></p>
                </div>
                <div class="variations-section">
                    <div id="variationsList"></div>
                    <button type="button" class="btn btn-secondary btn-add-variation" onclick="addVariation()"><i class="las la-plus"></i> <?= p_lang('add_variation', 'Add Variation Group') ?></button>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn btn-secondary" onclick="prevStep(3)"><i class="las la-arrow-left"></i> <?= p_lang('back', 'Back') ?></button>
                    <button type="button" class="btn btn-primary" onclick="generatePreview()"><?= p_lang('preview', 'Preview Product') ?> <i class="las la-eye"></i></button>
                </div>
            </div>

            <!-- STEP 5: Preview & Save -->
            <div class="wizard-step" data-step="5">
                <div class="step-header">
                    <h3><i class="las la-check-circle"></i> <?= p_lang('review_save', 'Review & Save') ?></h3>
                </div>
                <div class="preview-card">
                    <div class="preview-header">
                        <span class="preview-badge" id="previewType">Digital</span>
                        <h2 id="previewTitle">Product Title</h2>
                        <div class="preview-price" id="previewPrice">0.00 TRY</div>
                    </div>
                    <div class="preview-body">
                        <div class="preview-image" id="previewImage"><i class="las la-image"></i></div>
                        <div class="preview-details">
                            <div class="preview-section"><label><?= p_lang('description', 'Description') ?></label><div id="previewDescription" class="preview-content">-</div></div>
                            <div class="preview-section" id="previewVariations" style="display: none;"><label><?= p_lang('variations', 'Variations') ?></label><div id="previewVariationsList">-</div></div>
                            <div class="preview-section" id="previewFileSection"><label><?= p_lang('delivery_file', 'Delivery File') ?></label><div id="previewFile">-</div></div>
                        </div>
                    </div>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn btn-secondary" onclick="prevStep(4)"><i class="las la-arrow-left"></i> <?= p_lang('back_edit', 'Back to Edit') ?></button>
                    <button type="submit" class="btn btn-success btn-lg"><i class="las la-save"></i> <?= p_lang('save_product', 'Save Product') ?></button>
                </div>
            </div>
        </form>

        <style>
        .wizard-progress { margin-bottom: 40px; }
        .progress-line { height: 4px; background: #e2e8f0; border-radius: 2px; position: relative; margin-bottom: 20px; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, var(--primary-color), #818cf8); border-radius: 2px; transition: width 0.4s ease; }
        .progress-steps { display: flex; justify-content: space-between; }
        .step { display: flex; flex-direction: column; align-items: center; gap: 8px; opacity: 0.4; transition: all 0.3s; }
        .step.active, .step.completed { opacity: 1; }
        .step.completed .step-number { background: #10b981; color: white; }
        .step-number { width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; transition: all 0.3s; }
        .step.active .step-number { background: var(--primary-color); color: white; transform: scale(1.1); }
        .step-label { font-size: 12px; font-weight: 600; color: #64748b; white-space: nowrap; }
        .step.active .step-label { color: var(--primary-color); }
        .wizard-step { display: none; animation: fadeIn 0.4s ease; }
        .wizard-step.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .step-header { text-align: center; margin-bottom: 30px; }
        .step-header h3 { font-size: 24px; margin-bottom: 8px; }
        .step-header p { color: #64748b; }
        .step-actions { display: flex; justify-content: space-between; margin-top: 40px; padding-top: 30px; border-top: 1px solid #e2e8f0; }
        .type-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin: 30px 0; }
        @media (max-width: 768px) { .type-selector { grid-template-columns: 1fr; } }
        .type-card { position: relative; border: 2px solid #e2e8f0; border-radius: 16px; padding: 30px; cursor: pointer; transition: all 0.3s; }
        .type-card:hover { border-color: var(--primary-color); transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .type-card input { position: absolute; opacity: 0; }
        .type-card:has(input:checked) { border-color: var(--primary-color); background: rgba(99, 102, 241, 0.02); }
        .type-card:has(input:checked) .type-selected { opacity: 1; }
        .type-content { text-align: center; }
        .type-icon { width: 80px; height: 80px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 36px; margin: 0 auto 20px; }
        .type-icon.digital { background: rgba(99, 102, 241, 0.1); color: var(--primary-color); }
        .type-icon.physical { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .type-content h4 { font-size: 20px; margin-bottom: 10px; }
        .type-content > p { color: #64748b; font-size: 14px; margin-bottom: 20px; }
        .type-selected { position: absolute; top: 15px; right: 15px; color: var(--primary-color); font-size: 24px; opacity: 0; transition: opacity 0.3s; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 8px; color: #374151; }
        .price-input { display: flex; gap: 10px; }
        .price-input input { flex: 1; }
        .currency-select { width: 100px; }
        .checkbox-label { display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 15px; background: #f8fafc; border-radius: 12px; border: 2px solid transparent; transition: all 0.3s; }
        .checkbox-label:hover { border-color: var(--primary-color); }
        .checkbox-label input { width: 20px; height: 20px; accent-color: var(--primary-color); }
        .checkbox-label span { font-weight: 600; }
        .image-upload { border: 2px dashed #e2e8f0; border-radius: 16px; padding: 40px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .image-upload:hover { border-color: var(--primary-color); background: rgba(99, 102, 241, 0.02); }
        .upload-preview { display: flex; flex-direction: column; align-items: center; gap: 10px; color: #94a3b8; }
        .upload-preview i { font-size: 48px; }
        .upload-preview.has-image { color: var(--primary-color); }
        .upload-preview img { max-width: 200px; max-height: 200px; border-radius: 12px; }
        .file-upload-area { border: 2px dashed #e2e8f0; border-radius: 16px; padding: 50px; text-align: center; transition: all 0.3s; }
        .file-upload-area:hover { border-color: var(--primary-color); background: rgba(99, 102, 241, 0.02); }
        .upload-icon { width: 80px; height: 80px; border-radius: 20px; background: rgba(99, 102, 241, 0.1); color: var(--primary-color); display: flex; align-items: center; justify-content: center; font-size: 36px; margin: 0 auto 20px; }
        .file-info { margin-top: 20px; padding: 15px; background: rgba(16, 185, 129, 0.1); border-radius: 12px; color: #059669; display: flex; align-items: center; gap: 10px; justify-content: center; }
        .info-card { display: flex; gap: 20px; padding: 30px; background: #f8fafc; border-radius: 16px; border-left: 4px solid #10b981; }
        .info-icon { font-size: 32px; color: #10b981; }
        .info-content h4 { margin-bottom: 8px; }
        .info-content p { color: #64748b; font-size: 14px; margin: 0; }
        .variations-section { background: #f8fafc; border-radius: 16px; padding: 24px; }
        .btn-add-variation { width: 100%; margin-top: 20px; padding: 15px; }
        .variation-group-box { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 16px; }
        .variation-group-header { display: grid; grid-template-columns: 1fr auto auto; gap: 12px; margin-bottom: 16px; }
        .variation-options-list { display: flex; flex-direction: column; gap: 10px; padding-left: 16px; border-left: 3px solid #e2e8f0; }
        .variation-option-row { display: grid; grid-template-columns: 1fr 120px 40px; gap: 10px; }
        .preview-card { background: white; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; }
        .preview-header { padding: 30px; border-bottom: 1px solid #e2e8f0; text-align: center; }
        .preview-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 15px; }
        .preview-badge.digital { background: rgba(99, 102, 241, 0.1); color: var(--primary-color); }
        .preview-badge.physical { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .preview-header h2 { font-size: 28px; margin-bottom: 10px; }
        .preview-price { font-size: 32px; font-weight: 900; color: var(--primary-color); }
        .preview-body { display: grid; grid-template-columns: 300px 1fr; gap: 30px; padding: 30px; }
        @media (max-width: 768px) { .preview-body { grid-template-columns: 1fr; } }
        .preview-image { aspect-ratio: 1; background: #f8fafc; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 60px; color: #cbd5e1; }
        .preview-image img { width: 100%; height: 100%; object-fit: cover; border-radius: 16px; }
        .preview-details { display: flex; flex-direction: column; gap: 24px; }
        .preview-section label { font-weight: 700; font-size: 12px; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px; display: block; }
        .preview-content { color: #475569; line-height: 1.6; }
        .btn-lg { padding: 15px 40px; font-size: 16px; }
        .btn-success { background: #10b981; color: white; border: none; }
        .btn-success:hover { background: #059669; }
        </style>

        <script>
        let currentStep = 1, selectedType = '', variationCount = 0;
        function selectType(type) {
            selectedType = type;
            document.getElementById('btnStep1').disabled = false;
        }
        function nextStep(step) {
            if (step === 3 && !validateStep2()) return;
            document.querySelector(`.wizard-step[data-step="${currentStep}"]`).classList.remove('active');
            document.querySelector(`.progress-steps .step[data-step="${currentStep}"]`).classList.remove('active');
            document.querySelector(`.progress-steps .step[data-step="${currentStep}"]`).classList.add('completed');
            currentStep = step;
            document.querySelector(`.wizard-step[data-step="${currentStep}"]`).classList.add('active');
            document.querySelector(`.progress-steps .step[data-step="${currentStep}"]`).classList.add('active');
            document.getElementById('progressFill').style.width = `${(currentStep / 5) * 100}%`;
            if (step === 3) setupDeliveryStep();
        }
        function prevStep(step) {
            document.querySelector(`.wizard-step[data-step="${currentStep}"]`).classList.remove('active');
            document.querySelector(`.progress-steps .step[data-step="${currentStep}"]`).classList.remove('active');
            currentStep = step;
            document.querySelector(`.wizard-step[data-step="${currentStep}"]`).classList.add('active');
            document.querySelector(`.progress-steps .step[data-step="${currentStep}"]`).classList.add('active');
            document.getElementById('progressFill').style.width = `${(currentStep / 5) * 100}%`;
        }
        function validateStep2() {
            const title = document.getElementById('prodTitle').value.trim();
            const price = document.getElementById('prodPrice').value;
            if (!title) { alert('<?= p_lang('error_title', 'Please enter a product name') ?>'); document.getElementById('prodTitle').focus(); return false; }
            if (!price || price <= 0) { alert('<?= p_lang('error_price', 'Please enter a valid price') ?>'); document.getElementById('prodPrice').focus(); return false; }
            return true;
        }
        function setupDeliveryStep() {
            const digitalSection = document.getElementById('digitalUpload');
            const physicalSection = document.getElementById('physicalSettings');
            if (selectedType === 'digital') {
                digitalSection.style.display = 'block';
                physicalSection.style.display = 'none';
            } else {
                digitalSection.style.display = 'none';
                physicalSection.style.display = 'block';
            }
        }
        document.getElementById('imageUpload').addEventListener('click', () => document.getElementById('prodImageInput').click());
        document.getElementById('prodImageInput').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    preview.classList.add('has-image');
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        document.getElementById('deliveryFileInput').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                document.getElementById('fileInfo').style.display = 'flex';
            }
        });
        function addVariation() {
            const wrapper = document.getElementById('variationsList');
            const idx = variationCount++;
            const div = document.createElement('div');
            div.className = 'variation-group-box';
            div.innerHTML = `
                <div class="variation-group-header">
                    <input type="text" name="group_title[${idx}]" class="form-control" placeholder="<?= p_lang('variation_name', 'e.g., Size, Color') ?>">
                    <select name="group_type[${idx}]" class="form-control"><option value="single"><?= p_lang('single_choice', 'Single Choice') ?></option><option value="multi"><?= p_lang('multi_choice', 'Multi Choice') ?></option></select>
                    <button type="button" class="btn btn-icon" style="color: #ef4444;" onclick="this.closest('.variation-group-box').remove()"><i class="las la-trash-alt"></i></button>
                </div>
                <div class="variation-options-list" id="optionsList${idx}">
                    <button type="button" class="btn btn-secondary" style="font-size: 12px;" onclick="addOption(${idx})"><i class="las la-plus"></i> <?= p_lang('add_option', 'Add Option') ?></button>
                </div>
            `;
            wrapper.appendChild(div);
            setTimeout(() => addOption(idx), 100);
        }
        function addOption(groupIdx) {
            const list = document.getElementById(`optionsList${groupIdx}`);
            const btn = list.querySelector('.btn-secondary');
            const oIdx = list.querySelectorAll('.variation-option-row').length;
            const row = document.createElement('div');
            row.className = 'variation-option-row';
            row.innerHTML = `
                <input type="text" name="opt_name[${groupIdx}][${oIdx}]" class="form-control" placeholder="<?= p_lang('option_name', 'Option name') ?>">
                <input type="number" step="0.01" name="opt_price[${groupIdx}][${oIdx}]" class="form-control" placeholder="<?= p_lang('price_adjustment', 'Price +/-') ?>">
                <button type="button" class="btn btn-icon" onclick="this.closest('.variation-option-row').remove()"><i class="las la-times-circle"></i></button>
            `;
            list.insertBefore(row, btn);
        }
        function generatePreview() {
            const title = document.getElementById('prodTitle').value || '<?= p_lang('untitled', 'Untitled') ?>';
            const price = parseFloat(document.getElementById('prodPrice').value) || 0;
            const currency = document.querySelector('select[name="currency"]').value;
            const tax = parseFloat(document.querySelector('input[name="tax_rate"]').value) || 20;
            const priceWithTax = price * (1 + tax / 100);
            const desc = document.getElementById('prodDescription').value;
            
            document.getElementById('previewType').textContent = selectedType === 'digital' ? '<?= p_lang('digital', 'Digital') ?>' : '<?= p_lang('physical', 'Physical') ?>';
            document.getElementById('previewType').className = 'preview-badge ' + selectedType;
            document.getElementById('previewTitle').textContent = title;
            document.getElementById('previewPrice').textContent = priceWithTax.toFixed(2) + ' ' + currency;
            document.getElementById('previewDescription').textContent = desc || '<?= p_lang('no_description', 'No description') ?>';
            
            const imagePreview = document.getElementById('imagePreview');
            const previewImageDiv = document.getElementById('previewImage');
            if (imagePreview.querySelector('img')) {
                previewImageDiv.innerHTML = `<img src="${imagePreview.querySelector('img').src}" alt="${title}">`;
            }
            
            const variationsHtml = [];
            document.querySelectorAll('.variation-group-box').forEach(box => {
                const gTitle = box.querySelector('input[name^="group_title"]').value;
                if (gTitle) {
                    const options = [];
                    box.querySelectorAll('.variation-option-row').forEach(row => {
                        const optName = row.querySelector('input[name^="opt_name"]').value;
                        const optPrice = row.querySelector('input[name^="opt_price"]').value;
                        if (optName) options.push(`${optName} ${optPrice ? '(' + (optPrice > 0 ? '+' : '') + optPrice + ')' : ''}`);
                    });
                    if (options.length) variationsHtml.push(`<strong>${gTitle}:</strong> ${options.join(', ')}`);
                }
            });
            
            const variationsSection = document.getElementById('previewVariations');
            if (variationsHtml.length) {
                document.getElementById('previewVariationsList').innerHTML = variationsHtml.join('<br>');
                variationsSection.style.display = 'block';
            } else {
                variationsSection.style.display = 'none';
            }
            
            const fileSection = document.getElementById('previewFileSection');
            if (selectedType === 'digital') {
                const fileName = document.getElementById('fileName').textContent;
                document.getElementById('previewFile').textContent = fileName || '<?= p_lang('no_file', 'No file uploaded') ?>';
                fileSection.style.display = 'block';
            } else {
                fileSection.style.display = 'none';
            }
            
            nextStep(5);
        }
        </script>

    <?php else: ?>
        <?php $edit = ($action === 'edit' && isset($_GET['id'])) ? ($products[$_GET['id']] ?? null) : null; ?>
        
        <!-- MODERN ADD/EDIT FORM -->
        <form method="post" enctype="multipart/form-data" id="productForm">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 340px 1fr; gap: 40px;">
                <!-- Sidebar -->
                <div style="display: flex; flex-direction: column; gap: 25px;">
                    <!-- Product Image -->
                    <div class="panel" style="background: #f8fafc; border: 1px solid #f1f5f9; padding: 25px; border-radius: 16px;">
                        <h4 style="margin: 0 0 20px 0; font-size: 14px; color: #64748b; text-transform: uppercase; font-weight: 700;"><?= p_lang('product_image', 'Product Image') ?></h4>
                        <div class="image-upload-box" id="imageUploadBox" style="width: 100%; aspect-ratio: 1; border-radius: 16px; border: 2px dashed #cbd5e1; background: <?= ($edit && $edit['image']) ? 'url('.rtrim($cfg_mainurl, '/').'/inc/data/ecommerce/vault/'.$edit['image'].')' : '#fff' ?>; background-size: cover; background-position: center; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s;" onclick="document.getElementById('prodImageInput').click()">
                            <?php if (!$edit || !$edit['image']): ?>
                                <div style="text-align: center; color: #94a3b8;">
                                    <i class="las la-cloud-upload-alt" style="font-size: 48px; margin-bottom: 10px;"></i>
                                    <div style="font-size: 12px;"><?= p_lang('click_upload', 'Click to upload') ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="prod_image" id="prodImageInput" class="form-control" style="margin-top: 15px; font-size: 11px;" accept="image/*" onchange="previewImage(this)">
                        <?php if ($edit && $edit['image']): ?><input type="hidden" name="existing_image" value="<?= $edit['image'] ?>"><?php endif; ?>
                    </div>

                    <!-- Product Type -->
                    <div class="panel" style="background: #f8fafc; border: 1px solid #f1f5f9; padding: 25px; border-radius: 16px;">
                        <h4 style="margin: 0 0 20px 0; font-size: 14px; color: #64748b; text-transform: uppercase; font-weight: 700;"><?= p_lang('product_type', 'Product Type') ?></h4>
                        
                        <div class="type-toggle" style="display: flex; gap: 10px; margin-bottom: 20px;">
                            <label class="type-option <?= (!$edit || $edit['type'] === 'physical') ? 'active' : '' ?>" style="flex: 1; padding: 15px; border: 2px solid #e2e8f0; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.3s;" onclick="setType('physical')">
                                <input type="radio" name="type" value="physical" <?= (!$edit || $edit['type'] === 'physical') ? 'checked' : '' ?> style="display: none;">
                                <i class="las la-box" style="font-size: 28px; color: #10b981; margin-bottom: 8px; display: block;"></i>
                                <div style="font-weight: 700; font-size: 13px;"><?= p_lang('physical', 'Physical') ?></div>
                            </label>
                            <label class="type-option <?= ($edit && $edit['type'] === 'digital') ? 'active' : '' ?>" style="flex: 1; padding: 15px; border: 2px solid #e2e8f0; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.3s;" onclick="setType('digital')">
                                <input type="radio" name="type" value="digital" <?= ($edit && $edit['type'] === 'digital') ? 'checked' : '' ?> style="display: none;">
                                <i class="las la-cloud-download-alt" style="font-size: 28px; color: var(--primary-color); margin-bottom: 8px; display: block;"></i>
                                <div style="font-weight: 700; font-size: 13px;"><?= p_lang('digital', 'Digital') ?></div>
                            </label>
                        </div>
                        
                        <!-- Digital File Upload -->
                        <div id="digitalFileSection" style="display: <?= ($edit && $edit['type'] === 'digital') ? 'block' : 'none' ?>;">
                            <label style="font-weight: 700; font-size: 12px; margin-bottom: 8px; display: block; color: #64748b;"><?= p_lang('delivery_file', 'Delivery File') ?></label>
                            <div class="file-upload-mini" id="fileUploadMini" style="padding: 20px; border: 2px dashed #cbd5e1; border-radius: 12px; text-align: center; cursor: pointer;" onclick="document.getElementById('deliveryFileInput').click()">
                                <i class="las la-file-archive" style="font-size: 32px; color: var(--primary-color); margin-bottom: 8px;"></i>
                                <div style="font-size: 12px; color: #64748b;">
                                    <?php if ($edit && $edit['file']): ?>
                                        <span style="color: #10b981;"><i class="las la-check"></i> <?= $edit['file'] ?></span>
                                    <?php else: ?>
                                        <?= p_lang('upload_file', 'Upload file') ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <input type="file" name="delivery_file" id="deliveryFileInput" style="display: none;" onchange="updateFileName(this)">
                            <?php if ($edit && $edit['file']): ?><input type="hidden" name="existing_file" value="<?= $edit['file'] ?>"><?php endif; ?>
                        </div>
                        
                        <!-- Stock -->
                        <div class="input-group" style="margin-top: 20px;">
                            <label style="font-weight: 700; font-size: 12px; margin-bottom: 8px; display: block; color: #64748b;"><?= p_lang('stock_quantity', 'Stock Quantity') ?></label>
                            <input type="number" name="stock" class="form-control" style="font-weight: 700;" value="<?= $edit['stock'] ?? '999' ?>">
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="panel" style="background: #f8fafc; border: 1px solid #f1f5f9; padding: 25px; border-radius: 16px;">
                        <h4 style="margin: 0 0 20px 0; font-size: 14px; color: #64748b; text-transform: uppercase; font-weight: 700;"><?= p_lang('pricing', 'Pricing') ?></h4>
                        
                        <div class="input-group" style="margin-bottom: 15px;">
                            <label style="font-weight: 700; font-size: 12px; margin-bottom: 8px; display: block; color: #64748b;"><?= p_lang('current_price', 'Current Price') ?> *</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="number" step="0.01" name="price" class="form-control" style="flex: 1; font-weight: 800; font-size: 18px; color: var(--primary-color);" value="<?= $edit['price'] ?? '' ?>" required>
                                <select name="currency" class="form-control" style="width: 80px; font-weight: 700;">
                                    <option value="TRY" <?= ($edit && $edit['currency'] === 'TRY') ? 'selected' : '' ?>>TRY</option>
                                    <option value="USD" <?= ($edit && $edit['currency'] === 'USD') ? 'selected' : '' ?>>USD</option>
                                    <option value="EUR" <?= ($edit && $edit['currency'] === 'EUR') ? 'selected' : '' ?>>EUR</option>
                                    <option value="GBP" <?= ($edit && $edit['currency'] === 'GBP') ? 'selected' : '' ?>>GBP</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="input-group" style="margin-bottom: 15px;">
                            <label style="font-weight: 700; font-size: 12px; margin-bottom: 8px; display: block; color: #64748b;"><?= p_lang('old_price', 'Old Price (Optional)') ?></label>
                            <input type="number" step="0.01" name="old_price" class="form-control" style="font-weight: 700;" value="<?= $edit['old_price'] ?? '' ?>" placeholder="0.00">
                            <small style="color: #94a3b8; font-size: 11px;"><?= p_lang('old_price_help', 'Shows discount badge') ?></small>
                        </div>
                        
                        <div class="input-group">
                            <label style="font-weight: 700; font-size: 12px; margin-bottom: 8px; display: block; color: #64748b;"><?= p_lang('tax_rate', 'Tax Rate %') ?></label>
                            <input type="number" step="0.1" name="tax_rate" class="form-control" value="<?= $edit['tax_rate'] ?? '20' ?>">
                        </div>
                    </div>
                    
                    <!-- Featured -->
                    <div class="panel" style="background: #f8fafc; border: 1px solid #f1f5f9; padding: 20px; border-radius: 16px;">
                        <label class="checkbox-label" style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin: 0;">
                            <input type="checkbox" name="featured" value="1" <?= ($edit && $edit['featured']) ? 'checked' : '' ?> style="width: 20px; height: 20px; accent-color: var(--primary-color);">
                            <span style="font-weight: 600; font-size: 14px;">
                                <i class="las la-crown" style="color: #f59e0b; margin-right: 5px;"></i>
                                <?= p_lang('featured_product', 'Featured Product') ?>
                            </span>
                        </label>
                        <small style="color: #94a3b8; font-size: 11px; margin-left: 32px; display: block; margin-top: 5px;"><?= p_lang('featured_help', 'Show in featured section') ?></small>
                    </div>

                    <div class="panel" style="background: #f8fafc; border: 1px solid #f1f5f9; padding: 25px; border-radius: 16px;">
                        <h4 style="margin: 0 0 20px 0; font-size: 14px; color: #64748b; text-transform: uppercase; font-weight: 700;"><?= p_lang('social_proof', 'Social Proof') ?></h4>
                        <div class="input-group" style="margin-bottom: 15px;">
                            <label style="font-weight: 700; font-size: 12px; margin-bottom: 8px; display: block; color: #64748b;"><?= p_lang('rating', 'Star Rating (1-5)') ?></label>
                            <input type="number" step="0.1" min="1" max="5" name="rating" class="form-control" style="font-weight: 700;" value="<?= $edit['rating'] ?? '5.0' ?>">
                        </div>
                        <div class="input-group">
                            <label style="font-weight: 700; font-size: 12px; margin-bottom: 8px; display: block; color: #64748b;"><?= p_lang('reviews_count', 'Reviews Count') ?></label>
                            <input type="number" name="reviews_count" class="form-control" style="font-weight: 700;" value="<?= $edit['reviews_count'] ?? rand(15, 85) ?>">
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div>
                    <!-- Basic Info -->
                    <div class="panel" style="padding: 35px; border-radius: 20px; margin-bottom: 30px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
                            <div class="input-group">
                                <label style="font-weight: 700; font-size: 12px; color: #64748b; text-transform: uppercase; margin-bottom: 8px; display: block;"><?= p_lang('product_name', 'Product Name') ?> *</label>
                                <input type="text" name="title" id="prodTitle" class="form-control" style="font-size: 18px; padding: 15px; font-weight: 700;" value="<?= $edit['title'] ?? '' ?>" placeholder="<?= p_lang('product_name_placeholder', 'Enter product name') ?>" required>
                            </div>
                            <div class="input-group">
                                <label style="font-weight: 700; font-size: 12px; color: #64748b; text-transform: uppercase; margin-bottom: 8px; display: block;"><?= p_lang('slug', 'URL Slug') ?></label>
                                <input type="text" name="slug" id="prodSlug" class="form-control" style="font-size: 18px; padding: 15px; background: #f8fafc; font-weight: 600;" value="<?= $edit['slug'] ?? '' ?>" placeholder="<?= p_lang('slug_placeholder', 'auto-generated') ?>">
                            </div>
                        </div>

                        <div>
                            <label style="font-weight: 700; font-size: 12px; color: #64748b; text-transform: uppercase; margin-bottom: 12px; display: block;"><?= p_lang('description', 'Description') ?></label>
                            <textarea name="description" id="fullDescription" style="height: 300px;"><?= $edit['description'] ?? '' ?></textarea>
                        </div>
                    </div>

                    <!-- Variations -->
                    <div class="panel" style="padding: 35px; border-radius: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px;">
                            <h4 style="margin: 0; font-weight: 800; color: var(--primary-color);"><i class="las la-cogs"></i> <?= p_lang('variations', 'Variations & Options') ?></h4>
                            <button type="button" class="btn btn-primary" style="font-size: 13px; padding: 10px 20px;" onclick="addGroup()"><i class="las la-plus"></i> <?= p_lang('add_variation_group', 'Add Group') ?></button>
                        </div>
                        
                        <div id="groupsWrapper" style="display: flex; flex-direction: column; gap: 20px;">
                            <?php if ($edit && !empty($edit['variation_groups'])): foreach ($edit['variation_groups'] as $g_idx => $vg): ?>
                                <div class="variation-group" style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 16px; padding: 25px;">
                                    <div style="display: grid; grid-template-columns: 1fr 180px 40px; gap: 15px; margin-bottom: 20px;">
                                        <input type="text" name="group_title[<?= $g_idx ?>]" class="form-control" style="font-weight: 800;" value="<?= $vg['title'] ?>" placeholder="<?= p_lang('group_title', 'Group Title') ?>">
                                        <select name="group_type[<?= $g_idx ?>]" class="form-control" style="font-weight: 700;">
                                            <option value="single" <?= $vg['type'] === 'single' ? 'selected' : '' ?>><?= p_lang('single_choice', 'Single Choice') ?></option>
                                            <option value="multi" <?= $vg['type'] === 'multi' ? 'selected' : '' ?>><?= p_lang('multi_choice', 'Multi Choice') ?></option>
                                        </select>
                                        <button type="button" class="btn btn-icon" style="color: #ef4444;" onclick="this.closest('.variation-group').remove()"><i class="las la-trash-alt"></i></button>
                                    </div>
                                    <div class="options-list" style="display: flex; flex-direction: column; gap: 10px; padding-left: 20px; border-left: 3px solid #e2e8f0;">
                                        <?php foreach ($vg['options'] as $o_idx => $opt): ?>
                                            <div style="display: grid; grid-template-columns: 1fr 140px 40px; gap: 10px;">
                                                <input type="text" name="opt_name[<?= $g_idx ?>][<?= $o_idx ?>]" class="form-control" value="<?= $opt['name'] ?>" placeholder="<?= p_lang('option_name', 'Option name') ?>">
                                                <input type="number" step="0.01" name="opt_price[<?= $g_idx ?>][<?= $o_idx ?>]" class="form-control" style="font-weight: 700; color: var(--primary-color);" value="<?= $opt['price'] ?>" placeholder="+0.00">
                                                <button type="button" class="btn btn-icon" onclick="this.parentElement.remove()"><i class="las la-times-circle"></i></button>
                                            </div>
                                        <?php endforeach; ?>
                                        <button type="button" class="btn btn-secondary" style="width: fit-content; font-size: 12px; padding: 8px 16px;" onclick="addOptionRow(this, <?= $g_idx ?>)"><i class="las la-plus-circle"></i> <?= p_lang('add_option', 'Add Option') ?></button>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div style="margin-top: 40px; display: flex; justify-content: flex-end; gap: 15px;">
                        <a href="products" class="btn btn-secondary" style="padding: 15px 40px; font-size: 16px;"><?= p_lang('cancel', 'Cancel') ?></a>
                        <button type="submit" class="btn btn-primary btn-lg" style="padding: 15px 60px; font-size: 16px;">
                            <i class="las la-save"></i> <?= $edit ? p_lang('update_product', 'Update Product') : p_lang('create_product', 'Create Product') ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <style>
        .type-option:hover { border-color: var(--primary-color) !important; }
        .type-option.active { border-color: var(--primary-color) !important; background: rgba(99, 102, 241, 0.05); }
        .type-option.active i { transform: scale(1.1); }
        .image-upload-box:hover { border-color: var(--primary-color) !important; background: rgba(99, 102, 241, 0.02) !important; }
        </style>

        <script>
        tinymce.init({
            selector: '#fullDescription',
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            promotion: false, branding: false, skin: 'oxide-dark', content_css: 'dark',
            protect: [
                /<\?php[\s\S]*?\?>/g,
                /<\?=[\s\S]*?\?>/g
            ]
        });

        // Type Toggle
        function setType(type) {
            document.querySelectorAll('.type-option').forEach(el => el.classList.remove('active'));
            document.querySelector(`.type-option input[value="${type}"]`).closest('.type-option').classList.add('active');
            document.getElementById('digitalFileSection').style.display = type === 'digital' ? 'block' : 'none';
        }

        // Image Preview
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const box = document.getElementById('imageUploadBox');
                    box.style.background = `url(${e.target.result})`;
                    box.style.backgroundSize = 'cover';
                    box.style.backgroundPosition = 'center';
                    box.innerHTML = '';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // File Name Update
        function updateFileName(input) {
            if (input.files && input.files[0]) {
                document.getElementById('fileUploadMini').innerHTML = `
                    <i class="las la-check-circle" style="font-size: 32px; color: #10b981; margin-bottom: 8px;"></i>
                    <div style="font-size: 12px; color: #10b981; font-weight: 600;">${input.files[0].name}</div>
                    <small style="color: #94a3b8;">${(input.files[0].size / 1024 / 1024).toFixed(2)} MB</small>
                `;
            }
        }

        // Auto-Slug
        document.getElementById('prodTitle').addEventListener('input', (e) => {
            if (!document.getElementById('prodSlug').value) {
                const slug = e.target.value.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-');
                document.getElementById('prodSlug').placeholder = slug;
            }
        });

        let groupCount = <?= empty($edit['variation_groups']) ? 0 : count($edit['variation_groups']) ?>;
        function addGroup() {
            const wrapper = document.getElementById('groupsWrapper');
            const div = document.createElement('div');
            div.className = 'variation-group';
            div.style = "background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 16px; padding: 25px;";
            div.innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 180px 40px; gap: 15px; margin-bottom: 20px;">
                    <input type="text" name="group_title[${groupCount}]" class="form-control" style="font-weight: 800;" placeholder="<?= p_lang('group_title', 'Group Title') ?>">
                    <select name="group_type[${groupCount}]" class="form-control" style="font-weight: 700;">
                        <option value="single"><?= p_lang('single_choice', 'Single Choice') ?></option>
                        <option value="multi"><?= p_lang('multi_choice', 'Multi Choice') ?></option>
                    </select>
                    <button type="button" class="btn btn-icon" style="color: #ef4444;" onclick="this.closest('.variation-group').remove()"><i class="las la-trash-alt"></i></button>
                </div>
                <div class="options-list" style="display: flex; flex-direction: column; gap: 10px; padding-left: 20px; border-left: 3px solid #e2e8f0;">
                    <button type="button" class="btn btn-secondary" style="width: fit-content; font-size: 12px; padding: 8px 16px;" onclick="addOptionRow(this, ${groupCount})"><i class="las la-plus-circle"></i> <?= p_lang('add_option', 'Add Option') ?></button>
                </div>
            `;
            wrapper.appendChild(div);
            groupCount++;
        }

        function addOptionRow(btn, gIdx) {
            const list = btn.parentElement;
            const oIdx = list.querySelectorAll('div').length;
            const div = document.createElement('div');
            div.style = "display: grid; grid-template-columns: 1fr 140px 40px; gap: 10px;";
            div.innerHTML = `
                <input type="text" name="opt_name[${gIdx}][${oIdx}]" class="form-control" placeholder="<?= p_lang('option_name', 'Option name') ?>">
                <input type="number" step="0.01" name="opt_price[${gIdx}][${oIdx}]" class="form-control" style="font-weight: 700;" placeholder="+0.00">
                <button type="button" class="btn btn-icon" onclick="this.parentElement.remove()"><i class="las la-times-circle"></i></button>
            `;
            list.insertBefore(div, btn);
        }
        </script>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
