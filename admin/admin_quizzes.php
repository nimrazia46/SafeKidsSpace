<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

// ── Approve / Reject a teacher-submitted quiz ────
$quiz_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_review_program_quiz'])) {
    $rq_id = intval($_POST['quiz_id'] ?? 0);
    $rq_decision = ($_POST['decision'] ?? '') === 'approve' ? 'approved' : 'rejected';
    if ($rq_id > 0) {
        try {
            $q_count = 0;
            if ($rq_decision === 'approved') {
                $count_stmt = $pdo->prepare("SELECT total_questions FROM quizzes WHERE id = ?");
                $count_stmt->execute([$rq_id]);
                $q_count = intval($count_stmt->fetchColumn());
            }

            if ($rq_decision === 'approved' && $q_count < 10) {
                $quiz_message = "🚫 Can't approve — this quiz only has $q_count question(s). It needs a full 10 before it can go live.";
            } else {
                $qinfo_stmt = $pdo->prepare("SELECT title, teacher_id, program_id, slot_number FROM quizzes WHERE id = ?");
                $qinfo_stmt->execute([$rq_id]);
                $qinfo = $qinfo_stmt->fetch();

                $pdo->prepare(
                    "UPDATE quizzes SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?"
                )->execute([$rq_decision, $_SESSION['id'], $rq_id]);

                // Auto-replace: this program+slot can only ever have ONE live
                // (approved) quiz. The previously-approved one is archived, not
                // deleted, so students' past scores/results stay intact.
                if ($rq_decision === 'approved' && $qinfo) {
                    $pdo->prepare(
                        "UPDATE quizzes SET status = 'archived' WHERE program_id = ? AND slot_number = ? AND id != ? AND status = 'approved'"
                    )->execute([$qinfo['program_id'], $qinfo['slot_number'], $rq_id]);
                }

                $quiz_message = $rq_decision === 'approved'
                    ? "✅ Quiz approved — it's now visible to enrolled students. Any older live quiz in the same slot was archived."
                    : "🚫 Quiz rejected. The teacher can edit and resubmit it.";

                if ($qinfo) {
                    if ($rq_decision === 'approved') {
                        notify_user(
                            $pdo, $qinfo['teacher_id'],
                            "Quiz approved",
                            "Your quiz \"{$qinfo['title']}\" was approved and is now live.",
                            "teacher/teacher_quizzes.php",
                            "fa-solid fa-circle-check"
                        );

                        $enrolled_stmt = $pdo->prepare("SELECT DISTINCT child_id FROM enrollments WHERE program_id = ? AND status = 'active'");
                        $enrolled_stmt->execute([$qinfo['program_id']]);
                        foreach ($enrolled_stmt->fetchAll() as $enr_row) {
                            notify_user(
                                $pdo, $enr_row['child_id'],
                                "New quiz available!",
                                "A new quiz is ready for you: {$qinfo['title']}",
                                "quiz/quiz.php",
                                "fa-solid fa-circle-question"
                            );
                        }
                    } else {
                        notify_user(
                            $pdo, $qinfo['teacher_id'],
                            "Quiz rejected",
                            "Your quiz \"{$qinfo['title']}\" was rejected. You can edit and resubmit it.",
                            "teacher/teacher_quizzes.php",
                            "fa-solid fa-circle-xmark"
                        );
                    }
                }
            }
        } catch (PDOException $e) {
            $quiz_message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Edit a quiz's title ──
$is_edit_quiz_submit = isset($_POST['_edit_quiz_title']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_edit_quiz_submit) {
    $etq_id    = intval($_POST['quiz_id'] ?? 0);
    $etq_title = trim($_POST['title'] ?? '');
    if ($etq_id > 0 && $etq_title !== '') {
        try {
            $pdo->prepare("UPDATE quizzes SET title = ? WHERE id = ?")->execute([$etq_title, $etq_id]);
            $quiz_message = "✏️ Quiz title updated.";
        } catch (PDOException $e) {
            $quiz_message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $quiz_message = "Please enter a quiz title.";
    }
}

// ── Edit a single question ──
$is_edit_question_submit = isset($_POST['_edit_quiz_question']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_edit_question_submit) {
    $eq_id       = intval($_POST['question_id'] ?? 0);
    $eq_question = trim($_POST['question'] ?? '');
    $eq_a = trim($_POST['option_a'] ?? '');
    $eq_b = trim($_POST['option_b'] ?? '');
    $eq_c = trim($_POST['option_c'] ?? '');
    $eq_d = trim($_POST['option_d'] ?? '');
    $eq_correct = trim($_POST['correct_answer'] ?? '');

    if ($eq_id > 0 && $eq_question && $eq_a && $eq_b && $eq_c && $eq_d && in_array($eq_correct, ['A', 'B', 'C', 'D'])) {
        try {
            $pdo->prepare(
                "UPDATE quiz_questions SET question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_answer = ? WHERE id = ?"
            )->execute([$eq_question, $eq_a, $eq_b, $eq_c, $eq_d, $eq_correct, $eq_id]);
            $quiz_message = "✏️ Question updated.";
        } catch (PDOException $e) {
            $quiz_message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $quiz_message = "Please fill in the question, all 4 options, and pick the correct answer.";
    }
}

// ── Delete a question (admin — any quiz, any status) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_delete_quiz_question'])) {
    $dq_id = intval($_POST['question_id'] ?? 0);
    $dq_quiz_id = intval($_POST['quiz_id'] ?? 0);
    if ($dq_id > 0) {
        try {
            $pdo->prepare("DELETE FROM quiz_questions WHERE id = ?")->execute([$dq_id]);
            $pdo->prepare(
                "UPDATE quizzes SET total_questions = (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = ?) WHERE id = ?"
            )->execute([$dq_quiz_id, $dq_quiz_id]);
            $quiz_message = "🗑️ Question deleted.";
        } catch (PDOException $e) {
            $quiz_message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Delete an entire quiz (admin — any quiz, any status) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_delete_program_quiz'])) {
    $dqz_id = intval($_POST['quiz_id'] ?? 0);
    if ($dqz_id > 0) {
        try {
            $pdo->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?")->execute([$dqz_id]);
            $pdo->prepare("DELETE FROM quizzes WHERE id = ?")->execute([$dqz_id]);
            $quiz_message = "🗑️ Quiz deleted.";
        } catch (PDOException $e) {
            $quiz_message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Filters: teacher / program (GET params) ─────────────────────
$qfilter_teacher_id = intval($_GET['teacher_id'] ?? 0);
$qfilter_program_id = intval($_GET['program_id'] ?? 0);

// ── Fetch ALL program-linked quizzes (any status) with their questions ──
$program_quizzes = [];
try {
    $pq_sql = "SELECT q.id, q.title, q.program_id, q.slot_number, q.status, q.total_questions, q.teacher_id, p.title AS program_title,
                u.fullname AS teacher_name
         FROM quizzes q
         JOIN programs p ON p.id = q.program_id
         LEFT JOIN users u ON u.id = q.teacher_id
         WHERE q.program_id IS NOT NULL";
    $pq_params = [];
    if ($qfilter_teacher_id > 0) {
        $pq_sql .= " AND q.teacher_id = ?";
        $pq_params[] = $qfilter_teacher_id;
    }
    if ($qfilter_program_id > 0) {
        $pq_sql .= " AND q.program_id = ?";
        $pq_params[] = $qfilter_program_id;
    }
    $pq_sql .= " ORDER BY (q.status = 'pending') DESC, q.program_id ASC, q.slot_number ASC, q.id DESC";

    $pq_stmt = $pdo->prepare($pq_sql);
    $pq_stmt->execute($pq_params);
    foreach ($pq_stmt->fetchAll() as $row) {
        $qq_stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC");
        $qq_stmt->execute([$row['id']]);
        $row['questions'] = $qq_stmt->fetchAll();
        $program_quizzes[] = $row;
    }
} catch (PDOException $e) {
    $program_quizzes = [];
}

// ── Fetch teacher + program lists (for the filter dropdowns) ────
try {
    $quiz_teachers_list = $pdo->query("SELECT id, fullname FROM users WHERE LOWER(role) = 'teacher' ORDER BY fullname ASC")->fetchAll();
} catch (PDOException $e) {
    $quiz_teachers_list = [];
}
try {
    $quiz_programs_list = $pdo->query("SELECT id, title FROM programs ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    $quiz_programs_list = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Manage Quizzes</title>
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

<!-- ══════════════ EDIT QUIZ TITLE MODAL ══════════════ -->
<div class="apm-overlay" id="editQuizOverlay">
    <div class="apm-modal" role="dialog" aria-modal="true" aria-labelledby="eqz-title" style="max-width:420px;">
        <div class="apm-header">
            <h2 class="apm-title" id="eqz-title"><i class="fa-solid fa-pen"></i> Edit Quiz Title</h2>
            <button class="apm-close-btn" id="eqzCloseBtn" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="admin_quizzes.php" method="POST" id="editQuizForm">
            <input type="hidden" name="_edit_quiz_title" value="1">
            <input type="hidden" name="quiz_id" id="eqz_id">
            <div class="apm-group">
                <label class="apm-label" for="eqz_title_input">Quiz Title <span style="color:#f87171;">*</span></label>
                <input type="text" id="eqz_title_input" name="title" class="apm-input" required maxlength="255">
            </div>
            <button type="submit" class="apm-submit-btn" id="eqzSubmitBtn">
                <span class="apm-spinner" id="eqzSpinner"></span>
                <i class="fa-solid fa-floppy-disk" id="eqzBtnIcon"></i>
                <span id="eqzBtnText">Save Changes</span>
            </button>
        </form>
    </div>
</div><!-- /.editQuizOverlay -->

<!-- ══════════════ EDIT QUESTION MODAL ══════════════ -->
<div class="apm-overlay" id="editQuestionOverlay">
    <div class="apm-modal" role="dialog" aria-modal="true" aria-labelledby="eqq-title">
        <div class="apm-header">
            <h2 class="apm-title" id="eqq-title"><i class="fa-solid fa-pen"></i> Edit Question</h2>
            <button class="apm-close-btn" id="eqqCloseBtn" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="admin_quizzes.php" method="POST" id="editQuestionForm">
            <input type="hidden" name="_edit_quiz_question" value="1">
            <input type="hidden" name="question_id" id="eqq_id">

            <div class="apm-group">
                <label class="apm-label" for="eqq_question">Question <span style="color:#f87171;">*</span></label>
                <input type="text" id="eqq_question" name="question" class="apm-input" required>
            </div>
            <div class="apm-two-col">
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="eqq_a">Option A <span style="color:#f87171;">*</span></label>
                    <input type="text" id="eqq_a" name="option_a" class="apm-input" required>
                </div>
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="eqq_b">Option B <span style="color:#f87171;">*</span></label>
                    <input type="text" id="eqq_b" name="option_b" class="apm-input" required>
                </div>
            </div>
            <div class="apm-two-col" style="margin-top:18px;">
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="eqq_c">Option C <span style="color:#f87171;">*</span></label>
                    <input type="text" id="eqq_c" name="option_c" class="apm-input" required>
                </div>
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="eqq_d">Option D <span style="color:#f87171;">*</span></label>
                    <input type="text" id="eqq_d" name="option_d" class="apm-input" required>
                </div>
            </div>
            <div class="apm-group" style="margin-top:18px;">
                <label class="apm-label" for="eqq_correct">Correct Answer <span style="color:#f87171;">*</span></label>
                <select id="eqq_correct" name="correct_answer" class="apm-select" required>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
            </div>
            <button type="submit" class="apm-submit-btn" id="eqqSubmitBtn">
                <span class="apm-spinner" id="eqqSpinner"></span>
                <i class="fa-solid fa-floppy-disk" id="eqqBtnIcon"></i>
                <span id="eqqBtnText">Save Changes</span>
            </button>
        </form>
    </div>
</div><!-- /.editQuestionOverlay -->

<div class="main-content ad-wrap">

    <div class="ad-hero">
        <div class="ad-hero-left">
            <div class="ad-hero-icon"><i class="fa-solid fa-circle-question"></i></div>
            <div>
                <h1 class="ad-hero-title">Assigned Quiz</h1>
                <p class="ad-hero-sub">Paid-tier content — authored by teachers, reviewed here</p>
                <span class="ad-hero-badge"><i class="fa-solid fa-circle-check"></i> <?= count($program_quizzes) ?> Total Quizzes</span>
            </div>
        </div>
        <div class="ad-hero-right">
            <a href="admin_dashboard.php" class="ad-back-btn"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        </div>
    </div>

    <?php if ($quiz_message): ?>
        <div class="ad-flash ad-flash-success" id="adFlash1">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($quiz_message) ?>
        </div>
    <?php endif; ?>

    <p style="color:#64748b; font-size:.85rem; margin:-8px 0 18px;">Quizzes are authored by teachers (10 questions required) and reviewed here — admin does not create quizzes directly.</p>

    <form method="GET" action="admin_quizzes.php" style="display:flex; gap:12px; flex-wrap:wrap; align-items:end; margin-bottom:18px;">
        <div>
            <label class="apm-label">Filter by Teacher</label>
            <select name="teacher_id" class="apm-select" onchange="this.form.submit()">
                <option value="0">All Teachers</option>
                <?php foreach ($quiz_teachers_list as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= $qfilter_teacher_id === (int)$t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['fullname']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="apm-label">Filter by Program</label>
            <select name="program_id" class="apm-select" onchange="this.form.submit()">
                <option value="0">All Programs</option>
                <?php foreach ($quiz_programs_list as $prog): ?>
                    <option value="<?= (int)$prog['id'] ?>" <?= $qfilter_program_id === (int)$prog['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prog['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($qfilter_teacher_id > 0 || $qfilter_program_id > 0): ?>
            <a href="admin_quizzes.php" class="ad-back-btn"><i class="fa-solid fa-xmark"></i> Clear Filters</a>
        <?php endif; ?>
    </form>

    <?php if (!empty($program_quizzes)): ?>
        <?php foreach ($program_quizzes as $quiz): ?>
            <div class="ad-card" style="margin-top:18px;">
                <div class="ad-card-header" style="color:#facc15; border-color:rgba(250,204,21,.15);">
                    <span><i class="fa-solid fa-clipboard-question"></i> <?= htmlspecialchars($quiz['title']) ?>
                        <span class="ad-permission-pill" style="margin-left:6px; background:rgba(192,132,252,.12); color:#c084fc;">Quiz <?= intval($quiz['slot_number'] ?? 1) ?></span>
                        <span class="ad-permission-pill ad-permission-<?= htmlspecialchars($quiz['status']) ?>" style="margin-left:6px;"><?= ucfirst($quiz['status']) ?></span>
                        <span style="color:#64748b; font-weight:400; font-size:.8rem;">
                            — <?= htmlspecialchars($quiz['program_title']) ?> · <?= count($quiz['questions']) ?> question<?= count($quiz['questions']) === 1 ? '' : 's' ?>
                            <?= $quiz['teacher_name'] ? ' · by ' . htmlspecialchars($quiz['teacher_name']) : ' · by Admin' ?>
                        </span>
                    </span>
                    <div style="display:flex; gap:8px;">
                        <?php if ($quiz['status'] === 'pending'): ?>
                            <form action="admin_quizzes.php" method="POST" style="display:inline;">
                                <input type="hidden" name="_review_program_quiz" value="1">
                                <input type="hidden" name="quiz_id" value="<?= intval($quiz['id']) ?>">
                                <input type="hidden" name="decision" value="approve">
                                <button type="submit" class="ad-live-toggle-btn ad-live-toggle-grant">
                                    <i class="fa-solid fa-check"></i> Approve
                                </button>
                            </form>
                            <form action="admin_quizzes.php" method="POST" style="display:inline;">
                                <input type="hidden" name="_review_program_quiz" value="1">
                                <input type="hidden" name="quiz_id" value="<?= intval($quiz['id']) ?>">
                                <input type="hidden" name="decision" value="reject">
                                <button type="submit" class="ad-live-toggle-btn ad-live-toggle-revoke">
                                    <i class="fa-solid fa-xmark"></i> Reject
                                </button>
                            </form>
                        <?php endif; ?>
                        <button type="button" class="ad-live-toggle-btn adm-edit-quiz-btn" style="background:rgba(56,189,248,.12); color:#38bdf8; border-color:rgba(56,189,248,.3);"
                            data-id="<?= intval($quiz['id']) ?>" data-title="<?= htmlspecialchars($quiz['title'], ENT_QUOTES) ?>">
                            <i class="fa-solid fa-pen"></i> Edit Title
                        </button>
                        <form action="admin_quizzes.php" method="POST" class="ad-confirm-form" data-confirm-msg="Delete this entire quiz and all its questions?">
                            <input type="hidden" name="_delete_program_quiz" value="1">
                            <input type="hidden" name="quiz_id" value="<?= intval($quiz['id']) ?>">
                            <button type="submit" class="ad-live-toggle-btn ad-live-toggle-revoke">
                                <i class="fa-solid fa-trash"></i> Delete Quiz
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (!empty($quiz['questions'])): ?>
                    <div style="margin-bottom:18px;">
                        <?php foreach ($quiz['questions'] as $i => $q): ?>
                            <div style="background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:12px; padding:14px 16px; margin-bottom:10px;">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
                                    <p style="color:#f8fafc; font-weight:600; font-size:.88rem; margin:0 0 8px;"><?= $i + 1 ?>. <?= htmlspecialchars($q['question']) ?></p>
                                    <div style="display:flex; gap:6px; flex-shrink:0;">
                                        <button type="button" class="td-btn-icon-delete adm-edit-question-btn" style="background:rgba(56,189,248,.12); color:#38bdf8;" title="Edit question"
                                            data-id="<?= intval($q['id']) ?>"
                                            data-question="<?= htmlspecialchars($q['question'], ENT_QUOTES) ?>"
                                            data-a="<?= htmlspecialchars($q['option_a'], ENT_QUOTES) ?>"
                                            data-b="<?= htmlspecialchars($q['option_b'], ENT_QUOTES) ?>"
                                            data-c="<?= htmlspecialchars($q['option_c'], ENT_QUOTES) ?>"
                                            data-d="<?= htmlspecialchars($q['option_d'], ENT_QUOTES) ?>"
                                            data-correct="<?= htmlspecialchars($q['correct_answer'], ENT_QUOTES) ?>">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form action="admin_quizzes.php" method="POST" class="ad-confirm-form" data-confirm-msg="Delete this question?">
                                            <input type="hidden" name="_delete_quiz_question" value="1">
                                            <input type="hidden" name="question_id" value="<?= intval($q['id']) ?>">
                                            <input type="hidden" name="quiz_id" value="<?= intval($quiz['id']) ?>">
                                            <button type="submit" class="td-btn-icon-delete" title="Delete question">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:6px; font-size:.8rem;">
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
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="ad-empty" style="margin-top:18px;">
            <i class="fa-solid fa-circle-question"></i>
            <p>No quizzes submitted by teachers yet.</p>
        </div>
    <?php endif; ?>

</div><!-- /.main-content -->

<script>
/* ── Edit Quiz Title Modal ────────────────────────────────── */
const editQuizOverlay = document.getElementById('editQuizOverlay');
const eqzCloseBtn     = document.getElementById('eqzCloseBtn');
const editQuizForm    = document.getElementById('editQuizForm');

function eqzOpen()  { editQuizOverlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
function eqzClose() { editQuizOverlay.classList.remove('open'); document.body.style.overflow = ''; }

eqzCloseBtn.addEventListener('click', eqzClose);
editQuizOverlay.addEventListener('click', e => { if (e.target === editQuizOverlay) eqzClose(); });

document.querySelectorAll('.adm-edit-quiz-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.getElementById('eqz_id').value          = btn.dataset.id;
        document.getElementById('eqz_title_input').value = btn.dataset.title;
        eqzOpen();
    });
});

editQuizForm.addEventListener('submit', function(){
    document.getElementById('eqzSpinner').style.display = 'inline-block';
    document.getElementById('eqzBtnIcon').style.display  = 'none';
    document.getElementById('eqzBtnText').textContent    = 'Saving…';
    document.getElementById('eqzSubmitBtn').disabled     = true;
});
<?php if ($quiz_message && $is_edit_quiz_submit): ?> eqzOpen(); <?php endif; ?>

/* ── Edit Question Modal ──────────────────────────────────── */
const editQuestionOverlay = document.getElementById('editQuestionOverlay');
const eqqCloseBtn         = document.getElementById('eqqCloseBtn');
const editQuestionForm    = document.getElementById('editQuestionForm');

function eqqOpen()  { editQuestionOverlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
function eqqClose() { editQuestionOverlay.classList.remove('open'); document.body.style.overflow = ''; }

eqqCloseBtn.addEventListener('click', eqqClose);
editQuestionOverlay.addEventListener('click', e => { if (e.target === editQuestionOverlay) eqqClose(); });
document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { eqzClose(); eqqClose(); } });

document.querySelectorAll('.adm-edit-question-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.getElementById('eqq_id').value       = btn.dataset.id;
        document.getElementById('eqq_question').value = btn.dataset.question;
        document.getElementById('eqq_a').value        = btn.dataset.a;
        document.getElementById('eqq_b').value        = btn.dataset.b;
        document.getElementById('eqq_c').value        = btn.dataset.c;
        document.getElementById('eqq_d').value        = btn.dataset.d;
        document.getElementById('eqq_correct').value  = btn.dataset.correct;
        eqqOpen();
    });
});

editQuestionForm.addEventListener('submit', function(){
    document.getElementById('eqqSpinner').style.display = 'inline-block';
    document.getElementById('eqqBtnIcon').style.display  = 'none';
    document.getElementById('eqqBtnText').textContent    = 'Saving…';
    document.getElementById('eqqSubmitBtn').disabled     = true;
});
<?php if ($quiz_message && $is_edit_question_submit): ?> eqqOpen(); <?php endif; ?>

(function(){
    const adcOverlay    = document.getElementById('adcOverlay');
    const adcMessage    = document.getElementById('adcMessage');
    const adcConfirmBtn = document.getElementById('adcConfirmBtn');
    const adcCancelBtn  = document.getElementById('adcCancelBtn');
    let adcPendingForm  = null;

    document.querySelectorAll('form.ad-confirm-form').forEach(function(form){
        form.addEventListener('submit', function(e){
            e.preventDefault();
            adcPendingForm = form;
            adcMessage.textContent = form.getAttribute('data-confirm-msg') || 'Are you sure you want to continue?';
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

document.querySelectorAll('.ad-flash').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .4s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 6000);
});
</script>
</body>
</html>