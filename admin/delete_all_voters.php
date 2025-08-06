<?php
session_start();
include("../config/db.php");

// Check if admin is logged in
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

// Delete all voter records
$query = "DELETE FROM voters";
if ($conn->query($query) === TRUE) {
    $_SESSION['success_message'] = "All voter records have been deleted.";
} else {
    $_SESSION['error_message'] = "Error deleting records: " . $conn->error;
}

$conn->close();
header("Location: ../view_voter.php");
exit();
?>