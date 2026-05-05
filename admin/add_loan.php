<?php
require_once '../admin/includes/config.php';

// Fetch customers + accounts
$customers = $pdo->query("SELECT customer_id, full_name FROM customers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$accounts  = $pdo->query("SELECT account_id, account_number FROM accounts ORDER BY account_number")->fetchAll(PDO::FETCH_ASSOC);

$message = "";

$loanTypes = [
    "Personal Loan",
    "Business Loan",
    "Car Loan",
    "Education Loan",
    "Home Loan",
    "Emergency Loan"
];

// Interest mapping by term (1–24 months)
$interestMap = [
    1=>2, 2=>3, 3=>4, 4=>5, 5=>6, 6=>7,
    7=>8, 8=>9, 9=>10, 10=>11, 11=>12, 12=>13,
    13=>14, 14=>15, 15=>16, 16=>17, 17=>18, 18=>19,
    19=>19.5, 20=>20, 21=>20.5, 22=>21, 23=>21.5, 24=>22
];

$facilityFeeRate = 2; // % of principal

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loanName   = $_POST['loan_name'];
    $customerId = $_POST['customer_id'];
    $accountId  = $_POST['account_id'];
    $amount     = (float)$_POST['amount'];
    $termMonths = (int)$_POST['term_months'];
    $startDate  = $_POST['start_date'];

    // Ensure valid interest from mapping
    $interestRate = $interestMap[$termMonths] ?? 10;

    $facilityFee  = ($amount * $facilityFeeRate) / 100;
    $loanBalance  = $amount + $facilityFee;
    $endDate      = date('Y-m-d', strtotime("+$termMonths months", strtotime($startDate)));
    $totalPayable = $loanBalance + ($loanBalance * $interestRate / 100);

    try {
        $pdo->beginTransaction();

        $loanSql = "INSERT INTO loans 
        (loan_name, customer_id, account_id, amount, interest_rate, term_months, start_date, end_date, status, balance, facility_fee) 
        VALUES (:lname, :cid, :aid, :amt, :rate, :term, :sdate, :edate, 'active', :bal, :fee)";
        
        $stmt = $pdo->prepare($loanSql);
        $stmt->execute([
            ':lname' => $loanName,
            ':cid'   => $customerId,
            ':aid'   => $accountId,
            ':amt'   => $amount,
            ':rate'  => $interestRate,
            ':term'  => $termMonths,
            ':sdate' => $startDate,
            ':edate' => $endDate,
            ':bal'   => $loanBalance,
            ':fee'   => $facilityFee
        ]);

        // Update account balance
        $pdo->prepare("UPDATE accounts SET balance = balance + :amt WHERE account_id = :aid")
            ->execute([':amt' => $amount, ':aid' => $accountId]);

        // Record transaction
        $txnSql = "INSERT INTO transactions 
        (account_id, customer_id, txn_type, amount, description, txn_date) 
        VALUES (:aid, :cid, 'loan_disbursement', :amt, :desc, NOW())";
        
        $pdo->prepare($txnSql)->execute([
            ':aid'  => $accountId,
            ':cid'  => $customerId,
            ':amt'  => $amount,
            ':desc' => "Loan Disbursement - " . $loanName
        ]);

        $pdo->commit();

        $message = "✅ Loan Disbursed!<br>
        Loan: {$loanName}<br>
        Principal: " . number_format($amount, 2) . "<br>
        Facility Fee: " . number_format($facilityFee, 2) . "<br>
        Interest Rate: {$interestRate}%<br>
        Total Payable: " . number_format($totalPayable, 2) . "<br>
        Due Date: {$endDate}";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "❌ Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Loan</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script>
    const interestMap = <?= json_encode($interestMap) ?>;
    const facilityFeeRate = <?= $facilityFeeRate ?>;

    function updateInterestAndSchedule() {
        const term   = parseInt(document.getElementById('term_months').value) || 0;
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const rate   = interestMap[term] || '';

        document.getElementById('interest_rate').value = rate;

        if (term > 0 && amount > 0 && rate) {
            const fee          = (amount * facilityFeeRate) / 100;
            const loanBalance  = amount + fee;
            const totalPayable = loanBalance + (loanBalance * rate / 100);
            const monthly      = totalPayable / term;

            let schedule = "<h3>Repayment Schedule Preview</h3>";
            schedule += "<table><thead><tr><th>Month</th><th>Installment</th></tr></thead><tbody>";
            for (let i = 1; i <= term; i++) {
                schedule += "<tr><td>" + i + "</td><td>" + monthly.toFixed(2) + "</td></tr>";
            }
            schedule += "</tbody></table>";
            document.getElementById('schedule').innerHTML = schedule;
        } else {
            document.getElementById('schedule').innerHTML = "";
        }
    }
  </script>
</head>
<body>

<div class="muganda">
  <h2>PHARNYBIC BANK</h2>
  <nav>
    <a href="#">Add Loan</a>
    <a href="assign_fees.php">Assign Fees</a>
    <a href="loans.php">View Loan</a>
    <a href="view_accounts.php"> View Accounts</a>
  </nav>
  </div>

<main>
  <h3>Add New Loan</h3>

  <?php if ($message): ?>
    <div class="message"><?= $message ?></div>
  <?php endif; ?>

  <form method="post" class="form-box">
    <label>Loan Name:</label>
    <select name="loan_name" required>
      <option value="">-- Select Loan Type --</option>
      <?php foreach ($loanTypes as $type): ?>
        <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Customer:</label>
    <select name="customer_id" required>
      <option value="">-- Select Customer --</option>
      <?php foreach ($customers as $cust): ?>
        <option value="<?= $cust['customer_id'] ?>"><?= htmlspecialchars($cust['full_name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Account:</label>
    <select name="account_id" required>
      <option value="">-- Select Account --</option>
      <?php foreach ($accounts as $acct): ?>
        <option value="<?= $acct['account_id'] ?>"><?= htmlspecialchars($acct['account_number']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Amount:</label>
    <input type="number" step="0.01" id="amount" name="amount" required oninput="updateInterestAndSchedule()">

    <label>Term (Months):</label>
    <input type="number" id="term_months" name="term_months" min="1" max="24" required oninput="updateInterestAndSchedule()">

    <label>Interest Rate (%):</label>
    <input type="text" id="interest_rate" readonly>

    <label>Start Date:</label>
    <input type="date" name="start_date" required>

    <button type="submit">Add Loan</button>
  </form>

  <div id="schedule"></div>
</main>

</body>
</html>
