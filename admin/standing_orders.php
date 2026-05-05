<?php
require_once '../admin/includes/config.php';
session_start();

// --- Flash message ---
$msg = '';
if (!empty($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    unset($_SESSION['msg']);
}

// --- Filters ---
$where = [];
$params = [];

// Filter by customer
if (!empty($_GET['customer_id'])) {
    $where[] = "so.customer_id = :cust";
    $params[':cust'] = $_GET['customer_id'];
}

// Filter by status
if (!empty($_GET['status'])) {
    $where[] = "so.status = :st";
    $params[':st'] = $_GET['status'];
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// --- Query ---
$sql = "SELECT so.*, 
               fa.account_number AS from_acc, 
               ta.account_number AS to_acc,
               c.full_name
        FROM standing_orders so
        INNER JOIN accounts fa ON so.from_account_id = fa.account_id
        INNER JOIN accounts ta ON so.to_account_id = ta.account_id
        INNER JOIN customers c ON so.customer_id = c.customer_id
        $whereSQL
        ORDER BY so.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all customers for dropdown
$customers = $pdo->query("SELECT customer_id, full_name FROM customers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Standing Orders</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="standing-orders-container">
  <h2 class="standing-orders-title">🏦 Standing Orders</h2>

  <?php if ($msg): ?>
    <p class="flash-message"><?= htmlspecialchars($msg) ?></p>
  <?php endif; ?>

  <!-- Filter Form -->
  <form method="get" class="standing-orders-filter">
      <div class="filter-group">
          <label for="customer_id">Customer:</label>
          <select name="customer_id" id="customer_id">
              <option value="">-- All Customers --</option>
              <?php foreach ($customers as $cust): ?>
                  <option value="<?= $cust['customer_id'] ?>" <?= ($_GET['customer_id']??'')==$cust['customer_id']?'selected':'' ?>>
                      <?= htmlspecialchars($cust['full_name']) ?>
                  </option>
              <?php endforeach; ?>
          </select>
      </div>

      <div class="filter-group">
          <label for="status">Status:</label>
          <select name="status" id="status">
              <option value="">-- Any --</option>
              <option value="active" <?= ($_GET['status']??'')=='active'?'selected':'' ?>>Active</option>
              <option value="inactive" <?= ($_GET['status']??'')=='inactive'?'selected':'' ?>>Inactive</option>
          </select>
      </div>

      <div class="filter-actions">
          <button type="submit" class="btn-filter">Filter</button>
          <a href="standing_orders.php" class="btn_reset">Reset</a>
      </div>
  </form>

  <!-- Standing Orders Table -->
  <?php if ($orders): ?>
  <div class="standing-orders-table-wrapper">
      <table class="standing-orders-table">
          <thead>
              <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>From Account</th>
                  <th>To Account</th>
                  <th>Amount</th>
                  <th>Frequency</th>
                  <th>Next Run</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th>Action</th>
              </tr>
          </thead>
          <tbody>
              <?php foreach ($orders as $o): ?>
              <tr>
                  <td><?= $o['order_id'] ?></td>
                  <td><?= htmlspecialchars($o['full_name']) ?></td>
                  <td><?= htmlspecialchars($o['from_acc']) ?></td>
                  <td><?= htmlspecialchars($o['to_acc']) ?></td>
                  <td><?= number_format($o['amount'],2) ?></td>
                  <td><?= ucfirst($o['frequency']) ?></td>
                  <td><?= $o['next_run_date'] ?></td>
                  <td><?= ucfirst($o['status']) ?></td>
                  <td><?= $o['created_at'] ?></td>
                  <td>
                      <?php if ($o['status']=='active'): ?>
                          <a href="standing_orders_cancel.php?id=<?= $o['order_id'] ?>" class="btn-cancel" onclick="return confirm('Cancel this standing order?')">Cancel</a>
                      <?php else: ?>
                          <span class="inactive-text">-</span>
                      <?php endif; ?>
                  </td>
              </tr>
              <?php endforeach; ?>
          </tbody>
      </table>
  </div>
  <?php else: ?>
    <p class="standing-orders-empty">No standing orders found.</p>
  <?php endif; ?>
</div>
</body>
</html>
