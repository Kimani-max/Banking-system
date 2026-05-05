<?php
require_once '../admin/includes/config.php';

// Process matured FDs
$fdQuery = $pdo->query("
    SELECT fd.fd_id, fd.customer_id, fd.account_id, fd.amount, fd.maturity_amount, fd.maturity_date, 
           a.account_number, c.full_name
    FROM fixed_deposits fd
    INNER JOIN accounts a ON fd.account_id = a.account_id
    INNER JOIN customers c ON fd.customer_id = c.customer_id
    WHERE fd.maturity_date <= NOW() AND fd.status = 'active'
");
$fixedMaturities = $fdQuery->fetchAll(PDO::FETCH_ASSOC);

// Process matured RDs
$rdQuery = $pdo->query("
    SELECT rd.rd_id, rd.customer_id, rd.account_id, rd.maturity_amount, rd.maturity_date, 
           a.account_number, c.full_name
    FROM recurring_deposits rd
    INNER JOIN accounts a ON rd.account_id = a.account_id
    INNER JOIN customers c ON rd.customer_id = c.customer_id
    WHERE rd.maturity_date <= NOW() AND rd.status = 'active'
");
$recurringMaturities = $rdQuery->fetchAll(PDO::FETCH_ASSOC);

// Function to credit maturity to account
function creditMaturity($pdo, $accountId, $customerId, $amount, $desc) {
    // Update account balance
    $pdo->prepare("UPDATE accounts SET balance = balance + :amt WHERE account_id = :acct")
        ->execute([':amt' => $amount, ':acct' => $accountId]);

    // Record transaction
    $stmt = $pdo->prepare("
        INSERT INTO transactions (account_id, customer_id, txn_type, amount, description, txn_date)
        VALUES (:acct, :cust, 'deposit', :amt, :desc, NOW())
    ");
    $stmt->execute([
        ':acct' => $accountId,
        ':cust' => $customerId,
        ':amt'  => $amount,
        ':desc' => $desc
    ]);
}

// Process each FD maturity
foreach ($fixedMaturities as $fd) {
    creditMaturity($pdo, $fd['account_id'], $fd['customer_id'], $fd['maturity_amount'], "FD Maturity Payout");

    $pdo->prepare("UPDATE fixed_deposits SET status='closed', closed_at=NOW() WHERE fd_id=:id")
        ->execute([':id' => $fd['fd_id']]);
}

// Process each RD maturity
foreach ($recurringMaturities as $rd) {
    creditMaturity($pdo, $rd['account_id'], $rd['customer_id'], $rd['maturity_amount'], "RD Maturity Payout");

    $pdo->prepare("UPDATE recurring_deposits SET status='closed', closed_at=NOW() WHERE rd_id=:id")
        ->execute([':id' => $rd['rd_id']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maturity Processing</title>
</head>
<body>
    <h2>Maturity Processing Results</h2>

    <h3>Fixed Deposits</h3>
    <?php if ($fixedMaturities): ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Customer</th>
                <th>Account #</th>
                <th>Maturity Date</th>
                <th>Maturity Amount</th>
                <th>Status</th>
            </tr>
            <?php foreach ($fixedMaturities as $fd): ?>
            <tr>
                <td><?= htmlspecialchars($fd['full_name']) ?></td>
                <td><?= htmlspecialchars($fd['account_number']) ?></td>
                <td><?= htmlspecialchars($fd['maturity_date']) ?></td>
                <td><?= number_format($fd['maturity_amount'], 2) ?></td>
                <td>Credited & Closed</td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No matured fixed deposits found.</p>
    <?php endif; ?>

    <h3>Recurring Deposits</h3>
    <?php if ($recurringMaturities): ?>
        <table border="1" cellpadding="6">
            <tr>
                <th>Customer</th>
                <th>Account #</th>
                <th>Maturity Date</th>
                <th>Maturity Amount</th>
                <th>Status</th>
            </tr>
            <?php foreach ($recurringMaturities as $rd): ?>
            <tr>
                <td><?= htmlspecialchars($rd['full_name']) ?></td>
                <td><?= htmlspecialchars($rd['account_number']) ?></td>
                <td><?= htmlspecialchars($rd['maturity_date']) ?></td>
                <td><?= number_format($rd['maturity_amount'], 2) ?></td>
                <td>Credited & Closed</td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No matured recurring deposits found.</p>
    <?php endif; ?>
</body>
</html>
