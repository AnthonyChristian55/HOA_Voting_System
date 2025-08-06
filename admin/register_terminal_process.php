<?php
session_start();
include("../config/db.php");

// Ensure only admins can access this
if (!isset($_SESSION['admin'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Please log in again."]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
    exit();
}

$terminalName = trim($_POST['terminal_name'] ?? '');

if (empty($terminalName)) {
    echo json_encode(["status" => "error", "message" => "Missing terminal name."]);
    exit();
}

// Generate a 5-digit device identifier
$deviceIdentifier = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);

// Check if the generated device identifier is already registered (unlikely but possible)
$stmt = $conn->prepare("SELECT id FROM terminals WHERE device_identifier = ?");
$stmt->bind_param("s", $deviceIdentifier);
$stmt->execute();
$result = $stmt->get_result();

// If by chance the ID exists, generate a new one (up to 5 attempts)
$attempts = 0;
while ($result->num_rows > 0 && $attempts < 5) {
    $deviceIdentifier = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
    $stmt->execute();
    $result = $stmt->get_result();
    $attempts++;
}

if ($result->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Could not generate a unique device identifier. Please try again."]);
    exit();
}

// Check if the terminal name is already in use
$stmt = $conn->prepare("SELECT id FROM terminals WHERE terminal_name = ?");
$stmt->bind_param("s", $terminalName);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Terminal name already exists. Please choose a different name."]);
    exit();
}

// Register the new terminal
$stmt = $conn->prepare("INSERT INTO terminals (terminal_name, device_identifier) VALUES (?, ?)");
$stmt->bind_param("ss", $terminalName, $deviceIdentifier);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Terminal registered as " . htmlspecialchars($terminalName),
        "device_identifier" => $deviceIdentifier
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Could not register terminal."]);
}

$stmt->close();
$conn->close();
?>