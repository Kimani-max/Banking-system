<?php
header("Content-Type: application/json");
require_once "../admin/includes/config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method. Use POST."]);
    exit;
}

// Detect input type (JSON or POST form)
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    // fallback to normal POST
    $data = $_POST;
}

$from_account = $data['from_account'] ?? null;
$to_account   = $data['to_account'] ?? null;
$amount       = $data['amount'] ?? null;
$description  = $data['description'] ?? "Fund transfer";

if (!$from_account || !$to_account || !$amount) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

if ($from_account === $to_account) {
    echo json_encode(["status" => "error", "message" => "Source and destination accounts cannot be the same"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Lock source account
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE account_number = ? FOR UPDATE");
    $stmt->execute([$from_account]);
    $source = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$source) {
        throw new Exception("Source account not found");
    }

    if ($source['balance'] < $amount) {
        throw new Exception("Insufficient funds in source account");
    }

    // Lock destination account
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE account_number = ? FOR UPDATE");
    $stmt->execute([$to_account]);
    $dest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dest) {
        throw new Exception("Destination account not found");
    }

    // Update balances
    $pdo->prepare("UPDATE accounts SET balance = ? WHERE account_number = ?")
        ->execute([$source['balance'] - $amount, $from_account]);

    $pdo->prepare("UPDATE accounts SET balance = ? WHERE account_number = ?")
        ->execute([$dest['balance'] + $amount, $to_account]);

    // Insert transactions
    $stmt = $pdo->prepare("INSERT INTO transactions (account_id, type, amount, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$source['account_id'], 'transfer_out', $amount, "Transfer to $to_account - $description"]);
    $debitTxnId = $pdo->lastInsertId();

    $stmt->execute([$dest['account_id'], 'transfer_in', $amount, "Transfer from $from_account - $description"]);
    $creditTxnId = $pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Transfer completed successfully",
        "debit_txn_id" => $debitTxnId,
        "credit_txn_id" => $creditTxnId
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
