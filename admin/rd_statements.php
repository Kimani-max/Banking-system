<?php
require_once '../admin/includes/config.php';

// Filters
$customerId = $_GET['customer_id'] ?? '';
$rdId = $_GET['rd_id'] ?? '';

$where = [];
$params = [];

if ($customerId !== '') {
    $where[] = "rd.customer_id = :cust";
    $params[':cust'] = $customerId;
}
if ($rdId !== '') {
    $where[] = "rd.rd_id = :rd";
    $params[':rd'] = $rdId;
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// Fetch RD list with customer info
$sql = "
  SELECT rd.*, c.full_name, a.account_number
  FROM recurring_deposits rd
  INNER JOIN customers c ON rd.customer_id = c.customer_id
  INNER JOIN accounts a ON rd.account_id = a.account_id
  $whereSQL
  ORDER BY rd.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rds = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch payments for a specific RD
$payments = [];
if ($rdId !== '') {
    $payStmt = $pdo->prepare("
        SELECT * FROM recurring_deposit_payments
        WHERE rd_id = :rd
        ORDER BY payment_date ASC
    ");
    $payStmt->execute([':rd' => $rdId]);
    $payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);
}

function maskAccount($acct) {
    return substr($acct, 0, 2) . str_repeat('*', max(0, strlen($acct) - 6)) . substr($acct, -4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Recurring Deposit Statements</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <div class="rd-statements-container">
    <h2 class="rd-statements-title">📑 Recurring Deposit Statements</h2>

    <form method="get" class="rd-statements-filter">
      <div class="filter-group">
        <label for="customer_id">Customer ID:</label>
        <input type="text" name="customer_id" id="customer_id" value="<?= htmlspecialchars($customerId) ?>">
      </div>

      <div class="filter-group">
        <label for="rd_id">RD ID:</label>
        <input type="text" name="rd_id" id="rd_id" value="<?= htmlspecialchars($rdId) ?>">
      </div>

      <div class="filter-actions">
        <button type="submit" class="btn-filter">Filter</button>
        <a href="rd_statements.php" class="btn_reset">Reset</a>
      </div>
    </form>

    <?php if ($rds): ?>
      <h3 class="rd-section-title">Recurring Deposits</h3>
      <div class="rd-table-wrapper">
        <table class="rd-table">
          <thead>
            <tr>
              <th>RD ID</th>
              <th>Customer</th>
              <th>Account</th>
              <th>Amount</th>
              <th>Term</th>
              <th>Interest Rate</th>
              <th>Maturity Date</th>
              <th>Status</th>
              <th>Maturity Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rds as $rd): 
              $totalDeposit = $rd['deposit_amount'] * $rd['term_months'];
              $interest = ($totalDeposit * $rd['interest_rate'] * $rd['term_months']) / (12 * 100);
              $maturityAmt = $totalDeposit + $interest;
            ?>
              <tr>
                <td><?= htmlspecialchars($rd['rd_id']) ?></td>
                <td><?= htmlspecialchars($rd['full_name']) ?></td>
                <td><?= htmlspecialchars(maskAccount($rd['account_number'])) ?></td>
                <td><?= number_format($rd['deposit_amount'], 2) ?></td>
                <td><?= htmlspecialchars($rd['term_months']) ?> months</td>
                <td><?= htmlspecialchars($rd['interest_rate']) ?>%</td>
                <td><?= htmlspecialchars($rd['maturity_date']) ?></td>
                <td><?= htmlspecialchars(ucfirst($rd['status'])) ?></td>
                <td><?= number_format($maturityAmt, 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="rd-empty-msg">No recurring deposits found.</p>
    <?php endif; ?>

    <?php if ($payments): ?>
      <h3 class="rd-section-title">Payments for RD #<?= htmlspecialchars($rdId) ?></h3>
      <div class="rd-table-wrapper">
        <table class="rd-table">
          <thead>
            <tr>
              <th>Installment</th>
              <th>Amount</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $i => $pay): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= number_format($pay['amount'], 2) ?></td>
                <td><?= htmlspecialchars($pay['payment_date']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php elseif ($rdId !== ''): ?>
      <p class="rd-empty-msg">No payments found for this RD.</p>
    <?php endif; ?>
  </div>
</body>
</html>
