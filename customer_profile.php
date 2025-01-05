<?php
session_start();
include 'dbConnect.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

$customerId = $_SESSION['customer_id'];

// Fetch customer profile information
$query = "SELECT name, email, address, phone, profile_url FROM customers WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $customerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($customer = mysqli_fetch_assoc($result)) {
        $name = $customer['name'];
        $email = $customer['email'];
        $address = $customer['address'];
        $phone = $customer['phone'];
        $profileUrl = !empty($customer['profile_url']) ? $customer['profile_url'] : 'images/default.png';
    } else {
        echo "Error fetching profile information.";
        exit();
    }

    mysqli_stmt_close($stmt);
} else {
    die("Failed to prepare statement: " . mysqli_error($conn));
}

// Calculate total amount spent by the customer for completed orders
$totalSpentQuery = "SELECT SUM(total) AS total_spent FROM orders WHERE customer_id = ? AND order_status = 'completed'";
$totalSpentStmt = mysqli_prepare($conn, $totalSpentQuery);
if ($totalSpentStmt) {
    mysqli_stmt_bind_param($totalSpentStmt, "i", $customerId);
    mysqli_stmt_execute($totalSpentStmt);
    $totalSpentResult = mysqli_stmt_get_result($totalSpentStmt);
    $totalSpent = mysqli_fetch_assoc($totalSpentResult)['total_spent'];
    mysqli_stmt_close($totalSpentStmt);
} else {
    echo "Error calculating total amount spent.";
}

mysqli_close($conn);

// Calculate total items in the cart
$totalItems = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $totalItems += $item['quantity'];
    }
}

$isLoggedIn = isset($_SESSION['customer_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/profile.css">
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
                <img src="<?php echo htmlspecialchars($profileUrl) . '?' . time(); ?>" alt="Profile" class="profile-pic" onclick="toggleDropdown()">
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

<main>
    <div class="profile-section">
        <div class="profile-picture">
            <h1>PROFILE PICTURE</h1>
            <img src="<?php echo htmlspecialchars($profileUrl) . '?' . time(); ?>" alt="Profile Picture">
        </div>
        <div class="profile-info">
            <div class="profile-details">
                <h1>USER INFORMATION</h1>
                <hr>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($name); ?></p>
                <hr>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p><hr>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($address); ?></p><hr>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p><hr>
                <button id="editProfileBtn">Edit Profile</button>
            </div>
        </div>
    </div>

    <section class="total-spent">
        <h2>Total Amount Spent</h2>
        <p>You have spent a total of ₱<?php echo htmlspecialchars($totalSpent); ?> on our store.</p>
    </section>
</main>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Profile</h2>
        <form id="editProfileForm" enctype="multipart/form-data">
            <label for="editName">Name:</label>
            <input type="text" id="editName" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            <label for="editEmail">Email:</label>
            <input type="email" id="editEmail" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            <label for="editAddress">Address:</label>
            <input type="text" id="editAddress" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
            <label for="editPhone">Phone:</label>
            <input type="text" id="editPhone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
            <label for="editProfilePic">Profile Picture:</label>
            <input type="file" id="editProfilePic" name="profile_pic" accept="image/*">
            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>

<script>
    // Toggle profile dropdown
    function toggleDropdown() {
        const dropdown = document.getElementById('dropdownMenu');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }

    // Modal handling
    const editProfileModal = document.getElementById("editProfileModal");
    const editProfileBtn = document.getElementById("editProfileBtn");
    const closeBtn = document.getElementsByClassName("close")[0];

    editProfileBtn.onclick = function() {
        editProfileModal.style.display = "block";
    }

    closeBtn.onclick = function() {
        editProfileModal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == editProfileModal) {
            editProfileModal.style.display = "none";
        }
    }

    // Handle form submission
    document.getElementById('editProfileForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "update_profile.php", true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                alert("Profile updated successfully!");
                editProfileModal.style.display = "none";
                location.reload(); // Reload the page to reflect changes
            }
        };
        const formData = new FormData(this);
        xhr.send(formData);
    });

    // Close dropdown when clicking outside
    window.addEventListener('click', function(event) {
        if (!event.target.matches('.profile-pic')) {
            const dropdowns = document.getElementsByClassName('dropdown-content');
            for (let i = 0; i < dropdowns.length; i++) {
                const openDropdown = dropdowns[i];
                if (openDropdown.style.display === 'block') {
                    openDropdown.style.display = 'none';
                }
            }
        }
    });

    function updateCartCount() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'get_cart_count.php', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById('cart-count').textContent = xhr.responseText;
            }
        };
        xhr.send();
    }
    // Call updateCartCount every 5 seconds to refresh the cart count
    setInterval(updateCartCount, 5000);

    // Optionally, call updateCartCount on page load
    document.addEventListener('DOMContentLoaded', updateCartCount);
</script>
</body>
</html>