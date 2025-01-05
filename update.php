<?php
include 'dbConnect.php';

if (!isset($_GET['id'])) {
    die("Product ID not specified.");
}

$product_id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updateProduct'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $availability = isset($_POST['availability']) ? 1 : 0;
    $category_id = $_POST['category_id'];

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "images/";
        $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $targetFile = $targetDir . uniqid() . '.' . $imageFileType;

        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image_url = $targetFile;
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error = "File is not an image.";
        }
    } else {
        $image_url = $_POST['existing_image_url'];
    }

    if (!isset($error)) {
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image_url = ?, availability = ?, category_id = ? WHERE id = ?");
        $stmt->bind_param("ssdisiii", $name, $description, $price, $stock, $image_url, $availability, $category_id, $product_id);

        if ($stmt->execute()) {
            header("Location: products.php");
            exit();
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch product details
$query = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Product not found.");
}

$product = $result->fetch_assoc();
$stmt->close();

// Fetch categories
$categoryQuery = "SELECT id, name FROM categories";
$categoryResult = $conn->query($categoryQuery);
$categories = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/update.css">
</head>
<body>
    <header class="header">
        <div class="logo">
            <img src="images/logo.png" alt="3rdy Sari-Sari Store Logo" class="logo-img">
            <span class="company-name">3rdy Sari-Sari Store</span>
        </div>
    </header>
    <a href="products.php" class="back-arrow"><i class="fas fa-arrow-left"></i> Back to Products</a>
    <div class="container">
        <h1>Update Product</h1>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="input-group">
                <label for="name">Product Name</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            <div class="input-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            <div class="input-group">
                <label for="price">Price</label>
                <input type="number" step="0.01" name="price" id="price" value="<?php echo $product['price']; ?>" required>
            </div>
            <div class="input-group">
                <label for="stock">Stock</label>
                <input type="number" name="stock" id="stock" value="<?php echo $product['stock']; ?>" required>
            </div>
            <div class="input-group">
                <label for="category_id">Category</label>
                <select name="category_id" id="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
                <label for="image">Image</label>
                <input type="file" name="image" id="image" accept="image/*">
                <input type="hidden" name="existing_image_url" value="<?php echo htmlspecialchars($product['image_url']); ?>">
                <div class="current-image">
                    <p>Current Image:</p>
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Current Product Image" style="max-width: 100px; max-height: 100px;">
                </div>
            </div>
            <div class="input-group">
                <label for="availability">Availability</label>
                <input type="checkbox" name="availability" id="availability" <?php echo $product['availability'] ? 'checked' : ''; ?>>
            </div>
            <input type="submit" class="btn" value="Update Product" name="updateProduct">
        </form>
        <?php if (isset($error)) { echo "<p class='error-message'>$error</p>"; } ?>
    </div>
</body>
</html>