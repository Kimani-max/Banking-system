<?php
session_start();
require_once '../admin/includes/config.php';

// Restrict access
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['manager','admin'])) {
    header("Location: login.php");
    exit;
}

$message = "";

// Approve / Reject action
if (isset($_GET['action'], $_GET['id'], $_GET['type'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);
    $type = $_GET['type'];
    $user_id = $_SESSION['user_id']; // approver id

    try {
        if ($type === 'account') {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE accounts SET status='active', approved_by=? WHERE account_id=?");
                $stmt->execute([$user_id, $id]);
                $message = "✅ Account approved.";
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE accounts SET status='rejected', approved_by=? WHERE account_id=?");
                $stmt->execute([$user_id, $id]);
                $message = "❌ Account rejected.";
            }
        } elseif ($type === 'loan') {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE loans SET status='approved', approved_by=? WHERE loan_id=?");
                $stmt->execute([$user_id, $id]);
                $message = "✅ Loan approved.";
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE loans SET status='rejected', approved_by=? WHERE loan_id=?");
                $stmt->execute([$user_id, $id]);
                $message = "❌ Loan rejected.";
            }
        }
    } catch (Exception $e) {
        $message = "⚠️ Error: " . $e->getMessage();
    }
}

// Fetch pending accounts
$pending_accounts = [];
try {
    $pending_accounts = $pdo->query("SELECT a.account_id, a.account_number, c.first_name, c.last_name, a.account_type 
                                     FROM accounts a
                                     JOIN customers c ON a.customer_id = c.customer_id
                                     WHERE a.status='pending'
                                     ORDER BY a.created_at DESC")->fetchAll();
} catch (PDOException $e) {}

// Fetch pending loans
$pending_loans = [];
try {
    $pending_loans = $pdo->query("SELECT l.loan_id, c.first_name, c.last_name, l.amount, l.loan_type 
                                  FROM loans l
                                  JOIN customers c ON l.customer_id = c.customer_id
                                  WHERE l.status='pending'
                                  ORDER BY l.created_at DESC")->fetchAll();
} catch (PDOException $e) {}
?>
<link rel="stylesheet" href="../assets/css/style.css">
<h2 class="page-title">Approvals Dashboard</h2>

<?php if ($message): ?>
    <p class="status-message"><?php echo $message; ?></p>
<?php endif; ?>

<h3 class="section-title-approvals">Pending Account Approvals</h3>
<?php if ($pending_accounts): ?>
<table class="styled-table">
    <thead>
        <tr>
            <th>Account Number</th>
            <th>Customer</th>
            <th>Account Type</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($pending_accounts as $acc): ?>
        <tr>
            <td><?php echo htmlspecialchars($acc['account_number']); ?></td>
            <td><?php echo htmlspecialchars($acc['first_name']." ".$acc['last_name']); ?></td>
            <td><?php echo htmlspecialchars($acc['account_type']); ?></td>
            <td class="action-links">
                <a href="?action=approve&type=account&id=<?php echo $acc['account_id']; ?>" class="btn-approve">Approve</a>
                <a href="?action=reject&type=account&id=<?php echo $acc['account_id']; ?>" class="btn-reject">Reject</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p class="empty-note">No pending accounts.</p>
<?php endif; ?>

<h3 class="section-title-approvals">Pending Loan Approvals</h3>
<?php if ($pending_loans): ?>
<table class="styled-table">
    <thead>
        <tr>
            <th>Customer</th>
            <th>Loan Type</th>
            <th>Amount</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($pending_loans as $loan): ?>
        <tr>
            <td><?php echo htmlspecialchars($loan['first_name']." ".$loan['last_name']); ?></td>
            <td><?php echo htmlspecialchars($loan['loan_type']); ?></td>
            <td><?php echo number_format($loan['amount'],2); ?></td>
            <td class="action-links">
                <a href="?action=approve&type=loan&id=<?php echo $loan['loan_id']; ?>" class="btn-approve">Approve</a>
                <a href="?action=reject&type=loan&id=<?php echo $loan['loan_id']; ?>" class="btn-reject">Reject</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p class="empty-note">No pending loans.</p>
<?php endif; ?>
