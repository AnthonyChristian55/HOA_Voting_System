<?php
ob_start();
session_start();
include("../config/db.php");

// Set timezone to Philippine Time (Asia/Manila)
date_default_timezone_set('Asia/Manila');

// Ensure voter session exists AND terminal is verified
if (!isset($_SESSION['voter_code']) || !isset($_SESSION['valid_pin'])) {
    die("Error: Session expired or terminal not verified. Please start again.");
}

$voter_code = $_SESSION['voter_code']; // Get voter code from session
$terminal_pin = $_SESSION['valid_pin']; // Get terminal PIN from session

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['candidates'])) {
    $selectedCandidates = $_POST['candidates'];

    // Fetch vote settings
    $settings_result = $conn->query("SELECT min_votes, max_votes FROM settings LIMIT 1");
    if (!$settings_result || $settings_result->num_rows == 0) {
        die("Error: Vote settings not found. Please contact the administrator.");
    }

    $settings = $settings_result->fetch_assoc();
    $min_votes = (int) $settings['min_votes'];
    $max_votes = (int) $settings['max_votes'];

    // Validate vote count
    $voteCount = count($selectedCandidates);
    if ($voteCount < $min_votes || $voteCount > $max_votes) {
        die("Invalid number of votes. Please select between $min_votes and $max_votes candidates.");
    }

    // Validate candidate IDs (ensure only valid integers are allowed)
    $validCandidates = [];
    foreach ($selectedCandidates as $candidateID) {
        if (ctype_digit($candidateID)) { // Ensure numeric values only
            $validCandidates[] = (int) $candidateID;
        }
    }

    if (empty($validCandidates)) {
        die("Invalid candidate selection.");
    }

    // Check if voter has already voted
    $stmt = $conn->prepare("SELECT code_status FROM voters WHERE unique_code = ? LIMIT 1");
    if (!$stmt) {
        die("Error: Database query failed.");
    }

    $stmt->bind_param("s", $voter_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Error: Invalid voter code.");
    }

    $row = $result->fetch_assoc();
    if ($row['code_status'] === 'used' && $voter_code !== '12345678') {
        die("Error: You have already voted.");
    }

    // Verify terminal exists
    $stmt = $conn->prepare("SELECT terminal_name FROM terminal WHERE pin = ?");
    $stmt->bind_param("s", $terminal_pin);
    $stmt->execute();
    $result = $stmt->get_result();
    $terminal = $result->fetch_assoc();

    if (!$terminal) {
        die("Error: Invalid terminal session.");
    }
    $stmt->close();

    // **START TRANSACTION** - Prevents Partial Updates
    $conn->begin_transaction();

    try {
        // Generate timestamp in Philippine Time
        $timestamp = date("Y-m-d H:i:s");

        // Insert a new vote record (using terminal PIN)
        $stmt = $conn->prepare("INSERT INTO votes (timestamp, terminal_id) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception("Error preparing vote insert.");
        }

        $stmt->bind_param("ss", $timestamp, $terminal_pin);
        $stmt->execute();
        $vote_id = $conn->insert_id; // Get last inserted vote ID

        if (!$vote_id) {
            throw new Exception("Error recording vote.");
        }

        // Generate unique voting number (format: YYYYMMDD-HHMM-XXXXX)
        $datePart = date("Ymd"); // YYYYMMDD
        $timePart = date("Hi");  // HHMM
        $uniqueVotingNumber = "$datePart-$timePart-$vote_id";

        // Update voting number after getting a unique ID
        $stmt = $conn->prepare("UPDATE votes SET voting_number = ? WHERE id = ?");
        $stmt->bind_param("si", $uniqueVotingNumber, $vote_id);
        $stmt->execute();

        // Store selected candidates in vote_details
        $stmt = $conn->prepare("INSERT INTO vote_details (vote_id, candidate_id) VALUES (?, ?)");
        foreach ($validCandidates as $candidateID) {
            $stmt->bind_param("ii", $vote_id, $candidateID);
            $stmt->execute();
        }

        // Store votes for each selected candidate
        $stmt = $conn->prepare("UPDATE candidates SET votes = votes + 1 WHERE id = ?");
        foreach ($validCandidates as $candidateID) {
            $stmt->bind_param("i", $candidateID);
            $stmt->execute();
        }

        // Mark voter as "used" after successful vote submission (except for test code)
        if ($voter_code !== '12345678') {
            $stmt = $conn->prepare("UPDATE voters SET code_status = 'used' WHERE unique_code = ?");
            $stmt->bind_param("s", $voter_code);
            $stmt->execute();
        }

        // Commit the transaction (all changes are saved)
        $conn->commit();

        // Store unique voting number in session
        $_SESSION['voting_number'] = $uniqueVotingNumber;

        // Redirect to confirmation page
        header("Location: ../vote_success.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // Revert all changes if something goes wrong
        die("Error: " . $e->getMessage());
    }
} else {
    die("No candidates selected.");
}
?>