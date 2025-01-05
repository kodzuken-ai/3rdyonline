<?php
session_start();
include 'dbConnect.php'; // Ensure this file connects to your database

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $name = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $address = filter_var(trim($_POST['address']), FILTER_SANITIZE_STRING);
    $phone = filter_var(trim($_POST['phone']), FILTER_SANITIZE_STRING);

    // Check if email is valid
    if (!$email) {
        echo "Invalid email address.";
        exit;
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare and execute the query
    $stmt = $conn->prepare("INSERT INTO customers (name, email, password, address, phone) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssss", $name, $email, $hashed_password, $address, $phone);

        if ($stmt->execute()) {
            echo 'success'; // Indicate success
        } else {
            // Handle execution error
            if ($conn->errno == 1062) {
                echo "Email already registered.";
            } else {
                error_log("Database error: " . $conn->error); // Log error
                echo "Database error. Please try again later.";
            }
        }

        $stmt->close();
    } else {
        // Handle statement preparation error
        error_log("Database error: " . $conn->error); // Log error
        echo "Database error. Please try again later.";
    }

    $conn->close();
}
?>