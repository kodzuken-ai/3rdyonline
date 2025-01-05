<?php
session_name('admin_session');
session_start();
include 'dbConnect.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
    exit();
}

// Get admin name from session
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
// Fetch orders from the database with filtering
function getOrders($conn, $search = '', $statusFilter = '', $limit = 10, $offset = 0) {
    $sql = "SELECT orders.id as order_id, customers.name as customer_name, orders.created_at as order_date, orders.order_status as status 
            FROM orders 
            JOIN customers ON orders.customer_id = customers.id
            WHERE customers.name LIKE ?";

    if ($statusFilter) {
        $sql .= " AND orders.order_status = ?";
    }

    $sql .= " LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $searchTerm = '%' . $search . '%';

    if ($statusFilter) {
        $stmt->bind_param('ssii', $searchTerm, $statusFilter, $limit, $offset);
    } else {
        $stmt->bind_param('sii', $searchTerm, $limit, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        echo "Error: " . $conn->error;
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];

    $updateSql = "UPDATE orders SET order_status = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param('si', $status, $orderId);

    if ($updateStmt->execute()) {
        echo "Order status updated successfully.";
    } else {
        echo "Error updating order status: " . $conn->error;
    }
    exit();
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'pending'; // Default to 'pending'
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$orders = getOrders($conn, $search, $statusFilter, $limit, $offset);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/manageOrder.css">
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
            <h1>Manage Orders</h1>
            <div class="search-filters-container">
                <div class="filters">
                    <a href="?status=pending&search=<?php echo urlencode($search); ?>" class="filter-btn">Pending</a>
                    <a href="?status=delivered&search=<?php echo urlencode($search); ?>" class="filter-btn">Delivered</a>
                    <a href="?status=completed&search=<?php echo urlencode($search); ?>" class="filter-btn">Completed</a>
                    <a href="?status=cancelled&search=<?php echo urlencode($search); ?>" class="filter-btn">Cancelled</a>
                </div>
                <form method="GET" class="search-bar">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by customer name">
                    <button type="submit">Search</button>
                </form>
            </div>

            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return false;">
                                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                        <select name="status" onchange="updateStatus(this)">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <button onclick="viewDetails(<?php echo htmlspecialchars($order['order_id']); ?>)" class="btn-view">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">Previous</a>
                <?php endif; ?>
                <span>Page <?php echo $page; ?></span>
                <?php if (count($orders) == $limit): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">Next</a>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Order Details</h2>
            <div id="orderDetailsContent">

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

        function updateStatus(selectElement) {
            const form = selectElement.closest('form');
            const orderId = form.querySelector('input[name="order_id"]').value;
            const status = selectElement.value;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    alert(xhr.responseText);
                }
            };
            xhr.send('order_id=' + encodeURIComponent(orderId) + '&status=' + encodeURIComponent(status));
        }

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

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('orderModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>