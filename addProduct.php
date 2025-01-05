<?php
session_name('admin_session');
session_start();
include 'dbConnect.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addProduct'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $availability = isset($_POST['availability']) ? 1 : 0;
    $category_id = $_POST['category'];

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "images/";
        $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        // Generate a unique file name to prevent overwriting
        $uniqueFileName = uniqid('img_', true) . '.' . $imageFileType;
        $targetFile = $targetDir . $uniqueFileName;

        // Check if the file is an image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            // Move the uploaded file to the target directory
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image_url = $targetFile;
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error = "File is not an image.";
        }
    } else {
        $error = "No file was uploaded or there was an upload error.";
    }

    // If no errors, insert the product into the database
    if (!isset($error)) {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, image_url, availability, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdisii", $name, $description, $price, $stock, $image_url, $availability, $category_id);

        if ($stmt->execute()) {
            header("Location: products.php");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/adminAddProduct.css">
</head>
<body>
    <header class="header">
        <div class="logo">
            <img src="images/logo.png" alt="3rdy Sari-Sari Store Logo" class="logo-img">
            <span class="company-name">3rdy Sari-Sari Store</span>
        </div>
    </header>
    <a href="products.php" class="back-arrow"><i class="fas fa-arrow-left"></i> Back to Products</a>    
    <div class="main-content">
        <div class="container">
            <h1>Add New Product</h1>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="input-group">
                    <label for="name">Product Name</label>
                    <input type="text" name="name" id="name" required>
                </div>
                <div class="input-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" required></textarea>
                </div>
                <div class="input-group">
                    <label for="price">Price</label>
                    <input type="number" step="0.01" name="price" id="price" required>
                </div>
                <div class="input-group">
                    <label for="stock">Stock</label>
                    <input type="number" name="stock" id="stock" required>
                </div>
                <div class="input-group">
                    <label for="category">Category</label>
                    <select name="category" id="category" required>
                        <?php
                        // Fetch categories from the database
                        $result = $conn->query("SELECT id, name FROM categories");
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="image">Image</label>
                    <input type="file" name="image" id="image" accept="image/*" required>
                </div>
                <div class="input-group">
                    <label for="availability">Availability</label>
                    <input type="checkbox" name="availability" id="availability" checked>
                </div>
                <input type="submit" class="btn" value="Add Product" name="addProduct">
            </form>
            <?php if (isset($error)) { echo "<p class='error-message'>$error</p>"; } ?>
        </div>
    </div>
</body>
</html>