<?php
session_start();
require_once '../admin/includes/config.php';

// Only staff, manager, admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['teller', 'manager', 'admin'])) {
    header("Location: login.php");
    exit;
}

$message = "";

// Fetch customers for dropdown
$customers = $pdo->query("SELECT customer_id, full_name FROM customers ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id   = $_POST['customer_id'];
    $account_type  = $_POST['account_type'];
    $balance       = $_POST['balance'];

    // Generate unique account number
    $account_number = "12" . str_pad(rand(0, 99999999), 8, "0", STR_PAD_LEFT);

    try {
        $stmt = $pdo->prepare("INSERT INTO accounts (customer_id, account_number, account_type, balance, status) 
                               VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$customer_id, $account_number, $account_type, $balance]);
        $message = "✅ Account created successfully!<br>Account Number: <b>$account_number</b>";
    } catch (PDOException $e) {
        $message = "❌ Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Open Account</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="account-container">
    <h2 class="account-title">Open New Account</h2>

    <?php if ($message): ?>
        <p class="account-message"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="post" class="account-form">
        <div class="form-open">
            <label for="customer_id">Customer</label>
            <select name="customer_id" id="customer_id" required>
                <option value="">-- Select Customer --</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?php echo $c['customer_id']; ?>">
                        <?php echo htmlspecialchars($c['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-open">
            <label for="account_type">Account Type</label>
            <select name="account_type" id="account_type" required>
                <option value="savings">Savings</option>
                <option value="current">Current</option>
                <option value="fixed">Fixed Deposit</option>
                <option value="recurring">Recurring Deposit</option>
            </select>
        </div>

        <div class="form-open">
            <label for="balance">Initial Balance</label>
            <input type="number" step="0.01" name="balance" id="balance" value="0.00" required>
        </div>

        <button type="submit" class="account-submit">Create Account</button>
    </form>
</div>

<?php include '../admin/includes/footer.php'; ?>
</body>
</html>
