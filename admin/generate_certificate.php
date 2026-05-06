<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php"); exit(); }

require_once '../includes/database.php';
require_once '../includes/pdf_tools/fpdf.php';

// head and footer
class PDF extends FPDF {
    // header
    function Header() {
        // $this->Image(filepath, X, Y, Width, Height)
        if (file_exists('../assets/img/header.png')) {
            $this->Image('../assets/img/header.png', 15, 10, 45); 
        }
        $this->Ln(35); 
    }

    // Page Footer
    function Footer() {
        $this->SetY(-20);
        
        // CENTER
        $this->SetFont('Arial', 'B', 9);
        // Set text to DTI Blue (RGB: 0, 51, 153)
        $this->SetTextColor(0, 51, 153);
        $this->Cell(0, 5, 'ZAMBOANGA PENINSULA REGION', 0, 1, 'C');
        $this->Ln(2);
        

        $this->SetTextColor(0, 0, 0);
        
        $y_pos = $this->GetY();
        
        // --- LEFT SIDE ---
        $this->SetFont('Arial', '', 8);
        $this->SetXY(15, $y_pos); 
        $this->Cell(80, 4, 'DTI 9 Regional Office, 2F, David Waistrom Building, ADZU Lantaka Campus,', 0, 2, 'L');
        $this->Cell(80, 4, 'NS Valderosa Street, 7000 Zamboanga City, Philippines', 0, 0, 'L');
        
        // --- RIGHT SIDE ---
        
        // Row 1: Telephone
        $this->SetXY(140, $y_pos);
        $this->SetFont('ZapfDingbats', '', 9); 
        $this->Cell(5, 4, chr(37), 0, 0);      
        $this->SetFont('Arial', '', 8);        
        $this->Cell(25, 4, '(6362) 955-3237', 0, 0);
        
        // Row 1: Envelope
        $this->SetXY(175, $y_pos);
        $this->SetFont('ZapfDingbats', '', 9); 
        $this->Cell(5, 4, chr(41), 0, 0);      
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 51, 153);
        $this->Cell(30, 4, 'r09@dti.gov.ph', 0, 0);
        
        // Row 2: Website 
        $this->SetXY(140, $y_pos + 4);        
        $this->SetFont('Arial', 'B', 8); 
        //$this->Cell(8, 4, 'Web:', 0, 0);      
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(0, 51, 153);
        $this->Cell(40, 4, '        www.dti.gov.ph', 0, 0);
    }
}

$db = new Database();
$conn = $db->getConnection();
$emp_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// fetch
$stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
$stmt->bind_param("i", $emp_id); 
$stmt->execute();
$emp = $stmt->get_result()->fetch_assoc();
if (!$emp) { die("Employee not found."); }

$stmt_work = $conn->prepare("SELECT * FROM employment_history WHERE employee_id = ? ORDER BY start_date DESC LIMIT 1");
$stmt_work->bind_param("i", $emp_id); 
$stmt_work->execute();
$work = $stmt_work->get_result()->fetch_assoc();
if (!$work) { die("Cannot generate certificate: No work experience found for this employee."); }

$midInitial = !empty($emp['middle_name']) ? substr($emp['middle_name'], 0, 1) . '. ' : '';
$fullName = strtoupper($emp['first_name'] . ' ' . $midInitial . $emp['last_name'] . ' ' . $emp['name_extension']);

$pronoun1 = ($emp['sex'] == 'Female') ? 'Her' : 'His';
$pronoun2 = ($emp['sex'] == 'Female') ? 'she' : 'he';
$title = ($emp['sex'] == 'Female') ? 'Ms.' : 'Mr.';
$shortName = $title . ' ' . $emp['last_name'];

$emp_type = $work['employment_type'];
$position = $work['position_title'];
$dept = $work['department_program'];

if ($emp_type == 'JO' || $emp_type == 'Job Order') {
    $type_str = "Job Order (JO)";
    $dept_str = ""; // hide dept if JO
} else {
    $type_str = "Contract of Service (COS)";
    $dept_str = " under the " . $dept . ",";
}

$start = date('F j, Y', strtotime($work['start_date']));
$end = (!empty($work['end_date']) && $work['end_date'] != '0000-00-00') ? date('F j, Y', strtotime($work['end_date'])) : 'Present';

$todayStr = date('jS \d\a\y \o\f F Y');

// Compile the Paragraphs
 $type_str . " worker" . $dept_str . " serving as " . $position . ". " . $pronoun1 . " engagement covered the period from " . $start . " to " . $end . ", and " . $pronoun2 . " was assigned to the DTI Regional Office.";

$paragraph2 = "This certification is issued upon the request of " . $shortName . " for whatever legal purpose it may serve.";

$paragraph3 = "Issued this " . $todayStr . ", at the Department of Trade and Industry, Regional Office IX, Zamboanga City, Philippines.";


// FPDF DOCUMENT LAYOUT 

$pdf = new PDF('P', 'mm', 'Letter');
$pdf->AddPage();

// Margins: Left, Top, Right
$pdf->SetMargins(31.5, 12.7, 25.4); 

// Bottom Margin (3.3mm)
$pdf->SetAutoPageBreak(true, 3.3);

// TITLE
$pdf->SetFont('Arial', 'B', 20);

$pdf->SetX(31.5); 


$pdf->Cell(159, 10, 'CERTIFICATION', 0, 1, 'C');

$pdf->Ln(15);

// SALUTATION
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'TO WHOM IT MAY CONCERN:', 0, 1, 'L');


// BODY PARAGRAPHS 
$indent = "          "; 
$space_12pt = 4.23;

// 1st paragraph
$pdf->SetFont('Arial', '', 12);
$pdf->Ln($space_12pt); // 12pt Before


$paragraph1 = "This is to certify that " . $fullName . ", was engaged by the Department of Trade and Industry (DTI) as a " . $type_str . " worker" . $dept_str . " serving as " . $position . ". " . $pronoun1 . " engagement covered the period from " . $start . " to " . $end . ", and " . $pronoun2 . " was assigned to the DTI Regional Office.";

$pdf->MultiCell(0, 8, $indent . $paragraph1, 0, 'J');

$pdf->Ln($space_12pt); 
$pdf->MultiCell(0, 8, $indent . $paragraph2, 0, 'J');


// Paragraph 3
$pdf->Ln($space_12pt);
$pdf->MultiCell(0, 8, $indent . $paragraph3, 0, 'J');


$pdf->Ln(25); 

// SIGNATORY BLOCK
$pdf->SetFont('Arial', 'B', 12);

$pdf->SetX(95); 
$pdf->Cell(0, 6, 'SHARON B. BAZAN-MICUBO', 0, 1, 'C');

$pdf->SetFont('Arial', '', 11);
$pdf->SetX(95);
$pdf->Cell(0, 6, 'Administrative Officer V (HRMO III)', 0, 1, 'C');


$filename = 'Certificate_' . str_replace(' ', '_', $emp['last_name']) . '.pdf';
$pdf->Output('I', $filename);
?>