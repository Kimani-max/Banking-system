<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../admin/includes/config.php';

// Get today’s date
$today = date('Y-m-d');

// Fetch all assigned fees with account and fee details
$sql = "
    SELECT af.account_fee_id, af.account_id, af.last_applied,
           f.fee_id, f.fee_name, f.amount, f.frequency,
           a.account_number, a.balance, a.customer_id,
           c.full_name
    FROM account_fees af
    INNER JOIN accounts a ON af.account_id = a.account_id
    INNER JOIN fees f ON af.fee_id = f.fee_id
    INNER JOIN customers c ON a.customer_id = c.customer_id
    WHERE f.status = 'active'
";
$stmt = $pdo->query($sql);
$assignedFees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Track processed fees
$processed = [];

foreach ($assignedFees as $af) {
    $due = false;

    // Decide if fee is due
    if ($af['frequency'] === 'monthly') {
        if (!$af['last_applied'] || date('Y-m', strtotime($af['last_applied'])) < date('Y-m')) {
            $due = true;
        }
    } elseif ($af['frequency'] === 'yearly') {
        if (!$af['last_applied'] || date('Y', strtotime($af['last_applied'])) < date('Y')) {
            $due = true;
        }
    } elseif ($af['frequency'] === 'one_time') {
        if (!$af['last_applied']) {
            $due = true;
        }
    }

    if ($due) {
        $pdo->beginTransaction();
        try {
            // Deduct from account balance
            $newBalance = $af['balance'] - $af['amount'];
            $upd = $pdo->prepare("UPDATE accounts SET balance = :bal WHERE account_id = :acct");
            $upd->execute([':bal' => $newBalance, ':acct' => $af['account_id']]);

            // Record in transactions
            $ins = $pdo->prepare("
                INSERT INTO transactions (account_id, customer_id, txn_type, amount, description, txn_date)
                VALUES (:acct, :cust, 'debit', :amt, :descr, NOW())
            ");
            $ins->execute([
                ':acct'  => $af['account_id'],
                ':cust'  => $af['customer_id'],
                ':amt'   => $af['amount'],
                ':descr' => $af['fee_name'] . " Fee Deduction"
            ]);

            // Update last_applied
            $upd2 = $pdo->prepare("UPDATE account_fees SET last_applied = :today WHERE account_fee_id = :id");
            $upd2->execute([':today' => $today, ':id' => $af['account_fee_id']]);

            $pdo->commit();

            $processed[] = $af['full_name'] . " | " . $af['account_number'] . " | " . $af['fee_name'];
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "Error processing fee for account " . $af['account_number'] . ": " . $e->getMessage() . "<br>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Process Fees</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <h2>Process Fees</h2>

  <?php if ($processed): ?>
    <p>The following fees were applied successfully:</p>
    <ul>
      <?php foreach ($processed as $p): ?>
        <li><?= htmlspecialchars($p) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p>No fees were due today.</p>
  <?php endif; ?>

</body>
</html>
