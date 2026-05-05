<?php
session_start();
require_once '../admin/includes/config.php';

// Only staff, manager, admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['teller', 'manager', 'admin'])) {
    header("Location: login.php");
    exit;
}

$search = $_GET['search'] ?? '';
$query = "SELECT a.*, c.full_name 
          FROM accounts a 
          JOIN customers c ON a.customer_id = c.customer_id 
          WHERE 1";
$params = [];

if ($search) {
    $query .= " AND (a.account_number LIKE ? OR c.full_name LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params = [$searchTerm, $searchTerm];
}

$query .= " ORDER BY a.opened_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Accounts List</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="accounts-container">
    <h2 class="accounts-title">Accounts List</h2>
    <a href="open_account.php" class="accounts-add-btn">+ Open New Account</a>

    <form method="get" class="accounts-search-form">
        <input type="text" name="search" placeholder="Search by account number or customer" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
        <a href="view_accounts.php" class="reset-link">Reset</a>
    </form>

    <table class="accounts-table">
        <tr>
            <th>Account ID</th>
            <th>Account Number</th>
            <th>Customer</th>
            <th>Type</th>
            <th>Balance</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
        <?php if ($accounts): ?>
            <?php foreach ($accounts as $a): ?>
                <tr>
                    <td><?php echo $a['account_id']; ?></td>
                    <td><?php echo htmlspecialchars($a['account_number']); ?></td>
                    <td><?php echo htmlspecialchars($a['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($a['account_type']); ?></td>
                    <td><?php echo number_format($a['balance'], 2); ?></td>
                    <td><?php echo htmlspecialchars($a['status']); ?></td>
                    <td><?php echo $a['opened_at']; ?></td>
                    <td>
                        <a href="edit_account.php?id=<?php echo $a['account_id']; ?>" class="edit-link">Edit</a> |
                        <a href="close_account.php?id=<?php echo $a['account_id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to close this account?');">Close</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8">No accounts found.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php include '../admin/includes/footer.php'; ?>
</body>
</html>
