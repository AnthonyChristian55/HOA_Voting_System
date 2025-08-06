<?php
session_start();
include("config/db.php");

// Ensure session voting number exists and terminal is verified
if (!isset($_SESSION['voting_number']) || !isset($_SESSION['voter_code']) || !isset($_SESSION['valid_pin'])) {
    header("Location: index.php");
    exit();
}

$votingNumber = $_SESSION['voting_number'];
$voterCode = $_SESSION['voter_code'];

// First get the vote details (timestamp and terminal) - UPDATED to use terminal_id
$stmt = $conn->prepare("
    SELECT votes.timestamp, terminal.terminal_name
    FROM votes 
    LEFT JOIN terminal ON votes.terminal_id = terminal.pin
    WHERE votes.voting_number = ?
");
$stmt->bind_param("s", $votingNumber);
$stmt->execute();
$voteResult = $stmt->get_result();

if ($voteResult->num_rows === 0) {
    die("Error: Vote record not found.");
}

$voteRow = $voteResult->fetch_assoc();
$voteTimestamp = $voteRow['timestamp'];
$terminalName = $voteRow['terminal_name'] ?? "Unknown Terminal";

// Then get the candidates separately
$stmt = $conn->prepare("
    SELECT CONCAT(candidates.fname, ' ', candidates.lname) as candidate_name
    FROM vote_details
    JOIN candidates ON vote_details.candidate_id = candidates.id
    JOIN votes ON vote_details.vote_id = votes.id
    WHERE votes.voting_number = ?
    ORDER BY candidates.lname
");
$stmt->bind_param("s", $votingNumber);
$stmt->execute();
$candidateResult = $stmt->get_result();

$stmt->close();
$conn->close();

unset($_SESSION['voting_number']);
unset($_SESSION['voter_code']);
// Note: We keep $_SESSION['valid_pin'] for terminal persistence
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Success</title>
    <link rel="stylesheet" href="./css/print.css" media="print">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 90%;
            max-width: 600px;
            margin-bottom: 20px;
        }

        h2 {
            color: #2c3e50;
            text-align: center;
            margin-top: 0;
            margin-bottom: 20px;
        }

        p {
            margin: 10px 0;
            color: #333;
        }

        strong {
            color: #2c3e50;
        }

        .centered-list {
            margin: 15px 0;
        }

        ul {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
        }

        li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        li:last-child {
            border-bottom: none;
        }

        #printButton {
            background-color: #2980b9;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 10px 0;
            transition: background-color 0.3s;
        }

        #printButton:hover {
            background-color: #3498db;
        }

        #homeLink {
            color: #3498db;
            text-decoration: none;
            margin-top: 10px;
            display: inline-block;
            padding: 10px 15px;
            border: 1px solid #3498db;
            border-radius: 5px;
            transition: all 0.3s;
        }

        #homeLink:hover {
            background-color: #3498db;
            color: white;
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
                width: 95%;
            }

            h2 {
                font-size: 1.5rem;
            }

            #printButton,
            #homeLink {
                width: 100%;
                text-align: center;
            }
        }
    </style>

    <script>
        function printReceipt() {
            var printContents = document.getElementById("printable").innerHTML;

            var printWindow = window.open('', '_blank');

            printWindow.document.write(`
        <html>
        <head>
            <title>Voting Receipt</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 0;
                    font-size: 12px;
                }
                .container {
                    border: 1px solid #ddd;
                    padding: 15px;
                    width: 380px;
                    margin: 0 auto;
                }
                h2 {
                    color: #2c3e50;
                    text-align: center;
                    margin: 5px 0 10px 0;
                    font-size: 16px;
                }
                p {
                    margin: 8px 0;
                    line-height: 1.3;
                }
                ul {
                    list-style-type: none;
                    padding-left: 0;
                    margin: 8px 0;
                }
                li {
                    padding: 4px 0;
                    border-bottom: 1px solid #eee;
                    font-size: 11px;
                }
                @media print {
                    @page {
                        size: auto;
                        margin: 0;
                    }
                    body {
                        margin: 0;
                        padding: 0;
                    }
                    /* Hide print headers/footers */
                    @page {
                        size: auto;
                        margin: 4mm;
                        marks: none;
                    }
                }
            </style>
        </head>
        <body onload="window.print()">
            ${printContents}
        </body>
        </html>
    `);

            printWindow.document.close();
            printWindow.focus();
        }
    </script>

</head>

<body>
    <div class="container" id="printable">
        <h2>Thank You for Voting!</h2>
        <p>Your vote has been successfully recorded.</p>
        <p><strong>Voting Number:</strong> <?= htmlspecialchars($votingNumber) ?></p>
        <p><strong>Vote Timestamp:</strong> <?= htmlspecialchars($voteTimestamp) ?></p>
        <p><strong>Terminal:</strong> <?= htmlspecialchars($terminalName) ?></p>
        <p><strong>Candidates Voted:</strong></p>
        <div class="centered-list">
            <ul>
                <?php while ($candidateRow = $candidateResult->fetch_assoc()): ?>
                <li><?= htmlspecialchars($candidateRow['candidate_name']) ?></li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
    <button id="printButton" onclick="printReceipt()">Print Receipt</button>
    <a id="homeLink" href="index.php">Return to Home</a>
</body>

</html>