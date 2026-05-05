<?php
require_once '../admin/includes/config.php';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $account_id  = $_POST['account_id'];
    $amount      = $_POST['amount'];
    $term        = $_POST['term'];

    $rates = [
        1 => 3.0,  3 => 3.5,  6 => 4.0,  12 => 5.0,
        18 => 5.5, 24 => 6.0
    ];
    $rate = $rates[$term] ?? 3.0;

    $start_date    = date('Y-m-d');
    $maturity_date = date('Y-m-d', strtotime("+$term months"));

    $sql = "INSERT INTO fixed_deposits 
            (customer_id, account_id, amount, term_months, interest_rate, start_date, maturity_date, status)
            VALUES (:cust, :acct, :amt, :term, :rate, :start, :maturity, 'active')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cust'     => $customer_id,
        ':acct'     => $account_id,
        ':amt'      => $amount,
        ':term'     => $term,
        ':rate'     => $rate,
        ':start'    => $start_date,
        ':maturity' => $maturity_date
    ]);

    $pdo->prepare("UPDATE accounts SET balance = balance - :amt WHERE account_id = :acct")
        ->execute([':amt' => $amount, ':acct' => $account_id]);

    $pdo->prepare("INSERT INTO transactions (account_id, customer_id, txn_type, amount, description)
                   VALUES (:acct, :cust, 'fd_open', :amt, 'Fixed Deposit Opening')")
        ->execute([':acct' => $account_id, ':cust' => $customer_id, ':amt' => $amount]);

    $success = "✅ Fixed Deposit successfully created!";
}

$customers = $pdo->query("SELECT customer_id, full_name FROM customers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Fixed Deposit</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script>
function loadAccounts(customerId) {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "fetch_accounts.php?customer_id=" + customerId, true);
    xhr.onload = function() {
        if (this.status === 200) {
            document.getElementById("account_id").innerHTML = this.responseText;
        }
    };
    xhr.send();
}
</script>
</head>
<body>
<div class="fd-container">
    <h2 class="fd-title">💰 Add Fixed Deposit</h2>

    <?php if (!empty($success)): ?>
        <div class="fd-message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" class="fd-form">
        <div class="fd-form-group">
            <label for="customer_id">Customer:</label>
            <select name="customer_id" id="customer_id" onchange="loadAccounts(this.value)" required>
                <option value="">--Select Customer--</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="fd-form-group">
            <label for="account_id">Account:</label>
            <select name="account_id" id="account_id" required>
                <option value="">--Select Customer First--</option>
            </select>
        </div>

        <div class="fd-form-group">
            <label for="amount">Amount:</label>
            <input type="number" step="0.01" name="amount" id="amount" required>
        </div>

        <div class="fd-form-group">
            <label for="term">Term (months):</label>
            <select name="term" id="term" required>
                <?php for ($i=1; $i<=24; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> months</option>
                <?php endfor; ?>
            </select>
        </div>

        <button type="submit" class="fd-submit-btn">Create Fixed Deposit</button>
    </form>
</div>
</body>
</html>
