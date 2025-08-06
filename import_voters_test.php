<?php
session_start();
include("./config/db.php");

$conn->set_charset("utf8mb4");

// Function to generate a unique code for the household
function generateRandomCode()
{
    return strtoupper(bin2hex(random_bytes(4))); // 8-character alphanumeric code
}

// Function to validate and format contact numbers
function formatContactNumber($contact_number)
{
    $contact_number = trim($contact_number);
    if (empty($contact_number)) {
        return "";
    }

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

            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if (count($data) < 9) {
                    $errors[] = "Insufficient number of columns.";
                    continue;
                }

                list($fname, $lname, $block, $unit, $contact_number, $birthday, $sex, $voter_type, $status) = array_pad($data, 9, '');

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

                // Only assign a unique code if the status is NOT 'delinquent'
                $unique_code = ($status != 'Delinquent') ? generateRandomCode() : '';

                // Insert the voter data
                $query = "INSERT INTO voters_test (fname, lname, birthday, sex, block, unit, voter_type, unique_code, status, contact_number) 
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
                $update_codes_query = "SELECT block, unit FROM voters_test WHERE status != 'Delinquent' GROUP BY block, unit";
                $result = $conn->query($update_codes_query);

                while ($row = $result->fetch_assoc()) {
                    $block = $row['block'];
                    $unit = $row['unit'];

                    do {
                        $unique_code = generateRandomCode();

                        $check_query = "SELECT 1 FROM voters_test WHERE unique_code = ?";
                        $check_stmt = $conn->prepare($check_query);
                        $check_stmt->bind_param("s", $unique_code);
                        $check_stmt->execute();
                        $check_stmt->store_result();

                    } while ($check_stmt->num_rows > 0);

                    // Apply unique code to all voters in the household, except 'Delinquent' ones
                    $update_query = "UPDATE voters_test SET unique_code = ? WHERE block = ? AND unit = ? AND status != 'Delinquent'";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("sss", $unique_code, $block, $unit);
                    $update_stmt->execute();
                    $update_stmt->close();
                }

                $_SESSION['success_message'] = "$imported_rows voters imported successfully and codes updated!";
            }
        } else {
            $_SESSION['error_message'] = "Error opening the file.";
        }
    }

    header("Location: import_voters_test.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Voters Test Data</title>
    <link rel="stylesheet" href="./css/styles.css">
</head>

<body>

    <div class="container">
        <h2>Import Voters Test Data</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <p style="color: green;">
                <?= $_SESSION['success_message'];
                unset($_SESSION['success_message']); ?>
            </p>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <p style="color: red;">
                <?= $_SESSION['error_message'];
                unset($_SESSION['error_message']); ?>
            </p>
        <?php endif; ?>

        <form action="import_voters_test.php" method="POST" enctype="multipart/form-data">
            <label for="csv_file">Upload CSV File:</label>
            <input type="file" name="csv_file" accept=".csv" required>
            <button type="submit">Import</button>
        </form>

        <a href="view_voters_test.php">View Imported Voters</a>
    </div>

</body>

</html>