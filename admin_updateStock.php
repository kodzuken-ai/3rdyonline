<?php
session_start();
include 'dbConnect.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the product ID and additional stock value from the form
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $additionalStock = isset($_POST['new_stock']) ? (int)$_POST['new_stock'] : null;

    // Validate inputs
    if ($productId > 0 && $additionalStock !== null && $additionalStock >= 0) {
        // Retrieve the current stock value
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $stmt->bind_result($currentStock);
        $stmt->fetch();
        $stmt->close();

        // Calculate the new stock value
        $newStock = $currentStock + $additionalStock;

        // Prepare the SQL statement to update the stock
        $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt->bind_param("ii", $newStock, $productId);

        // Execute the statement
        if ($stmt->execute()) {
            // Redirect back to the dashboard with a success message
            $_SESSION['message'] = "Stock added successfully.";
            header("Location: adminDashboard.php");
            exit();
        } else {
            // Handle execution error
            $_SESSION['error'] = "Failed to add stock: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        // Handle validation error
        $_SESSION['error'] = "Invalid product ID or stock value.";
    }
}

// Redirect back to the dashboard if the request method is not POST
header("Location: adminDashboard.php");
exit();
?>