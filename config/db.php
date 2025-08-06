<?php
$host = "localhost";
$user = "root"; // Change if using another user
$pass = ""; // Change if your MySQL has a password
$dbname = "election_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>