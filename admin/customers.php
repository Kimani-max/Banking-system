<?php
require_once '../admin/includes/config.php';

// Fetch customers with their accounts
$sql = "
    SELECT 
        c.customer_id,
        c.full_name,
        c.email,
        c.phone,
        c.address,
        c.created_at,
        a.account_number
    FROM customers c
    LEFT JOIN accounts a ON c.customer_id = a.customer_id
    ORDER BY c.customer_id ASC
";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group accounts under customers
$customers = [];
foreach ($rows as $row) {
    $id = $row['customer_id'];
    if (!isset($customers[$id])) {
        $customers[$id] = [
            'customer_id' => $row['customer_id'],
            'full_name'   => $row['full_name'],
            'email'       => $row['email'],
            'phone'       => $row['phone'],
            'address'     => $row['address'],
            'created_at'  => $row['created_at'],
            'accounts'    => []
        ];
    }
    if ($row['account_number']) {
        $customers[$id]['accounts'][] = [
            'number'  => $row['account_number'],
        ];
    }
}

function maskAccount($acct) {
    return substr($acct, 0, 2) . str_repeat('*', max(0, strlen($acct) - 7)) . substr($acct, -4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customers & Accounts</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="page-header">
    <h2>Customers & Their Accounts</h2>
    <a href="dashboards/support_dashboard.php" class="back-btn">← Back to Dashboard</a>
</header>

<div class="table-container">
<table>
  <thead>
    <tr>
      <th>Customer ID</th>
      <th>Full Name</th>
      <th>Email</th>
      <th>Phone</th>
      <th>Address</th>
      <th>Created At</th>
      <th>Accounts</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($customers): ?>
        <?php foreach ($customers as $cust): ?>
        <tr>
          <td><?= htmlspecialchars($cust['customer_id']) ?></td>
          <td><?= htmlspecialchars($cust['full_name']) ?></td>
          <td><?= htmlspecialchars($cust['email']) ?></td>
          <td><?= htmlspecialchars($cust['phone']) ?></td>
          <td><?= htmlspecialchars($cust['address']) ?></td>
          <td><?= htmlspecialchars($cust['created_at']) ?></td>
          <td>
            <?php if ($cust['accounts']): ?>
                <?php foreach ($cust['accounts'] as $acct): ?>
                    <span class="account"><?= htmlspecialchars(maskAccount($acct['number'])) ?></span><br>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="no-account">No accounts</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="7">No customers found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</div>

</body>
</html>
