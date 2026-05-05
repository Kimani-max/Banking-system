<?php
require_once '../admin/includes/config.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $role     = $_POST['role'];
    $status   = $_POST['status'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // secure hash

    $sql = "INSERT INTO users (username, password, role, status, created_at) 
            VALUES (:username, :password, :role, :status, NOW())";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([
        ':username' => $username,
        ':password' => $password,
        ':role'     => $role,
        ':status'   => $status
    ])) {
        $message = "✅ User added successfully!";
    } else {
        $message = "❌ Failed to add user.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add User</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <h2>Add New User</h2>

  <?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <form method="post">
    <label>Username:</label>
    <input type="text" name="username" required><br>

    <label>Password:</label>
    <input type="password" name="password" required><br>

    <label>Role:</label>
    <select name="role" required>
      <option value="admin">Admin</option>
      <option value="manager">Manager</option>
      <option value="staff">Staff</option>
      <option value="auditor">Auditor</option>
      <option value="support">Support</option>
    </select><br>

    <label>Status:</label>
    <select name="status" required>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select><br>

    <button type="submit">Add User</button>
    <a href="users.php">Cancel</a>
  </form>
</body>
</html>
