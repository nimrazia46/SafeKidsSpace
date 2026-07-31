<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

// ── Confirm / Reject a submitted payment ──────────────────────────
$payment_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_review_payment'])) {
    $pay_id       = intval($_POST['payment_id'] ?? 0);
    $pay_decision = ($_POST['decision'] ?? '') === 'confirm' ? 'confirmed' : 'rejected';

    if ($pay_id > 0) {
        try {
            $pdo->beginTransaction();

            $find_pay = $pdo->prepare("SELECT * FROM payments WHERE id = ? AND status = 'pending'");
            $find_pay->execute([$pay_id]);
            $payment_row = $find_pay->fetch();

            if ($payment_row) {
                $pdo->prepare(
                    "UPDATE payments SET status = ?, confirmed_by = ?, confirmed_at = NOW() WHERE id = ?"
                )->execute([$pay_decision, $_SESSION['id'], $pay_id]);

                if ($pay_decision === 'confirmed') {
                    $pdo->prepare(
                        "UPDATE enrollments SET status = 'active', expires_at = ? WHERE id = ?"
                    )->execute([$payment_row['period_end'], $payment_row['enrollment_id']]);
                    $payment_message = "✅ Payment confirmed — the program is now active for that child.";
                } else {
                    $payment_message = "🚫 Payment rejected. The parent will need to resubmit.";
                }
                $pdo->commit();
            } else {
                $pdo->rollBack();
                $payment_message = "Payment not found or already reviewed.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $payment_message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Fetch pending payments awaiting confirmation ──────────────────
try {
    $pending_payments = $pdo->query(
        "SELECT pay.id, pay.amount, pay.method, pay.reference_note, pay.created_at, pay.period_start, pay.period_end,
                e.id AS enrollment_id, p.title AS program_title,
                parent.fullname AS parent_name, child.fullname AS child_name
         FROM payments pay
         JOIN enrollments e ON e.id = pay.enrollment_id
         JOIN programs p ON p.id = e.program_id
         JOIN users parent ON parent.id = e.parent_id
         JOIN users child ON child.id = e.child_id
         WHERE pay.status = 'pending'
         ORDER BY pay.created_at ASC"
    )->fetchAll();
} catch (PDOException $e) {
    $pending_payments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Parent Payments</title>
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
            <div class="ad-hero-icon"><i class="fa-solid fa-money-check-dollar"></i></div>
            <div>
                <h1 class="ad-hero-title">Parent Payments</h1>
                <p class="ad-hero-sub">Review and confirm program payments submitted by parents</p>
                <span class="ad-hero-badge"><i class="fa-solid fa-circle-check"></i> <?= count($pending_payments) ?> Waiting for Review</span>
            </div>
        </div>
        <div class="ad-hero-right">
            <a href="admin_dashboard.php" class="ad-back-btn"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        </div>
    </div>

    <?php if ($payment_message): ?>
        <div class="ad-flash ad-flash-success" id="adFlash1">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($payment_message) ?>
        </div>
    <?php endif; ?>

    <p class="ad-section-title"><i class="fa-solid fa-money-check-dollar"></i> Payment Confirmations</p>

    <div class="ad-card">
        <div style="overflow-x:auto;">
            <table class="ad-table">
                <thead>
                    <tr>
                        <th>Parent / Child</th>
                        <th>Program</th>
                        <th>Amount</th>
                        <th>Method / Reference</th>
                        <th>Submitted</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pending_payments)): ?>
                        <?php foreach ($pending_payments as $pay): ?>
                            <tr>
                                <td style="color:#f8fafc; font-weight:600; font-size:.85rem;">
                                    <?= htmlspecialchars($pay['parent_name']) ?>
                                    <div style="color:#64748b; font-weight:400; font-size:.78rem;">for <?= htmlspecialchars($pay['child_name']) ?></div>
                                </td>
                                <td style="color:#cbd5e1; font-size:.85rem;"><?= htmlspecialchars($pay['program_title']) ?></td>
                                <td style="color:#facc15; font-family:'Orbitron',sans-serif; font-size:.85rem;">Rs.<?= number_format($pay['amount'], 0) ?></td>
                                <td style="color:#cbd5e1; font-size:.82rem;">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $pay['method']))) ?>
                                    <div style="color:#64748b; font-size:.76rem;"><?= htmlspecialchars($pay['reference_note']) ?></div>
                                </td>
                                <td style="color:#64748b; font-size:.82rem;"><?= date('M d, Y', strtotime($pay['created_at'])) ?></td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <form action="admin_payments.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="_review_payment" value="1">
                                        <input type="hidden" name="payment_id" value="<?= intval($pay['id']) ?>">
                                        <input type="hidden" name="decision" value="confirm">
                                        <button type="submit" class="ad-live-toggle-btn ad-live-toggle-grant">
                                            <i class="fa-solid fa-check"></i> Confirm
                                        </button>
                                    </form>
                                    <form action="admin_payments.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="_review_payment" value="1">
                                        <input type="hidden" name="payment_id" value="<?= intval($pay['id']) ?>">
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
                            <td colspan="6">
                                <div class="ad-empty">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <p>No payments waiting for review. All caught up!</p>
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
