<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

include("./config/db.php");

// Get candidate details
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('Invalid Candidate ID!'); window.location.href='dashboard.php';</script>";
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM candidates WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$candidate = $result->fetch_assoc();

if (!$candidate) {
    echo "<script>alert('Candidate not found!'); window.location.href='dashboard.php';</script>";
    exit();
}

// Set default image if no photo is available
$imagePath = (!empty($candidate['photo']) && file_exists("./uploads/candidates/" . $candidate['photo']))
    ? "./uploads/candidates/" . htmlspecialchars($candidate['photo'])
    : "./uploads/placeholder.jpg";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Candidate</title>
    <link rel="stylesheet" href="./css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="text-center">Update Candidate</h2>
        <form action="./admin/update_candidate_process.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $candidate['id'] ?>">
            <input type="hidden" name="current_photo" value="<?= htmlspecialchars($candidate['photo']) ?>">

            <div class="mb-3">
                <label for="fname" class="form-label">First Name:</label>
                <input type="text" name="fname" class="form-control"
                    value="<?= htmlspecialchars($candidate['fname']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="lname" class="form-label">Last Name:</label>
                <input type="text" name="lname" class="form-control"
                    value="<?= htmlspecialchars($candidate['lname']) ?>" required>
            </div>

            <!-- Display Current Candidate Image -->
            <div class="mb-3">
                <label class="form-label">Current Picture:</label>
                <div>
                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="Candidate Image" width="100" height="100"
                        class="rounded-circle border">
                </div>
            </div>

            <!-- Remove Current Image Option -->
            <div class="mb-3 form-check">
                <input type="checkbox" name="remove_photo" id="remove_photo" class="form-check-input">
                <label for="remove_photo" class="form-check-label">Remove current picture</label>
            </div>

            <!-- Upload New Image -->
            <div class="mb-3">
                <label for="photo" class="form-label">Upload New Picture:</label>
                <input type="file" name="photo" class="form-control" accept=".jpg, .jpeg, .png">
            </div>

            <button type="submit" class="btn btn-primary w-100">Update Candidate</button>
        </form>

        <div class="text-center mt-3">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</body>

</html>