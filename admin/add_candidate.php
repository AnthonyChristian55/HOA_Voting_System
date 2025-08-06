<?php
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $uploadDir = "../uploads/candidates/"; // Directory to store images
    $defaultImage = "placeholder.jpg"; // Default image

    // Ensure names are provided
    if (empty($fname) || empty($lname)) {
        echo json_encode(["status" => "error", "message" => "First name and last name are required."]);
        exit();
    }

    $lowercaseFname = strtolower($fname);
    $lowercaseLname = strtolower($lname);

    // Check for duplicate (case-insensitive)
    $checkStmt = $conn->prepare("SELECT id FROM candidates WHERE LOWER(fname) = ? AND LOWER(lname) = ?");
    $checkStmt->bind_param("ss", $lowercaseFname, $lowercaseLname);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Candidate already exists."]);
        exit();
    }

    $imageName = $defaultImage; // Default image name

    // Handle Image Upload
    if (!empty($_FILES['photo']['name'])) {
        $imageName = time() . "_" . basename($_FILES['photo']['name']);
        $imagePath = $uploadDir . $imageName;
        $imageFileType = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowedTypes)) {
            echo json_encode(["status" => "error", "message" => "Only JPG, JPEG, PNG, and GIF files are allowed."]);
            exit();
        }

        // Ensure upload directory exists
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            echo json_encode(["status" => "error", "message" => "Failed to create upload directory."]);
            exit();
        }

        // Move uploaded file with error handling
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $imagePath)) {
            echo json_encode(["status" => "error", "message" => "Error uploading image. Please try again."]);
            exit();
        }

        // Double-check if file actually exists after upload
        if (!file_exists($imagePath)) {
            echo json_encode(["status" => "error", "message" => "Upload failed. File not found after upload attempt."]);
            exit();
        }
    }

    // Insert new candidate
    $stmt = $conn->prepare("INSERT INTO candidates (fname, lname, photo) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $fname, $lname, $imageName);

    if ($stmt->execute()) {
        // Get total candidates count
        $countResult = $conn->query("SELECT COUNT(*) AS total FROM candidates");
        $countRow = $countResult->fetch_assoc();
        $totalCandidates = $countRow['total'];

        echo json_encode([
            "status" => "success",
            "message" => "Candidate added successfully.",
            "totalCandidates" => $totalCandidates
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error adding candidate."]);
    }
    exit();
}
?>