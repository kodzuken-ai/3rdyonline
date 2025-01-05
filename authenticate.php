<?php
include 'dbConnect.php';
session_start();

if (isset($_POST['signIn'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Use password_verify to check hashed password
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $username;
            header("Location: dashboard.php");
            exit();
        }
    }
    echo "<script>
            alert('Invalid username or password!');
            window.location.href = 'login.php';
          </script>";
    exit();
}

if (isset($_POST['signUp'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $checkuser = "SELECT * FROM admin WHERE username = ?";
    $stmt = $mysqli->prepare($checkuser);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>
                alert('Username already exists!');
                window.location.href = 'register.php';
              </script>";
        exit();
    } else {
        // Hash the password before storing it
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertQuery = "INSERT INTO admin (username, password) VALUES (?, ?)";
        $stmt = $mysqli->prepare($insertQuery);
        $stmt->bind_param('ss', $username, $hashedPassword);

        if ($stmt->execute()) {
            echo "<script>
                    alert('User registered successfully!');
                    window.location.href = 'index.php';
                  </script>";
            exit();
        } else {
            echo "Error: " . $mysqli->error;
        }
    }
}
?>