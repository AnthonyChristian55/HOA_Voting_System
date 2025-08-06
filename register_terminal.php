<?php
session_start();
include("config/db.php");

if (isset($_GET['device_id'])) {
    $_SESSION['device_id'] = $_GET['device_id']; // Store device ID in session
}
// Ensure only admins can access this page
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// Default message
$terminalMessage = "❌ This device is not registered as a terminal.";
$terminalName = "";
$terminalId = null;

// Check if device identifier is available
if (isset($_GET['device_id']) && !empty($_GET['device_id'])) {
    $deviceIdentifier = $_GET['device_id'];

    $stmt = $conn->prepare("SELECT id, terminal_name FROM terminals WHERE device_identifier = ?");
    $stmt->bind_param("s", $deviceIdentifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $terminalId = $row['id'];
        $terminalName = $row['terminal_name'];
        $terminalMessage = "✅ This device is registered as <strong>Terminal " . htmlspecialchars($terminalName) . "</strong>.";
    }

    $stmt->close();
} else {
    // Redirect to get the device_id from localStorage
    echo "<script>
        let deviceId = localStorage.getItem('device_id');
        if (!deviceId) {
            deviceId = 'device-' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('device_id', deviceId);
        }
        window.location.href = 'register_terminal.php?device_id=' + encodeURIComponent(deviceId);
    </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Terminal</title>
</head>

<body>
    <a href="dashboard.php">Back to Dashboard</a>
    <h2>Register Device as Terminal</h2>
    <p id="terminal-status" style="color: green; font-weight: bold;"><?= $terminalMessage ?></p>

    <?php if (!$terminalName): ?>
        <form id="registerForm">
            <label for="terminal_name">Terminal Name:</label>
            <input type="text" id="terminal_name" name="terminal_name" required>
            <button type="submit">Register</button>
        </form>
        <p id="message"></p>
    <?php else: ?>
        <button onclick="deleteTerminal(<?= $terminalId ?>)">❌ Delete Terminal</button>
    <?php endif; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            function getDeviceIdentifier() {
                let deviceId = localStorage.getItem("device_id");
                if (!deviceId) {
                    deviceId = "device-" + Math.random().toString(36).substr(2, 9);
                    localStorage.setItem("device_id", deviceId);
                }
                return deviceId;
            }

            let deviceId = getDeviceIdentifier();

            const registerForm = document.getElementById("registerForm");
            if (registerForm) {
                registerForm.addEventListener("submit", function (event) {
                    event.preventDefault();
                    let terminalName = document.getElementById("terminal_name").value;

                    fetch("admin/register_terminal_process.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `terminal_name=${encodeURIComponent(terminalName)}&device_id=${encodeURIComponent(deviceId)}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById("message").innerText = data.message;
                            if (data.status === "success") {
                                setTimeout(() => {
                                    window.location.href = `register_terminal.php?device_id=${encodeURIComponent(deviceId)}`;
                                }, 1000);
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            document.getElementById("message").innerText = "❌ Registration failed. Please try again.";
                        });
                });
            }

            window.deleteTerminal = function (terminalId) {
                if (confirm("Are you sure you want to delete this terminal?")) {
                    fetch("admin/delete_terminal.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: "terminal_id=" + encodeURIComponent(terminalId)
                    })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            if (data.status === "success") {
                                localStorage.removeItem("device_id");

                                // Generate a new device_id and reload
                                let newDeviceId = "device-" + Math.random().toString(36).substr(2, 9);
                                localStorage.setItem("device_id", newDeviceId);
                                window.location.href = "register_terminal.php?device_id=" + encodeURIComponent(newDeviceId);
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            alert("❌ Failed to delete terminal.");
                        });
                }
            };
        });
    </script>
</body>

</html>