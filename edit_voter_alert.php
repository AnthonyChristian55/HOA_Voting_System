<?php
session_start();
include("./config/db.php");

// Check if admin is logged in
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

// Get voter ID
$voter_id = $_GET['id'] ?? null;

// Fetch voter details
$query = "SELECT fname, lname FROM voters WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$result = $stmt->get_result();
$voter = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Updated</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <script>
        <?php if (isset($_SESSION['alert_message'])): ?>
            Swal.fire({
                title: 'Status Changed',
                html: '<?php echo $_SESSION['alert_message']; ?>',
                icon: '<?php echo $_SESSION['alert_type'] ?? 'success'; ?>',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'view_voter.php';
            });
            <?php
            unset($_SESSION['alert_message']);
            unset($_SESSION['alert_type']);
            unset($_SESSION['new_code']);
            ?>
        <?php else: ?>
            window.location.href = 'view_voter.php';
        <?php endif; ?>
    </script>
</body>

</html>