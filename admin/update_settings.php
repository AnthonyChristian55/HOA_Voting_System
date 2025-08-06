<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $min_votes = $_POST['min_votes'];
    $max_votes = $_POST['max_votes'];

    // Ensure min votes is not greater than max votes
    if ($min_votes > $max_votes) {
        echo "<script>alert('Minimum votes cannot be greater than maximum votes!'); window.history.back();</script>";
        exit();
    }

    $stmt = $conn->prepare("UPDATE settings SET min_votes = ?, max_votes = ? WHERE id = 1");
    $stmt->bind_param("ii", $min_votes, $max_votes);

    if ($stmt->execute()) {
        echo "<script>alert('Settings updated successfully!'); window.location.href = '../dashboard.php';</script>";
    } else {
        echo "<script>alert('Error updating settings.'); window.history.back();</script>";
    }
}
?>