<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $removePhoto = isset($_POST['remove_photo']); // Check if remove checkbox was checked

    // Check for duplicate candidate (case-insensitive)
    $checkStmt = $conn->prepare("SELECT id FROM candidates WHERE LOWER(fname) = LOWER(?) AND LOWER(lname) = LOWER(?) AND id != ?");
    $checkStmt->bind_param("ssi", $fname, $lname, $id);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        echo "<script>alert('Candidate with the same name already exists!'); window.history.back();</script>";
        exit();
    }
    $checkStmt->close();

    // Fetch current photo
    $photoQuery = $conn->prepare("SELECT photo FROM candidates WHERE id = ?");
    $photoQuery->bind_param("i", $id);
    $photoQuery->execute();
    $photoQuery->bind_result($currentPhoto);
    $photoQuery->fetch();
    $photoQuery->close();

    $updatePhoto = false; // Flag to check if photo should be updated
    $targetDir = "../uploads/candidates/";
    $fileName = $currentPhoto; // Default to current photo

    // Handle Image Removal
    if ($removePhoto) {
        if (!empty($currentPhoto) && file_exists($targetDir . $currentPhoto) && $currentPhoto !== "placeholder.jpg") {
            unlink($targetDir . $currentPhoto); // Delete the existing photo
        }
        $fileName = "placeholder.jpg"; // Set to placeholder
        $updatePhoto = true;
    }

    // Check if a new file was uploaded
    if (!empty($_FILES["photo"]["name"])) {
        $fileExt = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "jpeg", "png"];

        // Validate file type
        if (!in_array($fileExt, $allowedTypes)) {
            echo "<script>alert('Invalid file type! Only JPG, JPEG, and PNG are allowed.'); window.history.back();</script>";
            exit();
        }

        // Validate file size (max 2MB)
        if ($_FILES["photo"]["size"] > 2 * 1024 * 1024) {
            echo "<script>alert('File size too large! Max allowed size is 2MB.'); window.history.back();</script>";
            exit();
        }

        // Generate a unique filename
        $fileName = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES["photo"]["name"]));
        $targetFilePath = $targetDir . $fileName;

        // Move uploaded file
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFilePath)) {
            $updatePhoto = true;

            // Delete old image if it exists and is not a placeholder
            if (!empty($currentPhoto) && file_exists($targetDir . $currentPhoto) && $currentPhoto !== "placeholder.jpg") {
                unlink($targetDir . $currentPhoto);
            }
        } else {
            echo "<script>alert('Error uploading new image. Please try again.'); window.history.back();</script>";
            exit();
        }
    }

    // Prepare SQL query based on changes
    if ($updatePhoto) {
        $stmt = $conn->prepare("UPDATE candidates SET fname = ?, lname = ?, photo = ? WHERE id = ?");
        $stmt->bind_param("sssi", $fname, $lname, $fileName, $id);
    } else {
        $stmt = $conn->prepare("UPDATE candidates SET fname = ?, lname = ? WHERE id = ?");
        $stmt->bind_param("ssi", $fname, $lname, $id);
    }

    // Execute query
    if ($stmt->execute()) {
        echo "<script>alert('Candidate updated successfully!'); window.location.href = '../update_candidate.php?id=$id';</script>";
    } else {
        echo "<script>alert('Error updating candidate.'); window.history.back();</script>";
    }

    $stmt->close();
}

$conn->close();
?>