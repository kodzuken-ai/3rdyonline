<?php
session_start();
include 'dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo "User not logged in.";
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customerId = $_SESSION['customer_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);

    // Validate input data
    if (empty($name) || empty($email) || empty($address) || empty($phone)) {
        echo "All fields are required.";
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit();
    }

    // Handle file upload
    $profilePicPath = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['profile_pic']['tmp_name']);
        $maxFileSize = 2 * 1024 * 1024; // 2 MB

        if (!in_array($fileType, $allowedTypes)) {
            echo "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            exit();
        }

        if ($_FILES['profile_pic']['size'] > $maxFileSize) {
            echo "File size exceeds the 2MB limit.";
            exit();
        }

        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uniqueFileName = uniqid('profile_', true) . '.' . pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $profilePicPath = $uploadDir . $uniqueFileName;

        if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $profilePicPath)) {
            echo "Error uploading profile picture.";
            exit();
        }
    }

    // Prepare and execute the update query
    $query = "UPDATE customers SET name = ?, email = ?, address = ?, phone = ?";
    if ($profilePicPath) {
        $query .= ", profile_url = ?";
    }
    $query .= " WHERE id = ?";

    $stmt = $conn->prepare($query);
    if ($profilePicPath) {
        $stmt->bind_param("sssssi", $name, $email, $address, $phone, $profilePicPath, $customerId);
    } else {
        $stmt->bind_param("ssssi", $name, $email, $address, $phone, $customerId);
    }

    if ($stmt->execute()) {
        echo "Profile updated successfully!";
    } else {
        echo "Error updating profile: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>