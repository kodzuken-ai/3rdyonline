<?php
session_name('admin_session');
session_start();
include 'dbConnect.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
    exit();
}

// Get the category ID from the URL
if (!isset($_GET['id'])) {
    header("Location: manageCategory.php");
    exit();
}

$categoryId = $_GET['id'];

// Fetch the current category details
$stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manageCategory.php");
    exit();
}

$category = $result->fetch_assoc();
$stmt->close();

// Handle the form submission for updating the category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updateCategory'])) {
    $newCategoryName = $_POST['category_name'];

    // Check if the new category name already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
    $stmt->bind_param("si", $newCategoryName, $categoryId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $error = "This category name already exists.";
    } else {
        // Update the category name in the database
        $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $newCategoryName, $categoryId);

        if ($stmt->execute()) {
            // Redirect to manageCategory.php after successful update
            header("Location: manageCategory.php");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Category</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/updateCategory.css">
</head>
<body>
    <header class="header">
        <div class="logo">
            <img src="images/logo.png" alt="3rdy Sari-Sari Store Logo" class="logo-img">
            <span class="company-name">3rdy Sari-Sari Store</span>
        </div>
    </header>
    <a href="manageCategory.php" class="back-arrow"><i class="fas fa-arrow-left"></i> Back to Categories</a>
    <div class="container">
        <h1>Update Category</h1>
        <form method="POST" action="">
            <div class="input-group">
                <label for="category_name">Category Name</label>
                <input type="text" name="category_name" id="category_name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
            </div>
            <input type="submit" class="btn" value="Update Category" name="updateCategory">
        </form>
        <?php if (isset($error)) { echo "<p class='error-message'>$error</p>"; } ?>
        <?php if (isset($success)) { echo "<p class='success-message'>$success</p>"; } ?>
    </div>
</body>
</html>