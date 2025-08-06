<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = $_POST['username'];
    $new_password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash password

    $stmt = $conn->prepare("UPDATE admin SET username = ?, password = ? WHERE id = 1");
    $stmt->bind_param("ss", $new_username, $new_password);

    if ($stmt->execute()) {
        echo "<script>alert('Admin credentials updated successfully. Please log in again.'); window.location.href = 'logout.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error updating admin credentials.'); window.history.back();</script>";
    }
}
?>