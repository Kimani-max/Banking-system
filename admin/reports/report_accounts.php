<?php
require_once '../../admin/includes/config.php';

// --- Filters ---
$where  = [];
$params = [];

if (!empty($_GET['customer_id'])) {
    $where[] = "a.customer_id = :cust";
    $params[':cust'] = $_GET['customer_id'];
}
if (!empty($_GET['type'])) {
    $where[] = "a.account_type = :type";
    $params[':type'] = $_GET['type'];
}
if (!empty($_GET['status'])) {
    $where[] = "a.status = :status";
    $params[':status'] = $_GET['status'];
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// --- Query ---
$sql = "
  SELECT 
      a.account_id, a.account_number, a.account_type, a.balance, a.status, a.opened_at,
      c.customer_id, c.full_name
  FROM accounts a
  INNER JOIN customers c ON a.customer_id = c.customer_id
  $whereSQL
  ORDER BY a.opened_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// dropdown customers
$customers = $pdo->query("SELECT customer_id, full_name FROM customers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

function maskAccount($acct) {
    return substr($acct, 0, 2) . str_repeat('*', max(0, strlen($acct) - 6)) . substr($acct, -4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Accounts Report</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="report-container"> 
        <h2>📊 Account Fees Report</h2>

        <form method="get" class="filter-form">
            <label for="customer_id">Customer:</label>
            <select name="customer_id" id="customer_id">
                <option value="">--All Customers--</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['customer_id'] ?>" <?= ($_GET['customer_id'] ?? '') == $c['customer_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="type">Type:</label>
            <select name="type" id="type">
                <option value="">--All--</option>
                <option value="savings" <?= ($_GET['type'] ?? '')=='savings'?'selected':'' ?>>Savings</option>
                <option value="checking" <?= ($_GET['type'] ?? '')=='checking'?'selected':'' ?>>Checking</option>
                <option value="fixed" <?= ($_GET['type'] ?? '')=='fixed'?'selected':'' ?>>Fixed Deposit</option>
            </select>

            <label for="status">Status:</label>
            <select name="status" id="status">
                <option value="">--All--</option>
                <option value="active" <?= ($_GET['status'] ?? '')=='active'?'selected':'' ?>>Active</option>
                <option value="inactive" <?= ($_GET['status'] ?? '')=='inactive'?'selected':'' ?>>Inactive</option>
                <option value="closed" <?= ($_GET['status'] ?? '')=='closed'?'selected':'' ?>>Closed</option>
            </select>

            <button type="submit" class="btn">Generate</button>
            <a href="report_accounts.php" class="btn reset">Reset</a>
        </form>

        <form method="get" action="export_report.php" class="export-form">
            <input type="hidden" name="report" value="accounts">
            <button type="submit" name="format" value="csv" class="btn export">Export CSV</button>
            <button type="submit" name="format" value="pdf" class="btn export">Export PDF</button>
        </form>

        <table class="styled-reports">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Account #</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Balance</th>
                    <th>Opened</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($accounts): ?>
                <?php foreach ($accounts as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['account_id']) ?></td>
                        <td><?= htmlspecialchars(maskAccount($a['account_number'])) ?></td>
                        <td><?= htmlspecialchars($a['full_name']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($a['account_type'])) ?></td>
                        <td><?= htmlspecialchars(ucfirst($a['status'])) ?></td>
                        <td><?= number_format($a['balance'], 2) ?></td>
                        <td><?= htmlspecialchars($a['opened_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7">No accounts found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
```
