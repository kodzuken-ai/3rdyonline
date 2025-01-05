<?php
include 'dbConnect.php';

// Set a custom session name for admin sessions
session_name('admin_session');
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signIn'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        // Verify the password
        if (password_verify($password, $admin['password'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            header("Location: adminDashboard.php");
            exit();
        } else {
            $error = "Incorrect email or password. Please try again.";
        }
    } else {
        $error = "Incorrect email or password. Please try again.";
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="css/Login.css">
</head>
<body>
<div class="container" id="signIn">
    <h1 class="form-title">Admin Login</h1>
    <form method="POST" action="" onsubmit="return validateForm();">
        <div class="input-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="Enter your email" required>
            <?php if (!empty($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
        </div>
        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" required>
        </div>
        <input type="submit" class="btn" value="Sign In" name="signIn">
    </form>
    <div class="links">
        <p>Don't have an account yet? <a id="SignupButton" href="adminRegister.php">Sign up</a></p>
    </div>
    <div class="switch-link">
        <p>Are you a customer? <a href="index.php">click here</a></p>
    </div>
</div>
</body>
</html>