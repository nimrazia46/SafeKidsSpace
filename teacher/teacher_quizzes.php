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

    // Create a program quiz (starts as 'draft' until submitted for review)
    if (isset($_POST['action']) && $_POST['action'] === 'create_program_quiz') {
        $qz_program_id = intval($_POST['program_id'] ?? 0);
        $qz_title      = trim($_POST['quiz_title'] ?? '');
        $qz_slot       = intval($_POST['slot_number'] ?? 0);

        $qz_is_assigned = false;
        if ($qz_program_id > 0) {
            $assign_check = $pdo->prepare("SELECT COUNT(*) FROM teacher_program_assignments WHERE teacher_id = ? AND program_id = ?");
            $assign_check->execute([$teacher_id, $qz_program_id]);
            $qz_is_assigned = $assign_check->fetchColumn() > 0;
        }

        if (!$qz_is_assigned) {
            $error_message = "You haven't been assigned to this program by an admin.";
        } elseif ($qz_program_id > 0 && !empty($qz_title) && in_array($qz_slot, [1, 2], true)) {
            try {
                // Guard: only one draft/pending quiz per program+slot at a time,
                // otherwise two half-finished quizzes could race for the same slot.
                $dupe_stmt = $pdo->prepare(
                    "SELECT id FROM quizzes WHERE program_id = ? AND slot_number = ? AND status IN ('draft','pending')"
                );
                $dupe_stmt->execute([$qz_program_id, $qz_slot]);

                if ($dupe_stmt->fetch()) {
                    $error_message = "A Quiz $qz_slot for this program is already in draft/review. Finish or submit that one before creating another — once admin approves it, it will automatically replace the current live Quiz $qz_slot.";
                } else {
                    $prog_name_stmt = $pdo->prepare("SELECT title FROM programs WHERE id = ?");
                    $prog_name_stmt->execute([$qz_program_id]);
                    $prog_name = $prog_name_stmt->fetchColumn();

                    $ins = $pdo->prepare(
                        "INSERT INTO quizzes (title, category, program_id, slot_number, teacher_id, status, total_questions)
                         VALUES (?, ?, ?, ?, ?, 'draft', 0)"
                    );
                    $ins->execute([$qz_title, $prog_name, $qz_program_id, $qz_slot, $teacher_id]);
                    $success_message = "📝 Quiz $qz_slot draft created. Add some questions, then submit it for admin review. It will replace the current live Quiz $qz_slot once approved.";
                }
            } catch (PDOException $e) {
                $error_message = "Database Error: " . $e->getMessage();
            }
        } else {
            $error_message = "Please select a program, a quiz slot, and enter a quiz title.";
        }
    }

    // Add multiple questions to one of this teacher's own quizzes in a single save
    if (isset($_POST['action']) && $_POST['action'] === 'add_quiz_questions_bulk') {
        $bq_quiz_id  = intval($_POST['quiz_id'] ?? 0);
        $bq_question = $_POST['question'] ?? [];
        $bq_a        = $_POST['option_a'] ?? [];
        $bq_b        = $_POST['option_b'] ?? [];
        $bq_c        = $_POST['option_c'] ?? [];
        $bq_d        = $_POST['option_d'] ?? [];
        $bq_correct  = $_POST['correct_answer'] ?? [];

        try {
            $own_stmt = $pdo->prepare("SELECT status, total_questions FROM quizzes WHERE id = ? AND teacher_id = ?");
            $own_stmt->execute([$bq_quiz_id, $teacher_id]);
            $quiz_row = $own_stmt->fetch();

            if (!$quiz_row) {
                $error_message = "Quiz not found or it doesn't belong to you.";
            } elseif ($quiz_row['status'] === 'approved') {
                $error_message = "🔒 This quiz is already approved — ask an admin to change its questions.";
            } else {
                $block_count = max(count($bq_question), count($bq_a), count($bq_b), count($bq_c), count($bq_d), count($bq_correct));
                $valid_rows  = [];
                $has_partial = false;

                for ($i = 0; $i < $block_count; $i++) {
                    $q  = trim($bq_question[$i] ?? '');
                    $a  = trim($bq_a[$i] ?? '');
                    $b  = trim($bq_b[$i] ?? '');
                    $c  = trim($bq_c[$i] ?? '');
                    $d  = trim($bq_d[$i] ?? '');
                    $cr = trim($bq_correct[$i] ?? '');

                    $filled = ($q !== '') + ($a !== '') + ($b !== '') + ($c !== '') + ($d !== '') + ($cr !== '');

                    if ($filled === 0) {
                        continue; // an untouched extra box — just skip it
                    }
                    if ($filled < 6 || !in_array($cr, ['A', 'B', 'C', 'D'], true)) {
                        $has_partial = true;
                        continue;
                    }
                    $valid_rows[] = [$q, $a, $b, $c, $d, $cr];
                }

                if ($has_partial) {
                    $error_message = "One or more question boxes are only partly filled in. Please finish them (question, all 4 options, and the correct answer) or clear them out, then save again.";
                } elseif (empty($valid_rows)) {
                    $error_message = "Please fill in at least one complete question before saving.";
                } else {
                    $existing_count = intval($quiz_row['total_questions']);
                    if ($existing_count + count($valid_rows) > 50) {
                        $allowed = max(50 - $existing_count, 0);
                        $error_message = "This quiz already has $existing_count/50 questions — you can only add $allowed more in this save.";
                    } else {
                        $ins = $pdo->prepare(
                            "INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_answer)
                             VALUES (?, ?, ?, ?, ?, ?, ?)"
                        );
                        foreach ($valid_rows as $row) {
                            $ins->execute([$bq_quiz_id, $row[0], $row[1], $row[2], $row[3], $row[4], $row[5]]);
                        }
                        $pdo->prepare(
                            "UPDATE quizzes SET total_questions = (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = ?) WHERE id = ?"
                        )->execute([$bq_quiz_id, $bq_quiz_id]);

                        $n = count($valid_rows);
                        $success_message = "✅ $n question" . ($n === 1 ? '' : 's') . " added.";
                    }
                }
            }
        } catch (PDOException $e) {
            $error_message = "Database Error: " . $e->getMessage();
        }
    }

    // Delete a question from this teacher's own (not-yet-approved) quiz
    if (isset($_POST['action']) && $_POST['action'] === 'delete_quiz_question') {
        $dq_id = intval($_POST['question_id'] ?? 0);
        $dq_quiz_id = intval($_POST['quiz_id'] ?? 0);
        try {
            $own_stmt = $pdo->prepare("SELECT status FROM quizzes WHERE id = ? AND teacher_id = ?");
            $own_stmt->execute([$dq_quiz_id, $teacher_id]);
            $quiz_row = $own_stmt->fetch();

            if (!$quiz_row) {
                $error_message = "Quiz not found or it doesn't belong to you.";
            } elseif ($quiz_row['status'] === 'approved') {
                $error_message = "🔒 This quiz is already approved — ask an admin to remove questions.";
            } else {
                $pdo->prepare("DELETE FROM quiz_questions WHERE id = ? AND quiz_id = ?")->execute([$dq_id, $dq_quiz_id]);
                $pdo->prepare(
                    "UPDATE quizzes SET total_questions = (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = ?) WHERE id = ?"
                )->execute([$dq_quiz_id, $dq_quiz_id]);
                $success_message = "🗑️ Question deleted.";
            }
        } catch (PDOException $e) {
            $error_message = "Database Error: " . $e->getMessage();
        }
    }

    // Delete an entire quiz this teacher owns (only if not yet approved)
    if (isset($_POST['action']) && $_POST['action'] === 'delete_program_quiz') {
        $dqz_id = intval($_POST['quiz_id'] ?? 0);
        try {
            $own_stmt = $pdo->prepare("SELECT status FROM quizzes WHERE id = ? AND teacher_id = ?");
            $own_stmt->execute([$dqz_id, $teacher_id]);
            $quiz_row = $own_stmt->fetch();

            if (!$quiz_row) {
                $error_message = "Quiz not found or it doesn't belong to you.";
            } elseif ($quiz_row['status'] === 'approved') {
                $error_message = "🔒 This quiz is live and approved — ask an admin to remove it.";
            } else {
                $pdo->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?")->execute([$dqz_id]);
                $pdo->prepare("DELETE FROM quizzes WHERE id = ? AND teacher_id = ?")->execute([$dqz_id, $teacher_id]);
                $success_message = "🗑️ Quiz deleted.";
            }
        } catch (PDOException $e) {
            $error_message = "Database Error: " . $e->getMessage();
        }
    }

    // Submit a draft/rejected quiz for admin review
    if (isset($_POST['action']) && $_POST['action'] === 'submit_quiz_for_review') {
        $sq_id = intval($_POST['quiz_id'] ?? 0);
        try {
            $own_stmt = $pdo->prepare("SELECT title, status, total_questions FROM quizzes WHERE id = ? AND teacher_id = ?");
            $own_stmt->execute([$sq_id, $teacher_id]);
            $quiz_row = $own_stmt->fetch();

            if (!$quiz_row) {
                $error_message = "Quiz not found or it doesn't belong to you.";
            } elseif ($quiz_row['status'] === 'approved') {
                $error_message = "This quiz is already approved.";
            } elseif (intval($quiz_row['total_questions']) < 1) {
                $error_message = "Add at least one question before submitting this quiz for review.";
            } else {
                $pdo->prepare("UPDATE quizzes SET status = 'pending' WHERE id = ? AND teacher_id = ?")->execute([$sq_id, $teacher_id]);
                $success_message = "📨 Quiz submitted for admin review.";
                notify_admins($pdo, "New quiz submitted", "$teacher_name submitted \"{$quiz_row['title']}\" for review.", "admin/admin_quizzes.php", "fa-solid fa-circle-question");
            }
        } catch (PDOException $e) {
            $error_message = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch active programs this teacher has been assigned to (for the create-quiz dropdown)
try {
    $programs_stmt = $pdo->prepare(
        "SELECT p.id, p.title
           FROM programs p
           JOIN teacher_program_assignments tpa ON tpa.program_id = p.id
          WHERE p.status = 'active' AND tpa.teacher_id = ?
          ORDER BY p.id ASC"
    );
    $programs_stmt->execute([$teacher_id]);
    $programs_list = $programs_stmt->fetchAll();
} catch (PDOException $e) {
    $programs_list = [];
}

// Fetch this teacher's own program quizzes, with questions
$my_program_quizzes = [];
try {
    $my_quiz_stmt = $pdo->prepare(
        "SELECT q.id, q.title, q.status, q.slot_number, q.total_questions, q.program_id, p.title AS program_title
         FROM quizzes q JOIN programs p ON p.id = q.program_id
         WHERE q.teacher_id = ?
         ORDER BY q.id DESC"
    );
    $my_quiz_stmt->execute([$teacher_id]);
    foreach ($my_quiz_stmt->fetchAll() as $quiz) {
        $qq_stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC");
        $qq_stmt->execute([$quiz['id']]);
        $quiz['questions'] = $qq_stmt->fetchAll();
        $my_program_quizzes[] = $quiz;
    }
} catch (PDOException $e) {
    $my_program_quizzes = [];
}

// Distinct programs represented in this teacher's quiz list — filter only shows if 2+
$quiz_filter_programs = [];
foreach ($my_program_quizzes as $mq) {
    $quiz_filter_programs[$mq['program_id']] = $mq['program_title'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Program Quizzes</title>
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

<!-- Custom confirmation modal (replaces native confirm()) -->
<div class="adc-overlay" id="adcOverlay">
    <div class="adc-modal">
        <div class="adc-icon" id="adcIcon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3 class="adc-title" id="adcTitle">Are you sure?</h3>
        <p class="adc-message" id="adcMessage"></p>
        <div class="adc-actions">
            <button type="button" class="adc-btn adc-btn-cancel" id="adcCancelBtn">Cancel</button>
            <button type="button" class="adc-btn adc-btn-confirm" id="adcConfirmBtn">Yes, Confirm</button>
        </div>
    </div>
</div>

<div class="main-content td-wrap">

    <div class="td-hero">
        <div class="td-hero-left">
            <img
                src="<?= !empty($_SESSION['profile_pic']) ? '../' . htmlspecialchars($_SESSION['profile_pic']) : '../assets/images/default-avatar.png' ?>"
                class="td-hero-avatar"
                alt="Profile Photo">
            <div>
                <h1 class="td-hero-title">Program Quizzes</h1>
                <p class="td-hero-sub">Create quizzes and submit them for admin review</p>
                <span class="td-hero-badge"><i class="fa-solid fa-graduation-cap"></i> Instructor</span>
            </div>
        </div>
        <div class="td-hero-right">
            <a href="teacher_quiz_results.php" class="td-btn" style="background:rgba(192,132,252,.12); border:1px solid rgba(192,132,252,.3); color:#c084fc; text-decoration:none;">
                <i class="fa-solid fa-chart-simple"></i> Quiz Results
            </a>
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

    <p class="td-section-title"><i class="fa-solid fa-circle-question"></i> Create a Quiz</p>

    <div class="td-card" style="margin-bottom:36px;">
        <?php if (!empty($programs_list)): ?>
            <form action="teacher_quizzes.php" method="POST">
                <input type="hidden" name="action" value="create_program_quiz">
                <div class="td-form-grid" style="grid-template-columns: 2fr 1.3fr 2.5fr auto; margin-bottom:0;">
                    <div class="td-form-group">
                        <label class="td-form-label">Program</label>
                        <select name="program_id" class="td-select" required>
                            <option value="">Select a program…</option>
                            <?php foreach ($programs_list as $prog): ?>
                                <option value="<?= intval($prog['id']) ?>"><?= htmlspecialchars($prog['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="td-form-group">
                        <label class="td-form-label">Quiz Slot</label>
                        <select name="slot_number" class="td-select" required>
                            <option value="1">Quiz 1 (after 4 videos)</option>
                            <option value="2">Quiz 2 (after all videos)</option>
                        </select>
                    </div>
                    <div class="td-form-group">
                        <label class="td-form-label">Quiz Title</label>
                        <input type="text" name="quiz_title" class="td-input" placeholder="e.g., Alphabet Recap Quiz" required>
                    </div>
                    <div class="td-form-group" style="justify-content:flex-end;">
                        <label class="td-form-label">&nbsp;</label>
                        <button type="submit" class="td-btn td-btn-green">
                            <i class="fa-solid fa-plus"></i> Create Draft
                        </button>
                    </div>
                </div>
                <p style="color:#64748b; font-size:.78rem; margin:10px 0 0;">
                    <i class="fa-solid fa-circle-info"></i>
                    Creating a new quiz for a slot that already has a live quiz won't remove the old one — it stays live for students until your new one is admin-approved, then it auto-replaces it.
                </p>
            </form>
        <?php else: ?>
            <div class="td-empty">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <p>You haven't been assigned to any program yet. Ask an admin to assign you one.</p>
            </div>
        <?php endif; ?>
    </div>

    <p class="td-section-title"><i class="fa-solid fa-list-check"></i> My Quizzes</p>

    <?php if (count($quiz_filter_programs) > 1): ?>
        <div style="margin-bottom:14px; max-width:280px;">
            <label class="td-form-label">Filter by Program</label>
            <select id="quizProgramFilter" class="td-select">
                <option value="0">All Programs</option>
                <?php foreach ($quiz_filter_programs as $pid => $ptitle): ?>
                    <option value="<?= (int)$pid ?>"><?= htmlspecialchars($ptitle) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="td-card" style="margin-bottom:36px;">
        <?php if (!empty($my_program_quizzes)): ?>
            <?php foreach ($my_program_quizzes as $quiz):
                $quiz_locked   = $quiz['status'] === 'approved';
                $can_submit    = in_array($quiz['status'], ['draft', 'rejected']) && intval($quiz['total_questions']) >= 1;
                $quiz_at_limit = intval($quiz['total_questions']) >= 50;
            ?>
                <div class="td-class-strip" style="flex-direction:column; align-items:stretch; gap:12px;" data-program-id="<?= (int)$quiz['program_id'] ?>">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                        <div class="td-class-strip-left">
                            <div class="td-class-icon"><i class="fa-solid fa-circle-question"></i></div>
                            <div>
                                <p class="td-class-title">
                                    <span class="td-status-pill" style="background:rgba(192,132,252,.12); color:#c084fc; margin-right:6px;">Quiz <?= intval($quiz['slot_number'] ?? 1) ?></span>
                                    <?= htmlspecialchars($quiz['title']) ?>
                                </p>
                                <span class="td-class-meta">
                                    <?= htmlspecialchars($quiz['program_title']) ?> &nbsp;•&nbsp;
                                    <?= count($quiz['questions']) ?> question<?= count($quiz['questions']) === 1 ? '' : 's' ?>
                                    &nbsp;•&nbsp;
                                    <span class="td-status-pill td-status-<?= htmlspecialchars($quiz['status']) ?>"><?= ucfirst($quiz['status']) ?></span>
                                </span>
                            </div>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <?php if ($can_submit): ?>
                                <form action="teacher_quizzes.php" method="POST" class="ad-confirm-form" data-confirm-msg="Submit this quiz for admin review? You can still edit it if rejected." data-confirm-positive="1">
                                    <input type="hidden" name="action" value="submit_quiz_for_review">
                                    <input type="hidden" name="quiz_id" value="<?= intval($quiz['id']) ?>">
                                    <button type="submit" class="td-btn td-btn-green" style="padding:8px 14px; font-size:.78rem;">
                                        <i class="fa-solid fa-paper-plane"></i> Submit for Review
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if ($quiz_locked): ?>
                                <span title="Live quizzes can only be removed by an admin" style="color:#64748b; font-size:.75rem; align-self:center;">
                                    <i class="fa-solid fa-lock"></i> Locked
                                </span>
                            <?php else: ?>
                                <form action="teacher_quizzes.php" method="POST" class="ad-confirm-form" data-confirm-msg="Delete this quiz and all its questions?">
                                    <input type="hidden" name="action" value="delete_program_quiz">
                                    <input type="hidden" name="quiz_id" value="<?= intval($quiz['id']) ?>">
                                    <button type="submit" class="td-btn-icon-delete" title="Delete quiz">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($quiz['questions'])): ?>
                        <div>
                            <?php foreach ($quiz['questions'] as $i => $q): ?>
                                <div style="background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:10px; padding:10px 14px; margin-bottom:8px;">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
                                        <p style="color:#f8fafc; font-weight:600; font-size:.85rem; margin:0 0 6px;"><?= $i + 1 ?>. <?= htmlspecialchars($q['question']) ?></p>
                                        <?php if (!$quiz_locked): ?>
                                            <form action="teacher_quizzes.php" method="POST" class="ad-confirm-form" data-confirm-msg="Delete this question?">
                                                <input type="hidden" name="action" value="delete_quiz_question">
                                                <input type="hidden" name="question_id" value="<?= intval($q['id']) ?>">
                                                <input type="hidden" name="quiz_id" value="<?= intval($quiz['id']) ?>">
                                                <button type="submit" class="td-btn-icon-delete" style="width:26px; height:26px; font-size:.7rem;" title="Delete question">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:4px; font-size:.78rem;">
                                        <?php foreach (['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']] as $letter => $opt): ?>
                                            <span style="color:<?= $letter === $q['correct_answer'] ? '#34d399' : '#94a3b8' ?>;">
                                                <?php if ($letter === $q['correct_answer']): ?><i class="fa-solid fa-check"></i><?php endif; ?>
                                                <?= $letter ?>. <?= htmlspecialchars($opt) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$quiz_locked && !$quiz_at_limit): ?>
                        <template id="qBlockTemplate-<?= intval($quiz['id']) ?>">
                            <div class="td-question-block" style="background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:10px; padding:12px 14px; margin-bottom:10px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                    <span class="qb-num" style="font-weight:700; color:#94a3b8; font-size:.78rem;"></span>
                                    <button type="button" class="qb-remove-btn td-btn-icon-delete" style="width:24px; height:24px; font-size:.68rem;" title="Remove this question box">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                                <input type="text" name="question[]" class="td-input" placeholder="Question text" style="margin-bottom:8px;">
                                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:8px; margin-bottom:8px;">
                                    <input type="text" name="option_a[]" class="td-input" placeholder="Option A">
                                    <input type="text" name="option_b[]" class="td-input" placeholder="Option B">
                                    <input type="text" name="option_c[]" class="td-input" placeholder="Option C">
                                    <input type="text" name="option_d[]" class="td-input" placeholder="Option D">
                                </div>
                                <select name="correct_answer[]" class="td-select" style="max-width:180px;">
                                    <option value="">Correct answer…</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                        </template>

                        <form action="teacher_quizzes.php" method="POST" class="td-bulk-question-form" data-quiz-id="<?= intval($quiz['id']) ?>" style="border-top:1px solid rgba(255,255,255,.06); padding-top:14px;">
                            <input type="hidden" name="action" value="add_quiz_questions_bulk">
                            <input type="hidden" name="quiz_id" value="<?= intval($quiz['id']) ?>">
                            <div class="td-question-blocks" id="qBlocks-<?= intval($quiz['id']) ?>"></div>
                            <div style="display:flex; gap:10px;">
                                <button type="button" class="td-btn qb-add-more-btn" style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15);">
                                    <i class="fa-solid fa-plus"></i> Add More
                                </button>
                                <button type="submit" class="td-btn td-btn-green">
                                    <i class="fa-solid fa-floppy-disk"></i> Save All Questions
                                </button>
                            </div>
                        </form>
                    <?php elseif ($quiz_at_limit && !$quiz_locked): ?>
                        <div style="border-top:1px solid rgba(255,255,255,.06); padding-top:14px; color:#34d399; font-size:.82rem;">
                            <i class="fa-solid fa-circle-check"></i> 50-question safety limit reached — ready to submit for review!
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="td-empty">
                <i class="fa-solid fa-circle-question"></i>
                <p>You haven't created any quizzes yet.</p>
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

/* ── Instant program filter for "My Quizzes" ──────────────── */
(function(){
    const filterSelect = document.getElementById('quizProgramFilter');
    if (!filterSelect) return;
    const cards = document.querySelectorAll('.td-class-strip[data-program-id]');

    filterSelect.addEventListener('change', function(){
        const selected = filterSelect.value;
        cards.forEach(function(card){
            card.style.display = (selected === '0' || card.dataset.programId === selected) ? '' : 'none';
        });
    });
})();

/* ── Bulk question boxes: start with 5, "Add More" clones, remove works ── */
document.querySelectorAll('.td-bulk-question-form').forEach(function(form){
    const quizId    = form.dataset.quizId;
    const container = document.getElementById('qBlocks-' + quizId);
    const template  = document.getElementById('qBlockTemplate-' + quizId);
    if (!container || !template) return;

    function renumber() {
        const blocks = container.querySelectorAll('.td-question-block');
        blocks.forEach(function(block, idx){
            block.querySelector('.qb-num').textContent = 'Question ' + (idx + 1);
        });
        blocks.forEach(function(block){
            block.querySelector('.qb-remove-btn').style.visibility = blocks.length > 1 ? 'visible' : 'hidden';
        });
    }

    function addBlock() {
        const clone = template.content.cloneNode(true);
        const block = clone.querySelector('.td-question-block');
        block.querySelector('.qb-remove-btn').addEventListener('click', function(){
            block.remove();
            renumber();
        });
        container.appendChild(clone);
        renumber();
    }

    for (let i = 0; i < 5; i++) addBlock();

    const addMoreBtn = form.querySelector('.qb-add-more-btn');
    if (addMoreBtn) addMoreBtn.addEventListener('click', addBlock);

    form.addEventListener('submit', function(){
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
    });
});

/* ── Custom confirmation modal ───────────────────────────── */
(function(){
    const adcOverlay    = document.getElementById('adcOverlay');
    const adcIcon       = document.getElementById('adcIcon');
    const adcMessage    = document.getElementById('adcMessage');
    const adcConfirmBtn = document.getElementById('adcConfirmBtn');
    const adcCancelBtn  = document.getElementById('adcCancelBtn');
    let adcPendingForm  = null;

    document.querySelectorAll('form.ad-confirm-form').forEach(function(form){
        form.addEventListener('submit', function(e){
            e.preventDefault();
            adcPendingForm = form;
            adcMessage.textContent = form.getAttribute('data-confirm-msg') || 'Are you sure you want to continue?';

            const isPositive = form.getAttribute('data-confirm-positive') === '1';
            adcConfirmBtn.classList.toggle('adc-btn-positive', isPositive);
            adcIcon.style.color = isPositive ? '#34d399' : '#f87171';
            adcIcon.style.background = isPositive ? 'rgba(52,211,153,.12)' : 'rgba(248,113,113,.12)';
            adcIcon.style.borderColor = isPositive ? 'rgba(52,211,153,.3)' : 'rgba(248,113,113,.3)';
            adcIcon.querySelector('i').className = isPositive ? 'fa-solid fa-paper-plane' : 'fa-solid fa-triangle-exclamation';

            adcOverlay.classList.add('open');
        });
    });
    adcConfirmBtn.addEventListener('click', function(){
        adcOverlay.classList.remove('open');
        if (adcPendingForm) { adcPendingForm.submit(); }
    });
    adcCancelBtn.addEventListener('click', function(){
        adcOverlay.classList.remove('open');
        adcPendingForm = null;
    });
    adcOverlay.addEventListener('click', function(e){
        if (e.target === adcOverlay) {
            adcOverlay.classList.remove('open');
            adcPendingForm = null;
        }
    });
})();
</script>
</body>
</html>