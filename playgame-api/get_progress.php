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

try {
    $stmt = $pdo->prepare(
        "SELECT letter, case_mode FROM user_letter_progress WHERE user_id = :user_id"
    );
    $stmt->execute([':user_id' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'traced'  => $rows, // [{letter: 'A', case_mode: 'upper'}, ...]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not load progress']);
}