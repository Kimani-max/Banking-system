<?php
session_start();
require_once '../admin/includes/config.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];

if ($query !== '') {
    // Search customers
    $stmt = $pdo->prepare("SELECT 'customer' AS type, customer_id, national_id, full_name, email 
                           FROM customers 
                           WHERE full_name LIKE ? OR national_id LIKE ? OR email LIKE ?");
    $stmt->execute(["%$query%", "%$query%", "%$query%"]);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Search accounts
    $stmt = $pdo->prepare("SELECT 'account' AS type, account_id, account_number, account_type, balance 
                           FROM accounts 
                           WHERE account_number LIKE ?");
    $stmt->execute(["%$query%"]);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Search Results</title>
    <meta charset="UTF-8">
</head>
<body>

<h2>Search Results for "<?php echo htmlspecialchars($query); ?>"</h2>

<?php if ($query === ''): ?>
    <p>Please enter a search term.</p>

<?php elseif (empty($results)): ?>
    <p>No results found.</p>

<?php else: ?>
    <table border="1" cellpadding="5">
        <tr>
            <th>Type</th>
            <th>Details</th>
        </tr>
        <?php foreach ($results as $row): ?>
            <tr>
                <td><?php echo ucfirst($row['type']); ?></td>
                <td>
                    <?php if ($row['type'] === 'customer'): ?>
                        Name: <?php echo htmlspecialchars($row['full_name']); ?><br>
                        National ID: <?php echo htmlspecialchars($row['national_id']); ?><br>
                        Email: <?php echo htmlspecialchars($row['email']); ?>
                    <?php elseif ($row['type'] === 'account'): ?>
                        Account Number: <?php echo htmlspecialchars($row['account_number']); ?><br>
                        Type: <?php echo htmlspecialchars($row['account_type']); ?><br>
                        Balance: <?php echo number_format($row['balance'], 2); ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<p><a href="index.php">Back to Home</a></p>

</body>
</html>
