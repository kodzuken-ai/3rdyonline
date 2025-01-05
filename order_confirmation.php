<?php
session_start();
include 'dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

// Retrieve order details from session
if (!isset($_SESSION['order_details'])) {
    header("Location: customer_cart.php");
    exit();
}

$orderDetails = $_SESSION['order_details'];
$cartItems = $orderDetails['cart_items'];
$total = $orderDetails['total'];

// Add delivery fee
$deliveryFee = 50;
$grandTotal = $total + $deliveryFee;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm'])) {
        // Process the order
        $customerId = $orderDetails['customer_id'];
        $barangay = trim($_POST['barangay']);
        $purok = trim($_POST['purok']);
        $city = "Iligan City";
        $province = "Lanao del Norte";
        $postalCode = "9200";
        $paymentMethod = trim($_POST['payment_method']);

        // Validate payment method
        if (empty($paymentMethod)) {
            echo "Please select a payment method.";
            exit();
        }

        // Concatenate the full delivery address
        $deliveryAddress = $barangay . ', ' . $purok . ', ' . $city . ', ' . $province . ', ' . $postalCode;

        if ($paymentMethod === 'Cash on Delivery') {
            // Insert order into the orders table for COD
            $orderQuery = "INSERT INTO orders (customer_id, total, delivery_fee, delivery_address, payment_method, payment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $orderStmt = $conn->prepare($orderQuery);
            $paymentStatus = 'pending'; // Assuming a default payment status
            $orderStmt->bind_param("iidsss", $customerId, $total, $deliveryFee, $deliveryAddress, $paymentMethod, $paymentStatus);
            $orderStmt->execute();
            $orderId = $orderStmt->insert_id;

            // Insert order items into the order_items table
            $orderItemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $orderItemStmt = $conn->prepare($orderItemQuery);

            // Update stock and sales for each product
            $updateStockQuery = "UPDATE products SET stock = stock - ?, sales = sales + ? WHERE id = ?";
            $updateStockStmt = $conn->prepare($updateStockQuery);

            foreach ($cartItems as $item) {
                $orderItemStmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
                $orderItemStmt->execute();

                // Reduce stock and increase sales
                $updateStockStmt->bind_param("iii", $item['quantity'], $item['quantity'], $item['product_id']);
                $updateStockStmt->execute();
            }

            // Clear the cart for COD
            $clearCartQuery = "DELETE FROM cart WHERE customer_id = ?";
            $clearCartStmt = $conn->prepare($clearCartQuery);
            $clearCartStmt->bind_param("i", $customerId);
            $clearCartStmt->execute();

            // Clear session order details
            unset($_SESSION['order_details']);

            // Redirect to order confirmation page
            header("Location: receipt.php?order_id=" . $orderId);
            exit();
        } else {
            // For online payments, redirect to upload proof page
            $_SESSION['order_details']['delivery_address'] = $deliveryAddress;
            $_SESSION['order_details']['payment_method'] = $paymentMethod;
            header("Location: upload_proof.php");
            exit();
        }
    } elseif (isset($_POST['cancel'])) {
        // Cancel the order and redirect to cart
        unset($_SESSION['order_details']);
        header("Location: customer_cart.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="css/orderConfirmation.css">
</head>
<body>
<div class="order-container">
    <h1>Review Your Order</h1>
    <div class="order-header">
        <div>Item</div>
        <div>Quantity</div>
        <div>Price</div>
    </div>

    <?php foreach ($cartItems as $item): ?>
    <div class="order-item">
        <div><?php echo htmlspecialchars($item['name']); ?></div>
        <div><?php echo $item['quantity']; ?></div>
        <div>₱<?php echo number_format($item['price'], 2); ?></div>
    </div>
    <?php endforeach; ?>

    <div class="order-summary">
        <div>Subtotal: <strong>₱<?php echo number_format($total, 2); ?></strong></div>
        <div>Delivery Fee: <strong>₱<?php echo number_format($deliveryFee, 2); ?></strong></div>
        <div>Total Amount: <strong>₱<?php echo number_format($grandTotal, 2); ?></strong></div>
    </div>

    <form method="post" class="button-container">
        <div class="form-group">
            <label for="barangay">Barangay:</label>
            <input type="text" id="barangay" name="barangay" placeholder="Enter your barangay" required>
        </div>
        <div class="form-group">
            <label for="purok">Purok:</label>
            <input type="text" id="purok" name="purok" placeholder="Enter your purok" required>
        </div>
        <div class="form-group">
            <label for="city">City:</label>
            <input type="text" id="city" name="city" value="Iligan City" readonly>
        </div>
        <div class="form-group">
            <label for="province">Province:</label>
            <input type="text" id="province" name="province" value="Lanao del Norte" readonly>
        </div>
        <div class="form-group">
            <label for="postal_code">Postal Code:</label>
            <input type="text" id="postal_code" name="postal_code" value="9200" readonly>
        </div>
        <div class="form-group">
            <label for="payment_method">Payment Method:</label>
            <select id="payment_method" name="payment_method" required>
                <option value="" disabled selected>Select a payment method</option>
                <option value="GCash">GCash</option>
                <option value="PayPal">PayPal</option>
                <option value="Cash on Delivery">Cash on Delivery</option>
            </select>
        </div>
        <button type="submit" name="confirm" class="btn confirm-btn">Confirm Order</button>
        <button type="button" class="btn cancel-btn" onclick="cancelOrder()">Cancel Order</button>
    </form>
</div>

<script>
function cancelOrder() {
    // Create a form and submit it to cancel the order
    const form = document.createElement('form');
    form.method = 'post';
    form.style.display = 'none';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'cancel';
    input.value = '1';
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
}
</script>
</body>
</html>