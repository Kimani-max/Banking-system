<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teller') {
    header("Location: ../login.php");
    exit;
}
?>
<?php include '../../admin/includes/header.php'; ?>

<link rel="stylesheet" href="../../assets/css/style.css">

<div class="teller-dashboard-container">
    <h2 class="teller-dashboard-title">Teller Dashboard</h2>
    <p class="teller-dashboard-welcome">
        Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> <span class="teller-role">(Teller)</span>
    </p>

    <div class="teller-dashboard-grid">
        <a href="../add_customer.php" class="teller-card">👥 Manage Customers</a>
        <a href="../view_accounts.php" class="teller-card">💳 Manage Accounts</a>
        <a href="../open_account.php" class="teller-card">🏦 Open Account</a>
        <a href="../../api/deposit.php" class="teller-card">💰 Deposit to Account</a>
        <a href="../../api/withdraw.php" class="teller-card">💸 Withdraw from Account</a>
        <a href="../statements.php" class="teller-card">📄 Account Statements</a>
        <a href="../add_fixed_deposit.php" class="teller-card">💼 Manage Fixed Deposit</a>
        <a href="../assign_fees.php" class="teller-card">💵 Assign Fees</a>
        <a href="../add_rd.php" class="teller-card">🔁 Manage Recurring Deposits</a>
        <a href="../rd_statements.php" class="teller-card">🧾 RD Statements</a>
        <a href="../standing_orders.php" class="teller-card">📋 Standing Orders</a>
        <a href="../add_standing_order.php" class="teller-card">⚙️ Manage Standing Order</a>
        <a href="../loans.php" class="teller-card">🏠 Loans</a>
        <a href="../add_loan.php" class="teller-card">💼 Manage Loans</a>
        <a href="../add_repayment.php" class="teller-card">💳 Loan Repayments</a>
    </div>
</div>

<?php include '../../admin/includes/footer.php'; ?>
