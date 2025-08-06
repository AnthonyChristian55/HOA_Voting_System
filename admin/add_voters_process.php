<?php
session_start();
include("../config/db.php");

// Ensure admin is logged in
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Function to generate an 8-character unique voting code
function generateVotingCode($conn)
{
    do {
        $code = strtoupper(substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8));
        $check = $conn->query("SELECT 1 FROM voters WHERE unique_code = '$code'");
    } while ($check->num_rows > 0);

    return $code;
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize input
    $fname = trim($conn->real_escape_string($_POST['fname']));
    $lname = trim($conn->real_escape_string($_POST['lname']));

    // Optional fields (can be empty)
    $birthday = isset($_POST['birthday']) ? trim($conn->real_escape_string($_POST['birthday'])) : null;
    $sex = isset($_POST['sex']) ? trim($conn->real_escape_string($_POST['sex'])) : null;
    $block = isset($_POST['block']) ? trim($conn->real_escape_string($_POST['block'])) : null;
    $unit = isset($_POST['unit']) ? trim($conn->real_escape_string($_POST['unit'])) : null;
    $voter_type = isset($_POST['voter_type']) ? trim($conn->real_escape_string($_POST['voter_type'])) : null;
    $status = isset($_POST['status']) ? trim($conn->real_escape_string($_POST['status'])) : null;
    $contact_number = isset($_POST['contact_number']) ? trim($conn->real_escape_string($_POST['contact_number'])) : null;

    // Ensure required fields (fname and lname) are not empty
    if (empty($fname) || empty($lname)) {
        $_SESSION['error'] = "First Name and Last Name are required.";
        header("Location: ../add_voter.php");
        exit();
    }

    // Validate Contact Number (must be exactly 11 digits, if provided)
    if (!empty($contact_number) && !preg_match('/^\d{11}$/', $contact_number)) {
        $_SESSION['error'] = "Contact number must be exactly 11 digits.";
        header("Location: ../add_voter.php");
        exit();
    }

    // Normalize case and format (only if fields are provided)
    $sex = !empty($sex) ? ucfirst(strtolower($sex)) : null;
    $voter_type = !empty($voter_type) ? ucwords(strtolower($voter_type)) : null;
    $status = !empty($status) ? ucwords(strtolower($status)) : null;

    // Validate dropdown selections if they are provided
    if (!empty($sex) && !in_array($sex, ["Male", "Female"])) {
        $_SESSION['error'] = "Invalid sex selection.";
        header("Location: ../add_voter.php");
        exit();
    }
    if (!empty($voter_type) && !in_array($voter_type, ["Homeowner", "Proxy"])) {
        $_SESSION['error'] = "Invalid voter type selection.";
        header("Location: ../add_voter.php");
        exit();
    }
    if (!empty($status) && !in_array($status, ["In Good Standing", "Delinquent"])) {
        $_SESSION['error'] = "Invalid status selection.";
        header("Location: ../add_voter.php");
        exit();
    }

    // **Check for exact duplicate voter (including optional fields)**
    $duplicate_check = $conn->prepare("SELECT COUNT(*) FROM voters 
    WHERE LOWER(fname) = LOWER(?) AND LOWER(lname) = LOWER(?) AND 
    (birthday IS NULL OR birthday = ?) AND (sex IS NULL OR sex = ?) AND 
    (block IS NULL OR block = ?) AND (unit IS NULL OR unit = ?) AND 
    (voter_type IS NULL OR voter_type = ?) AND (status IS NULL OR status = ?) AND 
    (contact_number IS NULL OR contact_number = ?)");

    $duplicate_check->bind_param("sssssssss", $fname, $lname, $birthday, $sex, $block, $unit, $voter_type, $status, $contact_number);
    $duplicate_check->execute();
    $duplicate_check->bind_result($count);
    $duplicate_check->fetch();
    $duplicate_check->close();

    // After the duplicate check query executes
    if ($count > 0) {
        $_SESSION['error_message'] = "This voter with the same information already exists in the database.";
        $_SESSION['old'] = $_POST; // Preserve the form input
        $conn->close();
        header("Location: ../add_voter.php");
        exit();
    }

    // Determine unique code based on:
    // 1. Same household (block and unit) if provided
    // 2. Same name if household info not provided
    $unique_code = null;

    if (!empty($status) && $status === "In Good Standing") {
        // First check for existing voters in the same household (if block and unit are provided)
        if (!empty($block) && !empty($unit)) {
            $household_check = $conn->prepare("SELECT unique_code FROM voters 
                                            WHERE block = ? AND unit = ? 
                                            AND status = 'In Good Standing'
                                            AND unique_code IS NOT NULL
                                            LIMIT 1");
            $household_check->bind_param("ss", $block, $unit);
            $household_check->execute();
            $household_check->bind_result($existing_code);
            $household_check->fetch();
            $household_check->close();

            if ($existing_code) {
                $unique_code = $existing_code;
            }
        }

        // If no household match found, check for voters with same name
        if (empty($unique_code)) {
            $name_check = $conn->prepare("SELECT unique_code FROM voters 
                                       WHERE LOWER(fname) = LOWER(?) AND LOWER(lname) = LOWER(?)
                                       AND status = 'In Good Standing'
                                       AND unique_code IS NOT NULL
                                       LIMIT 1");
            $name_check->bind_param("ss", $fname, $lname);
            $name_check->execute();
            $name_check->bind_result($existing_code);
            $name_check->fetch();
            $name_check->close();

            if ($existing_code) {
                $unique_code = $existing_code;
            }
        }

        // If still no code found, generate a new one
        if (empty($unique_code)) {
            $unique_code = generateVotingCode($conn);
        }
    }

    // Insert into the voters table
    $stmt = $conn->prepare("INSERT INTO voters (fname, lname, birthday, sex, block, unit, voter_type, unique_code, status, contact_number) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $fname, $lname, $birthday, $sex, $block, $unit, $voter_type, $unique_code, $status, $contact_number);

    if ($stmt->execute()) {
        if (!empty($status) && $status === "Delinquent") {
            $_SESSION['error_message'] = "Voter is delinquent and cannot vote.";
        } else {
            $_SESSION['success_message'] = "Voter added successfully!" . ($unique_code ? " Unique Code: " . $unique_code : "");
        }
    } else {
        $_SESSION['error_message'] = "Error: " . $conn->error;
        $_SESSION['old'] = $_POST; // Preserve form input on error
    }
    $stmt->close();
    $conn->close();

    header("Location: ../add_voter.php");
    exit();
}