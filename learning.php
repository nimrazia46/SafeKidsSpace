<?php
$base = ''; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/includes/db.php';

$current_page = 'learning.php';

// ─────────────────────────────────────────────────────────────
// LIVE CLASSES — feature flag (kept in sync with index.php)
// ─────────────────────────────────────────────────────────────
$live_classes_enabled = false;

$live_classes_feed = [];
if ($live_classes_enabled) {
    try {
        $live_feed_stmt = $pdo->query(
            "SELECT lc.id, lc.class_title, lc.subject_tag, lc.meeting_link, lc.scheduled_time, lc.status,
                    u.fullname AS teacher_name, u.profile_pic AS teacher_pic
             FROM live_classes lc
             JOIN users u ON u.id = lc.teacher_id
             WHERE lc.status IN ('Live', 'Scheduled')
             ORDER BY (lc.status = 'Live') DESC, lc.scheduled_time ASC
             LIMIT 4"
        );
        $live_classes_feed = $live_feed_stmt->fetchAll();
    } catch (PDOException $e) {
        $live_classes_feed = [];
    }
}

// ─────────────────────────────────────────────────────────────
// LIVE CLASSES WAITLIST — "Notify Me" button on the Coming Soon card
// ─────────────────────────────────────────────────────────────
if (!$live_classes_enabled && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join_live_waitlist') {
    if (!isset($_SESSION['id'])) {
        header("Location: account/login.php");
        exit();
    }
    try {
        $join_stmt = $pdo->prepare("INSERT IGNORE INTO live_class_waitlist (user_id) VALUES (?)");
        $join_stmt->execute([$_SESSION['id']]);
    } catch (PDOException $e) {
        // table may not exist yet if migration hasn't run — fail silently
    }
    header("Location: learning.php#liveClasses");
    exit();
}

$live_waitlist_count = 0;
$already_on_waitlist = false;
if (!$live_classes_enabled && isset($_SESSION['id'])) {
    try {
        $count_stmt = $pdo->query("SELECT COUNT(*) AS total FROM live_class_waitlist");
        $live_waitlist_count = $count_stmt->fetch()['total'] ?? 0;

        $check_stmt = $pdo->prepare("SELECT id FROM live_class_waitlist WHERE user_id = ? LIMIT 1");
        $check_stmt->execute([$_SESSION['id']]);
        $already_on_waitlist = (bool) $check_stmt->fetch();
    } catch (PDOException $e) {
        $live_waitlist_count = 0;
        $already_on_waitlist = false;
    }
} elseif (!$live_classes_enabled) {
    try {
        $count_stmt = $pdo->query("SELECT COUNT(*) AS total FROM live_class_waitlist");
        $live_waitlist_count = $count_stmt->fetch()['total'] ?? 0;
    } catch (PDOException $e) {
        $live_waitlist_count = 0;
    }
}

// ─────────────────────────────────────────────────────────────
// LEARNING PROGRAMS — the 4 live programs + this child's access
// ─────────────────────────────────────────────────────────────
$child_id = $_SESSION['id'] ?? 0; // 0 for guests — matches no real child, so everything shows as locked

// ── If a PARENT is viewing this page, figure out their linked children ────
// (needed only for the "Claim Free Trial" popup on each program card — a
// parent must NEVER see unlocked videos/quizzes here, since this page is
// the CHILD's viewing page. $child_id stays as the parent's own session id
// on purpose, so is_enrolled stays false and every card shows the locked
// "ask your parent to enroll" message + Claim button, never real content.)
$lp_role = $_SESSION['role'] ?? null;
$lp_parent_children = [];
if ($lp_role === 'parent' && isset($_SESSION['id'])) {
    try {
        $lp_pc_stmt = $pdo->prepare(
            "SELECT u.id, u.fullname FROM parent_monitoring pm JOIN users u ON u.id = pm.child_id WHERE pm.parent_id = ?"
        );
        $lp_pc_stmt->execute([$_SESSION['id']]);
        $lp_parent_children = $lp_pc_stmt->fetchAll();
    } catch (PDOException $e) {
        $lp_parent_children = [];
    }
}

// ── Reusable: log a distinct video watch (activity feed + parent monitoring
//    + video_watch_progress used for the quiz-unlock count) ─────────────────
function lp_log_video_watch(PDO $pdo, $child_id, $watched_video_id) {
    $vid_stmt = $pdo->prepare(
        "SELECT pv.title, pv.program_id, p.title AS program_title
         FROM program_videos pv JOIN programs p ON p.id = pv.program_id
         WHERE pv.id = ? AND pv.status = 'approved'"
    );
    $vid_stmt->execute([$watched_video_id]);
    $watched_video = $vid_stmt->fetch();

    if (!$watched_video) {
        return false;
    }

    $action_text = "Watched video: " . $watched_video['title'] . " (Program Video — " . $watched_video['program_title'] . ")";

    // Update the "last activity" summary every parent monitoring this child sees
    $upd_mon = $pdo->prepare(
        "UPDATE parent_monitoring SET last_watched_video = ?, last_action = ? WHERE child_id = ?"
    );
    $upd_mon->execute([$watched_video['title'], $action_text, $child_id]);

    // Add a permanent entry to the activity feed
    $ins_log = $pdo->prepare(
        "INSERT INTO kid_activity_logs (child_id, activity_name, activity_type, points_earned, duration_minutes)
         VALUES (?, ?, 'video_watch', 5, 0)"
    );
    $ins_log->execute([$child_id, $action_text]);

    // Record a distinct watch (used to unlock the quiz after N videos).
    // INSERT IGNORE so re-watching the same video doesn't inflate the count.
    $ins_progress = $pdo->prepare(
        "INSERT IGNORE INTO video_watch_progress (child_id, program_id, program_video_id) VALUES (?, ?, ?)"
    );
    $ins_progress->execute([$child_id, $watched_video['program_id'], $watched_video_id]);

    return true;
}

// ── AJAX: log that this child watched a video (called from the player) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_log_video_watch'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['id'])) {
        echo json_encode(['success' => false, 'error' => 'Please log in to track your progress.']);
        exit;
    }
    $watched_video_id = intval($_POST['video_id'] ?? 0);

    try {
        $ok = lp_log_video_watch($pdo, $child_id, $watched_video_id);
        echo json_encode($ok ? ['success' => true] : ['success' => false, 'error' => 'Video not found']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// All active programs
try {
    $lp_programs_stmt = $pdo->query(
        "SELECT id, title, slug, age_range, subjects, monthly_price, icon
         FROM programs WHERE status = 'active' ORDER BY id ASC"
    );
    $lp_programs = $lp_programs_stmt->fetchAll();
} catch (PDOException $e) {
    $lp_programs = [];
}

// This child's enrollment per program (if any)
$lp_enrollments = [];
try {
    $lp_enroll_stmt = $pdo->prepare("SELECT * FROM enrollments WHERE child_id = ?");
    $lp_enroll_stmt->execute([$child_id]);
    foreach ($lp_enroll_stmt->fetchAll() as $row) {
        $lp_enrollments[$row['program_id']] = $row;
    }
} catch (PDOException $e) {
    $lp_enrollments = [];
}

// Approved videos, grouped by program, in slot order
$lp_videos = [];
try {
    $lp_videos_stmt = $pdo->query(
        "SELECT id, program_id, title, video_url, video_type, order_index
         FROM program_videos WHERE status = 'approved'
         ORDER BY program_id ASC, order_index ASC"
    );
    foreach ($lp_videos_stmt->fetchAll() as $v) {
        $lp_videos[$v['program_id']][] = $v;
    }
} catch (PDOException $e) {
    $lp_videos = [];
}

// ── This child's distinct watched-video count per program ──────────
// Quizzes unlock once QUIZ_UNLOCK_VIDEO_COUNT distinct videos (free
// video included) have been watched in that program.
if (!defined('QUIZ_UNLOCK_VIDEO_COUNT')) {
    define('QUIZ_UNLOCK_VIDEO_COUNT', 4);
}

// Free trial video stays unlocked for this many days after enrollment,
// then it re-locks until the parent pays. Shared with parent_programs.php.
if (!defined('TRIAL_DAYS')) {
    define('TRIAL_DAYS', 7);
}
// Quiz 2 (slot_number = 2) unlocks dynamically once ALL approved videos in
// that program have been watched — see $lp_videos below for the per-program count.
// Fixed, system-wide pass mark used to lock a quiz after a passing attempt,
// and to gate whether a failed attempt needs teacher permission to retry.
if (!defined('QUIZ_PASS_PERCENTAGE')) {
    define('QUIZ_PASS_PERCENTAGE', 50);
}
$lp_watched_counts = [];
try {
    $lp_watch_stmt = $pdo->prepare(
        "SELECT program_id, COUNT(*) AS watched_count
         FROM video_watch_progress WHERE child_id = ? GROUP BY program_id"
    );
    $lp_watch_stmt->execute([$child_id]);
    foreach ($lp_watch_stmt->fetchAll() as $row) {
        $lp_watched_counts[$row['program_id']] = intval($row['watched_count']);
    }
} catch (PDOException $e) {
    $lp_watched_counts = [];
}

// ── Handle quiz submission ────────────────────────────────────────
$quiz_just_submitted = null; // will hold ['quiz_id' => x, 'score' => y, 'total' => z, 'percentage' => p]
$quiz_submit_error   = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_submit_quiz'])) {
    if (!isset($_SESSION['id'])) {
        header("Location: account/login.php");
        exit();
    }
    $submitted_quiz_id = intval($_POST['quiz_id'] ?? 0);
    $submitted_answers = $_POST['answers'] ?? []; // [question_id => 'A'/'B'/'C'/'D']

    try {
        // Server-side guard: never trust the client alone — re-check that this
        // child has actually watched enough videos in this quiz's program,
        // using the correct rule for this quiz's slot (1 = fixed count,
        // 2 = every approved video in the program, dynamic).
        $quiz_meta_stmt = $pdo->prepare("SELECT program_id, slot_number FROM quizzes WHERE id = ?");
        $quiz_meta_stmt->execute([$submitted_quiz_id]);
        $quiz_meta = $quiz_meta_stmt->fetch();
        $quiz_program_id = $quiz_meta ? $quiz_meta['program_id'] : null;
        $quiz_slot        = $quiz_meta ? intval($quiz_meta['slot_number'] ?? 1) : 1;

        $watched_for_this_quiz  = $quiz_program_id ? ($lp_watched_counts[$quiz_program_id] ?? 0) : 0;
        $total_videos_in_program = $quiz_program_id ? count($lp_videos[$quiz_program_id] ?? []) : 0;

        if ($quiz_slot === 2) {
            $this_quiz_unlocked = $total_videos_in_program > 0 && $watched_for_this_quiz >= $total_videos_in_program;
        } else {
            $this_quiz_unlocked = $watched_for_this_quiz >= QUIZ_UNLOCK_VIDEO_COUNT;
        }

        if (!$quiz_program_id || !$this_quiz_unlocked) {
            $quiz_submit_error = $quiz_slot === 2
                ? "Quiz is still locked — watch all videos in this program first."
                : "Quiz is still locked — watch " . QUIZ_UNLOCK_VIDEO_COUNT . " videos in this program first.";
        } else {

        // Pass/fail lock: never trust the client — re-check whether this child
        // is even allowed to attempt this quiz right now.
        $prior_stmt = $pdo->prepare(
            "SELECT MAX(passed) AS ever_passed FROM quiz_results WHERE user_id = ? AND quiz_id = ?"
        );
        $prior_stmt->execute([$child_id, $submitted_quiz_id]);
        $prior_row   = $prior_stmt->fetch();
        $ever_passed = $prior_row && $prior_row['ever_passed'] !== null && intval($prior_row['ever_passed']) === 1;

        $perm_stmt = $pdo->prepare(
            "SELECT id FROM quiz_retry_permissions WHERE quiz_id = ? AND child_id = ? AND consumed_at IS NULL ORDER BY id DESC LIMIT 1"
        );
        $perm_stmt->execute([$submitted_quiz_id, $child_id]);
        $open_permission_id = $perm_stmt->fetchColumn();

        $attempt_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM quiz_results WHERE user_id = ? AND quiz_id = ?");
        $attempt_count_stmt->execute([$child_id, $submitted_quiz_id]);
        $already_attempted = intval($attempt_count_stmt->fetchColumn()) > 0;

        if ($ever_passed) {
            $quiz_submit_error = "You already passed this quiz — it's locked now, nice work!";
        } elseif ($already_attempted && !$open_permission_id) {
            $quiz_submit_error = "This quiz is locked after a failed attempt — ask your teacher to allow you another try.";
        } else {

        $q_stmt = $pdo->prepare("SELECT id, correct_answer FROM quiz_questions WHERE quiz_id = ?");
        $q_stmt->execute([$submitted_quiz_id]);
        $questions = $q_stmt->fetchAll();

        $total = count($questions);
        $score = 0;
        foreach ($questions as $q) {
            $given = $submitted_answers[$q['id']] ?? '';
            if ($given === $q['correct_answer']) {
                $score++;
            }
        }
        $percentage = $total > 0 ? round(($score / $total) * 100, 2) : 0;
        $passed     = $percentage >= QUIZ_PASS_PERCENTAGE ? 1 : 0;

        $ins = $pdo->prepare(
            "INSERT INTO quiz_results (user_id, quiz_id, score, total, percentage, passed) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $ins->execute([$child_id, $submitted_quiz_id, $score, $total, $percentage, $passed]);

        // A retry permission is a single, one-time use — consume it now
        // whether this attempt passes or fails.
        if ($open_permission_id) {
            $pdo->prepare("UPDATE quiz_retry_permissions SET consumed_at = NOW() WHERE id = ?")->execute([$open_permission_id]);
        }

        $quiz_just_submitted = ['quiz_id' => $submitted_quiz_id, 'score' => $score, 'total' => $total, 'percentage' => $percentage, 'passed' => $passed];

        // Log to the activity feed too
        $quiz_title_stmt = $pdo->prepare("SELECT title FROM quizzes WHERE id = ?");
        $quiz_title_stmt->execute([$submitted_quiz_id]);
        $quiz_title = $quiz_title_stmt->fetchColumn();
        if ($quiz_title) {
            $log_stmt = $pdo->prepare(
                "INSERT INTO kid_activity_logs (child_id, activity_name, activity_type, points_earned, duration_minutes)
                 VALUES (?, ?, 'quiz', ?, 0)"
            );
            $log_stmt->execute([$child_id, "Completed quiz: $quiz_title ($score/$total)", max(5, $score * 2)]);
        }

        } // end pass/fail lock check
        } // end unlock check
    } catch (PDOException $e) {
        $quiz_just_submitted = null;
    }
}

// ── Fetch program-linked quizzes with their questions, grouped by program ──
$lp_quizzes = [];
try {
    $lp_quiz_stmt = $pdo->query(
        "SELECT id, program_id, slot_number, title, total_questions FROM quizzes WHERE program_id IS NOT NULL AND status = 'approved' ORDER BY slot_number ASC, id ASC"
    );
    foreach ($lp_quiz_stmt->fetchAll() as $quiz) {
        $qq_stmt = $pdo->prepare("SELECT id, question, option_a, option_b, option_c, option_d FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC");
        $qq_stmt->execute([$quiz['id']]);
        $quiz['questions'] = $qq_stmt->fetchAll();
        $lp_quizzes[$quiz['program_id']][] = $quiz;
    }
} catch (PDOException $e) {
    $lp_quizzes = [];
}

// ── Fetch this child's best result per quiz ────────────────────────
$lp_quiz_best_results = [];
try {
    $res_stmt = $pdo->prepare(
        "SELECT quiz_id, MAX(percentage) AS best_percentage, MAX(score) AS best_score, MAX(total) AS total,
                MAX(passed) AS ever_passed, COUNT(*) AS attempt_count
         FROM quiz_results WHERE user_id = ? GROUP BY quiz_id"
    );
    $res_stmt->execute([$child_id]);
    foreach ($res_stmt->fetchAll() as $row) {
        $lp_quiz_best_results[$row['quiz_id']] = $row;
    }
} catch (PDOException $e) {
    $lp_quiz_best_results = [];
}

// ── This child's currently-open (unused) retry permissions, per quiz ──
// Granted by a teacher after a failed attempt; good for exactly one more try.
$lp_open_retry_permissions = [];
try {
    $perm_stmt = $pdo->prepare(
        "SELECT quiz_id FROM quiz_retry_permissions WHERE child_id = ? AND consumed_at IS NULL"
    );
    $perm_stmt->execute([$child_id]);
    foreach ($perm_stmt->fetchAll() as $row) {
        $lp_open_retry_permissions[$row['quiz_id']] = true;
    }
} catch (PDOException $e) {
    $lp_open_retry_permissions = [];
}
?> 
<!DOCTYPE html>
<html lang="en">

<head>
<?php include __DIR__ . '/includes/favicon.php'; ?>

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SafeKidsSpace — Learning Universe</title>
    <link rel="stylesheet" href="assets/layout.css">
</head>
<body>

    <!-- ================= NAVBAR ================= -->
<?php include __DIR__ . '/includes/navbar.php'; ?>
    <!-- ================= MAIN ================= -->
    <div class="container">

        <!-- CONTENT -->

        <main class="main-content" id="content">

            <!-- HERO -->

            <section class="hero">

                <div class="hero-content">

                    <div class="hero-tag">
                        🌍 ALL-IN-ONE LEARNING PLATFORM
                    </div>

                    <h1>
                        Interactive Learning Universe For Kids Age 1–16
                    </h1>

                    <p>
                        Explore coding, mathematics, science, speaking skills, creativity, AI, quizzes, games,
                        storytelling, robotics, arts, and live classes in one magical learning platform.
                    </p>

                    <div class="hero-buttons">

                        <a href="#myPrograms" class="hero-btn hero-btn-primary">
                            <i class="fa-solid fa-rocket"></i>
                            Start Learning
                        </a>

                        <a href="#liveClasses" class="hero-btn hero-btn-secondary">
                            <i class="fa-solid fa-play"></i>
                            Watch Demo
                        </a>

                    </div>

                </div>

            </section>

            <!-- MY LEARNING PROGRAMS -->

            <div class="section-title" id="myPrograms">
                <h2>🎓 My Learning Programs</h2>
            </div>

            <?php if ($quiz_submit_error): ?>
                <div class="lp-locked-msg" style="margin-bottom:20px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <?= htmlspecialchars($quiz_submit_error) ?>
                </div>
            <?php endif; ?>

            <div class="lp-grid">
                <?php foreach ($lp_programs as $prog):
                    $prog_id     = $prog['id'];
                    $enrollment  = $lp_enrollments[$prog_id] ?? null;
                    $is_enrolled = $enrollment !== null;
                    $videos      = $lp_videos[$prog_id] ?? [];

                    $is_active_paid = false;
                    if ($is_enrolled && $enrollment['status'] === 'active') {
                        $is_active_paid = empty($enrollment['expires_at']) || $enrollment['expires_at'] >= date('Y-m-d');
                    }

                    // Free trial video is only free for TRIAL_DAYS days after enrollment.
                    // After that, even the "free" first video re-locks until payment.
                    $trial_expired = false;
                    if ($is_enrolled && $enrollment['status'] === 'trial') {
                        $trial_expired = strtotime(substr($enrollment['started_at'], 0, 10)) < strtotime('-' . TRIAL_DAYS . ' days', strtotime(date('Y-m-d')));
                    }

                    // Quiz 1 unlocks once QUIZ_UNLOCK_VIDEO_COUNT distinct videos
                    // (free video included) have been watched in this program.
                    // Quiz 2 unlocks once ALL approved videos in the program have
                    // been watched (dynamic — depends on how many are uploaded).
                    $watched_count       = $lp_watched_counts[$prog_id] ?? 0;
                    $total_videos_count  = count($videos);
                    $quiz1_unlocked      = $watched_count >= QUIZ_UNLOCK_VIDEO_COUNT;
                    $quiz2_unlocked      = $total_videos_count > 0 && $watched_count >= $total_videos_count;

                    $subject_list = array_filter(array_map('trim', explode(',', $prog['subjects'])));

                    // Each card gets one accent color from a small curated set
                    // (cycled by program id) — same palette used consistently
                    // for its border, icon, chips, and buttons.
                    $lp_palette = ['#a855f7', '#38bdf8', '#34d399', '#ec4899'];
                    $lp_accent  = $lp_palette[$prog_id % count($lp_palette)];
                ?>
                    <div class="lp-card" id="program-<?= intval($prog_id) ?>" style="--accent:<?= $lp_accent ?>;">
                        <div class="lp-card-top">
                            <div class="lp-card-top-left">
                                <div class="lp-icon"><i class="fa-solid <?= htmlspecialchars($prog['icon']) ?>"></i></div>
                                <div>
                                    <h3><?= htmlspecialchars($prog['title']) ?></h3>
                                    <span class="lp-age">For <?= htmlspecialchars($prog['age_range']) ?> yrs</span>
                                </div>
                            </div>
                            <?php if ($is_enrolled): ?>
                                <?php if ($is_active_paid): ?>
                                    <span class="lp-enroll-status lp-status-active"><i class="fa-solid fa-circle"></i> Active</span>
                                <?php elseif ($trial_expired): ?>
                                    <span class="lp-enroll-status lp-status-trial" style="background:rgba(248,113,113,0.12); color:#f87171; border-color:#f87171;"><i class="fa-solid fa-lock"></i> Trial Expired</span>
                                <?php else: ?>
                                    <span class="lp-enroll-status lp-status-trial"><i class="fa-solid fa-hourglass-half"></i> Trial</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="lp-price-pill">Rs.<?= number_format($prog['monthly_price'], 0) ?> <span>/month</span></div>
                            <?php endif; ?>
                        </div>

                        <div class="lp-subjects">
                            <?php foreach ($subject_list as $subj): ?>
                                <span class="lp-subject-chip"><?= htmlspecialchars($subj) ?></span>
                            <?php endforeach; ?>
                        </div>

                        <?php $lp_extra_count = ($is_enrolled && !empty($videos)) ? max(0, count($videos) - 2) : 0; ?>
                        <?php if ($is_enrolled): ?>
                            <div class="lp-price-row">
                                <div class="lp-price-pill">Rs.<?= number_format($prog['monthly_price'], 0) ?> <span>/month</span></div>
                                <?php if ($lp_extra_count > 0): ?>
                                    <button type="button" class="lp-view-all-btn" onclick="lpToggleMoreVideos(<?= intval($prog_id) ?>)">
                                        <span id="lpMoreVideosLabel<?= intval($prog_id) ?>">View all videos</span>
                                        <i class="fa-solid fa-chevron-right" id="lpMoreVideosIcon<?= intval($prog_id) ?>"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$is_enrolled): ?>
                            <div class="lp-locked-msg">
                                <i class="fa-solid fa-lock"></i>
                                Not enrolled yet — ask your parent to enroll you in this program from their dashboard.
                            </div>
                            <?php if (!isset($_SESSION['id'])): ?>
                                <a href="<?= $base ?>account/login.php" class="lp-enroll-btn" style="text-decoration:none;">
                                    <i class="fa-solid fa-lock-open"></i> Enroll Now
                                </a>
                            <?php elseif ($lp_role === 'parent'): ?>
                                <?php if (empty($lp_parent_children)): ?>
                                    <a href="<?= $base ?>parent/parent_dashboard.php" class="lp-enroll-btn" style="text-decoration:none;">
                                        <i class="fa-solid fa-lock-open"></i> Link a Child First
                                    </a>
                                <?php else: ?>
                                    <button type="button" class="lp-enroll-btn"
                                        onclick="lpOpenEnrollFlow(<?= intval($prog_id) ?>, <?= htmlspecialchars(json_encode($prog['title']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($prog['monthly_price']), ENT_QUOTES) ?>)">
                                        <i class="fa-solid fa-lock-open"></i> Enroll Now
                                    </button>
                                <?php endif; ?>
                            <?php elseif ($lp_role === 'student'): ?>
                                <button type="button" class="lp-enroll-btn" onclick="lpShowEnrollToast()">
                                    <i class="fa-solid fa-lock-open"></i> Enroll Now
                                </button>
                            <?php endif; ?>
                            <?php
                                $prog_quizzes = $lp_quizzes[$prog_id] ?? [];
                                $lp_includes_parts = [];
                                if (!empty($videos)) $lp_includes_parts[] = 'Videos';
                                if (!empty($prog_quizzes)) $lp_includes_parts[] = 'Assigned Quiz';
                            ?>
                            <?php if (!empty($lp_includes_parts)): ?>
                                <div class="lp-footer-bar">
                                    <div class="lp-footer-icon"><i class="fa-solid fa-box-open"></i></div>
                                    <div class="lp-footer-text">
                                        <span class="lp-footer-label">Program Includes</span>
                                        <span class="lp-footer-title"><?= implode(' and ', $lp_includes_parts) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (empty($videos)): ?>
                                <p class="lp-no-videos">No videos published yet — check back soon!</p>
                            <?php else: ?>
                                <?php
                                    $lp_video_row = function ($vid, $i) use ($prog_id, $is_active_paid, $trial_expired) {
                                        $is_free     = ($i === 0) && !$trial_expired;
                                        $is_unlocked = $is_free || $is_active_paid;
                                        ?>
                                        <div class="lp-video-row">
                                            <div class="lp-video-row-left">
                                                <span class="lp-video-icon <?= $is_unlocked ? 'lp-video-icon-unlocked' : 'lp-video-icon-locked' ?>">
                                                    <i class="fa-solid <?= $is_unlocked ? 'fa-play' : 'fa-lock' ?>"></i>
                                                </span>
                                                <span class="lp-video-title"><?= htmlspecialchars($vid['title']) ?></span>
                                                <?php if ($is_free): ?><span class="lp-free-tag">FREE</span><?php endif; ?>
                                            </div>
                                            <?php if ($is_unlocked): ?>
                                                <button type="button" class="lp-play-btn" onclick="lpPlayVideo(<?= intval($prog_id) ?>, <?= intval($vid['id']) ?>)">
                                                    <i class="fa-solid fa-play"></i> Watch
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="lp-lock-btn" disabled>
                                                    <i class="fa-solid fa-lock"></i> Locked
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                    };
                                    $lp_preview_count = 2;
                                ?>
                                <div class="lp-video-list">
                                    <?php foreach ($videos as $i => $vid): if ($i >= $lp_preview_count) continue; $lp_video_row($vid, $i); endforeach; ?>
                                </div>

                                <?php if ($lp_extra_count > 0): ?>
                                    <div class="lp-video-list lp-video-list-more" id="lpMoreVideos<?= intval($prog_id) ?>" style="display:none;">
                                        <?php foreach ($videos as $i => $vid): if ($i < $lp_preview_count) continue; $lp_video_row($vid, $i); endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php $prog_quizzes = $lp_quizzes[$prog_id] ?? []; ?>
                            <?php if (!empty($prog_quizzes)): ?>
                                <?php foreach ($prog_quizzes as $quiz):
                                    $q_slot        = intval($quiz['slot_number'] ?? 1);
                                    $quiz_unlocked = $q_slot === 2 ? $quiz2_unlocked : $quiz1_unlocked;
                                    $best          = $lp_quiz_best_results[$quiz['id']] ?? null;
                                    $ever_passed   = $best && intval($best['ever_passed']) === 1;
                                    $has_open_retry = !empty($lp_open_retry_permissions[$quiz['id']]);
                                    // Can this child actually take/retake it right now?
                                    $can_attempt   = !$best || (!$ever_passed && $has_open_retry);
                                    $just_done     = $quiz_just_submitted && $quiz_just_submitted['quiz_id'] == $quiz['id'];
                                ?>
                                    <?php if (!$quiz_unlocked): ?>
                                        <div class="lp-footer-bar lp-footer-bar-locked">
                                            <div class="lp-footer-icon"><i class="fa-solid fa-lock"></i></div>
                                            <div class="lp-footer-text">
                                                <?php if ($q_slot === 2): ?>
                                                    <span class="lp-footer-label">Quiz 2 Locked — <?= $watched_count ?>/<?= $total_videos_count ?: '?' ?> watched</span>
                                                    <span class="lp-footer-title">Watch all videos in this program to attempt <?= htmlspecialchars($quiz['title']) ?></span>
                                                <?php else: ?>
                                                    <span class="lp-footer-label">Quiz 1 Locked — <?= min($watched_count, QUIZ_UNLOCK_VIDEO_COUNT) ?>/<?= QUIZ_UNLOCK_VIDEO_COUNT ?> watched</span>
                                                    <span class="lp-footer-title">Watch <?= QUIZ_UNLOCK_VIDEO_COUNT ?> videos to attempt <?= htmlspecialchars($quiz['title']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php elseif (empty($quiz['questions'])): ?>
                                        <div class="lp-footer-bar lp-footer-bar-locked">
                                            <div class="lp-footer-icon"><i class="fa-solid fa-circle-question"></i></div>
                                            <div class="lp-footer-text">
                                                <span class="lp-footer-label">Up Next</span>
                                                <span class="lp-footer-title"><?= htmlspecialchars($quiz['title']) ?> — no questions yet</span>
                                            </div>
                                        </div>
                                    <?php elseif ($ever_passed): ?>
                                        <div class="lp-footer-bar">
                                            <div class="lp-footer-icon" style="color:#34d399;"><i class="fa-solid fa-circle-check"></i></div>
                                            <div class="lp-footer-text">
                                                <span class="lp-footer-label">Completed — Best <?= round($best['best_percentage']) ?>%</span>
                                                <span class="lp-footer-title"><?= htmlspecialchars($quiz['title']) ?></span>
                                            </div>
                                            <button type="button" class="lp-view-all-btn" onclick="lpOpenQuizModal(<?= intval($quiz['id']) ?>)">
                                                <span>View Result</span>
                                                <i class="fa-solid fa-chevron-right"></i>
                                            </button>
                                        </div>
                                    <?php elseif ($best && !$has_open_retry): ?>
                                        <div class="lp-footer-bar lp-footer-bar-locked" style="cursor:pointer;" onclick="lpOpenQuizModal(<?= intval($quiz['id']) ?>)">
                                            <div class="lp-footer-icon"><i class="fa-solid fa-lock"></i></div>
                                            <div class="lp-footer-text">
                                                <span class="lp-footer-label">Locked — scored <?= round($best['best_percentage']) ?>%</span>
                                                <span class="lp-footer-title"><?= htmlspecialchars($quiz['title']) ?> — tap for details</span>
                                            </div>
                                            <i class="fa-solid fa-chevron-right" style="color:#64748b; margin-left:auto;"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="lp-footer-bar">
                                            <div class="lp-footer-icon"><i class="fa-solid fa-rocket"></i></div>
                                            <div class="lp-footer-text">
                                                <span class="lp-footer-label"><?= $best ? 'Reattempt Unlocked — last score ' . round($best['best_percentage']) . '%' : 'Up Next' ?></span>
                                                <span class="lp-footer-title"><?= htmlspecialchars($quiz['title']) ?></span>
                                            </div>
                                            <button type="button" class="lp-view-all-btn" onclick="lpOpenQuizModal(<?= intval($quiz['id']) ?>)">
                                                <span><?= $best ? 'Reattempt Quiz' : 'Attempt Quiz' ?></span>
                                                <i class="fa-solid fa-chevron-right"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($quiz_unlocked && !empty($quiz['questions']) && $ever_passed): ?>
                                        <!-- Read-only template for a quiz this child already PASSED — score only, no retake form -->
                                        <template id="lpQuizTemplate<?= intval($quiz['id']) ?>">
                                            <div class="lp-quiz-modal-header">
                                                <h3 class="lp-quiz-modal-title"><?= htmlspecialchars($quiz['title']) ?></h3>
                                                <p class="lp-quiz-modal-subtitle"><i class="fa-solid fa-circle-check"></i> You already passed this quiz — here's your result</p>
                                            </div>
                                            <div class="lp-quiz-result">
                                                <i class="fa-solid fa-trophy"></i>
                                                Your best score: <strong><?= intval($best['best_score']) ?>/<?= intval($best['total']) ?></strong>
                                                (<?= round($best['best_percentage']) ?>%) — Passed ✅
                                            </div>
                                            <p style="color:#94a3b8; font-size:.82rem; margin-top:14px;">
                                                This quiz is locked now that you've passed it, so there's nothing more to attempt here.
                                            </p>
                                        </template>
                                    <?php elseif ($quiz_unlocked && !empty($quiz['questions']) && $best && !$ever_passed && !$can_attempt): ?>
                                        <!-- Read-only template for a quiz the child FAILED and is now locked out of -->
                                        <template id="lpQuizTemplate<?= intval($quiz['id']) ?>">
                                            <div class="lp-quiz-modal-header">
                                                <h3 class="lp-quiz-modal-title"><?= htmlspecialchars($quiz['title']) ?></h3>
                                                <p class="lp-quiz-modal-subtitle"><i class="fa-solid fa-lock"></i> This quiz is locked</p>
                                            </div>
                                            <div class="lp-quiz-result" style="background:rgba(148,163,184,.08); border-color:rgba(148,163,184,.25); color:#94a3b8;">
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                                You scored <strong><?= intval($best['best_score']) ?>/<?= intval($best['total']) ?></strong>
                                                (<?= round($best['best_percentage']) ?>%) — below the passing mark.
                                            </div>
                                            <p style="color:#cbd5e1; font-size:.9rem; margin-top:14px; line-height:1.5;">
                                                This quiz is now locked after your attempt. Please ask your teacher to grant you a reattempt so you can try again.
                                            </p>
                                        </template>
                                    <?php elseif ($quiz_unlocked && !empty($quiz['questions']) && $can_attempt): ?>
                                        <!-- Hidden template — cloned into the quiz popup on "Attempt Quiz" click -->
                                        <template id="lpQuizTemplate<?= intval($quiz['id']) ?>">
                                            <div class="lp-quiz-modal-header">
                                                        <h3 class="lp-quiz-modal-title"><?= htmlspecialchars($quiz['title']) ?></h3>
                                                        <p class="lp-quiz-modal-subtitle"><i class="fa-solid fa-circle-question"></i> <?= count($quiz['questions']) ?> question<?= count($quiz['questions']) === 1 ? '' : 's' ?> — pick the best answer for each</p>
                                                    </div>

                                                    <?php if ($just_done): ?>
                                                        <div class="lp-quiz-result">
                                                            <i class="fa-solid fa-party-horn"></i>
                                                            You scored <strong><?= $quiz_just_submitted['score'] ?>/<?= $quiz_just_submitted['total'] ?></strong>
                                                            (<?= round($quiz_just_submitted['percentage']) ?>%)!
                                                        </div>
                                                    <?php elseif ($best): ?>
                                                        <div class="lp-quiz-result" style="background:rgba(192,132,252,.08); border-color:rgba(192,132,252,.25); color:#c084fc;">
                                                            <i class="fa-solid fa-star"></i>
                                                            Best score so far: <strong><?= intval($best['best_score']) ?>/<?= intval($best['total']) ?></strong> (<?= round($best['best_percentage']) ?>%)
                                                        </div>
                                                    <?php endif; ?>

                                                    <form method="POST">
                                                        <input type="hidden" name="_submit_quiz" value="1">
                                                        <input type="hidden" name="quiz_id" value="<?= intval($quiz['id']) ?>">
                                                        <?php foreach ($quiz['questions'] as $qi => $q): ?>
                                                            <div class="lp-quiz-question">
                                                                <div class="lp-quiz-question-head">
                                                                    <span class="lp-quiz-q-num"><?= $qi + 1 ?></span>
                                                                    <p><?= htmlspecialchars($q['question']) ?></p>
                                                                </div>
                                                                <div class="lp-quiz-option-grid">
                                                                    <?php foreach (['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']] as $letter => $opt): ?>
                                                                        <label class="lp-quiz-option">
                                                                            <input type="radio" name="answers[<?= intval($q['id']) ?>]" value="<?= $letter ?>" required>
                                                                            <span class="lp-quiz-option-letter"><?= $letter ?></span>
                                                                            <span class="lp-quiz-option-text"><?= htmlspecialchars($opt) ?></span>
                                                                        </label>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <button type="submit" class="lp-quiz-submit-btn">
                                                            <i class="fa-solid fa-paper-plane"></i> Submit Answers
                                                        </button>
                                                    </form>
                                                </template>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php
                // ── Data for the modal's side list (thumbnails + lock status) ──
                // Built once here and read by JS when a video is opened, so
                // switching videos inside the modal needs no page reload.
                $lp_js_video_data = [];
                foreach ($lp_programs as $prog) {
                    $pid = $prog['id'];
                    $enr = $lp_enrollments[$pid] ?? null;
                    $active_paid = false;
                    if ($enr && $enr['status'] === 'active') {
                        $active_paid = empty($enr['expires_at']) || $enr['expires_at'] >= date('Y-m-d');
                    }
                    $enr_trial_expired = false;
                    if ($enr && $enr['status'] === 'trial') {
                        $enr_trial_expired = strtotime(substr($enr['started_at'], 0, 10)) < strtotime('-' . TRIAL_DAYS . ' days', strtotime(date('Y-m-d')));
                    }
                    $vids = $lp_videos[$pid] ?? [];
                    $list = [];
                    foreach ($vids as $i => $v) {
                        $is_free = ($i === 0) && !$enr_trial_expired;
                        $thumb = null;
                        if ($v['video_type'] !== 'file') {
                            if (preg_match('/embed\/([a-zA-Z0-9_-]+)/', $v['video_url'], $m)) {
                                $thumb = "https://img.youtube.com/vi/" . $m[1] . "/mqdefault.jpg";
                            }
                        }
                        $list[] = [
                            'id'     => intval($v['id']),
                            'title'  => $v['title'],
                            'url'    => $v['video_url'],
                            'type'   => $v['video_type'],
                            'thumb'  => $thumb,
                            'free'   => $is_free,
                            'locked' => !($is_free || $active_paid),
                        ];
                    }
                    $lp_js_video_data[$pid] = $list;
                }
            ?>
            <script>
                const lpProgramVideos = <?= json_encode($lp_js_video_data) ?>;
            </script>

            <!-- Video Player Modal — big player + side video list -->
            <div class="lp-modal-overlay" id="lpModalOverlay">
                <div class="lp-modal-box">
                    <div class="lp-modal-grid">
                        <div>
                            <div class="lp-modal-frame-wrap">
                                <iframe id="lpModalFrame" src="" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                                <video id="lpModalVideo" controls style="display:none;"></video>
                            </div>
                            <h4 class="lp-modal-video-title" id="lpModalVideoTitle"></h4>
                            <button type="button" class="lp-modal-close" onclick="lpCloseVideo()">
                                <i class="fa-solid fa-xmark"></i> Close
                            </button>
                        </div>
                        <div>
                            <p class="lp-modal-sidebar-title"><i class="fa-solid fa-list"></i> More in this program</p>
                            <div class="lp-modal-video-list" id="lpModalVideoList"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quiz Popup Modal — cloned from the quiz's hidden <template> on "Attempt Quiz" -->
            <div class="lp-modal-overlay" id="lpQuizModalOverlay">
                <div class="lp-quiz-modal-box">
                    <button type="button" class="lp-quiz-modal-close-x" onclick="lpCloseQuizModal()">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <div id="lpQuizModalBody"></div>
                </div>
            </div>

            <!-- FEATURES -->

            <div class="lp-features-wrap">

                <div class="section-title">
                    <h2>Platform Features</h2>
                </div>

                <div class="feature-grid">

                    <div class="feature-card">
                        <i class="fa-solid fa-gamepad"></i>
                        <h3>Gamified Learning</h3>
                        <p>Kids earn XP, rewards, badges and unlock achievements.</p>
                    </div>

                    <div class="feature-card">
                        <i class="fa-solid fa-book-open"></i>
                        <h3>Library</h3>
                        <p>Hundreds of curated storybooks kids can read anytime, safely.</p>
                    </div>

                    <div class="feature-card">
                        <i class="fa-solid fa-brain"></i>
                        <h3>Smart Quizzes</h3>
                        <p>Questions automatically adapt to child skill level.</p>
                    </div>

                    <div class="feature-card">
                        <i class="fa-solid fa-chart-line"></i>
                        <h3>Progress Tracking</h3>
                        <p>Parents monitor performance and learning analytics.</p>
                    </div>

                </div>

            </div>

            <!-- LIVE CLASSES -->

            <div class="section-title" id="liveClasses">
                <h2>Live Interactive Classes</h2>
                <?php if ($live_classes_enabled): ?>
                    <span style="font-size: 14px; color: #ef4444; font-family:'Orbitron',sans-serif; display:flex; align-items:center; gap:6px;">
                        <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#ef4444; animation:pulse 1.5s infinite;"></span>
                        LIVE NOW
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!$live_classes_enabled): ?>
                <!-- COMING SOON — HERO BANNER (same design as index.php) -->
                <div class="lcs-hero">
                    <div class="lcs-hero-left">
                        <span class="lcs-hero-pill"><i class="fa-solid fa-tower-broadcast"></i> COMING SOON</span>
                        <h3>Live Classes Are<br>Launching Soon!</h3>
                        <p>Our verified teachers are getting ready to host live, interactive sessions right here. Be the first to know when the doors open.</p>

                        <div class="lcs-feature-row">
                            <div class="lcs-feature-chip">
                                <span class="lcs-feature-icon lcs-feature-icon-purple"><i class="fa-solid fa-users"></i></span>
                                <div>
                                    <h5>Interactive Learning</h5>
                                    <span>Learn, ask &amp; grow together</span>
                                </div>
                            </div>
                            <div class="lcs-feature-chip">
                                <span class="lcs-feature-icon lcs-feature-icon-blue"><i class="fa-solid fa-shield-halved"></i></span>
                                <div>
                                    <h5>Verified Teachers</h5>
                                    <span>Trusted educators you can count on</span>
                                </div>
                            </div>
                        </div>

                        <?php if ($already_on_waitlist): ?>
                            <button class="lcs-notify-btn lcs-notify-btn-done" disabled>
                                <i class="fa-solid fa-circle-check"></i>
                                <span>You're on the list!</span>
                            </button>
                        <?php elseif (!isset($_SESSION['id'])): ?>
                            <a href="<?= $base ?>account/login.php" class="lcs-notify-btn" style="text-decoration:none;">
                                <i class="fa-solid fa-bell"></i>
                                <span>
                                    Be the first to know!
                                    <small>Log in to join the waitlist and get notified.</small>
                                </span>
                                <i class="fa-solid fa-chevron-right lcs-notify-arrow"></i>
                            </a>
                        <?php else: ?>
                            <form action="" method="POST">
                                <input type="hidden" name="action" value="join_live_waitlist">
                                <button type="submit" class="lcs-notify-btn">
                                    <i class="fa-solid fa-bell"></i>
                                    <span>
                                        Be the first to know!
                                        <small>Join the waitlist and get notified.</small>
                                    </span>
                                    <i class="fa-solid fa-chevron-right lcs-notify-arrow"></i>
                                </button>
                            </form>
                        <?php endif; ?>

                        <div class="lcs-waitlist-count">
                            <i class="fa-solid fa-user-astronaut"></i>
                            <?= number_format($live_waitlist_count) ?> explorer<?= $live_waitlist_count === 1 ? '' : 's' ?> already waiting
                        </div>
                    </div>
                </div>
            <?php elseif (!empty($live_classes_feed)): ?>
                <div class="live-grid">
                    <?php
                    $subject_theme = [
                        'science'  => ['grad' => 'linear-gradient(135deg,#0ea5e9,#22d3ee)', 'icon' => 'fa-flask'],
                        'math'     => ['grad' => 'linear-gradient(135deg,#7c3aed,#a78bfa)', 'icon' => 'fa-calculator'],
                        'english'  => ['grad' => 'linear-gradient(135deg,#f472b6,#ec4899)', 'icon' => 'fa-language'],
                        'art'      => ['grad' => 'linear-gradient(135deg,#f59e0b,#f97316)', 'icon' => 'fa-palette'],
                        'coding'   => ['grad' => 'linear-gradient(135deg,#22c55e,#16a34a)', 'icon' => 'fa-code'],
                    ];
                    foreach ($live_classes_feed as $lc):
                        $is_live = ($lc['status'] === 'Live');
                        $avatar  = !empty($lc['teacher_pic']) ? htmlspecialchars($lc['teacher_pic']) : 'images/gg.png';
                        $subject_key = strtolower($lc['subject_tag']);
                        $theme = null;
                        foreach ($subject_theme as $key => $val) {
                            if (strpos($subject_key, $key) !== false) { $theme = $val; break; }
                        }
                        if (!$theme) { $theme = ['grad' => 'linear-gradient(135deg,#7c3aed,#ec4899)', 'icon' => 'fa-satellite-dish']; }
                    ?>
                        <div class="live-card">
                            <div class="live-card-banner" style="background: <?= $theme['grad'] ?>;">
                                <i class="fa-solid <?= $theme['icon'] ?> live-card-banner-icon"></i>
                                <span class="live-badge"><i class="fa-solid fa-circle"></i> <?= $is_live ? 'Live' : 'Upcoming' ?></span>
                            </div>
                            <div class="live-card-body">
                                <div class="teacher-info">
                                    <img src="<?= $avatar ?>" alt="Teacher" class="teacher-avatar">
                                    <div class="teacher-name">
                                        <h4><?= htmlspecialchars($lc['teacher_name']) ?></h4>
                                        <span>Verified <?= htmlspecialchars($lc['subject_tag']) ?> Teacher</span>
                                    </div>
                                </div>
                                <h3><?= htmlspecialchars($lc['class_title']) ?></h3>
                                <div class="live-meta">
                                    <span>
                                        <i class="fa-regular fa-clock"></i>
                                        <?= $is_live ? 'Live Now' : 'Starts at ' . date('h:i A', strtotime($lc['scheduled_time'])) ?>
                                    </span>
                                </div>
                                <?php if ($is_live): ?>
                                    <a href="<?= htmlspecialchars($lc['meeting_link']) ?>" target="_blank" class="join-live-btn" style="display:block; text-align:center; text-decoration:none;">
                                        <i class="fa-solid fa-right-to-bracket"></i> Join Class Session
                                    </a>
                                <?php else: ?>
                                    <button class="join-live-btn" disabled style="opacity:.6; cursor:not-allowed;">
                                        <i class="fa-regular fa-clock"></i> Starts Soon
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="live-coming-soon">
                    <div class="live-coming-soon-icon"><i class="fa-solid fa-tower-broadcast"></i></div>
                    <h3>No Live Classes Right Now</h3>
                    <p>Check back soon — our teachers regularly host new live sessions.</p>
                </div>
            <?php endif; ?>

        </main>

    </div>

    <!-- ================= ENROLL ALERT (matches site's popup-card style) ================= -->
    <div id="lpEnrollPopupOverlay" class="popup-overlay enroll-popup-overlay" style="display:none;">
        <div class="popup-card enroll-popup-card">
            <div class="popup-header enroll-popup-header">
                <div class="popup-check-icon enroll-popup-icon">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <h2>Ask Your Parent</h2>
                <p>Only a parent account can enroll you in this program from their dashboard.</p>
            </div>
            <div class="popup-footer enroll-popup-footer">
                <button type="button" class="popup-continue-btn enroll-popup-confirm-btn" style="flex:1;" onclick="document.getElementById('lpEnrollPopupOverlay').style.display='none';">
                    Got It
                </button>
            </div>
        </div>
    </div>

    <!-- ================= ENROLL NOW FLOW (parent) ================= -->
    <div id="lpEnrollFlowOverlay" class="popup-overlay enroll-popup-overlay" style="display:none;">
        <div class="popup-card enroll-popup-card enroll-flow-card">
            <div class="popup-header enroll-popup-header">
                <div class="popup-check-icon enroll-popup-icon">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <h2 id="lpFlowProgramTitle">Enroll</h2>

                <?php if (count($lp_parent_children) > 1): ?>
                    <select id="lpFlowChildSelect" class="form-select enroll-flow-child-select">
                        <?php foreach ($lp_parent_children as $c): ?>
                            <option value="<?= intval($c['id']) ?>"><?= htmlspecialchars($c['fullname']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <!-- This body is filled dynamically based on the LIVE status fetched from the server -->
            <div id="lpFlowBody" class="enroll-flow-body">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </div>

            <div class="popup-footer enroll-popup-footer">
                <button type="button" class="popup-continue-btn enroll-popup-cancel-btn" style="flex:1;" onclick="document.getElementById('lpEnrollFlowOverlay').style.display='none';">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- ================= FOOTER ================= -->
    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- ================= JAVASCRIPT ================= -->

    <script>
        function lpShowEnrollToast() {
            document.getElementById('lpEnrollPopupOverlay').style.display = 'flex';
        }

        // ── ENROLL NOW FLOW ──────────────────────────────────────────
        const lpParentChildren = <?= json_encode($lp_parent_children) ?>;
        let lpFlowState = { programId: null, programTitle: '', monthlyPrice: 0 };

        function lpOpenEnrollFlow(programId, programTitle, monthlyPrice) {
            lpFlowState = { programId, programTitle, monthlyPrice: parseFloat(monthlyPrice) };
            document.getElementById('lpFlowProgramTitle').textContent = programTitle;
            document.getElementById('lpEnrollFlowOverlay').style.display = 'flex';

            const select = document.getElementById('lpFlowChildSelect');
            if (select) {
                select.onchange = lpRefreshEnrollStatus;
            }
            lpRefreshEnrollStatus();
        }

        function lpCurrentChildId() {
            const select = document.getElementById('lpFlowChildSelect');
            if (select) return select.value;
            return lpParentChildren.length ? lpParentChildren[0].id : null;
        }

        function lpRefreshEnrollStatus() {
            const body = document.getElementById('lpFlowBody');
            body.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            const childId = lpCurrentChildId();
            if (!childId) {
                body.innerHTML = '<p>No linked child found. Please link a child from your dashboard first.</p>';
                return;
            }

            fetch('<?= $base ?>parent/get_enrollment_status.php?child_id=' + childId + '&program_id=' + lpFlowState.programId)
                .then(r => r.text())
                .then(text => {
                    let res;
                    try {
                        res = JSON.parse(text);
                    } catch (e) {
                        // Server didn't return valid JSON — show the actual raw
                        // response right here instead of a generic message, so
                        // the real cause is visible without opening dev tools.
                        body.innerHTML = '<p style="color:#f87171; text-align:left; white-space:pre-wrap; font-size:11px; max-height:200px; overflow:auto;">Server returned invalid data:<br>' + lpEscapeHtml(text.substring(0, 800)) + '</p>';
                        return;
                    }
                    if (!res.success) {
                        body.innerHTML = '<p style="color:#f87171;">' + res.error + '</p>';
                        return;
                    }
                    lpRenderEnrollFlowBody(res, childId);
                })
                .catch(err => {
                    body.innerHTML = '<p style="color:#f87171;">Request failed: ' + err.message + '</p>';
                });
        }

        function lpEscapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function lpRenderEnrollFlowBody(res, childId) {
            const body = document.getElementById('lpFlowBody');
            const price = 'Rs.' + Number(res.monthly_price).toLocaleString();

            if (res.status === 'not_enrolled') {
                body.innerHTML =
                    '<div style="width:100%; text-align:center;">' +
                    '<p style="opacity:.8; font-size:14px;">Try 1 free video for <?= TRIAL_DAYS ?> days, or unlock everything now for ' + price + '/month.</p>' +
                    '<button type="button" class="popup-continue-btn" style="width:100%; margin-bottom:8px;" onclick="lpClaimTrial(' + childId + ')">' +
                    '<i class="fa-solid fa-gift"></i> Claim Free Trial (<?= TRIAL_DAYS ?> days)</button>' +
                    '<button type="button" class="popup-continue-btn" style="width:100%; background:transparent; border:1px solid #facc15; color:#facc15;" onclick="lpSkipToPay(' + childId + ')">' +
                    '<i class="fa-solid fa-bolt"></i> Skip Trial — Pay Now</button>' +
                    '</div>';
            } else if (res.status === 'trial') {
                body.innerHTML =
                    '<div style="width:100%; text-align:center;">' +
                    '<p style="opacity:.8; font-size:14px;"><i class="fa-solid fa-hourglass-half"></i> Free trial active — ' + res.trial_days_left + ' day(s) left.</p>' +
                    res.payment_form_html +
                    '</div>';
            } else if (res.status === 'trial_expired') {
                body.innerHTML =
                    '<div style="width:100%; text-align:center;">' +
                    '<p style="opacity:.8; font-size:14px; color:#f87171;"><i class="fa-solid fa-lock"></i> Free trial expired.</p>' +
                    res.payment_form_html +
                    '</div>';
            } else if (res.status === 'pending') {
                body.innerHTML = '<p style="opacity:.8; font-size:14px;"><i class="fa-solid fa-hourglass-half"></i> Payment submitted — under review. We\'ll unlock it as soon as it\'s confirmed.</p>';
            } else if (res.status === 'active') {
                body.innerHTML =
                    '<p style="opacity:.8; font-size:14px;"><i class="fa-solid fa-circle-check" style="color:#34d399;"></i> Already active' +
                    (res.expires_at ? ' — renews ' + res.expires_at : '') + '. Nothing to do here!</p>';
            }
        }

        function lpClaimTrial(childId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= $base ?>parent/parent_programs.php';
            form.innerHTML =
                '<input type="hidden" name="action" value="enroll_program">' +
                '<input type="hidden" name="child_id" value="' + childId + '">' +
                '<input type="hidden" name="program_id" value="' + lpFlowState.programId + '">';
            document.body.appendChild(form);
            form.submit();
        }

        function lpSkipToPay(childId) {
            const body = document.getElementById('lpFlowBody');
            body.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            fetch('<?= $base ?>parent/get_enrollment_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=ensure_enrollment&child_id=' + childId + '&program_id=' + lpFlowState.programId
            })
            .then(r => r.text())
            .then(text => {
                let res;
                try {
                    res = JSON.parse(text);
                } catch (e) {
                    body.innerHTML = '<p style="color:#f87171; text-align:left; white-space:pre-wrap; font-size:11px; max-height:200px; overflow:auto;">Server returned invalid data:<br>' + lpEscapeHtml(text.substring(0, 800)) + '</p>';
                    return;
                }
                if (!res.success) {
                    body.innerHTML = '<p style="color:#f87171;">' + res.error + '</p>';
                    return;
                }
                body.innerHTML = '<div style="text-align:center;">' + res.payment_form_html + '</div>';
            })
            .catch(err => {
                body.innerHTML = '<p style="color:#f87171;">Request failed: ' + err.message + '</p>';
            });
        }
    </script>

    <script>


        /* MY LEARNING PROGRAMS — video modal with side list */

        function lpRenderModalPlayer(url, type) {
            const frame = document.getElementById('lpModalFrame');
            const videoEl = document.getElementById('lpModalVideo');
            if (type === 'file') {
                frame.style.display = 'none';
                frame.src = '';
                videoEl.style.display = 'block';
                videoEl.src = url;
                videoEl.play().catch(() => {});
            } else {
                videoEl.style.display = 'none';
                videoEl.pause();
                videoEl.src = '';
                frame.style.display = 'block';
                frame.src = url;
            }
        }

        function lpRenderModalSidebar(programId, activeVideoId) {
            const list = lpProgramVideos[programId] || [];
            const listEl = document.getElementById('lpModalVideoList');
            listEl.innerHTML = '';

            list.forEach(function (v, i) {
                const row = document.createElement('div');
                row.className = 'lp-modal-video-row' + (v.id === activeVideoId ? ' active' : '') + (v.locked ? ' locked' : '');

                const thumbHtml = v.thumb
                    ? '<img class="lp-modal-thumb" src="' + v.thumb + '" alt="">'
                    : '<div class="lp-modal-thumb-fallback"><i class="fa-solid ' + (v.locked ? 'fa-lock' : 'fa-photo-film') + '"></i></div>';

                row.innerHTML =
                    thumbHtml +
                    '<div class="lp-modal-video-info">' +
                        '<div class="t">' + (i + 1) + '. ' + v.title + '</div>' +
                        '<span class="tag">' + (v.locked ? 'LOCKED' : (v.free ? 'FREE' : 'UNLOCKED')) + '</span>' +
                    '</div>';

                if (!v.locked) {
                    row.addEventListener('click', function () {
                        lpPlayVideo(programId, v.id);
                    });
                }
                listEl.appendChild(row);
            });
        }

        function lpPlayVideo(programId, videoId) {
            const list = lpProgramVideos[programId] || [];
            const video = list.find(v => v.id === videoId);
            if (!video || video.locked) return;

            lpRenderModalPlayer(video.url, video.type);
            document.getElementById('lpModalVideoTitle').textContent = video.title;
            lpRenderModalSidebar(programId, videoId);

            document.getElementById('lpModalOverlay').classList.add('open');
            document.body.style.overflow = 'hidden';

            // Log this watch for parent monitoring (fire-and-forget)
            const body = new URLSearchParams();
            body.append('_log_video_watch', '1');
            body.append('video_id', videoId);
            fetch('learning.php', { method: 'POST', body: body }).catch(() => {});
        }

        function lpCloseVideo() {
            const overlay = document.getElementById('lpModalOverlay');
            const frame = document.getElementById('lpModalFrame');
            const videoEl = document.getElementById('lpModalVideo');
            overlay.classList.remove('open');
            frame.src = '';
            videoEl.pause();
            videoEl.src = '';
            document.body.style.overflow = '';
        }

        document.getElementById('lpModalOverlay').addEventListener('click', function (e) {
            if (e.target === this) lpCloseVideo();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { lpCloseVideo(); lpCloseQuizModal(); }
        });

        /* "View All Videos" collapsible list */
        function lpToggleMoreVideos(programId) {
            const list  = document.getElementById('lpMoreVideos' + programId);
            const icon  = document.getElementById('lpMoreVideosIcon' + programId);
            const label = document.getElementById('lpMoreVideosLabel' + programId);
            if (!list) return;

            const isOpen = list.style.display !== 'none';

            list.style.display = isOpen ? 'none' : 'flex';
            if (icon) icon.style.transform = isOpen ? '' : 'rotate(180deg)';
            if (label) label.textContent = isOpen ? 'View All Videos' : 'Show Less';
        }

        /* QUIZ POPUP — clone the quiz's hidden <template> into the modal */
        function lpOpenQuizModal(quizId) {
            const tpl = document.getElementById('lpQuizTemplate' + quizId);
            const body = document.getElementById('lpQuizModalBody');
            if (!tpl || !body) return;

            body.innerHTML = '';
            body.appendChild(tpl.content.cloneNode(true));

            document.getElementById('lpQuizModalOverlay').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function lpCloseQuizModal() {
            const overlay = document.getElementById('lpQuizModalOverlay');
            if (overlay) overlay.classList.remove('open');
            document.body.style.overflow = '';
        }

        document.getElementById('lpQuizModalOverlay').addEventListener('click', function (e) {
            if (e.target === this) lpCloseQuizModal();
        });

        <?php if ($quiz_just_submitted): ?>
        // Auto-open the quiz popup to show the just-submitted result
        document.addEventListener('DOMContentLoaded', function () {
            lpOpenQuizModal(<?= intval($quiz_just_submitted['quiz_id']) ?>);
        });
        <?php endif; ?>

        /* INTERACTIVE BUTTON EFFECT */

        const buttons = document.querySelectorAll('button, .hero-btn');

        buttons.forEach(btn => {

            btn.addEventListener('mouseenter', () => {
                btn.style.transform = 'translateY(-3px) scale(1.02)';
            });

            btn.addEventListener('mouseleave', () => {
                btn.style.transform = '';
            });

        });

        /* ROTATING HERO TAG */

        const heroTexts = [
            "🌍 ALL-IN-ONE LEARNING PLATFORM",
            "Learn Coding Through Games 🚀",
            "Master Maths With Fun Challenges 🧠",
            "Speak English Confidently 🎤",
            "Build AI Robots & Future Skills 🤖"
        ];

        let tagIndex = 0;

        setInterval(() => {
            tagIndex = (tagIndex + 1) % heroTexts.length;
            const tag = document.querySelector('.hero-tag');
            if (tag) tag.innerHTML = heroTexts[tagIndex];
        }, 3000);

    </script>

</body>

</html>