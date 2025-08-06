<?php
session_start();
include("./config/db.php");

$test = $conn->query("SELECT picture_upload FROM candidates LIMIT 1");
$row = $test->fetch_assoc();

echo "<h2>Image Path Test</h2>";
echo "<p>Database value: " . htmlspecialchars($row['picture_upload']) . "</p>";

$paths = [
    './uploads/' . $row['picture_upload'],
    './uploads/candidates/' . $row['picture_upload']
];

foreach ($paths as $path) {
    echo "<p>Checking: " . htmlspecialchars($path) . " - ";
    echo file_exists($path) ? "EXISTS" : "DOES NOT EXIST";
    echo "</p>";

    if (file_exists($path)) {
        echo "<img src='" . htmlspecialchars($path) . "' style='max-width: 200px;'><br><br>";
    }
}

echo "<h3>Placeholder Check</h3>";
echo "<p>./uploads/placeholder.jpg - ";
echo file_exists('./uploads/placeholder.jpg') ? "EXISTS" : "DOES NOT EXIST";
echo "</p>";
if (file_exists('./uploads/placeholder.jpg')) {
    echo "<img src='./uploads/placeholder.jpg' style='max-width: 200px;'>";
}
?>