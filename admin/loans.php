<?php
require_once '../admin/includes/config.php';

// Fetch all loans with customer + account info
$sql = "
    SELECT 
        l.loan_id,
        l.loan_name,
        l.amount,
        l.interest_rate,
        l.term_months,
        l.start_date,
        l.end_date,
        l.status,
        l.balance,
        c.full_name,
        a.account_number
    FROM loans l
    INNER JOIN customers c ON l.customer_id = c.customer_id
    INNER JOIN accounts a ON l.account_id = a.account_id
    ORDER BY l.created_at ASC
";
$stmt = $pdo->query($sql);

// helper to mask account numbers
function maskAccount($acct) {
    return substr($acct, 0, 2) . str_repeat('*', max(0, strlen($acct) - 6)) . substr($acct, -4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Loans Management</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="healing">
  <h1>Bank System - Loans</h1>
  <nav>
    <a href="accounts.php">Accounts</a>
    <a href="view_customers.php">Customers</a>
    <a href="transactions.php">Transactions</a>
    <a href="loans.php" class="active">Loans</a>
    <a href="../logout.php">Logout</a>
  </nav>
</div>

<main>
  <h2>All Loans</h2>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Loan ID</th>
          <th>Loan Name</th>
          <th>Customer</th>
          <th>Account</th>
          <th>Amount</th>
          <th>Interest (%)</th>
          <th>Term (Months)</th>
          <th>Start Date</th>
          <th>End Date</th>
          <th>Status</th>
          <th>Balance</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
          <tr>
            <td><?= htmlspecialchars($row['loan_id']) ?></td>
            <td><?= htmlspecialchars($row['loan_name']) ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars(maskAccount($row['account_number'])) ?></td>
            <td><?= number_format($row['amount'], 2) ?></td>
            <td><?= htmlspecialchars($row['interest_rate']) ?></td>
            <td><?= htmlspecialchars($row['term_months']) ?></td>
            <td><?= htmlspecialchars($row['start_date']) ?></td>
            <td><?= htmlspecialchars($row['end_date']) ?></td>
            <td class="status <?= strtolower($row['status']) ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
            <td><?= number_format($row['balance'], 2) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>
