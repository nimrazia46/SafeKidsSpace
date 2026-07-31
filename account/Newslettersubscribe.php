<?php
// ============================================================
// newsletter_subscribe.php
// Handles the footer "Join The Galaxy" newsletter form.
// Expects a POST request with an "email" field (sent via fetch/AJAX).
// Always responds with JSON: { success: bool, message: string }
// ============================================================

header('Content-Type: application/json');
include __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$email = trim($_POST['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit();
}

try {
    // INSERT IGNORE so re-subscribing with the same email doesn't error out
    $stmt = $pdo->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'You\'re in! Welcome to the galaxy. 🚀']);
    } else {
        // Email already existed in the table
        echo json_encode(['success' => true, 'message' => 'You\'re already subscribed. See you among the stars! ✨']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
}