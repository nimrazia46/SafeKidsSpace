<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

// Strict Security: admins only
$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

// Fetch platform stats
try {
    $total_users   = $pdo->query("SELECT COUNT(id) as total FROM users")->fetch()['total'] ?? 0;
    $count_kids    = $pdo->query("SELECT COUNT(id) as total FROM users WHERE LOWER(role) IN ('student', 'child')")->fetch()['total'] ?? 0;
    $count_parents = $pdo->query("SELECT COUNT(id) as total FROM users WHERE LOWER(role) = 'parent'")->fetch()['total'] ?? 0;
    $count_teachers = $pdo->query("SELECT COUNT(id) as total FROM users WHERE LOWER(role) = 'teacher'")->fetch()['total'] ?? 0;

    $pending_deactivations = $pdo->query("SELECT COUNT(*) FROM deactivation_requests WHERE status = 'pending'")->fetchColumn();
    $pending_payments_count = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
    $pending_videos_count  = $pdo->query("SELECT COUNT(*) FROM program_videos WHERE status = 'pending'")->fetchColumn();
    $pending_quizzes_count = $pdo->query("SELECT COUNT(*) FROM quizzes WHERE status = 'pending'")->fetchColumn();
    $pending_orders_count  = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
    $pending_career_applications = $pdo->query("SELECT COUNT(*) FROM career_applications WHERE status = 'pending'")->fetchColumn();
} catch (PDOException $e) {
    $total_users = $count_kids = $count_parents = $count_teachers = 0;
    $pending_deactivations = $pending_payments_count = $pending_videos_count = $pending_quizzes_count = $pending_orders_count = $pending_career_applications = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Admin Command Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/layout.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>

<?php include __DIR__ . '/../includes/admin_navbar.php'; ?>

<div class="main-content ad-wrap">

    <!-- ═══════════════════════════════════════════
         HERO BANNER
    ═══════════════════════════════════════════ -->
    <div class="ad-hero">
        <div class="ad-hero-left">
            <div class="ad-hero-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
                <h1 class="ad-hero-title">SafeKidsSpace Core</h1>
                <p class="ad-hero-sub">Central Management System — users, analytics &amp; platform controls</p>
                <span class="ad-hero-badge"><i class="fa-solid fa-circle-check"></i> Admin — All Systems Active</span>
            </div>
        </div>
        <div class="ad-hero-right">
            <a href="../index.php" class="ad-back-btn">
                <i class="fa-solid fa-house"></i> Back to Home
            </a>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════
         STAT CARDS
    ═══════════════════════════════════════════ -->
    <p class="ad-section-title"><i class="fa-solid fa-chart-bar"></i> Platform Analytics</p>

    <div class="ad-stat-grid">

        <div class="ad-stat-card users">
            <div class="ad-stat-icon" style="background:rgba(56,189,248,.10); color:#38bdf8;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="ad-stat-val"><?= number_format($total_users) ?></div>
                <div class="ad-stat-label">Total Users</div>
            </div>
        </div>

        <div class="ad-stat-card kids">
            <div class="ad-stat-icon" style="background:rgba(52,211,153,.10); color:#34d399;">
                <i class="fa-solid fa-child-reaching"></i>
            </div>
            <div>
                <div class="ad-stat-val" style="color:#34d399;"><?= number_format($count_kids) ?></div>
                <div class="ad-stat-label">Active Kids</div>
            </div>
        </div>

        <div class="ad-stat-card parents">
            <div class="ad-stat-icon" style="background:rgba(250,204,21,.10); color:#facc15;">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div>
                <div class="ad-stat-val" style="color:#facc15;"><?= number_format($count_parents) ?></div>
                <div class="ad-stat-label">Parent Monitors</div>
            </div>
        </div>

        <div class="ad-stat-card teachers">
            <div class="ad-stat-icon" style="background:rgba(192,132,252,.10); color:#c084fc;">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>
            <div>
                <div class="ad-stat-val" style="color:#c084fc;"><?= number_format($count_teachers) ?></div>
                <div class="ad-stat-label">Teachers</div>
            </div>
        </div>

    </div>

    <!-- ═══════════════════════════════════════════
         QUICK LINKS — one card per admin section
    ═══════════════════════════════════════════ -->
    <p class="ad-section-title" style="margin-top:36px;"><i class="fa-solid fa-grip"></i> Manage</p>

    <div class="ad-programs-grid">

        <a href="admin_users.php" class="ad-program-card" style="text-decoration:none; color:inherit; display:block; position:relative;">
            <div class="ad-program-icon"><i class="fa-solid fa-users"></i></div>
            <h4>Users</h4>
            <p class="ad-program-subjects">Accounts &amp; deactivation requests</p>
            <?php if ($pending_deactivations > 0): ?>
                <span class="ad-permission-pill ad-permission-pending" style="margin-top:14px; display:inline-block;">
                    <i class="fa-solid fa-hourglass-half"></i> <?= intval($pending_deactivations) ?> pending
                </span>
            <?php endif; ?>
        </a>

        <a href="admin_live_classes.php" class="ad-program-card" style="text-decoration:none; color:inherit; display:block;">
            <div class="ad-program-icon"><i class="fa-solid fa-satellite-dish"></i></div>
            <h4>Live Classes</h4>
            <p class="ad-program-subjects">Teacher permissions &amp; scheduling oversight</p>
        </a>

        <a href="admin_program_videos.php" class="ad-program-card" style="text-decoration:none; color:inherit; display:block; position:relative;">
            <div class="ad-program-icon"><i class="fa-solid fa-graduation-cap"></i></div>
            <h4>Program Videos</h4>
            <p class="ad-program-subjects">Teacher-submitted videos — add, approve, delete</p>
            <?php if ($pending_videos_count > 0): ?>
                <span class="ad-permission-pill ad-permission-pending" style="margin-top:14px; display:inline-block;">
                    <i class="fa-solid fa-hourglass-half"></i> <?= intval($pending_videos_count) ?> pending
                </span>
            <?php endif; ?>
        </a>

        <a href="admin_manage_programs.php" class="ad-program-card" style="text-decoration:none; color:inherit; display:block;">
            <div class="ad-program-icon"><i class="fa-solid fa-layer-group"></i></div>
            <h4>Manage Programs</h4>
            <p class="ad-program-subjects">Add programs, activate/deactivate, assign teachers</p>
        </a>

        <a href="admin_quizzes.php" class="ad-program-card" style="text-decoration:none; color:inherit; display:block; position:relative;">
            <div class="ad-program-icon"><i class="fa-solid fa-circle-question"></i></div>
            <h4>Assigned Quiz</h4>
            <p class="ad-program-subjects">Teacher-submitted quizzes — approve, delete</p>
            <?php if ($pending_quizzes_count > 0): ?>
                <span class="ad-permission-pill ad-permission-pending" style="margin-top:14px; display:inline-block;">
                    <i class="fa-solid fa-hourglass-half"></i> <?= intval($pending_quizzes_count) ?> pending
                </span>
            <?php endif; ?>
        </a>

        <a href="admin_payments.php" class="ad-program-card" style="text-decoration:none; color:inherit; display:block; position:relative;">
            <div class="ad-program-icon"><i class="fa-solid fa-money-check-dollar"></i></div>
            <h4>Parent Payments</h4>
            <p class="ad-program-subjects">Program payment confirmations</p>
            <?php if ($pending_payments_count > 0): ?>
                <span class="ad-permission-pill ad-permission-pending" style="margin-top:14px; display:inline-block;">
                    <i class="fa-solid fa-hourglass-half"></i> <?= intval($pending_payments_count) ?> pending
                </span>
            <?php endif; ?>
        </a>

        <a href="admin_videos.php" class="ad-program-card" style="text-decoration:none; color:inherit; display:block;">
            <div class="ad-program-icon"><i class="fa-solid fa-video"></i></div>
            <h4>Videos</h4>
            <p class="ad-program-subjects">Public video library</p>
        </a>

        <a href="admin_products.php" class="ad-program-card" style="text-decoration:none; color:inherit; display:block;">
            <div class="ad-program-icon"><i class="fa-solid fa-box-open"></i></div>
            <h4>Store Products</h4>
            <p class="ad-program-subjects">Kids store inventory &amp; stock</p>
        </a>

        <a href="admin_orders.php" class="ad-program-card" style="text-decoration:none; color:inherit; display:block; position:relative;">
            <div class="ad-program-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <h4>Store Orders</h4>
            <p class="ad-program-subjects">Review &amp; confirm customer orders</p>
            <?php if ($pending_orders_count > 0): ?>
                <span class="ad-permission-pill ad-permission-pending" style="margin-top:14px; display:inline-block;">
                    <i class="fa-solid fa-hourglass-half"></i> <?= intval($pending_orders_count) ?> pending
                </span>
            <?php endif; ?>
        </a>

        <a href="admin_books.php" class="ad-program-card" style="text-decoration:none; color:inherit; display:block;">
            <div class="ad-program-icon"><i class="fa-solid fa-book-open"></i></div>
            <h4>Library Books</h4>
            <p class="ad-program-subjects">Reading library catalogue</p>
        </a>

        <a href="admin_fun_quiz.php" class="ad-program-card" style="text-decoration:none; color:inherit; display:block;">
            <div class="ad-program-icon"><i class="fa-solid fa-face-laugh-wink"></i></div>
            <h4>Fun Quiz</h4>
            <p class="ad-program-subjects">Free-to-play quiz questions</p>
        </a>

        <a href="admin_career_applications.php" class="ad-program-card" style="text-decoration:none; color:inherit; display:block; position:relative;">
            <div class="ad-program-icon"><i class="fa-solid fa-briefcase"></i></div>
            <h4>Career Applications</h4>
            <p class="ad-program-subjects">Teacher job applications &amp; CVs</p>
            <?php if ($pending_career_applications > 0): ?>
                <span class="ad-permission-pill ad-permission-pending" style="margin-top:14px; display:inline-block;">
                    <i class="fa-solid fa-hourglass-half"></i> <?= intval($pending_career_applications) ?> pending
                </span>
            <?php endif; ?>
        </a>

    </div>

</div><!-- /.main-content -->

</body>
</html>