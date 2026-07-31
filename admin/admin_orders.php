<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

// Admin only
$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

$order_message = '';

// ── Confirm / Reject an order ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_review_order'])) {
    $order_id  = intval($_POST['order_id'] ?? 0);
    $decision  = ($_POST['decision'] ?? '') === 'confirm' ? 'confirmed' : 'rejected';

    if ($order_id > 0) {
        try {
            $pdo->beginTransaction();

            $find_order = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND order_status = 'pending'");
            $find_order->execute([$order_id]);
            $order_row = $find_order->fetch();

            if ($order_row) {
                $pdo->prepare(
                    "UPDATE orders SET order_status = ? WHERE id = ?"
                )->execute([$decision, $order_id]);

                if ($decision === 'confirmed') {
                    $order_message = "✅ Order #{$order_id} confirmed — the customer will see it as Confirmed.";
                    notify_user(
                        $pdo,
                        $order_row['user_id'],
                        'Order confirmed',
                        "Your order #{$order_id} has been confirmed and is being processed.",
                        'store/my_orders.php',
                        'fa-solid fa-circle-check'
                    );
                } else {
                    // Stock was already deducted when the order was placed.
                    // Since this order never went through, give it back.
                    $restore_stmt = $pdo->prepare(
                        "UPDATE store_products p
                         JOIN order_items oi ON oi.product_id = p.id
                         SET p.stock = p.stock + oi.qty
                         WHERE oi.order_id = ?"
                    );
                    $restore_stmt->execute([$order_id]);

                    $order_message = "🚫 Order #{$order_id} rejected. The customer will be notified.";
                    notify_user(
                        $pdo,
                        $order_row['user_id'],
                        'Order rejected',
                        "Your order #{$order_id} could not be processed and has been rejected.",
                        'store/my_orders.php',
                        'fa-solid fa-circle-xmark'
                    );
                }

                $pdo->commit();
            } else {
                $pdo->rollBack();
                $order_message = "Order not found or already reviewed.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $order_message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Mark a confirmed order as delivered ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_mark_delivered'])) {
    $deliver_order_id = intval($_POST['order_id'] ?? 0);

    if ($deliver_order_id > 0) {
        try {
            $find_order = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND order_status = 'confirmed'");
            $find_order->execute([$deliver_order_id]);
            $order_row = $find_order->fetch();

            if ($order_row) {
                $pdo->prepare("UPDATE orders SET order_status = 'delivered' WHERE id = ?")->execute([$deliver_order_id]);

                $order_message = "📦 Order #{$deliver_order_id} marked as delivered.";
                notify_user(
                    $pdo,
                    $order_row['user_id'],
                    'Order delivered',
                    "Your order #{$deliver_order_id} has been delivered. We hope your kids enjoy it!",
                    'store/my_orders.php',
                    'fa-solid fa-truck'
                );
            } else {
                $order_message = "Order not found or not in a confirmed state.";
            }
        } catch (PDOException $e) {
            $order_message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Active tab / filter ─────────────────────────────────────────────
$valid_tabs  = ['pending', 'confirmed', 'delivered', 'rejected', 'all'];
$active_tab  = isset($_GET['status']) && in_array($_GET['status'], $valid_tabs, true) ? $_GET['status'] : 'pending';

// ── Counts for tab badges ───────────────────────────────────────────
try {
    $count_rows = $pdo->query(
        "SELECT order_status, COUNT(*) AS c FROM orders GROUP BY order_status"
    )->fetchAll();
    $status_counts = ['pending' => 0, 'confirmed' => 0, 'delivered' => 0, 'rejected' => 0];
    $total_count = 0;
    foreach ($count_rows as $row) {
        if (isset($status_counts[$row['order_status']])) {
            $status_counts[$row['order_status']] = (int) $row['c'];
        }
        $total_count += (int) $row['c'];
    }
} catch (PDOException $e) {
    $status_counts = ['pending' => 0, 'confirmed' => 0, 'delivered' => 0, 'rejected' => 0];
    $total_count = 0;
}

// ── Fetch orders for active tab ─────────────────────────────────────
try {
    if ($active_tab === 'all') {
        $ord_stmt = $pdo->query(
            "SELECT o.*, u.fullname AS customer_name, u.email AS customer_email, u.role AS customer_role
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             ORDER BY o.order_date DESC"
        );
    } else {
        $ord_stmt = $pdo->prepare(
            "SELECT o.*, u.fullname AS customer_name, u.email AS customer_email, u.role AS customer_role
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             WHERE o.order_status = ?
             ORDER BY o.order_date DESC"
        );
        $ord_stmt->execute([$active_tab]);
    }
    $orders = $ord_stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}

// ── Fetch order items for the orders we're about to show ───────────
$items_by_order = [];
if (!empty($orders)) {
    $order_ids = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $item_stmt = $pdo->prepare(
        "SELECT * FROM order_items WHERE order_id IN ($placeholders) ORDER BY id ASC"
    );
    $item_stmt->execute($order_ids);
    foreach ($item_stmt->fetchAll() as $item) {
        $items_by_order[$item['order_id']][] = $item;
    }
}

$ord_status_labels = [
    'pending'   => ['Pending Confirmation', '#facc15', 'fa-hourglass-half'],
    'confirmed' => ['Confirmed', '#22c55e', 'fa-circle-check'],
    'delivered' => ['Delivered', '#38bdf8', 'fa-truck'],
    'rejected'  => ['Rejected', '#f87171', 'fa-circle-xmark'],
];

$tabs = [
    'all'       => ['All Orders', $total_count],
    'pending'   => ['Pending', $status_counts['pending']],
    'confirmed' => ['Confirmed', $status_counts['confirmed']],
    'delivered' => ['Delivered', $status_counts['delivered']],
    'rejected'  => ['Rejected', $status_counts['rejected']],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Admin Orders</title>
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
            <div class="ad-hero-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <div>
                <h1 class="ad-hero-title">Store Orders</h1>
                <p class="ad-hero-sub">Review incoming orders and confirm them to notify the customer</p>
                <span class="ad-hero-badge"><i class="fa-solid fa-hourglass-half"></i> <?= $status_counts['pending'] ?> Waiting for Review</span>
            </div>
        </div>
        <div class="ad-hero-right">
            <a href="admin_dashboard.php" class="ad-back-btn"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        </div>
    </div>

    <?php if ($order_message): ?>
        <div class="ad-flash ad-flash-success" id="adFlash1">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($order_message) ?>
        </div>
    <?php endif; ?>

    <p class="ad-section-title"><i class="fa-solid fa-cart-shopping"></i> Customer Orders</p>

    <div class="ao-tabs">
        <?php foreach ($tabs as $key => $tab): ?>
            <a href="admin_orders.php?status=<?= $key ?>" class="ao-tab <?= $active_tab === $key ? 'active' : '' ?>">
                <?= htmlspecialchars($tab[0]) ?> <span class="ao-tab-count"><?= $tab[1] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="ad-card">
        <div style="overflow-x:auto;">
            <table class="ad-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Placed</th>
                        <th>Status</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order):
                            $status_info = $ord_status_labels[$order['order_status']] ?? ['Pending Confirmation', '#facc15', 'fa-hourglass-half'];
                            $order_items = $items_by_order[$order['id']] ?? [];
                        ?>
                            <tr>
                                <td style="color:#f8fafc; font-weight:700; font-size:.85rem;">#<?= intval($order['id']) ?></td>
                                <td style="color:#cbd5e1; font-size:.85rem;">
                                    <?= htmlspecialchars($order['customer_name'] ?? $order['billing_name'] ?? 'Guest') ?>
                                    <div style="color:#64748b; font-weight:400; font-size:.76rem;"><?= htmlspecialchars($order['contact_number'] ?? $order['customer_email'] ?? '') ?></div>
                                </td>
                                <td class="ao-items-list">
                                    <?php if (!empty($order_items)): ?>
                                        <?php foreach ($order_items as $item): ?>
                                            <?= htmlspecialchars($item['title']) ?> <span>× <?= intval($item['qty']) ?></span><br>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span>No items found</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color:#facc15; font-family:'Orbitron',sans-serif; font-size:.85rem;">Rs.<?= number_format($order['total_amount'], 0) ?></td>
                                <td style="color:#cbd5e1; font-size:.82rem;">
                                    <?= htmlspecialchars(ucfirst($order['payment_method'] ?? '—')) ?>
                                    <?php if (!empty($order['payment_reference'])): ?>
                                        <div style="color:#64748b; font-size:.76rem;">TID: <?= htmlspecialchars($order['payment_reference']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="color:#64748b; font-size:.82rem;"><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                <td>
                                    <span class="ad-status-pill" style="background:<?= $status_info[1] ?>1a; color:<?= $status_info[1] ?>; border:1px solid <?= $status_info[1] ?>40;">
                                        <i class="fa-solid <?= $status_info[2] ?>"></i> <?= htmlspecialchars($status_info[0]) ?>
                                    </span>
                                </td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <?php if ($order['order_status'] === 'pending'): ?>
                                        <div class="ao-actions">
                                            <form action="admin_orders.php?status=<?= htmlspecialchars($active_tab) ?>" method="POST" class="ad-confirm-form" data-confirm-msg="Confirm order #<?= intval($order['id']) ?>? The customer will be notified.">
                                                <input type="hidden" name="_review_order" value="1">
                                                <input type="hidden" name="order_id" value="<?= intval($order['id']) ?>">
                                                <input type="hidden" name="decision" value="confirm">
                                                <button type="submit" class="ad-live-toggle-btn ad-live-toggle-grant">
                                                    <i class="fa-solid fa-check"></i> Confirm
                                                </button>
                                            </form>
                                            <form action="admin_orders.php?status=<?= htmlspecialchars($active_tab) ?>" method="POST" class="ad-confirm-form" data-confirm-msg="Reject order #<?= intval($order['id']) ?>? The customer will be notified.">
                                                <input type="hidden" name="_review_order" value="1">
                                                <input type="hidden" name="order_id" value="<?= intval($order['id']) ?>">
                                                <input type="hidden" name="decision" value="reject">
                                                <button type="submit" class="ad-live-toggle-btn ad-live-toggle-revoke">
                                                    <i class="fa-solid fa-xmark"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    <?php elseif ($order['order_status'] === 'confirmed'): ?>
                                        <div class="ao-actions">
                                            <form action="admin_orders.php?status=<?= htmlspecialchars($active_tab) ?>" method="POST" class="ad-confirm-form" data-confirm-msg="Mark order #<?= intval($order['id']) ?> as delivered? The customer will be notified.">
                                                <input type="hidden" name="_mark_delivered" value="1">
                                                <input type="hidden" name="order_id" value="<?= intval($order['id']) ?>">
                                                <button type="submit" class="ad-live-toggle-btn ad-live-toggle-grant">
                                                    <i class="fa-solid fa-truck"></i> Mark Delivered
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#64748b; font-size:.8rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <div class="ad-empty">
                                    <i class="fa-solid fa-box-open"></i>
                                    <p>No orders found in this view.</p>
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