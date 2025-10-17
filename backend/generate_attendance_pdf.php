<?php
session_start();
include 'db.php';
// IMPORTANT: You must download and include the FPDF library here.
// Download FPDF from http://www.fpdf.org/ and place fpdf.php in your backend folder.
require('../fpdf/fpdf.php'); 

// Protect admin route
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Redirect non-admins
    header("Location: ../Guest/index.php");
    exit();
}

// 1. Fetch data from the database
$sql = "
    SELECT a.log_id, m.user_id, m.gender, m.address, a.time_in, a.time_out
    FROM attendance_logs a
    JOIN membership m ON a.members_id = m.members_id
    ORDER BY a.time_in DESC
";

$result = $conn->query($sql);

if (!$result) {
    // Handle database query error
    die("Database query failed: " . $conn->error);
}

// 2. Initialize PDF object
// Orientation: L (Landscape), Unit: mm (millimeters), Format: A4
$pdf = new FPDF('L', 'mm', 'A4'); // Use 'L' for Landscape to fit more columns
$pdf->AddPage();

// 3. Title
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Attendance Logs Report - Charles Gym', 0, 1, 'C');
$pdf->Ln(5); // Line break

// 4. Table Header Configuration
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(200, 220, 255); // Light blue background for header
$pdf->SetTextColor(0);

// Column widths in mm (Total width is 277mm for A4 Landscape)
$col_widths = [
    'log_id' => 15, 
    'user_id' => 30, 
    'gender' => 20, 
    'address' => 70, 
    'time_in' => 60, 
    'time_out' => 60, 
    'status' => 22
]; 
$headers = ['Log ID', 'Member ID', 'Gender', 'Address', 'Time In', 'Time Out', 'Status'];

// Calculate total width to ensure it matches the column definition array
$width_values = array_values($col_widths); 

// Draw the header cells
for ($i = 0; $i < count($headers); $i++) {
    $pdf->Cell($width_values[$i], 7, $headers[$i], 1, 0, 'C', true);
}
$pdf->Ln(); // Move to next line

// 5. Table Body
$pdf->SetFont('Arial', '', 9);
$pdf->SetFillColor(240, 240, 240); // Light gray for rows
$fill = false; // Alternating row color flag

while ($row = $result->fetch_assoc()) {
    $status = !empty($row['time_out']) ? 'OUT' : 'IN';
    $time_out = $row['time_out'] ?: '-';

    // Limit address length for display simplicity
    $display_address = substr($row['address'], 0, 40) . (strlen($row['address']) > 40 ? '...' : '');

    // Draw row cells
    $pdf->Cell($col_widths['log_id'], 6, $row['log_id'], 1, 0, 'C', $fill);
    $pdf->Cell($col_widths['user_id'], 6, $row['user_id'], 1, 0, 'L', $fill);
    $pdf->Cell($col_widths['gender'], 6, $row['gender'], 1, 0, 'C', $fill);
    $pdf->Cell($col_widths['address'], 6, $display_address, 1, 0, 'L', $fill); 
    $pdf->Cell($col_widths['time_in'], 6, $row['time_in'], 1, 0, 'C', $fill);
    $pdf->Cell($col_widths['time_out'], 6, $time_out, 1, 0, 'C', $fill);
    $pdf->Cell($col_widths['status'], 6, $status, 1, 0, 'C', $fill);
    
    $pdf->Ln(); // New row
    $fill = !$fill; // Toggle fill color
}

// 6. Output the PDF
$conn->close();
$pdf->Output('I', 'charles_gym_attendance_report_' . date('Ymd') . '.pdf'); 
// 'I' means send file inline to the browser (open in a new tab)
?>