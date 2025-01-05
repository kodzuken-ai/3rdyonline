<?php
session_start();
include 'dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

// Get the order ID from the URL
$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if (!$orderId) {
    echo "Invalid order ID.";
    exit();
}

// Retrieve order details from the database, including the delivery fee
$orderQuery = "SELECT o.*, c.name AS customer_name FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = ? AND o.customer_id = ?";
$orderStmt = $conn->prepare($orderQuery);
if (!$orderStmt) {
    echo "Failed to prepare statement: " . $conn->error;
    exit();
}
$orderStmt->bind_param("ii", $orderId, $_SESSION['customer_id']);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();

if ($orderResult->num_rows === 0) {
    echo "Order not found.";
    exit();
}

$order = $orderResult->fetch_assoc();

// Retrieve order items
$orderItemsQuery = "SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
$orderItemsStmt = $conn->prepare($orderItemsQuery);
if (!$orderItemsStmt) {
    echo "Failed to prepare statement: " . $conn->error;
    exit();
}
$orderItemsStmt->bind_param("i", $orderId);
$orderItemsStmt->execute();
$orderItemsResult = $orderItemsStmt->get_result();

// Use the delivery fee from the database
$deliveryFee = $order['delivery_fee'];
$grandTotal = $order['total'] + $deliveryFee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>
    <link rel="stylesheet" href="css/receipt.css">
</head>
<body>
<div class="receipt-container">
    <h1>3rdy Sari-Sari Store</h1>
    <div class="receipt-summary">
        <div>Customer:</div>
        <div colspan="2"><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></div>
    </div>
    <div class="receipt-header">
        <div>Item</div>
        <div>Qty</div>
        <div>Price</div>
    </div>

    <?php while ($item = $orderItemsResult->fetch_assoc()): ?>
    <div class="receipt-item">
        <div><?php echo htmlspecialchars($item['name']); ?></div>
        <div><?php echo $item['quantity']; ?></div>
        <div>₱<?php echo number_format($item['price'], 2); ?></div>
    </div>
    <?php endwhile; ?>

    <div class="receipt-summary">
        <div>Subtotal:</div>
        <div colspan="2"><strong>₱<?php echo number_format($order['total'], 2); ?></strong></div>
    </div>
    <div class="receipt-summary">
        <div>Delivery Fee:</div>
        <div colspan="2"><strong>₱<?php echo number_format($deliveryFee, 2); ?></strong></div>
    </div>
    <div class="receipt-summary">
        <div>Total Amount:</div>
        <div colspan="2"><strong>₱<?php echo number_format($grandTotal, 2); ?></strong></div>
    </div>
    <div class="receipt-summary">
        <div>Payment Method:</div>
        <div colspan="2"><strong><?php echo htmlspecialchars($order['payment_method']); ?></strong></div>
    </div>
    <div class="receipt-summary">
        <div>Delivery Address:</div>
        <div colspan="2"><strong><?php echo htmlspecialchars($order['delivery_address']); ?></strong></div>
    </div>
    <div class="receipt-summary">
        <div>Order Date:</div>
        <div colspan="2"><strong><?php echo htmlspecialchars($order['created_at']); ?></strong></div>
    </div>

    <div class="receipt-footer">
        Thank you for your purchase!
    </div>

    <div class="button-container">
        <a href="index.php" class="btn">Continue Shopping</a>
    </div>
</div>
</body>
</html>