<?php
session_start();
require_once '../admin/includes/config.php';

// Only manager & admin should access
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    header("Location: login.php");
    exit;
}

// --- KPIs ---
$total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$active_accounts = $pdo->query("SELECT COUNT(*) FROM accounts WHERE status='active'")->fetchColumn();
$closed_accounts = $pdo->query("SELECT COUNT(*) FROM accounts WHERE status='closed'")->fetchColumn();

$total_deposits = $pdo->query("SELECT IFNULL(SUM(amount),0) FROM transactions WHERE txn_type='debit'")->fetchColumn();
$total_withdrawals = $pdo->query("SELECT IFNULL(SUM(amount),0) FROM transactions WHERE txn_type='credit'")->fetchColumn();

$today_deposits = $pdo->query("SELECT IFNULL(SUM(amount),0) FROM transactions WHERE txn_type='deposit' AND DATE(txn_date)=CURDATE()")->fetchColumn();
$today_withdrawals = $pdo->query("SELECT IFNULL(SUM(amount),0) FROM transactions WHERE txn_type='withdrawal' AND DATE(txn_date)=CURDATE()")->fetchColumn();

try {
    $total_loans = $pdo->query("SELECT IFNULL(SUM(amount),0) FROM loans")->fetchColumn();
} catch (PDOException $e) { $total_loans = 0; }

try {
    $pending_approvals = $pdo->query("SELECT COUNT(*) FROM approvals WHERE status='pending'")->fetchColumn();
} catch (PDOException $e) { $pending_approvals = 0; }

try {
    $open_tickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status='open'")->fetchColumn();
} catch (PDOException $e) { $open_tickets = 0; }
?>
<link rel="stylesheet" href="../assets/css/style.css">

<h2 class="page-title">Bank Performance Dashboard</h2>

<div class="kpi-grid">
    <div class="kpi-card">
        <h3>Total Customers</h3>
        <p><?php echo $total_customers; ?></p>
    </div>

    <div class="kpi-card">
        <h3>Active Accounts</h3>
        <p><?php echo $active_accounts; ?></p>
    </div>

    <div class="kpi-card">
        <h3>Closed Accounts</h3>
        <p><?php echo $closed_accounts; ?></p>
    </div>

    <div class="kpi-card">
        <h3>Total Deposits</h3>
        <p><?php echo number_format($total_deposits, 2); ?></p>
        <small>Today: <?php echo number_format($today_deposits, 2); ?></small>
    </div>

    <div class="kpi-card">
        <h3>Total Withdrawals</h3>
        <p><?php echo number_format($total_withdrawals, 2); ?></p>
        <small>Today: <?php echo number_format($today_withdrawals, 2); ?></small>
    </div>

    <div class="kpi-card">
        <h3>Loans Issued</h3>
        <p><?php echo number_format($total_loans, 2); ?></p>
    </div>

    <div class="kpi-card">
        <h3>Pending Approvals</h3>
        <p><?php echo $pending_approvals; ?></p>
    </div>

    <div class="kpi-card">
        <h3>Open Tickets</h3>
        <p><?php echo $open_tickets; ?></p>
    </div>
</div>

<hr>

<h3 class="section-title-performance">Visual Reports</h3>
<div class="chart-grid">
    <canvas id="accountsChart"></canvas>
    <canvas id="txnChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Accounts distribution
const accountsCtx = document.getElementById('accountsChart').getContext('2d');
new Chart(accountsCtx, {
    type: 'pie',
    data: {
        labels: ['Active Accounts', 'Closed Accounts'],
        datasets: [{
            data: [<?php echo $active_accounts; ?>, <?php echo $closed_accounts; ?>],
            backgroundColor: ['#4CAF50', '#F44336']
        }]
    }
});

// Deposits vs Withdrawals
const txnCtx = document.getElementById('txnChart').getContext('2d');
new Chart(txnCtx, {
    type: 'bar',
    data: {
        labels: ['Deposits', 'Withdrawals'],
        datasets: [{
            label: 'Amount',
            data: [<?php echo $total_deposits; ?>, <?php echo $total_withdrawals; ?>],
            backgroundColor: ['#2196F3', '#FF9800']
        }]
    },
    options: {
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php include '../admin/includes/footer.php'; ?>
