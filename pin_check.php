<?php
session_start();
include("./config/db.php");

// Check if PIN is already validated
if (isset($_SESSION['valid_pin'])) {
    header('Location: index.php');
    exit;
}

// Process PIN submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'])) {
    $pin = $conn->real_escape_string($_POST['pin']);

    // Validate PIN against database
    $stmt = $conn->prepare("SELECT * FROM terminal WHERE pin = ?");
    $stmt->bind_param("s", $pin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Valid PIN
        $_SESSION['valid_pin'] = $pin;
        setcookie('terminal_pin', $pin, time() + (86400 * 30), "/"); // 30-day cookie
        header('Location: index.php');
        exit;
    } else {
        $error = "Invalid PIN. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Terminal Access</title>
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
        <div class="modal-content">
            <h1>Enter Terminal PIN</h1>
            <?php if (isset($error))
                echo "<p style='color:red'>$error</p>"; ?>
            <form method="POST">
                <input type="text" name="pin" placeholder="Enter 5-digit PIN" required pattern="\d{5}"
                    title="5-digit PIN" maxlength="5">
                <button type="submit" class="user-btn">Submit</button>
            </form>
        </div>
    </div>
</body>

</html>