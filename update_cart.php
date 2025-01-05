<?php
session_start();
include 'dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo "User not logged in.";
    exit();
}

$customerId = $_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT);

    if ($productId === null || $quantity === null || $quantity <= 0) {
        echo "Invalid input.";
        exit();
    }

    // Fetch the current stock of the product
    $stockQuery = "SELECT stock FROM products WHERE id = ?";
    $stockStmt = $conn->prepare($stockQuery);
    if (!$stockStmt) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        exit();
    }
    $stockStmt->bind_param("i", $productId);
    $stockStmt->execute();
    $stockResult = $stockStmt->get_result();

    if ($stockResult->num_rows === 0) {
        echo "Product not found.";
        exit();
    }

    $stockRow = $stockResult->fetch_assoc();
    $availableStock = $stockRow['stock'];

    // Check if the requested quantity exceeds available stock
    if ($quantity > $availableStock) {
        echo "Cannot add more than available stock. Available stock: $availableStock.";
        exit();
    }

    // Check if the product is already in the cart
    $query = "SELECT quantity FROM cart WHERE customer_id = ? AND product_id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        exit();
    }
    $stmt->bind_param("ii", $customerId, $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $cartRow = $result->fetch_assoc();
        $currentCartQuantity = $cartRow['quantity'];

        // Check if the total quantity in the cart exceeds available stock
        if ($currentCartQuantity + $quantity > $availableStock) {
            echo "Cannot add more than available stock. Available stock: $availableStock.";
            exit();
        }

        // Update the quantity if the product is already in the cart
        $updateQuery = "UPDATE cart SET quantity = quantity + ? WHERE customer_id = ? AND product_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        if (!$updateStmt) {
            echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
            exit();
        }
        $updateStmt->bind_param("iii", $quantity, $customerId, $productId);
        if ($updateStmt->execute()) {
            echo "Cart updated successfully.";
        } else {
            echo "Failed to update cart: " . $updateStmt->error;
        }
        $updateStmt->close();
    } else {
        // Insert a new record if the product is not in the cart
        $insertQuery = "INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        if (!$insertStmt) {
            echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
            exit();
        }
        $insertStmt->bind_param("iii", $customerId, $productId, $quantity);
        if ($insertStmt->execute()) {
            echo "Product added to cart successfully.";
        } else {
            echo "Failed to add product to cart: " . $insertStmt->error;
        }
        $insertStmt->close();
    }

    $stmt->close();
    $stockStmt->close();
}

mysqli_close($conn);
?>