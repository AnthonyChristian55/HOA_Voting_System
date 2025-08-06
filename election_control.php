<?php
session_start();
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

include("./config/db.php");

// Get current election status
$stmt = $pdo->query("SELECT is_active FROM election_status LIMIT 1");
$is_active = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Election Control Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }

        .control-panel {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .toggle-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #28a745;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .status-message {
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
        }

        .active {
            background-color: #d4edda;
            color: #155724;
        }

        .inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <div class="control-panel">
        <h1>Election Control Panel</h1>

        <div class="toggle-container">
            <label class="toggle-switch">
                <input type="checkbox" id="electionToggle" <?= $is_active ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
            <span>Election Status: <strong><?= $is_active ? 'OPEN' : 'CLOSED' ?></strong></span>
        </div>

        <div id="statusMessage" class="status-message <?= $is_active ? 'active' : 'inactive' ?>">
            <?= $is_active ? '✅ Election is currently accepting votes' : '❌ Election is closed - no votes accepted' ?>
        </div>
    </div>

    <script>
        document.getElementById('electionToggle').addEventListener('change', function () {
            const isActive = this.checked;

            fetch('admin/toggle_election.php', {  // Adjusted path
                method: 'POST',
                body: JSON.stringify({ active: isActive ? 1 : 0 }),
                headers: { 'Content-Type': 'application/json' }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const statusMessage = document.getElementById('statusMessage');
                        statusMessage.textContent = isActive
                            ? '✅ Election is currently accepting votes'
                            : '❌ Election is closed - no votes accepted';
                        statusMessage.className = `status-message ${isActive ? 'active' : 'inactive'}`;
                        this.nextElementSibling.innerHTML = `Election Status: <strong>${isActive ? 'OPEN' : 'CLOSED'}</strong>`;
                    } else {
                        alert(data.error || 'Error updating status');
                        this.checked = !isActive;
                    }
                });
        });
    </script>
</body>

</html>