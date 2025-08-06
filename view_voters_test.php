<?php
session_start();
include("./config/db.php"); // Database connection

// Handle delete request
if (isset($_POST['delete_all'])) {
    $deleteQuery = "DELETE FROM voters_test";
    if ($conn->query($deleteQuery) === TRUE) {
        $_SESSION['success_message'] = "All records deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting records: " . $conn->error;
    }
    header("Location: view_voters_test.php");
    exit();
}

// Fetch records
$query = "SELECT * FROM voters_test ORDER BY id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Imported Voters (Test Table)</title>
    <link rel="stylesheet" href="./css/styles.css">
</head>

<body>

    <div class="admin-container">
        <h2>Imported Voters (Test Table)</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <p style="color: green;"><?= $_SESSION['success_message'];
            unset($_SESSION['success_message']); ?></p>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <p style="color: red;"><?= $_SESSION['error_message'];
            unset($_SESSION['error_message']); ?></p>
        <?php endif; ?>

        <form method="post" onsubmit="return confirm('Are you sure you want to delete all records?');">
            <button type="submit" name="delete_all"
                style="background-color: red; color: white; padding: 10px; border: none; cursor: pointer;">
                Delete All Records
            </button>
        </form>

        <table border="1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Birthday</th>
                    <th>Sex</th>
                    <th>Block</th>
                    <th>Unit</th>
                    <th>Voter Type</th>
                    <th>Unique Code</th>
                    <th>Status</th>
                    <th>Contact Number</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rowCount = 1; // Initialize row number
                while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $rowCount++; ?></td> <!-- Use row number instead of database ID -->
                        <td><?= htmlspecialchars($row['fname']) ?></td>
                        <td><?= htmlspecialchars($row['lname']) ?></td>
                        <td><?= htmlspecialchars($row['birthday']) ?></td>
                        <td><?= htmlspecialchars($row['sex']) ?></td>
                        <td><?= htmlspecialchars($row['block']) ?></td>
                        <td><?= htmlspecialchars($row['unit']) ?></td>
                        <td><?= htmlspecialchars($row['voter_type']) ?></td>
                        <td><?= htmlspecialchars($row['unique_code']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                        <td><?= htmlspecialchars($row['contact_number']) ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>

        </table>

        <a href="import_voters_test.php">Back to Import</a>
    </div>

</body>

</html>