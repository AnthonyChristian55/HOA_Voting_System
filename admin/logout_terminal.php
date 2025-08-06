<?php
session_start();

// Clear terminal session
unset($_SESSION['valid_pin']);

// Redirect back to PIN entry
header('Location: ../pin_check.php');
exit;
?>