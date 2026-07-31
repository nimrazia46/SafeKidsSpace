<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

// Admin only
$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

$product_success = '';
$product_error   = '';

// ── Delete Product ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_delete_product'])) {
    $del_id = intval($_POST['product_id'] ?? 0);
    if ($del_id > 0) {
        try {
            $img_stmt = $pdo->prepare("SELECT image_path FROM store_products WHERE id = ?");
            $img_stmt->execute([$del_id]);
            $img_path = $img_stmt->fetchColumn();

            $pdo->prepare("DELETE FROM store_products WHERE id = ?")->execute([$del_id]);

            if ($img_path && strpos($img_path, 'images/storeproduct/') === 0 && file_exists(__DIR__ . '/../' . $img_path)) {
                @unlink(__DIR__ . '/../' . $img_path);
            }
            $product_success = "🗑️ Product deleted successfully.";
        } catch (PDOException $e) {
            $product_error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Toggle Active/Inactive ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_toggle_product_active'])) {
    $toggle_id  = intval($_POST['product_id'] ?? 0);
    $new_active = intval($_POST['new_active'] ?? 0) === 1 ? 1 : 0;
    if ($toggle_id > 0) {
        try {
            $pdo->prepare("UPDATE store_products SET is_active = ? WHERE id = ?")->execute([$new_active, $toggle_id]);
            $product_success = $new_active ? "✅ Product is now visible on the store." : "🚫 Product hidden from the store.";
        } catch (PDOException $e) {
            $product_error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Edit Product ─────────────────────────────────────────────
$is_edit_submit = isset($_POST['_edit_product']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_edit_submit) {
    $edit_id     = intval($_POST['product_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = floatval($_POST['price'] ?? 0);
    $stock       = max(0, intval($_POST['stock'] ?? 0));
    $category_id = intval($_POST['category_id'] ?? 0);
    $badge_tag   = trim($_POST['badge_tag'] ?? '');
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if ($edit_id <= 0 || $title === '' || $price <= 0 || $category_id <= 0) {
        $product_error = "Title, price, and category are required.";
    } else {
        try {
            $new_image_path = null;

            if (!empty($_FILES['product_image']['name'])) {
                $file    = $_FILES['product_image'];
                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

                if (in_array($file['type'], $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                    $upload_dir    = 'images/storeproduct/';               // web-relative, unprefixed (matches existing DB data & is stored as-is)
                    $upload_dir_fs = __DIR__ . '/../' . $upload_dir;          // real filesystem path (independent of caller depth)
                    if (!is_dir($upload_dir_fs)) {
                        mkdir($upload_dir_fs, 0755, true);
                    }
                    $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $safe_name = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                    $dest      = $upload_dir . $safe_name;      // web-relative, stored in DB
                    $dest_fs   = $upload_dir_fs . $safe_name;   // real path, used to actually write the file

                    if (move_uploaded_file($file['tmp_name'], $dest_fs)) {
                        $old_stmt = $pdo->prepare("SELECT image_path FROM store_products WHERE id = ?");
                        $old_stmt->execute([$edit_id]);
                        $old_img = $old_stmt->fetchColumn();
                        if ($old_img && strpos($old_img, 'images/storeproduct/') === 0 && file_exists(__DIR__ . '/../' . $old_img)) {
                            @unlink(__DIR__ . '/../' . $old_img);
                        }
                        $new_image_path = $dest;
                    }
                } else {
                    $product_error = "Image must be JPG/PNG/WEBP/GIF and under 5MB. Other changes were not saved.";
                }
            }

            if (!$product_error) {
                if ($new_image_path) {
                    $stmt = $pdo->prepare(
                        "UPDATE store_products
                         SET title = ?, description = ?, price = ?, stock = ?, category_id = ?, badge_tag = ?, is_active = ?, image_path = ?
                         WHERE id = ?"
                    );
                    $stmt->execute([$title, $description, $price, $stock, $category_id, $badge_tag !== '' ? $badge_tag : null, $is_active, $new_image_path, $edit_id]);
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE store_products
                         SET title = ?, description = ?, price = ?, stock = ?, category_id = ?, badge_tag = ?, is_active = ?
                         WHERE id = ?"
                    );
                    $stmt->execute([$title, $description, $price, $stock, $category_id, $badge_tag !== '' ? $badge_tag : null, $is_active, $edit_id]);
                }
                $product_success = "✏️ Product updated successfully!";
            }
        } catch (PDOException $e) {
            $product_error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Flash messages coming back from process_add_product.php redirects
if (!empty($_GET['product_success'])) $product_success = htmlspecialchars($_GET['product_success']);
if (!empty($_GET['product_error']))   $product_error   = htmlspecialchars($_GET['product_error']);

// ── Fetch store categories (for Add/Edit Product form) ───────
try {
    $store_categories = $pdo->query("SELECT id, name FROM store_categories ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    $store_categories = [];
}

// ── Fetch all products with category name ───────────────────
try {
    $all_products = $pdo->query(
        "SELECT p.*, c.name AS category_name
         FROM store_products p
         LEFT JOIN store_categories c ON c.id = p.category_id
         ORDER BY p.id DESC"
    )->fetchAll();
} catch (PDOException $e) {
    $all_products = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/layout.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>

<?php include __DIR__ . '/../includes/admin_navbar.php'; ?>

<!-- ══════════════════════════════════════════════════════════════
     ADD PRODUCT MODAL POPUP
══════════════════════════════════════════════════════════════ -->
<div class="apm-overlay" id="addProductOverlay">
    <div class="apm-modal" role="dialog" aria-modal="true" aria-labelledby="apm-title">

        <div class="apm-header">
            <h2 class="apm-title" id="apm-title">
                <i class="fa-solid fa-box-open"></i> Add Store Product
            </h2>
            <button class="apm-close-btn" id="apmCloseBtn" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="process_add_product.php" method="POST" enctype="multipart/form-data" id="addProductForm">

            <div class="apm-group">
                <label class="apm-label" for="apm_title">Product Title <span style="color:#f87171;">*</span></label>
                <input type="text" id="apm_title" name="title" class="apm-input"
                       placeholder="e.g., Astra-Rover Coding Kit" required maxlength="255">
            </div>

            <div class="apm-group">
                <label class="apm-label" for="apm_desc">Description</label>
                <textarea id="apm_desc" name="description" class="apm-textarea"
                          placeholder="Describe the product for kids and parents..."></textarea>
            </div>

            <div class="apm-two-col">
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="apm_price">Price (PKR / $) <span style="color:#f87171;">*</span></label>
                    <input type="number" id="apm_price" name="price" class="apm-input"
                           placeholder="0.00" step="0.01" min="0.01" required>
                </div>
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="apm_stock">Stock Quantity <span style="color:#f87171;">*</span></label>
                    <input type="number" id="apm_stock" name="stock" class="apm-input"
                           placeholder="e.g., 15" step="1" min="0" required>
                </div>
            </div>

            <div class="apm-group" style="margin-top:18px;">
                <label class="apm-label" for="apm_cat">Category <span style="color:#f87171;">*</span></label>
                <select id="apm_cat" name="category_id" class="apm-select" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($store_categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="apm-group" style="margin-top:18px;">
                <label class="apm-label" for="apm_badge">Badge Tag <span style="color:#64748b;">(optional)</span></label>
                <input type="text" id="apm_badge" name="badge_tag" class="apm-input"
                       placeholder="e.g., Top Pick, New Release, Best Seller" maxlength="50">
                <div class="apm-badge-hints">
                    <span class="apm-hint-chip" onclick="setBadge('addProductForm', 'Top Pick')">Top Pick</span>
                    <span class="apm-hint-chip" onclick="setBadge('addProductForm', 'Best Seller')">Best Seller</span>
                    <span class="apm-hint-chip" onclick="setBadge('addProductForm', 'New Release')">New Release</span>
                    <span class="apm-hint-chip" onclick="setBadge('addProductForm', 'Trending')">Trending</span>
                    <span class="apm-hint-chip" onclick="setBadge('addProductForm', 'Award Winner')">Award Winner</span>
                    <span class="apm-hint-chip" onclick="setBadge('addProductForm', 'Kids Favorite')">Kids Favorite</span>
                </div>
            </div>

            <div class="apm-group">
                <label class="apm-label">Product Image <span style="color:#f87171;">*</span></label>
                <div class="apm-file-zone" id="apmFileZone">
                    <input type="file" name="product_image" id="apm_image"
                           accept="image/jpeg,image/png,image/webp,image/gif" required>
                    <i class="fa-solid fa-cloud-arrow-up apm-file-icon"></i>
                    <p class="apm-file-label">
                        <strong>Click to upload</strong> or drag &amp; drop<br>
                        JPG, PNG, WEBP, GIF — max 5MB
                    </p>
                    <img id="apm-preview" src="" alt="Preview">
                </div>
            </div>

            <div class="apm-group">
                <div class="apm-toggle-row">
                    <div>
                        <label for="apm_active" style="font-weight:700;">Publish immediately</label><br>
                        <small>Product will appear on the store page right away</small>
                    </div>
                    <label class="apm-switch">
                        <input type="checkbox" name="is_active" id="apm_active" checked>
                        <span class="apm-slider"></span>
                    </label>
                </div>
            </div>

            <button type="submit" class="apm-submit-btn" id="apmSubmitBtn">
                <span class="apm-spinner" id="apmSpinner"></span>
                <i class="fa-solid fa-circle-plus" id="apmBtnIcon"></i>
                <span id="apmBtnText">Add Product to Store</span>
            </button>

        </form>
    </div>
</div><!-- /.apm-overlay -->

<!-- ══════════════════════════════════════════════════════════════
     EDIT PRODUCT MODAL POPUP (single reusable modal, filled via JS)
══════════════════════════════════════════════════════════════ -->
<div class="apm-overlay" id="editProductOverlay">
    <div class="apm-modal" role="dialog" aria-modal="true" aria-labelledby="epm-title">

        <div class="apm-header">
            <h2 class="apm-title" id="epm-title">
                <i class="fa-solid fa-pen"></i> Edit Store Product
            </h2>
            <button class="apm-close-btn" id="epmCloseBtn" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="admin_products.php" method="POST" enctype="multipart/form-data" id="editProductForm">
            <input type="hidden" name="_edit_product" value="1">
            <input type="hidden" name="product_id" id="epm_id">

            <div class="apm-group">
                <label class="apm-label" for="epm_title_input">Product Title <span style="color:#f87171;">*</span></label>
                <input type="text" id="epm_title_input" name="title" class="apm-input" required maxlength="255">
            </div>

            <div class="apm-group">
                <label class="apm-label" for="epm_desc">Description</label>
                <textarea id="epm_desc" name="description" class="apm-textarea"></textarea>
            </div>

            <div class="apm-two-col">
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="epm_price">Price (PKR / $) <span style="color:#f87171;">*</span></label>
                    <input type="number" id="epm_price" name="price" class="apm-input" step="0.01" min="0.01" required>
                </div>
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="epm_stock">Stock Quantity <span style="color:#f87171;">*</span></label>
                    <input type="number" id="epm_stock" name="stock" class="apm-input" step="1" min="0" required>
                </div>
            </div>

            <div class="apm-group" style="margin-top:18px;">
                <label class="apm-label" for="epm_cat">Category <span style="color:#f87171;">*</span></label>
                <select id="epm_cat" name="category_id" class="apm-select" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($store_categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="apm-group" style="margin-top:18px;">
                <label class="apm-label" for="epm_badge">Badge Tag <span style="color:#64748b;">(optional)</span></label>
                <input type="text" id="epm_badge" name="badge_tag" class="apm-input" maxlength="50">
                <div class="apm-badge-hints">
                    <span class="apm-hint-chip" onclick="setBadge('editProductForm', 'Top Pick')">Top Pick</span>
                    <span class="apm-hint-chip" onclick="setBadge('editProductForm', 'Best Seller')">Best Seller</span>
                    <span class="apm-hint-chip" onclick="setBadge('editProductForm', 'New Release')">New Release</span>
                    <span class="apm-hint-chip" onclick="setBadge('editProductForm', 'Trending')">Trending</span>
                    <span class="apm-hint-chip" onclick="setBadge('editProductForm', 'Award Winner')">Award Winner</span>
                    <span class="apm-hint-chip" onclick="setBadge('editProductForm', 'Kids Favorite')">Kids Favorite</span>
                </div>
            </div>

            <div class="apm-group">
                <label class="apm-label">Product Image <span style="color:#64748b;">(leave empty to keep current image)</span></label>
                <div class="apm-file-zone" id="epmFileZone">
                    <input type="file" name="product_image" id="epm_image" accept="image/jpeg,image/png,image/webp,image/gif">
                    <i class="fa-solid fa-cloud-arrow-up apm-file-icon"></i>
                    <p class="apm-file-label">
                        <strong>Click to replace</strong> or drag &amp; drop<br>
                        JPG, PNG, WEBP, GIF — max 5MB
                    </p>
                    <img id="epm-preview" src="" alt="Preview">
                </div>
            </div>

            <div class="apm-group">
                <div class="apm-toggle-row">
                    <div>
                        <label for="epm_active" style="font-weight:700;">Published</label><br>
                        <small>Product will appear on the store page</small>
                    </div>
                    <label class="apm-switch">
                        <input type="checkbox" name="is_active" id="epm_active">
                        <span class="apm-slider"></span>
                    </label>
                </div>
            </div>

            <button type="submit" class="apm-submit-btn" id="epmSubmitBtn">
                <span class="apm-spinner" id="epmSpinner"></span>
                <i class="fa-solid fa-floppy-disk" id="epmBtnIcon"></i>
                <span id="epmBtnText">Save Changes</span>
            </button>

        </form>
    </div>
</div><!-- /.editProductOverlay -->

<!-- Custom confirmation modal (replaces native confirm()) -->
<div class="adc-overlay" id="adcOverlay">
    <div class="adc-modal">
        <div class="adc-icon" id="adcIcon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3 class="adc-title" id="adcTitle">Are you sure?</h3>
        <p class="adc-message" id="adcMessage"></p>
        <div class="adc-actions">
            <button type="button" class="adc-btn adc-btn-cancel" id="adcCancelBtn">Cancel</button>
            <button type="button" class="adc-btn adc-btn-confirm" id="adcConfirmBtn">Yes, Confirm</button>
        </div>
    </div>
</div>

<div class="main-content ad-wrap">

    <?php if ($product_success): ?>
        <div class="ad-flash ad-flash-success" id="adFlash">
            <i class="fa-solid fa-circle-check"></i> <?= $product_success ?>
        </div>
    <?php endif; ?>
    <?php if ($product_error): ?>
        <div class="ad-flash ad-flash-error" id="adFlash">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= $product_error ?>
        </div>
    <?php endif; ?>

    <div class="ad-hero">
        <div class="ad-hero-left">
            <div class="ad-hero-icon"><i class="fa-solid fa-box-open"></i></div>
            <div>
                <h1 class="ad-hero-title">Manage Store Products</h1>
                <p class="ad-hero-sub">Add, edit, publish/hide, and remove products from the Kids Store</p>
                <span class="ad-hero-badge"><i class="fa-solid fa-circle-check"></i> <?= count($all_products) ?> Total Products</span>
            </div>
        </div>
        <div class="ad-hero-right">
            <button type="button" class="ad-back-btn" id="openAddProductBtn" style="border:none; cursor:pointer;">
                <i class="fa-solid fa-circle-plus"></i> Add Product
            </button>
        </div>
    </div>

    <p class="ad-section-title"><i class="fa-solid fa-list"></i> All Products</p>

    <?php if (empty($all_products)): ?>
        <div class="ad-empty">
            <i class="fa-solid fa-box-open"></i>
            <p>No products yet. Click "Add Product" to list your first item.</p>
        </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Badge</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_products as $p):
                    $stock = (int) ($p['stock'] ?? 0);
                    if ($stock <= 0)      { $stockClass = 'ad-stock-out'; $stockLabel = 'Out of stock'; }
                    elseif ($stock <= 5)  { $stockClass = 'ad-stock-low'; $stockLabel = $stock . ' left'; }
                    else                  { $stockClass = 'ad-stock-ok';  $stockLabel = $stock . ' pcs'; }
                ?>
                <tr>
                    <td>
                        <img src="<?= '../' . htmlspecialchars($p['image_path']) ?>"
                             style="width:56px;height:56px;object-fit:cover;border-radius:8px;" alt="">
                    </td>
                    <td style="font-weight:700;"><?= htmlspecialchars($p['title']) ?></td>
                    <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                    <td>$<?= number_format($p['price'], 2) ?></td>
                    <td><span class="ad-stock-badge <?= $stockClass ?>"><?= $stockLabel ?></span></td>
                    <td><?= $p['badge_tag'] ? htmlspecialchars($p['badge_tag']) : '—' ?></td>
                    <td>
                        <form action="admin_products.php" method="POST" style="display:inline;">
                            <input type="hidden" name="_toggle_product_active" value="1">
                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                            <input type="hidden" name="new_active" value="<?= $p['is_active'] ? 0 : 1 ?>">
                            <button type="submit" class="ad-back-btn" style="padding:5px 12px; font-size:.75rem; cursor:pointer; border:none;
                                <?= $p['is_active']
                                    ? 'background:rgba(192,132,252,.15); color:#c084fc;'
                                    : 'background:rgba(148,163,184,.12); color:#94a3b8;' ?>">
                                <?= $p['is_active'] ? 'Active' : 'Hidden' ?>
                            </button>
                        </form>
                    </td>
                    <td style="white-space:nowrap;">
                        <button type="button" class="ad-back-btn adm-edit-product-btn" style="padding:6px 10px; font-size:.8rem;"
                            data-id="<?= (int)$p['id'] ?>"
                            data-title="<?= htmlspecialchars($p['title'], ENT_QUOTES) ?>"
                            data-description="<?= htmlspecialchars($p['description'] ?? '', ENT_QUOTES) ?>"
                            data-price="<?= htmlspecialchars($p['price'], ENT_QUOTES) ?>"
                            data-stock="<?= (int) $p['stock'] ?>"
                            data-category="<?= (int) $p['category_id'] ?>"
                            data-badge="<?= htmlspecialchars($p['badge_tag'] ?? '', ENT_QUOTES) ?>"
                            data-active="<?= (int) $p['is_active'] ?>"
                            data-image="<?= '../' . htmlspecialchars($p['image_path'], ENT_QUOTES) ?>">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form action="admin_products.php" method="POST" style="display:inline;" class="ad-confirm-form" data-confirm-msg="Delete this product permanently? This cannot be undone.">
                            <input type="hidden" name="_delete_product" value="1">
                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="ad-back-btn" style="padding:6px 10px; font-size:.8rem; background:rgba(248,113,113,.12); color:#f87171; border-color:rgba(248,113,113,.3);">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div><!-- /.main-content -->

<script>
/* ── Add Product Modal ───────────────────────────────────── */
const overlay      = document.getElementById('addProductOverlay');
const openBtn       = document.getElementById('openAddProductBtn');
const closeBtn      = document.getElementById('apmCloseBtn');
const form          = document.getElementById('addProductForm');
const imageInput    = document.getElementById('apm_image');
const preview       = document.getElementById('apm-preview');

function openModal() {
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    overlay.classList.remove('open');
    document.body.style.overflow = '';
}
openBtn.addEventListener('click', openModal);
closeBtn.addEventListener('click', closeModal);
overlay.addEventListener('click', function(e) { if (e.target === overlay) closeModal(); });
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeModal(); closeEditModal(); }
});

imageInput.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
    reader.readAsDataURL(file);
});

function setBadge(formId, text) {
    const f = document.getElementById(formId);
    f.querySelector('input[name="badge_tag"]').value = text;
}

form.addEventListener('submit', function() {
    document.getElementById('apmSpinner').style.display = 'inline-block';
    document.getElementById('apmBtnIcon').style.display = 'none';
    document.getElementById('apmBtnText').textContent   = 'Uploading…';
    document.getElementById('apmSubmitBtn').disabled     = true;
});

<?php if ($product_error && !$is_edit_submit): ?> openModal(); <?php endif; ?>

/* ── Edit Product Modal ──────────────────────────────────── */
const editOverlay   = document.getElementById('editProductOverlay');
const editCloseBtn  = document.getElementById('epmCloseBtn');
const editForm      = document.getElementById('editProductForm');
const editImageInput= document.getElementById('epm_image');
const editPreview   = document.getElementById('epm-preview');

function openEditModal() { editOverlay.classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeEditModal(){ editOverlay.classList.remove('open'); document.body.style.overflow = ''; }

editCloseBtn.addEventListener('click', closeEditModal);
editOverlay.addEventListener('click', function(e){ if (e.target === editOverlay) closeEditModal(); });

document.querySelectorAll('.adm-edit-product-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.getElementById('epm_id').value           = btn.dataset.id;
        document.getElementById('epm_title_input').value   = btn.dataset.title;
        document.getElementById('epm_desc').value          = btn.dataset.description;
        document.getElementById('epm_price').value         = btn.dataset.price;
        document.getElementById('epm_stock').value         = btn.dataset.stock;
        document.getElementById('epm_cat').value            = btn.dataset.category;
        document.getElementById('epm_badge').value          = btn.dataset.badge;
        document.getElementById('epm_active').checked       = btn.dataset.active === '1';
        editPreview.src = btn.dataset.image;
        editPreview.style.display = 'block';
        openEditModal();
    });
});

editImageInput.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => { editPreview.src = e.target.result; editPreview.style.display = 'block'; };
    reader.readAsDataURL(file);
});

editForm.addEventListener('submit', function() {
    document.getElementById('epmSpinner').style.display = 'inline-block';
    document.getElementById('epmBtnIcon').style.display = 'none';
    document.getElementById('epmBtnText').textContent   = 'Saving…';
    document.getElementById('epmSubmitBtn').disabled    = true;
});

<?php if ($product_error && $is_edit_submit): ?> openEditModal(); <?php endif; ?>

/* ── Custom confirmation modal ───────────────────────────── */
(function(){
    const adcOverlay    = document.getElementById('adcOverlay');
    const adcMessage    = document.getElementById('adcMessage');
    const adcConfirmBtn = document.getElementById('adcConfirmBtn');
    const adcCancelBtn  = document.getElementById('adcCancelBtn');
    let adcPendingForm  = null;

    document.querySelectorAll('form.ad-confirm-form').forEach(function(f){
        f.addEventListener('submit', function(e){
            e.preventDefault();
            adcPendingForm = f;
            adcMessage.textContent = f.getAttribute('data-confirm-msg') || 'Are you sure you want to continue?';
            adcOverlay.classList.add('open');
        });
    });
    adcConfirmBtn.addEventListener('click', function(){
        adcOverlay.classList.remove('open');
        if (adcPendingForm) { adcPendingForm.submit(); }
    });
    adcCancelBtn.addEventListener('click', function(){
        adcOverlay.classList.remove('open');
        adcPendingForm = null;
    });
    adcOverlay.addEventListener('click', function(e){
        if (e.target === adcOverlay) {
            adcOverlay.classList.remove('open');
            adcPendingForm = null;
        }
    });
})();

/* Auto-dismiss flash alerts */
document.querySelectorAll('#adFlash').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .4s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 6000);
});
</script>
</body>
</html>