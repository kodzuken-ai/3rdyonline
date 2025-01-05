<?php
session_start();
include 'dbConnect.php'; // Ensure this file connects to your database

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $username = filter_var(trim($_POST['username']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT id, name, password FROM customers WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $name, $hashed_password);
            $stmt->fetch();

            // Verify the password
            if (password_verify($password, $hashed_password)) {
                // Password is correct, start a session
                session_regenerate_id(true); // Regenerate session ID
                $_SESSION['customer_id'] = $id;
                $_SESSION['user_name'] = $name;
                echo 'success'; // Indicate success
            } else {
                echo "Invalid password.";
            }
        } else {
            echo "No user found with that email.";
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