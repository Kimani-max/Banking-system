<?php
session_start();
require_once '../admin/includes/config.php';

if (isset($_SESSION['user_id'])) {
    // Clear remember_token in DB
    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Clear session
session_unset();
session_destroy();

// Clear cookie
setcookie("rememberme", "", time() - 3600, "/", "", true, true);

header("Location: login.php");
exit;
