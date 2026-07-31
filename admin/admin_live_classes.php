<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

// ── Grant / Revoke live-class permission for a teacher ─────────
$live_permission_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_toggle_live_permission'])) {
    $target_teacher_id = intval($_POST['teacher_id'] ?? 0);
    $new_permission     = intval($_POST['new_permission'] ?? 0) === 1 ? 1 : 0;
    if ($target_teacher_id > 0) {
        try {
            $pdo->prepare("UPDATE users SET can_go_live = ? WHERE id = ? AND LOWER(role) = 'teacher'")
                ->execute([$new_permission, $target_teacher_id]);
            $live_permission_message = $new_permission
                ? "✅ Teacher approved to host live classes."
                : "🚫 Live-class permission revoked for this teacher.";
        } catch (PDOException $e) {
            $live_permission_message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Fetch all teachers with their live-class permission status ─
try {
    $teachers_list = $pdo->query("SELECT id, fullname, email, can_go_live FROM users WHERE LOWER(role) = 'teacher' ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    $teachers_list = [];
}

// ── Fetch recent live classes across all teachers (oversight) ──
try {
    $live_classes_overview = $pdo->query(
        "SELECT lc.id, lc.class_title, lc.subject_tag, lc.scheduled_time, lc.status, u.fullname AS teacher_name
         FROM live_classes lc
         JOIN users u ON u.id = lc.teacher_id
         ORDER BY lc.scheduled_time DESC
         LIMIT 20"
    )->fetchAll();
} catch (PDOException $e) {
    $live_classes_overview = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Manage Live Classes</title>
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

<div class="main-content ad-wrap">

    <div class="ad-hero">
        <div class="ad-hero-left">
            <div class="ad-hero-icon"><i class="fa-solid fa-satellite-dish"></i></div>
            <div>
                <h1 class="ad-hero-title">Live Class Control Center</h1>
                <p class="ad-hero-sub">Manage teacher permissions and oversee scheduled live classes</p>
            </div>
        </div>
        <div class="ad-hero-right">
            <a href="admin_dashboard.php" class="ad-back-btn"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        </div>
    </div>

    <?php if ($live_permission_message): ?>
        <div class="ad-flash ad-flash-success" id="adFlash1">
            <i class="fa-solid fa-circle-check"></i> <?= $live_permission_message ?>
        </div>
    <?php endif; ?>

    <div class="ad-two-col">

        <!-- Teacher Permissions Table -->
        <div class="ad-card">
            <div class="ad-card-header" style="color:#c084fc; border-color:rgba(192,132,252,.15);">
                <span><i class="fa-solid fa-user-check"></i> Teacher Live-Class Permissions</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="ad-table">
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($teachers_list)): ?>
                            <?php foreach ($teachers_list as $teacher): ?>
                                <tr>
                                    <td style="font-weight:700; color:#f8fafc;"><?= htmlspecialchars($teacher['fullname']) ?></td>
                                    <td style="color:#64748b; font-size:.88rem;"><?= htmlspecialchars($teacher['email']) ?></td>
                                    <td>
                                        <?php if (!empty($teacher['can_go_live'])): ?>
                                            <span class="ad-permission-pill ad-permission-approved">Approved</span>
                                        <?php else: ?>
                                            <span class="ad-permission-pill ad-permission-pending">Not Approved</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <form action="admin_live_classes.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="_toggle_live_permission" value="1">
                                            <input type="hidden" name="teacher_id" value="<?= intval($teacher['id']) ?>">
                                            <?php if (!empty($teacher['can_go_live'])): ?>
                                                <input type="hidden" name="new_permission" value="0">
                                                <button type="submit" class="ad-live-toggle-btn ad-live-toggle-revoke">
                                                    <i class="fa-solid fa-ban"></i> Revoke
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="new_permission" value="1">
                                                <button type="submit" class="ad-live-toggle-btn ad-live-toggle-grant">
                                                    <i class="fa-solid fa-check"></i> Grant Access
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">
                                    <div class="ad-empty">
                                        <i class="fa-solid fa-chalkboard-user"></i>
                                        <p>No registered teachers found yet.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Live Classes Overview -->
        <div class="ad-card">
            <div class="ad-card-header" style="color:#38bdf8; border-color:rgba(56,189,248,.15);">
                <span><i class="fa-solid fa-video"></i> Live Classes Overview</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="ad-table">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Teacher</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($live_classes_overview)): ?>
                            <?php foreach ($live_classes_overview as $lc):
                                $lc_status = strtolower($lc['status'] ?? 'scheduled');
                            ?>
                                <tr>
                                    <td style="font-weight:700; color:#f8fafc;">
                                        <?= htmlspecialchars($lc['class_title']) ?>
                                        <div style="color:#64748b; font-size:.78rem; font-weight:400;">
                                            <?= date('M d, Y — h:i A', strtotime($lc['scheduled_time'])) ?>
                                        </div>
                                    </td>
                                    <td style="color:#cbd5e1; font-size:.88rem;"><?= htmlspecialchars($lc['teacher_name']) ?></td>
                                    <td>
                                        <span class="ad-status-pill ad-status-<?= $lc_status ?>"><?= htmlspecialchars($lc['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">
                                    <div class="ad-empty">
                                        <i class="fa-solid fa-tower-broadcast"></i>
                                        <p>No live classes scheduled yet.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /.ad-two-col -->

</div><!-- /.main-content -->

<script>
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
