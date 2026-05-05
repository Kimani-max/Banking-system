<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'auditor') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Auditor Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

    <div class="fruit">
        <h1>Auditor Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <strong><?= htmlspecialchars($_SESSION['username']); ?></strong> (Auditor)</span>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
</div>

    <main class="dashboard-container">
        <section class="card-grid">
            <a href="../audit_logs.php" class="card">
                <h3>Audit Logs</h3>
                <p>Review all system audit trails and activities</p>
            </a>

            <a href="../reports/reports.php" class="card">
                <h3>Compliance Reports</h3>
                <p>Generate and verify compliance reports</p>
            </a>

            <a href="../transactions.php" class="card">
                <h3>Transactions</h3>
                <p>Inspect transaction history and activities</p>
            </a>

            <a href="../loans.php" class="card">
                <h3>Loans</h3>
                <p>Examine loan issuance and repayment records</p>
            </a>

            <a href="../statements.php" class="card">
                <h3>Account Statements</h3>
                <p>Access customer account statements</p>
            </a>

            <a href="../rd_statements.php" class="card">
                <h3>Recurring Deposits</h3>
                <p>Check RD statements and maturity info</p>
            </a>

            <a href="../standing_orders.php" class="card">
                <h3>Standing Orders</h3>
                <p>Monitor standing order transactions</p>
            </a>
        </section>
    </main>

    <?php include '../../admin/includes/footer.php'; ?>
</body>
</html>
