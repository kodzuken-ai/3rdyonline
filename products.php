<?php
session_name('admin_session');
session_start();
include 'dbConnect.php'; // Ensure the database connection is included

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
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

// Number of products per page
$productsPerPage = 10;

// Get the current page number from the URL, default to 1 if not set
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Calculate the offset for the SQL query
$offset = ($page - 1) * $productsPerPage;

// Get the search query from the URL, if any
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Get the total number of products
$totalProductsQuery = "SELECT COUNT(*) as total FROM products WHERE name LIKE ?";
$stmt = $conn->prepare($totalProductsQuery);
$searchTerm = '%' . $searchQuery . '%';
$stmt->bind_param('s', $searchTerm);
$stmt->execute();
$totalProductsResult = $stmt->get_result();
$totalProductsRow = $totalProductsResult->fetch_assoc();
$totalProducts = $totalProductsRow['total'];

// Calculate the total number of pages
$totalPages = ceil($totalProducts / $productsPerPage);

// Fetch products for the current page
$query = "SELECT id, name, description, price, stock, image_url, availability FROM products WHERE name LIKE ? ORDER BY id ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('sii', $searchTerm, $productsPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/viewProducts.css">
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
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Admin</h2>
            <i class="fas fa-bars hamburger" id="hamburger" aria-label="Toggle Sidebar"></i>
        </div>
        <nav>
            <ul>
                <li><a href="adminDashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                <li><a href="products.php"><i class="fas fa-eye"></i><span>Manage Products</span></a></li>
                <li><a href="manageCategory.php"><i class="fas fa-plus"></i><span>Manage Category</span></a></li>
                <li><a href="manageOrder.php"><i class="fas fa-shopping-cart"></i><span>Manage Orders</span></a></li>
                <li><a href="sales.php"><i class="fas fa-chart-line"></i><span>Sales Report</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></li>
            </ul>
        </nav>
    </aside>

    <main class="main-content">
        <h1>Product Inventory</h1>
        <div class="add-product-container">
            <button class="button" onclick="window.location.href='addProduct.php';">Add Product</button>
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                <button type="submit" class="button">Search</button>
            </form>
        </div>
        <div class="container">
            <div class="product-table-container2">
                <table class="product-table2">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Image</th>
                            <th>Availability</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row["id"]; ?></td>
                                <td><?php echo htmlspecialchars($row["name"]); ?></td>
                                <td><?php echo htmlspecialchars($row["description"]); ?></td>
                                <td><?php echo number_format($row["price"], 2); ?></td>
                                <td><?php echo $row["stock"]; ?></td>
                                <td><img src="<?php echo htmlspecialchars($row["image_url"]); ?>" alt="Product Image" width="50"></td>
                                <td><?php echo $row["availability"] ? 'Available' : 'Unavailable'; ?></td>
                                <td>
                                    <button class="button" onclick="window.location.href='update.php?id=<?php echo $row['id']; ?>';">Update</button>
                                    <button class="button" onclick="if(confirm('Are you sure you want to delete this record?')) window.location.href='delete.php?id=<?php echo $row['id']; ?>';">Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Links -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchQuery); ?>">&laquo; Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchQuery); ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const hamburger = document.getElementById('hamburger');

        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    });
</script>
</body>
</html>