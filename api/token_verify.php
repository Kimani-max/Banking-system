<?php
require __DIR__ . '/../admin/includes/config.php';

function verify_token(PDO $pdo, $token) {
    $stmt = $pdo->prepare("SELECT * FROM api_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC); // returns row if valid
}
