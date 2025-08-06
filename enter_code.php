<?php
session_start();
include("./config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $voter_code = trim($_POST["voter_code"]);

    if (empty($voter_code)) {
        $_SESSION['error_message'] = "Please enter your voter code.";
    } else {
        // Check if the code exists and is either unused or is our test code
        $query = "SELECT * FROM voters WHERE unique_code = ? AND (code_status = 'unused' OR unique_code = '12345678')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $voter_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Store voter code in session but DO NOT mark it as used yet
            $_SESSION['voter_code'] = $voter_code;

            // Redirect to voting page
            header("Location: user.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Invalid or already used voter code.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Voter Code</title>
    <link rel="stylesheet" href="./css/styles.css">
    <style>
        input[type="text"] {
            width: 60%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            text-align: center;
            box-sizing: border-box;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Enter Your Voter Code</h2>

        <?php if (isset($_SESSION['error_message'])): ?>
            <p style="color: red;"><?= $_SESSION['error_message']; ?></p>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <form method="POST" action="enter_code.php" class="voter-form">
            <label for="voter_code">Voter Code:</label>
            <input type="text" name="voter_code" id="voter_code" class="voter-input" required>
            <button type="submit" class="voter-btn">Proceed to Vote</button>
        </form>
        <div class="navigation">
            <a href="index.php" class="back-btn">← Back to Home</a>
        </div>
    </div>
</body>

</html>