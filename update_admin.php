<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

include("./config/db.php");

// Fetch current admin info
$sql = "SELECT username FROM admin WHERE id = 1"; // Adjust if needed
$adminResult = $conn->query($sql);
$admin = $adminResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        :root {
            --primary-color: #4a6fa5;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --light-gray: #f5f5f5;
            --border-color: #ddd;
            --text-color: #333;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f9f9f9;
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 600px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .back-btn {
            color: #3498db;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 8px 12px;
            border: 1px solid #3498db;
            border-radius: 4px;
            transition: all 0.3s;
            font-weight: bold;
            align-self: flex-start;
            margin-bottom: 10px;
            margin-left: 200px;
        }

        .back-btn:hover {
            background-color: #3498db;
            color: white;
        }

        .alert {
            padding: 12px;
            border-radius: 4px;
            font-size: 15px;
            width: 100%;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .admin-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            width: 100%;
        }

        h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-weight: 500;
            color: #555;
            font-size: 14px;
        }

        input {
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 15px;
            transition: border 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 5px;
        }

        button:hover {
            background-color: #2980b9;
        }

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .admin-section {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success_message'];
                unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?= $_SESSION['error_message'];
                unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Update Admin Credentials Section -->
        <div class="admin-section">
            <h3>Update Admin Credentials</h3>
            <form action="admin/update_admin_process.php" method="POST">
                <label for="username">New Username:</label>
                <input type="text" name="username" placeholder="Enter new username" required>

                <label for="password">New Password:</label>
                <input type="password" name="password" placeholder="Enter new password" required>

                <button type="submit">Update Credentials</button>
            </form>
        </div>

        <!-- Add New Admin Section -->
        <div class="admin-section">
            <h3>Add New Admin Account</h3>
            <form action="admin/add_admin_process.php" method="POST">
                <label for="new_username">Username:</label>
                <input type="text" name="new_username" placeholder="Enter new admin username" required>

                <label for="new_password">Password:</label>
                <input type="password" name="new_password" placeholder="Enter new admin password" required>

                <button type="submit">Create Admin Account</button>
            </form>
        </div>
    </div>
</body>

</html>