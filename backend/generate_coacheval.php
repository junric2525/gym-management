<?php
// PHP Configuration and Setup
ini_set('display_errors', 0); // Suppress errors from being outputted directly to the PDF stream

// Ensure the session is started only once and before any output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =======================================================================
// 1. CRITICAL SECURITY CHECK & Authorization
// =======================================================================
// Only admins can access this script to generate the report
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../Guest/index.php");
    exit(); 
}

// =======================================================================
// 2. Database Connection & FDPF Setup
// =======================================================================

// --- FIX 1: Corrected path for db.php ---
// Since generate_coacheval.php and db.php are in the same folder (backend):
if (!file_exists('db.php')) {
    die("<h1>Configuration Error</h1><p>The database configuration file (db.php) could not be found in the current directory.</p>");
}
include 'db.php'; // Corrected inclusion path

// --- FIX 2: Corrected path for FDPF ---
// The original path was '../fpdf/fpdf.php'. Assuming the 'fpdf' folder is 
// sibling to 'backend' (i.e., both are under a common root):
if (!file_exists('../fpdf/fpdf.php')) {
    die("<h1>Library Error</h1><p>The FPDF library file (fpdf.php) could not be found at the specified path (../fpdf/fpdf.php).</p>");
}
require('../fpdf/fpdf.php'); 

// --- MOCK FPDF BLOCK (For testing environments without the library) ---
// Keep this only if you need to run the script in an environment without FPDF.
if (!class_exists('FPDF')) {
    class FPDF {
        protected $pdf;
        public function __construct($orientation='P', $unit='mm', $size='A4') { echo ""; }
        public function AddPage() { echo ""; }
        public function SetFont($family, $style='', $size=12) { echo ""; }
        public function SetFillColor($r, $g, $b) { echo ""; }
        public function SetTextColor($r, $g, $b) { echo ""; }
        public function SetDrawColor($r, $g, $b) { echo ""; }
        public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') { /* echo ""; */ }
        public function Output($dest='I', $name='doc.pdf', $isUTF8=false) { die("PDF Generation successful: " . $name); }
        public function SetTitle($title) { echo ""; }
        public function SetAuthor($author) { echo ""; }
    }
}
// ------------------------------------------------------------------------


// Check for database connection object ($conn) created in db.php
if (!isset($conn) || $conn->connect_error) {
    die("<h1>Database Connection Error</h1><p>The PDF report could not be generated due to a connection issue. Details: " . ($conn->connect_error ?? "Connection object missing") . "</p>");
}


// =======================================================================
// 3. Data Fetching - Using Prepared Statements (Security Best Practice)
// =======================================================================
$coach_evaluations = [];
$sql = "
    SELECT 
        ce.evaluation_id, 
        c.name AS coach_name, 
        CONCAT_WS(' ', u.first_name, u.last_name) AS member_name, 
        ce.behavior_rating, 
        ce.teaching_rating, 
        ce.communication_rating, 
        ce.opinion, 
        ce.evaluation_date
    FROM 
        coach_evaluations ce
    JOIN 
        membership m ON ce.member_id = m.members_id
    JOIN
        users u ON m.user_id = u.id 
    LEFT JOIN
        coaches c ON ce.coach_id = c.coach_id
    ORDER BY 
        ce.evaluation_date DESC;
";

// Use a prepared statement for robust and secure database interaction
if ($stmt = $conn->prepare($sql)) {
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $coach_evaluations[] = $row;
            }
            $result->free();
        }
    } else {
        die("<h1>Database Query Error</h1><p>The report query failed to execute.</p>");
    }
    $stmt->close();
} else {
    die("<h1>Database Prepare Error</h1><p>Could not prepare the database statement.</p>");
}

$conn->close(); 

// =======================================================================
// 4. FDPF Report Generation
// =======================================================================

$pdf = new FPDF('L','mm','A4'); // Landscape orientation
$pdf->SetTitle('Coach Evaluations Report - Charles Gym');
$pdf->SetAuthor('Charles Gym Admin System');
$pdf->AddPage();

// Report Title
$pdf->SetFont('Arial','B',16);
$pdf->Cell(280, 10, 'Coach Evaluations Report', 0, 1, 'C');
$pdf->SetFont('Arial','',10);
$pdf->Cell(280, 5, 'Date Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
$pdf->Ln(5);

// Define Table Headers and Widths
$header = array('ID', 'Coach Name', 'Evaluated By', 'Behavior', 'Teaching', 'Comm.', 'Opinion Summary', 'Date');
$w = array(15, 40, 45, 20, 20, 20, 90, 30);
$h = 8;

// Print table header
$pdf->SetFillColor(200, 220, 255);
$pdf->SetTextColor(0);
$pdf->SetDrawColor(128, 128, 128);
$pdf->SetLineWidth(.3);
$pdf->SetFont('Arial','B',8);

for($i=0; $i<count($header); $i++) {
    $pdf->Cell($w[$i], $h, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Table Data
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0);
$pdf->SetFont('Arial','',8);
$fill = false;

if (empty($coach_evaluations)) {
    $pdf->Cell(array_sum($w), $h, 'No coach evaluations found.', 1, 1, 'C', $fill);
} else {
    foreach ($coach_evaluations as $row) {
        $pdf->Cell($w[0], $h, $row['evaluation_id'], 'LR', 0, 'C', $fill);
        
        // Use utf8_decode and htmlspecialchars_decode for better character handling in FPDF
        $pdf->Cell($w[1], $h, utf8_decode(htmlspecialchars_decode($row['coach_name'] ?? 'N/A')), 'LR', 0, 'L', $fill);
        $pdf->Cell($w[2], $h, utf8_decode(htmlspecialchars_decode($row['member_name'])), 'LR', 0, 'L', $fill);
        
        $pdf->Cell($w[3], $h, $row['behavior_rating'], 'LR', 0, 'C', $fill);
        $pdf->Cell($w[4], $h, $row['teaching_rating'], 'LR', 0, 'C', $fill);
        $pdf->Cell($w[5], $h, $row['communication_rating'], 'LR', 0, 'C', $fill);
        
        // Truncate long opinion text
        $opinion_text = htmlspecialchars_decode($row['opinion']);
        $opinion_summary = substr($opinion_text, 0, 50);
        if (strlen($opinion_text) > 50) {
            $opinion_summary .= '...';
        }
        $pdf->Cell($w[6], $h, utf8_decode($opinion_summary), 'LR', 0, 'L', $fill);
        
        $pdf->Cell($w[7], $h, date('Y-m-d', strtotime($row['evaluation_date'])), 'LR', 0, 'C', $fill);
        $pdf->Ln();
        $fill = !$fill; // Alternate row colors
    }
}

// Closing line
$pdf->Cell(array_sum($w), 0, '', 'T', 1, 'C');


// Output the PDF
$pdf_filename = 'Coach_Evaluations_Report_' . date('Ymd_His') . '.pdf';
$pdf->Output('I', $pdf_filename);

exit;
?>