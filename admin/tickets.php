<?php
session_start();
require_once '../admin/includes/config.php'; // provides $pdo

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

$internalRoles = ['admin','manager','support','staff','auditor'];
$loggedInUserId = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;
$loggedInInternal = $loggedInUserId && in_array($role, $internalRoles);

$messages = [];
$errors = [];

/* ---------- CREATE TICKET ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_ticket') {
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['message'] ?? '');
    $cust_national_id = trim($_POST['customer_national_id'] ?? '');
    $customer_email_input = trim($_POST['customer_email'] ?? '');

    $customer_id = null;
    if ($cust_national_id) {
        $stmt = $pdo->prepare("SELECT customer_id, email FROM customers WHERE national_id = ? LIMIT 1");
        $stmt->execute([$cust_national_id]);
        $cust = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cust) {
            $customer_id = $cust['customer_id'];
            if ($customer_email_input && $customer_email_input !== $cust['email']) {
                $u = $pdo->prepare("UPDATE customers SET email = ? WHERE customer_id = ?");
                $u->execute([$customer_email_input, $customer_id]);
            }
        } else {
            $errors[] = "Customer with national ID " . e($cust_national_id) . " not found.";
        }
    } elseif (!$loggedInInternal) {
        $errors[] = "Please provide your national ID.";
    }

    if ($subject === '' || $body === '') {
        $errors[] = "Subject and message are required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO tickets (customer_id, user_id, subject, message, status, assigned_to, created_at, updated_at)
                               VALUES (?, ?, ?, ?, 'open', NULL, NOW(), NOW())");
        $creator_user_id = $loggedInInternal ? $loggedInUserId : null;
        $stmt->execute([$customer_id, $creator_user_id, $subject, $body]);
        $ticket_id = $pdo->lastInsertId();

        $messages[] = "Ticket created successfully (Ticket #{$ticket_id}).";
    }
}

/* ---------- POST A REPLY ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_reply') {
    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $reply_message = trim($_POST['reply_message'] ?? '');
    $reply_national_id = trim($_POST['reply_national_id'] ?? '');

    if ($ticket_id <= 0 || $reply_message === '') {
        $errors[] = "Reply message cannot be empty.";
    } else {
        $reply_user_id = null;
        $reply_customer_id = null;

        if ($loggedInInternal) {
            $reply_user_id = $loggedInUserId;
        } else {
            if (!$reply_national_id) {
                $errors[] = "Please provide your national ID to reply.";
            } else {
                $stmt = $pdo->prepare("SELECT customer_id FROM tickets WHERE ticket_id = ? LIMIT 1");
                $stmt->execute([$ticket_id]);
                $r = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($r) {
                    $cstmt = $pdo->prepare("SELECT customer_id FROM customers WHERE customer_id = ? AND national_id = ? LIMIT 1");
                    $cstmt->execute([$r['customer_id'], $reply_national_id]);
                    $found = $cstmt->fetch(PDO::FETCH_ASSOC);
                    if ($found) {
                        $reply_customer_id = $found['customer_id'];
                    } else {
                        $errors[] = "National ID does not match the ticket’s customer.";
                    }
                } else {
                    $errors[] = "Ticket not found.";
                }
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, customer_id, message, created_at)
                                   VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$ticket_id, $reply_user_id, $reply_customer_id, $reply_message]);
            $messages[] = "Reply posted.";
        }
    }
}

/* ---------- FETCH tickets ---------- */
$tickets = [];
if ($loggedInInternal) {
    $stmt = $pdo->query("SELECT t.*, c.full_name AS customer_name
                         FROM tickets t
                         LEFT JOIN customers c ON t.customer_id = c.customer_id
                         ORDER BY t.updated_at DESC");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $filter_national = trim($_GET['national_id'] ?? '');
    if ($filter_national) {
        $stmt = $pdo->prepare("SELECT c.customer_id FROM customers c WHERE c.national_id = ? LIMIT 1");
        $stmt->execute([$filter_national]);
        $cust = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cust) {
            $stmt2 = $pdo->prepare("SELECT t.*, c.full_name AS customer_name
                                    FROM tickets t
                                    LEFT JOIN customers c ON t.customer_id = c.customer_id
                                    WHERE t.customer_id = ?
                                    ORDER BY t.updated_at DESC");
            $stmt2->execute([$cust['customer_id']]);
            $tickets = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

/* ---------- Helper: fetch replies ---------- */
function fetchReplies($pdo, $ticket_id) {
    $stmt = $pdo->prepare("SELECT tr.*, u.username AS staff_name, c.full_name AS customer_name
                           FROM ticket_replies tr
                           LEFT JOIN users u ON tr.user_id = u.user_id
                           LEFT JOIN customers c ON tr.customer_id = c.customer_id
                           WHERE tr.ticket_id = ? ORDER BY tr.created_at ASC");
    $stmt->execute([$ticket_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support Tickets</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="ticket-container">
    <h1>Support Tickets</h1>

    <?php foreach ($messages as $m): ?>
        <div class="alert_success"><?php echo e($m); ?></div>
    <?php endforeach; ?>

    <?php foreach ($errors as $err): ?>
        <div class="alert_error"><?php echo e($err); ?></div>
    <?php endforeach; ?>

    <section class="create-ticket">
        <h2>Create a Ticket</h2>
        <form method="post" class="ticket-form">
            <input type="hidden" name="action" value="create_ticket">

            <label>Customer National ID:</label>
            <input type="text" name="customer_national_id" required>

            <label>Email (optional):</label>
            <input type="email" name="customer_email">

            <label>Subject:</label>
            <input type="text" name="subject" required>

            <label>Message:</label>
            <textarea name="message" required rows="5"></textarea>

            <button type="submit" class="create-ticket-btn">Create Ticket</button>
        </form>
    </section>

    <?php if (!$loggedInInternal): ?>
    <section class="view-own">
        <h2>View Your Tickets</h2>
        <form method="get" class="ticket-form">
            <label>National ID:</label>
            <input type="text" name="national_id" required>
            <button type="submit" class="ticket-btn-form">View</button>
        </form>
    </section>
    <?php endif; ?>

    <section class="tickets-list">
        <h2>Tickets</h2>
        <?php if (empty($tickets)): ?>
            <p>No tickets available.</p>
        <?php else: ?>
            <?php foreach ($tickets as $t): ?>
                <div class="ticket-card">
                    <h3>Ticket #<?php echo e($t['ticket_id']); ?> — <?php echo e($t['subject']); ?></h3>
                    <p class="meta">Customer: <?php echo e($t['customer_name'] ?? 'N/A'); ?> | Status: <?php echo e($t['status']); ?></p>
                    <p class="message"><?php echo e($t['message']); ?></p>

                    <div class="replies">
                        <h4>Replies</h4>
                        <?php $replies = fetchReplies($pdo, $t['ticket_id']); ?>
                        <?php if ($replies): ?>
                            <?php foreach ($replies as $r): ?>
                                <div class="reply">
                                    <strong>
                                        <?php echo $r['staff_name'] ? e($r['staff_name']).' (staff)' : e($r['customer_name']).' (customer)'; ?>:
                                    </strong>
                                    <?php echo nl2br(e($r['message'])); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No replies yet.</p>
                        <?php endif; ?>
                    </div>

                    <form method="post" class="reply-form">
                        <input type="hidden" name="action" value="add_reply">
                        <input type="hidden" name="ticket_id" value="<?php echo e($t['ticket_id']); ?>">
                        <?php if (!$loggedInInternal): ?>
                            <label>National ID:</label>
                            <input type="text" name="reply_national_id" required>
                        <?php endif; ?>
                        <label>Message:</label>
                        <textarea name="reply_message" rows="3" required></textarea>
                        <button type="submit" class="ticket-reply-btn">Reply</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>

<?php include '../admin/includes/footer.php'; ?>
</body>
</html>
