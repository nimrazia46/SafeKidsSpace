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

// Check whether the admin has granted this teacher permission to go live
$can_go_live = false;
try {
    $perm_stmt = $pdo->prepare("SELECT can_go_live FROM users WHERE id = ? LIMIT 1");
    $perm_stmt->execute([$teacher_id]);
    $perm_row = $perm_stmt->fetch();
    $can_go_live = !empty($perm_row['can_go_live']);
} catch (PDOException $e) {
    $can_go_live = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Schedule live class (only allowed once admin has granted permission)
    if (isset($_POST['action']) && $_POST['action'] === 'schedule_class') {
        if (!$can_go_live) {
            $error_message = "🔒 You don't have permission to host live classes yet. Please wait for admin approval.";
        } else {
            $title   = trim($_POST['class_title']);
            $subject = trim($_POST['subject_tag']);
            $link    = trim($_POST['meeting_link']);
            $time    = $_POST['scheduled_time'];
            if (!empty($title) && !empty($link) && !empty($time)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO live_classes (teacher_id, class_title, subject_tag, meeting_link, scheduled_time, status) VALUES (?, ?, ?, ?, ?, 'Scheduled')");
                    $stmt->execute([$teacher_id, $title, $subject, $link, $time]);
                    $success_message = "🚀 Live classroom scheduled successfully!";
                    notify_admins(
                        $pdo,
                        "New live class scheduled",
                        "$teacher_name scheduled \"$title\" for " . date('M d, h:i A', strtotime($time)) . ".",
                        "admin/admin_live_classes.php",
                        "fa-solid fa-satellite-dish"
                    );
                } catch (PDOException $e) {
                    $error_message = "Database Error: Unable to schedule class. " . $e->getMessage();
                }
            } else {
                $error_message = "Please fill in all live class fields.";
            }
        }
    }

    // Start a scheduled class ("Go Live")
    if (isset($_POST['action']) && $_POST['action'] === 'start_live') {
        $class_id = intval($_POST['class_id'] ?? 0);
        if ($can_go_live && $class_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE live_classes SET status = 'Live', started_at = NOW() WHERE id = ? AND teacher_id = ?");
                $stmt->execute([$class_id, $teacher_id]);
                $success_message = "📡 You're live! Students can now join your session.";

                $title_stmt = $pdo->prepare("SELECT class_title FROM live_classes WHERE id = ?");
                $title_stmt->execute([$class_id]);
                $live_title = $title_stmt->fetchColumn() ?: 'a live class';

                notify_role(
                    $pdo, 'student',
                    "🔴 Live now!",
                    "$teacher_name just started \"$live_title\" — join now!",
                    "index.php",
                    "fa-solid fa-tower-broadcast"
                );
                notify_admins(
                    $pdo,
                    "Teacher went live",
                    "$teacher_name started \"$live_title\".",
                    "admin/admin_live_classes.php",
                    "fa-solid fa-tower-broadcast"
                );
            } catch (PDOException $e) {
                $error_message = "Database Error: Unable to start the class.";
            }
        } else {
            $error_message = "You don't have permission to start a live class.";
        }
    }

    // End a live class
    if (isset($_POST['action']) && $_POST['action'] === 'end_live') {
        $class_id = intval($_POST['class_id'] ?? 0);
        if ($class_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE live_classes SET status = 'Ended', ended_at = NOW() WHERE id = ? AND teacher_id = ?");
                $stmt->execute([$class_id, $teacher_id]);
                $success_message = "✅ Live session marked as ended.";
            } catch (PDOException $e) {
                $error_message = "Database Error: Unable to end the class.";
            }
        }
    }
}

// Fetch scheduled classes
try {
    $classes_stmt = $pdo->prepare("SELECT * FROM live_classes WHERE teacher_id = ? ORDER BY scheduled_time ASC LIMIT 20");
    $classes_stmt->execute([$teacher_id]);
    $active_classes = $classes_stmt->fetchAll();
} catch (PDOException $e) {
    $active_classes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Live Classes</title>
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
                <h1 class="td-hero-title">Live Class Control</h1>
                <p class="td-hero-sub">Launch, host, and manage your live classroom sessions</p>
                <span class="td-hero-badge"><i class="fa-solid fa-graduation-cap"></i> Instructor</span>
            </div>
        </div>
        <div class="td-hero-right">
            <a href="teacher_dashboard.php" class="td-btn" style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); text-decoration:none;">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
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

    <p class="td-section-title"><i class="fa-solid fa-sliders"></i> Launch Live Classroom</p>

    <div class="td-form-grid" style="grid-template-columns: 1fr; max-width: 560px; margin: 0 auto 36px;">
        <div class="td-card">
            <div class="td-card-header" style="color:#38bdf8; border-color:rgba(56,189,248,.15); justify-content:center;">
                <i class="fa-solid fa-satellite-dish"></i> New Session
            </div>
            <?php if ($can_go_live): ?>
            <form action="teacher_live_classes.php" method="POST">
                <input type="hidden" name="action" value="schedule_class">
                <div class="td-form-group">
                    <label class="td-form-label">Class Title</label>
                    <input type="text" name="class_title" class="td-input" placeholder="e.g., Space Physics Explored" required>
                </div>
                <div class="td-form-group">
                    <label class="td-form-label">Subject Domain</label>
                    <select name="subject_tag" class="td-select">
                        <option value="Science Quest">Science Quest</option>
                        <option value="Math Universe">Math Universe</option>
                        <option value="Cosmic Language">Cosmic Language</option>
                    </select>
                </div>
                <div class="td-form-group">
                    <label class="td-form-label">Meeting URL</label>
                    <input type="url" name="meeting_link" class="td-input" placeholder="https://zoom.us/j/..." required>
                </div>
                <div class="td-form-group">
                    <label class="td-form-label">Launch Time</label>
                    <input type="datetime-local" name="scheduled_time" class="td-input" required>
                </div>
                <button type="submit" class="td-btn td-btn-purple" style="margin-top:4px;">
                    <i class="fa-solid fa-video"></i> Initialize Virtual Room
                </button>
            </form>
            <?php else: ?>
                <div class="td-locked-notice">
                    <i class="fa-solid fa-lock"></i>
                    <p><strong>Awaiting Admin Approval</strong></p>
                    <span>You'll be able to schedule and host live classes as soon as an admin verifies and approves your teacher profile.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <p class="td-section-title"><i class="fa-solid fa-headset"></i> Upcoming Live Channels</p>

    <div class="td-card" style="margin-bottom:36px;">
        <?php if (!empty($active_classes)): ?>
            <?php foreach ($active_classes as $class):
                $class_status = $class['status'] ?? 'Scheduled';
            ?>
                <div class="td-class-strip">
                    <div class="td-class-strip-left">
                        <div class="td-class-icon"><i class="fa-solid fa-video"></i></div>
                        <div>
                            <p class="td-class-title"><?= htmlspecialchars($class['class_title']) ?></p>
                            <span class="td-class-meta">
                                <i class="fa-regular fa-calendar"></i>
                                <?= date('M d, Y — h:i A', strtotime($class['scheduled_time'])) ?>
                                &nbsp;•&nbsp;
                                <span class="td-status-pill td-status-<?= strtolower($class_status) ?>"><?= htmlspecialchars($class_status) ?></span>
                            </span>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                        <span class="td-subject-badge"><?= htmlspecialchars($class['subject_tag']) ?></span>

                        <?php if ($class_status === 'Scheduled'): ?>
                            <form action="teacher_live_classes.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="start_live">
                                <input type="hidden" name="class_id" value="<?= intval($class['id']) ?>">
                                <button type="submit" class="td-connect-btn" style="background:linear-gradient(135deg,#ef4444,#f97316); border:none; cursor:pointer;">
                                    <i class="fa-solid fa-tower-broadcast"></i> Go Live
                                </button>
                            </form>
                        <?php elseif ($class_status === 'Live'): ?>
                            <a href="<?= htmlspecialchars($class['meeting_link']) ?>" target="_blank" class="td-connect-btn">
                                <i class="fa-solid fa-satellite me-1"></i> Connect
                            </a>
                            <form action="teacher_live_classes.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="end_live">
                                <input type="hidden" name="class_id" value="<?= intval($class['id']) ?>">
                                <button type="submit" class="td-connect-btn" style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); cursor:pointer;">
                                    <i class="fa-solid fa-circle-stop"></i> End Class
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="td-subject-badge" style="opacity:.6;">Ended</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="td-empty">
                <i class="fa-solid fa-tower-broadcast"></i>
                <p>No live sessions scheduled yet. Use the form above to launch one.</p>
            </div>
        <?php endif; ?>
    </div>

</div><!-- /.main-content -->

<script>
document.querySelectorAll('.td-alert').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .4s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 5000);
});
</script>
</body>
</html>