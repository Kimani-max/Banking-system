<?php
session_start();
require_once '../../admin/includes/config.php';

// Only allow managers or admins to access reports
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','manager','auditor'])) {
    header("Location: ../login.php");
    exit;
}

// Log access in audit_logs
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address, details) 
                           VALUES (:uid, :action, :ip, :details)");
    $stmt->execute([
        ':uid' => $_SESSION['user_id'],
        ':action' => 'Accessed Reports Menu',
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ':details' => 'User opened the reports menu page'
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports Center</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="reports-container">
        <h2>📊 Reports Center</h2>
        <p>Select the type of report you want to view:</p>

        <div class="report-links">
            <a class="btn" href="report_transactions.php">Transactions Report</a>
            <a class="btn" href="report_loans.php">Loans Report</a>
            <a class="btn" href="report_fees.php">Fees & Charges Report</a>
            <a class="btn" href="report_accounts.php">Accounts Report</a>
            <a class="btn" href="report_customers.php">Customers Report</a>
        </div>
    </div>
</body>
</html>
