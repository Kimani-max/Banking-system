<?php
require_once '../admin/includes/config.php';
session_start();

if (!empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE standing_orders SET status = 'inactive' WHERE order_id = :id");
    $stmt->execute([':id' => $id]);

    $_SESSION['msg'] = "Standing order #$id has been canceled successfully.";
}

header("Location: standing_orders.php");
exit;
