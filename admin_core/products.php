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
if (!is_array($products)) $products = [];

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die('CSRF validation failed');
    
    if (($action === 'wizard' && isset($_POST['wizard_complete'])) || $action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? uniqid('prod_');
        $type = $_POST['type'] ?? 'physical';
        $title = htmlspecialchars($_POST['title'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $tax_rate = (float)($_POST['tax_rate'] ?? 20);
        $currency = strtoupper(trim($_POST['currency'] ?? 'TRY')) ?: 'TRY';
        $stock = (int)($_POST['stock'] ?? 999);
        $description = $_POST['description'] ?? '';
        $featured = isset($_POST['featured']);
        $old_price = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
        
        $raw_slug = !empty($_POST['slug']) ? $_POST['slug'] : $title;
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $raw_slug)));
        if (empty($slug)) $slug = $id;

        foreach ($products as $p_id => $p_data) {
            if ($p_id !== $id && ($p_data['slug'] ?? '') === $slug) {
                $slug .= '-' . substr(uniqid(), -3);
                break;
            }
        }
        
        $variation_groups = [];
        if (isset($_POST['group_title']) && is_array($_POST['group_title'])) {
            foreach ($_POST['group_title'] as $g_idx => $g_title) {
                if (!empty($g_title)) {
                    $group_options = [];
                    if (isset($_POST['opt_name'][$g_idx]) && is_array($_POST['opt_name'][$g_idx])) {
                        foreach ($_POST['opt_name'][$g_idx] as $o_idx => $o_name) {
                            if (!empty($o_name)) {
                                $group_options[] = [
                                    'name' => htmlspecialchars($o_name),
                                    'price' => (float)($_POST['opt_price'][$g_idx][$o_idx] ?? 0)
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
            'reviews_count' => (int)($_POST['reviews_count'] ?? 24),
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

$total_prod = count($products);
$digital_prod = 0;
$physical_prod = 0;
$featured_prod = 0;
foreach ($products as $pr) {
    if (($pr['type'] ?? 'physical') === 'digital') $digital_prod++;
    else $physical_prod++;
    if (!empty($pr['featured'])) $featured_prod++;
}

$admin_page_title = p_lang('product_management', 'Products');
$admin_page_subtitle = p_lang('products_subtitle', 'Manage digital downloads, physical inventory, and pricing.');
$admin_active_nav = 'products';
require __DIR__ . '/includes/header.php';
?>

<script src="<?= rtrim($cfg_mainurl, '/') ?>/adminassets/js/tinymce/tinymce.min.js"></script>

<?php if (isset($_GET['success'])): ?>
    <div class="notice notice-success">
        <i class="las la-check-circle"></i> <?= p_lang('operation_success', 'Operation completed successfully.') ?>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>

    <div class="bento-grid" style="margin-bottom: 24px;">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= p_lang('total_products', 'Total Products') ?></span>
                <div class="stat-card-icon"><i class="las la-boxes"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($total_prod) ?></div>
            <div class="stat-card-footer"><?= p_lang('active_catalog', 'Active in store catalog') ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= p_lang('digital_product', 'Digital Products') ?></span>
                <div class="stat-card-icon"><i class="las la-cloud-download-alt"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($digital_prod) ?></div>
            <div class="stat-card-footer"><?= p_lang('instant_delivery', 'Instant file delivery') ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= p_lang('physical_product', 'Physical Products') ?></span>
                <div class="stat-card-icon" style="background: var(--success-bg); color: var(--success-color);"><i class="las la-box"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($physical_prod) ?></div>
            <div class="stat-card-footer"><?= p_lang('requires_shipping', 'Requires shipping') ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title"><?= p_lang('featured', 'Featured') ?></span>
                <div class="stat-card-icon" style="background: var(--warning-bg); color: var(--warning-color);"><i class="las la-star"></i></div>
            </div>
            <div class="stat-card-value"><?= number_format($featured_prod) ?></div>
            <div class="stat-card-footer"><?= p_lang('highlighted_in_store', 'Highlighted in storefront') ?></div>
        </div>
    </div>

    <div class="panel" style="padding: 0; overflow: hidden;">
        <div style="padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h3 class="panel-title"><i class="las la-box-open"></i> <?= p_lang('products', 'Products') ?></h3>
            <div style="display: flex; gap: 10px;">
                <a href="?action=wizard" class="btn btn-secondary">
                    <i class="las la-magic"></i> <?= p_lang('wizard_add', 'Product Wizard') ?>
                </a>
                <a href="?action=add" class="btn btn-primary">
                    <i class="las la-plus"></i> <?= p_lang('add_product', 'Add Product') ?>
                </a>
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 64px 24px;">
                <div style="width: 56px; height: 56px; border-radius: 14px; background: var(--bg-body); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; font-size: 26px; color: var(--text-muted); margin-bottom: 14px;">
                    <i class="las la-box-open"></i>
                </div>
                <h3 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 600; color: var(--text-main);"><?= p_lang('no_products_found', 'No Products Added Yet') ?></h3>
                <p style="margin: 0 auto 20px auto; max-width: 400px; font-size: 13px; color: var(--text-muted);">
                    <?= p_lang('no_products_desc', 'Create your first physical or digital product using the step-by-step wizard or quick editor.') ?>
                </p>
                <div style="display: inline-flex; gap: 10px;">
                    <a href="?action=wizard" class="btn btn-secondary"><i class="las la-magic"></i> <?= p_lang('wizard_add', 'Product Wizard') ?></a>
                    <a href="?action=add" class="btn btn-primary"><i class="las la-plus"></i> <?= p_lang('add_product', 'Add Product') ?></a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 68px;"><?= p_lang('media', 'Image') ?></th>
                            <th><?= p_lang('product_name', 'Product') ?></th>
                            <th><?= p_lang('type', 'Type') ?></th>
                            <th><?= p_lang('price', 'Price') ?></th>
                            <th><?= p_lang('stock', 'Stock') ?></th>
                            <th><?= p_lang('slug', 'URL Slug') ?></th>
                            <th style="text-align: right;"><?= p_lang('actions', 'Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <div style="width: 48px; height: 48px; border-radius: 10px; background: var(--bg-body); border: 1px solid var(--border-color); overflow: hidden; display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--text-muted);">
                                        <?php if (!empty($p['image'])): ?>
                                            <img src="<?= rtrim($cfg_mainurl, '/') ?>/inc/data/ecommerce/vault/<?= htmlspecialchars($p['image']) ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="">
                                        <?php else: ?>
                                            <i class="las la-image"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 6px;">
                                        <?= htmlspecialchars($p['title']) ?>
                                        <?php if (!empty($p['featured'])): ?>
                                            <i class="las la-star" style="color: var(--warning-color); font-size: 14px;" title="<?= p_lang('featured', 'Featured') ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--text-muted);">ID: <?= htmlspecialchars($p['id']) ?></div>
                                </td>
                                <td>
                                    <?php if (($p['type'] ?? 'physical') === 'digital'): ?>
                                        <span class="badge badge-primary"><i class="las la-cloud-download-alt"></i> <?= p_lang('digital', 'Digital') ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><i class="las la-box"></i> <?= p_lang('physical', 'Physical') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 700; color: var(--text-main);">
                                    <?= number_format($p['price'] ?? 0, 2) ?>
                                    <span style="font-size: 11.5px; font-weight: 500; color: var(--text-muted);"><?= htmlspecialchars($p['currency'] ?? 'TRY') ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-neutral"><?= (int)($p['stock'] ?? 0) ?></span>
                                </td>
                                <td>
                                    <code style="font-size: 11.5px; background: var(--bg-body); border: 1px solid var(--border-color); padding: 3px 8px; border-radius: 6px; color: var(--text-muted);">
                                        /product/<?= htmlspecialchars($p['slug'] ?? $p['id']) ?>
                                    </code>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                        <a href="?action=edit&id=<?= urlencode($p['id']) ?>" class="btn btn-secondary btn-sm">
                                            <i class="las la-pen"></i> <?= p_lang('edit', 'Edit') ?>
                                        </a>
                                        <a href="?action=delete&id=<?= urlencode($p['id']) ?>" onclick="return confirm('<?= p_lang('delete_confirm', 'Delete this product permanently?') ?>')" class="btn-icon" style="color: var(--danger-color);" title="<?= p_lang('delete', 'Delete') ?>">
                                            <i class="las la-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php else: 
    $edit = ($action === 'edit' && isset($_GET['id'])) ? ($products[$_GET['id']] ?? null) : null;
    if ($action === 'wizard'):
?>
    <div style="max-width: 760px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: 18px; font-weight: 700;"><?= p_lang('wizard_add', 'Product Setup Wizard') ?></h2>
            <a href="products" class="btn btn-secondary btn-sm"><i class="las la-arrow-left"></i> <?= p_lang('back_to_products', 'Back to Products') ?></a>
        </div>

        <div class="panel">
            <form method="post" enctype="multipart/form-data" id="wizardForm">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="wizard_complete" value="1">
                
                <div class="wizard-progress">
                    <div class="progress-line"><div class="progress-fill" id="progressFill" style="width: 20%;"></div></div>
                    <div class="progress-steps">
                        <div class="step active" data-step="1"><div class="step-number">1</div><div class="step-label"><?= p_lang('step_type', 'Type') ?></div></div>
                        <div class="step" data-step="2"><div class="step-number">2</div><div class="step-label"><?= p_lang('step_basic', 'Details') ?></div></div>
                        <div class="step" data-step="3"><div class="step-number">3</div><div class="step-label"><?= p_lang('step_delivery', 'Delivery') ?></div></div>
                        <div class="step" data-step="4"><div class="step-number">4</div><div class="step-label"><?= p_lang('step_options', 'Options') ?></div></div>
                        <div class="step" data-step="5"><div class="step-number">5</div><div class="step-label"><?= p_lang('step_preview', 'Review') ?></div></div>
                    </div>
                </div>

                <div class="wizard-step active" data-step="1">
                    <div class="step-header">
                        <h3><?= p_lang('choose_type', 'Select Product Type') ?></h3>
                        <p><?= p_lang('type_desc', 'Choose how this product will be delivered to your customers.') ?></p>
                    </div>
                    <div class="type-selector">
                        <label class="type-card" onclick="selectType('digital')">
                            <input type="radio" name="type" value="digital" id="typeDigital">
                            <div class="type-content">
                                <div class="type-icon digital"><i class="las la-cloud-download-alt"></i></div>
                                <h4><?= p_lang('digital_product', 'Digital Product') ?></h4>
                                <p><?= p_lang('digital_desc', 'Software, e-books, licenses, or downloadable archives.') ?></p>
                            </div>
                        </label>
                        <label class="type-card" onclick="selectType('physical')">
                            <input type="radio" name="type" value="physical" id="typePhysical">
                            <div class="type-content">
                                <div class="type-icon physical"><i class="las la-box"></i></div>
                                <h4><?= p_lang('physical_product', 'Physical Product') ?></h4>
                                <p><?= p_lang('physical_desc', 'Shippable goods, hardware, apparel, or printed items.') ?></p>
                            </div>
                        </label>
                    </div>
                    <div class="step-actions" style="justify-content: flex-end;">
                        <button type="button" class="btn btn-primary btn-lg" onclick="nextStep(2)" id="btnStep1" disabled><?= p_lang('continue', 'Continue') ?> <i class="las la-arrow-right"></i></button>
                    </div>
                </div>

                <div class="wizard-step" data-step="2">
                    <div class="step-header">
                        <h3><?= p_lang('basic_info', 'Product Details & Pricing') ?></h3>
                    </div>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label><?= p_lang('product_name', 'Product Name') ?> *</label>
                            <input type="text" name="title" id="prodTitle" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><?= p_lang('price', 'Price') ?> *</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="number" step="0.01" name="price" id="prodPrice" class="form-control" style="flex: 1;" required>
                                <select name="currency" class="form-control" style="width: 90px;"><option value="TRY">TRY</option><option value="USD">USD</option><option value="EUR">EUR</option></select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?= p_lang('old_price', 'Compare-at / Old Price') ?></label>
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
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px 14px; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main);">
                                <input type="checkbox" name="featured" value="1" style="width: 18px; height: 18px; accent-color: var(--primary-color);">
                                <span><?= p_lang('featured', 'Highlight as Featured Product') ?></span>
                            </label>
                        </div>
                        <div class="form-group full-width">
                            <label><?= p_lang('description', 'Description') ?></label>
                            <textarea name="description" id="prodDescription" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label><?= p_lang('product_image', 'Product Image') ?></label>
                            <div class="image-upload" id="imageUpload">
                                <div class="upload-preview" id="imagePreview"><i class="las la-cloud-upload-alt"></i><span><?= p_lang('drag_drop', 'Click to select product image') ?></span></div>
                                <input type="file" name="prod_image" id="prodImageInput" accept="image/*" style="display: none;">
                            </div>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="btn btn-secondary" onclick="prevStep(1)"><i class="las la-arrow-left"></i> <?= p_lang('back', 'Back') ?></button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(3)"><?= p_lang('continue', 'Continue') ?> <i class="las la-arrow-right"></i></button>
                    </div>
                </div>

                <div class="wizard-step" data-step="3">
                    <div class="step-header">
                        <h3><?= p_lang('delivery_setup', 'Fulfilment & Delivery') ?></h3>
                        <p id="deliveryDesc"><?= p_lang('delivery_digital_desc', 'Configure delivery method for this product.') ?></p>
                    </div>
                    <div id="digitalUpload" style="display: none;">
                        <div class="image-upload" id="fileUploadArea">
                            <i class="las la-file-archive" style="font-size: 40px; color: var(--primary-color); display: block; margin-bottom: 10px;"></i>
                            <h4 style="margin: 0 0 6px 0;"><?= p_lang('upload_file', 'Upload Digital Package') ?></h4>
                            <p style="font-size: 12.5px; color: var(--text-muted); margin: 0 0 16px 0;"><?= p_lang('upload_desc', 'ZIP, PDF, or software archive.') ?></p>
                            <input type="file" name="delivery_file" id="deliveryFileInput" style="display: none;">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('deliveryFileInput').click()"><i class="las la-folder-open"></i> <?= p_lang('choose_file', 'Select File') ?></button>
                            <div id="fileInfo" style="display: none; margin-top: 14px; color: var(--success-color); font-weight: 600; font-size: 13px; align-items: center; justify-content: center; gap: 6px;"><i class="las la-check-circle"></i><span id="fileName"></span> (<small id="fileSize"></small>)</div>
                        </div>
                    </div>
                    <div id="physicalSettings" style="display: none;">
                        <div style="padding: 20px; background: var(--bg-body); border-radius: 12px; border: 1px solid var(--border-color); display: flex; gap: 14px; align-items: center;">
                            <i class="las la-shipping-fast" style="font-size: 28px; color: var(--success-color);"></i>
                            <div>
                                <h4 style="margin: 0 0 4px 0; font-size: 15px;"><?= p_lang('physical_setup', 'Physical Shipping') ?></h4>
                                <p style="margin: 0; font-size: 13px; color: var(--text-muted);"><?= p_lang('physical_info', 'Customers will be prompted for their shipping address during checkout.') ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="btn btn-secondary" onclick="prevStep(2)"><i class="las la-arrow-left"></i> <?= p_lang('back', 'Back') ?></button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(4)"><?= p_lang('continue', 'Continue') ?> <i class="las la-arrow-right"></i></button>
                    </div>
                </div>

                <div class="wizard-step" data-step="4">
                    <div class="step-header">
                        <h3><?= p_lang('variations', 'Variations & Options') ?></h3>
                        <p><?= p_lang('variations_desc', 'Optional: add selectable attributes such as size, edition, or license tier.') ?></p>
                    </div>
                    <div id="variationsList" style="display: flex; flex-direction: column; gap: 12px;"></div>
                    <button type="button" class="btn btn-secondary" style="width: 100%; margin-top: 12px;" onclick="addVariation()"><i class="las la-plus"></i> <?= p_lang('add_variation', 'Add Option Group') ?></button>
                    <div class="step-actions">
                        <button type="button" class="btn btn-secondary" onclick="prevStep(3)"><i class="las la-arrow-left"></i> <?= p_lang('back', 'Back') ?></button>
                        <button type="button" class="btn btn-primary" onclick="generatePreview()"><?= p_lang('preview', 'Review Product') ?> <i class="las la-eye"></i></button>
                    </div>
                </div>

                <div class="wizard-step" data-step="5">
                    <div class="step-header">
                        <h3><?= p_lang('review_save', 'Review & Publish') ?></h3>
                    </div>
                    <div style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 16px; border-bottom: 1px solid var(--border-color); margin-bottom: 16px;">
                            <div>
                                <span class="badge badge-primary" id="previewType">Digital</span>
                                <h2 id="previewTitle" style="margin: 8px 0 0 0; font-size: 20px;">Product Title</h2>
                            </div>
                            <div id="previewPrice" style="font-size: 24px; font-weight: 800; color: var(--primary-color);">0.00 TRY</div>
                        </div>
                        <div style="display: grid; grid-template-columns: 120px 1fr; gap: 20px; align-items: start;">
                            <div id="previewImage" style="width: 120px; height: 120px; border-radius: 10px; background: var(--bg-surface); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 36px; color: var(--text-muted); overflow: hidden;"><i class="las la-image"></i></div>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <div><label><?= p_lang('description', 'Description') ?></label><div id="previewDescription" style="font-size: 13.5px; color: var(--text-main);">-</div></div>
                                <div id="previewVariations" style="display: none;"><label><?= p_lang('variations', 'Variations') ?></label><div id="previewVariationsList" style="font-size: 13px;">-</div></div>
                                <div id="previewFileSection"><label><?= p_lang('delivery_file', 'Delivery File') ?></label><div id="previewFile" style="font-size: 13px;">-</div></div>
                            </div>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button type="button" class="btn btn-secondary" onclick="prevStep(4)"><i class="las la-arrow-left"></i> <?= p_lang('back_edit', 'Back') ?></button>
                        <button type="submit" class="btn btn-primary btn-lg"><i class="las la-save"></i> <?= p_lang('save_product', 'Publish Product') ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <style>
    .wizard-progress { margin-bottom: 32px; }
    .progress-line { height: 4px; background: var(--border-color); border-radius: 2px; margin-bottom: 16px; overflow: hidden; }
    .progress-fill { height: 100%; background: var(--primary-color); transition: width 0.3s ease; }
    .progress-steps { display: flex; justify-content: space-between; }
    .step { display: flex; flex-direction: column; align-items: center; gap: 6px; opacity: 0.45; }
    .step.active, .step.completed { opacity: 1; }
    .step-number { width: 30px; height: 30px; border-radius: 50%; background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-muted); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12.5px; }
    .step.active .step-number { background: var(--primary-color); border-color: var(--primary-color); color: #fff; }
    .step.completed .step-number { background: var(--success-color); border-color: var(--success-color); color: #fff; }
    .step-label { font-size: 11.5px; font-weight: 600; color: var(--text-muted); }
    .step.active .step-label { color: var(--primary-color); }
    .wizard-step { display: none; }
    .wizard-step.active { display: block; }
    .step-header { text-align: center; margin-bottom: 24px; }
    .step-header h3 { margin: 0 0 6px 0; font-size: 20px; }
    .step-header p { margin: 0; color: var(--text-muted); font-size: 13.5px; }
    .step-actions { display: flex; justify-content: space-between; margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--border-color); }
    .type-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 20px 0; }
    .type-card { position: relative; border: 1px solid var(--border-color); background: var(--bg-body); border-radius: 14px; padding: 24px; cursor: pointer; transition: all 0.2s ease; text-align: center; }
    .type-card:hover { border-color: var(--primary-color); }
    .type-card input { position: absolute; opacity: 0; }
    .type-card:has(input:checked) { border-color: var(--primary-color); background: var(--primary-glow); }
    .type-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 12px; }
    .type-icon.digital { background: var(--primary-glow); color: var(--primary-color); }
    .type-icon.physical { background: var(--success-bg); color: var(--success-color); }
    .type-content h4 { margin: 0 0 6px 0; font-size: 16px; }
    .type-content p { margin: 0; font-size: 12.5px; color: var(--text-muted); }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-group.full-width { grid-column: 1 / -1; }
    .image-upload { border: 1px dashed var(--border-color); background: var(--bg-body); border-radius: 12px; padding: 32px; text-align: center; cursor: pointer; transition: all 0.2s ease; }
    .image-upload:hover { border-color: var(--primary-color); }
    .upload-preview { display: flex; flex-direction: column; align-items: center; gap: 8px; color: var(--text-muted); }
    .upload-preview i { font-size: 36px; color: var(--primary-color); }
    .upload-preview img { max-width: 160px; max-height: 160px; border-radius: 10px; }
    .variation-group-box { background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px; }
    .variation-group-header { display: grid; grid-template-columns: 1fr 150px 36px; gap: 10px; margin-bottom: 12px; }
    .variation-options-list { display: flex; flex-direction: column; gap: 8px; padding-left: 12px; border-left: 2px solid var(--border-color); }
    .variation-option-row { display: grid; grid-template-columns: 1fr 120px 36px; gap: 8px; }
    @media (max-width: 640px) { .type-selector, .form-grid { grid-template-columns: 1fr; } }
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
        if (step === 3) {
            document.getElementById('digitalUpload').style.display = selectedType === 'digital' ? 'block' : 'none';
            document.getElementById('physicalSettings').style.display = selectedType === 'physical' ? 'block' : 'none';
        }
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
    document.getElementById('imageUpload').addEventListener('click', () => document.getElementById('prodImageInput').click());
    document.getElementById('prodImageInput').addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                document.getElementById('imagePreview').innerHTML = `<img src="${ev.target.result}" alt="Preview">`;
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
                <input type="text" name="group_title[${idx}]" class="form-control" placeholder="<?= p_lang('variation_name', 'e.g., Size, License') ?>">
                <select name="group_type[${idx}]" class="form-control"><option value="single"><?= p_lang('single_choice', 'Single Choice') ?></option><option value="multi"><?= p_lang('multi_choice', 'Multi Choice') ?></option></select>
                <button type="button" class="btn-icon" style="color: var(--danger-color);" onclick="this.closest('.variation-group-box').remove()"><i class="las la-trash-alt"></i></button>
            </div>
            <div class="variation-options-list" id="optionsList${idx}">
                <button type="button" class="btn btn-secondary btn-sm" style="width: fit-content;" onclick="addOption(${idx})"><i class="las la-plus"></i> <?= p_lang('add_option', 'Add Option') ?></button>
            </div>
        `;
        wrapper.appendChild(div);
        addOption(idx);
    }
    function addOption(groupIdx) {
        const list = document.getElementById(`optionsList${groupIdx}`);
        const btn = list.querySelector('button');
        const oIdx = list.querySelectorAll('.variation-option-row').length;
        const row = document.createElement('div');
        row.className = 'variation-option-row';
        row.innerHTML = `
            <input type="text" name="opt_name[${groupIdx}][${oIdx}]" class="form-control" placeholder="<?= p_lang('option_name', 'Option name') ?>">
            <input type="number" step="0.01" name="opt_price[${groupIdx}][${oIdx}]" class="form-control" placeholder="+0.00">
            <button type="button" class="btn-icon" onclick="this.closest('.variation-option-row').remove()"><i class="las la-times"></i></button>
        `;
        list.insertBefore(row, btn);
    }
    function generatePreview() {
        const title = document.getElementById('prodTitle').value || 'Untitled';
        const price = parseFloat(document.getElementById('prodPrice').value) || 0;
        const currency = document.querySelector('select[name="currency"]').value;
        const tax = parseFloat(document.querySelector('input[name="tax_rate"]').value) || 20;
        const priceWithTax = price * (1 + tax / 100);
        const desc = document.getElementById('prodDescription').value;
        
        document.getElementById('previewType').textContent = selectedType === 'digital' ? '<?= p_lang('digital', 'Digital') ?>' : '<?= p_lang('physical', 'Physical') ?>';
        document.getElementById('previewTitle').textContent = title;
        document.getElementById('previewPrice').textContent = priceWithTax.toFixed(2) + ' ' + currency;
        document.getElementById('previewDescription').textContent = desc || '-';
        
        const imagePreview = document.getElementById('imagePreview');
        const previewImageDiv = document.getElementById('previewImage');
        if (imagePreview.querySelector('img')) {
            previewImageDiv.innerHTML = `<img src="${imagePreview.querySelector('img').src}" style="width:100%;height:100%;object-fit:cover;" alt="">`;
        }
        
        const fileSection = document.getElementById('previewFileSection');
        if (selectedType === 'digital') {
            document.getElementById('previewFile').textContent = document.getElementById('fileName').textContent || '-';
            fileSection.style.display = 'block';
        } else {
            fileSection.style.display = 'none';
        }
        nextStep(5);
    }
    </script>

<?php else: ?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="products" class="btn btn-secondary btn-sm">
                <i class="las la-arrow-left"></i> <?= p_lang('product_list', 'Back to Products') ?>
            </a>
            <h2 style="margin: 0; font-size: 18px; font-weight: 700;">
                <?= $edit ? p_lang('edit_product', 'Edit Product') : p_lang('add_product', 'Add New Product') ?>
            </h2>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" id="productForm">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= htmlspecialchars($edit['id']) ?>"><?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;" class="product-editor-grid">
            <div>
                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title"><i class="las la-cube"></i> <?= p_lang('basic_info', 'Product Information') ?></h3>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="input-group">
                            <label><?= p_lang('product_name', 'Product Name') ?> *</label>
                            <input type="text" name="title" id="prodTitle" class="form-control" value="<?= htmlspecialchars($edit['title'] ?? '') ?>" placeholder="<?= p_lang('product_name_placeholder', 'Enter product name') ?>" required>
                        </div>
                        <div class="input-group">
                            <label><?= p_lang('slug', 'URL Slug') ?></label>
                            <input type="text" name="slug" id="prodSlug" class="form-control" value="<?= htmlspecialchars($edit['slug'] ?? '') ?>" placeholder="<?= p_lang('slug_placeholder', 'auto-generated') ?>">
                        </div>
                    </div>

                    <div class="input-group" style="margin-bottom: 0;">
                        <label><?= p_lang('description', 'Description') ?></label>
                        <textarea name="description" id="fullDescription" style="height: 280px;"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title"><i class="las la-layer-group"></i> <?= p_lang('variations', 'Variations & Options') ?></h3>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addGroup()">
                            <i class="las la-plus"></i> <?= p_lang('add_variation_group', 'Add Group') ?>
                        </button>
                    </div>
                    
                    <div id="groupsWrapper" style="display: flex; flex-direction: column; gap: 16px;">
                        <?php if ($edit && !empty($edit['variation_groups'])): foreach ($edit['variation_groups'] as $g_idx => $vg): ?>
                            <div class="variation-group" style="background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 160px 34px; gap: 10px; margin-bottom: 12px;">
                                    <input type="text" name="group_title[<?= $g_idx ?>]" class="form-control" value="<?= htmlspecialchars($vg['title']) ?>" placeholder="<?= p_lang('group_title', 'Group Title') ?>">
                                    <select name="group_type[<?= $g_idx ?>]" class="form-control">
                                        <option value="single" <?= ($vg['type'] ?? 'single') === 'single' ? 'selected' : '' ?>><?= p_lang('single_choice', 'Single Choice') ?></option>
                                        <option value="multi" <?= ($vg['type'] ?? '') === 'multi' ? 'selected' : '' ?>><?= p_lang('multi_choice', 'Multi Choice') ?></option>
                                    </select>
                                    <button type="button" class="btn-icon" style="color: var(--danger-color);" onclick="this.closest('.variation-group').remove()"><i class="las la-trash-alt"></i></button>
                                </div>
                                <div class="options-list" style="display: flex; flex-direction: column; gap: 8px; padding-left: 14px; border-left: 2px solid var(--border-color);">
                                    <?php foreach ($vg['options'] as $o_idx => $opt): ?>
                                        <div style="display: grid; grid-template-columns: 1fr 130px 34px; gap: 8px;">
                                            <input type="text" name="opt_name[<?= $g_idx ?>][<?= $o_idx ?>]" class="form-control" value="<?= htmlspecialchars($opt['name']) ?>" placeholder="<?= p_lang('option_name', 'Option name') ?>">
                                            <input type="number" step="0.01" name="opt_price[<?= $g_idx ?>][<?= $o_idx ?>]" class="form-control" value="<?= htmlspecialchars($opt['price']) ?>" placeholder="+0.00">
                                            <button type="button" class="btn-icon" onclick="this.parentElement.remove()"><i class="las la-times"></i></button>
                                        </div>
                                    <?php endforeach; ?>
                                    <button type="button" class="btn btn-secondary btn-sm" style="width: fit-content;" onclick="addOptionRow(this, <?= $g_idx ?>)"><i class="las la-plus"></i> <?= p_lang('add_option', 'Add Option') ?></button>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <div>
                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title"><i class="las la-tag"></i> <?= p_lang('pricing', 'Pricing & Tax') ?></h3>
                    </div>
                    
                    <div class="input-group">
                        <label><?= p_lang('current_price', 'Price') ?> *</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="number" step="0.01" name="price" class="form-control" style="flex: 1; font-weight: 700;" value="<?= htmlspecialchars($edit['price'] ?? '') ?>" required>
                            <select name="currency" class="form-control" style="width: 90px; font-weight: 600;">
                                <option value="TRY" <?= ($edit && ($edit['currency'] ?? '') === 'TRY') ? 'selected' : '' ?>>TRY</option>
                                <option value="USD" <?= ($edit && ($edit['currency'] ?? '') === 'USD') ? 'selected' : '' ?>>USD</option>
                                <option value="EUR" <?= ($edit && ($edit['currency'] ?? '') === 'EUR') ? 'selected' : '' ?>>EUR</option>
                                <option value="GBP" <?= ($edit && ($edit['currency'] ?? '') === 'GBP') ? 'selected' : '' ?>>GBP</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label><?= p_lang('old_price', 'Compare-at Price (Optional)') ?></label>
                        <input type="number" step="0.01" name="old_price" class="form-control" value="<?= htmlspecialchars($edit['old_price'] ?? '') ?>" placeholder="0.00">
                    </div>
                    
                    <div class="input-group" style="margin-bottom: 0;">
                        <label><?= p_lang('tax_rate', 'Tax Rate (%)') ?></label>
                        <input type="number" step="0.1" name="tax_rate" class="form-control" value="<?= htmlspecialchars($edit['tax_rate'] ?? '20') ?>">
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title"><i class="las la-shipping-fast"></i> <?= p_lang('product_type', 'Product Type & Stock') ?></h3>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px;">
                        <label class="type-option <?= (!$edit || ($edit['type'] ?? 'physical') === 'physical') ? 'active' : '' ?>" style="padding: 12px; border: 1px solid var(--border-color); background: var(--bg-body); border-radius: 10px; text-align: center; cursor: pointer; margin: 0;" onclick="setType('physical')">
                            <input type="radio" name="type" value="physical" <?= (!$edit || ($edit['type'] ?? 'physical') === 'physical') ? 'checked' : '' ?> style="display: none;">
                            <i class="las la-box" style="font-size: 22px; color: var(--success-color); display: block; margin-bottom: 4px;"></i>
                            <span style="font-weight: 600; font-size: 12.5px; color: var(--text-main);"><?= p_lang('physical', 'Physical') ?></span>
                        </label>
                        <label class="type-option <?= ($edit && ($edit['type'] ?? '') === 'digital') ? 'active' : '' ?>" style="padding: 12px; border: 1px solid var(--border-color); background: var(--bg-body); border-radius: 10px; text-align: center; cursor: pointer; margin: 0;" onclick="setType('digital')">
                            <input type="radio" name="type" value="digital" <?= ($edit && ($edit['type'] ?? '') === 'digital') ? 'checked' : '' ?> style="display: none;">
                            <i class="las la-cloud-download-alt" style="font-size: 22px; color: var(--primary-color); display: block; margin-bottom: 4px;"></i>
                            <span style="font-weight: 600; font-size: 12.5px; color: var(--text-main);"><?= p_lang('digital', 'Digital') ?></span>
                        </label>
                    </div>

                    <div id="digitalFileSection" style="display: <?= ($edit && ($edit['type'] ?? '') === 'digital') ? 'block' : 'none' ?>; margin-bottom: 16px;">
                        <label><?= p_lang('delivery_file', 'Delivery File') ?></label>
                        <div id="fileUploadMini" style="padding: 16px; border: 1px dashed var(--border-color); background: var(--bg-body); border-radius: 10px; text-align: center; cursor: pointer;" onclick="document.getElementById('deliveryFileInput').click()">
                            <i class="las la-file-archive" style="font-size: 26px; color: var(--primary-color); display: block; margin-bottom: 4px;"></i>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                <?php if ($edit && !empty($edit['file'])): ?>
                                    <span style="color: var(--success-color); font-weight: 600;"><i class="las la-check"></i> <?= htmlspecialchars($edit['file']) ?></span>
                                <?php else: ?>
                                    <?= p_lang('upload_file', 'Click to select file') ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <input type="file" name="delivery_file" id="deliveryFileInput" style="display: none;" onchange="updateFileName(this)">
                        <?php if ($edit && !empty($edit['file'])): ?><input type="hidden" name="existing_file" value="<?= htmlspecialchars($edit['file']) ?>"><?php endif; ?>
                    </div>

                    <div class="input-group">
                        <label><?= p_lang('stock_quantity', 'Stock Quantity') ?></label>
                        <input type="number" name="stock" class="form-control" value="<?= htmlspecialchars($edit['stock'] ?? '999') ?>">
                    </div>

                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px 12px; background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-main); margin: 0;">
                        <input type="checkbox" name="featured" value="1" <?= ($edit && !empty($edit['featured'])) ? 'checked' : '' ?> style="width: 16px; height: 16px; accent-color: var(--primary-color);">
                        <span style="font-weight: 600; font-size: 13px;"><?= p_lang('featured_product', 'Featured Product') ?></span>
                    </label>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title"><i class="las la-image"></i> <?= p_lang('product_image', 'Product Image') ?></h3>
                    </div>
                    <div id="imageUploadBox" style="width: 100%; aspect-ratio: 16/10; border-radius: 10px; border: 1px dashed var(--border-color); background: <?= ($edit && !empty($edit['image'])) ? 'url('.rtrim($cfg_mainurl, '/').'/inc/data/ecommerce/vault/'.htmlspecialchars($edit['image']).')' : 'var(--bg-body)' ?>; background-size: cover; background-position: center; display: flex; align-items: center; justify-content: center; cursor: pointer;" onclick="document.getElementById('prodImageInput').click()">
                        <?php if (!$edit || empty($edit['image'])): ?>
                            <div style="text-align: center; color: var(--text-muted);">
                                <i class="las la-cloud-upload-alt" style="font-size: 36px; display: block; margin-bottom: 4px; color: var(--primary-color);"></i>
                                <span style="font-size: 12px;"><?= p_lang('click_upload', 'Click to upload image') ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="prod_image" id="prodImageInput" class="form-control" style="margin-top: 10px; font-size: 12px;" accept="image/*" onchange="previewImage(this)">
                    <?php if ($edit && !empty($edit['image'])): ?><input type="hidden" name="existing_image" value="<?= htmlspecialchars($edit['image']) ?>"><?php endif; ?>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h3 class="panel-title"><i class="las la-star"></i> <?= p_lang('social_proof', 'Rating & Reviews') ?></h3>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="input-group" style="margin-bottom: 0;">
                            <label><?= p_lang('rating', 'Star Rating (1-5)') ?></label>
                            <input type="number" step="0.1" min="1" max="5" name="rating" class="form-control" value="<?= htmlspecialchars($edit['rating'] ?? '5.0') ?>">
                        </div>
                        <div class="input-group" style="margin-bottom: 0;">
                            <label><?= p_lang('reviews_count', 'Reviews Count') ?></label>
                            <input type="number" name="reviews_count" class="form-control" value="<?= htmlspecialchars($edit['reviews_count'] ?? 24) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky-actions">
            <a href="products" class="btn btn-secondary"><?= p_lang('cancel', 'Cancel') ?></a>
            <button type="submit" class="btn btn-primary">
                <i class="las la-save"></i> <?= $edit ? p_lang('update_product', 'Update Product') : p_lang('create_product', 'Create Product') ?>
            </button>
        </div>
    </form>

    <style>
    .type-option.active { border-color: var(--primary-color) !important; background: var(--primary-glow) !important; }
    @media (max-width: 900px) {
        .product-editor-grid { grid-template-columns: 1fr !important; }
    }
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

    function setType(type) {
        document.querySelectorAll('.type-option').forEach(el => el.classList.remove('active'));
        document.querySelector(`.type-option input[value="${type}"]`).closest('.type-option').classList.add('active');
        document.getElementById('digitalFileSection').style.display = type === 'digital' ? 'block' : 'none';
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const box = document.getElementById('imageUploadBox');
                box.style.background = `url(${e.target.result}) center/cover`;
                box.innerHTML = '';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function updateFileName(input) {
        if (input.files && input.files[0]) {
            document.getElementById('fileUploadMini').innerHTML = `
                <i class="las la-check-circle" style="font-size: 26px; color: var(--success-color); display: block; margin-bottom: 4px;"></i>
                <div style="font-size: 12px; color: var(--success-color); font-weight: 600;">${input.files[0].name}</div>
                <small style="color: var(--text-muted);">${(input.files[0].size / 1024 / 1024).toFixed(2)} MB</small>
            `;
        }
    }

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
        div.style = "background: var(--bg-body); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px;";
        div.innerHTML = `
            <div style="display: grid; grid-template-columns: 1fr 160px 34px; gap: 10px; margin-bottom: 12px;">
                <input type="text" name="group_title[${groupCount}]" class="form-control" placeholder="<?= p_lang('group_title', 'Group Title') ?>">
                <select name="group_type[${groupCount}]" class="form-control">
                    <option value="single"><?= p_lang('single_choice', 'Single Choice') ?></option>
                    <option value="multi"><?= p_lang('multi_choice', 'Multi Choice') ?></option>
                </select>
                <button type="button" class="btn-icon" style="color: var(--danger-color);" onclick="this.closest('.variation-group').remove()"><i class="las la-trash-alt"></i></button>
            </div>
            <div class="options-list" style="display: flex; flex-direction: column; gap: 8px; padding-left: 14px; border-left: 2px solid var(--border-color);">
                <button type="button" class="btn btn-secondary btn-sm" style="width: fit-content;" onclick="addOptionRow(this, ${groupCount})"><i class="las la-plus"></i> <?= p_lang('add_option', 'Add Option') ?></button>
            </div>
        `;
        wrapper.appendChild(div);
        groupCount++;
    }

    function addOptionRow(btn, gIdx) {
        const list = btn.parentElement;
        const oIdx = list.querySelectorAll('div').length;
        const div = document.createElement('div');
        div.style = "display: grid; grid-template-columns: 1fr 130px 34px; gap: 8px;";
        div.innerHTML = `
            <input type="text" name="opt_name[${gIdx}][${oIdx}]" class="form-control" placeholder="<?= p_lang('option_name', 'Option name') ?>">
            <input type="number" step="0.01" name="opt_price[${gIdx}][${oIdx}]" class="form-control" placeholder="+0.00">
            <button type="button" class="btn-icon" onclick="this.parentElement.remove()"><i class="las la-times"></i></button>
        `;
        list.insertBefore(div, btn);
    }
    </script>
<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
