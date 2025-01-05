<?php
session_start();
include 'dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

// Check if cart_id is set
if (!isset($_GET['cart_id'])) {
    header("Location: customer_cart.php");
    exit();
}

$cartId = intval($_GET['cart_id']);

// Remove the item from the cart
$removeQuery = "DELETE FROM cart WHERE id = ? AND customer_id = ?";
$removeStmt = $conn->prepare($removeQuery);
$removeStmt->bind_param("ii", $cartId, $_SESSION['customer_id']);
$removeStmt->execute();

// Redirect back to the cart page
header("Location: customer_cart.php");
exit();
?>