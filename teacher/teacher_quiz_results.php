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

$success_message = '';
$error_message   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Teacher grants a one-time reattempt to a child who failed one of their quizzes
    if (isset($_POST['action']) && $_POST['action'] === 'grant_quiz_retry') {
        $gr_quiz_id  = intval($_POST['quiz_id'] ?? 0);
        $gr_child_id = intval($_POST['child_id'] ?? 0);
        try {
            $own_stmt = $pdo->prepare("SELECT id FROM quizzes WHERE id = ? AND teacher_id = ? AND status = 'approved'");
            $own_stmt->execute([$gr_quiz_id, $teacher_id]);
            if (!$own_stmt->fetch()) {
                $error_message = "Quiz not found, not yours, or not live yet.";
            } else {
                $open_stmt = $pdo->prepare(
                    "SELECT id FROM quiz_retry_permissions WHERE quiz_id = ? AND child_id = ? AND consumed_at IS NULL"
                );
                $open_stmt->execute([$gr_quiz_id, $gr_child_id]);
                if ($open_stmt->fetch()) {
                    $error_message = "This student already has an unused reattempt permission for this quiz.";
                } else {
                    $pdo->prepare(
                        "INSERT INTO quiz_retry_permissions (child_id, quiz_id, granted_by) VALUES (?, ?, ?)"
                    )->execute([$gr_child_id, $gr_quiz_id, $teacher_id]);
                    $success_message = "🔓 Reattempt allowed — the student can attempt this quiz one more time.";
                    notify_user($pdo, $gr_child_id, "Quiz reattempt allowed", "Your teacher allowed you to reattempt a quiz.", "learning.php", "fa-solid fa-rotate-right");
                }
            }
        } catch (PDOException $e) {
            $error_message = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch marks/results for this teacher's LIVE (approved) quizzes — every
// enrolled child in that program, their latest attempt, pass/fail, and
// whether they currently have an unused reattempt permission.
$my_quiz_results = []; // quiz_id => list of child rows
try {
    $live_quiz_stmt = $pdo->prepare(
        "SELECT id, title, program_id, slot_number FROM quizzes WHERE teacher_id = ? AND status = 'approved'"
    );
    $live_quiz_stmt->execute([$teacher_id]);
    foreach ($live_quiz_stmt->fetchAll() as $live_quiz) {
        $rows_stmt = $pdo->prepare(
            "SELECT u.id AS child_id, u.fullname,
                    qr.score, qr.total, qr.percentage, qr.passed,
                    (SELECT COUNT(*) FROM quiz_results WHERE user_id = u.id AND quiz_id = ?) AS attempt_count,
                    (SELECT id FROM quiz_retry_permissions WHERE quiz_id = ? AND child_id = u.id AND consumed_at IS NULL LIMIT 1) AS open_permission
             FROM enrollments e
             JOIN users u ON u.id = e.child_id
             LEFT JOIN quiz_results qr ON qr.id = (
                 SELECT id FROM quiz_results WHERE quiz_id = ? AND user_id = u.id ORDER BY id DESC LIMIT 1
             )
             WHERE e.program_id = ? AND e.status = 'active'
             ORDER BY u.fullname ASC"
        );
        $rows_stmt->execute([$live_quiz['id'], $live_quiz['id'], $live_quiz['id'], $live_quiz['program_id']]);
        $my_quiz_results[$live_quiz['id']] = [
            'title' => $live_quiz['title'],
            'slot_number' => $live_quiz['slot_number'],
            'students' => $rows_stmt->fetchAll(),
        ];
    }
} catch (PDOException $e) {
    $my_quiz_results = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Quiz Results</title>
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
                <h1 class="td-hero-title">Quiz Results</h1>
                <p class="td-hero-sub">See how your students are doing and grant reattempts</p>
                <span class="td-hero-badge"><i class="fa-solid fa-graduation-cap"></i> Instructor</span>
            </div>
        </div>
        <div class="td-hero-right">
            <a href="teacher_quizzes.php" class="td-btn" style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); text-decoration:none;">
                <i class="fa-solid fa-circle-question"></i> Manage Quizzes
            </a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="td-alert td-alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <?= $success_message ?>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="td-alert td-alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= $error_message ?>
        </div>
    <?php endif; ?>

    <p class="td-section-title"><i class="fa-solid fa-chart-simple"></i> Student Results</p>

    <div class="td-card" style="margin-bottom:36px;">
        <?php if (!empty($my_quiz_results)): ?>
            <?php foreach ($my_quiz_results as $rq_id => $rq):
                $rq_students = $rq['students'];
            ?>
                <div class="td-class-strip" style="flex-direction:column; align-items:stretch; gap:12px; margin-bottom:18px;">
                    <p class="td-class-title" style="margin:0;">
                        <span class="td-status-pill" style="background:rgba(192,132,252,.12); color:#c084fc; margin-right:6px;">Quiz <?= intval($rq['slot_number'] ?? 1) ?></span>
                        <?= htmlspecialchars($rq['title']) ?>
                    </p>

                    <?php if (empty($rq_students)): ?>
                        <p style="color:#64748b; font-size:.82rem; margin:0;">No enrolled students yet for this program.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table style="width:100%; min-width:640px; border-collapse:collapse; font-size:.82rem; table-layout:fixed;">
                                <colgroup>
                                    <col>
                                    <col style="width:100px;">
                                    <col style="width:150px;">
                                    <col style="width:140px;">
                                    <col style="width:210px;">
                                </colgroup>
                                <thead>
                                    <tr style="color:#94a3b8; text-align:left; border-bottom:1px solid rgba(255,255,255,.08);">
                                        <th style="padding:8px 6px;">Student</th>
                                        <th style="padding:8px 6px;">Attempts</th>
                                        <th style="padding:8px 6px;">Last Score</th>
                                        <th style="padding:8px 6px;">Result</th>
                                        <th style="padding:8px 6px;">Reattempt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rq_students as $stu):
                                    $has_attempt = $stu['attempt_count'] > 0 && $stu['total'] !== null;
                                    $stu_passed  = $has_attempt && intval($stu['passed']) === 1;
                                ?>
                                    <tr style="border-bottom:1px solid rgba(255,255,255,.04);">
                                        <td style="padding:8px 6px; color:#f8fafc; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($stu['fullname']) ?></td>
                                        <td style="padding:8px 6px; color:#94a3b8;"><?= intval($stu['attempt_count']) ?></td>
                                        <td style="padding:8px 6px; color:#94a3b8;">
                                            <?= $has_attempt ? intval($stu['score']) . '/' . intval($stu['total']) . ' (' . round($stu['percentage']) . '%)' : '—' ?>
                                        </td>
                                        <td style="padding:8px 6px;">
                                            <?php if (!$has_attempt): ?>
                                                <span style="color:#64748b;">Not attempted</span>
                                            <?php elseif ($stu_passed): ?>
                                                <span style="color:#34d399;"><i class="fa-solid fa-circle-check"></i> Passed</span>
                                            <?php else: ?>
                                                <span style="color:#f87171;"><i class="fa-solid fa-circle-xmark"></i> Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding:8px 6px;">
                                            <?php if ($has_attempt && !$stu_passed): ?>
                                                <?php if (!empty($stu['open_permission'])): ?>
                                                    <span style="color:#c084fc; font-size:.78rem;"><i class="fa-solid fa-unlock"></i> Reattempt granted</span>
                                                <?php else: ?>
                                                    <form action="teacher_quiz_results.php" method="POST">
                                                        <input type="hidden" name="action" value="grant_quiz_retry">
                                                        <input type="hidden" name="quiz_id" value="<?= intval($rq_id) ?>">
                                                        <input type="hidden" name="child_id" value="<?= intval($stu['child_id']) ?>">
                                                        <button type="submit" class="td-btn td-btn-green" style="padding:5px 10px; font-size:.74rem; white-space:nowrap;">
                                                            <i class="fa-solid fa-rotate-right"></i> Allow Reattempt
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color:#64748b;">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="td-empty">
                <i class="fa-solid fa-chart-simple"></i>
                <p>No live quizzes yet — approved quizzes will show student results here.</p>
            </div>
        <?php endif; ?>
    </div>

</div><!-- /.main-content -->

</body>
</html>
