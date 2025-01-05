<?php
session_start();
include 'dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

$customerId = $_SESSION['customer_id'];
$isLoggedIn = isset($_SESSION['customer_id']);

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];

    if ($status === 'cancelled') {
        $cancelQuery = "UPDATE orders SET order_status = ? WHERE id = ? AND customer_id = ? AND order_status = 'pending'";
        $stmt = $conn->prepare($cancelQuery);
        if ($stmt) {
            $stmt->bind_param("sii", $status, $orderId, $customerId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Fetch profile URL
$profileUrl = 'images/default.png';
$profileQuery = "SELECT profile_url FROM customers WHERE id = ?";
$stmt = $conn->prepare($profileQuery);
if ($stmt) {
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $profileResult = $stmt->get_result();
    $profileRow = $profileResult->fetch_assoc();
    if (!empty($profileRow['profile_url'])) {
        $profileUrl = $profileRow['profile_url'];
    }
    $stmt->close();
}

// Fetch total distinct items in the cart
$totalItems = 0;
if ($isLoggedIn) {
    $cartQuery = "SELECT COUNT(DISTINCT product_id) as total_items FROM cart WHERE customer_id = ?";
    $stmt = $conn->prepare($cartQuery);
    if ($stmt) {
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalItems = $row['total_items'] ?? 0;
        }
        $stmt->close();
    }
}

// Handle order status filter and search
$orderStatus = isset($_GET['status']) ? $_GET['status'] : 'All';
$searchTerm = isset($_GET['order_search']) ? $_GET['order_search'] : '';

// Build the query based on the filter and search
$query = "SELECT id AS order_id, created_at AS order_date, order_status AS status, total AS total_amount FROM orders WHERE customer_id = ?";
$params = [$customerId];
$types = "i";

if ($orderStatus !== 'All') {
    $query .= " AND order_status = ?";
    $params[] = $orderStatus;
    $types .= "s";
}

if ($searchTerm) {
    $query .= " AND id LIKE ?";
    $params[] = '%' . $searchTerm . '%';
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/order.css">
</head>
<body>
<header>
    <div class="logo" onclick="window.location.href='index.php'">
        <img src="images/Logo.png" alt="Logo">
        <span style="font-size: 1.5rem; font-weight: bold;">3rdy Sari-Sari Store</span>
    </div>
    <nav class="main-nav">
        <a href="index.php" class="nav-button">Home</a>
        <a href="category.php" class="nav-button">Category</a>
    </nav>
    <div class="nav-search">
        <form method="GET" action="category.php">
            <input type="text" class="search-bar" id="search" name="search" placeholder="Search...">
        </form>
    </div>
    <div class="cart">
        <a href="customer_cart.php" class="cart-icon" id="cartIcon">
            <i class="fas fa-shopping-cart"></i> <span id="cart-count"><?php echo htmlspecialchars($totalItems); ?></span>
        </a>
    </div>
    <div class="auth-links">
        <?php if ($isLoggedIn): ?>
            <div class="profile-dropdown">
                <img src="<?php echo htmlspecialchars($profileUrl); ?>" alt="Profile" class="profile-pic" onclick="toggleDropdown()">
                <div id="dropdownMenu" class="dropdown-content" style="display: none;">
                    <a href="customer_profile.php">Profile</a>
                    <a href="orders.php">Orders</a>
                    <a href="customer_logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="#" id="signInLink">Sign In</a> | <a href="#" id="signUpLink">Sign Up</a>
        <?php endif; ?>
    </div>
</header>
<div class="container">
    <h1>My Orders</h1>
    <div class="order-controls">
        <div class="order-filters">
            <a href="?status=All" class="filter-button">All</a>
            <a href="?status=Pending" class="filter-button">Pending</a>
            <a href="?status=Delivered" class="filter-button">Delivered</a>
            <a href="?status=Completed" class="filter-button">Completed</a>
            <a href="?status=Cancelled" class="filter-button">Cancelled</a>
        </div>
        <div class="order-search">
            <form method="GET" action="">
                <input type="text" name="order_search" placeholder="Search Orders..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button type="submit">Search</button>
            </form>
        </div>
    </div>
    <?php if (count($orders) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                        <td><?php echo htmlspecialchars($order['total_amount']); ?></td>
                        <td>
                            <a href="viewOrder.php?id=<?php echo urlencode($order['order_id']); ?>" class="view-order-link">View</a>
                            <?php if ($order['status'] === 'pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" class="cancel-button" onclick="return confirm('Are you sure you want to cancel this order?');">Cancel</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No orders found.</p>
    <?php endif; ?>
</div>
<script>
    function toggleDropdown() {
        const dropdown = document.getElementById('dropdownMenu');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }

    window.onclick = function(event) {
        if (!event.target.matches('.profile-pic')) {
            const dropdowns = document.getElementsByClassName('dropdown-content');
            for (let i = 0; i < dropdowns.length; i++) {
                const openDropdown = dropdowns[i];
                if (openDropdown.style.display === 'block') {
                    openDropdown.style.display = 'none';
                }
            }
        }
    }

    // Clear query parameters on page load
    window.onload = function() {
        if (window.location.search) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }
</script>
</body>
</html>