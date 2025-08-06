<?php
session_start();
include("./config/db.php");

// Check if admin is logged in
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

$successMessage = $errorMessage = "";

// Fetch the latest logo
$query = "SELECT logo_path FROM picture_upload ORDER BY uploaded_at DESC LIMIT 1";
$result = $conn->query($query);
$row = $result->fetch_assoc();
$logoPath = $row['logo_path'] ?? 'default_logo.png'; // Default if no logo

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["logo"])) {
    $targetDir = "uploads/"; // Relative path from admin folder
    $fileName = time() . "_" . basename($_FILES["logo"]["name"]); // Prevent duplicate names
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // Allowed file types
    $allowedTypes = ["jpg", "jpeg", "png"];

    // Ensure uploads folder exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    if (in_array($fileType, $allowedTypes)) {
        if ($_FILES["logo"]["size"] < 2 * 1024 * 1024) { // 2MB limit
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], $targetFilePath)) {
                // Save to database
                $stmt = $conn->prepare("INSERT INTO picture_upload (logo_path, uploaded_at) VALUES (?, NOW())");
                $stmt->bind_param("s", $fileName);
                if ($stmt->execute()) {
                    $successMessage = "Logo updated successfully!";
                    $logoPath = $fileName; // Update displayed image
                } else {
                    $errorMessage = "Database error: Failed to save logo.";
                }
            } else {
                $errorMessage = "File upload failed.";
            }
        } else {
            $errorMessage = "File is too large. Max size: 2MB.";
        }
    } else {
        $errorMessage = "Invalid file type. Only JPG, JPEG, PNG allowed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Logo</title>
    <link rel="stylesheet" href="./css/styles.css">
</head>

<body>

    <div class="admin-container">
        <h2>Update System Logo</h2>

        <!-- Show current logo -->
        <div class="logo-preview">
            <img src="uploads/<?= htmlspecialchars($logoPath) ?>" alt="Current Logo" class="logo">
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($successMessage)): ?>
            <p class="success"><?= $successMessage; ?></p>
        <?php elseif (!empty($errorMessage)): ?>
            <p class="error"><?= $errorMessage; ?></p>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="logo-upload-container">
            <form action="update_logo.php" method="POST" enctype="multipart/form-data" class="logo-upload-form">
                <h3>Update Website Logo</h3>
                <div class="form-group">
                    <label for="logo">Select a new logo:</label>
                    <input type="file" name="logo" id="logo" accept=".jpg,.jpeg,.png" required>
                </div>
                <button type="submit" class="upload-btn">Upload Logo</button>
            </form>
        </div>

        <br>
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>

</body>

</html>