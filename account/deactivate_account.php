<?php
// ============================================================
// deactivate_account.php
// Submits an account deactivation request for admin approval.
// The account stays fully active/usable until an admin approves
// it from admin_dashboard.php — this endpoint does NOT delete or
// disable anything by itself.
// ============================================================

session_start();
header('Content-Type: application/json');
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'You must be signed in to do this.']);
    exit();
}

$sks_role = strtolower(trim($_SESSION['role'] ?? ''));
if ($sks_role === 'admin' || $sks_role === 'administrator') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin accounts cannot be deactivated this way.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$user_id  = $_SESSION['id'];
$password = trim($_POST['confirm_delete_password'] ?? '');

if ($password === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter your password to confirm.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password. Request not submitted.']);
        exit();
    }

    // Don't allow duplicate pending requests
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM deactivation_requests WHERE user_id = :uid AND status = 'pending'");
    $checkStmt->execute([':uid' => $user_id]);
    if ((int) $checkStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending deactivation request.']);
        exit();
    }

    $insert = $pdo->prepare("INSERT INTO deactivation_requests (user_id, status) VALUES (:uid, 'pending')");
    $insert->execute([':uid' => $user_id]);

    $update = $pdo->prepare("UPDATE users SET account_status = 'pending_deactivation' WHERE id = :id");
    $update->execute([':id' => $user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Your deactivation request has been submitted. An admin will review it soon — you can keep using your account until then.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
}