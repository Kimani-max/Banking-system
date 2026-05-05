<?php
require_once '../admin/includes/config.php';

// Fetch active loans
$loans = $pdo->query("
    SELECT l.loan_id, l.loan_name, l.balance, l.customer_id, c.full_name, a.account_id, a.account_number
    FROM loans l
    INNER JOIN customers c ON l.customer_id = c.customer_id
    INNER JOIN accounts a ON l.account_id = a.account_id
    WHERE l.status = 'active'
    ORDER BY c.full_name
")->fetchAll(PDO::FETCH_ASSOC);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loanId = $_POST['loan_id'] ?? '';
    $amount = (float)($_POST['amount'] ?? 0);

    if ($loanId && $amount > 0) {
        try {
            $pdo->beginTransaction();

            // Fetch loan details
            $loanStmt = $pdo->prepare("SELECT * FROM loans WHERE loan_id = :loan");
            $loanStmt->execute([':loan' => $loanId]);
            $loan = $loanStmt->fetch(PDO::FETCH_ASSOC);

            if (!$loan) {
                throw new Exception("Loan not found.");
            }

            $newBalance = $loan['balance'] - $amount;
            if ($newBalance < 0) {
                throw new Exception("Repayment exceeds loan balance.");
            }

            // Insert into loan_repayments
            $stmt = $pdo->prepare("
                INSERT INTO loan_repayments (loan_id, account_id, customer_id, amount) 
                VALUES (:loan, :acct, :cust, :amt)
            ");
            $stmt->execute([
                ':loan' => $loan['loan_id'],
                ':acct' => $loan['account_id'],
                ':cust' => $loan['customer_id'],
                ':amt'  => $amount
            ]);

            // Insert into transactions
            $stmt = $pdo->prepare("
                INSERT INTO transactions (account_id, customer_id, txn_type, amount, description) 
                VALUES (:acct, :cust, 'loan_repayment', :amt, 'Loan repayment')
            ");
            $stmt->execute([
                ':acct' => $loan['account_id'],
                ':cust' => $loan['customer_id'],
                ':amt'  => $amount
            ]);

            // Update loan balance
            $updateLoan = $pdo->prepare("
                UPDATE loans SET balance = :bal, status = :status WHERE loan_id = :loan
            ");
            $updateLoan->execute([
                ':bal'    => $newBalance,
                ':status' => $newBalance == 0 ? 'cleared' : 'active',
                ':loan'   => $loan['loan_id']
            ]);

            $pdo->commit();
            $message = "✅ Repayment of " . number_format($amount, 2) . " recorded successfully. 
                        New balance: " . number_format($newBalance, 2);

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "❌ Error: " . $e->getMessage();
        }
    } else {
        $message = "⚠️ Please select a loan and enter a valid repayment amount.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Loan Repayment</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="form-container">
    <h2 class="form-title">💰 Add Loan Repayment</h2>

    <?php if ($message): ?>
        <p class="<?= strpos($message, 'Error') !== false ? 'error-message' : 'success-message' ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form method="post" class="styled-form">
        <div class="form-group">
            <label for="loan_id">Select Loan:</label>
            <select name="loan_id" id="loan_id" required onchange="fillBalance(this)">
                <option value="">-- Choose Loan --</option>
                <?php foreach ($loans as $loan): ?>
                    <option value="<?= $loan['loan_id'] ?>" data-balance="<?= $loan['balance'] ?>">
                        <?= htmlspecialchars($loan['full_name'].' - '.$loan['loan_name'].' (Acct: '.$loan['account_number'].')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Current Balance:</label>
            <input type="text" id="balance_display" readonly placeholder="Select a loan first">
        </div>

        <div class="form-group">
            <label for="amount">Repayment Amount:</label>
            <input type="number" step="0.01" name="amount" id="amount" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Submit Repayment</button>
            <a href="loan_repayments.php" class="btn-secondary">View Repayments</a>
        </div>
    </form>
</div>

<script>
function fillBalance(select) {
    let bal = select.options[select.selectedIndex].getAttribute("data-balance");
    document.getElementById("balance_display").value = bal ? parseFloat(bal).toFixed(2) : "";
}
</script>
</body>
</html>
