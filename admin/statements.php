<?php
require_once '../admin/includes/config.php'; // adjust if needed

error_reporting(E_ALL);
ini_set('display_errors', 1);

$accountId = $_GET['account_id'] ?? '';
$from = $_GET['from'] ?? '';
$to   = $_GET['to']   ?? '';

$where = [];
$params = [];

if ($accountId !== '') {
    $where[] = "t.account_id = :acct";
    $params[':acct'] = $accountId;
}
if ($from && $to) {
    $where[] = "DATE(t.txn_date) BETWEEN :from AND :to";
    $params[':from'] = $from;
    $params[':to']   = $to;
} elseif ($from) {
    $where[] = "DATE(t.txn_date) >= :from";
    $params[':from'] = $from;
} elseif ($to) {
    $where[] = "DATE(t.txn_date) <= :to";
    $params[':to'] = $to;
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// fetch all accounts for dropdown
$acctList = $pdo->query("SELECT account_id, account_number FROM accounts ORDER BY account_number")->fetchAll(PDO::FETCH_ASSOC);

// fetch current balance (if account chosen)
$currentBal = 0.0;
if ($accountId !== '') {
    $balStmt = $pdo->prepare("SELECT balance FROM accounts WHERE account_id = :acct");
    $balStmt->execute([':acct' => $accountId]);
    $currentBal = (float)$balStmt->fetchColumn();
}

// fetch transactions newest→oldest (include balance_after)
$data = [];
if ($accountId !== '') {
    $sql = "
      SELECT 
        t.txn_date, t.txn_type, t.amount, t.description, t.balance_after,
        c.full_name
      FROM transactions t
      INNER JOIN customers c ON t.customer_id = c.customer_id
      $whereSQL
      ORDER BY t.txn_date ASC, t.txn_id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Compute display balances: use balance_after when present, otherwise compute by reversing from current balance
$computed = [];
if (!empty($data)) {
    $running = $currentBal; // start from current balance
    foreach ($data as $row) {
        // If transaction row has balance_after stored, trust it and reset running to it
        if (isset($row['balance_after']) && $row['balance_after'] !== null && $row['balance_after'] !== '') {
            $balAfter = (float)$row['balance_after'];
            $running = $balAfter;
        } else {
            // No stored balance_after: use current running (which started as current balance or last known)
            $balAfter = $running;
        }

        // Save computed value on the row
        $row['balance_after_computed'] = $balAfter;
        $computed[] = $row;

        // Reverse the effect for the next, older transaction
        $amt = (float)$row['amount'];
        $type = strtolower($row['txn_type'] ?? '');
        if ($type === 'deposit') {
            // walking backwards, remove deposit
            $running -= $amt;
        } elseif ($type === 'withdraw' || $type === 'withdrawal') {
            // walking backwards, restore withdrawn amount
            $running += $amt;
        } else {
            // If other txn types exist, adjust here as needed
        }
    }
}

function maskAccount($acct) {
    return substr($acct,0,2).str_repeat('*',max(0,strlen($acct)-6)).substr($acct,-4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Account Statement</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="love">
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
<h2>Account Statement</h2>

<form method="get" class="filter">
    <label for="account_id">Account:</label>
    <select name="account_id" id="account_id" required>
        <option value="">--Select Account--</option>
        <?php foreach ($acctList as $acct): ?>
            <option value="<?= htmlspecialchars($acct['account_id']) ?>" <?= $accountId == $acct['account_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars(maskAccount($acct['account_number'])) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="from">From:</label>
    <input type="date" name="from" id="from" value="<?= htmlspecialchars($from) ?>">

    <label for="to">To:</label>
    <input type="date" name="to" id="to" value="<?= htmlspecialchars($to) ?>">

    <button type="submit">View</button>
    <a href="statements.php" class="reset-btn">Reset</a>
</form>

<?php if ($accountId === ''): ?>
    <p class="empty-note">Select an account to view its statement.</p>
<?php elseif (empty($computed)): ?>
    <p class="empty-note">No transactions found for the chosen account/date range.</p>
<?php else: ?>
<table>
  <thead>
    <tr>
      <th>Date</th>
      <th>Description</th>
      <th>Type</th>
      <th>Amount</th>
      <th>Balance (after txn)</th>
      <th>By</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($computed as $row): ?>
    <tr>
      <td><?= htmlspecialchars($row['txn_date']) ?></td>
      <td><?= htmlspecialchars($row['description']) ?></td>
      <td><?= htmlspecialchars(ucfirst($row['txn_type'])) ?></td>
      <td><?= number_format((float)$row['amount'], 2) ?></td>
      <td><?= number_format((float)$row['balance_after_computed'], 2) ?></td>
      <td><?= htmlspecialchars($row['full_name']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
</main>

</body>
</html>
