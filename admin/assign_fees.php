<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../admin/includes/config.php';

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_fee'])) {
    $stmt = $pdo->prepare("INSERT INTO account_fees (account_id, fee_id, last_applied) 
                           VALUES (:acct, :fee, NULL)");
    $stmt->execute([
        ':acct' => $_POST['account_id'],
        ':fee'  => $_POST['fee_id']
    ]);
    header("Location: assign_fees.php");
    exit;
}

// Fetch all accounts with customer info
$accounts = $pdo->query("
    SELECT a.account_id, a.account_number, c.full_name
    FROM accounts a
    INNER JOIN customers c ON a.customer_id = c.customer_id
    ORDER BY c.full_name
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all available fees
$fees = $pdo->query("SELECT * FROM fees WHERE status = 'active' ORDER BY fee_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch assigned fees
$assigned = $pdo->query("
    SELECT af.account_fee_id, a.account_number, c.full_name, f.fee_name, f.amount, f.frequency, af.last_applied
    FROM account_fees af
    INNER JOIN accounts a ON af.account_id = a.account_id
    INNER JOIN customers c ON a.customer_id = c.customer_id
    INNER JOIN fees f ON af.fee_id = f.fee_id
    ORDER BY af.account_fee_id ASC
")->fetchAll(PDO::FETCH_ASSOC);

function maskAccount($acct) {
    return substr($acct, 0, 2) . str_repeat('*', max(0, strlen($acct) - 6)) . substr($acct, -4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Assign Fees to Accounts</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <div class="assign-fees">
      <h2>Assign Fees to Accounts</h2>
  </div>

  <main class="assign-fees-content">

      <!-- Assign Fee Form -->
      <form method="post" class="assign-fees-form">
        <label>Account:</label>
        <select name="account_id" required>
          <option value="">--Select Account--</option>
          <?php foreach ($accounts as $a): ?>
            <option value="<?= $a['account_id'] ?>">
              <?= htmlspecialchars($a['full_name'] . " | " . maskAccount($a['account_number'])) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label>Fee:</label>
        <select name="fee_id" required>
          <option value="">--Select Fee--</option>
          <?php foreach ($fees as $f): ?>
            <option value="<?= $f['fee_id'] ?>">
              <?= htmlspecialchars($f['fee_name'] . " (" . number_format($f['amount'],2) . " - " . $f['frequency'] . ")") ?>
            </option>
          <?php endforeach; ?>
        </select>

        <button type="submit" name="assign_fee">Assign Fee</button>
      </form>

      <!-- Assigned Fees List -->
      <section class="assign-fees-list">
        <h2>Assigned Fees</h2>
        <table class="assign-fees-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Account</th>
              <th>Fee</th>
              <th>Amount</th>
              <th>Frequency</th>
              <th>Last Applied</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($assigned): ?>
              <?php foreach ($assigned as $af): ?>
                <tr>
                  <td><?= htmlspecialchars($af['account_fee_id']) ?></td>
                  <td><?= htmlspecialchars($af['full_name']) ?></td>
                  <td><?= htmlspecialchars(maskAccount($af['account_number'])) ?></td>
                  <td><?= htmlspecialchars($af['fee_name']) ?></td>
                  <td><?= number_format($af['amount'], 2) ?></td>
                  <td><?= htmlspecialchars($af['frequency']) ?></td>
                  <td><?= $af['last_applied'] ? htmlspecialchars($af['last_applied']) : 'Never' ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="7">No fees assigned</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>

  </main>
</body>
</html>
```
