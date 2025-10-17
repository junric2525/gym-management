<?php
// CRITICAL: Ensure the path to FPDF is correct relative to the backend folder.
require('../fpdf/fpdf.php'); 

session_start();
include 'db.php'; // Includes the database connection

// CRITICAL SECURITY CHECK
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php");
    exit();
}

// 1. Fetch data from the database (Similar query to the view page)
$query = "
    SELECT
        s.subscription_id,
        s.members_id,
        s.subscription_type,
        s.gcash_reference_number,
        s.start_date,
        s.end_date,
        s.status,
        s.created_at,
        CONCAT(u.first_name, ' ', u.last_name) AS full_name
    FROM
        subscription s
    JOIN
        membership m ON s.members_id = m.members_id
    JOIN
        users u ON m.user_id = u.id
    ORDER BY s.created_at DESC
";
$result = $conn->query($query);

if (!$result) {
    die("Database query failed: " . $conn->error);
}

// 2. Initialize PDF object
// Using 'L' for Landscape to accommodate more columns
$pdf = new FPDF('L', 'mm', 'A4'); 
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// 3. Title
$pdf->Cell(0, 10, 'All Subscriptions Report - Charles Gym', 0, 1, 'C');
$pdf->Ln(5);

// 4. Table Header
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(200, 220, 255);
$pdf->SetTextColor(0);

// Column widths in mm (Total width approx 277mm for A4 Landscape)
$col_widths = [15, 20, 45, 25, 40, 30, 30, 15, 30]; 
$headers = ['Sub ID', 'Member ID', 'Member Name', 'Plan', 'GCash Ref', 'Start Date', 'End Date', 'Status', 'Created At'];

// Draw the header cells
for ($i = 0; $i < count($headers); $i++) {
    $pdf->Cell($col_widths[$i], 7, $headers[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// 5. Table Body
$pdf->SetFont('Arial', '', 8);
$pdf->SetFillColor(240, 240, 240);
$fill = false; 

while ($row = $result->fetch_assoc()) {
    $end_date = $row['end_date'] ?: 'N/A';
    $status = ucfirst($row['status']);

    // Draw row cells
    $pdf->Cell($col_widths[0], 6, $row['subscription_id'], 1, 0, 'C', $fill);
    $pdf->Cell($col_widths[1], 6, $row['members_id'], 1, 0, 'C', $fill);
    $pdf->Cell($col_widths[2], 6, $row['full_name'], 1, 0, 'L', $fill);
    $pdf->Cell($col_widths[3], 6, ucfirst($row['subscription_type']), 1, 0, 'C', $fill); 
    $pdf->Cell($col_widths[4], 6, $row['gcash_reference_number'], 1, 0, 'L', $fill);
    $pdf->Cell($col_widths[5], 6, $row['start_date'], 1, 0, 'C', $fill);
    $pdf->Cell($col_widths[6], 6, $end_date, 1, 0, 'C', $fill);
    $pdf->Cell($col_widths[7], 6, $status, 1, 0, 'C', $fill);
    $pdf->Cell($col_widths[8], 6, $row['created_at'], 1, 0, 'C', $fill);
    
    $pdf->Ln();
    $fill = !$fill; 
}

// 6. Output the PDF
$conn->close();
$pdf->Output('I', 'charles_gym_subscriptions_report_' . date('Ymd') . '.pdf'); 
?>