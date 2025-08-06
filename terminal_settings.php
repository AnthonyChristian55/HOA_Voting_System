<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new terminal
    if (isset($_POST['add_terminal'])) {
        $pin = $conn->real_escape_string($_POST['pin']);
        $name = $conn->real_escape_string($_POST['terminal_name']);

        if (!empty($pin) && !empty($name)) {
            $stmt = $conn->prepare("INSERT INTO terminal (pin, terminal_name) VALUES (?, ?)");
            $stmt->bind_param("ss", $pin, $name);
            $stmt->execute();
            $stmt->close();
        }
    }
    // Update terminal
    elseif (isset($_POST['update_terminal'])) {
        $id = $conn->real_escape_string($_POST['terminal_id']);
        $pin = $conn->real_escape_string($_POST['pin']);
        $name = $conn->real_escape_string($_POST['terminal_name']);

        $stmt = $conn->prepare("UPDATE terminal SET pin = ?, terminal_name = ? WHERE pin = ?");
        $stmt->bind_param("sss", $pin, $name, $id);
        $stmt->execute();
        $stmt->close();
    }
    // Delete terminal
    elseif (isset($_POST['delete_terminal'])) {
        $id = $conn->real_escape_string($_POST['terminal_id']);

        $stmt = $conn->prepare("DELETE FROM terminal WHERE pin = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch all terminals
$terminals = $conn->query("SELECT * FROM terminal ORDER BY terminal_name");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal Settings</title>
    <link rel="stylesheet" href="./css/terminal_settings.css">
</head>

<body>
    <div class="container">
        <h1 style="display: flex; justify-content: space-between; align-items: center;">
            <span>Terminal Management</span>
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        </h1>

        <!-- Add New Terminal Form -->
        <h2>Add New Terminal</h2>
        <form method="POST">
            <div class="form-group">
                <label for="pin">PIN (5 digits):</label>
                <input type="text" id="pin" name="pin" pattern="\d{5}" title="5-digit PIN" required>
            </div>
            <div class="form-group">
                <label for="terminal_name">Terminal Name:</label>
                <input type="text" id="terminal_name" name="terminal_name" required>
            </div>
            <button type="submit" name="add_terminal" class="btn-primary">Add Terminal</button>
        </form>

        <!-- Terminal List -->
        <h2>Existing Terminals</h2>
        <table>
            <thead>
                <tr>
                    <th>PIN</th>
                    <th>Terminal Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($terminal = $terminals->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($terminal['pin']) ?></td>
                        <td><?= htmlspecialchars($terminal['terminal_name']) ?></td>
                        <td class="action-btns">
                            <button onclick="openEditModal(
                            '<?= $terminal['pin'] ?>',
                            '<?= htmlspecialchars($terminal['terminal_name'], ENT_QUOTES) ?>'
                        )" class="btn-warning">Edit</button>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="terminal_id" value="<?= $terminal['pin'] ?>">
                                <button type="submit" name="delete_terminal" class="btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this terminal?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Terminal</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="terminal_id" id="edit_terminal_id">
                <div class="form-group">
                    <label for="edit_pin">PIN:</label>
                    <input type="text" id="edit_pin" name="pin" pattern="\d{5}" title="5-digit PIN" required>
                </div>
                <div class="form-group">
                    <label for="edit_terminal_name">Terminal Name:</label>
                    <input type="text" id="edit_terminal_name" name="terminal_name" required>
                </div>
                <button type="submit" name="update_terminal" class="btn-primary">Update Terminal</button>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openEditModal(pin, name) {
            const modal = document.getElementById('editModal');
            document.getElementById('edit_terminal_id').value = pin;
            document.getElementById('edit_pin').value = pin;
            document.getElementById('edit_terminal_name').value = name;
            modal.style.display = 'block';

            // Close modal when clicking outside content
            modal.onclick = function (event) {
                if (event.target === modal) {
                    closeEditModal();
                }
            };
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal with escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeEditModal();
            }
        });
    </script>
</body>

</html>