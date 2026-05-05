<?php
require_once '../admin/includes/config.php';

// Fetch all customers
$customers = $pdo->query("SELECT customer_id, full_name FROM customers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

$accounts = [];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $from_account = $_POST['from_account_id'];
    $to_account = $_POST['to_account_id'];
    $amount = $_POST['amount'];
    $frequency = $_POST['frequency'];
    $next_run_date = $_POST['next_run_date'];

    $sql = "INSERT INTO standing_orders (customer_id, from_account_id, to_account_id, amount, frequency, next_run_date, status) 
            VALUES (:cust, :from_acc, :to_acc, :amt, :freq, :run_date, 'active')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cust' => $customer_id,
        ':from_acc' => $from_account,
        ':to_acc' => $to_account,
        ':amt' => $amount,
        ':freq' => $frequency,
        ':run_date' => $next_run_date
    ]);

    $message = "✅ Standing order added successfully.";
}

// Fetch accounts for dropdowns when a customer is selected
if (!empty($_GET['customer_id'])) {
    $cid = $_GET['customer_id'];
    $stmt = $pdo->prepare("SELECT account_id, account_number FROM accounts WHERE customer_id = :cid");
    $stmt->execute([':cid' => $cid]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function maskAccount($acct) {
    return substr($acct, 0, 2) . str_repeat('*', max(0, strlen($acct) - 6)) . substr($acct, -4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Standing Order</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="form-container">
  <h2 class="form-title">🏦 Add Standing Order</h2>

  <?php if ($message): ?>
    <p class="success-message"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <form method="post" class="styled-form">
      <div class="form-group">
          <label for="customer_id">Customer:</label>
          <select name="customer_id" id="customer_id" onchange="window.location='?customer_id='+this.value" required>
              <option value="">-- Select Customer --</option>
              <?php foreach ($customers as $c): ?>
                  <option value="<?= $c['customer_id'] ?>" <?= ($_GET['customer_id']??'')==$c['customer_id']?'selected':'' ?>>
                      <?= htmlspecialchars($c['full_name']) ?>
                  </option>
              <?php endforeach; ?>
          </select>
      </div>

      <?php if (!empty($accounts)): ?>
      <div class="form-group">
          <label for="from_account_id">From Account:</label>
          <select name="from_account_id" id="from_account_id" required>
              <?php foreach ($accounts as $a): ?>
                  <option value="<?= $a['account_id'] ?>">
                      <?= htmlspecialchars(maskAccount($a['account_number'])) ?>
                  </option>
              <?php endforeach; ?>
          </select>
      </div>

      <div class="form-group">
          <label for="to_account_id">To Account:</label>
          <select name="to_account_id" id="to_account_id" required>
              <?php foreach ($accounts as $a): ?>
                  <option value="<?= $a['account_id'] ?>">
                      <?= htmlspecialchars(maskAccount($a['account_number'])) ?>
                  </option>
              <?php endforeach; ?>
          </select>
      </div>
      <?php endif; ?>

      <div class="form-group">
          <label for="amount">Amount:</label>
          <input type="number" step="0.01" name="amount" id="amount" required>
      </div>

      <div class="form-group">
          <label for="frequency">Frequency:</label>
          <select name="frequency" id="frequency" required>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
          </select>
      </div>

      <div class="form-group">
          <label for="next_run_date">Next Run Date:</label>
          <input type="date" name="next_run_date" id="next_run_date" required>
      </div>

      <div class="form-actions">
          <button type="submit" class="btn-primary">Add Standing Order</button>
          <a href="standing_orders.php" class="btn-secondary">View Orders</a>
      </div>
  </form>
</div>
</body>
</html>
