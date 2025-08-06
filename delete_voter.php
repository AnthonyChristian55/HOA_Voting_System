<?php
session_start();
include("./config/db.php");

// Check if admin is logged in
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

// Ensure voter ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_voter.php");
    exit();
}

$voter_id = $_GET['id'];

// Fetch voter details for confirmation
$query = "SELECT id, fname, lname FROM voters WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$result = $stmt->get_result();
$voter = $result->fetch_assoc();

if (!$voter) {
    header("Location: view_voter.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Voter</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .admin-container {
            width: 100%;
            max-width: 500px;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            color: #dc3545;
            margin-top: 0;
            margin-bottom: 20px;
        }

        p {
            color: #495057;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        strong {
            color: #212529;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        button[type="submit"] {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button[type="submit"]:hover {
            background-color: #c82333;
        }

        a {
            display: block;
            padding: 12px;
            background-color: #f8f9fa;
            color: #495057;
            text-decoration: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.2s;
            border: 1px solid #dee2e6;
        }

        a:hover {
            background-color: #e9ecef;
        }

        @media (max-width: 600px) {
            .admin-container {
                padding: 20px;
            }

            button[type="submit"],
            a {
                padding: 10px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <h2>Delete Voter</h2>
        <p>Are you sure you want to permanently delete
            <strong><?= htmlspecialchars($voter['fname'] . ' ' . $voter['lname']) ?></strong> from the system?</p>

        <form action="admin/delete_voter_process.php" method="POST">
            <input type="hidden" name="voter_id" value="<?= htmlspecialchars($voter['id']) ?>">
            <button type="submit" name="delete">Confirm Deletion</button>
            <a href="view_voter.php">Cancel</a>
        </form>
    </div>
</body>

</html>