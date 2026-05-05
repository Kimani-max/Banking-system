<?php
require_once '../admin/includes/config.php';

if (!isset($_GET['id'])) {
    die("User ID not provided.");
}

$user_id = intval($_GET['id']);
$message = "";

// fetch user details
$sql = "SELECT * FROM users WHERE user_id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $role     = $_POST['role'];
    $status   = $_POST['status'];

    if (!empty($_POST['password_hash'])) {
        $password = password_hash($_POST['password_hash'], PASSWORD_DEFAULT);
        $sql = "UPDATE users 
                   SET username = :username, role = :role, 
                       status = :status, password_hash = :password_hash
                 WHERE user_id = :id";
        $params = [
            ':username' => $username,
            ':role'     => $role,
            ':status'   => $status,
            ':password_hash' => $password_hash,
            ':id'       => $user_id
        ];
    } else {
        $sql = "UPDATE users 
                   SET username = :username, role = :role, 
                       status = :status
                 WHERE user_id = :id";
        $params = [
            ':username' => $username,
            ':role'     => $role,
            ':status'   => $status,
            ':id'       => $user_id
        ];
    }

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        $message = "✅ User updated successfully!";
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = "❌ Failed to update user.";
    }
}
?>
<link rel="stylesheet" href="../assets/css/style.css">
<div class="form-container">
  <h2 class="page-title">Edit User</h2>

  <?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <form method="post" class="styled-form">
    <label>Username:</label>
    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

    <label>Password (leave blank if unchanged):</label>
    <input type="password" name="password">

    <label>Role:</label>
    <select name="role" required>
      <option value="admin"   <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
      <option value="manager" <?= $user['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
      <option value="staff"   <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
      <option value="auditor" <?= $user['role'] === 'auditor' ? 'selected' : '' ?>>Auditor</option>
      <option value="support" <?= $user['role'] === 'support' ? 'selected' : '' ?>>Support</option>
    </select>

    <label>Status:</label>
    <select name="status" required>
      <option value="active"   <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>

    <div class="form-actions">
      <button type="submit" class="btn-primary">Update User</button>
      <a href="users.php" class="btn-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php include '../admin/includes/footer.php'; ?>
