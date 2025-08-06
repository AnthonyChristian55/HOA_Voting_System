<?php
session_start();
include("./config/db.php");

// Check if admin is logged in
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

// Ensure ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: view_voter.php");
    exit();
}

$voter_id = $_GET['id'];

// Fetch voter details
$query = "SELECT id, fname, lname, birthday, sex, block, unit, voter_type, contact_number FROM voters WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$result = $stmt->get_result();
$voter = $result->fetch_assoc();

if (!$voter) {
    header("Location: view_voter.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $birthday = trim($_POST['birthday']) ?: NULL;
    $sex = trim($_POST['sex']) ?: NULL;
    $block = trim($_POST['block']) ?: NULL;
    $unit = trim($_POST['unit']) ?: NULL;
    $voter_type = trim($_POST['voter_type']) ?: NULL;
    $contact_number = trim($_POST['contact_number']) ?: NULL;

    // Validate required fields (only first and last name)
    if (empty($fname) || empty($lname)) {
        $_SESSION['error_message'] = "First name and last name are required.";
        header("Location: edit_voter.php?id=$voter_id");
        exit();
    }

    // Validate Contact Number (must be exactly 11 digits if provided)
    if (!empty($contact_number) && !preg_match('/^\d{11}$/', $contact_number)) {
        $_SESSION['error_message'] = "Contact number must be exactly 11 digits.";
        header("Location: edit_voter.php?id=$voter_id");
        exit();
    }

    // Update query (status removed from the query)
    $update_query = "UPDATE voters SET fname=?, lname=?, birthday=?, sex=?, block=?, unit=?, voter_type=?, contact_number=? WHERE id=?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssssssi", $fname, $lname, $birthday, $sex, $block, $unit, $voter_type, $contact_number, $voter_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Voter updated successfully!";
        header("Location: view_voter.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating voter.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Voter</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .admin-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h2 {
            margin-top: 0;
            color: #333;
            text-align: center;
        }

        .error-message {
            color: #d32f2f;
            background-color: #fde8e8;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-weight: bold;
            color: #555;
        }

        input,
        select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        input:focus,
        select:focus {
            border-color: #4a90e2;
            outline: none;
        }

        button {
            padding: 12px;
            background-color: #4a90e2;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #357ab8;
        }

        a {
            display: inline-block;
            padding: 12px;
            text-align: center;
            background-color: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        a:hover {
            background-color: #e0e0e0;
        }

        @media (max-width: 600px) {
            .admin-container {
                padding: 15px;
            }

            input,
            select,
            button,
            a {
                padding: 8px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <h2>Edit Voter</h2>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message"><?= $_SESSION['error_message'];
            unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <label>First Name:</label>
            <input type="text" name="fname" value="<?= htmlspecialchars($voter['fname']) ?>" required>

            <label>Last Name:</label>
            <input type="text" name="lname" value="<?= htmlspecialchars($voter['lname']) ?>" required>

            <label>Birthday:</label>
            <input type="date" name="birthday" value="<?= htmlspecialchars($voter['birthday']) ?>">

            <label>Sex:</label>
            <select name="sex">
                <option value="" <?= empty($voter['sex']) ? 'selected' : '' ?>>Select</option>
                <option value="Male" <?= $voter['sex'] == 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= $voter['sex'] == 'Female' ? 'selected' : '' ?>>Female</option>
            </select>

            <label>Block:</label>
            <input type="text" name="block" value="<?= htmlspecialchars($voter['block']) ?>">

            <label>Unit:</label>
            <input type="text" name="unit" value="<?= htmlspecialchars($voter['unit']) ?>">

            <label>Voter Type:</label>
            <select name="voter_type">
                <option value="" <?= empty($voter['voter_type']) ? 'selected' : '' ?>>Select</option>
                <option value="Homeowner" <?= $voter['voter_type'] == 'Homeowner' ? 'selected' : '' ?>>Homeowner</option>
                <option value="Proxy" <?= $voter['voter_type'] == 'Proxy' ? 'selected' : '' ?>>Proxy</option>
            </select>

            <label>Contact Number:</label>
            <input type="text" name="contact_number" value="<?= htmlspecialchars($voter['contact_number']) ?>"
                pattern="\d{11}" title="Enter an 11-digit contact number">

            <button type="submit">Update Voter</button>
            <a href="view_voter.php">Cancel</a>
        </form>
    </div>
</body>

</html>