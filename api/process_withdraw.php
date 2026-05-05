<?php
session_start();
require_once '../admin/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountId = $_POST['account_id'] ?? null;
    $amount    = $_POST['amount'] ?? null;

    if (!$accountId || !$amount || $amount <= 0) {
        header("Location: withdraw.php?error=Invalid+request");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Lock the account row & get balance + customer_id
        $stmt = $pdo->prepare(
            "SELECT balance, customer_id FROM accounts WHERE account_id = :id FOR UPDATE"
        );
        $stmt->execute([':id' => $accountId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            throw new Exception("Account not found.");
        }

        $currentBalance = (float)$account['balance'];
        if ($currentBalance < $amount) {
            throw new Exception("Insufficient funds.");
        }

        $newBalance = $currentBalance - $amount;

        // Update balance
        $stmt = $pdo->prepare(
            "UPDATE accounts SET balance = :balance WHERE account_id = :id"
        );
        $stmt->execute([':balance' => $newBalance, ':id' => $accountId]);

        // Insert transaction
        $stmt = $pdo->prepare(
            "INSERT INTO transactions (account_id, customer_id, txn_type, amount, description, txn_date)
             VALUES (:account, :customer, 'withdraw', :amount, 'Cash withdrawal', NOW())"
        );
        $stmt->execute([
            ':account'  => $accountId,
            ':customer' => $account['customer_id'],
            ':amount'   => $amount
        ]);

        $pdo->commit();

        header("Location: withdraw.php?success=Withdrawal+completed.+New+balance:+".urlencode(number_format($newBalance,2)));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: withdraw.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: withdraw.php?error=Invalid+request+method");
    exit;
}
