<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'parent') {
    header("Location: ../account/login.php");
    exit();
}

$parent_id = $_SESSION['id'];

// ── Fetch linked children (for the switcher tabs) ─────────────
$linked_children = [];
try {
    $s = $pdo->prepare("SELECT u.id, u.fullname, u.profile_pic FROM parent_monitoring pm JOIN users u ON u.id = pm.child_id WHERE pm.parent_id = ?");
    $s->execute([$parent_id]);
    $linked_children = $s->fetchAll();
} catch (PDOException $e) {
    $linked_children = [];
}

$selected_child_id = intval($_GET['child_id'] ?? ($linked_children[0]['id'] ?? 0));

$child_info = [];
foreach ($linked_children as $c) {
    if ($c['id'] == $selected_child_id) { $child_info = $c; break; }
}

// If nothing is linked, or the child_id doesn't belong to this parent, bounce to the hub
if (empty($linked_children) || empty($child_info)) {
    header("Location: parent_dashboard.php");
    exit();
}

$child_activities   = [];
$child_quiz_results = [];
$child_badges       = [];
$child_stats        = ['total_xp' => 0, 'total_time' => 0, 'activity_count' => 0];
$monitor_row        = null;

try {
    $s = $pdo->prepare("SELECT * FROM kid_activity_logs WHERE child_id = ? ORDER BY created_at DESC LIMIT 20");
    $s->execute([$selected_child_id]);
    $child_activities = $s->fetchAll();
    foreach ($child_activities as $act) {
        $child_stats['total_xp']       += intval($act['points_earned']);
        $child_stats['total_time']     += intval($act['duration_minutes']);
        $child_stats['activity_count'] += 1;
    }
} catch (PDOException $e) {
    $child_activities = [];
}

try {
    $s = $pdo->prepare("
        SELECT qr.*, q.title AS quiz_title, q.category
        FROM quiz_results qr JOIN quizzes q ON q.id = qr.quiz_id
        WHERE qr.user_id = ? ORDER BY qr.completed_at DESC LIMIT 10
    ");
    $s->execute([$selected_child_id]);
    $child_quiz_results = $s->fetchAll();
} catch (PDOException $e) {
    $child_quiz_results = [];
}

try {
    $s = $pdo->prepare("SELECT b.*, ub.earned_at FROM user_badges ub JOIN badges b ON b.id = ub.badge_id WHERE ub.user_id = ?");
    $s->execute([$selected_child_id]);
    $child_badges = $s->fetchAll();
} catch (PDOException $e) {
    $child_badges = [];
}

try {
    $mon = $pdo->prepare("SELECT last_watched_video, last_action, updated_at FROM parent_monitoring WHERE parent_id = ? AND child_id = ?");
    $mon->execute([$parent_id, $selected_child_id]);
    $monitor_row = $mon->fetch();
} catch (PDOException $e) {
    $monitor_row = null;
}

function activityIcon($type) {
    $map = [
        'video'       => ['fa-play-circle',    '#38bdf8'],
        'video_watch' => ['fa-play-circle',    '#38bdf8'],
        'quiz'        => ['fa-circle-question', '#c084fc'],
        'course'      => ['fa-graduation-cap',  '#34d399'],
        'book'        => ['fa-book-open',       '#facc15'],
        'task'        => ['fa-check-circle',    '#fb923c'],
        'homework'    => ['fa-book-atlas',      '#f472b6'],
        'store'       => ['fa-store',           '#a78bfa'],
        'enrollment'  => ['fa-user-plus',       '#34d399'],
    ];
    return $map[$type] ?? ['fa-star', '#94a3b8'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Activity &amp; Progress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/layout.css">
    <link rel="stylesheet" href="../assets/parent.css">
</head>
<body>

<?php include __DIR__ . '/../includes/parent_navbar.php'; ?>

<div class="main-content pd-wrap">

    <div class="pd-hero">
        <div class="pd-hero-left">
            <img
                src="<?= !empty($_SESSION['profile_pic']) ? '../' . htmlspecialchars($_SESSION['profile_pic']) : '../assets/images/default-avatar.png' ?>"
                class="pd-hero-avatar"
                alt="Profile Photo">
            <div>
                <h1 class="pd-hero-title">Activity &amp; Progress</h1>
                <p class="pd-hero-sub">What <?= htmlspecialchars($child_info['fullname']) ?> has been up to</p>
                <span class="pd-hero-badge"><i class="fa-solid fa-user-shield"></i> Parent Account</span>
            </div>
        </div>
        <div class="pd-hero-right">
            <a href="parent_dashboard.php" class="pd-btn" style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); text-decoration:none;">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="pd-children-tabs" style="margin-bottom:28px;">
        <?php foreach ($linked_children as $child): ?>
            <a href="parent_activity.php?child_id=<?= $child['id'] ?>" class="pd-child-tab <?= $child['id'] == $selected_child_id ? 'active' : '' ?>">
                <img src="<?= !empty($child['profile_pic']) ? '../' . htmlspecialchars($child['profile_pic']) : 'https://cdn-icons-png.flaticon.com/512/4333/4333609.png' ?>" alt="">
                <?= htmlspecialchars($child['fullname']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($monitor_row) && (!empty($monitor_row['last_watched_video']) || !empty($monitor_row['last_action']))): ?>
        <div class="pd-monitor-row">
            <i class="fa-solid fa-satellite-dish"></i>
            <div style="flex:1; display:flex; flex-wrap:wrap; gap:24px;">
                <?php if (!empty($monitor_row['last_watched_video'])): ?>
                    <div>
                        <strong>Last Watched Video</strong>
                        <?= htmlspecialchars($monitor_row['last_watched_video']) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($monitor_row['last_action'])): ?>
                    <div>
                        <strong>Last Action</strong>
                        <?= htmlspecialchars($monitor_row['last_action']) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($monitor_row['updated_at'])): ?>
                    <div>
                        <strong>Last Seen</strong>
                        <?= date('M d, Y — h:i A', strtotime($monitor_row['updated_at'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="pd-stat-grid">
        <div class="pd-stat-card">
            <div class="pd-stat-icon" style="background:rgba(56,189,248,.12); color:#38bdf8;">
                <i class="fa-solid fa-bolt-lightning"></i>
            </div>
            <div>
                <div class="pd-stat-val"><?= number_format($child_stats['total_xp']) ?></div>
                <div class="pd-stat-label">Total XP Earned</div>
            </div>
        </div>
        <div class="pd-stat-card">
            <div class="pd-stat-icon" style="background:rgba(192,132,252,.12); color:#c084fc;">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div>
                <div class="pd-stat-val"><?= $child_stats['total_time'] ?><span style="font-size:.8rem; color:#64748b;"> min</span></div>
                <div class="pd-stat-label">Time Spent Learning</div>
            </div>
        </div>
        <div class="pd-stat-card">
            <div class="pd-stat-icon" style="background:rgba(52,211,153,.12); color:#34d399;">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <div>
                <div class="pd-stat-val"><?= $child_stats['activity_count'] ?></div>
                <div class="pd-stat-label">Activities Completed</div>
            </div>
        </div>
        <div class="pd-stat-card">
            <div class="pd-stat-icon" style="background:rgba(250,204,21,.1); color:#facc15;">
                <i class="fa-solid fa-medal"></i>
            </div>
            <div>
                <div class="pd-stat-val"><?= count($child_badges) ?></div>
                <div class="pd-stat-label">Badges Earned</div>
            </div>
        </div>
    </div>

    <p class="pd-section-title" style="margin-top:28px;"><i class="fa-solid fa-wave-square" style="color:#38bdf8"></i> Recent Activity Log</p>

    <div class="pd-card" style="margin-bottom:32px;">
        <?php if (!empty($child_activities)): ?>
            <div style="max-height:440px; overflow-y:auto; padding-right:4px;">
                <?php foreach ($child_activities as $act):
                    [$icon, $color] = activityIcon($act['activity_type']);
                ?>
                    <div class="pd-activity-item">
                        <div class="pd-act-icon" style="background:<?= $color ?>1a; color:<?= $color ?>;">
                            <i class="fa-solid <?= $icon ?>"></i>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <p class="pd-act-name"><?= htmlspecialchars($act['activity_name']) ?></p>
                            <div class="pd-act-meta">
                                <span><i class="fa-solid fa-tag" style="font-size:.65rem;"></i> <?= htmlspecialchars($act['activity_type']) ?></span>
                                <?php if ($act['duration_minutes'] > 0): ?>
                                    <span><i class="fa-regular fa-clock" style="font-size:.65rem;"></i> <?= $act['duration_minutes'] ?> min</span>
                                <?php endif; ?>
                                <span><i class="fa-regular fa-calendar" style="font-size:.65rem;"></i> <?= date('M d, h:i A', strtotime($act['created_at'])) ?></span>
                            </div>
                        </div>
                        <?php if ($act['points_earned'] > 0): ?>
                            <div class="pd-xp-pill">+<?= $act['points_earned'] ?> XP</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="pd-empty">
                <i class="fa-solid fa-satellite"></i>
                <p>No activities logged yet for <?= htmlspecialchars($child_info['fullname']) ?>.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="pd-two-col">

        <div class="pd-card">
            <div class="pd-card-header" style="color:#c084fc; border-color:rgba(192,132,252,.15);">
                <i class="fa-solid fa-circle-question"></i> Quiz Results
            </div>
            <?php if (!empty($child_quiz_results)): ?>
                <div style="overflow-x:auto;">
                    <table class="pd-quiz-table">
                        <thead>
                            <tr><th>Quiz</th><th>Category</th><th>Score</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($child_quiz_results as $qr):
                                $pct = floatval($qr['percentage'] ?? 0);
                                $scoreClass = $pct >= 75 ? 'pd-score-high' : ($pct >= 50 ? 'pd-score-mid' : 'pd-score-low');
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($qr['quiz_title']) ?></td>
                                    <td><span class="pd-cat-tag"><?= htmlspecialchars($qr['category'] ?? '—') ?></span></td>
                                    <td>
                                        <span class="pd-score-pill <?= $scoreClass ?>">
                                            <?= $qr['score'] ?>/<?= $qr['total'] ?> (<?= number_format($pct, 0) ?>%)
                                        </span>
                                    </td>
                                    <td style="color:#475569; font-size:.78rem;"><?= date('M d, Y', strtotime($qr['completed_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="pd-empty">
                    <i class="fa-solid fa-circle-question"></i>
                    <p>No quiz results yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="pd-card">
            <div class="pd-card-header" style="color:#facc15; border-color:rgba(250,204,21,.15);">
                <i class="fa-solid fa-trophy"></i> Achievements &amp; Badges
            </div>
            <?php if (!empty($child_badges)): ?>
                <div class="pd-badges-grid">
                    <?php foreach ($child_badges as $b): ?>
                        <div class="pd-badge-item">
                            <div class="pd-badge-icon">🏆</div>
                            <div class="pd-badge-name"><?= htmlspecialchars($b['title']) ?></div>
                            <?php if (!empty($b['description'])): ?>
                                <div class="pd-badge-date"><?= htmlspecialchars($b['description']) ?></div>
                            <?php endif; ?>
                            <div class="pd-badge-date"><?= date('M d, Y', strtotime($b['earned_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="pd-empty">
                    <i class="fa-solid fa-medal"></i>
                    <p>No badges earned yet — keep learning!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /.main-content -->

</body>
</html>
