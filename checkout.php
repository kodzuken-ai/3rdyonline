<?php
session_start();
include 'dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

$customerId = $_SESSION['customer_id'];

// Fetch cart items to create an order
$query = "SELECT cart.id as cart_id, products.id as product_id, products.name, products.price, cart.quantity 
          FROM cart 
          JOIN products ON cart.product_id = products.id 
          WHERE cart.customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $total += $row['price'] * $row['quantity'];
}

// Check if cart is empty
if (empty($cartItems)) {
    header("Location: customer_cart.php");
    exit();
}

// Add delivery fee
$deliveryFee = 50;
$grandTotal = $total + $deliveryFee;

// Check if the total is less than 100
if ($grandTotal < 100) {
    $_SESSION['error_message'] = "The total amount must be at least 100 to proceed to checkout.";
    header("Location: customer_cart.php");
    exit();
}

// Store order details in session for confirmation
$_SESSION['order_details'] = [
    'customer_id' => $customerId,
    'total' => $total,
    'delivery_fee' => $deliveryFee,
    'grand_total' => $grandTotal,
    'cart_items' => $cartItems
];

// Redirect to order confirmation page
header("Location: order_confirmation.php");
exit();
?>