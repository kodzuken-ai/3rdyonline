<?php
session_name('admin_session');
session_start();
include 'dbConnect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch admin details from the session or database
$adminId = $_SESSION['admin_id'];
$query = "SELECT name FROM admins WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $adminId);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$adminName = $admin['name'];
$profilePicture = 'images/default.png'; // Set default profile picture

// Handle adding a new category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addCategory'])) {
    $categoryName = $_POST['category_name'];

    // Check if the category already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
    $stmt->bind_param("s", $categoryName);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $error = "This category already exists.";
    } else {
        // Insert the new category into the database
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $categoryName);

        if ($stmt->execute()) {
            $success = "Category added successfully.";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch categories and product counts
$categories = [];
$stmt = $conn->prepare("
    SELECT c.id, c.name, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON p.category_id = c.id 
    GROUP BY c.id, c.name
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/manageCategory.css">
</head>
<body>
    <header class="header">
        <div class="logo">
            <img src="images/logo.png" alt="3rdy Sari-Sari Store Logo" class="logo-img">
            <span class="company-name">3rdy Sari-Sari Store</span>
        </div>
        <div class="profile-menu">
            <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture" class="profile-img">
            <span><?php echo htmlspecialchars($adminName); ?></span>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Admin</h2>
                <i class="fas fa-bars hamburger" id="hamburger" aria-label="Toggle Sidebar"></i>
            </div>
            <ul>
                <li><a href="adminDashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                <li><a href="products.php"><i class="fas fa-eye"></i><span>Manage Products</span></a></li>
                <li><a href="manageCategory.php"><i class="fas fa-plus"></i><span>Manage Category</span></a></li>
                <li><a href="manageOrder.php"><i class="fas fa-shopping-cart"></i><span>Manage Orders</span></a></li>
                <li><a href="sales.php"><i class="fas fa-chart-line"></i><span>Sales Report</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="container">
                <h1>Manage Categories</h1>
                <form method="POST" action="">
                    <div class="input-group">
                        <label for="category_name">Category Name</label>
                        <input type="text" name="category_name" id="category_name" required>
                    </div>
                    <input type="submit" class="btn" value="Add Category" name="addCategory">
                </form>
                <?php if (isset($error)) { echo "<p class='error-message'>$error</p>"; } ?>
                <?php if (isset($success)) { echo "<p class='success-message'>$success</p>"; } ?>

                <!-- Display categories in a table -->
                <table>
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Product Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo $category['product_count']; ?></td>
                                <td>
                                    <button class="action-btn update-btn" onclick="updateCategory(<?php echo $category['id']; ?>)">Update</button>
                                    <button class="action-btn delete-btn" onclick="deleteCategory(<?php echo $category['id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const hamburger = document.getElementById('hamburger');

            hamburger.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
            });
        });

        function updateCategory(categoryId) {
            // Implement update functionality
            window.location.href = 'updateCategory.php?id=' + categoryId;
        }

        function deleteCategory(categoryId) {
            if (confirm('Are you sure you want to delete this category?')) {
                // Implement delete functionality
                window.location.href = 'deleteCategory.php?id=' + categoryId;
            }
        }
    </script>
</body>
</html>