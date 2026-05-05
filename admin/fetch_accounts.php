<?php
require_once '../admin/includes/config.php';

if (!empty($_GET['customer_id'])) {
    $cust = $_GET['customer_id'];
    $stmt = $pdo->prepare("SELECT account_id, account_number FROM accounts WHERE customer_id = :cust AND status='active'");
    $stmt->execute([':cust' => $cust]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($accounts) {
        foreach ($accounts as $a) {
            echo "<option value='{$a['account_id']}'>" . htmlspecialchars($a['account_number']) . "</option>";
        }
    } else {
        echo "<option value=''>No active accounts</option>";
    }
}
?>
