<?php
require_once '../../admin/includes/config.php';

// --- Filters ---
$where  = [];
$params = [];

// customer filter
if (!empty($_GET['customer_id'])) {
    $where[] = "a.customer_id = :cust";
    $params[':cust'] = $_GET['customer_id'];
}

// date range
if (!empty($_GET['from']) && !empty($_GET['to'])) {
    $where[] = "DATE(af.assigned_at) BETWEEN :from AND :to";
    $params[':from'] = $_GET['from'];
    $params[':to']   = $_GET['to'];
} elseif (!empty($_GET['from'])) {
    $where[] = "DATE(af.assigned_at) >= :from";
    $params[':from'] = $_GET['from'];
} elseif (!empty($_GET['to'])) {
    $where[] = "DATE(af.assigned_at) <= :to";
    $params[':to'] = $_GET['to'];
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// --- Query ---
$sql = "
  SELECT 
      af.account_fee_id, af.account_id, af.fee_id, af.last_applied,
      f.fee_name, f.amount, c.full_name, a.account_number
  FROM account_fees af
  INNER JOIN accounts a  ON af.account_id = a.account_id
  INNER JOIN customers c ON a.customer_id = c.customer_id
  INNER JOIN fees f      ON af.fee_id = f.fee_id
  $whereSQL
  ORDER BY af.last_applied DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// dropdown for customers
$customers = $pdo->query("SELECT customer_id, full_name FROM customers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

// helper to mask account
function maskAccount($acct) {
    return substr($acct,0,2).str_repeat('*',max(0,strlen($acct)-6)).substr($acct,-4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Fees Report</title>
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

            <label for="from">From:</label>
            <input type="date" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">

            <label for="to">To:</label>
            <input type="date" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>">

            <button type="submit" class="btn">Generate</button>
            <a href="report_fees.php" class="btn reset">Reset</a>
        </form>

        <form method="get" action="export_report.php" class="export-form">
            <input type="hidden" name="report" value="fees">
            <button type="submit" name="format" value="csv" class="btn export">Export CSV</button>
            <button type="submit" name="format" value="pdf" class="btn export">Export PDF</button>
        </form>

        <table class="styled-reports">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Account</th>
                    <th>Fee Name</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($fees): ?>
                <?php foreach ($fees as $f): ?>
                    <tr>
                        <td><?= htmlspecialchars($f['account_fee_id']) ?></td>
                        <td><?= htmlspecialchars($f['last_applied']) ?></td>
                        <td><?= htmlspecialchars($f['full_name']) ?></td>
                        <td><?= htmlspecialchars(maskAccount($f['account_number'])) ?></td>
                        <td><?= htmlspecialchars($f['fee_name']) ?></td>
                        <td><?= number_format($f['amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">No records found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
```
