<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php"); exit(); }

require_once '../includes/database.php';
require_once '../includes/pdf_tools/fpdf.php';
require_once '../includes/pdf_tools/fpdi/autoload.php'; 
use setasign\Fpdi\Fpdi;

$db = new Database();
$conn = $db->getConnection();

$emp_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// fetch

$stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
$stmt->bind_param("i", $emp_id); $stmt->execute();
$emp = $stmt->get_result()->fetch_assoc();

if (!$emp) { die("Employee not found."); }

// fetch Government IDs
$gov_ids = ['TIN' => '', 'PhilSys (PSN)' => '', 'PhilHealth' => '', 'Pag-IBIG' => '', 'UMID' => '', 'PWD' => '', 'Solo Parent' => ''];
$stmt_ids = $conn->prepare("SELECT id_type, id_number FROM employee_identifications WHERE employee_id = ?");
$stmt_ids->bind_param("i", $emp_id); $stmt_ids->execute(); $res_ids = $stmt_ids->get_result();
while($id = $res_ids->fetch_assoc()) { $gov_ids[$id['id_type']] = $id['id_number']; }

// fetch family
$fam = ['Spouse' => '', 'Father' => '', 'Mother' => '', 'Children' => []];
$stmt_fam = $conn->prepare("SELECT relationship_type, first_name, last_name, name_extension, date_of_birth, middle_name FROM employee_family WHERE employee_id = ?");
$stmt_fam->bind_param("i", $emp_id); $stmt_fam->execute(); $res_fam = $stmt_fam->get_result();
while($f = $res_fam->fetch_assoc()) {
    if($f['relationship_type'] == 'Child') { $fam['Children'][] = $f; }
    else { $fam[$f['relationship_type']] = $f; }
}

// fetch education
$edu = [];
$education_levels = ['Elementary', 'Secondary', 'Vocational', 'College', 'Graduate Studies'];
$stmt_edu = $conn->prepare("SELECT * FROM employee_education WHERE employee_id = ? ORDER BY year_graduated DESC");
$stmt_edu->bind_param("i", $emp_id); $stmt_edu->execute(); $res_edu = $stmt_edu->get_result();
while($ed = $res_edu->fetch_assoc()) { $edu[] = $ed; }

//fetch eligibility
$eligibility = [];
$stmt_elig = $conn->prepare("SELECT * FROM employee_eligibility WHERE employee_id = ?");
$stmt_elig->bind_param("i", $emp_id); $stmt_elig->execute(); $res_elig = $stmt_elig->get_result();
while($el = $res_elig->fetch_assoc()) { $eligibility[] = $el; }

// fetch trainings
$trainings = [];
$stmt_trn = $conn->prepare("SELECT * FROM employee_training WHERE employee_id = ? ORDER BY start_date DESC");
$stmt_trn->bind_param("i", $emp_id); $stmt_trn->execute(); $res_trn = $stmt_trn->get_result();
while($tr = $res_trn->fetch_assoc()) { $trainings[] = $tr; }

// fetch work exp
$work_experience = [];
$stmt_work = $conn->prepare("SELECT * FROM employment_history WHERE employee_id = ? ORDER BY start_date DESC");
$stmt_work->bind_param("i", $emp_id); $stmt_work->execute(); $res_work = $stmt_work->get_result();
while($wk = $res_work->fetch_assoc()) { $work_experience[] = $wk; }

// fetch other details (skills, distinctions, memberships)
$others = [];
$stmt_oth = $conn->prepare("SELECT * FROM employee_other_details WHERE employee_id = ?");
$stmt_oth->bind_param("i", $emp_id); $stmt_oth->execute(); $res_oth = $stmt_oth->get_result();
while($oth = $res_oth->fetch_assoc()) { $others[] = $oth; }

$pdf = new Fpdi();

$pdf->SetAutoPageBreak(false);

$pageCount = $pdf->setSourceFile('../assets/PDS_Template.pdf');

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $pdf->AddPage('P', 'Letter'); 
    $tplIdx = $pdf->importPage($pageNo);
    $pdf->useTemplate($tplIdx, 0, 0, 215.9, 279.4); 
    
    $pdf->SetFont('Arial', 'B', 6);

    if ($pageNo == 1) {
        
        // basic info
        $pdf->SetXY(50, 40); $pdf->Cell(0, 0, strtoupper(utf8_decode($emp['last_name'])));
        $pdf->SetXY(50, 46); $pdf->Cell(0, 0, strtoupper(utf8_decode($emp['first_name'])));
        $pdf->SetXY(50, 51); $pdf->Cell(0, 0, strtoupper(utf8_decode($emp['middle_name'])));
        $pdf->SetXY(160, 47); $pdf->Cell(0, 0, strtoupper(utf8_decode($emp['name_extension'])));
        
        $dob = date('m/d/Y', strtotime($emp['dob']));
        $pdf->SetXY(50, 60); $pdf->Cell(0, 0, $dob);
        $pdf->SetXY(50, 65); $pdf->Cell(0, 0, strtoupper(utf8_decode($emp['place_of_birth'])));
        
        // checkboxes 
        if ($emp['sex'] == 'Male') { $pdf->SetXY(50, 71); $pdf->Cell(0, 0, 'X'); } 
        else { $pdf->SetXY(75.5, 71.5); $pdf->Cell(0, 0, 'X'); }

        // citizenship
        if ($emp['citizenship'] == 'Filipino') { 
            $pdf->SetXY(134, 58); 
            $pdf->Cell(0, 0, 'X'); 
        } 
        elseif ($emp['citizenship'] == 'Dual Citizenship') { 
            $pdf->SetXY(150.5, 58); 
            $pdf->Cell(0, 0, 'X'); 

            if ($emp['citizenship_type'] == 'By Birth') {
                $pdf->SetXY(155, 62); 
                $pdf->Cell(0, 0, 'X');
            } elseif ($emp['citizenship_type'] == 'By Naturalization') {
                $pdf->SetXY(169, 62); 
                $pdf->Cell(0, 0, 'X');
            }
            if (!empty($emp['citizenship_country'])) {
                $pdf->SetXY(160, 70); 
                $pdf->Cell(0, 0, strtoupper(utf8_decode($emp['citizenship_country'])));
            }
        }

        // civil status
        switch ($emp['civil_status']) {
            case 'Single': $pdf->SetXY(50, 77); $pdf->Cell(0, 0, 'X'); break;
            case 'Married': $pdf->SetXY(76, 77); $pdf->Cell(0, 0, 'X'); break;
            case 'Widowed': $pdf->SetXY(50, 80); $pdf->Cell(0, 0, 'X'); break;
            case 'Separated': $pdf->SetXY(76, 80); $pdf->Cell(0, 0, 'X'); break;
            case 'Others': $pdf->SetXY(50, 83.5); $pdf->Cell(0, 0, 'X'); break;
        }

        // address
        $pdf->SetXY(120, 77); $pdf->Cell(0, 0, strtoupper(utf8_decode($emp['residential_address'])));
        $pdf->SetXY(120, 100); $pdf->Cell(0, 0, strtoupper(utf8_decode($emp['permanent_address'])));
        $pdf->SetXY(120, 95); $pdf->Cell(0, 0, strtoupper(utf8_decode($emp['residential_zip'])));
        $pdf->SetXY(120, 120); $pdf->Cell(0, 0, strtoupper(utf8_decode($emp['permanent_zip'])));
        
        // contact infos
        $pdf->SetXY(120, 131); $pdf->Cell(0, 0, $emp['contact_number']);
        $pdf->SetXY(120, 138); $pdf->Cell(0, 0, $emp['email']);
        $pdf->SetXY(50, 101); $pdf->Cell(0, 0, $emp['blood_type']);
        
        // IDs
        $pdf->SetXY(50, 107); $pdf->Cell(0, 0, $gov_ids['UMID'] ?? '');
        $pdf->SetXY(50, 112); $pdf->Cell(0, 0, $gov_ids['Pag-IBIG'] ?? '');
        $pdf->SetXY(50, 119); $pdf->Cell(0, 0, $gov_ids['PhilHealth'] ?? '');  
        $pdf->SetXY(50, 125); $pdf->Cell(0, 0, $gov_ids['PhilSys (PSN)'] ?? '');    
        $pdf->SetXY(50, 131); $pdf->Cell(0, 0, $gov_ids['TIN'] ?? '');
        $pdf->SetXY(50, 138); $pdf->Cell(0, 0, $emp['office_id'] ?? '');
        
        // fam bg
        if(!empty($fam['Spouse'])) {
            $pdf->SetXY(50, 147); $pdf->Cell(0, 0, strtoupper(utf8_decode($fam['Spouse']['last_name'])));
            $pdf->SetXY(50, 151); $pdf->Cell(0, 0, strtoupper(utf8_decode($fam['Spouse']['first_name'])));
            $pdf->SetXY(50, 157); $pdf->Cell(0, 0, strtoupper(utf8_decode($fam['Spouse']['middle_name'])));
            $pdf->SetXY(95, 152); $pdf->Cell(0, 0, strtoupper(utf8_decode($fam['Spouse']['name_extension'])));
        }
        if(!empty($fam['Father'])) {
            $pdf->SetXY(50, 181); $pdf->Cell(0, 0, strtoupper(utf8_decode($fam['Father']['last_name'])));
            $pdf->SetXY(50, 186.5); $pdf->Cell(0, 0, strtoupper(utf8_decode($fam['Father']['first_name'])));
            $pdf->SetXY(50, 192); $pdf->Cell(0, 0, strtoupper(utf8_decode($fam['Father']['middle_name'])));
            $pdf->SetXY(95, 187.5); $pdf->Cell(0, 0, strtoupper(utf8_decode($fam['Father']['name_extension'])));
        }
        if(!empty($fam['Mother'])) {
            $pdf->SetXY(50, 202); $pdf->Cell(0, 0, strtoupper(utf8_decode($fam['Mother']['last_name'])));
            $pdf->SetXY(50, 207); $pdf->Cell(0, 0, strtoupper(utf8_decode($fam['Mother']['first_name'])));
            $pdf->SetXY(50, 211); $pdf->Cell(0, 0, strtoupper(utf8_decode($fam['Mother']['middle_name'])));
        }

        // Children Loop
        $child_y = 151; 
        foreach($fam['Children'] as $index => $child) {
            if ($index > 11) break; 
            $pdf->SetXY(120, $child_y); 
            $pdf->Cell(0, 0, strtoupper(utf8_decode($child['first_name'] . ' ' . $child['last_name'])));
            
            $child_dob = date('m/d/Y', strtotime($child['date_of_birth']));
            $pdf->SetXY(180, $child_y);
            $pdf->Cell(0, 0, $child_dob);
            
            $child_y += 6; 
        }

        // educ bg
        $edu_y_coordinates = [
            'Elementary'              => 232, 
            'Secondary'               => 240, 
            // 'Senior High School'      => 248,
            'Vocational/Trade Course' => 248, 
            'College'                 => 255, 
            'Graduate Studies'        => 260,  
        ];

        $printed_levels = []; 

        foreach($edu as $ed) {
            $level = $ed['level'];
            
            if (isset($edu_y_coordinates[$level]) && !isset($printed_levels[$level])) {
                
                $printed_levels[$level] = true; 
                $y = $edu_y_coordinates[$level];

                // school name
                $pdf->SetXY(50, $y); 
                $pdf->Cell(0, 0, strtoupper(utf8_decode($ed['school_name'])));

                // degree/course
                $course_text = $ed['degree_course'];
                if (!empty($ed['strand'])) {
                    $course_text = $ed['strand']; 
                }
                $pdf->SetXY(92, $y); 
                $pdf->Cell(0, 0, strtoupper(utf8_decode($course_text)));

                // inclusive dates (From - To)
                if (!empty($ed['start_year'])) {
                    $pdf->SetXY(134, $y); 
                    $pdf->Cell(0, 0, $ed['start_year']);
                }
                if (!empty($ed['end_year'])) {
                    $pdf->SetXY(143, $y); 
                    $pdf->Cell(0, 0, $ed['end_year']);
                }

                // units earned if not graduated
                if ($ed['is_graduated'] == 0) {
                    $pdf->SetXY(155, $y); 
                    $pdf->Cell(0, 0, $ed['highest_level_units']);
                }

                // year grad
                if (!empty($ed['year_graduated'])) {
                    $pdf->SetXY(170, $y); 
                    $pdf->Cell(0, 0, $ed['year_graduated']);
                }

                // honors
                if (!empty($ed['academic_honors'])) {
                    $pdf->SetXY(180, $y); 
                    $pdf->Cell(0, 0, strtoupper(utf8_decode($ed['academic_honors'])));
                }
            }
        }
        
    } elseif ($pageNo == 2) {
        // eligibility 
        $elig_y = 22; 
        foreach($eligibility as $index => $el) {
            if ($index > 32) break; 
            $pdf->SetXY(32, $elig_y); $pdf->Cell(0, 0, strtoupper(utf8_decode($el['eligibility_name'])));
            $pdf->SetXY(110, $elig_y); $pdf->Cell(0, 0, date('m/d/Y', strtotime($el['date_of_exam_conferment'])));
            $pdf->SetXY(130, $elig_y); $pdf->Cell(0, 0, $el['place_of_exam_conferment']);
            $pdf->SetXY(155, $elig_y); $pdf->Cell(0, 0, strtoupper(utf8_decode($el['license_number'])));
            $pdf->SetXY(90, $elig_y); $pdf->Cell(0, 0, $el['rating']);
            $pdf->SetXY(173, $elig_y); $pdf->Cell(0, 0, date('m/d/Y', strtotime($el['valid_until'])));
            $elig_y += 7;
        }

        // work exp
        $work_y = 94; 
        
        foreach($work_experience as $index => $work) {
            if ($index > 27) break; 
            
            // start
            if (!empty($work['start_date'])) {
                $pdf->SetXY(31, $work_y); 
                $pdf->Cell(0, 0, date('m/d/Y', strtotime($work['start_date'])));
            }
            
            // end Date
           // end Date
            if (!empty($work['end_date'])) {
                $pdf->SetXY(46, $work_y); 
                $pdf->Cell(0, 0, date('m/d/Y', strtotime($work['end_date'])));
            } else {
                $pdf->SetXY(46, $work_y); 
                $pdf->Cell(0, 0, 'PRESENT');
            }
            
            // position Title
            $pdf->SetXY(61, $work_y); 
            $pdf->Cell(0, 0, strtoupper(utf8_decode($work['position_title'])));
            
            // Department / Agency
            $pdf->SetXY(110, $work_y); 
            $pdf->Cell(0, 0, strtoupper(utf8_decode($work['department_program'])));
            
            // JO/COS 
            $pdf->SetFont('Arial', 'B', 4.5); // Shrink font size just for this column
            $pdf->SetXY(155, $work_y); 
            $pdf->Cell(0, 0, strtoupper(utf8_decode($work['employment_type'])));
            $pdf->SetFont('Arial', 'B', 6);   // Reset back to normal size
            
            // salary
            if(!empty($work['salary'])) {
                $pdf->SetXY(171, $work_y); 
                $pdf->Cell(0, 0, number_format($work['salary'], 2)); 
            }

            $work_y += 6.5; 
        }
        

        
    } elseif ($pageNo == 3) {
        $trn_y = 92; // starting Y 
        $pdf->SetFont('Arial', 'B', 5);

        foreach($trainings as $index => $tr) {
            if ($index > 20) break; 
            
            $pdf->SetXY(20, $trn_y); 
            $pdf->Cell(0, 0, strtoupper(utf8_decode($tr['training_title'])));
            
            if(!empty($tr['start_date'])) {
                $pdf->SetXY(97.5, $trn_y); 
                $pdf->Cell(0, 0, date('m/d/Y', strtotime($tr['start_date'])));
            }
            if(!empty($tr['end_date'])) {
                $pdf->SetXY(112, $trn_y); 
                $pdf->Cell(0, 0, date('m/d/Y', strtotime($tr['end_date'])));
            }
            
            $pdf->SetXY(129, $trn_y); 
            $pdf->Cell(0, 0, $tr['hours']);
            
            $pdf->SetXY(140, $trn_y); 
            $pdf->Cell(0, 0, strtoupper(utf8_decode($tr['l_and_d_type'])));
            
            $pdf->SetXY(156, $trn_y); 
            $pdf->Cell(0, 0, strtoupper(utf8_decode($tr['sponsor'])));
            
            $trn_y += 6.5; // move down
        }

        $skill_y = 228;
        $dist_y = 228;
        $mem_y = 228;

        foreach($others as $o) {
            if($o['detail_type'] == 'Skill') {
                $pdf->SetXY(20, $skill_y); 
                $pdf->Cell(0, 0, strtoupper(utf8_decode($o['detail_description'])));
                $skill_y += 5; // move down
            } 
            elseif ($o['detail_type'] == 'Distinction') {
                $pdf->SetXY(70, $dist_y); 
                $pdf->Cell(0, 0, strtoupper(utf8_decode($o['detail_description'])));
                $dist_y += 5; 
            } 
            elseif ($o['detail_type'] == 'Membership') {
                $pdf->SetXY(156, $mem_y); 
                $pdf->Cell(0, 0, strtoupper(utf8_decode($o['detail_description'])));
                $mem_y += 5; 
            }
        }
        
    } elseif ($pageNo == 4) {
        $pdf->SetFont('Arial', 'B', 6);
        
        if ($emp['is_indigenous'] == 1 && !empty($emp['indigenous_group_name'])) {
            $pdf->SetXY(73, 142); 
            $pdf->Cell(0, 0, strtoupper(utf8_decode($emp['indigenous_group_name'])));
        }

        if ($emp['is_pwd'] == 1 && !empty($gov_ids['PWD'])) {
            $pdf->SetXY(62, 149); 
            $pdf->Cell(0, 0, 'ID#: ' . $gov_ids['PWD']);
        }

        if ($emp['is_solo_parent'] == 1 && !empty($gov_ids['Solo Parent'])) {
            $pdf->SetXY(50, 157); 
            $pdf->Cell(0, 0, 'ID#: ' . $gov_ids['Solo Parent']);
        }

        // photo
    //    if (!empty($emp['photo_path']) && file_exists('../' . $emp['photo_path'])) {
    //         // (filepath, X, Y, Width, Height);
    //         $pdf->Image('../' . $emp['photo_path'], 162.1, 170.5, 28.8, 31.8); 
    //     }

    }
    
}

$filename = 'PDS_' . str_replace(' ', '_', $emp['last_name']) . '_' . $emp['first_name'] . '.pdf';

// 'I' opens it in the browser tab. 
$pdf->Output('I', $filename);
?>