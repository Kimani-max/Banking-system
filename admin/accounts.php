<?php
require_once '../admin/includes/config.php';

// Fetch accounts with customer info
$sql = "
    SELECT 
        a.account_id,
        a.account_number,
        a.balance,
        c.customer_id,
        c.full_name,
        c.email
    FROM accounts a
    INNER JOIN customers c ON a.customer_id = c.customer_id
    ORDER BY a.account_id ASC
";
$stmt = $pdo->query($sql);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

function maskAccount($acct) {
    return substr($acct, 0, 2) . str_repeat('*', max(0, strlen($acct) - 6)) . substr($acct, -4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Accounts List</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="darling">
  <h1>PHARNYBIC BANK - Accounts</h1>
  <nav>
    <a href="dashboards/admin_dashboard.php">Dashboard</a>
    <a href="accounts.php" class="active">Accounts</a>
    <a href="view_customers.php">Customers</a>
    <a href="transactions.php">Transactions</a>
    <a href="loans.php">Loans</a>
    <a href="logout.php">Logout</a>
  </nav>
</div>

<main>
  <h2>Accounts</h2>
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Account ID</th>
          <th>Account Number</th>
          <th>Customer ID</th>
          <th>Customer Name</th>
          <th>Email</th>
          <th>Balance (KSH)</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($accounts): ?>
            <?php foreach ($accounts as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['account_id']) ?></td>
              <td><?= htmlspecialchars(maskAccount($row['account_number'])) ?></td>
              <td><?= htmlspecialchars($row['customer_id']) ?></td>
              <td><?= htmlspecialchars($row['full_name']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= number_format($row['balance'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">No accounts found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>
