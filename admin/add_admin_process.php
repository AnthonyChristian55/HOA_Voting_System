<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST["new_username"]);
    $new_password = trim($_POST["new_password"]);

    // Validate input
    if (empty($new_username) || empty($new_password)) {
        $_SESSION['error_message'] = "All fields are required.";
        header("Location: ../update_admin.php");
        exit();
    }

    // Check if the username already exists
    $checkQuery = "SELECT id FROM admin WHERE username = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $new_username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error_message'] = "Username already exists. Choose a different one.";
        header("Location: ../update_admin.php");
        exit();
    }
    $stmt->close();

    // Hash the password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Insert the new admin into the database
    $insertQuery = "INSERT INTO admin (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ss", $new_username, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "New admin account added successfully!";
    } else {
        $_SESSION['error_message'] = "Error adding new admin. Please try again.";
    }

    $stmt->close();
    $conn->close();

    header("Location: ../update_admin.php");
    exit();
} else {
    header("Location: ../update_admin.php");
    exit();
}
?>