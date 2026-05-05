<?php
session_start();
require_once '../admin/includes/config.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'manager', 'admin'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: view_customers.php");
    exit;
}

$message = "";
$stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    die("Customer not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $address    = trim($_POST['address']);
    $national_id = trim($_POST['national_id']);

    $stmt = $pdo->prepare("UPDATE customers 
                           SET full_name=?, email=?, phone=?, address=?, national_id=? 
                           WHERE customer_id=?");
    $stmt->execute([$full_name, $email, $phone, $address, $national_id, $id]);
    $message = "✅ Customer updated successfully!";
}
?>

<?php include '../admin/includes/header.php'; ?>
<h2>Edit Customer</h2>
<?php if ($message): ?><p><?php echo $message; ?></p><?php endif; ?>

<form method="post">
    <label>Full Name: <input type="text" name="full_name" value="<?php echo htmlspecialchars($customer['full_name']); ?>" required></label><br><br>
    <label>Email: <input type="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required></label><br><br>
    <label>Phone: <input type="text" name="phone" value="<?php echo htmlspecialchars($customer['phone']); ?>" required></label><br><br>
    <label>Address: <textarea name="address" required><?php echo htmlspecialchars($customer['address']); ?></textarea></label><br><br>
    <label>National ID/Passport: <input type="text" name="national_id" value="<?php echo htmlspecialchars($customer['national_id']); ?>" required></label><br><br>
    <button type="submit">Update Customer</button>
</form>
<?php include '../admin/includes/footer.php'; ?>
