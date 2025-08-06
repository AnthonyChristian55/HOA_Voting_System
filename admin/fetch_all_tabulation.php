<?php
include("../config/db.php");

// Fetch all candidates sorted by votes (DESC)
$result = $conn->query("SELECT fname, lname, votes FROM candidates ORDER BY votes DESC");

$candidates = [];
while ($row = $result->fetch_assoc()) {
    $candidates[] = $row;
}

header("Content-Type: application/json");
echo json_encode($candidates);
?>