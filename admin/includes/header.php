<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<header>
    <div class="header-logo">
    <img src="../../assets/images/bank_logo.png" alt="Bank Logo" class="logo">
    <span>PHARNYBIC BANK</span>
    </div>
    <?php if (isset($_SESSION['username'])): ?>
        <nav>
            <a href="#">Dashboard</a>
            <a href="../view_customers.php">Customers</a>
            <a href="../accounts.php">Accounts</a>
            <a href="../transactions.php">Transactions</a>
            <a href="../statements.php">Statements</a>
            <a href="../logout.php">Logout</a>
        </nav>
    <?php endif; ?>
</header>
