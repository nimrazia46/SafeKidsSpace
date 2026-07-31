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

$ord_status_labels = [
    'pending'         => ['Pending Confirmation', '#facc15', 'fa-hourglass-half'],
    'confirmed'       => ['Confirmed', '#22c55e', 'fa-circle-check'],
    'delivered'       => ['Delivered', '#38bdf8', 'fa-truck'],
    'pending_payment' => ['Pending Verification', '#facc15', 'fa-hourglass-half'],
    'rejected'        => ['Rejected', '#f87171', 'fa-circle-xmark'],
];

function ord_fetch_orders($pdo, $user_id) {
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
    return $orders;
}

// ── Parent's own orders ─────────────────────────────────────────
$parent_own_orders = ord_fetch_orders($pdo, $parent_id);

// ── Linked children (for the switcher tabs) ─────────────────────
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

$child_orders = [];
if ($selected_child_id && !empty($child_info)) {
    $child_orders = ord_fetch_orders($pdo, $selected_child_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Orders</title>
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
                <h1 class="pd-hero-title">Orders</h1>
                <p class="pd-hero-sub">Store orders — yours and your children's</p>
                <span class="pd-hero-badge"><i class="fa-solid fa-user-shield"></i> Parent Account</span>
            </div>
        </div>
        <div class="pd-hero-right">
            <a href="parent_dashboard.php" class="pd-btn" style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); text-decoration:none;">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- ═══════════════ MY OWN ORDERS ═══════════════ -->
    <p class="pd-section-title"><i class="fa-solid fa-receipt" style="color:#facc15"></i> My Orders</p>

    <?php if (empty($parent_own_orders)): ?>
        <div class="pd-empty" style="margin-bottom:36px;">
            <i class="fa-solid fa-box-open"></i>
            <p>You haven't placed any orders yet. <a href="../store/store.php" style="color:#38bdf8;">Visit the store →</a></p>
        </div>
    <?php else: ?>
        <div style="margin-bottom:36px;">
            <?php foreach ($parent_own_orders as $order):
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
        </div>
    <?php endif; ?>

    <!-- ═══════════════ CHILD'S ORDERS ═══════════════ -->
    <p class="pd-section-title"><i class="fa-solid fa-child-reaching" style="color:#38bdf8"></i> Child's Orders</p>

    <?php if (empty($linked_children)): ?>
        <div class="pd-empty">
            <i class="fa-solid fa-user-plus"></i>
            <p>No children linked yet. <a href="parent_dashboard.php" style="color:#38bdf8;">Link a child →</a></p>
        </div>
    <?php else: ?>
        <div class="pd-children-tabs" style="margin-bottom:20px;">
            <?php foreach ($linked_children as $child): ?>
                <a href="parent_orders.php?child_id=<?= $child['id'] ?>" class="pd-child-tab <?= $child['id'] == $selected_child_id ? 'active' : '' ?>">
                    <img src="<?= !empty($child['profile_pic']) ? '../' . htmlspecialchars($child['profile_pic']) : 'https://cdn-icons-png.flaticon.com/512/4333/4333609.png' ?>" alt="">
                    <?= htmlspecialchars($child['fullname']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($child_orders)): ?>
            <div class="pd-empty">
                <i class="fa-solid fa-box-open"></i>
                <p><?= htmlspecialchars($child_info['fullname'] ?? 'This child') ?> hasn't placed any orders yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($child_orders as $order):
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
    <?php endif; ?>

</div><!-- /.main-content -->

</body>
</html>