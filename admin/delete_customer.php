<?php
session_start();
require_once '../admin/includes/config.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'manager', 'admin'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    try {
        // Instead of deleting, we could mark as inactive
        $stmt = $pdo->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
header("Location: view_customers.php");
exit;
