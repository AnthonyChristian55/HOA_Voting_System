<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    session_destroy();
    header("Location: ../admin_login.php");
    exit();
}

include("../config/db.php");


// 3. Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$is_active = (int) $data['active'];

try {
    // 4. Update database
    $stmt = $pdo->prepare("UPDATE elections SET is_active = ? WHERE id = 1"); // Use your election ID
    $success = $stmt->execute([$is_active]);

    // 5. Log the action
    if ($success) {
        $log_message = sprintf(
            "[%s] Election status changed to %s by admin ID: %d\n",
            date('Y-m-d H:i:s'),
            $is_active ? 'OPEN' : 'CLOSED',
            $_SESSION['admin_id']
        );
        file_put_contents('admin_actions.log', $log_message, FILE_APPEND);
    }

    echo json_encode(['success' => $success]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>