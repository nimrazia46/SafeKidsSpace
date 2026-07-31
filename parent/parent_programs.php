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

// Free trial video stays unlocked for this many days after enrollment,
// then it re-locks until the parent pays (kept in sync with learning.php).
if (!defined('TRIAL_DAYS')) {
    define('TRIAL_DAYS', 7);
}

$success_msg = '';
$error_msg   = '';

if (($_GET['payment_submitted'] ?? '') === '1') {
    $success_msg = "💳 Payment submitted for review! Videos unlock once our team confirms it.";
} elseif (($_GET['enrolled'] ?? '') === '1') {
    $success_msg = "🎉 Enrolled! Your child can now watch the first video free for " . TRIAL_DAYS . " days.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ── Enroll a linked child into a program (starts as 'trial') ───────────
    if ($_POST['action'] === 'enroll_program') {
        $enroll_child_id   = intval($_POST['child_id'] ?? 0);
        $enroll_program_id = intval($_POST['program_id'] ?? 0);
        if ($enroll_child_id && $enroll_program_id) {
            $own = $pdo->prepare("SELECT id FROM parent_monitoring WHERE parent_id = ? AND child_id = ?");
            $own->execute([$parent_id, $enroll_child_id]);
            if ($own->fetch()) {
                try {
                    // Explicit check first — a child can only ever claim the free
                    // trial for a given program ONCE, even if it has since expired.
                    $existing = $pdo->prepare("SELECT id FROM enrollments WHERE child_id = ? AND program_id = ?");
                    $existing->execute([$enroll_child_id, $enroll_program_id]);

                    if ($existing->fetch()) {
                        $error_msg = "Free trial already claimed for this program — it can't be claimed again. Please unlock with a payment instead.";
                    } else {
                        $ins = $pdo->prepare("INSERT INTO enrollments (parent_id, child_id, program_id, status) VALUES (?, ?, ?, 'trial')");
                        $ins->execute([$parent_id, $enroll_child_id, $enroll_program_id]);

                        $prog_name_stmt = $pdo->prepare("SELECT title FROM programs WHERE id = ?");
                        $prog_name_stmt->execute([$enroll_program_id]);
                        $prog_name = $prog_name_stmt->fetchColumn();

                        if ($prog_name) {
                            $log_stmt = $pdo->prepare(
                                "INSERT INTO kid_activity_logs (child_id, activity_name, activity_type, points_earned, duration_minutes)
                                 VALUES (?, ?, 'enrollment', 10, 0)"
                            );
                            $log_stmt->execute([$enroll_child_id, "Enrolled in: " . $prog_name]);

                            $child_name_stmt = $pdo->prepare("SELECT fullname FROM users WHERE id = ?");
                            $child_name_stmt->execute([$enroll_child_id]);
                            $child_name = $child_name_stmt->fetchColumn() ?: 'a child';

                            notify_admins(
                                $pdo,
                                "New program enrollment",
                                ($_SESSION['fullname'] ?? 'A parent') . " enrolled $child_name in \"$prog_name\".",
                                "admin/admin_dashboard.php",
                                "fa-solid fa-graduation-cap"
                            );
                        }

                        header("Location: parent_programs.php?child_id=" . intval($enroll_child_id) . "&enrolled=1#program-" . intval($enroll_program_id));
                        exit();
                    }
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            } else {
                $error_msg = "That child isn't linked to your account.";
            }
        } else {
            $error_msg = "Please select a child and a program.";
        }
    }

    // ── Submit a payment for review (manual confirmation by admin) ─────────
    if ($_POST['action'] === 'submit_payment') {
        $pay_enrollment_id = intval($_POST['enrollment_id'] ?? 0);
        $pay_method        = trim($_POST['method'] ?? 'manual');
        $pay_reference     = trim($_POST['reference_note'] ?? '');
        if ($pay_enrollment_id) {
            $own = $pdo->prepare(
                "SELECT e.id, e.child_id, e.program_id, p.title AS program_title, p.monthly_price
                 FROM enrollments e JOIN programs p ON p.id = e.program_id
                 WHERE e.id = ? AND e.parent_id = ?"
            );
            $own->execute([$pay_enrollment_id, $parent_id]);
            $enr = $own->fetch();
            if ($enr) {
                try {
                    $ins = $pdo->prepare(
                        "INSERT INTO payments (enrollment_id, amount, method, reference_note, status, period_start, period_end)
                         VALUES (?, ?, ?, ?, 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH))"
                    );
                    $ins->execute([$pay_enrollment_id, $enr['monthly_price'], $pay_method, $pay_reference]);

                    $child_name_stmt = $pdo->prepare("SELECT fullname FROM users WHERE id = ?");
                    $child_name_stmt->execute([$enr['child_id']]);
                    $child_name = $child_name_stmt->fetchColumn() ?: 'their child';

                    notify_admins(
                        $pdo,
                        "New payment submitted",
                        ($_SESSION['fullname'] ?? 'A parent') . " submitted Rs." . number_format($enr['monthly_price'], 0) . " for {$enr['program_title']} ($child_name).",
                        "admin/admin_payments.php",
                        "fa-solid fa-money-check-dollar"
                    );

                    // Send them straight back to THIS child's tab, scrolled to
                    // THIS program's card — not whichever child tab happened
                    // to be first/selected before the payment was submitted.
                    header("Location: parent_programs.php?child_id=" . intval($enr['child_id']) . "&payment_submitted=1#program-" . intval($enr['program_id']));
                    exit();
                } catch (PDOException $e) {
                    $error_msg = "Database error: " . $e->getMessage();
                }
            } else {
                $error_msg = "Invalid enrollment.";
            }
        } else {
            $error_msg = "Please fill in the payment details.";
        }
    }
}

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

if (empty($linked_children) || empty($child_info)) {
    header("Location: parent_dashboard.php");
    exit();
}

// ── Programs + enrollments + pending payments for selected child ───────
$pd_programs = [];
try {
    $pd_prog_stmt = $pdo->query(
        "SELECT id, title, slug, age_range, subjects, monthly_price, icon
         FROM programs WHERE status = 'active' ORDER BY id ASC"
    );
    $pd_programs = $pd_prog_stmt->fetchAll();
} catch (PDOException $e) {
    $pd_programs = [];
}

$pd_child_enrollments = [];
$pd_pending_payments  = [];
try {
    $pd_enroll_stmt = $pdo->prepare("SELECT * FROM enrollments WHERE child_id = ? AND parent_id = ?");
    $pd_enroll_stmt->execute([$selected_child_id, $parent_id]);
    foreach ($pd_enroll_stmt->fetchAll() as $row) {
        $pd_child_enrollments[$row['program_id']] = $row;
    }
} catch (PDOException $e) {
    $pd_child_enrollments = [];
}

if (!empty($pd_child_enrollments)) {
    try {
        $enr_ids = array_column($pd_child_enrollments, 'id');
        $placeholders = implode(',', array_fill(0, count($enr_ids), '?'));
        $pend_stmt = $pdo->prepare("SELECT enrollment_id FROM payments WHERE enrollment_id IN ($placeholders) AND status = 'pending'");
        $pend_stmt->execute($enr_ids);
        foreach ($pend_stmt->fetchAll() as $row) {
            $pd_pending_payments[$row['enrollment_id']] = true;
        }
    } catch (PDOException $e) {
        $pd_pending_payments = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Learning Programs</title>
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
                <h1 class="pd-hero-title">Learning Programs</h1>
                <p class="pd-hero-sub">Enroll <?= htmlspecialchars($child_info['fullname']) ?> into paid learning programs</p>
                <span class="pd-hero-badge"><i class="fa-solid fa-user-shield"></i> Parent Account</span>
            </div>
        </div>
        <div class="pd-hero-right">
            <a href="parent_dashboard.php" class="pd-btn" style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); text-decoration:none;">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="pd-alert pd-alert-success"><i class="fa-solid fa-circle-check"></i> <?= $success_msg ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="pd-alert pd-alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= $error_msg ?></div>
    <?php endif; ?>

    <div class="pd-children-tabs" style="margin-bottom:28px;">
        <?php foreach ($linked_children as $child): ?>
            <a href="parent_programs.php?child_id=<?= $child['id'] ?>" class="pd-child-tab <?= $child['id'] == $selected_child_id ? 'active' : '' ?>">
                <img src="<?= !empty($child['profile_pic']) ? '../' . htmlspecialchars($child['profile_pic']) : 'https://cdn-icons-png.flaticon.com/512/4333/4333609.png' ?>" alt="">
                <?= htmlspecialchars($child['fullname']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <p class="pd-section-title"><i class="fa-solid fa-graduation-cap" style="color:#7c3aed"></i> Programs for <?= htmlspecialchars($child_info['fullname']) ?></p>

    <div class="pd-programs-grid">
        <?php foreach ($pd_programs as $prog):
            $prog_id     = $prog['id'];
            $enrollment  = $pd_child_enrollments[$prog_id] ?? null;
            $is_enrolled = $enrollment !== null;
            $has_pending = $is_enrolled && !empty($pd_pending_payments[$enrollment['id']]);

            $is_active_paid = false;
            if ($is_enrolled && $enrollment['status'] === 'active') {
                $is_active_paid = empty($enrollment['expires_at']) || $enrollment['expires_at'] >= date('Y-m-d');
            }

            $trial_expired = false;
            if ($is_enrolled && $enrollment['status'] === 'trial') {
                $trial_expired = strtotime(substr($enrollment['started_at'], 0, 10)) < strtotime('-' . TRIAL_DAYS . ' days', strtotime(date('Y-m-d')));
            }

            $subject_list = array_filter(array_map('trim', explode(',', $prog['subjects'])));
        ?>
            <div class="pd-program-card" id="program-<?= intval($prog_id) ?>">
                <div class="pd-program-top">
                    <div class="pd-program-icon"><i class="fa-solid <?= htmlspecialchars($prog['icon']) ?>"></i></div>
                    <div>
                        <h4><?= htmlspecialchars($prog['title']) ?></h4>
                        <span class="pd-program-age">Age <?= htmlspecialchars($prog['age_range']) ?></span>
                    </div>
                </div>
                <p class="pd-program-subjects"><?= htmlspecialchars(implode(', ', $subject_list)) ?></p>
                <div class="pd-program-price">Rs.<?= number_format($prog['monthly_price'], 0) ?> <span>/ month</span></div>

                <?php if (!$is_enrolled): ?>
                    <form method="POST" class="pd-enroll-form" data-child-name="<?= htmlspecialchars($child_info['fullname']) ?>" data-program-name="<?= htmlspecialchars($prog['title']) ?>">
                        <input type="hidden" name="action" value="enroll_program">
                        <input type="hidden" name="child_id" value="<?= intval($selected_child_id) ?>">
                        <input type="hidden" name="program_id" value="<?= intval($prog_id) ?>">
                        <button type="button" class="pd-enroll-btn" onclick="pdShowClaimPopup(this)">
                            <i class="fa-solid fa-plus me-1"></i> Enroll <?= htmlspecialchars($child_info['fullname']) ?>
                        </button>
                    </form>

                <?php elseif ($is_active_paid): ?>
                    <span class="pd-program-status pd-status-active">
                        <i class="fa-solid fa-circle-check"></i> Active
                        <?php if (!empty($enrollment['expires_at'])): ?> — renews <?= date('M d, Y', strtotime($enrollment['expires_at'])) ?><?php endif; ?>
                    </span>

                <?php elseif ($has_pending): ?>
                    <span class="pd-program-status pd-status-pending">
                        <i class="fa-solid fa-hourglass-half"></i> Payment under review
                    </span>

                <?php else: ?>
                    <?php if ($trial_expired): ?>
                        <span class="pd-program-status" style="background:rgba(248,113,113,0.12); color:#f87171;">
                            <i class="fa-solid fa-lock"></i> Free trial expired
                        </span>
                    <?php else: ?>
                        <span class="pd-program-status pd-status-trial">
                            <i class="fa-solid fa-unlock"></i> Trial — 1 free video unlocked (<?= TRIAL_DAYS ?>-day access)
                        </span>
                    <?php endif; ?>
                    <button type="button" class="pd-pay-toggle-btn" onclick="pdTogglePay(<?= intval($enrollment['id']) ?>)">
                        <i class="fa-solid fa-credit-card me-1"></i> Unlock All Videos
                    </button>
                    <div class="popup-overlay payment-popup-overlay" id="pdPayForm<?= intval($enrollment['id']) ?>" style="display:none;">
                        <div class="popup-card payment-popup-card">
                            <div class="popup-header payment-popup-header">
                                <h2><?= htmlspecialchars($prog['title']) ?></h2>
                                <p>Unlock all videos for <?= htmlspecialchars($child_info['fullname']) ?> — Rs.<?= number_format($prog['monthly_price'], 0) ?>/month</p>
                            </div>

                            <?php
                                $payment_form_action = '';
                                $payment_enrollment_id = $enrollment['id'];
                                $payment_button_amount_label = 'Rs.' . number_format($prog['monthly_price'], 0) . ' ';
                                include __DIR__ . '/../includes/payment_form.php';
                            ?>

                            <div class="popup-footer payment-popup-footer">
                                <button type="button" class="popup-continue-btn payment-popup-close-btn" onclick="pdTogglePay(<?= intval($enrollment['id']) ?>)">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

</div><!-- /.main-content -->

<!-- FREE TRIAL CLAIM POPUP -->
<div id="pdClaimPopupOverlay" class="popup-overlay enroll-popup-overlay" style="display:none;">
    <div class="popup-card enroll-popup-card">
        <div class="popup-header enroll-popup-header">
            <div class="popup-check-icon enroll-popup-icon">
                <i class="fa-solid fa-gift"></i>
            </div>
            <h3>Free Trial Video</h3>
            <p id="pdClaimPopupText" class="enroll-popup-text"></p>
            <p class="enroll-popup-subtext">
                Valid for <?= TRIAL_DAYS ?> days — cannot be claimed again after use.
            </p>
        </div>
        <div class="popup-footer enroll-popup-footer">
            <button type="button" class="enroll-popup-cancel-btn" onclick="pdCloseClaimPopup()">Cancel</button>
            <button type="button" id="pdClaimConfirmBtn" class="enroll-popup-confirm-btn" onclick="pdConfirmClaim()">Unlock Now</button>
        </div>
    </div>
</div>

<script>
let pdClaimFormRef = null;

function pdShowClaimPopup(btn) {
    const form = btn.closest('form');
    pdClaimFormRef = form;
    const childName = form.dataset.childName;
    const programName = form.dataset.programName;
    document.getElementById('pdClaimPopupText').textContent =
        'Unlock 1 free video for "' + programName + '" in ' + childName + '\'s account.';
    document.getElementById('pdClaimPopupOverlay').style.display = 'flex';
}

function pdCloseClaimPopup() {
    document.getElementById('pdClaimPopupOverlay').style.display = 'none';
    pdClaimFormRef = null;
}

function pdConfirmClaim() {
    if (pdClaimFormRef) {
        pdClaimFormRef.submit();
    }
}
</script>

<script>
function pdTogglePay(enrollmentId) {
    const el = document.getElementById('pdPayForm' + enrollmentId);
    if (!el) return;
    el.style.display = (el.style.display === 'flex') ? 'none' : 'flex';
}

document.querySelectorAll('.pd-alert').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .4s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 5000);
});
</script>
</body>
</html>