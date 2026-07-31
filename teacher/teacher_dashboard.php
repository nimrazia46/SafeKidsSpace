<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'teacher') {
    header("Location: ../account/login.php");
    exit();
}

$teacher_id   = $_SESSION['id'];
$teacher_name = $_SESSION['fullname'] ?? 'Teacher';

$can_go_live = false;
try {
    $perm_stmt = $pdo->prepare("SELECT can_go_live FROM users WHERE id = ? LIMIT 1");
    $perm_stmt->execute([$teacher_id]);
    $perm_row = $perm_stmt->fetch();
    $can_go_live = !empty($perm_row['can_go_live']);
} catch (PDOException $e) {
    $can_go_live = false;
}

// Quick counts for the hub cards
try {
    $cls_stmt = $pdo->prepare("SELECT COUNT(*) FROM live_classes WHERE teacher_id = ? AND status IN ('Scheduled','Live')");
    $cls_stmt->execute([$teacher_id]);
    $upcoming_classes_count = (int) $cls_stmt->fetchColumn();
} catch (PDOException $e) {
    $upcoming_classes_count = 0;
}

try {
    $vid_stmt = $pdo->prepare("SELECT COUNT(*) FROM program_videos WHERE teacher_id = ? AND status = 'pending'");
    $vid_stmt->execute([$teacher_id]);
    $pending_videos_count = (int) $vid_stmt->fetchColumn();
} catch (PDOException $e) {
    $pending_videos_count = 0;
}

try {
    $qz_stmt = $pdo->prepare("SELECT COUNT(*) FROM quizzes WHERE teacher_id = ? AND status = 'pending'");
    $qz_stmt->execute([$teacher_id]);
    $pending_quizzes_count = (int) $qz_stmt->fetchColumn();
} catch (PDOException $e) {
    $pending_quizzes_count = 0;
}

try {
    $qr_stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM quiz_results qr JOIN quizzes q ON q.id = qr.quiz_id WHERE q.teacher_id = ?"
    );
    $qr_stmt->execute([$teacher_id]);
    $total_quiz_attempts = (int) $qr_stmt->fetchColumn();
} catch (PDOException $e) {
    $total_quiz_attempts = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Teacher Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/layout.css">
    <link rel="stylesheet" href="../assets/teacher.css">
</head>
<body>

<?php include __DIR__ . '/../includes/teacher_navbar.php'; ?>

<div class="main-content td-wrap">

    <div class="td-hero">
        <div class="td-hero-left">
            <img
                src="<?= !empty($_SESSION['profile_pic']) ? '../' . htmlspecialchars($_SESSION['profile_pic']) : '../assets/images/default-avatar.png' ?>"
                class="td-hero-avatar"
                alt="Profile Photo">
            <div>
                <h1 class="td-hero-title">Welcome, <?= htmlspecialchars($teacher_name) ?></h1>
                <p class="td-hero-sub">Academy Command Deck — manage classes, videos &amp; quizzes</p>
                <span class="td-hero-badge">
                    <i class="fa-solid fa-graduation-cap"></i> Instructor
                    <?= $can_go_live ? '' : ' — Live classes pending admin approval' ?>
                </span>
            </div>
        </div>
    </div>

    <p class="td-section-title"><i class="fa-solid fa-grip"></i> Manage</p>

    <div class="td-form-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">

        <a href="teacher_live_classes.php" class="td-card" style="text-decoration:none; color:inherit; display:block; cursor:pointer;">
            <div class="td-card-header" style="color:#38bdf8; border-color:rgba(56,189,248,.15);">
                <i class="fa-solid fa-satellite-dish"></i> Live Classes
            </div>
            <p style="color:#94a3b8; font-size:.85rem; margin:0;">Schedule, launch, and host live classroom sessions.</p>
            <?php if ($upcoming_classes_count > 0): ?>
                <span class="td-status-pill td-status-scheduled" style="margin-top:12px; display:inline-block;">
                    <?= $upcoming_classes_count ?> upcoming
                </span>
            <?php endif; ?>
        </a>

        <a href="teacher_program_videos.php" class="td-card" style="text-decoration:none; color:inherit; display:block; cursor:pointer;">
            <div class="td-card-header" style="color:#34d399; border-color:rgba(52,211,153,.15);">
                <i class="fa-solid fa-clapperboard"></i> Program Videos
            </div>
            <p style="color:#94a3b8; font-size:.85rem; margin:0;">Submit and manage your program video library.</p>
            <?php if ($pending_videos_count > 0): ?>
                <span class="td-status-pill td-status-pending" style="margin-top:12px; display:inline-block;">
                    <?= $pending_videos_count ?> awaiting review
                </span>
            <?php endif; ?>
        </a>

        <a href="teacher_quizzes.php" class="td-card" style="text-decoration:none; color:inherit; display:block; cursor:pointer;">
            <div class="td-card-header" style="color:#c084fc; border-color:rgba(192,132,252,.15);">
                <i class="fa-solid fa-circle-question"></i> Quizzes
            </div>
            <p style="color:#94a3b8; font-size:.85rem; margin:0;">Create quizzes and submit them for admin review.</p>
            <?php if ($pending_quizzes_count > 0): ?>
                <span class="td-status-pill td-status-pending" style="margin-top:12px; display:inline-block;">
                    <?= $pending_quizzes_count ?> awaiting review
                </span>
            <?php endif; ?>
        </a>

        <a href="teacher_quiz_results.php" class="td-card" style="text-decoration:none; color:inherit; display:block; cursor:pointer;">
            <div class="td-card-header" style="color:#f472b6; border-color:rgba(244,114,182,.15);">
                <i class="fa-solid fa-chart-simple"></i> Quiz Results
            </div>
            <p style="color:#94a3b8; font-size:.85rem; margin:0;">See how students scored on your quizzes and grant reattempts.</p>
            <?php if ($total_quiz_attempts > 0): ?>
                <span class="td-status-pill td-status-scheduled" style="margin-top:12px; display:inline-block;">
                    <?= $total_quiz_attempts ?> submissions
                </span>
            <?php endif; ?>
        </a>

        <a href="teacher_orders.php" class="td-card" style="text-decoration:none; color:inherit; display:block; cursor:pointer;">
            <div class="td-card-header" style="color:#facc15; border-color:rgba(250,204,21,.15);">
                <i class="fa-solid fa-receipt"></i> My Orders
            </div>
            <p style="color:#94a3b8; font-size:.85rem; margin:0;">Your own Kids Store order history.</p>
        </a>

    </div>

</div><!-- /.main-content -->

</body>
</html>