<?php
require_once '../admin/includes/config.php';

// --- Build WHERE clause ---
$where  = [];
$params = [];

// description filter
if (!empty($_GET['desc'])) {
    $where[] = "t.description LIKE :desc";
    $params[':desc'] = '%' . $_GET['desc'] . '%';
}

// date range
if (!empty($_GET['from']) && !empty($_GET['to'])) {
    $where[] = "DATE(t.txn_date) BETWEEN :from AND :to";
    $params[':from'] = $_GET['from'];
    $params[':to']   = $_GET['to'];
} elseif (!empty($_GET['from'])) {
    $where[] = "DATE(t.txn_date) >= :from";
    $params[':from'] = $_GET['from'];
} elseif (!empty($_GET['to'])) {
    $where[] = "DATE(t.txn_date) <= :to";
    $params[':to'] = $_GET['to'];
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// --- Query ---
$sql = "
  SELECT 
      t.txn_id, 
      t.txn_type, 
      t.amount, 
      t.description,
      t.txn_date,
      t.customer_id,
      a.account_id,
      a.account_number,
      c.full_name
  FROM transactions t
  INNER JOIN accounts a ON t.account_id = a.account_id
  INNER JOIN customers c ON t.customer_id = c.customer_id
  $whereSQL
  ORDER BY t.txn_date ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

function maskAccount($acct) {
    return substr($acct, 0, 2) . str_repeat('*', max(0, strlen($acct) - 6)) . substr($acct, -4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transactions List</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="emeka">
  <h1>Bank System - Transactions</h1>
  <nav>
    <a href="dashboards/admin_dashboard.php">Dashboard</a>
    <a href="accounts.php">Accounts</a>
    <a href="view_customers.php">Customers</a>
    <a href="transactions.php" class="active">Transactions</a>
    <a href="loans.php">Loans</a>
    <a href="../logout.php">Logout</a>
  </nav>
</div>

<main>
  <h2>All Transactions</h2>

  <form method="get" class="filter-bar">
      <label for="desc">Description:</label>
      <input type="text" name="desc" id="desc" value="<?= htmlspecialchars($_GET['desc'] ?? '') ?>">

      <label for="from">From:</label>
      <input type="date" name="from" id="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">

      <label for="to">To:</label>
      <input type="date" name="to" id="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>">

      <button type="submit">Filter</button>
      <a href="transactions.php" class="reset-btn">Reset</a>
  </form>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Txn ID</th>
          <th>Date</th>
          <th>Account</th>
          <th>Customer ID</th>
          <th>Customer</th>
          <th>Type</th>
          <th>Description</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
          <tr>
            <td><?= htmlspecialchars($row['txn_id']) ?></td>
            <td><?= htmlspecialchars($row['txn_date']) ?></td>
            <td><?= htmlspecialchars($row['account_id'].' | '.maskAccount($row['account_number'])) ?></td>
            <td><?= htmlspecialchars($row['customer_id']) ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars(ucfirst($row['txn_type'])) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td><?= number_format($row['amount'], 2) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>
