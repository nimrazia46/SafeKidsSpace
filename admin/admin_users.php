<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

// ── Admin: directly deactivate/reactivate a user ────────────────
$account_toggle_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_toggle_account_status'])) {
    $toggle_user_id    = intval($_POST['user_id'] ?? 0);
    $toggle_new_status = ($_POST['new_status'] ?? '') === 'deactivate' ? 'deactivated' : 'active';

    if ($toggle_user_id > 0) {
        try {
            $target_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $target_stmt->execute([$toggle_user_id]);
            $target_role = strtolower(trim($target_stmt->fetchColumn()));

            if ($target_role === 'admin' || $target_role === 'administrator') {
                $account_toggle_message = "🚫 Admin accounts can't be deactivated.";
            } else {
                $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?")
                    ->execute([$toggle_new_status, $toggle_user_id]);

                $pdo->prepare(
                    "UPDATE deactivation_requests
                     SET status = ?, reviewed_at = NOW(), reviewed_by = ?
                     WHERE user_id = ? AND status = 'pending'"
                )->execute([
                    $toggle_new_status === 'deactivated' ? 'approved' : 'rejected',
                    $_SESSION['id'],
                    $toggle_user_id
                ]);

                $account_toggle_message = $toggle_new_status === 'deactivated'
                    ? "✅ Account deactivated."
                    : "✅ Account reactivated.";
            }
        } catch (PDOException $e) {
            $account_toggle_message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Approve / Reject an account deactivation request ────────────
$deactivation_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_review_deactivation'])) {
    $request_id = intval($_POST['request_id'] ?? 0);
    $decision   = ($_POST['decision'] ?? '') === 'approve' ? 'approved' : 'rejected';
    if ($request_id > 0) {
        try {
            $find_stmt = $pdo->prepare("SELECT user_id FROM deactivation_requests WHERE id = ? AND status = 'pending'");
            $find_stmt->execute([$request_id]);
            $req_row = $find_stmt->fetch();

            if ($req_row) {
                $pdo->prepare(
                    "UPDATE deactivation_requests SET status = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?"
                )->execute([$decision, $_SESSION['id'], $request_id]);

                $new_account_status = $decision === 'approved' ? 'deactivated' : 'active';
                $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?")
                    ->execute([$new_account_status, $req_row['user_id']]);

                $deactivation_message = $decision === 'approved'
                    ? "✅ Account deactivated as requested."
                    : "🚫 Deactivation request rejected — account remains active.";
            } else {
                $deactivation_message = "This request was already reviewed.";
            }
        } catch (PDOException $e) {
            $deactivation_message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Fetch all users ──────────────────────────────────────────────
try {
    $all_users = $pdo->query("SELECT id, fullname, email, role, account_status FROM users ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    $all_users = [];
}

// ── Role filter tabs ─────────────────────────────────────────────
$valid_role_tabs = ['all', 'student', 'parent', 'teacher', 'admin'];
$active_role_tab = isset($_GET['role']) && in_array($_GET['role'], $valid_role_tabs, true) ? $_GET['role'] : 'all';

$role_tab_counts = ['all' => count($all_users), 'student' => 0, 'parent' => 0, 'teacher' => 0, 'admin' => 0];
foreach ($all_users as $u) {
    $r = strtolower(trim($u['role']));
    if ($r === 'child') { $r = 'student'; }
    if ($r === 'administrator') { $r = 'admin'; }
    if (isset($role_tab_counts[$r])) { $role_tab_counts[$r]++; }
}

$role_tabs = [
    'all'     => ['All Users', $role_tab_counts['all']],
    'student' => ['Students',  $role_tab_counts['student']],
    'parent'  => ['Parents',   $role_tab_counts['parent']],
    'teacher' => ['Teachers',  $role_tab_counts['teacher']],
    'admin'   => ['Admins',    $role_tab_counts['admin']],
];

// Filtered list for the table below (counts above always reflect the full set)
$filtered_users = array_filter($all_users, function ($u) use ($active_role_tab) {
    if ($active_role_tab === 'all') return true;
    $r = strtolower(trim($u['role']));
    if ($r === 'child') { $r = 'student'; }
    if ($r === 'administrator') { $r = 'admin'; }
    return $r === $active_role_tab;
});

// ── Fetch pending deactivation requests ─────────────────────────
try {
    $deactivation_requests = $pdo->query(
        "SELECT dr.id, dr.reason, dr.requested_at, u.id AS user_id, u.fullname, u.email, u.role
         FROM deactivation_requests dr
         JOIN users u ON u.id = dr.user_id
         WHERE dr.status = 'pending'
         ORDER BY dr.requested_at ASC"
    )->fetchAll();
} catch (PDOException $e) {
    $deactivation_requests = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Manage Users</title>
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

    <div class="ad-hero">
        <div class="ad-hero-left">
            <div class="ad-hero-icon"><i class="fa-solid fa-users"></i></div>
            <div>
                <h1 class="ad-hero-title">Manage Users</h1>
                <p class="ad-hero-sub">Accounts &amp; deactivation requests — all in one place</p>
                <span class="ad-hero-badge"><i class="fa-solid fa-circle-check"></i> <?= count($all_users) ?> Total Accounts</span>
            </div>
        </div>
        <div class="ad-hero-right">
            <a href="admin_dashboard.php" class="ad-back-btn"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        </div>
    </div>

    <?php if ($account_toggle_message): ?>
        <div class="ad-flash ad-flash-success" id="adFlash1">
            <i class="fa-solid fa-circle-check"></i> <?= $account_toggle_message ?>
        </div>
    <?php endif; ?>
    <?php if ($deactivation_message): ?>
        <div class="ad-flash ad-flash-success" id="adFlash2">
            <i class="fa-solid fa-circle-check"></i> <?= $deactivation_message ?>
        </div>
    <?php endif; ?>

    <p class="ad-section-title"><i class="fa-solid fa-table-list"></i> All Accounts</p>

    <div class="ao-tabs">
        <?php foreach ($role_tabs as $key => $tab): ?>
            <a href="admin_users.php?role=<?= $key ?>" class="ao-tab <?= $active_role_tab === $key ? 'active' : '' ?>">
                <?= htmlspecialchars($tab[0]) ?> <span class="ao-tab-count"><?= $tab[1] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="ad-card">
        <div style="overflow-x:auto;">
            <table class="ad-table">
                <thead>
                    <tr>
                        <th>UID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($filtered_users)): ?>
                        <?php foreach ($filtered_users as $user_row):
                            $normalized_role = strtolower(trim($user_row['role']));
                            if ($normalized_role === 'student' || $normalized_role === 'child') {
                                $badge_class = 'ad-badge-kid';
                            } elseif ($normalized_role === 'parent') {
                                $badge_class = 'ad-badge-parent';
                            } elseif ($normalized_role === 'teacher') {
                                $badge_class = 'ad-badge-teacher';
                            } else {
                                $badge_class = 'ad-badge-admin';
                            }
                        ?>
                            <tr>
                                <td><span class="ad-uid">#<?= $user_row['id'] ?></span></td>
                                <td style="font-weight:700; color:#f8fafc;"><?= htmlspecialchars($user_row['fullname']) ?></td>
                                <td style="color:#64748b; font-size:.88rem;"><?= htmlspecialchars($user_row['email']) ?></td>
                                <td><span class="ad-role-badge <?= $badge_class ?>"><?= htmlspecialchars($user_row['role']) ?></span></td>
                                <td>
                                    <?php if (($user_row['account_status'] ?? 'active') === 'deactivated'): ?>
                                        <span class="ad-permission-pill ad-permission-pending">Deactivated</span>
                                    <?php else: ?>
                                        <span class="ad-permission-pill ad-permission-approved">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <?php if ($normalized_role === 'admin' || $normalized_role === 'administrator'): ?>
                                        <span style="color:#64748b; font-size:.8rem;">—</span>
                                    <?php elseif (($user_row['account_status'] ?? 'active') === 'deactivated'): ?>
                                        <form action="admin_users.php?role=<?= htmlspecialchars($active_role_tab) ?>" method="POST" style="display:inline;" class="ad-confirm-form" data-confirm-msg="Reactivate this account? The user will be able to sign in again.">
                                            <input type="hidden" name="_toggle_account_status" value="1">
                                            <input type="hidden" name="user_id" value="<?= intval($user_row['id']) ?>">
                                            <input type="hidden" name="new_status" value="activate">
                                            <button type="submit" class="ad-live-toggle-btn ad-live-toggle-grant">
                                                <i class="fa-solid fa-user-check"></i> Reactivate
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="admin_users.php?role=<?= htmlspecialchars($active_role_tab) ?>" method="POST" style="display:inline;" class="ad-confirm-form" data-confirm-msg="Deactivate this account? The user will not be able to sign in until reactivated.">
                                            <input type="hidden" name="_toggle_account_status" value="1">
                                            <input type="hidden" name="user_id" value="<?= intval($user_row['id']) ?>">
                                            <input type="hidden" name="new_status" value="deactivate">
                                            <button type="submit" class="ad-live-toggle-btn ad-live-toggle-revoke">
                                                <i class="fa-solid fa-user-slash"></i> Deactivate
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="ad-empty">
                                    <i class="fa-solid fa-database"></i>
                                    <p>No user records found in the database.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="ad-section-title" style="margin-top:36px;"><i class="fa-solid fa-user-slash"></i> Pending Deactivation Requests</p>

    <div class="ad-card">
        <div class="ad-card-header" style="color:#f87171; border-color:rgba(248,113,113,.15);">
            <span><i class="fa-solid fa-user-slash"></i> Requests</span>
            <span class="ad-sync-badge"><?= count($deactivation_requests) ?> waiting</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="ad-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Requested</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($deactivation_requests)): ?>
                        <?php foreach ($deactivation_requests as $req): ?>
                            <tr>
                                <td style="font-weight:700; color:#f8fafc;"><?= htmlspecialchars($req['fullname']) ?></td>
                                <td style="color:#64748b; font-size:.88rem;"><?= htmlspecialchars($req['email']) ?></td>
                                <td><span class="ad-permission-pill ad-permission-pending"><?= htmlspecialchars(ucfirst($req['role'])) ?></span></td>
                                <td style="color:#64748b; font-size:.82rem;"><?= date('M d, Y', strtotime($req['requested_at'])) ?></td>
                                <td style="text-align:right;">
                                    <form action="admin_users.php" method="POST" style="display:inline;" class="ad-confirm-form" data-confirm-msg="Approve this request and deactivate the account? The user will be signed out and unable to log in until reactivated.">
                                        <input type="hidden" name="_review_deactivation" value="1">
                                        <input type="hidden" name="request_id" value="<?= intval($req['id']) ?>">
                                        <input type="hidden" name="decision" value="approve">
                                        <button type="submit" class="ad-live-toggle-btn ad-live-toggle-grant">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form action="admin_users.php" method="POST" style="display:inline;" class="ad-confirm-form" data-confirm-msg="Reject this deactivation request? The account will remain active.">
                                        <input type="hidden" name="_review_deactivation" value="1">
                                        <input type="hidden" name="request_id" value="<?= intval($req['id']) ?>">
                                        <input type="hidden" name="decision" value="reject">
                                        <button type="submit" class="ad-live-toggle-btn ad-live-toggle-revoke">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="ad-empty">
                                    <i class="fa-solid fa-user-check"></i>
                                    <p>No pending deactivation requests.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

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
