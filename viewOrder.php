<?php
require_once 'dbConnect.php';  // Ensure this file initializes $conn

// Check if order ID is provided
if (!isset($_GET['id'])) {
    echo "Order ID is missing.";
    exit;
}

$orderId = $_GET['id'];

// Fetch order details including delivery fee and payment proof
$sql = "SELECT orders.id as order_id, customers.name as customer_name, orders.created_at as order_date, 
        orders.order_status as status, orders.total, orders.delivery_address, orders.payment_method, 
        orders.delivery_fee, orders.payment_proof
        FROM orders
        JOIN customers ON orders.customer_id = customers.id
        WHERE orders.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $orderId);
$stmt->execute();
$orderResult = $stmt->get_result(); 

if ($orderResult->num_rows === 0) {
    echo "Order not found.";
    exit;
}

$order = $orderResult->fetch_assoc();

// Fetch order items
$sqlItems = "SELECT products.name, order_items.quantity, order_items.price
             FROM order_items
             JOIN products ON order_items.product_id = products.id
             WHERE order_items.order_id = ?";
$stmtItems = $conn->prepare($sqlItems);
$stmtItems->bind_param('i', $orderId);
$stmtItems->execute();
$itemsResult = $stmtItems->get_result();

// Calculate subtotal
$subtotal = 0;
$items = [];
while ($item = $itemsResult->fetch_assoc()) {
    $subtotal += $item['quantity'] * $item['price'];
    $items[] = $item;
}

// Calculate grand total
$grandTotal = $subtotal + $order['delivery_fee'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link rel="stylesheet" href="css/viewOrder.css">

    </style>
</head>
<body>
<div class="container">
    <h1>Order Details</h1>
    <div class="section">
        <div class="details-row">
            <p><strong>Order #<?php echo htmlspecialchars($order['order_id']); ?></strong></p>
            <p><strong>Status:</strong> <span class="status-badge"><?php echo htmlspecialchars($order['status']); ?></span></p>
        </div>
        <div class="details-row">
            <p><strong>Date:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
            <p><strong>Customer Information:</strong></p>
        </div>
        <div class="details-row">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
        </div>
    </div>

    <div class="section">
        <h2>Order Items</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Unit Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td>₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="total-row">Total Amount:</td>
                    <td class="total-row">₱<?php echo number_format($grandTotal, 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="section">   
        <h2>Payment Information</h2>
        <p class="payment-info"><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
        <?php if (in_array($order['payment_method'], ['GCash', 'PayPal']) && !empty($order['payment_proof'])): ?>
            <p class="payment-info"><strong>Payment Proof:</strong></p>
            <img src="<?php echo htmlspecialchars($order['payment_proof']); ?>" alt="Payment Proof" class="payment-proof">
        <?php endif; ?>
    </div>

    <div class="section">
    <button onclick="window.location.href='orders.php'" style="padding: 0.5rem 1rem; background-color: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer;">
        Back to Orders
    </button>
</div>
</div>
</body>
</html>
