<?php
session_start();
include("../config/db.php");

// Check if admin is logged in
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// Reuse the existing search logic from view_voter.php
$search_query = "";
$search_category = "";
if (isset($_GET['search']) && isset($_GET['category'])) {
    $search_query = trim($_GET['search']);
    $search_category = $_GET['category'];

    $allowed_categories = ['fname', 'lname', 'block', 'unit'];
    if (!in_array($search_category, $allowed_categories)) {
        $_SESSION['error_message'] = "Invalid search category.";
        header("Location: view_voter.php");
        exit();
    }

    $query = "SELECT * FROM voters WHERE $search_category LIKE ?";
    $stmt = $conn->prepare($query);
    $search_param = "%" . $search_query . "%";
    $stmt->bind_param("s", $search_param);
} else {
    $query = "SELECT * FROM voters";
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();

// Create CSV content instead of XML
$output = fopen('php://output', 'w');

// Set headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="voters_export_' . date('Y-m-d') . '.csv"');
header('Cache-Control: max-age=0');

// Write headers
fputcsv($output, array(
    'No.',
    'First Name',
    'Last Name',
    'Birthday',
    'Sex',
    'Block',
    'Unit',
    'Type',
    'Status',
    'Contact',
    'Code',
    'Code Status'
));

// Write data
$count = 1;
while ($row = $result->fetch_assoc()) {
    fputcsv($output, array(
        $count++,
        $row['fname'],
        $row['lname'],
        $row['birthday'],
        $row['sex'],
        $row['block'],
        $row['unit'],
        $row['voter_type'],
        $row['status'],
        $row['contact_number'],
        $row['unique_code'],
        $row['code_status']
    ));
}

fclose($output);
exit();
?>