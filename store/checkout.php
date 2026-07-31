<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cart_data'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}

// ---------------------------------------------------------------
// 1) Parse cart
// ---------------------------------------------------------------
$cart    = json_decode($_POST['cart_data'], true);
$user_id = $_SESSION['id'];

if (empty($cart) || !is_array($cart)) {
    echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
    exit();
}

// ---------------------------------------------------------------
// 2) Collect + validate billing / contact / payment fields
// ---------------------------------------------------------------
$first_name      = isset($_POST['first_name'])      ? trim($_POST['first_name'])      : '';
$last_name       = isset($_POST['last_name'])       ? trim($_POST['last_name'])       : '';
$contact_number  = isset($_POST['contact_number'])  ? trim($_POST['contact_number'])  : '';
$address         = isset($_POST['address'])         ? trim($_POST['address'])         : '';
$city            = isset($_POST['city'])            ? trim($_POST['city'])            : '';
$payment_method  = isset($_POST['payment_method'])  ? strtolower(trim($_POST['payment_method'])) : '';
$payment_ref     = isset($_POST['payment_reference']) ? trim($_POST['payment_reference']) : '';

$errors = [];

if ($first_name === '' || mb_strlen($first_name) < 2) {
    $errors['first_name'] = 'Please enter your first name.';
}

if ($last_name === '') {
    $errors['last_name'] = 'Please enter your last name.';
}

// Pakistani mobile format: 03XXXXXXXXX or +923XXXXXXXXX
if (!preg_match('/^(03[0-9]{9}|\+92 ?3[0-9]{9})$/', $contact_number)) {
    $errors['contact_number'] = 'Please enter a valid contact number (e.g. 03001234567).';
}

if ($address === '' || mb_strlen($address) < 6) {
    $errors['address'] = 'Please enter a complete delivery address.';
}

if ($city === '' || mb_strlen($city) < 2) {
    $errors['city'] = 'Please enter your city.';
}

$billing_name    = trim($first_name . ' ' . $last_name);
$billing_address = trim($address . ', ' . $city);

$valid_methods = ['cod', 'jazzcash', 'easypaisa'];
if (!in_array($payment_method, $valid_methods, true)) {
    $errors['payment_method'] = 'Please select a valid payment method.';
}

// JazzCash / EasyPaisa require a transaction ID reference
if (in_array($payment_method, ['jazzcash', 'easypaisa'], true)) {
    if ($payment_ref === '' || mb_strlen($payment_ref) < 4) {
        $errors['payment_reference'] = 'Please enter your transaction ID (TID) for verification.';
    }
}

if (!empty($errors)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fix the highlighted fields.', 'errors' => $errors]);
    exit();
}

// ---------------------------------------------------------------
// 3) Recompute totals server-side (never trust client-side prices)
// ---------------------------------------------------------------
try {
    $pdo->beginTransaction();

    $product_ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    // SELECT ... FOR UPDATE locks these rows so two simultaneous orders
    // can't both "see" the same stock and oversell the last item.
    $price_stmt = $pdo->prepare("SELECT id, title, price, image_path, stock FROM store_products WHERE id IN ($placeholders) AND is_active = 1 FOR UPDATE");
    $price_stmt->execute($product_ids);
    $products_by_id = [];
    foreach ($price_stmt->fetchAll() as $row) {
        $products_by_id[$row['id']] = $row;
    }

    $subtotal = 0;
    $verified_cart = [];
    $stock_errors = [];
    foreach ($cart as $product_id => $item) {
        $pid = (int) $product_id;
        if (!isset($products_by_id[$pid])) {
            continue; // skip products that no longer exist / are inactive
        }
        $qty = max(1, (int) ($item['qty'] ?? 1));
        $available_stock = (int) $products_by_id[$pid]['stock'];

        if ($available_stock <= 0) {
            $stock_errors[] = $products_by_id[$pid]['title'] . ' is out of stock.';
            continue;
        }
        if ($qty > $available_stock) {
            $stock_errors[] = 'Only ' . $available_stock . ' left for ' . $products_by_id[$pid]['title'] . '.';
            continue;
        }

        $unit_price = (float) $products_by_id[$pid]['price'];

        $verified_cart[$pid] = [
            'title'      => $products_by_id[$pid]['title'],
            'price'      => $unit_price,
            'image'      => $products_by_id[$pid]['image_path'],
            'qty'        => $qty,
        ];
        $subtotal += $unit_price * $qty;
    }

    if (!empty($stock_errors)) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => implode(' ', $stock_errors)]);
        exit();
    }

    if (empty($verified_cart)) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'None of the items in your cart are available anymore.']);
        exit();
    }

    // Delivery charge rule: free delivery on orders RS 5000+, otherwise flat RS 250
    $delivery_charge = ($subtotal >= 5000) ? 0 : 250;
    $grand_total = $subtotal + $delivery_charge;

    // Every new order (COD included) starts as "pending" until an admin
    // reviews and confirms it from the Admin Orders panel.
    $order_status = 'pending';

    // Pull the logged-in user's account email automatically (Option A) —
    // no separate email field in the checkout form; we trust the
    // registered account email tied to this session.
    $user_stmt = $pdo->prepare("SELECT fullname, email FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();
    $user_email = $user['email'] ?? null;

    // -----------------------------------------------------------
    // 4) Insert order
    // -----------------------------------------------------------
    $stmt = $pdo->prepare(
        "INSERT INTO orders
            (user_id, total_amount, order_status, billing_name, billing_address,
             contact_number, email, payment_method, payment_reference, delivery_charge)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $user_id,
        $grand_total,
        $order_status,
        $billing_name,
        $billing_address,
        $contact_number,
        $user_email,
        $payment_method,
        $payment_ref !== '' ? $payment_ref : null,
        $delivery_charge
    ]);
    $order_id = $pdo->lastInsertId();

    $item_stmt = $pdo->prepare(
        "INSERT INTO order_items (order_id, product_id, title, price, qty, image_path)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    // Stock is reduced right away since the order row already exists
    // ('pending'); if the admin later rejects it, the reject action
    // restores the stock back (see admin_orders.php).
    $stock_update_stmt = $pdo->prepare(
        "UPDATE store_products SET stock = stock - ? WHERE id = ? AND stock >= ?"
    );

    $saved_items = [];
    foreach ($verified_cart as $product_id => $item) {
        $item_stmt->execute([
            $order_id,
            $product_id,
            $item['title'],
            $item['price'],
            $item['qty'],
            $item['image']
        ]);

        $stock_update_stmt->execute([$item['qty'], $product_id, $item['qty']]);
        if ($stock_update_stmt->rowCount() === 0) {
            // Someone else grabbed the remaining stock between our lock and now.
            throw new Exception('Sorry, ' . $item['title'] . ' just sold out. Please update your cart.');
        }

        $saved_items[] = [
            'title'    => $item['title'],
            'price'    => $item['price'],
            'qty'      => $item['qty'],
            'image'    => $item['image'] ? '../' . $item['image'] : '../images/banner.png',
            'subtotal' => $item['price'] * $item['qty']
        ];
    }

    $pdo->commit();

    // Let admins know a new order needs review (mirrors the payment-submitted notification).
    notify_admins(
        $pdo,
        'New order placed',
        ($user['fullname'] ?? 'A customer') . " placed a new order (#{$order_id}) worth Rs." . number_format($grand_total, 0) . ".",
        'admin/admin_orders.php',
        'fa-solid fa-cart-shopping'
    );

    echo json_encode([
        'status'            => 'success',
        'order_id'          => $order_id,
        'user_name'         => $user['fullname'] ?? 'Customer',
        'items'             => $saved_items,
        'subtotal'          => $subtotal,
        'delivery_charge'   => $delivery_charge,
        'total'             => $grand_total,
        'billing_name'      => $billing_name,
        'billing_address'   => $billing_address,
        'contact_number'    => $contact_number,
        'email'             => $user_email,
        'payment_method'    => $payment_method,
        'payment_reference' => $payment_ref,
        'order_status'      => $order_status,
        'order_date'        => date('d M Y, h:i A')
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>