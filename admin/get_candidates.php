<?php
include("../config/db.php");

$limit = 10; // Candidates per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Changed ORDER BY to use last name (lname) instead of id
$result = $conn->query("SELECT * FROM candidates ORDER BY lname ASC LIMIT $limit OFFSET $offset");

echo '<table>
        <tr>
            <th>#</th> <!-- Row Number Column -->
            <th>First Name</th>
            <th>Last Name</th>
            <th>Actions</th>
        </tr>';

$rowNumber = $offset + 1; // Calculate row number based on pagination
while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>" . $rowNumber++ . "</td>
            <td>" . htmlspecialchars($row['fname']) . "</td>
            <td>" . htmlspecialchars($row['lname']) . "</td>
            <td>
                <a href='update_candidate.php?id=" . $row['id'] . "'>Edit</a> |
                <a href='admin/delete_candidate.php?id=" . $row['id'] . "' onclick='return confirm(\"Are you sure?\")'>Delete</a>
            </td>
          </tr>";
}

echo "</table>";
?>