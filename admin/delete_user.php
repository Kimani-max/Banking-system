<?php
require_once '../admin/includes/config.php';

if (!isset($_GET['id'])) {
    die("User ID not provided.");
}

$user_id = intval($_GET['id']);
$message = "";

// fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// delete if confirmed
if (isset($_POST['confirm'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :id");
    if ($stmt->execute([':id' => $user_id])) {
        $message = "✅ User deleted successfully!";
    } else {
        $message = "❌ Failed to delete user.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Delete User</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <h2>Delete User</h2>

  <?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
    <p><a href="users.php">Back to Users</a></p>
  <?php else: ?>
    <p>Are you sure you want to delete user 
       <strong><?= htmlspecialchars($user['username']) ?></strong> (ID: <?= $user['user_id'] ?>)?</p>

    <form method="post">
      <button type="submit" name="confirm" value="1">Yes, Delete</button>
      <a href="users.php">Cancel</a>
    </form>
  <?php endif; ?>
</body>
</html>
