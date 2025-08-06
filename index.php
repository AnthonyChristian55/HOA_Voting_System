<?php
session_start();
include("./config/db.php");

// Redirect to PIN check if not validated
if (!isset($_SESSION['valid_pin'])) {
    header('Location: pin_check.php');
    exit;
}

// Verify PIN against database
$pin = $_SESSION['valid_pin'];
$stmt = $conn->prepare("SELECT terminal_name FROM terminal WHERE pin = ?");
$stmt->bind_param("s", $pin);
$stmt->execute();
$terminal = $stmt->get_result()->fetch_assoc();

if (!$terminal) {
    // Invalid PIN in session - clear and redirect
    unset($_SESSION['valid_pin']);
    header('Location: pin_check.php');
    exit;
}

// Fetch the most recent logo from the database
$query = "SELECT logo_path FROM picture_upload ORDER BY uploaded_at DESC LIMIT 1";
$result = $conn->query($query);
$row = $result->fetch_assoc();
$logoPath = $row['logo_path'] ?? 'default_logo.png'; // Default if no logo is uploaded
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election System - <?= htmlspecialchars($terminal['terminal_name']) ?></title>
    <link rel="stylesheet" href="./css/styles.css">
</head>

<body>

    <div class="container">
        <div class="logo-container">
            <img src="uploads/<?= htmlspecialchars($logoPath) ?>" alt="Current Logo" class="logo">
        </div>

        <div class="modal-content">
            <h3>Terminal: <?= htmlspecialchars($terminal['terminal_name']) ?></h3>
            <h3>Proceed to Vote:</h3>
            <button class="user-btn" onclick="location.href='enter_code.php'">Enter Voters Code</button>

            <!-- Optional: Terminal logout button -->
            <div style="margin-top: 20px;">
                <button class="logout-btn" onclick="location.href='admin/logout_terminal.php'">Switch Terminal</button>
            </div>
        </div>
    </div>

</body>

</html>