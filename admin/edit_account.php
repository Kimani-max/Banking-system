<?php
session_start();
require_once '../admin/includes/config.php';

// Only staff, manager, admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['teller', 'manager', 'admin'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: view_accounts.php");
    exit;
}

$id = $_GET['id'];
$message = "";

// Fetch account
$stmt = $pdo->prepare("SELECT * FROM accounts WHERE account_id = ?");
$stmt->execute([$id]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account) {
    header("Location: view_accounts.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_type = $_POST['account_type'];
    $status = $_POST['status'];

    try {
        $update = $pdo->prepare("UPDATE accounts SET account_type = ?, status = ? WHERE account_id = ?");
        $update->execute([$account_type, $status, $id]);
        $message = "✅ Account updated successfully!";
        // refresh account
        $stmt->execute([$id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "❌ Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Account</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="edit-account-container">
    <h2 class="edit-title">Edit Account</h2>

    <?php if ($message): ?>
        <div class="edit-message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="post" class="edit-form">
        <div class="form-group">
            <label>Account Number</label>
            <input type="text" value="<?php echo htmlspecialchars($account['account_number']); ?>" disabled>
        </div>

        <div class="form-group">
            <label>Account Type</label>
            <select name="account_type" required>
                <option value="savings" <?php if ($account['account_type']=='savings') echo "selected"; ?>>Savings</option>
                <option value="current" <?php if ($account['account_type']=='current') echo "selected"; ?>>Current</option>
                <option value="fixed" <?php if ($account['account_type']=='fixed') echo "selected"; ?>>Fixed Deposit</option>
                <option value="recurring" <?php if ($account['account_type']=='recurring') echo "selected"; ?>>Recurring Deposit</option>
            </select>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status" required>
                <option value="active" <?php if ($account['status']=='active') echo "selected"; ?>>Active</option>
                <option value="closed" <?php if ($account['status']=='closed') echo "selected"; ?>>Closed</option>
            </select>
        </div>

        <button type="submit" class="btn-update">Update Account</button>
        <a href="view_accounts.php" class="btn-secondary">Cancel</a>
    </form>
</div>

<?php include '../admin/includes/footer.php'; ?>
</body>
</html>
