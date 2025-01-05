<?php
session_start();
include 'dbConnect.php'; // Ensure this file connects to your database

// Fetch available products with a limit of 30 for display on the index page
$query = "SELECT * FROM products WHERE availability = 1 LIMIT 15";
$result = mysqli_query($conn, $query);

// Fetch popular products (assuming you have a 'sales' column)
$popularProductsQuery = "SELECT * FROM products ORDER BY sales DESC LIMIT 5";
$popularProductsResult = mysqli_query($conn, $popularProductsQuery);

// Calculate total distinct items in the cart from the database
$totalItems = 0;
$isLoggedIn = isset($_SESSION['customer_id']);
$profileUrl = 'images/default.png'; // Default profile image

if ($isLoggedIn) {
    $customerId = $_SESSION['customer_id'];
    
    // Calculate total distinct items in the cart
    $totalItemsQuery = "SELECT COUNT(DISTINCT product_id) as totalItems FROM cart WHERE customer_id = ?";
    $stmt = $conn->prepare($totalItemsQuery);
    if ($stmt) {
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $totalItemsResult = $stmt->get_result();
        $totalItemsRow = $totalItemsResult->fetch_assoc();
        $totalItems = $totalItemsRow['totalItems'] ?? 0;
        $stmt->close();
    }

    // Fetch the profile URL
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
    <title>3rdy Sari-Sari Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/index.css">
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
            <i class="fas fa-shopping-cart"></i> <span id="cart-count"><?php echo $totalItems; ?></span>
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

<!-- Hero Section -->
<section class="hero">
    <img src="images/hero.jpeg" alt="Hero Banner" class="hero-image">
    <div class="hero-content">
        <h1>Welcome to 3rdy Sari-Sari Store</h1>
        <p>Your one-stop shop for all your needs!</p>
        <a href="category.php" class="hero-button">Shop Now</a>
    </div>
</section>

<div class="products" id="product-list">
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <?php while ($product = mysqli_fetch_assoc($result)): ?>
            <div class="product" data-id="<?php echo htmlspecialchars($product['id']); ?>" 
                 data-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>" 
                 data-description="<?php echo htmlspecialchars(strtolower($product['description'])); ?>"
                 data-stock="<?php echo htmlspecialchars($product['stock']); ?>"> <!-- Keep stock data attribute -->
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                <p><?php echo htmlspecialchars($product['description']); ?></p>
                <p>Price: <?php echo htmlspecialchars($product['price']); ?></p>
                <a href="#" class="view-product">View Product</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No products found.</p>
    <?php endif; ?>
</div>

<!-- Popular Products Section -->
<div class="popular-products">
   <h2 style="font-size: 1.5rem; text-align: left; color: #ffffff; margin: 10px 0;">Popular Products:</h2><br>
    <div class="popular-product-list">
        <?php if ($popularProductsResult && mysqli_num_rows($popularProductsResult) > 0): ?>
            <?php while ($product = mysqli_fetch_assoc($popularProductsResult)): ?>
                <div class="product" data-id="<?php echo htmlspecialchars($product['id']); ?>" 
                     data-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>" 
                     data-description="<?php echo htmlspecialchars(strtolower($product['description'])); ?>"
                     data-stock="<?php echo htmlspecialchars($product['stock']); ?>"> <!-- Keep stock data attribute -->
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                    <p>Price: <?php echo htmlspecialchars($product['price']); ?></p>
                    <a href="#" class="view-product">View Product</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No popular products found.</p>
        <?php endif; ?>
    </div>
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
                <p>Stock: <span id="productPreviewStock"></span></p> <!-- Stock displayed here -->
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
<?php include 'footer.php'?>
<script>
    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
</script>
<script src="scripts/index.js"></script>
</body>
</html>