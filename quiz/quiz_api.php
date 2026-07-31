<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php'; // same $pdo connection every other page on the site uses

/* POST: Save Score — requires login so results can be linked to the real account */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['score'])) {

    if (!isset($_SESSION['id'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Please sign in to save your score."]);
        exit;
    }

    $user_id  = $_SESSION['id'];
    $fullname = $_SESSION['fullname'] ?? 'Player';
    $score    = isset($_POST['score']) ? (int) $_POST['score'] : 0;
    $category = trim($_POST['category'] ?? 'iq');

    try {
        $stmt = $pdo->prepare("INSERT INTO leaderboard (username, user_id, score, category) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fullname, $user_id, $score, $category]);

        // Also log this to the child's activity feed so parents can see it too.
        try {
            $log_stmt = $pdo->prepare(
                "INSERT INTO kid_activity_logs (child_id, activity_name, activity_type, points_earned, duration_minutes)
                 VALUES (?, ?, 'quiz', ?, 0)"
            );
            $log_stmt->execute([$user_id, "Fun Quiz (" . ucfirst($category) . ") — scored $score", $score]);
        } catch (PDOException $e) {
            // Non-critical — never let the activity log break score saving.
        }

        echo json_encode(["status" => "success"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Could not save score."]);
    }
    exit;
}

/* GET: Questions */
if (isset($_GET['get_questions'])) {

    $category = $_GET['category'] ?? 'iq';

    try {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE category = ?");
        $stmt->execute([$category]);
        echo json_encode($stmt->fetchAll());
    } catch (PDOException $e) {
        echo json_encode([]);
    }
    exit;
}

/* GET: My Rank (based on each player's best score in this category) */
if (isset($_GET['get_rank'])) {
    if (!isset($_SESSION['id'])) {
        echo json_encode(["rank" => null, "total" => 0, "best_score" => null]);
        exit;
    }
    $category = trim($_GET['category'] ?? 'iq');
    $user_id  = $_SESSION['id'];
    try {
        $best_stmt = $pdo->prepare("SELECT MAX(score) FROM leaderboard WHERE category = ? AND user_id = ?");
        $best_stmt->execute([$category, $user_id]);
        $best = $best_stmt->fetchColumn();

        if ($best === false || $best === null) {
            echo json_encode(["rank" => null, "total" => 0, "best_score" => null]);
            exit;
        }

        $rank_stmt = $pdo->prepare(
            "SELECT COUNT(*) + 1 FROM (
                SELECT user_id, MAX(score) AS best_score FROM leaderboard WHERE category = ? GROUP BY user_id
             ) t WHERE t.best_score > ?"
        );
        $rank_stmt->execute([$category, $best]);
        $rank = $rank_stmt->fetchColumn();

        $total_stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM leaderboard WHERE category = ?");
        $total_stmt->execute([$category]);
        $total = $total_stmt->fetchColumn();

        echo json_encode(["rank" => (int)$rank, "total" => (int)$total, "best_score" => (int)$best]);
    } catch (PDOException $e) {
        echo json_encode(["rank" => null, "total" => 0, "best_score" => null]);
    }
    exit;
}
if (isset($_GET['get_scores'])) {
    $category = trim($_GET['category'] ?? 'iq');
    try {
        $stmt = $pdo->prepare("SELECT username, score FROM leaderboard WHERE category = ? ORDER BY score DESC LIMIT 10");
        $stmt->execute([$category]);
        echo json_encode($stmt->fetchAll());
    } catch (PDOException $e) {
        echo json_encode([]);
    }
    exit;
}
?>