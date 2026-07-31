<?php
require_once __DIR__ . "/../includes/db.php";

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_GET["category"]) || trim($_GET["category"]) === "") {
    echo json_encode(["error" => "Missing category"]);
    exit;
}

$category = trim($_GET["category"]);

try {
    $stmt = $pdo->prepare("
        SELECT word FROM words
        WHERE category = ? AND status = 1
        ORDER BY RAND() LIMIT 10
    ");
    $stmt->execute([$category]);

    $data = array_column($stmt->fetchAll(), 'word');

    echo json_encode($data);

} catch (PDOException $e) {
    echo json_encode(["error" => "Could not load words."]);
}
