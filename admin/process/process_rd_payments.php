<?php
require_once '../../admin/includes/config.php';

$today = date('Y-m-d');

// 1. Get all active recurring deposits
$rds = $pdo->query("SELECT * FROM recurring_deposits WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rds as $rd) {
    $rdId      = $rd['rd_id'];
    $custId    = $rd['customer_id'];
    $acctId    = $rd['account_id'];
    $amount    = $rd['deposit_amount'];
    $term      = $rd['term_months'];
    $rate      = $rd['interest_rate'];
    $start     = new DateTime($rd['start_date']);
    $maturity  = new DateTime($rd['maturity_date']);

    // 2. Count payments made
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM recurring_deposit_payments WHERE rd_id = :rd");
    $stmt->execute([':rd' => $rdId]);
    $madePayments = (int)$stmt->fetchColumn();

    // 3. If not matured, process due installments
    if ($today < $maturity->format('Y-m-d')) {
        $monthsPassed = $start->diff(new DateTime($today))->m + ($start->diff(new DateTime($today))->y * 12);
        $duePayments = min($monthsPassed, $term);

        while ($madePayments < $duePayments) {
            // Check account balance
            $balStmt = $pdo->prepare("SELECT balance FROM accounts WHERE account_id = :a");
            $balStmt->execute([':a' => $acctId]);
            $bal = (float)$balStmt->fetchColumn();

            if ($bal >= $amount) {
                // Deduct from balance
                $pdo->prepare("UPDATE accounts SET balance = balance - :amt WHERE account_id = :a")
                    ->execute([':amt' => $amount, ':a' => $acctId]);

                // Record payment
                $pdo->prepare("INSERT INTO recurring_deposit_payments (rd_id, customer_id, payment_date, amount) 
                               VALUES (:rd, :cust, NOW(), :amt)")
                    ->execute([':rd' => $rdId, ':cust' => $custId, ':amt' => $amount]);

                $madePayments++;
                echo "RD {$rdId}: Payment of {$amount} deducted.<br>";
            } else {
                echo "RD {$rdId}: Insufficient balance.<br>";
                break;
            }
        }
    }

    // 4. If matured and all payments are made, credit maturity amount
    if ($today >= $maturity->format('Y-m-d') && $madePayments >= $term) {
        $totalDeposit = $amount * $term;
        $interest = ($totalDeposit * $rate * $term) / (12 * 100); // simple interest
        $maturityAmt = $totalDeposit + $interest;

        // Credit to account
        $pdo->prepare("UPDATE accounts SET balance = balance + :amt WHERE account_id = :a")
            ->execute([':amt' => $maturityAmt, ':a' => $acctId]);

        // Mark RD closed
        $pdo->prepare("UPDATE recurring_deposits SET status = 'closed' WHERE rd_id = :rd")
            ->execute([':rd' => $rdId]);

        echo "RD {$rdId}: Matured. Credited {$maturityAmt} to account {$acctId}.<br>";
    }
}
?>
