<?php
session_start();
include("./config/db.php");

// Check if admin is logged in
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

// Search feature logic
$search_query = "";
$search_category = "";
if (isset($_GET['search']) && isset($_GET['category'])) {
    $search_query = trim($_GET['search']);
    $search_category = $_GET['category'];

    // Define allowed categories
    $allowed_categories = ['fname', 'lname', 'block', 'unit'];

    if (!in_array($search_category, $allowed_categories)) {
        $_SESSION['error_message'] = "Invalid search category.";
        header("Location: view_voter.php");
        exit();
    }

    // Dynamic SQL query based on search category
    $query = "SELECT * FROM voters WHERE $search_category LIKE ?";
    $stmt = $conn->prepare($query);
    $search_param = "%" . $search_query . "%";
    $stmt->bind_param("s", $search_param);
} else {
    // Default: fetch all voters
    $query = "SELECT * FROM voters";
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Voters</title>
    <link rel="stylesheet" href="./css/styles.css">
    <link rel="stylesheet" href="./css/print_voter_code.css" media="print">
</head>

<body>
    <div class="voter-list-container">
        <div class="voter-list-header">
            <h2>Voter List</h2>
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <!-- Status Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert success">
                <?= $_SESSION['success_message'];
                unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert error">
                <?= $_SESSION['error_message'];
                unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Search Form -->
        <div class="search-container">
            <form method="GET" action="view_voter.php" class="search-form">
                <div class="search-row">
                    <select name="category" required class="search-select">
                        <option value="fname" <?= $search_category == "fname" ? "selected" : "" ?>>First Name</option>
                        <option value="lname" <?= $search_category == "lname" ? "selected" : "" ?>>Last Name</option>
                        <option value="block" <?= $search_category == "block" ? "selected" : "" ?>>Block</option>
                        <option value="unit" <?= $search_category == "unit" ? "selected" : "" ?>>Unit</option>
                    </select>

                    <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>"
                        placeholder="Enter search term" required class="search-input">

                    <div class="button-group">
                        <button type="submit" class="search-btn">Search</button>
                        <a href="view_voter.php" class="reset-btn">Reset Search</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Delete All Button -->
        <div class="action-container">
            <form method="POST" action="admin/delete_all_voters.php"
                onsubmit="return confirm('Are you sure you want to delete all voters?');">
                <button type="submit" class="delete-all-btn">
                    Delete All Records
                </button>
            </form>
            <form method="POST" action="admin/export_voters.php">
                <button type="submit" class="export-excel-btn">
                    Export to Excel
                </button>
            </form>
        </div>

        <!-- Voter Table -->
        <div class="table-wrapper">
            <table class="voter-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Birthday</th>
                        <th>Sex</th>
                        <th>Block</th>
                        <th>Unit</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Contact</th>
                        <th>Code</th>
                        <th>Code Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td><?= htmlspecialchars($row["fname"]) ?></td>
                            <td><?= htmlspecialchars($row["lname"]) ?></td>
                            <td><?= htmlspecialchars($row["birthday"]) ?></td>
                            <td><?= htmlspecialchars($row["sex"]) ?></td>
                            <td><?= htmlspecialchars($row["block"]) ?></td>
                            <td><?= htmlspecialchars($row["unit"]) ?></td>
                            <td><?= htmlspecialchars($row["voter_type"]) ?></td>
                            <td><?= htmlspecialchars($row["status"]) ?></td>
                            <td><?= htmlspecialchars($row["contact_number"]) ?></td>
                            <td><?= htmlspecialchars($row["unique_code"]) ?></td>
                            <td><?= htmlspecialchars($row["code_status"]) ?></td>
                            <td class="actions">
                                <div class="action-buttons">
                                    <a href="edit_voter.php?id=<?= $row['id'] ?>" class="btn-edit">Edit</a>
                                    <a href="delete_voter.php?id=<?= $row['id'] ?>" class="btn-delete"
                                        onclick="return confirm('Are you sure?')">Delete</a>
                                    <button
                                        onclick="printVoterCode('<?= htmlspecialchars($row['fname']) ?>', '<?= htmlspecialchars($row['lname']) ?>', '<?= htmlspecialchars($row['block']) ?>', '<?= htmlspecialchars($row['unit']) ?>', '<?= htmlspecialchars($row['unique_code']) ?>')"
                                        class="btn-print" title="Print">
                                        Print
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function printVoterCode(fname, lname, block, unit, uniqueCode) {
            let printWindow = window.open('', '', 'width=400,height=300');

            printWindow.document.write(`
        <html>
        <head>
            <title>Voter Code</title>
            <link rel="stylesheet" href="./css/print_voter_code.css" media="print">
            <style>
                @media screen {
                    body.voter-code-print {
                        display: block !important;
                    }
                }
            </style>
        </head>
        <body class="voter-code-print">
            <div class="voter-code-container">
                <h2>Voter's Information</h2>
                <p><strong>Name:</strong> ${fname} ${lname}</p>
                <p><strong>Block:</strong> ${block}</p>
                <p><strong>Unit:</strong> ${unit}</p>
                <p><strong>Unique Code:</strong></p>
                <div class="unique-code-display">${uniqueCode}</div>
                <div class="print-footer">Enter this code to vote.</div>
            </div>
            <script>
                window.onload = function() { 
                    window.print(); 
                    setTimeout(function() {
                        window.close();
                    }, 100);
                }
            <\/script>
        </body>
        </html>
    `);
            printWindow.document.close();
        }
    </script>

</body>

</html>