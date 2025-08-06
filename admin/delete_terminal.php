<?php
session_start();
include("../config/db.php");

// Ensure only admins can delete a terminal
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

$terminalId = $_POST['terminal_id'] ?? '';

if (empty($terminalId) || !is_numeric($terminalId)) {
    echo json_encode(["status" => "error", "message" => "Invalid terminal ID."]);
    exit();
}

// Delete terminal from database
$stmt = $conn->prepare("DELETE FROM terminals WHERE id = ?");
$stmt->bind_param("i", $terminalId);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Terminal deleted successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to delete terminal."]);
}

$stmt->close();
$conn->close();
?>