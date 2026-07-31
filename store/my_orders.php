<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../account/login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Fetch this user's own orders with their items
$orders = [];
try {
    $ord_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
    $ord_stmt->execute([$user_id]);
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/layout.css">

</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
    <div class="myo-wrap">

        <div class="myo-hero">
            <div class="myo-hero-icon"><i class="fa-solid fa-receipt"></i></div>
            <div>
                <h1 class="myo-hero-title">My Orders</h1>
                <p class="myo-hero-sub">Every item you've ordered from the Kids Store</p>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="myo-empty">
                <i class="fa-solid fa-box-open"></i>
                <p>You haven't placed any orders yet. <a href="store.php" style="color:#38bdf8;">Visit the store →</a></p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order):
                $status_info = $ord_status_labels[$order['order_status']] ?? ['Pending Confirmation', '#facc15', 'fa-hourglass-half'];
            ?>
                <div class="myo-card">
                    <div class="myo-card-top">
                        <div>
                            <div class="myo-id">Order #<?= intval($order['id']) ?></div>
                            <div class="myo-date"><?= date('d M Y, h:i A', strtotime($order['order_date'])) ?></div>
                        </div>
                        <span class="myo-status-pill" style="background:<?= $status_info[1] ?>1a; color:<?= $status_info[1] ?>;">
                            <i class="fa-solid <?= $status_info[2] ?>"></i> <?= $status_info[0] ?>
                        </span>
                    </div>

                    <?php foreach ($order['items'] as $item): ?>
                        <div class="myo-item-row">
                            <img src="<?= htmlspecialchars($item['image_path'] ? '../' . $item['image_path'] : '../images/banner.png') ?>" alt="">
                            <div>
                                <div class="myo-item-title"><?= htmlspecialchars($item['title']) ?></div>
                                <div class="myo-item-meta">Qty: <?= intval($item['qty']) ?></div>
                            </div>
                            <div class="myo-item-price">$<?= number_format($item['price'] * $item['qty'], 2) ?></div>
                        </div>
                    <?php endforeach; ?>

                    <div class="myo-card-bottom">
                        <div class="myo-meta-line">
                            <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($order['billing_address']) ?>
                            &nbsp;•&nbsp;
                            <i class="fa-solid fa-credit-card"></i> <?= htmlspecialchars(ucfirst($order['payment_method'])) ?>
                        </div>
                        <div class="myo-total">Total: $<?= number_format($order['total_amount'], 2) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>