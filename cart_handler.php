<?php

require_once __DIR__ . '/config.php';
requireCustomer();

header('Content-Type: application/json');

$customerId = intval($_SESSION['user_id']);
$action     = $_POST['action'] ?? '';

// Ensure cart table exists
$conn->query("CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart (customer_id, menu_item_id)
)");

function cartCount($conn, $customerId) {
    return (int)$conn->query("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE customer_id='$customerId'")->fetch_row()[0];
}

function cartTotal($conn, $customerId) {
    return (float)$conn->query("SELECT COALESCE(SUM(c.quantity * m.price),0) FROM cart c JOIN menu_items m ON c.menu_item_id=m.id WHERE c.customer_id='$customerId'")->fetch_row()[0];
}

if ($action === 'add') {
    $itemId = intval($_POST['item_id'] ?? 0);
    $qty    = max(1, intval($_POST['qty'] ?? 1));
    if (!$itemId) { echo json_encode(['ok'=>false,'msg'=>'Invalid item.']); exit; }

    // Check item exists and is available
    $item = $conn->query("SELECT id, restaurant_id, availability FROM menu_items WHERE id='$itemId'")->fetch_assoc();
    if (!$item || !$item['availability']) { echo json_encode(['ok'=>false,'msg'=>'Item not available.']); exit; }

    // Check cart doesn't mix restaurants
    $existingRest = $conn->query("SELECT DISTINCT m.restaurant_id FROM cart c JOIN menu_items m ON c.menu_item_id=m.id WHERE c.customer_id='$customerId'")->fetch_row();
    if ($existingRest && $existingRest[0] != $item['restaurant_id']) {
        echo json_encode(['ok'=>false,'msg'=>'Your cart has items from another restaurant. Clear cart first.','conflict'=>true]);
        exit;
    }

    $conn->query("INSERT INTO cart (customer_id, menu_item_id, quantity) VALUES ('$customerId','$itemId','$qty')
                  ON DUPLICATE KEY UPDATE quantity = quantity + '$qty'");

    echo json_encode(['ok'=>true,'msg'=>'Added to cart!','count'=>cartCount($conn,$customerId),'total'=>cartTotal($conn,$customerId)]);
    exit;
}

if ($action === 'update') {
    $cartId = intval($_POST['cart_id'] ?? 0);
    $qty    = intval($_POST['qty'] ?? 1);
    if ($qty < 1) {
        $conn->query("DELETE FROM cart WHERE id='$cartId' AND customer_id='$customerId'");
    } else {
        $conn->query("UPDATE cart SET quantity='$qty' WHERE id='$cartId' AND customer_id='$customerId'");
    }
    echo json_encode(['ok'=>true,'count'=>cartCount($conn,$customerId),'total'=>cartTotal($conn,$customerId)]);
    exit;
}


if ($action === 'remove') {
    $cartId = intval($_POST['cart_id'] ?? 0);
    $conn->query("DELETE FROM cart WHERE id='$cartId' AND customer_id='$customerId'");
    echo json_encode(['ok'=>true,'count'=>cartCount($conn,$customerId),'total'=>cartTotal($conn,$customerId)]);
    exit;
}

if ($action === 'clear') {
    $conn->query("DELETE FROM cart WHERE customer_id='$customerId'");
    echo json_encode(['ok'=>true,'count'=>0,'total'=>0]);
    exit;
}

echo json_encode(['ok'=>false,'msg'=>'Unknown action.']);