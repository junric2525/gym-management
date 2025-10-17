<?php
// PHP Configuration and Setup
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// =======================================================================
// 1. CONFIGURATION & INCLUDES
// =======================================================================

// !!! IMPORTANT: Adjust this path to where your fpdf.php file is located !!!
require('../fpdf/fpdf.php'); 

$db_error = false;

// Database Connection
if (file_exists('../backend/db.php')) {
    include '../backend/db.php'; 
    if (!isset($conn) || $conn->connect_error) {
        // Log error but proceed to generate a PDF with an error message
        $db_error = true;
    }
} else {
    $db_error = true;
}

// CRITICAL SECURITY CHECK
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Generate a simple error PDF for unauthorized access
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(40,10,'ACCESS DENIED');
    $pdf->Output('I', 'Access_Denied.pdf');
    exit(); 
}

// =======================================================================
// 2. DATA FETCH
// =======================================================================
$evaluations = [];
$report_title = "Gym Evaluation Report";

if (!$db_error) {
    $sql = "
        SELECT 
            ge.evaluation_id, 
            ge.cleanliness_rating, 
            ge.equipment_rating, 
            ge.staff_rating, 
            ge.opinion_text, 
            ge.submission_date,
            m.members_id, 
            CONCAT(u.first_name, ' ', u.last_name) AS member_name 
        FROM 
            gym_evaluations ge
        JOIN 
            membership m ON ge.member_id = m.members_id
        JOIN 
            users u ON m.user_id = u.id   
        ORDER BY 
            ge.submission_date DESC;
    ";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $evaluations[] = $row;
        }
    }
    $conn->close();
}


// =======================================================================
// 3. FPDF GENERATION CLASS
// =======================================================================

// Custom FPDF class to include header and footer
class PDF extends FPDF
{
    private $report_title;
    
    // Pass title to the class
    function __construct($title) {
        parent::__construct('L', 'mm', 'A4'); // Force Landscape and A4 here for consistency
        $this->report_title = $title;
    }

    // Page header
    function Header()
    {
        // Title
        $this->SetFont('Arial','B',16);
        $this->Cell(0,10, $this->report_title, 0, 1, 'C');
        
        // Date
        $this->SetFont('Arial','',10);
        $this->Cell(0,5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');

        // Line break
        $this->Ln(5);
    }

    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Page number
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }

    // Colored table
    function FancyTable($header, $data)
    {
        // Colors, line width and bold font
        $this->SetFillColor(200, 220, 255);
        $this->SetDrawColor(128,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial','B',9);
        
        // --- CORRECTED COLUMN WIDTHS FOR LANDSCAPE A4 ---
        // Total available width (A4 Landscape with 10mm margins) is 297 - 20 = 277mm.
        $total_page_width = 277; 
        
        // Fixed column widths: [ID, Member ID, Member Name, Clean, Equip, Staff, Date]
        // Reduced Date back to 25mm to allow more space for Opinion
        $w = array(15, 20, 30, 15, 15, 15, 25);
        
        $fixed_width = array_sum($w); // 135mm
        // The opinion width uses all remaining space: 277 - 135 = 142mm
        $opinion_width = $total_page_width - $fixed_width;
        
        $all_widths = $w;
        $all_widths[] = $opinion_width; // Add opinion width to the array for header loop
        
        // Header
        $header[] = 'Opinion/Feedback'; // Add header for the new column
        for($i=0;$i<count($header);$i++)
            $this->Cell($all_widths[$i], 7, $header[$i], 1, 0, 'C', true);
        $this->Ln();
        
        // Color and font restoration
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial','',8);
        
        // Data
        $fill = false;
        foreach($data as $row)
        {
            // Calculate height for opinion text
            $opinion_text = $row['opinion_text'] ?: 'N/A';
            $line_height = 4;
            $required_lines = $this->NbLines($opinion_width, $opinion_text);
            $height = $line_height * $required_lines;
            
            // Set minimum and maximum row height
            if ($height < 8) $height = 8;
            if ($height > 20) $height = 20;

            // Check if we need a new page before drawing the row
            if ($this->GetY() + $height > $this->PageBreakTrigger) {
                $this->AddPage($this->CurOrientation);
            }
            
            // Draw fixed columns (ID to Date)
            $this->Cell($w[0], $height, $row['evaluation_id'], 'LR', 0, 'C', $fill);
            $this->Cell($w[1], $height, $row['members_id'], 'R', 0, 'C', $fill);
            $this->Cell($w[2], $height, $row['member_name'], 'R', 0, 'L', $fill);
            $this->Cell($w[3], $height, $row['cleanliness_rating'], 'R', 0, 'C', $fill);
            $this->Cell($w[4], $height, $row['equipment_rating'], 'R', 0, 'C', $fill);
            $this->Cell($w[5], $height, $row['staff_rating'], 'R', 0, 'C', $fill);
            $this->Cell($w[6], $height, date('Y-m-d', strtotime($row['submission_date'])), 'R', 0, 'C', $fill);

            // Opinion Text (MultiCell must be used for wrapping text)
            // Use MultiCell to wrap and set the position back to the start of the next line
            $x_before_multi = $this->GetX();
            $y_before_multi = $this->GetY();
            $this->MultiCell($opinion_width, $line_height, $opinion_text, 'R', 'L', $fill);
            
            // Draw the top and bottom borders for the last column (Opinion/Feedback) manually if MultiCell didn't
            $this->SetXY($x_before_multi, $y_before_multi);
            $this->Cell($opinion_width, $height, '', 'R', 1, 'L', $fill);

            // Move pointer to the start of the next line, using the calculated height
            $this->SetY($y_before_multi + $height);

            // Draw horizontal bottom line for the row
            $this->SetX(10); // Reset X to left margin
            $this->Cell($total_page_width, 0, '', 'T', 1);

            // Toggle fill color for striped rows
            $fill = !$fill;
        }
    }
    
    // Function to calculate the number of lines a MultiCell will require (Remains the same)
    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
        $s = str_replace("\r",'',$txt);
        $nb = strlen($s);
        if($nb>0 and $s[$nb-1]=="\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $l = 0;
        $nl = 1;
        while($i<$nb)
        {
            $c = $s[$i];
            if($c=="\n")
            {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep = $i;
            $l += $cw[$c];
            if($l>$wmax)
            {
                if($sep==-1)
                {
                    if($i==$j)
                        $i++;
                }
                else
                    $i = $sep+1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }
}


// =======================================================================
// 4. PDF OUTPUT
// =======================================================================
$pdf = new PDF($report_title);
$pdf->AliasNbPages(); // Enable {nb} in footer
$pdf->AddPage('L'); // Explicitly added Landscape in FPDF constructor above, but keep for consistency
$pdf->SetMargins(10, 10, 10); // Set margins to 10mm for more space

if ($db_error) {
    $pdf->SetFont('Arial','B',14);
    $pdf->SetTextColor(255, 0, 0); // Red text
    $pdf->Cell(0, 10, 'DATABASE CONNECTION ERROR. Report data could not be fetched.', 0, 1, 'C');
} elseif (empty($evaluations)) {
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0, 10, 'No gym evaluations found to generate a report.', 0, 1, 'C');
} else {
    // Column headers for the FancyTable
    $header = array('Eval ID', 'Member ID', 'Member Name', 'Clean', 'Equip', 'Staff', 'Date');
    
    $pdf->SetFont('Arial','',10);
    
    // Add a summary cell before the table
    $pdf->Cell(0, 8, 'Total Evaluations: ' . count($evaluations), 0, 1, 'L');
    $pdf->Ln(2);

    $pdf->FancyTable($header, $evaluations);
}


// Output the PDF to the browser
$pdf->Output('I', 'Gym_Evaluation_Report_' . date('Ymd') . '.pdf');
exit;
?>
