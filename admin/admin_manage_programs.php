<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

$page_message = '';
$page_error   = '';

// ── Add a new program ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_add_program'])) {
    $p_title    = trim($_POST['title'] ?? '');
    $p_age      = trim($_POST['age_range'] ?? '');
    $p_subjects = trim($_POST['subjects'] ?? '');
    $p_price    = floatval($_POST['monthly_price'] ?? 0);
    $p_icon     = trim($_POST['icon'] ?? '') ?: 'fa-graduation-cap';

    if ($p_title === '' || $p_age === '' || $p_subjects === '' || $p_price <= 0) {
        $page_error = "Please fill in all program fields.";
    } else {
        $p_slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $p_title), '-'));
        try {
            // Keep slug unique
            $slug_check = $pdo->prepare("SELECT COUNT(*) FROM programs WHERE slug = ?");
            $slug_check->execute([$p_slug]);
            if ($slug_check->fetchColumn() > 0) {
                $p_slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
            }

            $ins = $pdo->prepare(
                "INSERT INTO programs (title, slug, age_range, subjects, monthly_price, icon, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'active')"
            );
            $ins->execute([$p_title, $p_slug, $p_age, $p_subjects, $p_price, $p_icon]);
            $page_message = "✅ Program \"$p_title\" added and is now live on the Learning page.";
        } catch (PDOException $e) {
            $page_error = "Database Error: " . $e->getMessage();
        }
    }
}

// ── Activate / Deactivate a program ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_toggle_program_status'])) {
    $t_program_id  = intval($_POST['program_id'] ?? 0);
    $t_new_status  = ($_POST['new_status'] ?? '') === 'deactivate' ? 'inactive' : 'active';

    if ($t_program_id > 0) {
        try {
            $pdo->prepare("UPDATE programs SET status = ? WHERE id = ?")
                ->execute([$t_new_status, $t_program_id]);
            $page_message = $t_new_status === 'inactive'
                ? "🚫 Program deactivated — it no longer shows on the Learning page for new enrollments."
                : "✅ Program reactivated — it's visible on the Learning page again.";
        } catch (PDOException $e) {
            $page_error = "Database Error: " . $e->getMessage();
        }
    }
}

// ── Assign / update teachers for a program ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_assign_teachers'])) {
    $a_program_id = intval($_POST['program_id'] ?? 0);
    $a_teacher_ids = array_map('intval', $_POST['teacher_ids'] ?? []);

    if ($a_program_id > 0) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM teacher_program_assignments WHERE program_id = ?")->execute([$a_program_id]);

            $insert_assign = $pdo->prepare(
                "INSERT INTO teacher_program_assignments (teacher_id, program_id, assigned_by) VALUES (?, ?, ?)"
            );
            foreach ($a_teacher_ids as $tid) {
                if ($tid > 0) {
                    $insert_assign->execute([$tid, $a_program_id, $_SESSION['id']]);

                    notify_user(
                        $pdo, $tid,
                        "Program assigned to you",
                        "You've been assigned to manage a learning program. You can now add videos and quizzes to it.",
                        "teacher/teacher_program_videos.php",
                        "fa-solid fa-graduation-cap"
                    );
                }
            }
            $pdo->commit();
            $page_message = "✅ Teacher assignments updated.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $page_error = "Database Error: " . $e->getMessage();
        }
    }
}

// ── Fetch all programs with stats + assigned teacher names ─────
try {
    $programs = $pdo->query(
        "SELECT p.*,
                (SELECT COUNT(*) FROM program_videos pv WHERE pv.program_id = p.id AND pv.status = 'approved') AS video_count,
                (SELECT COUNT(*) FROM program_videos pv WHERE pv.program_id = p.id AND pv.status = 'pending')  AS pending_video_count,
                (SELECT COUNT(*) FROM enrollments e WHERE e.program_id = p.id AND e.status = 'active') AS enrolled_count,
                (SELECT GROUP_CONCAT(u.fullname SEPARATOR ', ')
                   FROM teacher_program_assignments tpa
                   JOIN users u ON u.id = tpa.teacher_id
                  WHERE tpa.program_id = p.id) AS assigned_teacher_names
         FROM programs p
         ORDER BY p.id ASC"
    )->fetchAll();
} catch (PDOException $e) {
    $programs = [];
    $page_error = $page_error ?: "Couldn't load programs. Have you run migration_teacher_program_assignments.sql yet? (" . $e->getMessage() . ")";
}

// ── Fetch all teachers (for the assignment checkboxes) ─────────
try {
    $all_teachers = $pdo->query("SELECT id, fullname, email FROM users WHERE LOWER(role) = 'teacher' ORDER BY fullname ASC")->fetchAll();
} catch (PDOException $e) {
    $all_teachers = [];
}

// ── Fetch currently-assigned teacher IDs per program (for pre-checking boxes) ──
$assigned_map = []; // program_id => [teacher_id, teacher_id, ...]
try {
    $assign_rows = $pdo->query("SELECT program_id, teacher_id FROM teacher_program_assignments")->fetchAll();
    foreach ($assign_rows as $row) {
        $assigned_map[$row['program_id']][] = (int)$row['teacher_id'];
    }
} catch (PDOException $e) {
    $assigned_map = [];
}

// ── Curated icon choices for the "Add New Program" icon picker ──
$icon_options = [
    'fa-graduation-cap' => 'General',
    'fa-palette'        => 'Art & Drawing',
    'fa-book'           => 'Reading & Stories',
    'fa-calculator'     => 'Math',
    'fa-flask'          => 'Science',
    'fa-music'          => 'Music',
    'fa-globe'          => 'Geography',
    'fa-language'       => 'Languages',
    'fa-puzzle-piece'   => 'Puzzles & Logic',
    'fa-code'           => 'Coding',
    'fa-shapes'         => 'Shapes & Colors',
    'fa-microscope'     => 'Discovery',
    'fa-chess'          => 'Strategy Games',
    'fa-rocket'         => 'Space & Adventure',
    'fa-leaf'           => 'Nature',
    'fa-child'          => 'Early Learning',
    'fa-star'           => 'Achievement',
    'fa-heart'          => 'Wellness',
];

$current_page = 'admin_manage_programs.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Manage Programs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/layout.css">
    <link rel="stylesheet" href="../assets/admin.css">
    <style>
        /* ── Icon picker (Add Program) ───────────────────────────── */
        .apm-icon-select { position: relative; }
        .apm-icon-select-btn {
            width: 100%; display: flex; align-items: center; gap: 10px;
            background: rgba(15,23,42,.6); border: 1px solid rgba(148,163,184,.2);
            color: #e2e8f0; border-radius: 10px; padding: 10px 12px; font-size: .88rem;
            cursor: pointer; text-align: left;
        }
        .apm-icon-select-btn:hover { border-color: rgba(56,189,248,.4); }
        .apm-icon-select-btn i.fa-chevron-down { margin-left: auto; font-size: .7rem; opacity: .6; }
        .apm-icon-select-menu {
            display: none; position: fixed; z-index: 9999;
            max-height: 260px; overflow-y: auto; background: #0f172a; border: 1px solid rgba(148,163,184,.2);
            border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,.45); padding: 6px;
        }
        .apm-icon-select-menu.open { display: block; }
        .apm-icon-option {
            display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 8px;
            color: #cbd5e1; font-size: .85rem; cursor: pointer;
        }
        .apm-icon-option:hover, .apm-icon-option.active { background: rgba(56,189,248,.12); color: #e0f2fe; }
        .apm-icon-option i { width: 18px; text-align: center; color: #38bdf8; }

        /* ── Teacher assignment toggle chips ─────────────────────── */
        .apm-chip {
            display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; border-radius: 999px;
            border: 1px solid rgba(148,163,184,.2); background: rgba(148,163,184,.06); color: #94a3b8;
            font-size: .82rem; cursor: pointer; transition: all .15s ease; user-select: none;
        }
        .apm-chip:hover { border-color: rgba(56,189,248,.35); color: #e2e8f0; }
        .apm-chip input[type="checkbox"] { display: none; }
        .apm-chip .apm-chip-check { display: none; font-size: .7rem; }
        .apm-chip.active {
            background: linear-gradient(135deg, rgba(56,189,248,.2), rgba(99,102,241,.2));
            border-color: rgba(56,189,248,.5); color: #e0f2fe;
        }
        .apm-chip.active .apm-chip-check { display: inline-block; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/admin_navbar.php'; ?>

<div class="main-content ad-wrap">

    <div class="ad-hero">
        <div class="ad-hero-left">
            <div class="ad-hero-icon"><i class="fa-solid fa-layer-group"></i></div>
            <div>
                <h1 class="ad-hero-title">Manage Programs</h1>
                <p class="ad-hero-sub">Add new programs, activate/deactivate them, and assign teachers</p>
            </div>
        </div>
        <div class="ad-hero-right">
            <a href="admin_dashboard.php" class="ad-back-btn"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        </div>
    </div>

    <?php if ($page_message): ?>
        <div class="ad-flash ad-flash-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($page_message) ?></div>
    <?php endif; ?>
    <?php if ($page_error): ?>
        <div class="ad-flash ad-flash-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($page_error) ?></div>
    <?php endif; ?>

    <p class="ad-section-title"><i class="fa-solid fa-chart-simple"></i> Programs Overview</p>

    <div class="ad-programs-grid">
        <?php if (!empty($programs)): ?>
            <?php foreach ($programs as $prog): ?>
                <div class="ad-program-card">
                    <div class="ad-program-icon"><i class="fa-solid <?= htmlspecialchars($prog['icon']) ?>"></i></div>
                    <h4><?= htmlspecialchars($prog['title']) ?></h4>
                    <span class="ad-program-age">Age <?= htmlspecialchars($prog['age_range']) ?></span>
                    <p class="ad-program-subjects"><?= htmlspecialchars($prog['subjects']) ?></p>
                    <div class="ad-program-stats">
                        <div>
                            <strong>Rs.<?= number_format($prog['monthly_price'], 0) ?></strong>
                            <span>Per Month</span>
                        </div>
                        <div>
                            <strong><?= intval($prog['video_count']) ?>/10</strong>
                            <span>Videos Live</span>
                        </div>
                        <div>
                            <strong><?= intval($prog['enrolled_count']) ?></strong>
                            <span>Enrolled</span>
                        </div>
                    </div>
                    <?php if ($prog['pending_video_count'] > 0): ?>
                        <span class="ad-permission-pill ad-permission-pending" style="margin-top:14px; display:inline-block;">
                            <i class="fa-solid fa-hourglass-half"></i> <?= intval($prog['pending_video_count']) ?> awaiting review
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="ad-empty">
                <i class="fa-solid fa-graduation-cap"></i>
                <p>No programs found yet — add your first program below.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Add New Program ─────────────────────────────────────── -->
    <p class="ad-section-title"><i class="fa-solid fa-circle-plus"></i> Add New Program</p>
    <div class="ad-card">
        <form action="admin_manage_programs.php" method="POST">
            <input type="hidden" name="_add_program" value="1">
            <div style="display:grid; grid-template-columns: 2fr 1fr 2fr 1fr 1fr; gap:14px; align-items:end;">
                <div>
                    <label class="apm-label">Program Title</label>
                    <input type="text" name="title" class="apm-input" placeholder="e.g. Junior Artists" required>
                </div>
                <div>
                    <label class="apm-label">Age Range</label>
                    <input type="text" name="age_range" class="apm-input" placeholder="e.g. 6-9" required>
                </div>
                <div>
                    <label class="apm-label">Subjects (comma separated)</label>
                    <input type="text" name="subjects" class="apm-input" placeholder="Drawing,Colors,Crafts" required>
                </div>
                <div>
                    <label class="apm-label">Monthly Price (Rs.)</label>
                    <input type="number" name="monthly_price" class="apm-input" min="1" step="1" placeholder="999" required>
                </div>
                <div>
                    <label class="apm-label">Icon</label>
                    <div class="apm-icon-select" id="iconSelectWrap">
                        <button type="button" class="apm-icon-select-btn" id="iconSelectBtn">
                            <i class="fa-solid fa-graduation-cap" id="iconSelectPreview"></i>
                            <span id="iconSelectLabel">General</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="apm-icon-select-menu" id="iconSelectMenu">
                            <?php foreach ($icon_options as $icon_class => $icon_label): ?>
                                <div class="apm-icon-option<?= $icon_class === 'fa-graduation-cap' ? ' active' : '' ?>"
                                     data-icon="<?= htmlspecialchars($icon_class) ?>"
                                     data-label="<?= htmlspecialchars($icon_label) ?>">
                                    <i class="fa-solid <?= htmlspecialchars($icon_class) ?>"></i>
                                    <span><?= htmlspecialchars($icon_label) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="icon" id="iconSelectValue" value="fa-graduation-cap">
                    </div>
                </div>
            </div>
            <button type="submit" class="ad-live-toggle-btn ad-live-toggle-grant" style="margin-top:16px;">
                <i class="fa-solid fa-plus"></i> Add Program
            </button>
        </form>
    </div>

    <!-- ── All Programs ────────────────────────────────────────── -->
    <p class="ad-section-title" style="margin-top:36px;"><i class="fa-solid fa-graduation-cap"></i> All Programs</p>

    <?php if (empty($programs)): ?>
        <div class="ad-empty">
            <i class="fa-solid fa-graduation-cap"></i>
            <p>No programs yet. Add your first one above.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($programs as $prog): ?>
        <?php
            $prog_id = (int)$prog['id'];
            $assigned_ids = $assigned_map[$prog_id] ?? [];
            $is_active = $prog['status'] === 'active';
        ?>
        <div class="ad-card" style="margin-bottom:20px;">
            <div class="ad-card-header" style="justify-content:space-between;">
                <span>
                    <i class="fa-solid <?= htmlspecialchars($prog['icon']) ?>"></i>
                    <?= htmlspecialchars($prog['title']) ?>
                    <span class="ad-permission-pill <?= $is_active ? 'ad-permission-approved' : 'ad-permission-pending' ?>" style="margin-left:10px;">
                        <?= $is_active ? 'Active' : 'Inactive' ?>
                    </span>
                </span>
                <form action="admin_manage_programs.php" method="POST" style="margin:0;">
                    <input type="hidden" name="_toggle_program_status" value="1">
                    <input type="hidden" name="program_id" value="<?= $prog_id ?>">
                    <input type="hidden" name="new_status" value="<?= $is_active ? 'deactivate' : 'activate' ?>">
                    <button type="submit" class="ad-back-btn" style="<?= $is_active ? 'color:#f87171;border-color:rgba(248,113,113,.3);' : 'color:#22c55e;border-color:rgba(34,197,94,.3);' ?>">
                        <i class="fa-solid <?= $is_active ? 'fa-ban' : 'fa-circle-check' ?>"></i>
                        <?= $is_active ? 'Deactivate' : 'Activate' ?>
                    </button>
                </form>
            </div>

            <div style="padding:18px 20px;">
                <div style="display:flex; gap:28px; flex-wrap:wrap; color:#94a3b8; font-size:.85rem; margin-bottom:16px;">
                    <span><strong style="color:#e2e8f0;">Age:</strong> <?= htmlspecialchars($prog['age_range']) ?></span>
                    <span><strong style="color:#e2e8f0;">Price:</strong> Rs.<?= number_format($prog['monthly_price'], 0) ?>/mo</span>
                    <span><strong style="color:#e2e8f0;">Live Videos:</strong> <?= intval($prog['video_count']) ?>/10</span>
                    <span><strong style="color:#e2e8f0;">Enrolled:</strong> <?= intval($prog['enrolled_count']) ?></span>
                </div>

                <p class="apm-label" style="margin-bottom:8px;">Assigned Teachers</p>
                <form action="admin_manage_programs.php" method="POST">
                    <input type="hidden" name="_assign_teachers" value="1">
                    <input type="hidden" name="program_id" value="<?= $prog_id ?>">
                    <?php if (empty($all_teachers)): ?>
                        <p style="color:#64748b; font-size:.82rem;">No teacher accounts yet — add one from "Add Teacher".</p>
                    <?php else: ?>
                        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
                            <?php foreach ($all_teachers as $t): ?>
                                <?php $t_checked = in_array((int)$t['id'], $assigned_ids, true); ?>
                                <label class="apm-chip<?= $t_checked ? ' active' : '' ?>">
                                    <input type="checkbox" name="teacher_ids[]" value="<?= (int)$t['id'] ?>" class="apm-chip-input"
                                        <?= $t_checked ? 'checked' : '' ?>>
                                    <i class="fa-solid fa-check apm-chip-check"></i>
                                    <?= htmlspecialchars($t['fullname']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="ad-back-btn" style="color:#38bdf8; border-color:rgba(56,189,248,.3);">
                            <i class="fa-solid fa-user-check"></i> Apply Changes
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<script>
/* ── Icon picker (Add Program) ────────────────────────────── */
(function(){
    const btn   = document.getElementById('iconSelectBtn');
    const menu  = document.getElementById('iconSelectMenu');
    const value = document.getElementById('iconSelectValue');
    const preview = document.getElementById('iconSelectPreview');
    const label   = document.getElementById('iconSelectLabel');
    if (!btn) return;

    // Move the menu out to <body>. Cards use backdrop-filter, which creates its
    // own stacking context — an absolutely-positioned menu left inside would get
    // visually clipped/hidden behind the next card. Fixed + body-level fixes that.
    document.body.appendChild(menu);

    function positionMenu() {
        const rect = btn.getBoundingClientRect();
        menu.style.top   = (rect.bottom + 6) + 'px';
        menu.style.left  = rect.left + 'px';
        menu.style.width = rect.width + 'px';
    }

    btn.addEventListener('click', function(e){
        e.stopPropagation();
        if (menu.classList.contains('open')) {
            menu.classList.remove('open');
        } else {
            positionMenu();
            menu.classList.add('open');
        }
    });

    menu.querySelectorAll('.apm-icon-option').forEach(function(opt){
        opt.addEventListener('click', function(){
            menu.querySelectorAll('.apm-icon-option').forEach(o => o.classList.remove('active'));
            opt.classList.add('active');
            value.value = opt.dataset.icon;
            label.textContent = opt.dataset.label;
            preview.className = 'fa-solid ' + opt.dataset.icon;
            menu.classList.remove('open');
        });
    });

    document.addEventListener('click', function(e){
        if (!menu.contains(e.target) && e.target !== btn) menu.classList.remove('open');
    });
    window.addEventListener('resize', function(){ if (menu.classList.contains('open')) positionMenu(); });
    window.addEventListener('scroll', function(e){
        if (!menu.classList.contains('open')) return;
        if (menu.contains(e.target)) return; // scrolling inside the menu itself — don't close it
        menu.classList.remove('open');
    }, true);
})();

/* ── Teacher assignment toggle chips ──────────────────────── */
document.querySelectorAll('.apm-chip-input').forEach(function(cb){
    cb.addEventListener('change', function(){
        this.closest('.apm-chip').classList.toggle('active', this.checked);
    });
});

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