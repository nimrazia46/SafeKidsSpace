<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

$userId = (int) $_SESSION['id'];

$allowedGames = ['wordsearch', 'mathmatch'];
$game = isset($_GET['game']) ? trim($_GET['game']) : '';

if (!in_array($game, $allowedGames, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid game']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT state_json FROM user_game_progress WHERE user_id = :user_id AND game = :game"
    );
    $stmt->execute([':user_id' => $userId, ':game' => $game]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode([
            'success' => true,
            'state'   => json_decode($row['state_json']),
        ]);
    } else {
        echo json_encode(['success' => true, 'state' => null]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not load progress']);
}
