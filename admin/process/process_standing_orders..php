<?php
require_once '../../admin/includes/config.php';

// Get today's date
$today = date('Y-m-d');

// 1. Fetch due standing orders
$sql = "SELECT so.*, 
               fa.account_id AS from_acc_id, fa.balance AS from_balance, fa.account_number AS from_acc_num,
               ta.account_id AS to_acc_id, ta.balance AS to_balance, ta.account_number AS to_acc_num,
               c.full_name
        FROM standing_orders so
        INNER JOIN accounts fa ON so.from_account_id = fa.account_id
        INNER JOIN accounts ta ON so.to_account_id = ta.account_id
        INNER JOIN customers c ON so.customer_id = c.customer_id
        WHERE so.status = 'active' 
        AND so.next_run_date <= :today";
$stmt = $pdo->prepare($sql);
$stmt->execute([':today' => $today]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($orders as $order) {
    $fromAcc = $order['from_acc_id'];
    $toAcc = $order['to_acc_id'];
    $amount = $order['amount'];
    $fromBal = $order['from_balance'];
    $toBal   = $order['to_balance'];

    // 2. Check sufficient funds
    if ($fromBal < $amount) {
        echo "Skipped Order #{$order['order_id']} (Insufficient funds)<br>";
        continue;
    }

    // 3. Deduct from source
    $newFromBal = $fromBal - $amount;
    $stmt = $pdo->prepare("UPDATE accounts SET balance = :bal WHERE account_id = :acc");
    $stmt->execute([':bal' => $newFromBal, ':acc' => $fromAcc]);

    // 4. Credit to destination
    $newToBal = $toBal + $amount;
    $stmt = $pdo->prepare("UPDATE accounts SET balance = :bal WHERE account_id = :acc");
    $stmt->execute([':bal' => $newToBal, ':acc' => $toAcc]);

    // 5. Record transactions
    $desc = "Standing Order Transfer (Order ID {$order['order_id']})";

    // debit txn
    $stmt = $pdo->prepare("INSERT INTO transactions (account_id, customer_id, txn_type, amount, description, txn_date) 
                           VALUES (:acc, :cust, 'withdraw', :amt, :desc, NOW())");
    $stmt->execute([
        ':acc' => $fromAcc,
        ':cust' => $order['customer_id'],
        ':amt' => $amount,
        ':desc' => $desc
    ]);

    // credit txn
    $stmt = $pdo->prepare("INSERT INTO transactions (account_id, customer_id, txn_type, amount, description, txn_date) 
                           VALUES (:acc, :cust, 'deposit', :amt, :desc, NOW())");
    $stmt->execute([
        ':acc' => $toAcc,
        ':cust' => $order['customer_id'],
        ':amt' => $amount,
        ':desc' => $desc
    ]);

    // 6. Update next run date
    if ($order['frequency'] === 'weekly') {
        $nextDate = date('Y-m-d', strtotime($order['next_run_date'] . ' +7 days'));
    } elseif ($order['frequency'] === 'monthly') {
        $nextDate = date('Y-m-d', strtotime($order['next_run_date'] . ' +1 month'));
    } else {
        $nextDate = $today;
    }

    $stmt = $pdo->prepare("UPDATE standing_orders SET next_run_date = :nextDate WHERE order_id = :id");
    $stmt->execute([':nextDate' => $nextDate, ':id' => $order['order_id']]);

    echo "Processed Standing Order #{$order['order_id']} - {$order['full_name']} transferred $amount<br>";
}

echo "<br>Standing order processing completed.";
