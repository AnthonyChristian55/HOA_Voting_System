<?php
include("../config/db.php");

$limit = 10; // Candidates per page
$result = $conn->query("SELECT COUNT(*) AS total FROM candidates");
$row = $result->fetch_assoc();
$totalCandidates = $row['total'];
$totalPages = max(1, ceil($totalCandidates / $limit)); // Ensure at least 1 page

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, min($page, $totalPages)); // Ensure page is within range

echo '<div class="pagination">';

if ($page > 1) {
    echo '<a href="#" onclick="loadCandidates(' . ($page - 1) . ')">&laquo; Previous</a>';
}

echo " Page $page of $totalPages ";

if ($page < $totalPages) {
    echo '<a href="#" onclick="loadCandidates(' . ($page + 1) . ')">Next &raquo;</a>';
}

echo '</div>';

// Auto-update pagination when a new candidate is added (only if a new page is needed)
echo '<script>
        if (' . ($totalCandidates % $limit) . ' === 1 && ' . $page . ' === ' . $totalPages . ') {
            loadCandidates(' . $totalPages . ');
        }
      </script>';
?>