<?php
session_start();
include 'dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

// Check if cart_id and action are set
if (!isset($_GET['cart_id']) || !isset($_GET['action'])) {
    header("Location: cart.php");
    exit();
}

$cartId = intval($_GET['cart_id']);
$action = $_GET['action'];

// Fetch the current quantity from the database
$query = "SELECT quantity FROM cart WHERE id = ? AND customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $cartId, $_SESSION['customer_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // If no such cart item exists, redirect back
    header("Location: customer_cart.php");
    exit();
}

$row = $result->fetch_assoc();
$currentQuantity = $row['quantity'];

// Determine the new quantity
if ($action === 'increase') {
    $newQuantity = $currentQuantity + 1;
} elseif ($action === 'decrease') {
    $newQuantity = max(1, $currentQuantity - 1); // Ensure quantity doesn't go below 1
} else {
    header("Location: customer_cart.php");
    exit();
}

// Update the quantity in the database
$updateQuery = "UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("iii", $newQuantity, $cartId, $_SESSION['customer_id']);
$updateStmt->execute();

// Redirect back to the cart page
header("Location: customer_cart.php");
exit();
?>