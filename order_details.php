<?php
// order_details.php
include 'dbConnect.php';

if (!isset($_GET['order_id'])) {
    echo "No order ID provided";
    exit;
}

$orderId = (int)$_GET['order_id'];

// Get order details
$orderQuery = "
    SELECT 
        o.id as order_id,
        o.created_at as order_date,
        c.name as customer_name,
        c.phone as customer_phone,
        o.total as total_amount,
        o.payment_method,
        o.order_status
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ?
";

$stmt = $conn->prepare($orderQuery);
if (!$stmt) {
    echo "Error preparing statement: " . $conn->error;
    exit;
}
$stmt->bind_param("i", $orderId);
if (!$stmt->execute()) {
    echo "Error executing query: " . $stmt->error;
    exit;
}
$orderResult = $stmt->get_result();
$orderDetails = $orderResult->fetch_assoc();

if (!$orderDetails) {
    echo "No order details found for this order ID.";
    exit;
}

// Get order items
$itemsQuery = "
    SELECT 
        p.name as product_name,
        p.price as unit_price,
        oi.quantity,
        (oi.quantity * p.price) as subtotal
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
";

$stmt = $conn->prepare($itemsQuery);
if (!$stmt) {
    echo "Error preparing statement: " . $conn->error;
    exit;
}
$stmt->bind_param("i", $orderId);
if (!$stmt->execute()) {
    echo "Error executing query: " . $stmt->error;
    exit;
}
$itemsResult = $stmt->get_result();
$orderItems = $itemsResult->fetch_all(MYSQLI_ASSOC);

if (empty($orderItems)) {
    echo "No products found for this order.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/orderDetails.css">
</head>
<body>
<div class="order-details">
    <div class="order-header">
        <div class="order-info">
            <h3>Order #<?php echo htmlspecialchars($orderDetails['order_id']); ?></h3>
            <p>Date: <?php echo date('F j, Y g:i A', strtotime($orderDetails['order_date'])); ?></p>
            <p>Status: <span class="status-badge <?php echo strtolower($orderDetails['order_status']); ?>">
                <?php echo htmlspecialchars($orderDetails['order_status']); ?>
            </span></p>
        </div>
        <div class="customer-info">
            <h4>Customer Information</h4>
            <p>Name: <?php echo htmlspecialchars($orderDetails['customer_name']); ?></p>
            <p>Phone: <?php echo htmlspecialchars($orderDetails['customer_phone']); ?></p>
        </div>
    </div>

    <div class="order-items">
        <h4>Order Items</h4>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Unit Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="total-label">Total Amount</td>
                    <td class="total-amount">₱<?php echo number_format($orderDetails['total_amount'], 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="payment-info">
        <h4>Payment Information</h4>
        <p>Payment Method: <?php echo htmlspecialchars($orderDetails['payment_method']); ?></p>
    </div>

    <!-- Back button to navigate to order.php -->
    
</div>


</body>
