<?php
session_start();
require_once '../admin/includes/config.php'; // $pdo connection

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$internalRoles = ['admin','manager','support','staff','auditor'];
$loggedInUserId = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;
$loggedInInternal = $loggedInUserId && in_array($role, $internalRoles);

$messages = [];
$errors = [];

// ---------- MARK AS READ/UNREAD ----------
if (isset($_GET['toggle_id'])) {
    $notif_id = intval($_GET['toggle_id']);
    $stmt = $pdo->prepare("SELECT is_read FROM notifications WHERE notification_id = ?");
    $stmt->execute([$notif_id]);
    $notif = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($notif) {
        $new_status = $notif['is_read'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = ? WHERE notification_id = ?");
        $stmt->execute([$new_status, $notif_id]);
        $messages[] = "Notification marked as " . ($new_status ? "read" : "unread") . ".";
    }
}

// ---------- SEND NOTIFICATION (staff/admin) ----------
if ($loggedInInternal && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$customer_id || !$title || !$message) {
        $errors[] = "All fields are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO notifications (customer_id, title, message, created_at)
                               VALUES (?, ?, ?, NOW())");
        $stmt->execute([$customer_id, $title, $message]);
        $messages[] = "Notification sent to customer.";
    }
}

// ---------- FETCH NOTIFICATIONS ----------
$notifications = [];
if ($loggedInInternal) {
    // staff/admin see all notifications
    $stmt = $pdo->query("SELECT n.*, c.full_name, c.national_id
                         FROM notifications n
                         LEFT JOIN customers c ON n.customer_id = c.customer_id
                         ORDER BY n.created_at DESC");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // fetch all customers for dropdown
    $customers = $pdo->query("SELECT customer_id, full_name, national_id FROM customers ORDER BY full_name ASC")
                     ->fetchAll(PDO::FETCH_ASSOC);
} else {
    // customer view requires national ID
    $cust_national_id = trim($_GET['national_id'] ?? '');
    if ($cust_national_id) {
        $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE national_id = ? LIMIT 1");
        $stmt->execute([$cust_national_id]);
        $cust = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cust) {
            $stmt2 = $pdo->prepare("SELECT * FROM notifications WHERE customer_id = ? ORDER BY created_at DESC");
            $stmt2->execute([$cust['customer_id']]);
            $notifications = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}
?>
<?php include '../admin/includes/header.php'; ?>

<h2>Notifications</h2>

<?php foreach ($messages as $m): ?>
    <p><?php echo e($m); ?></p>
<?php endforeach; ?>
<?php foreach ($errors as $err): ?>
    <p><?php echo e($err); ?></p>
<?php endforeach; ?>

<?php if ($loggedInInternal): ?>
    <h3>Send Notification</h3>
    <form method="post">
        <input type="hidden" name="action" value="send">

        <label>Customer:
            <select name="customer_id" required>
                <option value="">-- Select Customer --</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?php echo e($c['customer_id']); ?>">
                        <?php echo e($c['full_name'] . " (" . $c['national_id'] . ")"); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label><br>

        <label>Title:
            <input type="text" name="title" required>
        </label><br>

        <label>Message:
            <textarea name="message" rows="4" required></textarea>
        </label><br>

        <button type="submit">Send</button>
    </form>
<?php else: ?>
    <form method="get">
        <label>Enter National ID to view your notifications:
            <input type="text" name="national_id" required>
        </label>
        <button type="submit">View</button>
    </form>
<?php endif; ?>

<h3>Notifications List</h3>
<?php if (empty($notifications)): ?>
    <p>No notifications found.</p>
<?php else: ?>
    <?php foreach ($notifications as $n): ?>
        <div>
            <strong><?php echo e($n['title']); ?></strong> (<?php echo e($n['created_at']); ?>)<br>
            <?php if ($loggedInInternal): ?>
                <small>Customer: <?php echo e($n['full_name'] ?? 'N/A'); ?> (<?php echo e($n['national_id'] ?? ''); ?>)</small><br>
            <?php endif; ?>
            <p><?php echo nl2br(e($n['message'])); ?></p>
            <small>Status: <?php echo $n['is_read'] ? "Read" : "Unread"; ?> | 
                <a href="?toggle_id=<?php echo e($n['notification_id']); ?>">Mark as <?php echo $n['is_read'] ? "Unread" : "Read"; ?></a>
            </small>
        </div>
        <hr>
    <?php endforeach; ?>
<?php endif; ?>

<?php include '../admin/includes/footer.php'; ?>
