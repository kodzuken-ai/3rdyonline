<?php
session_name('admin_session');
session_start();
include 'dbConnect.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
    exit();
}

// Check if the category ID is provided in the URL
if (!isset($_GET['id'])) {
    header("Location: manageCategory.php");
    exit();
}

$categoryId = $_GET['id'];

// Prepare and execute the SQL statement to delete the category
$stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
$stmt->bind_param("i", $categoryId);

if ($stmt->execute()) {
    // Redirect to the manageCategory page with a success message
    $_SESSION['success_message'] = "Category deleted successfully.";
} else {
    // Redirect to the manageCategory page with an error message
    $_SESSION['error_message'] = "Error deleting category: " . $stmt->error;
}

$stmt->close();
$conn->close();

header("Location: manageCategory.php");
exit();