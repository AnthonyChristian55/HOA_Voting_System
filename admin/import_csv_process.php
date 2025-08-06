<?php
session_start();
include("../config/db.php");

$conn->set_charset("utf8mb4");

// Function to generate a unique code for the household
function generateRandomCode()
{
    return strtoupper(bin2hex(random_bytes(4))); // 8-character alphanumeric code
}
function formatContactNumber($contact_number)
{
    if (!isset($contact_number) || trim($contact_number) === '') {
        return ""; // Empty contact number is allowed
    }

    $contact_number = trim($contact_number);

    // 🔍 Check if the value looks like a birthdate (DD/MM/YYYY or MM/DD/YYYY)
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $contact_number)) {
        return false; // Treat it as invalid (since it's a birthdate)
    }

    // Process phone numbers as usual
    $numbers = explode("/", $contact_number);
    $formatted_numbers = [];

    foreach ($numbers as $num) {
        $num = trim($num);
        if (preg_match('/^\d+(\.\d+)?E\+\d+$/i', $num)) {
            $num = sprintf('%.0f', $num);
        }

        $num = preg_replace('/[^0-9+]/', '', $num);

        if (preg_match('/^09\d{9}$/', $num) || preg_match('/^9\d{9}$/', $num)) {
        } elseif (preg_match('/^63\d{10}$/', $num)) {
        } elseif (preg_match('/^\+\d{10,15}$/', $num)) {
        } elseif (preg_match('/^[1-9]\d{9,14}$/', $num)) {
        } elseif (preg_match('/^\d{6,8}$/', $num)) {
            $num = "Landline: " . $num;
        } else {
            return false;
        }

        $formatted_numbers[] = $num;
    }

    return implode(" / ", $formatted_numbers);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csv_file"])) {
    $file = $_FILES["csv_file"]["tmp_name"];

    if (!file_exists($file)) {
        $_SESSION['error_message'] = "No file uploaded.";
    } elseif (mime_content_type($file) !== "text/plain" && mime_content_type($file) !== "text/csv") {
        $_SESSION['error_message'] = "Invalid file type. Please upload a CSV file.";
    } else {
        $handle = fopen($file, "r");
        stream_filter_prepend($handle, "convert.iconv.ISO-8859-1/UTF-8");
        if ($handle !== false) {
            fgetcsv($handle);

            $imported_rows = 0;
            $errors = [];
            $name_code_map = []; // To store name combinations and their codes

            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if (count($data) < 9) {
                    $errors[] = "Insufficient number of columns.";
                    continue;
                }

                // Ensure all variables are assigned correctly
                list($fname, $lname, $block, $unit, $contact_number, $birthday, $sex, $voter_type, $status) = $data;

                $contact_number = trim($contact_number); // Ensure it's not null
                $formatted_contact = formatContactNumber($contact_number);

                if ($formatted_contact === false) {
                    $errors[] = "Invalid contact number: $contact_number";
                    continue;
                }

                $fname = mb_convert_encoding(trim($fname), "UTF-8", "auto");
                $lname = mb_convert_encoding(trim($lname), "UTF-8", "auto");
                $block = mb_convert_encoding(trim($block), "UTF-8", "auto");
                $unit = mb_convert_encoding(trim($unit), "UTF-8", "auto");
                $contact_number = mb_convert_encoding(trim($contact_number), "UTF-8", "auto");
                $birthday = mb_convert_encoding(trim($birthday), "UTF-8", "auto");
                $sex = mb_convert_encoding(trim($sex), "UTF-8", "auto");
                $voter_type = mb_convert_encoding(trim($voter_type), "UTF-8", "auto");
                $status = mb_convert_encoding(trim($status), "UTF-8", "auto");

                $formatted_contact = formatContactNumber($contact_number);
                if ($formatted_contact === false) {
                    $errors[] = "Invalid contact number: $contact_number";
                    continue;
                }

                if (!empty($sex) && !in_array($sex, ["Male", "Female"])) {
                    $errors[] = "Invalid sex value for $fname $lname: $sex (should be 'Male' or 'Female')";
                    continue;
                }

                // Check if this name combination already exists in the database or in current import
                $name_key = strtolower($fname . '|' . $lname);
                $existing_code = null;

                // First check if we've already processed this name in current import
                if (isset($name_code_map[$name_key])) {
                    $existing_code = $name_code_map[$name_key];
                } else {
                    // Check if this name exists in the database
                    $check_name_query = "SELECT unique_code FROM voters WHERE LOWER(fname) = LOWER(?) AND LOWER(lname) = LOWER(?) AND status != 'Delinquent' LIMIT 1";
                    $check_stmt = $conn->prepare($check_name_query);
                    $check_stmt->bind_param("ss", $fname, $lname);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();

                    if ($check_result->num_rows > 0) {
                        $row = $check_result->fetch_assoc();
                        $existing_code = $row['unique_code'];
                        $name_code_map[$name_key] = $existing_code; // Store for future reference in this import
                    }
                    $check_stmt->close();
                }

                // Only assign a unique code if the status is NOT 'delinquent'
                $unique_code = ($status != 'Delinquent') ?
                    ($existing_code ? $existing_code : generateRandomCode()) : '';

                // Store the code for this name if it's new
                if ($status != 'Delinquent' && !$existing_code) {
                    $name_code_map[$name_key] = $unique_code;
                }

                // Insert the voter data
                $query = "INSERT INTO voters (fname, lname, birthday, sex, block, unit, voter_type, unique_code, status, contact_number) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssssssss", $fname, $lname, $birthday, $sex, $block, $unit, $voter_type, $unique_code, $status, $formatted_contact);

                if ($stmt->execute()) {
                    $imported_rows++;
                } else {
                    $errors[] = "Error inserting voter data: $fname $lname";
                }

                $stmt->close();
            }

            fclose($handle);

            if (!empty($errors)) {
                $_SESSION['error_message'] = implode("<br>", $errors);
            } else {
                // Update unique_code for households, excluding 'Delinquent' voters
                $update_codes_query = "SELECT block, unit FROM voters WHERE status != 'Delinquent' GROUP BY block, unit";
                $result = $conn->query($update_codes_query);

                while ($row = $result->fetch_assoc()) {
                    $block = $row['block'];
                    $unit = $row['unit'];

                    do {
                        $unique_code = generateRandomCode();

                        $check_query = "SELECT 1 FROM voters WHERE unique_code = ?";
                        $check_stmt = $conn->prepare($check_query);
                        $check_stmt->bind_param("s", $unique_code);
                        $check_stmt->execute();
                        $check_stmt->store_result();

                    } while ($check_stmt->num_rows > 0);

                    // Apply unique code to all voters in the household, except 'Delinquent' ones
                    $update_query = "UPDATE voters SET unique_code = ? WHERE block = ? AND unit = ? AND status != 'Delinquent'";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("sss", $unique_code, $block, $unit);
                    $update_stmt->execute();
                    $update_stmt->close();
                }

                // Now ensure all voters with the same name have the same code (for voters who own multiple properties)
                $same_name_voters_query = "SELECT fname, lname, GROUP_CONCAT(DISTINCT unique_code) as codes 
                                         FROM voters 
                                         WHERE status != 'Delinquent' 
                                         GROUP BY LOWER(fname), LOWER(lname) 
                                         HAVING COUNT(DISTINCT unique_code) > 1";
                $same_name_result = $conn->query($same_name_voters_query);

                while ($row = $same_name_result->fetch_assoc()) {
                    $fname = $row['fname'];
                    $lname = $row['lname'];
                    $codes = explode(',', $row['codes']);
                    $primary_code = $codes[0]; // Use the first code as the standard

                    // Update all records with this name to use the primary code
                    $update_name_code_query = "UPDATE voters SET unique_code = ? 
                                             WHERE LOWER(fname) = LOWER(?) AND LOWER(lname) = LOWER(?) 
                                             AND status != 'Delinquent'";
                    $update_name_stmt = $conn->prepare($update_name_code_query);
                    $update_name_stmt->bind_param("sss", $primary_code, $fname, $lname);
                    $update_name_stmt->execute();
                    $update_name_stmt->close();
                }

                $_SESSION['success_message'] = "$imported_rows voters imported successfully and codes updated!";
            }
        } else {
            $_SESSION['error_message'] = "Error opening the file.";
        }
    }

    header("Location: ../add_voter.php");
    exit();
}