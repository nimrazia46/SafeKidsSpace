<?php
// ============================================================
// change_password.php
// Handles the Settings modal's "Change Password" form.
// Verifies the current password before allowing a new one.
// Always responds with JSON: { success: bool, message: string }
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

$user_id          = $_SESSION['id'];
$current_password = trim($_POST['current_password'] ?? '');
$new_password      = trim($_POST['new_password'] ?? '');
$confirm_password  = trim($_POST['confirm_password'] ?? '');

if ($current_password === '' || $new_password === '' || $confirm_password === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit();
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
    exit();
}

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($current_password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit();
    }

    $newHash = password_hash($new_password, PASSWORD_DEFAULT);

    $update = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
    $update->execute([
        ':password' => $newHash,
        ':id' => $user_id
    ]);

    // Force re-login: destroy the current session so the new password
    // must be used to sign back in.
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Your password has been updated successfully.',
        'redirect' => 'login.php'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
}