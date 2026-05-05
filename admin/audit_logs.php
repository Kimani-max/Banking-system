<?php
require_once '../admin/includes/config.php';
session_start();

// ✅ Access control for auditors only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'auditor') {
    header("Location: ../login.php");
    exit;
}

// --- FILTERS ---
$where  = [];
$params = [];

// Filter by username
if (!empty($_GET['username'])) {
    $where[] = "u.username LIKE :uname";
    $params[':uname'] = '%' . $_GET['username'] . '%';
}

// Date range filter
if (!empty($_GET['from']) && !empty($_GET['to'])) {
    $where[] = "DATE(a.created_at) BETWEEN :from AND :to";
    $params[':from'] = $_GET['from'];
    $params[':to']   = $_GET['to'];
} elseif (!empty($_GET['from'])) {
    $where[] = "DATE(a.created_at) >= :from";
    $params[':from'] = $_GET['from'];
} elseif (!empty($_GET['to'])) {
    $where[] = "DATE(a.created_at) <= :to";
    $params[':to'] = $_GET['to'];
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// --- PAGINATION ---
$limit  = 10;
$page   = isset($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total logs
$countSQL = "SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON a.user_id = u.user_id $whereSQL";
$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// --- MAIN QUERY ---
$sql = "
  SELECT 
      a.log_id,
      u.username,
      a.action,
      a.ip_address,
      a.details,
      a.created_at
  FROM audit_logs a
  LEFT JOIN users u ON a.user_id = u.user_id
  $whereSQL
  ORDER BY a.created_at DESC
  LIMIT :offset, :limit
";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val);
}
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Audit Logs</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
  <h2>Audit Logs</h2>
  <p class="welcome-text">Welcome, <?= htmlspecialchars($_SESSION['username']); ?> (Auditor)</p>

  <form method="get" class="filter-form">
    <div class="form-group">
      <label for="username">User:</label>
      <input type="text" name="username" id="username" 
             value="<?= htmlspecialchars($_GET['username'] ?? '') ?>" placeholder="Search username">
    </div>

    <div class="form-group">
      <label for="from">From:</label>
      <input type="date" name="from" id="from" 
             value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label for="to">To:</label>
      <input type="date" name="to" id="to" 
             value="<?= htmlspecialchars($_GET['to'] ?? '') ?>">
    </div>

    <button type="submit" class="btn">Filter</button>
    <a href="audit_logs.php" class="btn-secondary">Reset</a>
    <a href="export_audit_logs.php?<?= http_build_query($_GET) ?>" class="btn-export">Export CSV</a>
  </form>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Action</th>
          <th>Details</th>
          <th>IP Address</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
          <tr>
            <td><?= htmlspecialchars($row['log_id']) ?></td>
            <td><?= htmlspecialchars($row['username'] ?? 'Unknown') ?></td>
            <td><?= htmlspecialchars($row['action']) ?></td>
            <td><?= htmlspecialchars($row['details']) ?></td>
            <td><?= htmlspecialchars($row['ip_address']) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
          </tr>
        <?php endwhile; ?>
        <?php if ($totalRows == 0): ?>
          <tr><td colspan="6">No audit logs found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link">Prev</a>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
           class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link">Next</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
