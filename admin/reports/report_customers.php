<?php
require_once '../../admin/includes/config.php';

// --- Filters ---
$where  = [];
$params = [];

// filter by search (name, email, phone)
if (!empty($_GET['search'])) {
    $where[] = "(c.full_name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search)";
    $params[':search'] = "%" . $_GET['search'] . "%";
}

// filter by date range (customer registration)
if (!empty($_GET['from']) && !empty($_GET['to'])) {
    $where[] = "DATE(c.created_at) BETWEEN :from AND :to";
    $params[':from'] = $_GET['from'];
    $params[':to']   = $_GET['to'];
} elseif (!empty($_GET['from'])) {
    $where[] = "DATE(c.created_at) >= :from";
    $params[':from'] = $_GET['from'];
} elseif (!empty($_GET['to'])) {
    $where[] = "DATE(c.created_at) <= :to";
    $params[':to'] = $_GET['to'];
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// --- Query ---
$sql = "
  SELECT 
      c.customer_id, c.full_name, c.email, c.phone, c.address, c.created_at
  FROM customers c
  $whereSQL
  ORDER BY c.created_at ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customers Report</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="report-container"> 
        <h2>📊 Customers Fees Report</h2>

        <form method="get" class="filter-form">
            <label for="search">Search:</label>
            <input type="text" name="search" placeholder="Name, Email, or Phone" 
                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">

            <label for="from">From:</label>
            <input type="date" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">

            <label for="to">To:</label>
            <input type="date" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>">

            <button type="submit">Generate</button>
            <a href="report_customers.php" class="btn reset">Reset</a>
        </form>

        <form method="get" action="export_report.php" class="export-form">
            <input type="hidden" name="report" value="customers">
            <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <input type="hidden" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">
            <input type="hidden" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>">

            <button type="submit" name="format" value="csv" class="btn export">Export CSV</button>
            <button type="submit" name="format" value="pdf" class="btn export">Export PDF</button>
        </form>

        <table class="styled-reports">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($customers): ?>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['customer_id']) ?></td>
                        <td><?= htmlspecialchars($c['full_name']) ?></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td><?= htmlspecialchars($c['phone']) ?></td>
                        <td><?= htmlspecialchars($c['address']) ?></td>
                        <td><?= htmlspecialchars($c['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">No customers found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
```
