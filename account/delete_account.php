<?php
// ============================================================
// delete_account.php
// Handles the Settings modal's "Delete Account" form.
// Requires password confirmation before deleting.
// Deletes the user's row (all related data cascades via FK
// constraints already defined in the database), removes their
// uploaded profile picture from disk, then destroys the session.
// Always responds with JSON: { success: bool, message: string, redirect?: string }
// ============================================================

session_start();
header('Content-Type: application/json');
include __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'You must be signed in to do this.']);
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
    $stmt = $pdo->prepare("SELECT password, profile_pic FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password. Account not deleted.']);
        exit();
    }

    // Delete the user. All related rows (progress, quiz_results, certificates,
    // user_badges, enrollments, etc.) are removed automatically via
    // "ON DELETE CASCADE" foreign keys already defined in the database.
    $delete = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $delete->execute([':id' => $user_id]);

    // Clean up their uploaded profile picture (skip the default logo/external avatars)
    if (!empty($user['profile_pic']) && strpos($user['profile_pic'], 'images/profile_pic/') === 0 && file_exists(__DIR__ . '/../' . $user['profile_pic'])) {
        @unlink(__DIR__ . '/../' . $user['profile_pic']);
    }

    // Destroy the session completely
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Your account has been permanently deleted.', 'redirect' => 'login.php']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
}
