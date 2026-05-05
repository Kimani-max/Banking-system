<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: ../login.php");
    exit;
}
?>
<link rel="stylesheet" href="../../assets/css/style.css">
<?php include '../../admin/includes/header.php'; ?>
<h2>Manager Dashboard</h2>
<p class="dashboard-welcome">
  Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Manager)
</p>
<ul class="dashboard-menu">
    <li><a href="../add_customer.php">Manage Customers</a></li>
    <li><a href="../view_accounts.php">Manage Accounts</a></li>
    <li><a href="../approvals.php">Approve Accounts/Loans</a></li>
    <li><a href="../performance.php">View Performance</a></li>
    <li><a href="../manage_fees.php">Manage Fees</a></li>
    <li><a href="../assign_fees.php">Assign Fees</a></li>
    <li><a href="../reports/reports.php">System Reports</a></li>
    <li><a href="../loans.php">Loans</a></li>
</ul>
<?php include '../../admin/includes/footer.php'; ?>
