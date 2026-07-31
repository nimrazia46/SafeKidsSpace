<?php
// ============================================================
// notifications_mark_read.php
// Marks either ALL of the current user's notifications as read
// (action=clear_all) or a single one (action=mark_one&id=...).
//
// NOTE: broadcast notifications (user_id IS NULL in the table)
// share one is_read flag across every user. That's a simple
// trade-off — if you want each user to have their own read state
// on broadcast messages, that needs an extra
// notification_reads (notification_id, user_id) table.
// ============================================================

session_start();
header('Content-Type: application/json');
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['id'])) {
    // Guests have no persistent identity to track read-state for.
    echo json_encode(['success' => false, 'message' => 'Not signed in.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$user_id = $_SESSION['id'];
$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
$action  = $_POST['action'] ?? '';

try {
    if ($action === 'clear_all') {
        $stmt = $pdo->prepare(
            "UPDATE notifications SET is_read = 1
             WHERE user_id = :uid OR (user_id IS NULL AND (target_role = :role OR target_role IS NULL))"
        );
        $stmt->execute([':uid' => $user_id, ':role' => $user_role]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'mark_one') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid notification id.']);
            exit();
        }
        $stmt = $pdo->prepare(
            "UPDATE notifications SET is_read = 1
             WHERE id = :id AND (user_id = :uid OR (user_id IS NULL AND (target_role = :role OR target_role IS NULL)))"
        );
        $stmt->execute([':id' => $id, ':uid' => $user_id, ':role' => $user_role]);
        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong.']);
}