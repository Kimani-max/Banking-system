<?php
header("Content-Type: application/json");
require __DIR__ . '/../admin/includes/config.php';

// 1. Grab token from headers
$headers = getallheaders();
$token   = $headers['Authorization'] ?? '';

if (!$token) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Missing token"]);
    exit;
}

// 2. Validate token and fetch user info
$stmt = $pdo->prepare("
    SELECT u.user_id, u.username
    FROM api_tokens t
    JOIN users u ON u.user_id = t.user_id
    WHERE t.token = ? AND t.expires_at > NOW()
");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Invalid or expired token"]);
    exit;
}

// 3. For now we’ll treat the user_id as customer_id
$customer_id = $user['user_id'];

// Debug output — you can comment out once you confirm it
error_log("transactions.php: customer_id resolved as " . var_export($customer_id, true));
// or just dump to screen temporarily
var_dump($customer_id);

// 4. Join accounts & transactions using account_id
$sql = "
    SELECT 
        a.account_id,
        a.customer_id,
        t.txn_id,
        t.txn_type,
        t.amount,
        t.description,
        t.txn_date
    FROM accounts a
    LEFT JOIN transactions t ON t.customer_id = a.customer_id
    WHERE a.customer_id = 1
    ORDER BY t.txn_date DESC
    LIMIT 50
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Return JSON
echo json_encode([
    "success"      => true,
    "customer"     => $user['username'],
    "transactions" => $rows
]);
