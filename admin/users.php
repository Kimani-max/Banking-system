<?php
require_once '../admin/includes/config.php';

// Fetch all users
$sql = "SELECT user_id, username, role, status, created_at FROM users ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="../assets/css/style.css">

<div class="page-container">
  <h2 class="page-title">System Users</h2>

  <a href="add_user.php" class="btn-primary-user">+ Add New User</a>

  <div class="table-container">
    <table class="styled-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Role</th>
          <th>Status</th>
          <th>Created At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($users): ?>
          <?php foreach ($users as $user): ?>
            <tr>
              <td><?= htmlspecialchars($user['user_id']) ?></td>
              <td><?= htmlspecialchars($user['username']) ?></td>
              <td><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
              <td><?= htmlspecialchars($user['status']) ?></td>
              <td><?= htmlspecialchars($user['created_at']) ?></td>
              <td>
                <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="btn-secondary">Edit</a>
                <a href="delete_user.php?id=<?= $user['user_id'] ?>" class="btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6">No users found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../admin/includes/footer.php'; ?>
