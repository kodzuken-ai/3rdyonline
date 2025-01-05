<?php
session_start();
include 'dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

// Check if order details are available
if (!isset($_SESSION['order_details'])) {
    header("Location: customer_cart.php");
    exit();
}

$orderDetails = $_SESSION['order_details'];
$cartItems = $orderDetails['cart_items'];
$total = $orderDetails['total'];
$deliveryFee = $orderDetails['delivery_fee']; // Retrieve the delivery fee from session
$grandTotal = $total + $deliveryFee; // Calculate the grand total including delivery fee

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm'])) {
        // Confirm payment logic
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['payment_proof']['tmp_name'];
            $fileName = $_FILES['payment_proof']['name'];
            $fileSize = $_FILES['payment_proof']['size'];
            $fileType = $_FILES['payment_proof']['type'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];

            // Validate file type
            if (in_array($fileExtension, $allowedExtensions)) {
                $uploadDir = 'uploads/';
                $destPath = $uploadDir . uniqid() . '.' . $fileExtension;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    // Insert order into the orders table after payment proof is uploaded
                    $customerId = $orderDetails['customer_id'];
                    $deliveryAddress = $orderDetails['delivery_address'];
                    $paymentMethod = $orderDetails['payment_method'];
                    $paymentStatus = 'confirmed'; // Assuming payment is confirmed after proof upload

                    $orderQuery = "INSERT INTO orders (customer_id, total, delivery_fee, delivery_address, payment_method, payment_status, payment_proof, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                    $orderStmt = $conn->prepare($orderQuery);
                    $orderStmt->bind_param("iidssss", $customerId, $total, $deliveryFee, $deliveryAddress, $paymentMethod, $paymentStatus, $destPath);

                    if ($orderStmt->execute()) {
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

                        // Clear the cart
                        $clearCartQuery = "DELETE FROM cart WHERE customer_id = ?";
                        $clearCartStmt = $conn->prepare($clearCartQuery);
                        $clearCartStmt->bind_param("i", $customerId);
                        $clearCartStmt->execute();

                        // Clear session order details
                        unset($_SESSION['order_details']);

                        // Redirect to receipt page
                        header("Location: receipt.php?order_id=" . $orderId);
                        exit();
                    } else {
                        echo "<script>alert('Error processing order.');</script>";
                    }
                } else {
                    echo "<script>alert('Error uploading file.');</script>";
                }
            } else {
                echo "<script>alert('Invalid file type. Please upload a JPG, JPEG, PNG, or PDF file.');</script>";
            }
        } else {
            echo "<script>alert('Please upload a payment proof.');</script>";
        }
    } elseif (isset($_POST['cancel'])) {
        // Cancel order logic
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
    <title>Upload Payment Proof</title>
    <link rel="stylesheet" href="css/proof.css">
</head>
<body>
<div class="upload-container">
    <h1>Proof of Payment</h1>
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="payment_proof">Upload Proof of Payment:</label>
            <input type="file" id="payment_proof" name="payment_proof">
        </div>
        <button type="submit" name="confirm" class="btn confirm-btn">Confirm Payment</button>
        <button type="submit" name="cancel" class="btn cancel-btn">Cancel Order</button>
    </form>
</div>
</body>
</html>