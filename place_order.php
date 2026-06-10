<?php
require_once 'config.php';
requireCustomer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

$customerId = intval($_SESSION['user_id']);
$notes      = trim($_POST['notes'] ?? '');

// Fetch cart items
$cartItems = $conn->query("
    SELECT c.quantity, m.id AS menu_item_id, m.price, m.restaurant_id, m.availability
    FROM cart c
    JOIN menu_items m ON c.menu_item_id = m.id
    WHERE c.customer_id = '$customerId'
");

$items  = [];
$total  = 0;
$restId = null;

while ($row = $cartItems->fetch_assoc()) {
    if (!$row['availability']) {
        $_SESSION['flash_error'] = 'One or more items are no longer available.';
        header('Location: cart.php');
        exit;
    }
    if ($restId && $restId !== $row['restaurant_id']) {
        $_SESSION['flash_error'] = 'Cart contains items from multiple restaurants.';
        header('Location: cart.php');
        exit;
    }
    $restId  = $row['restaurant_id'];
    $subtotal = $row['price'] * $row['quantity'];
    $total   += $subtotal;
    $items[] = $row;
}

if (empty($items)) {
    header('Location: cart.php');
    exit;
}

// Create order
$notesEsc = $conn->real_escape_string($notes);
$conn->query("
    INSERT INTO orders (customer_id, restaurant_id, total_price, notes, status, payment_status, created_at)
    VALUES ('$customerId', '$restId', '$total', '$notesEsc', 'pending', 'unpaid', NOW())
");
$orderId = $conn->insert_id;

// Insert order items
foreach ($items as $item) {
    $menuItemId = intval($item['menu_item_id']);
    $qty        = intval($item['quantity']);
    $price      = floatval($item['price']);
    $sub        = $price * $qty;
    $conn->query("
        INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, subtotal)
        VALUES ('$orderId', '$menuItemId', '$qty', '$price', '$sub')
    ");
}

$result = $conn->query("
    INSERT INTO order_status_log (order_id, old_status, new_status, changed_by, changed_at)
    VALUES ('$orderId', NULL, 'pending', '$customerId', NOW())
");
if (!$result) {
    die('Log error: ' . $conn->error);
}

if ($conn->error) {
    die("Order Status Log Error: " . $conn->error);
}

// Clear cart
$conn->query("DELETE FROM cart WHERE customer_id = '$customerId'");

// Redirect to payment
header("Location: payment.php?order_id=$orderId");
exit;
