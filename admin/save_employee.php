<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) { 
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access.']); 
    exit(); 
}

require_once '../includes/database.php';
header('Content-Type: application/json');

if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['office_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

if (!is_numeric($_POST['contact_number'])) {
    echo json_encode(['success' => false, 'message' => 'Contact number must be numeric.']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize 
    foreach ($_POST as $key => $value) {
        if (is_string($value)) {
            $_POST[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        } elseif (is_array($value)) {
            foreach ($value as $k => $v) {
                $_POST[$key][$k] = htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
            }
        }
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Check if Office ID already exists
    $checkStmt = $conn->prepare("SELECT employee_id FROM employees WHERE office_id = ?");
    $checkStmt->bind_param("s", $_POST['office_id']);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Duplicate Error: The Office ID No. "' . $_POST['office_id'] . '" is already registered!',
            'field' => 'office_id'
        ]);
        exit();
    }

    $conn->begin_transaction();
    try {
        // photo upload
        $photo_path = NULL;
        if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $new_filename = preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['office_id']) . '_' . time() . '.' . $ext;
            $target_dir = "../assets/img/uploads/";
            if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            if(move_uploaded_file($_FILES['photo']['tmp_name'], $target_dir . $new_filename)) {
                $photo_path = "assets/img/uploads/" . $new_filename;
            }
        }

        // --- 1. EMPLOYEES ---
        // --- 1. EMPLOYEES ---
        $emp_query = "INSERT INTO employees (
            office_id, first_name, middle_name, last_name, name_extension, 
            dob, place_of_birth, sex, civil_status, citizenship, citizenship_type, citizenship_country,
            email, contact_number, residential_address, residential_zip, permanent_address, permanent_zip, blood_type, 
            emergency_contact_name, emergency_contact_number, photo_path,
            is_indigenous, indigenous_group_name, is_pwd, is_solo_parent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt1 = $conn->prepare($emp_query);
        $is_ig = isset($_POST['is_indigenous']) ? 1 : 0;
        $ig_name = $is_ig ? $_POST['ig_name'] : NULL;
        $is_pwd = isset($_POST['is_pwd']) ? 1 : 0;
        $is_solo = isset($_POST['is_solo_parent']) ? 1 : 0;
        $cit_type = isset($_POST['citizenship_type']) ? $_POST['citizenship_type'] : NULL;
        $cit_country = isset($_POST['citizenship_country']) ? $_POST['citizenship_country'] : NULL;

        $stmt1->bind_param("ssssssssssssssssssssssisii", 
            $_POST['office_id'], $_POST['first_name'], $_POST['middle_name'], $_POST['last_name'], $_POST['name_extension'],
            $_POST['dob'], $_POST['place_of_birth'], $_POST['sex'], $_POST['civil_status'], $_POST['citizenship'], 
            $cit_type, $cit_country, $_POST['email'], $_POST['contact_number'], 
            $_POST['residential_address'], $_POST['residential_zip'], $_POST['permanent_address'], $_POST['permanent_zip'], $_POST['blood_type'],
            $_POST['emergency_contact_name'], $_POST['emergency_contact_number'], $photo_path,
            $is_ig, $ig_name, $is_pwd, $is_solo
        );
        $stmt1->execute();
        $emp_id = $conn->insert_id;

        // --- 2. FAMILY ---
        $fam_query = "INSERT INTO employee_family (employee_id, relationship_type, first_name, middle_name, last_name, name_extension, date_of_birth) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtFam = $conn->prepare($fam_query);
        $null_val = NULL;

        if (!empty($_POST['spouse_fname']) && !empty($_POST['spouse_lname'])) {
            $rel = 'Spouse';
            $stmtFam->bind_param("issssss", $emp_id, $rel, $_POST['spouse_fname'], $_POST['spouse_mname'], $_POST['spouse_lname'], $_POST['spouse_ext'], $null_val);
            $stmtFam->execute();
        }
        if (!empty($_POST['father_fname']) && !empty($_POST['father_lname'])) {
            $rel = 'Father';
            $stmtFam->bind_param("issssss", $emp_id, $rel, $_POST['father_fname'], $_POST['father_mname'], $_POST['father_lname'], $_POST['father_ext'], $null_val);
            $stmtFam->execute();
        }
        if (!empty($_POST['mother_fname']) && !empty($_POST['mother_lname'])) {
            $rel = 'Mother';
            $stmtFam->bind_param("issssss", $emp_id, $rel, $_POST['mother_fname'], $_POST['mother_mname'], $_POST['mother_lname'], $null_val, $null_val);
            $stmtFam->execute();
        }
        if(isset($_POST['child_fname']) && is_array($_POST['child_fname'])) {
            $rel = 'Child';
            for($i = 0; $i < count($_POST['child_fname']); $i++) {
                if(!empty($_POST['child_fname'][$i]) && !empty($_POST['child_lname'][$i])) {
                    $dob = !empty($_POST['child_dob'][$i]) ? $_POST['child_dob'][$i] : NULL;
                    $stmtFam->bind_param("issssss", $emp_id, $rel, $_POST['child_fname'][$i], $null_val, $_POST['child_lname'][$i], $null_val, $dob);
                    $stmtFam->execute();
                }
            }
        }

        // --- 3. IDs ---
        $stmtID = $conn->prepare("INSERT INTO employee_identifications (employee_id, id_type, id_number) VALUES (?, ?, ?)");
        $ids = [
            'TIN' => 'id_tin', 
            'UMID' => 'id_umid', 
            'PhilHealth' => 'id_philhealth', 
            'Pag-IBIG' => 'id_pagibig',
            'PhilSys (PSN)' => 'id_philsys',
            'PWD' => 'pwd_id',
            'Solo Parent' => 'solo_parent_id'
        ];
        foreach($ids as $type => $input) {
            if(!empty($_POST[$input])) {
                $stmtID->bind_param("iss", $emp_id, $type, $_POST[$input]);
                $stmtID->execute();
            }
        }

        // --- 4. EDUCATION ---
        // --- 4. EDUCATION ---
       // --- 4. EDUCATION ---
        if(isset($_POST['edu_level']) && is_array($_POST['edu_level'])) {
            $edu_query = "INSERT INTO employee_education (employee_id, level, school_name, degree_course, start_year, end_year, is_graduated, year_graduated, highest_level_units, academic_honors) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtEdu = $conn->prepare($edu_query);
            for($i = 0; $i < count($_POST['edu_level']); $i++) {
                if(!empty($_POST['school_name'][$i])) {
                    $is_grad = $_POST['is_graduated'][$i];
                    $y_grad = !empty($_POST['year_graduated'][$i]) ? $_POST['year_graduated'][$i] : NULL;
                    $units = !empty($_POST['highest_level_units'][$i]) ? $_POST['highest_level_units'][$i] : NULL;
                    $honors = !empty($_POST['academic_honors'][$i]) ? $_POST['academic_honors'][$i] : NULL;
                    $s_year = !empty($_POST['start_year'][$i]) ? $_POST['start_year'][$i] : NULL;
                    $e_year = !empty($_POST['end_year'][$i]) ? $_POST['end_year'][$i] : NULL;
                    
                    $stmtEdu->bind_param("isssssisss", 
                        $emp_id, $_POST['edu_level'][$i], $_POST['school_name'][$i], $_POST['degree_course'][$i], 
                        $s_year, $e_year, $is_grad, $y_grad, $units, $honors
                    );
                    $stmtEdu->execute();
                }
            }
        }

        // --- 5. ELIGIBILITY ---
        if(isset($_POST['eligibility_name']) && is_array($_POST['eligibility_name'])) {
            $elig_query = "INSERT INTO employee_eligibility (employee_id, eligibility_name, rating, date_of_exam_conferment, place_of_exam_conferment, license_number, valid_until) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtElig = $conn->prepare($elig_query);
            for($i = 0; $i < count($_POST['eligibility_name']); $i++) {
                if(!empty($_POST['eligibility_name'][$i])) {
                    $rating = !empty($_POST['eligibility_rating'][$i]) ? $_POST['eligibility_rating'][$i] : NULL;
                    $exam_date = !empty($_POST['date_of_exam_conferment'][$i]) ? $_POST['date_of_exam_conferment'][$i] : NULL;
                    $valid_date = !empty($_POST['valid_until'][$i]) ? $_POST['valid_until'][$i] : NULL;
                    
                    $stmtElig->bind_param("isdssss", 
                        $emp_id, $_POST['eligibility_name'][$i], $rating, 
                        $exam_date, $_POST['place_of_exam_conferment'][$i], 
                        $_POST['license_number'][$i], $valid_date
                    );
                    $stmtElig->execute();
                }
            }
        }

        // --- 6. EMPLOYMENT HISTORY ---
        // --- 6. EMPLOYMENT HISTORY ---
        // --- 6. EMPLOYMENT HISTORY ---
        if(isset($_POST['position_title']) && is_array($_POST['position_title'])) {
            $work_query = "INSERT INTO employment_history (employee_id, start_date, end_date, position_title, department_program, employment_type, salary, office_assignment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtWork = $conn->prepare($work_query);
            
            for($i = 0; $i < count($_POST['position_title']); $i++) {
                // Only insert if they actually typed a position title
                if(!empty(trim($_POST['position_title'][$i]))) {
                    $s_date = !empty($_POST['start_date'][$i]) ? $_POST['start_date'][$i] : NULL;
                    $e_date = !empty($_POST['end_date'][$i]) ? $_POST['end_date'][$i] : NULL; // NULL for "Present"
                    $salary = !empty($_POST['salary'][$i]) ? floatval($_POST['salary'][$i]) : NULL;
                    
                    $base_type = $_POST['employment_type_base'][$i] ?? '';
                    $final_type = ($base_type === 'Others') ? ($_POST['employment_type_specify'][$i] ?? '') : $base_type;
                    
                    $stmtWork->bind_param("isssssds", 
                        $emp_id, $s_date, $e_date, $_POST['position_title'][$i], 
                        $_POST['department_program'][$i], $final_type, $salary, $_POST['office_assignment'][$i]
                    );
                    $stmtWork->execute();
                }
            }
        }

        // --- 8. OTHER INFORMATION ---
        if(isset($_POST['detail_type']) && is_array($_POST['detail_type'])) {
            $other_query = "INSERT INTO employee_other_details (employee_id, detail_type, detail_description) VALUES (?, ?, ?)";
            $stmtOther = $conn->prepare($other_query);
            for($i = 0; $i < count($_POST['detail_type']); $i++) {
                if(!empty($_POST['detail_type'][$i]) && !empty($_POST['detail_description'][$i])) {
                    $stmtOther->bind_param("iss", $emp_id, $_POST['detail_type'][$i], $_POST['detail_description'][$i]);
                    $stmtOther->execute();
                }
            }
        }

        $conn->commit();
        echo json_encode(['success' => true]);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit();
}