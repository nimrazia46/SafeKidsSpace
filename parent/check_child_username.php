<?php
/**
 * check_child_username.php
 * ─────────────────────────────────────────────────────────────
 * Real-time availability check while a parent is filling in the
 * "Create Child Account" form (parent_dashboard.php). Called on every
 * keystroke (debounced client-side).
 *
 * Child accounts don't collect a real email address — a login
 * identifier is generated as "<username>@" . CHILD_ACCOUNT_DOMAIN and
 * stored in users.email so login.php's existing (email + password)
 * check keeps working completely unchanged for every role.
 *
 * GET  ?username=sam123
 * → { success:true,  available:true }
 * → { success:true,  available:false, reason:"That username is already taken." }
 * → { success:false, error:"..." }   (invalid format / not logged in)
 * ─────────────────────────────────────────────────────────────
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/child_account.php';
header('Content-Type: application/json');

ini_set('display_errors', '0');
error_reporting(0);

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'parent') {
    echo json_encode(['success' => false, 'error' => 'Please log in as a parent.']);
    exit;
}

$username = trim($_GET['username'] ?? '');

$format_error = child_username_format_error($username);
if ($format_error) {
    echo json_encode(['success' => true, 'available' => false, 'reason' => $format_error]);
    exit;
}

try {
    $email = child_username_to_email($username);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'available' => false, 'reason' => 'That username is already taken.']);
    } else {
        echo json_encode(['success' => true, 'available' => true]);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Server error while checking availability.']);
}
