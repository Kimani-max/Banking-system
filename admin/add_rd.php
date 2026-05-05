<?php 
require_once '../admin/includes/config.php';

// Fetch customers for dropdown
$customers = $pdo->query("SELECT customer_id, full_name FROM customers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id   = $_POST['customer_id'];
    $account_id    = $_POST['account_id'];
    $deposit_amount= $_POST['deposit_amount'];
    $term_months   = $_POST['term_months'];
    $interest_rate = $_POST['interest_rate'];
    $maturity_date = $_POST['maturity_date'];

    $stmt = $pdo->prepare("INSERT INTO recurring_deposits 
        (customer_id, account_id, deposit_amount, term_months, interest_rate, maturity_date) 
        VALUES (:cust, :acct, :amt, :term, :rate, :mat)");
    $stmt->execute([
        ':cust' => $customer_id,
        ':acct' => $account_id,
        ':amt'  => $deposit_amount,
        ':term' => $term_months,
        ':rate' => $interest_rate,
        ':mat'  => $maturity_date
    ]);

    $message = "✅ Recurring Deposit successfully created!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Recurring Deposit</title>
  <link rel="stylesheet" href="../assets/css/style.css">

  <script>
    const interestRates = {
      1: 3.0, 3: 3.5, 6: 4.0, 12: 5.0,
      18: 5.5, 24: 6.0
    };

    function loadAccounts() {
      const custId = document.getElementById('rd_customer_id').value;
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "fetch_accounts.php?customer_id=" + custId, true);
      xhr.onload = function() {
        document.getElementById('rd_account_id').innerHTML = this.responseText;
      };
      xhr.send();
    }

    function updateInterestAndMaturity() {
      const term = parseInt(document.getElementById('rd_term_months').value);
      const start = new Date();
      let maturity = new Date(start.setMonth(start.getMonth() + term));
      let maturityStr = maturity.toISOString().split('T')[0];
      let rate = interestRates[term] ?? 4.0;

      document.getElementById('rd_interest_rate').value = rate;
      document.getElementById('rd_maturity_date').value = maturityStr;
    }
  </script>
</head>
<body>
  <div class="rd-container">
    <h2 class="rd-title">📆 Add Recurring Deposit</h2>

    <?php if ($message): ?>
      <div class="rd-message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" class="rd-form">
      <div class="rd-form-group">
        <label for="rd_customer_id">Customer:</label>
        <select name="customer_id" id="rd_customer_id" onchange="loadAccounts()" required>
          <option value="">-- Select Customer --</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="rd-form-group">
        <label for="rd_account_id">Account:</label>
        <select name="account_id" id="rd_account_id" required>
          <option value="">-- Select Account --</option>
        </select>
      </div>

      <div class="rd-form-group">
        <label for="rd_deposit_amount">Deposit Amount:</label>
        <input type="number" step="0.01" name="deposit_amount" id="rd_deposit_amount" required>
      </div>

      <div class="rd-form-group">
        <label for="rd_term_months">Term (Months):</label>
        <input type="number" name="term_months" id="rd_term_months" min="1" max="24" onchange="updateInterestAndMaturity()" required>
      </div>

      <div class="rd-form-group">
        <label for="rd_interest_rate">Interest Rate (%):</label>
        <input type="text" name="interest_rate" id="rd_interest_rate" readonly>
      </div>

      <div class="rd-form-group">
        <label for="rd_maturity_date">Maturity Date:</label>
        <input type="date" name="maturity_date" id="rd_maturity_date" readonly>
      </div>

      <button type="submit" class="rd-submit-btn">Create Recurring Deposit</button>
    </form>
  </div>
</body>
</html>
