<?php
session_start();
require_once '../../admin/includes/config.php';

// --- Build filters ---
$where  = [];
$params = [];

// date range filter
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

// customer filter
if (!empty($_GET['customer_id'])) {
    $where[] = "c.customer_id = :cust";
    $params[':cust'] = $_GET['customer_id'];
}

// account filter
if (!empty($_GET['account_id'])) {
    $where[] = "a.account_id = :acct";
    $params[':acct'] = $_GET['account_id'];
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// --- Query ---
$sql = "
  SELECT 
      t.txn_id, t.txn_date, t.txn_type, t.amount, t.description,
      a.account_number, c.customer_id, c.full_name
  FROM transactions t
  INNER JOIN accounts a ON t.account_id = a.account_id
  INNER JOIN customers c ON t.customer_id = c.customer_id
  $whereSQL
  ORDER BY t.txn_date ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// dropdown data
$customers = $pdo->query("SELECT customer_id, full_name FROM customers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$accounts  = $pdo->query("SELECT account_id, account_number FROM accounts ORDER BY account_number")->fetchAll(PDO::FETCH_ASSOC);

// helper
function maskAccount($acct) {
    return substr($acct, 0, 2) . str_repeat('*', max(0, strlen($acct) - 6)) . substr($acct, -4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions Report</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="report-container">
        <h2>💰 Transactions Report</h2>

        <!-- Filter Form -->
        <form method="get" class="filter-form">
            <label for="from">From:</label>
            <input type="date" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">

            <label for="to">To:</label>
            <input type="date" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>">

            <label for="customer_id">Customer:</label>
            <select name="customer_id" id="customer_id">
                <option value="">--All Customers--</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['customer_id'] ?>" <?= ($_GET['customer_id'] ?? '') == $c['customer_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="account_id">Account:</label>
            <select name="account_id" id="account_id">
                <option value="">--All Accounts--</option>
                <?php foreach ($accounts as $a): ?>
                    <option value="<?= $a['account_id'] ?>" <?= ($_GET['account_id'] ?? '') == $a['account_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(maskAccount($a['account_number'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn">Generate</button>
            <a href="report_transactions.php" class="btn reset">Reset</a>
        </form>

        <!-- Export Buttons -->
        <form method="get" action="export_report.php" class="export-form">
            <input type="hidden" name="report" value="transactions">
            <button type="submit" name="format" value="csv" class="btn export">Export CSV</button>
            <button type="submit" name="format" value="pdf" class="btn export">Export PDF</button>
        </form>

        <!-- Table -->
        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Account</th>
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
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars(maskAccount($row['account_number'])) ?></td>
                    <td><?= htmlspecialchars(ucfirst($row['txn_type'])) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td><?= number_format($row['amount'], 2) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
