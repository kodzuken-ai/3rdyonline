<?php
session_start();
include 'dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: index.php");
    exit();
}

$customerId = $_SESSION['customer_id'];
$isLoggedIn = isset($_SESSION['customer_id']);

// Fetch cart items from the database
$query = "SELECT cart.id as cart_id, products.id as product_id, products.name, products.price, cart.quantity, products.image_url 
          FROM cart 
          JOIN products ON cart.product_id = products.id 
          WHERE cart.customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $total += $row['price'] * $row['quantity'];
}

// Add delivery fee
$deliveryFee = 50;
$grandTotal = $total + $deliveryFee;

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/cart.css">
</head>
<body>
<header>
    <div class="logo" onclick="window.location.href='index.php'">
        <img src="images/Logo.png" alt="Logo">
        <span style="color: white; font-size: 1.5rem; font-weight: bold;">3rdy Sari-Sari Store</span>
    </div>
   
    <div class="cart">
        <a href="customer_cart.php" class="cart-icon">
            <i class="fas fa-shopping-cart"></i> <span id="cart-count"><?php echo count($cartItems); ?></span>
        </a>
    </div>
    <div class="auth-links">
        <?php if ($isLoggedIn): ?>
            <div class="profile-dropdown">
                <img src="<?php echo htmlspecialchars($profileUrl); ?>" alt="Profile" class="profile-pic" onclick="toggleDropdown()">
                <div id="dropdownMenu" class="dropdown-content">
                    <a href="customer_profile.php">Profile</a>
                    <a href="orders.php">Orders</a>
                    <a href="customer_logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="#" onclick="openAuthModal()">Sign In</a> | <a href="#" onclick="openAuthModal()">Sign Up</a>
        <?php endif; ?>
    </div>
</header>
<div class="back-arrow" onclick="window.history.back()">
        <i class="fas fa-arrow-left"></i> Back
    </div>
<div class="cart-container">
    <h1>Your Cart (<?php echo count($cartItems); ?> items)</h1>
    <div class="cart-header">
        <div>Item</div>
        <div>Price</div>
        <div>Quantity</div>
        <div>Total</div>
        <div>Action</div>
    </div>

    <?php foreach ($cartItems as $item): ?>
    <div class="cart-item">
        <div class="item-details">
            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="Product Image">
            <div>
                <div class="item-name"> <?php echo htmlspecialchars($item['name']); ?> </div>
            </div>
        </div>
        <div>₱<?php echo number_format($item['price'], 2); ?></div>
        <div class="quantity-controls">
            <span><?php echo $item['quantity']; ?></span>
        </div>
        <div>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
        <div>
            <button class="remove-btn" onclick="removeItem(<?php echo $item['cart_id']; ?>)">Remove</button>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="cart-summary">
        <div>Subtotal: <strong>₱<?php echo number_format($total, 2); ?></strong></div>
        <div>Delivery Fee: <strong>₱<?php echo number_format($deliveryFee, 2); ?></strong></div>
        <div style="font-size: 1.4rem; font-weight: bold;">Grand Total: ₱<?php echo number_format($grandTotal, 2); ?></div>
        <button class="checkout-btn" onclick="handleCheckout(<?php echo $total; ?>)">Check out</button>
    </div>
</div>

<script>
function updateQuantity(cartId, action) {
    window.location.href = `update_quantity.php?cart_id=${cartId}&action=${action}`;
}

function removeItem(cartId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        window.location.href = `remove_item.php?cart_id=${cartId}`;
    }
}

function toggleDropdown() {
    const dropdown = document.getElementById('dropdownMenu');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

function handleCheckout(total) {
    if (total < 100) {
        alert('The subtotal must be at least 100 to proceed to checkout.');
    } else {
        window.location.href = 'checkout.php';
    }
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
</script>
</body>
</html>