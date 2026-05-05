<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'support') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Support Dashboard</title>
<link rel="stylesheet" href="../../assets/css/style.css">

</head>
<body>

<div class="live">
    <h2>Support Dashboard</h2>
    <div class="user-info">
        Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> (Support)
        <a href="../logout.php" class="logout">Logout</a>
    </div>
</div>

<main class="dashboard-container">
    <div class="dashboard-card">
        <i class="fas fa-ticket-alt"></i>
        <a href="../tickets.php">Handle Support Tickets</a>
    </div>

    <div class="dashboard-card">
        <i class="fas fa-headset"></i>
        <a href="../customer_help.php">Assist Customers</a>
    </div>

    <div class="dashboard-card">
        <i class="fas fa-users"></i>
        <a href="../customers.php">Customers</a>
    </div>

    <div class="dashboard-card">
        <i class="fas fa-university"></i>
        <a href="../accounts.php">Accounts</a>
    </div>
</main>

<?php include '../../admin/includes/footer.php'; ?>
</body>
</html>
