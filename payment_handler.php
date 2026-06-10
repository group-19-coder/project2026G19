<?php
require_once __DIR__ . '/config.php';
requireCustomer();

header('Content-Type: application/json');

$customerId = intval($_SESSION['user_id']);
$orderId    = intval($_POST['order_id'] ?? 0);
$method     = $_POST['method'] ?? '';

$allowed = ['fpx','card','ewallet','cash'];
if (!$orderId || !in_array($method, $allowed)) {
    echo json_encode(['ok'=>false,'msg'=>'Invalid request.']);
    exit;
}

// Ensure columns exist
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'unpaid'");

// Verify order belongs to this customer and is still unpaid
$order = $conn->query("
    SELECT id, status, payment_status, total_price
    FROM orders
    WHERE id = '$orderId' AND customer_id = '$customerId'
")->fetch_assoc();

if (!$order) {
    echo json_encode(['ok'=>false,'msg'=>'Order not found.']);
    exit;
}

if ($order['payment_status'] === 'paid') {
    echo json_encode(['ok'=>true,'msg'=>'Already paid.']);
    exit;
}

// Build detail string
$detail = '';
if ($method === 'fpx') {
    $bank   = $conn->real_escape_string($_POST['bank'] ?? '');
    $detail = 'fpx_' . $bank;
} elseif ($method === 'ewallet') {
    $wallet = $conn->real_escape_string($_POST['wallet'] ?? '');
    $detail = 'ewallet_' . $wallet;
} else {
    $detail = $method;
}
$detailEsc = $conn->real_escape_string($detail);

// For cash: mark pending; for online: mark paid + confirm order
if ($method === 'cash') {
    $payStatus  = 'unpaid';   // pay on pickup
    $orderStatus = 'pending';
} else {
    $payStatus  = 'paid';
    $orderStatus = 'confirmed';
}

$conn->query("
    UPDATE orders
    SET payment_method = '$detailEsc',
        payment_status = '$payStatus',
        status         = '$orderStatus'
    WHERE id = '$orderId' AND customer_id = '$customerId'
");

// Log status change
if ($orderStatus !== $order['status']) {
    $oldStatus = $conn->real_escape_string($order['status']);
    $conn->query("
        INSERT INTO order_status_log (order_id, old_status, new_status, changed_by, changed_at)
        VALUES ('$orderId', '$oldStatus', '$orderStatus', '$customerId', NOW())
    ");
}

echo json_encode(['ok'=>true,'msg'=>'Payment recorded.','payment_status'=>$payStatus]);
exit;