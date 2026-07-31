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

// ── Full payment history for the selected child (all statuses) ───────
$pd_payment_history = [];
try {
    $hist_stmt = $pdo->prepare(
        "SELECT pay.id, pay.amount, pay.method, pay.reference_note, pay.status,
                pay.created_at, pay.confirmed_at, pay.period_start, pay.period_end,
                p.title AS program_title
         FROM payments pay
         JOIN enrollments e ON e.id = pay.enrollment_id
         JOIN programs p ON p.id = e.program_id
         WHERE e.child_id = ? AND e.parent_id = ?
         ORDER BY pay.created_at DESC"
    );
    $hist_stmt->execute([$selected_child_id, $parent_id]);
    $pd_payment_history = $hist_stmt->fetchAll();
} catch (PDOException $e) {
    $pd_payment_history = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Payment History</title>
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
                <h1 class="pd-hero-title">Payment History</h1>
                <p class="pd-hero-sub">Every payment submitted for <?= htmlspecialchars($child_info['fullname']) ?></p>
                <span class="pd-hero-badge"><i class="fa-solid fa-user-shield"></i> Parent Account</span>
            </div>
        </div>
        <div class="pd-hero-right">
            <a href="parent_dashboard.php" class="pd-btn" style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); text-decoration:none;">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="pd-children-tabs" style="margin-bottom:28px;">
        <?php foreach ($linked_children as $child): ?>
            <a href="parent_payments.php?child_id=<?= $child['id'] ?>" class="pd-child-tab <?= $child['id'] == $selected_child_id ? 'active' : '' ?>">
                <img src="<?= !empty($child['profile_pic']) ? '../' . htmlspecialchars($child['profile_pic']) : 'https://cdn-icons-png.flaticon.com/512/4333/4333609.png' ?>" alt="">
                <?= htmlspecialchars($child['fullname']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <p class="pd-section-title"><i class="fa-solid fa-receipt" style="color:#facc15"></i> Payment History for <?= htmlspecialchars($child_info['fullname']) ?></p>

    <div class="pd-card" style="margin-bottom:32px;">
        <?php if (!empty($pd_payment_history)): ?>
            <div style="overflow-x:auto;">
                <table class="pd-quiz-table">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Amount</th>
                            <th>Method / Reference</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Reviewed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pd_payment_history as $hist): ?>
                            <tr>
                                <td style="color:#e2e8f0; font-size:.85rem;"><?= htmlspecialchars($hist['program_title']) ?></td>
                                <td style="color:#facc15; font-family:'Orbitron',sans-serif; font-size:.85rem;">Rs.<?= number_format($hist['amount'], 0) ?></td>
                                <td style="color:#94a3b8; font-size:.8rem;">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $hist['method']))) ?>
                                    <div style="color:#64748b; font-size:.74rem;"><?= htmlspecialchars($hist['reference_note']) ?></div>
                                </td>
                                <td>
                                    <span class="pd-program-status pd-status-<?= $hist['status'] === 'confirmed' ? 'active' : ($hist['status'] === 'pending' ? 'pending' : 'rejected') ?>">
                                        <?php if ($hist['status'] === 'confirmed'): ?>
                                            <i class="fa-solid fa-circle-check"></i> Confirmed
                                        <?php elseif ($hist['status'] === 'pending'): ?>
                                            <i class="fa-solid fa-hourglass-half"></i> Pending
                                        <?php else: ?>
                                            <i class="fa-solid fa-circle-xmark"></i> Rejected
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td style="color:#64748b; font-size:.78rem;"><?= date('M d, Y', strtotime($hist['created_at'])) ?></td>
                                <td style="color:#64748b; font-size:.78rem;">
                                    <?= $hist['confirmed_at'] ? date('M d, Y', strtotime($hist['confirmed_at'])) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="pd-empty">
                <i class="fa-solid fa-receipt"></i>
                <p>No payments yet for <?= htmlspecialchars($child_info['fullname']) ?>.</p>
            </div>
        <?php endif; ?>
    </div>

    <div style="text-align:center;">
        <a href="parent_programs.php?child_id=<?= $selected_child_id ?>" class="pd-btn pd-btn-primary" style="text-decoration:none; display:inline-flex;">
            <i class="fa-solid fa-credit-card"></i> Make a New Payment
        </a>
    </div>

</div><!-- /.main-content -->

</body>
</html>