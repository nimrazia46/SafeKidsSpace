<?php
$host = 'localhost';
$db   = 'safekidsspace'; 
$user = 'root';          
$pass = '';              
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function getEmbedUrl($url) {
    preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $url, $matches);
    if (isset($matches[1])) {
        return "https://www.youtube.com/embed/" . $matches[1];
    }
    return $url;
}

// ============================================================
// Notification helpers — used across admin/teacher/parent/student
// pages so every "something happened" moment shows up in the bell
// icon, scoped to the right audience only.
// ============================================================

/**
 * Send a notification to every admin only.
 * (Broadcast row with user_id = NULL, target_role = 'admin' —
 * only admin accounts will ever see it.)
 */
function notify_admins($pdo, $title, $message, $link = null, $icon = 'fa-solid fa-bell') {
    try {
        $pdo->prepare(
            "INSERT INTO notifications (user_id, target_role, title, message, link, icon) VALUES (NULL, 'admin', ?, ?, ?, ?)"
        )->execute([$title, $message, $link, $icon]);
    } catch (PDOException $e) {
        // Notifications are non-critical — never let a failure here break the main action.
    }
}

/**
 * Send a notification to every user of one role (e.g. every student,
 * or every parent) — used for site-wide announcements like "new
 * video/book/product added".
 */
function notify_role($pdo, $role, $title, $message, $link = null, $icon = 'fa-solid fa-bell') {
    try {
        $pdo->prepare(
            "INSERT INTO notifications (user_id, target_role, title, message, link, icon) VALUES (NULL, ?, ?, ?, ?, ?)"
        )->execute([$role, $title, $message, $link, $icon]);
    } catch (PDOException $e) {
        // Notifications are non-critical — never let a failure here break the main action.
    }
}

/**
 * Send a notification to one specific user (e.g. a single teacher,
 * parent, or student) — always private to that one account.
 */
function notify_user($pdo, $user_id, $title, $message, $link = null, $icon = 'fa-solid fa-bell') {
    if (empty($user_id)) return;
    try {
        $pdo->prepare(
            "INSERT INTO notifications (user_id, target_role, title, message, link, icon) VALUES (?, NULL, ?, ?, ?, ?)"
        )->execute([$user_id, $title, $message, $link, $icon]);
    } catch (PDOException $e) {
        // Notifications are non-critical — never let a failure here break the main action.
    }
}