<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

$fq_success = '';
$fq_error   = '';

$fq_categories = [
    'iq'               => 'IQ Quiz',
    'geography'        => 'Geography Quiz',
    'science'          => 'Science Quiz',
    'english'          => 'English Quiz',
    'generalknowledge' => 'General Knowledge Quiz',
    'coding'           => 'Coding Quiz',
];

// ── Add Question ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_add_question'])) {
    $q_question = trim($_POST['question'] ?? '');
    $q_opt1 = trim($_POST['option1'] ?? '');
    $q_opt2 = trim($_POST['option2'] ?? '');
    $q_opt3 = trim($_POST['option3'] ?? '');
    $q_opt4 = trim($_POST['option4'] ?? '');
    $q_correct = intval($_POST['correct_option'] ?? 0);
    $q_hint = trim($_POST['hint'] ?? '');
    $q_category = trim($_POST['category'] ?? '');

    if (!$q_question || !$q_opt1 || !$q_opt2 || !$q_opt3 || !$q_opt4 || $q_correct < 1 || $q_correct > 4 || !array_key_exists($q_category, $fq_categories)) {
        $fq_error = "Please fill in the question, all 4 options, pick the correct one, and choose a category.";
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO questions (question, option1, option2, option3, option4, correct_option, hint, category)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([$q_question, $q_opt1, $q_opt2, $q_opt3, $q_opt4, $q_correct, $q_hint ?: null, $q_category]);
            $fq_success = "✅ Question added to \"{$fq_categories[$q_category]}\"!";
        } catch (PDOException $e) {
            $fq_error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Edit Question ───────────────────────────────────────────
$fq_is_edit = isset($_POST['_edit_question']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $fq_is_edit) {
    $eq_id = intval($_POST['question_id'] ?? 0);
    $eq_question = trim($_POST['question'] ?? '');
    $eq_opt1 = trim($_POST['option1'] ?? '');
    $eq_opt2 = trim($_POST['option2'] ?? '');
    $eq_opt3 = trim($_POST['option3'] ?? '');
    $eq_opt4 = trim($_POST['option4'] ?? '');
    $eq_correct = intval($_POST['correct_option'] ?? 0);
    $eq_hint = trim($_POST['hint'] ?? '');
    $eq_category = trim($_POST['category'] ?? '');

    if ($eq_id <= 0 || !$eq_question || !$eq_opt1 || !$eq_opt2 || !$eq_opt3 || !$eq_opt4 || $eq_correct < 1 || $eq_correct > 4 || !array_key_exists($eq_category, $fq_categories)) {
        $fq_error = "Please fill in the question, all 4 options, pick the correct one, and choose a category.";
    } else {
        try {
            $pdo->prepare(
                "UPDATE questions SET question = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, correct_option = ?, hint = ?, category = ? WHERE id = ?"
            )->execute([$eq_question, $eq_opt1, $eq_opt2, $eq_opt3, $eq_opt4, $eq_correct, $eq_hint ?: null, $eq_category, $eq_id]);
            $fq_success = "✏️ Question updated!";
        } catch (PDOException $e) {
            $fq_error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Delete Question ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_delete_question'])) {
    $del_id = intval($_POST['question_id'] ?? 0);
    if ($del_id > 0) {
        try {
            $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$del_id]);
            $fq_success = "🗑️ Question deleted.";
        } catch (PDOException $e) {
            $fq_error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Fetch: category filter + questions + counts ─────────────
$selected_category = $_GET['category'] ?? 'all';
if ($selected_category !== 'all' && !array_key_exists($selected_category, $fq_categories)) {
    $selected_category = 'all';
}

try {
    $counts_stmt = $pdo->query("SELECT category, COUNT(*) AS total FROM questions GROUP BY category");
    $category_counts = [];
    foreach ($counts_stmt->fetchAll() as $row) {
        $category_counts[$row['category']] = (int) $row['total'];
    }
} catch (PDOException $e) {
    $category_counts = [];
}

try {
    if ($selected_category === 'all') {
        $all_questions = $pdo->query("SELECT * FROM questions ORDER BY category ASC, id DESC")->fetchAll();
    } else {
        $q_stmt = $pdo->prepare("SELECT * FROM questions WHERE category = ? ORDER BY id DESC");
        $q_stmt->execute([$selected_category]);
        $all_questions = $q_stmt->fetchAll();
    }
} catch (PDOException $e) {
    $all_questions = [];
}

$total_questions = array_sum($category_counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Manage Fun Quiz</title>
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

<!-- ══════════════ ADD QUESTION MODAL ══════════════ -->
<div class="apm-overlay" id="addQuestionOverlay">
    <div class="apm-modal" role="dialog" aria-modal="true" aria-labelledby="afq-title">
        <div class="apm-header">
            <h2 class="apm-title" id="afq-title"><i class="fa-solid fa-circle-plus"></i> Add Fun Quiz Question</h2>
            <button class="apm-close-btn" id="afqCloseBtn" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="admin_fun_quiz.php<?= $selected_category !== 'all' ? '?category=' . $selected_category : '' ?>" method="POST" id="addQuestionForm">
            <input type="hidden" name="_add_question" value="1">

            <div class="apm-group">
                <label class="apm-label" for="afq_category">Category <span style="color:#f87171;">*</span></label>
                <select id="afq_category" name="category" class="apm-select" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($fq_categories as $slug => $label): ?>
                        <option value="<?= $slug ?>" <?= $selected_category === $slug ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="apm-group">
                <label class="apm-label" for="afq_question">Question <span style="color:#f87171;">*</span></label>
                <textarea id="afq_question" name="question" class="apm-textarea" placeholder="e.g., What is the capital of France?" required></textarea>
            </div>

            <div class="apm-two-col">
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="afq_opt1">Option 1 <span style="color:#f87171;">*</span></label>
                    <input type="text" id="afq_opt1" name="option1" class="apm-input" required>
                </div>
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="afq_opt2">Option 2 <span style="color:#f87171;">*</span></label>
                    <input type="text" id="afq_opt2" name="option2" class="apm-input" required>
                </div>
            </div>
            <div class="apm-two-col" style="margin-top:18px;">
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="afq_opt3">Option 3 <span style="color:#f87171;">*</span></label>
                    <input type="text" id="afq_opt3" name="option3" class="apm-input" required>
                </div>
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="afq_opt4">Option 4 <span style="color:#f87171;">*</span></label>
                    <input type="text" id="afq_opt4" name="option4" class="apm-input" required>
                </div>
            </div>

            <div class="apm-group" style="margin-top:18px;">
                <label class="apm-label" for="afq_correct">Correct Option <span style="color:#f87171;">*</span></label>
                <select id="afq_correct" name="correct_option" class="apm-select" required>
                    <option value="1">Option 1</option>
                    <option value="2">Option 2</option>
                    <option value="3">Option 3</option>
                    <option value="4">Option 4</option>
                </select>
            </div>

            <div class="apm-group" style="margin-top:18px;">
                <label class="apm-label" for="afq_hint">Hint <span style="color:#64748b;">(optional)</span></label>
                <input type="text" id="afq_hint" name="hint" class="apm-input" placeholder="A helpful clue for kids">
            </div>

            <button type="submit" class="apm-submit-btn" id="afqSubmitBtn">
                <span class="apm-spinner" id="afqSpinner"></span>
                <i class="fa-solid fa-circle-plus" id="afqBtnIcon"></i>
                <span id="afqBtnText">Add Question</span>
            </button>
        </form>
    </div>
</div><!-- /.addQuestionOverlay -->

<!-- ══════════════ EDIT QUESTION MODAL ══════════════ -->
<div class="apm-overlay" id="editQuestionOverlay">
    <div class="apm-modal" role="dialog" aria-modal="true" aria-labelledby="efq-title">
        <div class="apm-header">
            <h2 class="apm-title" id="efq-title"><i class="fa-solid fa-pen"></i> Edit Question</h2>
            <button class="apm-close-btn" id="efqCloseBtn" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="admin_fun_quiz.php<?= $selected_category !== 'all' ? '?category=' . $selected_category : '' ?>" method="POST" id="editQuestionForm">
            <input type="hidden" name="_edit_question" value="1">
            <input type="hidden" name="question_id" id="efq_id">

            <div class="apm-group">
                <label class="apm-label" for="efq_category">Category <span style="color:#f87171;">*</span></label>
                <select id="efq_category" name="category" class="apm-select" required>
                    <?php foreach ($fq_categories as $slug => $label): ?>
                        <option value="<?= $slug ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="apm-group">
                <label class="apm-label" for="efq_question">Question <span style="color:#f87171;">*</span></label>
                <textarea id="efq_question" name="question" class="apm-textarea" required></textarea>
            </div>

            <div class="apm-two-col">
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="efq_opt1">Option 1 <span style="color:#f87171;">*</span></label>
                    <input type="text" id="efq_opt1" name="option1" class="apm-input" required>
                </div>
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="efq_opt2">Option 2 <span style="color:#f87171;">*</span></label>
                    <input type="text" id="efq_opt2" name="option2" class="apm-input" required>
                </div>
            </div>
            <div class="apm-two-col" style="margin-top:18px;">
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="efq_opt3">Option 3 <span style="color:#f87171;">*</span></label>
                    <input type="text" id="efq_opt3" name="option3" class="apm-input" required>
                </div>
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="efq_opt4">Option 4 <span style="color:#f87171;">*</span></label>
                    <input type="text" id="efq_opt4" name="option4" class="apm-input" required>
                </div>
            </div>

            <div class="apm-group" style="margin-top:18px;">
                <label class="apm-label" for="efq_correct">Correct Option <span style="color:#f87171;">*</span></label>
                <select id="efq_correct" name="correct_option" class="apm-select" required>
                    <option value="1">Option 1</option>
                    <option value="2">Option 2</option>
                    <option value="3">Option 3</option>
                    <option value="4">Option 4</option>
                </select>
            </div>

            <div class="apm-group" style="margin-top:18px;">
                <label class="apm-label" for="efq_hint">Hint <span style="color:#64748b;">(optional)</span></label>
                <input type="text" id="efq_hint" name="hint" class="apm-input">
            </div>

            <button type="submit" class="apm-submit-btn" id="efqSubmitBtn">
                <span class="apm-spinner" id="efqSpinner"></span>
                <i class="fa-solid fa-floppy-disk" id="efqBtnIcon"></i>
                <span id="efqBtnText">Save Changes</span>
            </button>
        </form>
    </div>
</div><!-- /.editQuestionOverlay -->

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

<div class="main-content ad-wrap">

    <?php if ($fq_success): ?>
        <div class="ad-flash ad-flash-success" id="adFlash1">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($fq_success) ?>
        </div>
    <?php endif; ?>
    <?php if ($fq_error): ?>
        <div class="ad-flash ad-flash-error" id="adFlash2">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= $fq_error ?>
        </div>
    <?php endif; ?>

    <div class="ad-hero">
        <div class="ad-hero-left">
            <div class="ad-hero-icon"><i class="fa-solid fa-face-laugh-wink"></i></div>
            <div>
                <h1 class="ad-hero-title">Manage Fun Quiz</h1>
                <p class="ad-hero-sub">Free-to-play quiz questions — open to everyone on the site</p>
                <span class="ad-hero-badge"><i class="fa-solid fa-circle-check"></i> <?= $total_questions ?> Total Questions</span>
            </div>
        </div>
        <div class="ad-hero-right">
            <button type="button" class="ad-back-btn" id="openAddQuestionBtn" style="border:none; cursor:pointer;">
                <i class="fa-solid fa-circle-plus"></i> Add Question
            </button>
        </div>
    </div>

    <p class="ad-section-title"><i class="fa-solid fa-filter"></i> Filter by Category</p>

    <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:24px;">
        <a href="admin_fun_quiz.php" class="ad-back-btn" style="text-decoration:none; <?= $selected_category === 'all' ? 'background:rgba(56,189,248,.15); color:#38bdf8; border-color:rgba(56,189,248,.3);' : '' ?>">
            All <span style="opacity:.7;">(<?= $total_questions ?>)</span>
        </a>
        <?php foreach ($fq_categories as $slug => $label): ?>
            <a href="admin_fun_quiz.php?category=<?= $slug ?>" class="ad-back-btn" style="text-decoration:none; <?= $selected_category === $slug ? 'background:rgba(56,189,248,.15); color:#38bdf8; border-color:rgba(56,189,248,.3);' : '' ?>">
                <?= htmlspecialchars($label) ?> <span style="opacity:.7;">(<?= $category_counts[$slug] ?? 0 ?>)</span>
            </a>
        <?php endforeach; ?>
    </div>

    <p class="ad-section-title"><i class="fa-solid fa-list"></i> Questions</p>

    <?php if (empty($all_questions)): ?>
        <div class="ad-empty">
            <i class="fa-solid fa-circle-question"></i>
            <p>No questions found<?= $selected_category !== 'all' ? ' in this category' : '' ?>. Click "Add Question" to create one.</p>
        </div>
    <?php else: ?>
        <?php foreach ($all_questions as $q):
            $options = [1 => $q['option1'], 2 => $q['option2'], 3 => $q['option3'], 4 => $q['option4']];
        ?>
            <div class="ad-card" style="margin-bottom:14px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:14px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:260px;">
                        <span class="ad-permission-pill ad-permission-approved" style="margin-bottom:8px; display:inline-block;">
                            <?= htmlspecialchars($fq_categories[$q['category']] ?? $q['category']) ?>
                        </span>
                        <p style="color:#f8fafc; font-weight:700; font-size:.95rem; margin:6px 0 10px;"><?= htmlspecialchars($q['question']) ?></p>
                        <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:6px; font-size:.82rem; margin-bottom:8px;">
                            <?php foreach ($options as $num => $opt): ?>
                                <span style="color:<?= $num == $q['correct_option'] ? '#34d399' : '#94a3b8' ?>;">
                                    <?php if ($num == $q['correct_option']): ?><i class="fa-solid fa-check"></i><?php endif; ?>
                                    <?= $num ?>. <?= htmlspecialchars($opt) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!empty($q['hint'])): ?>
                            <p style="color:#64748b; font-size:.78rem; margin:0;"><i class="fa-solid fa-lightbulb"></i> Hint: <?= htmlspecialchars($q['hint']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex; gap:8px; flex-shrink:0;">
                        <button type="button" class="ad-back-btn adm-edit-question-btn" style="padding:6px 12px; font-size:.8rem;"
                            data-id="<?= (int) $q['id'] ?>"
                            data-category="<?= htmlspecialchars($q['category'], ENT_QUOTES) ?>"
                            data-question="<?= htmlspecialchars($q['question'], ENT_QUOTES) ?>"
                            data-opt1="<?= htmlspecialchars($q['option1'], ENT_QUOTES) ?>"
                            data-opt2="<?= htmlspecialchars($q['option2'], ENT_QUOTES) ?>"
                            data-opt3="<?= htmlspecialchars($q['option3'], ENT_QUOTES) ?>"
                            data-opt4="<?= htmlspecialchars($q['option4'], ENT_QUOTES) ?>"
                            data-correct="<?= (int) $q['correct_option'] ?>"
                            data-hint="<?= htmlspecialchars($q['hint'] ?? '', ENT_QUOTES) ?>">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form action="admin_fun_quiz.php<?= $selected_category !== 'all' ? '?category=' . $selected_category : '' ?>" method="POST" class="ad-confirm-form" data-confirm-msg="Delete this question permanently?">
                            <input type="hidden" name="_delete_question" value="1">
                            <input type="hidden" name="question_id" value="<?= (int) $q['id'] ?>">
                            <button type="submit" class="ad-back-btn" style="padding:6px 12px; font-size:.8rem; background:rgba(248,113,113,.12); color:#f87171; border-color:rgba(248,113,113,.3);">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div><!-- /.main-content -->

<script>
/* ── Add Question Modal ──────────────────────────────────── */
const addQOverlay = document.getElementById('addQuestionOverlay');
const openAddQBtn = document.getElementById('openAddQuestionBtn');
const afqCloseBtn = document.getElementById('afqCloseBtn');
const addQForm    = document.getElementById('addQuestionForm');

function afqOpen()  { addQOverlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
function afqClose() { addQOverlay.classList.remove('open'); document.body.style.overflow = ''; }

openAddQBtn.addEventListener('click', afqOpen);
afqCloseBtn.addEventListener('click', afqClose);
addQOverlay.addEventListener('click', e => { if (e.target === addQOverlay) afqClose(); });

addQForm.addEventListener('submit', function() {
    document.getElementById('afqSpinner').style.display = 'inline-block';
    document.getElementById('afqBtnIcon').style.display  = 'none';
    document.getElementById('afqBtnText').textContent    = 'Saving…';
    document.getElementById('afqSubmitBtn').disabled     = true;
});
<?php if ($fq_error && !$fq_is_edit): ?> afqOpen(); <?php endif; ?>

/* ── Edit Question Modal ─────────────────────────────────── */
const editQOverlay = document.getElementById('editQuestionOverlay');
const efqCloseBtn  = document.getElementById('efqCloseBtn');
const editQForm    = document.getElementById('editQuestionForm');

function efqOpen()  { editQOverlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
function efqClose() { editQOverlay.classList.remove('open'); document.body.style.overflow = ''; }

efqCloseBtn.addEventListener('click', efqClose);
editQOverlay.addEventListener('click', e => { if (e.target === editQOverlay) efqClose(); });
document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { afqClose(); efqClose(); } });

document.querySelectorAll('.adm-edit-question-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.getElementById('efq_id').value       = btn.dataset.id;
        document.getElementById('efq_category').value = btn.dataset.category;
        document.getElementById('efq_question').value = btn.dataset.question;
        document.getElementById('efq_opt1').value     = btn.dataset.opt1;
        document.getElementById('efq_opt2').value     = btn.dataset.opt2;
        document.getElementById('efq_opt3').value     = btn.dataset.opt3;
        document.getElementById('efq_opt4').value     = btn.dataset.opt4;
        document.getElementById('efq_correct').value  = btn.dataset.correct;
        document.getElementById('efq_hint').value     = btn.dataset.hint;
        efqOpen();
    });
});

editQForm.addEventListener('submit', function() {
    document.getElementById('efqSpinner').style.display = 'inline-block';
    document.getElementById('efqBtnIcon').style.display  = 'none';
    document.getElementById('efqBtnText').textContent    = 'Saving…';
    document.getElementById('efqSubmitBtn').disabled     = true;
});
<?php if ($fq_error && $fq_is_edit): ?> efqOpen(); <?php endif; ?>

/* ── Custom confirmation modal ───────────────────────────── */
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
