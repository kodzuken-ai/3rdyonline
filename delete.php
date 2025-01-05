<?php
include('dbConnect.php'); // Ensure this file establishes a $conn connection

// Check if an 'id' parameter is passed in the URL
if (isset($_GET['id'])) {
    $product_id = (int) $_GET['id']; // Cast to integer for security

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Delete dependent rows in order_items
        $stmt = $conn->prepare("DELETE FROM order_items WHERE product_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $stmt->close();
        }

        // Now delete the product
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $stmt->close();
        }

        // Commit the transaction
        $conn->commit();

        echo "<script>alert('Product and related items deleted successfully!');</script>";
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        echo "<script>alert('Error deleting product: " . htmlspecialchars($e->getMessage()) . "');</script>";
    }

    // Redirect to products list
    echo "<script>window.location.href='products.php';</script>";
}

// Close the database connection
$conn->close();
?>