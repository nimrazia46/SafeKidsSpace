<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Admin only
$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

$redirect_url = 'admin_products.php';

// Must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirect_url");
    exit();
}

// ── Collect + sanitize inputs ─────────────────────────────────
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$price       = floatval($_POST['price'] ?? 0);
$stock       = max(0, intval($_POST['stock'] ?? 0));
$category_id = intval($_POST['category_id'] ?? 0);
$badge_tag   = trim($_POST['badge_tag'] ?? '');
$is_active   = isset($_POST['is_active']) ? 1 : 0;

// Basic validation
if ($title === '' || $price <= 0 || $category_id <= 0) {
    header("Location: {$redirect_url}?product_error=" . urlencode("Title, price, and category are required."));
    exit();
}

// ── Handle image upload ───────────────────────────────────────
$image_path = 'images/banner.png'; // default fallback (unprefixed, matches existing DB data format)

if (!empty($_FILES['product_image']['name'])) {
    $file     = $_FILES['product_image'];
    $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5 MB

    if (!in_array($file['type'], $allowed)) {
        header("Location: {$redirect_url}?product_error=" . urlencode("Only JPG, PNG, WEBP, or GIF images are allowed."));
        exit();
    }

    if ($file['size'] > $max_size) {
        header("Location: {$redirect_url}?product_error=" . urlencode("Image must be under 5MB."));
        exit();
    }

    // Make sure upload directory exists
    $upload_dir    = 'images/storeproduct/';               // web-relative, unprefixed (matches existing DB data format)
    $upload_dir_fs = __DIR__ . '/../' . $upload_dir;          // real filesystem path (independent of caller depth)
    if (!is_dir($upload_dir_fs)) {
        mkdir($upload_dir_fs, 0755, true);
    }

    $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safe_name = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $dest      = $upload_dir . $safe_name;      // web-relative, stored in DB
    $dest_fs   = $upload_dir_fs . $safe_name;   // real path, used to actually write the file

    if (move_uploaded_file($file['tmp_name'], $dest_fs)) {
        $image_path = $dest;
    } else {
        header("Location: {$redirect_url}?product_error=" . urlencode("Image upload failed. Check folder permissions."));
        exit();
    }
}

// ── Insert into store_products ────────────────────────────────
try {
    $stmt = $pdo->prepare("
        INSERT INTO store_products (category_id, title, description, price, stock, image_path, badge_tag, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $category_id,
        $title,
        $description,
        $price,
        $stock,
        $image_path,
        $badge_tag !== '' ? $badge_tag : null,
        $is_active
    ]);

    header("Location: {$redirect_url}?product_success=" . urlencode("✅ Product \"" . $title . "\" added to the store successfully!"));
    exit();

} catch (PDOException $e) {
    header("Location: {$redirect_url}?product_error=" . urlencode("Database error: " . $e->getMessage()));
    exit();
}