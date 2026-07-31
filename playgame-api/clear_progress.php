<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Lives in backend/, so ../includes/db.php resolves to safekidspace/includes/db.php
require_once __DIR__ . '/../includes/db.php';
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

$userId = (int) $_SESSION['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM user_letter_progress WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not clear progress']);
}