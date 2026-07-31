<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Expects includes/db.php to define a PDO instance called $pdo (or $conn —
// adjust the line below to match whichever variable your db.php actually sets).
require_once __DIR__ . '/../includes/db.php';
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

$userId = (int) $_SESSION['id'];

$letter = isset($_POST['letter']) ? strtoupper(trim($_POST['letter'])) : '';
$case   = isset($_POST['case']) ? strtolower(trim($_POST['case'])) : '';

if (!preg_match('/^[A-Z]$/', $letter) || !in_array($case, ['upper', 'lower'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid letter or case']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO user_letter_progress (user_id, letter, case_mode)
         VALUES (:user_id, :letter, :case_mode)
         ON DUPLICATE KEY UPDATE traced_at = CURRENT_TIMESTAMP"
    );
    $stmt->execute([
        ':user_id'   => $userId,
        ':letter'    => $letter,
        ':case_mode' => $case,
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not save progress']);
}