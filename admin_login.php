<?php
session_start();
include("./config/db.php");

// Redirect if admin is already logged in
if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit();
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // ✅ FIXED: Use "admin" instead of "admins"
        $query = "SELECT id, username, password FROM admin WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin'] = $admin['id']; // Store admin session
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="./css/styles.css">
</head>

<body>

    <div class="container">
        <h2>Admin Login</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="Enter Username" required class="form-input">
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Enter Password" required class="form-input">
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>
    </div>

</body>

</html>