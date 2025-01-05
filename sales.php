<?php
session_name('admin_session');
session_start();
include 'dbConnect.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
    exit();
}

// Get admin name and profile picture from session
$adminId = $_SESSION['admin_id'];
$query = "SELECT name FROM admins WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $adminId);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$adminName = $admin['name'];
$profilePicture = 'images/default.png'; // Set default profile picture

// Fetch sales data from the database with pagination and sorting
function getSalesData($conn, $startDate = null, $endDate = null, $category = null, $search = null, $limit = 10, $offset = 0, $sortColumn = 'order_date', $sortOrder = 'DESC') {
    $sql = "SELECT orders.id as order_id, customers.name as customer_name, orders.total as total_amount, orders.created_at as order_date 
            FROM orders 
            JOIN customers ON orders.customer_id = customers.id
            JOIN order_items ON orders.id = order_items.order_id
            JOIN products ON order_items.product_id = products.id
            WHERE orders.order_status = 'completed'";

    $params = [];
    $types = '';

    if ($startDate && $endDate) {
        $sql .= " AND orders.created_at BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= 'ss';
    }

    if ($category) {
        $sql .= " AND products.category_id = ?";
        $params[] = $category;
        $types .= 'i';
    }

    if ($search) {
        $sql .= " AND (customers.name LIKE ? OR orders.id LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ss';
    }

    $sql .= " GROUP BY orders.id ORDER BY $sortColumn $sortOrder LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        echo "Error: " . $conn->error;
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Handle date, category, search filter, pagination, and sorting
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$category = isset($_GET['category']) ? $_GET['category'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'order_date';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';

$salesData = getSalesData($conn, $startDate, $endDate, $category, $search, $limit, $offset, $sortColumn, $sortOrder);

// Fetch categories for the filter
$categoryResult = $conn->query("SELECT id, name FROM categories");
$categories = $categoryResult->fetch_all(MYSQLI_ASSOC);

// Calculate total sales
$totalSales = array_reduce($salesData, function($carry, $item) {
    return $carry + $item['total_amount'];
}, 0);

// Check if there are more pages
$nextPageData = getSalesData($conn, $startDate, $endDate, $category, $search, $limit, $offset + $limit, $sortColumn, $sortOrder);
$hasNextPage = !empty($nextPageData);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/sales.css">
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
            <h1>Sales Report</h1>
            <form method="GET" class="filter-form">
                <div>
                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                <div>
                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                <div>
                    <label for="category">Category:</label>
                    <select name="category" id="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="search">Search:</label>
                    <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by Customer or Order ID">
                </div>
                <div>
                    <button type="submit" class="btn">Filter</button>
                    <button type="button" class="btn" onclick="clearFilters()">Clear</button>
                    <button type="button" class="btn" onclick="showAll()">All</button>
                </div>
            </form>
            <?php if (!empty($salesData)): ?>
                <table>
                    <thead>
                        <tr>
                            <th><a href="?sort=order_id&order=<?php echo $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">Order ID</a></th>
                            <th><a href="?sort=customer_name&order=<?php echo $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">Customer Name</a></th>
                            <th><a href="?sort=total_amount&order=<?php echo $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">Total Amount</a></th>
                            <th><a href="?sort=order_date&order=<?php echo $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">Order Date</a></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesData as $sale): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sale['order_id']); ?></td>
                                <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($sale['total_amount']); ?></td>
                                <td><?php echo htmlspecialchars($sale['order_date']); ?></td>
                                <td><button class="btn-view" onclick="viewDetails(<?php echo $sale['order_id']; ?>)">View Details</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="total-sales">Total Revenue: <?php echo number_format($totalSales, 2); ?></div>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sortColumn); ?>&order=<?php echo urlencode($sortOrder); ?>">Previous</a>
                    <?php endif; ?>
                    <span>Page <?php echo $page; ?></span>
                    <?php if ($hasNextPage): ?>
                        <a href="?page=<?php echo $page + 1; ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sortColumn); ?>&order=<?php echo urlencode($sortOrder); ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p>No sales data found for the selected criteria.</p>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal for viewing order details -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Order Details</h2>
            <div id="orderDetailsContent">
                <!-- Order details will be loaded here -->
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

        function viewDetails(orderId) {
            // Fetch order details using AJAX
            fetch('order_details.php?order_id=' + orderId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('orderDetailsContent').innerHTML = data;
                    document.getElementById('orderModal').style.display = 'block';
                })
                .catch(error => console.error('Error fetching order details:', error));
        }

        function closeModal() {
            document.getElementById('orderModal').style.display = 'none';
        }

        // Close the modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == document.getElementById('orderModal')) {
                closeModal();
            }
        }

        function clearFilters() {
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
            document.getElementById('category').selectedIndex = 0;
            document.getElementById('search').value = '';
        }

        function showAll() {
            clearFilters();
            document.querySelector('.filter-form').submit();
        }
    </script>
</body>
</html>