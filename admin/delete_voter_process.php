<?php
session_start();
include("../config/db.php");

// Check if admin is logged in
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

// Check if delete request was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete"]) && !empty($_POST["voter_id"])) {
    $voter_id = $_POST["voter_id"];

    // Delete the voter from the database
    $query = "DELETE FROM voters WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $voter_id); // Use "i" for integer binding

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Voter deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting voter.";
    }

    $stmt->close();
    $conn->close();

    header("Location: ../view_voter.php");
    exit();
} else {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: ../view_voter.php");
    exit();
}
?>