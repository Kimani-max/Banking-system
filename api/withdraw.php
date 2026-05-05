<?php
require_once '../admin/includes/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = "";
$balance = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountId = $_POST['account_id'] ?? '';
    $amount    = $_POST['amount'] ?? 0;

    if ($accountId && $amount > 0) {
        try {
            $pdo->beginTransaction();

            // Get current balance + customer_id
            $stmt = $pdo->prepare("SELECT balance, customer_id FROM accounts WHERE account_id = :acct");
            $stmt->execute([':acct' => $accountId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new Exception("Account not found.");
            }

            $currentBalance = (float)$row['balance'];
            $customerId     = $row['customer_id'];

            if ($currentBalance < $amount) {
                throw new Exception("Insufficient funds.");
            }

            $newBalance = $currentBalance - $amount;

            // Update balance
            $stmt = $pdo->prepare("UPDATE accounts SET balance = :bal WHERE account_id = :acct");
            $stmt->execute([':bal' => $newBalance, ':acct' => $accountId]);

            // Insert transaction
            $stmt = $pdo->prepare("
                INSERT INTO transactions 
                (account_id, customer_id, txn_type, amount, description, txn_date, balance_after) 
                VALUES 
                (:acct, :cust, 'withdraw', :amt, 'Cash withdrawal', NOW(), :bal)
            ");
            $stmt->execute([
                ':acct' => $accountId,
                ':cust' => $customerId,
                ':amt'  => $amount,
                ':bal'  => $newBalance
            ]);

            $pdo->commit();
            $message = "✅ Withdrawal successful! New Balance: " . number_format($newBalance, 2);
            $balance = $newBalance;

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "❌ Error: " . $e->getMessage();
        }
    } else {
        $message = "⚠️ Please fill all fields correctly.";
    }
}

// Fetch accounts
$accounts = $pdo->query("
    SELECT a.account_id, a.account_number, c.full_name
    FROM accounts a 
    JOIN customers c ON a.customer_id = c.customer_id
    ORDER BY a.account_number
")->fetchAll(PDO::FETCH_ASSOC);

function maskAccount($acct) {
    return substr($acct, 0, 2) . str_repeat('*', max(0, strlen($acct) - 6)) . substr($acct, -4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Withdraw Money</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="teller-form-container">
  <h2 class="teller-form-title withdraw-title">💸 Withdraw Money</h2>

  <?php if ($message): ?>
    <div class="teller-msg"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="post" class="teller-form">
    <label for="account_id">Select Account:</label>
    <select name="account_id" required>
      <option value="">-- Choose Account --</option>
      <?php foreach ($accounts as $acct): ?>
        <option value="<?= $acct['account_id'] ?>">
          <?= maskAccount($acct['account_number']) ?> - <?= htmlspecialchars($acct['full_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label for="amount">Withdrawal Amount (Ksh):</label>
    <input type="number" step="0.01" name="amount" required>

    <button type="submit" class="teller-btn withdraw-btn">Withdraw Funds</button>
  </form>
</div>
</body>
</html>
