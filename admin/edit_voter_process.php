<?php
session_start();
include("./config/db.php");

// Check if admin is logged in
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

// Check if update request was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update"])) {
    $voter_id = $_POST["id"];
    $fname = trim($_POST["fname"]);
    $lname = trim($_POST["lname"]);
    $birthday = $_POST["birthday"] ?? null;
    $sex = $_POST["sex"] ?? null;
    $block = trim($_POST["block"] ?? '');
    $unit = trim($_POST["unit"] ?? '');
    $voter_type = trim($_POST["voter_type"] ?? '');
    $status = $_POST["status"] ?? null;
    $contact_number = trim($_POST["contact_number"] ?? '');

    // Validate required fields (only first and last names)
    if (empty($fname) || empty($lname)) {
        $_SESSION['error_message'] = "First name and last name are required.";
        header("Location: edit_voter.php?id=" . urlencode($voter_id));
        exit();
    }

    // Validate Contact Number (if provided, must be exactly 11 digits)
    if (!empty($contact_number) && !preg_match('/^\d{11}$/', $contact_number)) {
        $_SESSION['error_message'] = "Contact number must be exactly 11 digits.";
        header("Location: edit_voter.php?id=" . urlencode($voter_id));
        exit();
    }

    // Prepare dynamic query to only update provided fields
    $query = "UPDATE voters SET fname = ?, lname = ?";
    $params = ["ss", &$fname, &$lname];

    if (!empty($birthday)) {
        $query .= ", birthday = ?";
        $params[0] .= "s";
        $params[] = &$birthday;
    }
    if (!empty($sex)) {
        $query .= ", sex = ?";
        $params[0] .= "s";
        $params[] = &$sex;
    }
    if (!empty($block)) {
        $query .= ", block = ?";
        $params[0] .= "s";
        $params[] = &$block;
    }
    if (!empty($unit)) {
        $query .= ", unit = ?";
        $params[0] .= "s";
        $params[] = &$unit;
    }
    if (!empty($voter_type)) {
        $query .= ", voter_type = ?";
        $params[0] .= "s";
        $params[] = &$voter_type;
    }
    if (!empty($status)) {
        $query .= ", status = ?";
        $params[0] .= "s";
        $params[] = &$status;
    }
    if (!empty($contact_number)) {
        $query .= ", contact_number = ?";
        $params[0] .= "s";
        $params[] = &$contact_number;
    }

    $query .= " WHERE id = ?";
    $params[0] .= "i";
    $params[] = &$voter_id;

    $stmt = $conn->prepare($query);
    call_user_func_array([$stmt, 'bind_param'], $params);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Voter information updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating voter information.";
    }

    $stmt->close();
    $conn->close();

    header("Location: view_voter.php");
    exit();
} else {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: view_voter.php");
    exit();
}
?>