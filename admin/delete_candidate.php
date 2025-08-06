<?php
include("../config/db.php");

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Ensure ID is an integer

    // Prepare the DELETE statement
    $stmt = $conn->prepare("DELETE FROM candidates WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Redirect back to dashboard after successful deletion
        header("Location: ../dashboard.php");
        exit();
    } else {
        echo "Error deleting candidate.";
    }

    $stmt->close();
}

$conn->close();
?>