<?php
session_start();
include("config/db.php");

header("Content-Type: application/json"); // Ensure JSON response

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
    exit();
}

// Get device ID from the request
$deviceIdentifier = $_GET['device_id'] ?? '';

if (empty($deviceIdentifier)) {
    echo json_encode(["status" => "error", "message" => "No device ID provided."]);
    exit();
}

// Check if the device is registered as a terminal
$stmt = $conn->prepare("SELECT terminal_name FROM terminals WHERE device_identifier = ?");
$stmt->bind_param("s", $deviceIdentifier);
$stmt->execute();
$result = $stmt->get_result();
$terminal = $result->fetch_assoc();
$stmt->close();

if ($terminal) {
    $_SESSION['terminal_name'] = $terminal['terminal_name']; // Store in session
    echo json_encode(["status" => "success", "terminal_name" => $terminal['terminal_name']]);
} else {
    echo json_encode(["status" => "error", "message" => "This device is not registered as a terminal."]);
}
?>