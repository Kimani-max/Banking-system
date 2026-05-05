<?php
session_start();
require_once '../admin/includes/config.php';

// Only staff, manager, admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['teller', 'manager', 'admin'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: view_accounts.php");
    exit;
}

$id = $_GET['id'];

try {
    $stmt = $pdo->prepare("UPDATE accounts SET status = 'closed' WHERE account_id = ?");
    $stmt->execute([$id]);
} catch (PDOException $e) {
    // optional: log error
}

header("Location: view_accounts.php");
exit;
