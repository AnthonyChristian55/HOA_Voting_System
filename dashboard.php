<?php
session_start();
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

include("./config/db.php");

$limit = 10; // Candidates per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total number of candidates
$total_candidates_result = $conn->query("SELECT COUNT(*) AS total FROM candidates");
$total_candidates = $total_candidates_result->fetch_assoc()['total'];
$total_pages = max(1, ceil($total_candidates / $limit)); // Ensure at least 1 page

// New: Orders by last name (A-Z)
$candidate_list = $conn->query("SELECT * FROM candidates ORDER BY lname ASC LIMIT $limit OFFSET $offset");

// Fetch current admin info
$sql = "SELECT username FROM admin WHERE id = 1"; // Adjust if needed
$adminResult = $conn->query($sql);
$admin = $adminResult->fetch_assoc();

// Fetch current settings
$settings_result = $conn->query("SELECT * FROM settings LIMIT 1");
$settings = $settings_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="./css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        /* Base Styles */
        .admin-nav {
            background: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .admin-nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .admin-nav h2 {
            margin: 0 auto;
            /* Center the title */
            color: white;
            font-size: 1.5rem;
            text-align: center;
            flex-grow: 1;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px 10px;
        }

        .admin-nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            /* Center all links */
        }

        .nav-section {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 4px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            text-align: center;
            min-width: 120px;
        }

        .nav-link span {
            text-align: center;
            width: 100%;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Font Awesome icons */
        .fas {
            font-size: 0.9rem;
        }

        /* Responsive Styles */
        @media (max-width: 1024px) {
            .admin-nav-links {
                flex-direction: row;
                gap: 10px;
            }

            .nav-section {
                flex-direction: row;
                gap: 10px;
            }
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }

            .admin-nav-links {
                display: none;
                flex-direction: column;
                gap: 5px;
            }

            .admin-nav-links.active {
                display: flex;
            }

            .nav-section {
                flex-direction: column;
                gap: 5px;
                width: 100%;
            }

            .nav-link {
                justify-content: center;
                width: 100%;
            }
        }

        /* Landscape mode optimization */
        @media (orientation: landscape) and (max-width: 1024px) {
            .admin-nav-links {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .nav-section {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .nav-link {
                min-width: 150px;
                padding: 8px 10px;
            }

            .pagination span {
                padding: 8px 16px;
                background-color: #3498db;
                color: white;
            }
        }
    </style>

</head>

<body>
    <div class="admin-nav">
        <div class="admin-nav-header">
            <h2>Admin Dashboard</h2>
            <button class="mobile-menu-btn">☰</button>
        </div>

        <nav class="admin-nav-links">
            <div class="nav-section">
                <a href="./admin/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
                <a href="update_admin.php" class="nav-link">
                    <i class="fas fa-user-cog"></i> <span>Admin Settings</span>
                </a>
            </div>

            <div class="nav-section">
                <a href="update_logo.php" class="nav-link btn">
                    <i class="fas fa-image"></i> <span>Update Logo</span>
                </a>
                <a href="tabulation.php" class="nav-link">
                    <i class="fas fa-table"></i> <span>Vote Tabulation</span>
                </a>
            </div>

            <div class="nav-section">
                <a href="add_voter.php" class="nav-link">
                    <i class="fas fa-user-plus"></i> <span>Add Voter</span>
                </a>
                <a href="view_voter.php" class="nav-link">
                    <i class="fas fa-list"></i> <span>Voters List</span>
                </a>
            </div>

            <div class="nav-section">
                <a href="terminal_settings.php" class="nav-link">
                    <i class="fas fa-terminal"></i> <span>Terminal Settings</span>
                </a>
            </div>
        </nav>
    </div>

    <div class="section-wrapper">
        <!-- Candidate List Container -->
        <div class="section-container">
            <h3>Candidate List</h3>
            <div id="candidateTable">
                <table>
                    <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    $rowNumber = $offset + 1;
                    while ($row = $candidate_list->fetch_assoc()) { ?>
                        <tr>
                            <td><?= $rowNumber++ ?></td>
                            <td><?= htmlspecialchars($row['fname']) ?></td>
                            <td><?= htmlspecialchars($row['lname']) ?></td>
                            <td>
                                <a href="update_candidate.php?id=<?= $row['id'] ?>"
                                    style="text-decoration: none !important; color: #3498db !important; padding: 5px 10px !important; border: 1px solid #3498db !important; border-radius: 4px !important; margin-right: 5px !important; transition: all 0.3s ease !important; display: inline-block !important;"
                                    onmouseover="this.style.backgroundColor='#3498db'; this.style.color='white'"
                                    onmouseout="this.style.backgroundColor='transparent'; this.style.color='#3498db'">Edit</a>
                                |
                                <a href="admin/delete_candidate.php?id=<?= $row['id'] ?>"
                                    style="text-decoration: none !important; color: #e74c3c !important; padding: 5px 10px !important; border: 1px solid #e74c3c !important; border-radius: 4px !important; margin-left: 5px !important; transition: all 0.3s ease !important; display: inline-block !important;"
                                    onmouseover="this.style.backgroundColor='#e74c3c'; this.style.color='white'"
                                    onmouseout="this.style.backgroundColor='transparent'; this.style.color='#e74c3c'"
                                    onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination" id="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>">&laquo; Previous</a>
                <?php endif; ?>
                <span style="
    padding: 8px 16px !important;
    background-color: #3498db !important;
    color: white !important;
    border-radius: 4px !important;
    display: inline-block !important;
    margin: 0 5px !important;
    font-size: 14px !important;
">Page <?= $page ?> of <?= $total_pages ?></span>
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add Candidate Container -->
        <div class="section-container">
            <h3>Add Candidate</h3>
            <form id="addCandidateForm" enctype="multipart/form-data" class="candidate-form">
                <div class="form-row">
                    <input type="text" id="fname" name="fname" placeholder="First Name" required class="form-input">
                    <input type="text" id="lname" name="lname" placeholder="Last Name" required class="form-input">
                </div>
                <div class="form-row">
                    <input type="file" id="photo" name="photo" accept="image/*" class="file-input">
                </div>
                <button type="submit" class="submit-btn">Add Candidate</button>
            </form>
            <p id="message" class="message"></p>
        </div>

        <div class="section-container">
            <h3>Voting Settings</h3>
            <p><strong>Current Minimum Votes:</strong> <?= $settings['min_votes'] ?></p>
            <p><strong>Current Maximum Votes:</strong> <?= $settings['max_votes'] ?></p>
            <form action="admin/update_settings.php" method="POST" class="settings-form">
                <div class="form-group">
                    <label for="min_votes">Minimum Votes:</label>
                    <input type="number" name="min_votes" value="<?= $settings['min_votes'] ?>" required
                        class="settings-input">
                </div>
                <div class="form-group">
                    <label for="max_votes">Maximum Votes:</label>
                    <input type="number" name="max_votes" value="<?= $settings['max_votes'] ?>" required
                        class="settings-input">
                </div>
                <button type="submit" class="settings-btn">Update Settings</button>
            </form>
        </div>
    </div>
</body>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Mobile menu functionality
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const adminNavLinks = document.querySelector('.admin-nav-links');

        if (mobileMenuBtn && adminNavLinks) {
            mobileMenuBtn.addEventListener('click', function () {
                adminNavLinks.classList.toggle('active');

                // Change the button icon based on menu state
                if (adminNavLinks.classList.contains('active')) {
                    this.innerHTML = '✕'; // Show X when menu is open
                } else {
                    this.innerHTML = '☰'; // Show hamburger when menu is closed
                }
            });
        }

        // Add candidate form submission
        const addCandidateForm = document.getElementById("addCandidateForm");
        if (addCandidateForm) {
            addCandidateForm.addEventListener("submit", function (event) {
                event.preventDefault();

                let fname = document.getElementById("fname").value.trim();
                let lname = document.getElementById("lname").value.trim();
                let photo = document.getElementById("photo").files[0];
                let messageElement = document.getElementById("message");

                if (!fname || !lname) {
                    messageElement.style.color = "red";
                    messageElement.textContent = "First name and last name are required.";
                    return;
                }

                let formData = new FormData();
                formData.append("fname", fname);
                formData.append("lname", lname);

                if (photo) {
                    formData.append("photo", photo);
                }

                fetch("admin/add_candidate.php", {
                    method: "POST",
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === "success") {
                            messageElement.style.color = "green";
                            messageElement.textContent = data.message;

                            // Clear input fields
                            document.getElementById("fname").value = "";
                            document.getElementById("lname").value = "";
                            document.getElementById("photo").value = "";

                            // Reload last page of candidates
                            loadLastPage();
                        } else {
                            messageElement.style.color = "red";
                            messageElement.textContent = data.message;
                        }
                    })
                    .catch(error => {
                        messageElement.style.color = "red";
                        messageElement.textContent = "An error occurred.";
                    });
            });
        }

        // Initial load
        loadCandidates();
        applyButtonStyles();

        // Handle landscape orientation changes
        window.addEventListener('resize', handleOrientationChange);
    });

    function loadCandidates(page = 1) {
        fetch(`admin/get_candidates.php?page=${page}`)
            .then(response => response.text())
            .then(data => {
                const candidateTable = document.getElementById("candidateTable");
                if (candidateTable) {
                    candidateTable.innerHTML = data;
                    applyButtonStyles();
                }
            });

        fetch(`admin/get_pagination.php?page=${page}`)
            .then(response => response.text())
            .then(data => {
                const pagination = document.getElementById("pagination");
                if (pagination) {
                    pagination.innerHTML = data;
                    stylePagination();
                }
            });
    }

    function applyButtonStyles() {
        const editButtons = document.querySelectorAll('a[href^="update_candidate"]');
        const deleteButtons = document.querySelectorAll('a[href^="admin/delete_candidate"]');

        editButtons.forEach(button => {
            button.style.cssText = `
            text-decoration: none !important;
            color: #3498db !important;
            padding: 5px 10px !important;
            border: 1px solid #3498db !important;
            border-radius: 4px !important;
            margin-right: 5px !important;
            transition: all 0.3s ease !important;
            display: inline-block !important;
        `;
            button.onmouseover = () => {
                button.style.backgroundColor = '#3498db';
                button.style.color = 'white';
            };
            button.onmouseout = () => {
                button.style.backgroundColor = 'transparent';
                button.style.color = '#3498db';
            };
        });

        deleteButtons.forEach(button => {
            button.style.cssText = `
            text-decoration: none !important;
            color: #e74c3c !important;
            padding: 5px 10px !important;
            border: 1px solid #e74c3c !important;
            border-radius: 4px !important;
            margin-left: 5px !important;
            transition: all 0.3s ease !important;
            display: inline-block !important;
        `;
            button.onmouseover = () => {
                button.style.backgroundColor = '#e74c3c';
                button.style.color = 'white';
            };
            button.onmouseout = () => {
                button.style.backgroundColor = 'transparent';
                button.style.color = '#e74c3c';
            };
        });
    }

    function stylePagination() {
        const paginationSpans = document.querySelectorAll('#pagination span');
        paginationSpans.forEach(span => {
            span.style.cssText = `
            padding: 8px 16px !important;
            background-color: #3498db !important;
            color: white !important;
            border-radius: 4px !important;
            display: inline-block !important;
            margin: 0 5px !important;
            font-size: 14px !important;
        `;
        });
    }

    function loadLastPage() {
        fetch("admin/get_pagination.php")
            .then(response => response.text())
            .then(data => {
                let match = data.match(/Page \d+ of (\d+)/);
                let lastPage = match ? parseInt(match[1]) : 1;
                loadCandidates(lastPage);
            });
    }

    function handleOrientationChange() {
        if (window.innerWidth > window.innerHeight) {
            // Landscape mode
            const navLinks = document.querySelector('.admin-nav-links');
            if (navLinks) {
                navLinks.style.flexDirection = 'row';
                document.querySelectorAll('.nav-section').forEach(section => {
                    section.style.flexDirection = 'row';
                });
            }
        } else {
            // Portrait mode
            const navLinks = document.querySelector('.admin-nav-links');
            if (navLinks && !navLinks.classList.contains('active')) {
                navLinks.style.flexDirection = 'column';
                document.querySelectorAll('.nav-section').forEach(section => {
                    section.style.flexDirection = 'column';
                });
            }
        }
    }
</script>

</html>