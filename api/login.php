<?php
header("Content-Type: application/json");
require __DIR__ . '/../admin/includes/config.php';  // your PDO connection

// Get POST body as JSON
$data = json_decode(file_get_contents("php://input"), true);
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

// Find active customer
$stmt = $pdo->prepare("SELECT * FROM users WHERE username=? AND role='customer' AND status='active'");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $password === $user['password_hash']) {   // plaintext comparison
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 day'));

    $insert = $pdo->prepare("INSERT INTO api_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $insert->execute([$user['user_id'], $token, $expires]);

    echo json_encode([
        "success" => true,
        "token"   => $token,
        "expires" => $expires
    ]);
} else {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Invalid login"]);
}
