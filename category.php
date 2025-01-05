<?php
session_start();
include 'dbConnect.php'; // Ensure this file connects to your database

// Check if the connection was successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch categories for the sidebar
$categoriesQuery = "SELECT * FROM categories";
$categoriesResult = mysqli_query($conn, $categoriesQuery);
$categories = [];
if ($categoriesResult && mysqli_num_rows($categoriesResult) > 0) {
    while ($row = mysqli_fetch_assoc($categoriesResult)) {
        $categories[] = $row;
    }
} else {
    echo "Error fetching categories: " . mysqli_error($conn);
}

// Pagination setup
$limit = 25; // Number of products per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Initialize categoryId
$categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;

// Handle search query
$searchQuery = '';
$products = [];
$totalProducts = 0;

if (isset($_GET['search'])) {
    $searchQuery = $_GET['search'];
    $stmt = $conn->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM products WHERE availability = 1 AND (name LIKE ? OR description LIKE ?) LIMIT ? OFFSET ?");
    if ($stmt) {
        $searchTerm = '%' . $searchQuery . '%';
        $stmt->bind_param("ssii", $searchTerm, $searchTerm, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();

        // Get total number of products for pagination
        $totalResult = $conn->query("SELECT FOUND_ROWS() as total");
        $totalRow = $totalResult->fetch_assoc();
        $totalProducts = $totalRow['total'];
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
} else {
    // Fetch products for the selected category
    if ($categoryId) {
        $stmt = $conn->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM products WHERE availability = 1 AND category_id = ? LIMIT ? OFFSET ?");
        if ($stmt) {
            $stmt->bind_param("iii", $categoryId, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            $stmt->close();

            // Get total number of products for pagination
            $totalResult = $conn->query("SELECT FOUND_ROWS() as total");
            $totalRow = $totalResult->fetch_assoc();
            $totalProducts = $totalRow['total'];
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    } else {
        $result = mysqli_query($conn, "SELECT SQL_CALC_FOUND_ROWS * FROM products WHERE availability = 1 LIMIT $limit OFFSET $offset");
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $products[] = $row;
            }

            // Get total number of products for pagination
            $totalResult = $conn->query("SELECT FOUND_ROWS() as total");
            $totalRow = $totalResult->fetch_assoc();
            $totalProducts = $totalRow['total'];
        } else {
            echo "Error fetching products: " . mysqli_error($conn);
        }
    }
}

$isLoggedIn = isset($_SESSION['customer_id']);
$profileUrl = 'images/default.png'; // Default profile image

// Fetch total distinct items in cart for the logged-in user
$totalItems = 0;
if ($isLoggedIn) {
    $customerId = $_SESSION['customer_id'];
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/category.css">
    <style>

    </style>
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
        <a href="#" class="cart-icon" id="cartIcon">
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
<div class="category-page"> 
    <aside class="category-sidebar">
        <h2>Categories</h2>
        <ul>
            <li>
                <a href="category.php">All</a>
            </li>
            <?php foreach ($categories as $category): ?>
                <li>
                    <a href="category.php?category_id=<?php echo htmlspecialchars($category['id']); ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>
    <div class="products" id="product-list">
        <?php foreach ($products as $product): ?>
            <div class="product" data-id="<?php echo htmlspecialchars($product['id']); ?>" 
                 data-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>" 
                 data-description="<?php echo htmlspecialchars(strtolower($product['description'])); ?>"
                 data-stock="<?php echo htmlspecialchars($product['stock']); ?>">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                <p><?php echo htmlspecialchars($product['description']); ?></p>
                <p>Price: <?php echo htmlspecialchars($product['price']); ?></p>
                <a href="#" class="view-product">View Product</a>
            </div>
        <?php endforeach; ?>
    </div>

   
</div>

 <!-- Pagination Links -->
 <div class="pagination">
        <?php
        $totalPages = ceil($totalProducts / $limit);

        // Previous Page Link
        if ($page > 1) {
            $prevPage = $page - 1;
            echo '<a href="?page=' . $prevPage . '&search=' . urlencode($searchQuery) . '&category_id=' . $categoryId . '">&laquo; Previous</a>';
        }

        // Page Number Links
        for ($i = 1; $i <= $totalPages; $i++) {
            echo '<a href="?page=' . $i . '&search=' . urlencode($searchQuery) . '&category_id=' . $categoryId . '" class="' . ($i === $page ? 'active' : '') . '">' . $i . '</a>';
        }

        // Next Page Link
        if ($page < $totalPages) {
            $nextPage = $page + 1;
            echo '<a href="?page=' . $nextPage . '&search=' . urlencode($searchQuery) . '&category_id=' . $categoryId . '">Next &raquo;</a>';
        }
        ?>
    </div>
<!-- Product Modal -->
<div id="productQuantityModal" class="product-modal" style="display: none;">
    <div class="product-modal-content">
        <span class="product-modal-close" onclick="closeProductModal()">&times;</span>
        <div class="product-modal-body">
            <img id="productPreviewImage" src="" alt="Product Image">
            <div id="productPreview">
                <h2 id="productPreviewName"></h2>
                <p id="productPreviewDescription"></p>
                <p>Price: <span id="productPreviewPrice"></span></p>
                <p>Stock: <span id="productPreviewStock"></span></p>
                <div class="product-quantity-controls">
                    <button type="button" id="productDecrement">-</button>
                    <input type="number" id="productQuantity" name="quantity" value="1" min="1">
                    <button type="button" id="productIncrement">+</button>
                </div>
                <button id="productAddToCartButton">Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<!-- Auth Modal -->
<div id="authModal" class="auth-modal" style="display: none;">
    <div class="auth-modal-content">
        <span class="auth-close" onclick="closeAuthModal()">&times;</span>
        <div id="formToggle" class="form-toggle">
            <button id="loginToggle" class="active">Login</button>
            <span>|</span>
            <button id="registerToggle">Register</button>
        </div>
        
        <!-- Login Form -->
        <form id="loginForm" class="authForm" method="post">
            <h2>Login</h2>
            <input type="text" id="loginUsername" name="username" placeholder="Username" required>
            <input type="password" id="loginPassword" name="password" placeholder="Password" required>
            <button type="submit">Submit</button>
        </form>
        
        <!-- Register Form -->
        <form id="registerForm" class="authForm" method="post" style="display: none;">
            <h2>Register</h2>
            <input type="text" id="registerName" name="name" placeholder="Full Name" required>
            <input type="email" id="registerEmail" name="email" placeholder="Email" required>
            <input type="password" id="registerPassword" name="password" placeholder="Password" required>
            <textarea id="registerAddress" name="address" placeholder="Address" required></textarea>
            <input type="text" id="registerPhone" name="phone" placeholder="Phone Number" required>
            <button type="submit">Submit</button>
        </form>
       <div class="admin-link" style="margin-top: 30px; text-align: center;">
    <a href="adminLogin.php">Are you an admin? Click here</a>
</div>
    </div>

</div>

<div id="notification" class="notification" style="display: none;">Item added to cart!</div>
<script>
    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
</script>
<script src="scripts/category.js"></script>
</body>
</html>