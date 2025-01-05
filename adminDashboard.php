<?php
session_name('admin_session');
session_start();
include 'dbConnect.php';

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
    exit();
}

// Get admin name from session
$adminId = $_SESSION['admin_id'];
$query = "SELECT name FROM admins WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $adminId);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$adminName = htmlspecialchars($admin['name']);
$profilePicture = 'images/default.png'; // Set default profile picture

// Calculate total sales
$totalSalesQuery = "SELECT SUM(total) AS total_sales FROM orders WHERE order_status != 'canceled'";
$totalSalesStmt = $conn->prepare($totalSalesQuery);
$totalSalesStmt->execute();
$totalSalesResult = $totalSalesStmt->get_result();
$totalSales = $totalSalesResult->fetch_assoc()['total_sales'];

// Count new orders
$newOrdersQuery = "SELECT COUNT(*) AS new_orders FROM orders WHERE order_status = 'pending'";
$newOrdersStmt = $conn->prepare($newOrdersQuery);
$newOrdersStmt->execute();
$newOrdersResult = $newOrdersStmt->get_result();
$newOrders = $newOrdersResult->fetch_assoc()['new_orders'];

// Count number of products
$numberOfProductsQuery = "SELECT COUNT(*) AS number_of_products FROM products";
$numberOfProductsStmt = $conn->prepare($numberOfProductsQuery);
$numberOfProductsStmt->execute();
$numberOfProductsResult = $numberOfProductsStmt->get_result();
$numberOfProducts = $numberOfProductsResult->fetch_assoc()['number_of_products'];

// Count total number of customers
$numberOfCustomersQuery = "SELECT COUNT(*) AS number_of_customers FROM customers";
$numberOfCustomersStmt = $conn->prepare($numberOfCustomersQuery);
$numberOfCustomersStmt->execute();
$numberOfCustomersResult = $numberOfCustomersStmt->get_result();
$numberOfCustomers = $numberOfCustomersResult->fetch_assoc()['number_of_customers'];

// Determine the total number of low-stock products
$totalLowStockQuery = "SELECT COUNT(*) AS total FROM products WHERE stock < 15";
$totalLowStockStmt = $conn->prepare($totalLowStockQuery);
$totalLowStockStmt->execute();
$totalLowStockResult = $totalLowStockStmt->get_result();
$totalLowStock = $totalLowStockResult->fetch_assoc()['total'];

// Set the number of products per page
$productsPerPage = 10;
$totalPages = ceil($totalLowStock / $productsPerPage);

// Get the current page from the URL, default to 1 if not set
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, $currentPage); // Ensure the page is at least 1
$offset = ($currentPage - 1) * $productsPerPage;

// Query to get products with stock less than 15, limited to the current page
$lowStockQuery = "SELECT id, name, stock FROM products WHERE stock < 15 LIMIT ?, ?";
$lowStockStmt = $conn->prepare($lowStockQuery);
$lowStockStmt->bind_param('ii', $offset, $productsPerPage);
$lowStockStmt->execute();
$lowStockResult = $lowStockStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/adminDashboard.css">
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

        <main class="main-content">
            <h1>Welcome to the Admin Dashboard</h1>
            <p class="greeting">Hello, <?php echo htmlspecialchars($adminName); ?>! Here's a summary of your store's performance:</p>

            <!-- Display success or error message using JavaScript alert -->
            <script>
                <?php if (isset($_SESSION['message'])): ?>
                    alert("<?php echo addslashes($_SESSION['message']); ?>");
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    alert("<?php echo addslashes($_SESSION['error']); ?>");
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
            </script>

            <div class="dashboard-widgets">
                <div class="widget">
                    <h2>Total Sales</h2>
                    <p>₱<?php echo number_format($totalSales, 2); ?></p>
                </div>
                <div class="widget">
                    <h2>New Orders</h2>
                    <p><?php echo $newOrders; ?></p>
                </div>
                <div class="widget">
                    <h2>Products</h2>
                    <p><?php echo $numberOfProducts; ?></p>
                </div>
                <div class="widget">
                    <h2>Customers</h2>
                    <p><?php echo $numberOfCustomers; ?></p>
                </div>
            </div>
            <div class="quick-links">
                <h2>Low Stock Level</h2>
                <?php if ($lowStockResult->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th style="text-align: right;">Update Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $lowStockResult->fetch_assoc()): ?>
                                <tr <?php echo ($row['stock'] < 5) ? 'style="background-color: #ffcccc;"' : ''; ?>>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo $row['stock']; ?></td>
                                    <td>
                                        <?php if ($row['stock'] < 5): ?>
                                            <span style="color: red;">Critical</span>
                                        <?php elseif ($row['stock'] < 15): ?>
                                            <span style="color: orange;">Low</span>
                                        <?php else: ?>
                                            <span style="color: green;">Sufficient</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <form action="admin_updateStock.php" method="post">
                                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                            <input type="number" name="new_stock" min="0" placeholder="add Stock">
                                            <button type="submit">Update Stock</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                            <a href="?page=<?php echo $page; ?>" <?php if ($page == $currentPage) echo 'style="font-weight: bold;"'; ?>><?php echo $page; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php else: ?>
                    <p>All products are sufficiently stocked.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const hamburger = document.getElementById('hamburger');

            hamburger.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
            });
        });
    </script>
</body>
</html>