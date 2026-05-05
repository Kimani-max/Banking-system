<?php
require_once('../../vendor/TCPDF-main/tcpdf.php'); // adjust the path to your TCPDF folder
require_once 'includes/config.php'; // DB connection

$type = $_GET['type'] ?? 'transactions';

// Create new PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, strtoupper($type) . " REPORT", 0, 1, 'C');
$pdf->Ln(5);

$html = '<table border="1" cellpadding="4"><thead><tr>';

// Handle report type
switch ($type) {
    case 'transactions':
        $html .= '<th>ID</th><th>Account</th><th>Type</th><th>Amount</th><th>Date</th></tr></thead><tbody>';
        $stmt = $pdo->query("SELECT txn_id, account_id, txn_type, amount, txn_date FROM transactions ORDER BY txn_date DESC LIMIT 50");
        foreach ($stmt as $row) {
            $html .= "<tr>
                <td>{$row['txn_id']}</td>
                <td>{$row['account_id']}</td>
                <td>{$row['txn_type']}</td>
                <td>" . number_format($row['amount'], 2) . "</td>
                <td>{$row['txn_date']}</td>
            </tr>";
        }
        break;

    case 'accounts':
        $html .= '<th>ID</th><th>Customer</th><th>Type</th><th>Number</th><th>Balance</th></tr></thead><tbody>';
        $stmt = $pdo->query("SELECT account_id, customer_id, account_type, account_number, balance FROM accounts ORDER BY account_id DESC LIMIT 50");
        foreach ($stmt as $row) {
            $html .= "<tr>
                <td>{$row['account_id']}</td>
                <td>{$row['customer_id']}</td>
                <td>{$row['account_type']}</td>
                <td>{$row['account_number']}</td>
                <td>" . number_format($row['balance'], 2) . "</td>
            </tr>";
        }
        break;

    case 'customers':
        $html .= '<th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Address</th></tr></thead><tbody>';
        $stmt = $pdo->query("SELECT customer_id, full_name, email, phone, address FROM customers ORDER BY customer_id DESC LIMIT 50");
        foreach ($stmt as $row) {
            $html .= "<tr>
                <td>{$row['customer_id']}</td>
                <td>{$row['full_name']}</td>
                <td>{$row['email']}</td>
                <td>{$row['phone']}</td>
                <td>{$row['address']}</td>
            </tr>";
        }
        break;

    case 'loans':
        $html .= '<th>ID</th><th>Customer</th><th>Loan</th><th>Amount</th><th>Term</th><th>Status</th></tr></thead><tbody>';
        $stmt = $pdo->query("SELECT loan_id, customer_id, loan_name, amount, term, status FROM loans ORDER BY loan_id DESC LIMIT 50");
        foreach ($stmt as $row) {
            $html .= "<tr>
                <td>{$row['loan_id']}</td>
                <td>{$row['customer_id']}</td>
                <td>{$row['loan_name']}</td>
                <td>" . number_format($row['amount'], 2) . "</td>
                <td>{$row['term']} months</td>
                <td>{$row['status']}</td>
            </tr>";
        }
        break;

    default:
        $html .= '<td colspan="5">Unknown report type</td>';
}

$html .= '</tbody></table>';

// Add table to PDF
$pdf->SetFont('helvetica', '', 10);
$pdf->writeHTML($html, true, false, true, false, '');

// Output
$pdf->Output($type . '_report.pdf', 'I');
