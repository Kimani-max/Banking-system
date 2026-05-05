<?php
header("Content-Type: application/json");
require __DIR__ . '/../admin/includes/config.php';

// Token sent as header: Authorization: yourtoken
$headers = getallheaders();
$token   = $headers['Authorization'] ?? '';

if (!$token) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Missing token"]);
    exit;
}

// validate token
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

// fetch balances (simplest case, one account per user)
$acct = $pdo->prepare("SELECT account_number, balance FROM accounts WHERE customer_id = ?");
$acct->execute([$user['user_id']]);
$accounts = $acct->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success"  => true,
    "customer" => $user['username'],
    "accounts" => $accounts
]);
