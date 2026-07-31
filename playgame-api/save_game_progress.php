<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Lives in playgame-api/, so ../includes/db.php resolves to safekidspace/includes/db.php
require_once __DIR__ . '/../includes/db.php';
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

$userId = (int) $_SESSION['id'];

$allowedGames = ['wordsearch', 'mathmatch'];
$game  = isset($_POST['game']) ? trim($_POST['game']) : '';
$state = isset($_POST['state']) ? $_POST['state'] : '';

if (!in_array($game, $allowedGames, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid game']);
    exit;
}

if ($state === '' || json_decode($state) === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid state']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO user_game_progress (user_id, game, state_json)
         VALUES (:user_id, :game, :state_json)
         ON DUPLICATE KEY UPDATE state_json = :state_json2, updated_at = CURRENT_TIMESTAMP"
    );
    $stmt->execute([
        ':user_id'    => $userId,
        ':game'       => $game,
        ':state_json' => $state,
        ':state_json2'=> $state,
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not save progress']);
}
