<?php
session_start();
require_once '../../admin/includes/config.php';

// --- Filters ---
$where  = [];
$params = [];

// customer filter
if (!empty($_GET['customer_id'])) {
    $where[] = "l.customer_id = :cust";
    $params[':cust'] = $_GET['customer_id'];
}

// date range (loan start date)
if (!empty($_GET['from']) && !empty($_GET['to'])) {
    $where[] = "DATE(l.start_date) BETWEEN :from AND :to";
    $params[':from'] = $_GET['from'];
    $params[':to']   = $_GET['to'];
} elseif (!empty($_GET['from'])) {
    $where[] = "DATE(l.start_date) >= :from";
    $params[':from'] = $_GET['from'];
} elseif (!empty($_GET['to'])) {
    $where[] = "DATE(l.start_date) <= :to";
    $params[':to'] = $_GET['to'];
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// --- Query ---
$sql = "
  SELECT 
      l.loan_id, l.loan_name, l.customer_id, l.amount, l.interest_rate, 
      l.term_months, l.facility_fee, l.start_date,
      c.full_name
  FROM loans l
  INNER JOIN customers c ON l.customer_id = c.customer_id
  $whereSQL
  ORDER BY l.start_date ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// dropdown for customers
$customers = $pdo->query("SELECT customer_id, full_name FROM customers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

// compute repayments per loan
function getPaidAmount($pdo, $loanId) {
    $s = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM loan_repayments WHERE loan_id = :id");
    $s->execute([':id' => $loanId]);
    return (float)$s->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loans Report</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="report-container">
        <h2>📊 Loans Report</h2>

        <!-- Filter Form -->
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
            <a href="report_loans.php" class="btn reset">Reset</a>
        </form>

        <!-- Loans Table -->
        <table class="styled-reports">
            <thead>
                <tr>
                    <th>Loan ID</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Loan Name</th>
                    <th>Principal</th>
                    <th>Interest Rate (%)</th>
                    <th>Facility Fee</th>
                    <th>Total Repayable</th>
                    <th>Paid</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($loans as $l): 
                $principal = (float)$l['amount'];
                $rate      = (float)$l['interest_rate'];
                $months    = (int)$l['term_months'];
                $fee       = (float)$l['facility_fee'];

                $interestAmt = $principal * ($rate/100) * $months;
                $totalRepay  = $principal + $interestAmt + $fee;
                $paid        = getPaidAmount($pdo, $l['loan_id']);
                $balance     = $totalRepay - $paid;
            ?>
                <tr>
                    <td><?= htmlspecialchars($l['loan_id']) ?></td>
                    <td><?= htmlspecialchars($l['start_date']) ?></td>
                    <td><?= htmlspecialchars($l['full_name']) ?></td>
                    <td><?= htmlspecialchars($l['loan_name']) ?></td>
                    <td><?= number_format($principal, 2) ?></td>
                    <td><?= number_format($rate, 2) ?></td>
                    <td><?= number_format($fee, 2) ?></td>
                    <td><?= number_format($totalRepay, 2) ?></td>
                    <td><?= number_format($paid, 2) ?></td>
                    <td><?= number_format($balance, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
