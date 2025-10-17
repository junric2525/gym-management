<?php
// CRITICAL: Ensure this file is correctly located and the path to FPDF is correct.
require('../fpdf/fpdf.php'); // Assuming fpdf.php is accessible from the backend folder

session_start();
include 'db.php'; // Includes the database connection

// CRITICAL SECURITY CHECK
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php");
    exit();
}

// 1. Fetch data from the database (Same query used in the membership_manage.php file)
$sql = "
    SELECT 
        m.members_id, 
        m.user_id, 
        m.gender, 
        m.contact, 
        m.birth_date,
        m.approved_at,
        m.expiration_date,
        u.first_name, 
        u.last_name
    FROM 
        membership m
    JOIN 
        users u ON m.user_id = u.id 
    ORDER BY 
        m.approved_at DESC"; 
$result = $conn->query($sql);

if (!$result) {
    die("Database query failed: " . $conn->error);
}

// Function to calculate age (copied from membership_manage.php)
function calculateAge($birthDate) {
    if (!$birthDate || $birthDate === '0000-00-00') {
        return 'N/A';
    }
    try {
        $dob = new DateTime($birthDate);
        $now = new DateTime();
        $interval = $now->diff($dob);
        return $interval->y;
    } catch (Exception $e) {
        return 'N/A';
    }
}

// 2. Initialize PDF object
$pdf = new FPDF('P', 'mm', 'A4'); // P for Portrait, as there are fewer columns
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// 3. Title
$pdf->Cell(0, 10, 'Approved Membership Report - Charles Gym', 0, 1, 'C');
$pdf->Ln(5);

// 4. Table Header
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(200, 220, 255);
$pdf->SetTextColor(0);

// Column widths in mm (Total width approx 180mm for A4 Portrait)
$col_widths = [10, 20, 40, 15, 15, 30, 30, 20]; 
$headers = ['ID', 'User ID', 'Full Name', 'Age', 'Gender', 'Contact', 'Approved', 'Expires'];

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
    $age = calculateAge($row['birth_date']);
    $full_name = "{$row['first_name']} {$row['last_name']}";

    // Draw row cells
    $pdf->Cell($col_widths[0], 6, $row['members_id'], 1, 0, 'C', $fill);
    $pdf->Cell($col_widths[1], 6, $row['user_id'], 1, 0, 'L', $fill);
    $pdf->Cell($col_widths[2], 6, $full_name, 1, 0, 'L', $fill);
    $pdf->Cell($col_widths[3], 6, $age, 1, 0, 'C', $fill); 
    $pdf->Cell($col_widths[4], 6, $row['gender'], 1, 0, 'C', $fill);
    $pdf->Cell($col_widths[5], 6, $row['contact'], 1, 0, 'L', $fill);
    $pdf->Cell($col_widths[6], 6, date('Y-m-d', strtotime($row['approved_at'])), 1, 0, 'C', $fill); // Format date
    $pdf->Cell($col_widths[7], 6, date('Y-m-d', strtotime($row['expiration_date'])), 1, 0, 'C', $fill); // Format date
    
    $pdf->Ln();
    $fill = !$fill; 
}

// 6. Output the PDF
$conn->close();
$pdf->Output('I', 'charles_gym_members_report_' . date('Ymd') . '.pdf'); 
?>