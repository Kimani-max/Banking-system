<?php
session_start();
require_once '../admin/includes/config.php';

// Only staff, manager, admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['teller', 'manager', 'admin'])) {
    header("Location: login.php");
    exit;
}

$search = $_GET['search'] ?? '';
$query = "SELECT * FROM customers WHERE 1";
$params = [];

if ($search) {
    $query .= " AND (full_name LIKE ? OR national_id LIKE ? OR phone LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer List</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="customers-container">
    <h2 class="customers-title">Customer List</h2>
    <a href="add_customer.php" class="customers-add-btn">+ Add New Customer</a>

    <form method="get" class="customers-search-form">
        <input type="text" name="search" class="customers-search-input" 
               placeholder="Search by name, ID, or phone" 
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="customers-search-btn">Search</button>
        <a href="view_customers.php" class="customers-reset-link">Reset</a>
    </form>

    <table class="customers-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>National ID</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($customers): ?>
            <?php foreach ($customers as $c): ?>
                <tr>
                    <td><?php echo $c['customer_id']; ?></td>
                    <td><?php echo htmlspecialchars($c['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($c['email']); ?></td>
                    <td><?php echo htmlspecialchars($c['phone']); ?></td>
                    <td><?php echo htmlspecialchars($c['national_id']); ?></td>
                    <td>
                        <a href="edit_customer.php?id=<?php echo $c['customer_id']; ?>" class="customers-edit">Edit</a>
                        <a href="delete_customer.php?id=<?php echo $c['customer_id']; ?>" 
                           class="customers-delete" 
                           onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="no-data">No customers found.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../admin/includes/footer.php'; ?>
</body>
</html>
