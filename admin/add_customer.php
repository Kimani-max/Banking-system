<?php
session_start();
require_once '../admin/includes/config.php';

// Only allow staff, manager, admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['teller', 'manager', 'admin'])) {
    header("Location: login.php");
    exit;
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name   = trim($_POST['full_name']);
    $email       = trim($_POST['email']);
    $phone       = trim($_POST['phone']);
    $address     = trim($_POST['address']);
    $national_id = trim($_POST['national_id']);

    try {
        $stmt = $pdo->prepare("INSERT INTO customers (full_name, email, phone, address, national_id) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $phone, $address, $national_id]);
        $message = "✅ Customer added successfully!";
    } catch (PDOException $e) {
        $message = "❌ Error: " . $e->getMessage();
    }
}
?>

<link rel="stylesheet" href="../assets/css/style.css">

<div class="form-container">
  <h2 class="page-title">Add Customer</h2>

  <?php if ($message): ?>
    <p class="message"><?php echo htmlspecialchars($message); ?></p>
  <?php endif; ?>

  <form method="post" class="styled-form">
    <label>Full Name:</label>
    <input type="text" name="full_name" required>

    <label>Email:</label>
    <input type="email" name="email" required>

    <label>Phone:</label>
    <input type="text" name="phone" required>

    <label>Address:</label>
    <textarea name="address" required></textarea>

    <label>National ID/Passport:</label>
    <input type="text" name="national_id" required>

    <div class="form-actions">
      <button type="submit" class="btn-primary">Add Customer</button>
      <a href="../admin/dashboards/admin_dashboard.php" class="btn-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php include '../admin/includes/footer.php'; ?>
