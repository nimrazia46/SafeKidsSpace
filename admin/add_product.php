<?php
/**
 * add_product.php
 * ─────────────────────────────────────────────────────────────
 * The "Add Product" form is now a modal popup embedded directly
 * inside admin_dashboard.php.
 *
 * This file redirects admins back to the dashboard so the popup
 * opens there. Old direct links to add_product.php still work.
 */
session_start();

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../account/login.php");
    exit();
}

// Redirect to dashboard — the popup will open automatically
header("Location: admin_dashboard.php#add-product");
exit();