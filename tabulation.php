<?php
session_start();
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

include("./config/db.php");

$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total candidates count
$total_candidates_result = $conn->query("SELECT COUNT(*) AS total FROM candidates");
$total_candidates = $total_candidates_result->fetch_assoc()['total'];
$total_pages = ceil($total_candidates / $limit);

// Modified query to join with picture_upload table
$tabulation_result = $conn->query("SELECT id, fname, lname, votes, photo FROM candidates ORDER BY votes DESC LIMIT $limit OFFSET $offset");
$top_candidates = $conn->query("SELECT id, fname, lname, votes, photo FROM candidates ORDER BY votes DESC LIMIT 5");

if (!$tabulation_result) {
    die("Error fetching candidates: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Tabulation</title>
    <link rel="stylesheet" href="./css/tabulation_design.css">
    <link rel="stylesheet" href="./css/tabulation_print.css" media="print">
</head>

<body>
    <div class="tabulation-container">
        <div class="tabulation-header">
            <h2>Vote Tabulation</h2>
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

        <div class="tabulation-section">
            <h3>Vote Tabulation</h3>
            <table class="tabulation-table">
                <tr>
                    <th>Rank</th>
                    <th>Name</th>
                    <th></th>
                    <th>Votes</th>
                </tr>
                <?php
                $rank = $offset + 1;
                // For the main tabulation table:
                while ($row = $tabulation_result->fetch_assoc()) {
                    $imagePath = !empty($row['photo']) ? "./uploads/candidates/" . $row['photo'] : "./uploads/placeholder.jpg";
                    ?>
                    <tr>
                        <td><?= $rank ?></td>
                        <td><?= htmlspecialchars($row['fname']) . ' ' . htmlspecialchars($row['lname']) ?></td>
                        <td><img src="<?= htmlspecialchars($imagePath) ?>" alt="Candidate Photo" class="candidate-photo"
                                onerror="this.onerror=null; this.src='./uploads/placeholder.jpg';"></td>
                        <td><?= $row['votes'] ?></td>
                    </tr>
                    <?php
                    $rank++;
                } ?>
            </table>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>">&laquo; Previous</a>
                <?php endif; ?>

                <span>Page <?= $page ?> of <?= $total_pages ?></span>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>

            <div class="action-buttons">
                <button onclick="printTabulation()" class="btn btn-print">Print Vote Tabulation</button>
            </div>
        </div>

        <div class="tabulation-section top-candidates">
            <h3>Top 5 Candidates</h3>
            <table class="tabulation-table" id="topCandidates">
                <tr>
                    <th>Rank</th>
                    <th>Name</th>
                    <th></th>
                    <th>Votes</th>
                </tr>
                <?php
                $rank = 1; // Reset rank counter for top candidates
                while ($top = $top_candidates->fetch_assoc()) {
                    $imagePath = !empty($top['photo']) ? "./uploads/candidates/" . $top['photo'] : "./uploads/placeholder.jpg";
                    ?>
                    <tr>
                        <td><?= $rank ?></td>
                        <td><?= htmlspecialchars($top['fname']) . ' ' . htmlspecialchars($top['lname']) ?></td>
                        <td><img src="<?= htmlspecialchars($imagePath) ?>" alt="Candidate Photo" class="candidate-photo"
                                onerror="this.onerror=null; this.src='./uploads/placeholder.jpg';"></td>
                        <td><?= $top['votes'] ?></td>
                    </tr>
                    <?php
                    $rank++;
                }
                ?>
            </table>

            <div class="action-buttons">
                <button onclick="printTopCandidates()" class="btn btn-print">Print Top 5 Candidates</button>
            </div>
        </div>
    </div>

    <!-- Keep your existing JavaScript code -->
    <script>
        function printTabulation() {
            fetch("admin/fetch_all_tabulation.php")
                .then(response => response.json())
                .then(data => {
                    let newWindow = window.open("", "_blank");
                    newWindow.document.write(`
            <html>
            <head>
                <title>Vote Tabulation</title>
                <style>
                    @media print {
                        body { font-family: Arial; margin: 1cm; }
                        table { 
                            width: 100%; 
                            border-collapse: collapse;
                            margin-bottom: 2cm; /* Added spacing */
                        }
                        th, td { border: 1px solid #000; padding: 8px; }
                        th { background-color: #f2f2f2; }
                        .signatures {
                            margin-top: 2cm;
                            width: 100%;
                        }
                        .signature-group {
                            display: flex;
                            justify-content: space-between;
                            margin-bottom: 1.5cm;
                            width: 100%;
                        }
                        .signature-item {
                            text-align: center;
                            width: 30%;
                        }
                        .signature-line {
                            width: 80%;
                            margin: 0 auto;
                            border-top: 1px solid #000;
                            padding-top: 5px;
                        }
                        .signature-name {
                            margin-top: 10px;
                            display: block;
                        }
                    }
                </style>
            </head>
            <body>
                <h2 style="text-align: center;">Vote Tabulation</h2>
                <table>
                    <tr><th>Rank</th><th>First Name</th><th>Last Name</th><th>Votes</th></tr>`);

                    let rank = 1;
                    data.forEach(candidate => {
                        newWindow.document.write(
                            `<tr>
                    <td style="text-align: center;">${rank}</td>
                    <td>${candidate.fname}</td>
                    <td>${candidate.lname}</td>
                    <td style="text-align: center;">${candidate.votes}</td>
                </tr>`
                        );
                        rank++;
                    });

                    newWindow.document.write(`
                </table>
                
                <div class="signatures">
                    <!-- First row of 3 signatures -->
                    <div class="signature-group">
                        <div class="signature-item">
                            <div class="signature-line"></div>
                            <span class="signature-name"></span>
                        </div>
                        <div class="signature-item">
                            <div class="signature-line"></div>
                            <span class="signature-name"></span>
                        </div>
                        <div class="signature-item">
                            <div class="signature-line"></div>
                            <span class="signature-name"></span>
                        </div>
                    </div>
                    
                    <!-- Second row of 2 signatures -->
                    <div class="signature-group" style="justify-content: center;">
                        <div class="signature-item" style="margin-right: 15%;">
                            <div class="signature-line"></div>
                            <span class="signature-name"></span>
                        </div>
                        <div class="signature-item" style="margin-left: 15%;">
                            <div class="signature-line"></div>
                            <span class="signature-name"></span>
                        </div>
                    </div>
                </div>
            </body>
            </html>`);
                    newWindow.document.close();
                    setTimeout(() => {
                        newWindow.print();
                    }, 250);
                })
                .catch(error => console.error("Error fetching tabulation data:", error));
        }

        function printTopCandidates() {
            let table = document.getElementById("topCandidates");
            let clone = table.cloneNode(true);

            // Find the column index of "Photo" header
            let headerCells = clone.querySelectorAll("thead th");
            let photoColIndex = -1;
            headerCells.forEach((th, index) => {
                if (th.textContent.trim().toLowerCase() === "photo") {
                    photoColIndex = index;
                }
            });

            // If "Photo" column found, remove it from all rows
            clone.querySelectorAll("tr").forEach(row => {
                let cells = row.children;
                if (cells.length > 2) {
                    cells[2].remove();
                }
            });

            let newWindow = window.open("", "_blank");
            newWindow.document.write(`
        <html>
        <head>
            <title>Top 5 Candidates</title>
            <style>
                @media print {
                    body { font-family: Arial; margin: 1cm; }
                    table { 
                        width: 100%; 
                        border-collapse: collapse;
                        margin-bottom: 2cm;
                    }
                    th, td { 
                        border: 1px solid #000; 
                        padding: 8px;
                        text-align: center;
                    }
                    th { 
                        background-color: #f2f2f2; 
                    }
                    img { 
                        display: none !important; 
                    }
                    .signatures {
                        margin-top: 2cm;
                        width: 100%;
                    }
                    .signature-group {
                        display: flex;
                        justify-content: space-between;
                        margin-bottom: 1.5cm;
                        width: 100%;
                    }
                    .signature-item {
                        text-align: center;
                        width: 30%;
                    }
                    .signature-line {
                        width: 80%;
                        margin: 0 auto;
                        border-top: 1px solid #000;
                        padding-top: 5px;
                    }
                    .signature-name {
                        margin-top: 10px;
                        display: block;
                    }
                }
            </style>
        </head>
        <body>
            <h2 style="text-align: center;">Top 5 Candidates</h2>
            ${clone.outerHTML}
            
            <div class="signatures">
                <div class="signature-group">
                    <div class="signature-item">
                        <div class="signature-line"></div>
                        <span class="signature-name"></span>
                    </div>
                    <div class="signature-item">
                        <div class="signature-line"></div>
                        <span class="signature-name"></span>
                    </div>
                    <div class="signature-item">
                        <div class="signature-line"></div>
                        <span class="signature-name"></span>
                    </div>
                </div>
                <div class="signature-group" style="justify-content: center;">
                    <div class="signature-item" style="margin-right: 15%;">
                        <div class="signature-line"></div>
                        <span class="signature-name"></span>
                    </div>
                    <div class="signature-item" style="margin-left: 15%;">
                        <div class="signature-line"></div>
                        <span class="signature-name"></span>
                    </div>
                </div>
            </div>
        </body>
        </html>
    `);

            newWindow.document.close();
            setTimeout(() => {
                newWindow.print();
            }, 250);
        }
    </script>
</body>

</html>