<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../admin/includes/config.php';

// Handle add fee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fee'])) {
    $stmt = $pdo->prepare("INSERT INTO fees (fee_name, amount, frequency, description, status) 
                           VALUES (:name, :amount, :freq, :descr, 'active')");
    $stmt->execute([
        ':name'  => $_POST['fee_name'],
        ':amount'=> $_POST['amount'],
        ':freq'  => $_POST['frequency'],
        ':descr' => $_POST['description']
    ]);
    header("Location: manage_fees.php");
    exit;
}

// Handle delete fee
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM fees WHERE fee_id = :id");
    $stmt->execute([':id' => $_GET['delete']]);
    header("Location: manage_fees.php");
    exit;
}

// Fetch fees
$fees = $pdo->query("SELECT * FROM fees ORDER BY fee_id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Fees</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="fees-container">
  <h2 class="fees-title">Manage Fees</h2>

  <!-- Add Fee Form -->
  <form method="post" class="fees">
    <label>Fee Name:</label>
    <input type="text" name="fee_name" required><br>

    <label>Amount:</label>
    <input type="number" step="0.01" name="amount" required><br>

    <label>Frequency:</label>
    <select name="frequency" required>
      <option value="monthly">Monthly</option>
      <option value="yearly">Yearly</option>
      <option value="one_time">One Time</option>
    </select><br>

    <label>Description:</label>
    <input type="text" name="description"><br>

    <button type="submit" name="add_fee" class="btn-add-fee">Add Fee</button>
  </form>

  <!-- Fee List -->
  <h3 class="fees-subtitle">Existing Fees</h3>
  <table class="fees-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Fee Name</th>
        <th>Amount</th>
        <th>Frequency</th>
        <th>Description</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($fees): ?>
        <?php foreach ($fees as $f): ?>
          <tr>
            <td><?= htmlspecialchars($f['fee_id']) ?></td>
            <td><?= htmlspecialchars($f['fee_name']) ?></td>
            <td><?= number_format($f['amount'], 2) ?></td>
            <td><?= htmlspecialchars($f['frequency']) ?></td>
            <td><?= htmlspecialchars($f['description']) ?></td>
            <td><?= htmlspecialchars($f['status']) ?></td>
            <td>
              <a href="manage_fees.php?delete=<?= $f['fee_id'] ?>" 
                 class="btn-delete"
                 onclick="return confirm('Delete this fee?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="7">No fees defined.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>
