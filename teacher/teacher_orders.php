<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'teacher') {
    header("Location: ../account/login.php");
    exit();
}

$teacher_id = $_SESSION['id'];

$orders = [];
try {
    $ord_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
    $ord_stmt->execute([$teacher_id]);
    $orders_raw = $ord_stmt->fetchAll();

    if (!empty($orders_raw)) {
        $order_ids = array_column($orders_raw, 'id');
        $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
        $items_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($placeholders)");
        $items_stmt->execute($order_ids);
        $items_by_order = [];
        foreach ($items_stmt->fetchAll() as $item) {
            $items_by_order[$item['order_id']][] = $item;
        }
        foreach ($orders_raw as $order) {
            $order['items'] = $items_by_order[$order['id']] ?? [];
            $orders[] = $order;
        }
    }
} catch (PDOException $e) {
    $orders = [];
}

$ord_status_labels = [
    'pending'         => ['Pending Confirmation', '#facc15', 'fa-hourglass-half'],
    'confirmed'       => ['Confirmed', '#22c55e', 'fa-circle-check'],
    'delivered'       => ['Delivered', '#38bdf8', 'fa-truck'],
    'pending_payment' => ['Pending Verification', '#facc15', 'fa-hourglass-half'],
    'rejected'        => ['Rejected', '#f87171', 'fa-circle-xmark'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — My Orders</title>
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

<div class="main-content td-wrap">

    <div class="td-hero">
        <div class="td-hero-left">
            <img
                src="<?= !empty($_SESSION['profile_pic']) ? '../' . htmlspecialchars($_SESSION['profile_pic']) : '../assets/images/default-avatar.png' ?>"
                class="td-hero-avatar"
                alt="Profile Photo">
            <div>
                <h1 class="td-hero-title">My Orders</h1>
                <p class="td-hero-sub">Every item you've ordered from the Kids Store</p>
                <span class="td-hero-badge"><i class="fa-solid fa-graduation-cap"></i> Instructor</span>
            </div>
        </div>
        <div class="td-hero-right">
            <a href="teacher_dashboard.php" class="td-btn" style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); text-decoration:none;">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
        </div>
    </div>

    <p class="td-section-title"><i class="fa-solid fa-receipt"></i> Order History</p>

    <?php if (empty($orders)): ?>
        <div class="td-empty">
            <i class="fa-solid fa-box-open"></i>
            <p>You haven't placed any orders yet. <a href="../store/store.php" style="color:#38bdf8;">Visit the store →</a></p>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order):
            $status_info = $ord_status_labels[$order['order_status']] ?? ['Pending Confirmation', '#facc15', 'fa-hourglass-half'];
        ?>
            <div class="ord-card">
                <div class="ord-card-top">
                    <div>
                        <div class="ord-id">Order #<?= intval($order['id']) ?></div>
                        <div class="ord-date"><?= date('d M Y, h:i A', strtotime($order['order_date'])) ?></div>
                    </div>
                    <span class="ord-status-pill" style="background:<?= $status_info[1] ?>1a; color:<?= $status_info[1] ?>;">
                        <i class="fa-solid <?= $status_info[2] ?>"></i> <?= $status_info[0] ?>
                    </span>
                </div>

                <?php foreach ($order['items'] as $item): ?>
                    <div class="ord-item-row">
                        <img src="<?= htmlspecialchars($item['image_path'] ? '../' . $item['image_path'] : '../images/banner.png') ?>" alt="">
                        <div>
                            <div class="ord-item-title"><?= htmlspecialchars($item['title']) ?></div>
                            <div class="ord-item-meta">Qty: <?= intval($item['qty']) ?></div>
                        </div>
                        <div class="ord-item-price">$<?= number_format($item['price'] * $item['qty'], 2) ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="ord-card-bottom">
                    <div class="ord-meta-line">
                        <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($order['billing_address']) ?>
                        &nbsp;•&nbsp;
                        <i class="fa-solid fa-credit-card"></i> <?= htmlspecialchars(ucfirst($order['payment_method'])) ?>
                    </div>
                    <div class="ord-total">Total: $<?= number_format($order['total_amount'], 2) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div><!-- /.main-content -->

</body>
</html>