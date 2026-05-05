<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
?>
<?php include '../../admin/includes/header.php'; ?>

<link rel="stylesheet" href="../../assets/css/style.css">

<div class="admin-dashboard-container">
    <h2 class="admin-dashboard-title">Admin Dashboard</h2>
    <p class="admin-dashboard-welcome">
        Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> <span class="admin-role">(Admin)</span>
    </p>

    <div class="admin-dashboard-cards">
        <a href="../open_account.php" class="admin-dashboard-card">Open Account</a>
        <a href="../accounts.php" class="admin-dashboard-card">View Accounts</a>
        <a href="../users.php" class="admin-dashboard-card">Manage Users</a>
        <a href="../add_customer.php" class="admin-dashboard-card">Manage Customers</a>
        <a href="../reports/reports.php" class="admin-dashboard-card">System Reports</a>
        <a href="../manage_fees.php" class="admin-dashboard-card">Manage Fees</a>
        <a href="../assign_fees.php" class="admin-dashboard-card">Assign Fees</a>
        <a href="../add_loan.php" class="admin-dashboard-card">Add Loan</a>
    </div>
</div>

<?php include '../../admin/includes/footer.php'; ?>
