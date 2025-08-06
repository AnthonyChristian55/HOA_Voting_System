<?php
session_start();
include("./config/db.php");

// Check if the user has entered a voter code
if (!isset($_SESSION['voter_code'])) {
    header("Location: enter_code.php?error=Invalid Access");
    exit();
}

$voter_code = $_SESSION['voter_code'];

// Verify if the voter code exists
$stmt = $conn->prepare("SELECT * FROM voters WHERE unique_code = ?");
$stmt->bind_param("s", $voter_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    session_destroy();
    header("Location: enter_code.php?error=Invalid Voter Code");
    exit();
}

// Fetch voting settings
$settings_stmt = $conn->query("SELECT min_votes, max_votes FROM settings LIMIT 1");
$settings = $settings_stmt->fetch_assoc();

$min_votes = $settings ? (int) $settings['min_votes'] : 1;
$max_votes = $settings ? (int) $settings['max_votes'] : 5;

// Fetch candidates ordered by last name (lname) - THIS IS THE ONLY CHANGE
$candidates_stmt = $conn->query("SELECT id, fname, lname, photo FROM candidates ORDER BY lname ASC");
if (!$candidates_stmt) {
    die("Error fetching candidates: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Voting</title>
    <link rel="stylesheet" href="./css/voting_page.css">
</head>

<body>
    <div class="vote-container">
        <h2>Vote for Candidates</h2>
        <form action="user/submit_vote.php" method="POST" onsubmit="return validateVotes();">
            <p class="vote-instructions">Minimum number of candidates to vote:
                <strong><?= $min_votes ?></strong><br>Maximum number of
                candidates to vote:
                <strong><?= $max_votes ?></strong>
            </p>

            <div class="candidates-grid">
                <?php
                while ($row = $candidates_stmt->fetch_assoc()) {
                    $imagePath = !empty($row['photo']) ? "./uploads/candidates/" . $row['photo'] : "./uploads/placeholder.jpg";
                    ?>
                    <label class="candidate-card">
                        <input type="checkbox" name="candidates[]" value="<?= $row['id'] ?>" class="card-checkbox">
                        <div class="card-content">
                            <div class="candidate-image">
                                <img src="<?= htmlspecialchars($imagePath) ?>" alt="Candidate Image"
                                    onerror="this.onerror=null; this.src='./uploads/placeholder.jpg';">
                            </div>
                            <div class="candidate-info">
                                <h3><?= htmlspecialchars($row['lname'] . ", " . $row['fname']) ?></h3>
                            </div>
                        </div>
                    </label>
                <?php } ?>
            </div>

            <button type="submit" class="submit-vote-btn">Submit Vote</button>
        </form>
    </div>

    <script>
        function validateVotes() {
            let checkboxes = document.querySelectorAll('input[name="candidates[]"]:checked');
            let minVotes = <?= $min_votes ?>;
            let maxVotes = <?= $max_votes ?>;

            if (checkboxes.length < minVotes || checkboxes.length > maxVotes) {
                alert("Please select a minimum of " + minVotes + " candidates and a maximum of " + maxVotes + " candidates.");
                return false;
            }
            return true;
        }

        // Add visual feedback when selecting candidates
        document.querySelectorAll('.candidate-card').forEach(card => {
            card.addEventListener('click', function () {
                const checkbox = this.querySelector('.card-checkbox');
                checkbox.checked = !checkbox.checked;
                this.classList.toggle('selected', checkbox.checked);
            });
        });
    </script>
</body>

</html>