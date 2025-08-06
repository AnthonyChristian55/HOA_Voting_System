<?php
session_start();
include("./config/db.php");

// Ensure admin is logged in
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Preserve old input if there's an error
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

// Store session messages for pop-up
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// For form field repopulation
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Voter</title>
    <link rel="stylesheet" href="./css/styles.css">
</head>

<body>

    <div class="voter-management-container">
        <!-- Manual Voter Addition Section -->
        <div class="navigation">
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>
        <div class="voter-section manual-entry">
            <h2>Add Voter Manually</h2>

            <form action="admin/add_voters_process.php" method="POST" class="voter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="fname">First Name:</label>
                        <input type="text" name="fname" required value="<?= htmlspecialchars($old['fname'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="lname">Last Name:</label>
                        <input type="text" name="lname" required value="<?= htmlspecialchars($old['lname'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="birthday">Birthday:</label>
                        <input type="date" name="birthday" value="<?= htmlspecialchars($old['birthday'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="sex">Sex:</label>
                        <select name="sex">
                            <option value="Male" <?= (isset($old['sex']) && $old['sex'] == "Male") ? "selected" : "" ?>>
                                Male</option>
                            <option value="Female" <?= (isset($old['sex']) && $old['sex'] == "Female") ? "selected" : "" ?>>Female</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="block">Block:</label>
                        <input type="text" name="block" value="<?= htmlspecialchars($old['block'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="unit">Unit:</label>
                        <input type="text" name="unit" value="<?= htmlspecialchars($old['unit'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="voter_type">Voter Type:</label>
                        <select name="voter_type">
                            <option value="Homeowner" <?= (isset($old['voter_type']) && strtolower($old['voter_type']) == "homeowner") ? "selected" : "" ?>>Homeowner</option>
                            <option value="Proxy" <?= (isset($old['voter_type']) && strtolower($old['voter_type']) == "proxy") ? "selected" : "" ?>>Proxy</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select name="status">
                            <option value="In Good Standing" <?= (isset($old['status']) && $old['status'] == "In Good Standing") ? "selected" : "" ?>>In Good Standing</option>
                            <option value="Delinquent" <?= (isset($old['status']) && $old['status'] == "Delinquent") ? "selected" : "" ?>>Delinquent</option>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="contact_number">Contact Number:</label>
                    <input type="text" name="contact_number" pattern="\d{11}" maxlength="11"
                        title="Contact number must be exactly 11 digits"
                        value="<?= htmlspecialchars($old['contact_number'] ?? '') ?>">
                </div>

                <button type="submit" class="submit-btn">Add Voter</button>
            </form>
        </div>

        <!-- CSV Import Section -->
        <div class="voter-section csv-import">
            <h2>Import Voters from CSV</h2>

            <form action="admin/import_csv_process.php" method="POST" enctype="multipart/form-data" class="csv-form">
                <div class="form-group full-width">
                    <label for="csv_file">Upload CSV File:</label>
                    <input type="file" name="csv_file" accept=".csv" required>
                    <small>CSV format: First Name, Last Name, Birthday, Sex, Block, Unit, Voter Type, Contact Number,
                        Status</small>
                </div>

                <button type="submit" class="submit-btn">Import CSV</button>
            </form>
        </div>
    </div>

</body>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const successMessage = "<?= addslashes($successMessage) ?>";
        const errorMessage = "<?= addslashes($errorMessage) ?>";

        if (successMessage) {
            alert("SUCCESS: " + successMessage);
        }
        if (errorMessage) {
            alert("ERROR: " + errorMessage);
        }
    });
</script>

</html>