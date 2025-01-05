<?php
session_start();
include 'dbConnect.php'; // Ensure this file connects to your database

$totalItems = 0;
if (isset($_SESSION['customer_id'])) {
    $customerId = $_SESSION['customer_id'];
    $totalItemsQuery = "SELECT COUNT(DISTINCT product_id) as totalItems FROM cart WHERE customer_id = ?";
    $stmt = $conn->prepare($totalItemsQuery);
    if ($stmt) {
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $totalItemsResult = $stmt->get_result();
        $totalItemsRow = $totalItemsResult->fetch_assoc();
        $totalItems = $totalItemsRow['totalItems'] ?? 0;
        $stmt->close();
    }
}

echo $totalItems;
?>