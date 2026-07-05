<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) { 
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access.']); 
    exit(); 
}

require_once '../includes/database.php';
header('Content-Type: application/json');

if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please refresh the page and try again.']);
        exit();
    }

    // Sanitize Inputs
    foreach ($_POST as $key => $value) {
        if (is_string($value)) {
            $_POST[$key] = trim($value);
        } elseif (is_array($value)) {
            foreach ($value as $k => $v) {
                $_POST[$key][$k] = is_string($v) ? trim($v) : $v;
            }
        }
    }

    $db = new Database();
    $conn = $db->getConnection();
    $emp_id = intval($_POST['employee_id']);

    $raw_office_id = trim($_POST['office_id'] ?? '');
    
    if ($raw_office_id === '' || strtoupper($raw_office_id) === 'N/A' || strtoupper($raw_office_id) === 'NONE') {
        $final_office_id = NULL;
    } else {
        $final_office_id = $raw_office_id;
    }

    if ($final_office_id !== NULL) {
        $checkStmt = $conn->prepare("SELECT employee_id FROM employees WHERE office_id = ? AND employee_id != ?");
        $checkStmt->bind_param("si", $final_office_id, $emp_id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Duplicate Error: The Office ID No. "' . $final_office_id . '" is already registered to someone else!',
                'field' => 'office_id'
            ]);
            exit();
        }
        $checkStmt->close();
    }

    $conn->begin_transaction();
    try {
        // ---  PHOTO UPLOAD (Only updates if a new photo is selected) ---
        $photo_query_part = "";
        $photo_path = NULL;
        if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png'];
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
            finfo_close($finfo);
            $allowed_mime = ['image/jpeg', 'image/png'];

            if (in_array($ext, $allowed_ext) && in_array($mime, $allowed_mime) && $_FILES['photo']['size'] <= 2097152) {
                $new_filename = preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['office_id']) . '_' . time() . '.' . $ext;
                $target_dir = "../assets/img/uploads/";
                if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                if(move_uploaded_file($_FILES['photo']['tmp_name'], $target_dir . $new_filename)) {
                    $photo_path = "assets/img/uploads/" . $new_filename;
                    $photo_query_part = ", photo_path = ?";
                }
            } else {
                throw new Exception("Invalid photo. Only JPG/PNG images under 2MB are allowed.");
            }
        }

        // --- 1. UPDATE EMPLOYEES MAIN TABLE ---
        $is_ig = isset($_POST['is_indigenous']) ? 1 : 0;
        $ig_name = $is_ig ? $_POST['ig_name'] : NULL;
        $is_pwd = isset($_POST['is_pwd']) ? 1 : 0;
        $is_solo = isset($_POST['is_solo_parent']) ? 1 : 0;
        $cit_type = isset($_POST['citizenship_type']) ? $_POST['citizenship_type'] : NULL;
        $cit_country = isset($_POST['citizenship_country']) ? $_POST['citizenship_country'] : NULL;

        $emp_query = "UPDATE employees SET 
            office_id=?, first_name=?, middle_name=?, last_name=?, name_extension=?, 
            dob=?, place_of_birth=?, sex=?, civil_status=?, citizenship=?, citizenship_type=?, citizenship_country=?,
            email=?, contact_number=?, residential_address=?, residential_zip=?, permanent_address=?, permanent_zip=?, blood_type=?, 
            emergency_contact_name=?, emergency_contact_number=?, 
            is_indigenous=?, indigenous_group_name=?, is_pwd=?, is_solo_parent=?
            $photo_query_part
            WHERE employee_id=?";
            
        $stmt1 = $conn->prepare($emp_query);
        if (!$stmt1) throw new Exception("Prepare failed: " . $conn->error);

        if ($photo_path) {
            $stmt1->bind_param("sssssssssssssssssssssisiisi", 
                $final_office_id, $_POST['first_name'], $_POST['middle_name'], $_POST['last_name'], $_POST['name_extension'],
                $_POST['dob'], $_POST['place_of_birth'], $_POST['sex'], $_POST['civil_status'], $_POST['citizenship'], 
                $cit_type, $cit_country, $_POST['email'], $_POST['contact_number'], 
                $_POST['residential_address'], $_POST['residential_zip'], $_POST['permanent_address'], $_POST['permanent_zip'], $_POST['blood_type'],
                $_POST['emergency_contact_name'], $_POST['emergency_contact_number'],
                $is_ig, $ig_name, $is_pwd, $is_solo, $photo_path, $emp_id
            );
        } else {
            $stmt1->bind_param("sssssssssssssssssssssisiii", 
                $final_office_id, $_POST['first_name'], $_POST['middle_name'], $_POST['last_name'], $_POST['name_extension'],
                $_POST['dob'], $_POST['place_of_birth'], $_POST['sex'], $_POST['civil_status'], $_POST['citizenship'], 
                $cit_type, $cit_country, $_POST['email'], $_POST['contact_number'], 
                $_POST['residential_address'], $_POST['residential_zip'], $_POST['permanent_address'], $_POST['permanent_zip'], $_POST['blood_type'],
                $_POST['emergency_contact_name'], $_POST['emergency_contact_number'],
                $is_ig, $ig_name, $is_pwd, $is_solo, $emp_id
            );
        }
        $stmt1->execute();

        // WIPE OLD RELATED DATA TO PREVENT DUPLICATES
        $conn->query("DELETE FROM employee_family WHERE employee_id = $emp_id");
        $conn->query("DELETE FROM employee_identifications WHERE employee_id = $emp_id");
        $conn->query("DELETE FROM employee_education WHERE employee_id = $emp_id");
        $conn->query("DELETE FROM employee_eligibility WHERE employee_id = $emp_id");
        $conn->query("DELETE FROM employment_history WHERE employee_id = $emp_id");
        $conn->query("DELETE FROM employee_training WHERE employee_id = $emp_id");
        $conn->query("DELETE FROM employee_other_details WHERE employee_id = $emp_id");

        // --- 2. RE-INSERT FAMILY ---
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

        // --- 3. RE-INSERT IDs ---
        $stmtID = $conn->prepare("INSERT INTO employee_identifications (employee_id, id_type, id_number) VALUES (?, ?, ?)");
        $ids = ['TIN' => 'id_tin', 'UMID' => 'id_umid', 'PhilHealth' => 'id_philhealth', 'Pag-IBIG' => 'id_pagibig', 'PhilSys (PSN)' => 'id_philsys', 'PWD' => 'pwd_id', 'Solo Parent' => 'solo_parent_id'];
        foreach($ids as $type => $input) {
            if(!empty($_POST[$input])) {
                $stmtID->bind_param("iss", $emp_id, $type, $_POST[$input]);
                $stmtID->execute();
            }
        }

// --- 4. EDUCATION ---
        if (isset($_POST['edu_level']) && is_array($_POST['edu_level'])) {
            $edu_query = "INSERT INTO employee_education (employee_id, level, school_name, degree_course, start_year, end_year, is_graduated, year_graduated, highest_level_units, academic_honors) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtEdu = $conn->prepare($edu_query);
            for ($i = 0; $i < count($_POST['edu_level']); $i++) {
                $school = trim($_POST['school_name'][$i] ?? '');
                if (!empty($school)) {
                    $level = trim($_POST['edu_level'][$i] ?? '');
                    $course = trim($_POST['degree_course'][$i] ?? '');
                    $s_year = trim($_POST['start_year'][$i] ?? '') !== '' ? trim($_POST['start_year'][$i]) : NULL;
                    $e_year = trim($_POST['end_year'][$i] ?? '') !== '' ? trim($_POST['end_year'][$i]) : NULL;
                    $is_grad = isset($_POST['is_graduated'][$i]) && trim($_POST['is_graduated'][$i]) !== '' ? intval($_POST['is_graduated'][$i]) : 0;
                    $y_grad = trim($_POST['year_graduated'][$i] ?? '') !== '' ? trim($_POST['year_graduated'][$i]) : NULL;
                    $units = trim($_POST['highest_level_units'][$i] ?? '') !== '' ? trim($_POST['highest_level_units'][$i]) : NULL;
                    $honors = trim($_POST['academic_honors'][$i] ?? '') !== '' ? trim($_POST['academic_honors'][$i]) : NULL;
                    
                    $stmtEdu->bind_param("isssssisss", $emp_id, $level, $school, $course, $s_year, $e_year, $is_grad, $y_grad, $units, $honors);
                    $stmtEdu->execute();
                }
            }
        }

        // --- 5. ELIGIBILITY ---
        if (isset($_POST['eligibility_name']) && is_array($_POST['eligibility_name'])) {
            $elig_query = "INSERT INTO employee_eligibility (employee_id, eligibility_name, rating, date_of_exam_conferment, place_of_exam_conferment, license_number, valid_until) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtElig = $conn->prepare($elig_query);
            for ($i = 0; $i < count($_POST['eligibility_name']); $i++) {
                $elig_name = trim($_POST['eligibility_name'][$i] ?? '');
                if (!empty($elig_name)) {
                    $rating = trim($_POST['eligibility_rating'][$i] ?? '') !== '' ? trim($_POST['eligibility_rating'][$i]) : NULL;
                    $exam_date = trim($_POST['date_of_exam_conferment'][$i] ?? '') !== '' ? trim($_POST['date_of_exam_conferment'][$i]) : NULL;
                    $place = trim($_POST['place_of_exam_conferment'][$i] ?? '');
                    $license = trim($_POST['license_number'][$i] ?? '');
                    $valid_until = trim($_POST['valid_until'][$i] ?? '') !== '' ? trim($_POST['valid_until'][$i]) : NULL;
                    
                    $stmtElig->bind_param("issssss", $emp_id, $elig_name, $rating, $exam_date, $place, $license, $valid_until);
                    $stmtElig->execute();
                }
            }
        }

        // --- 6. EMPLOYMENT HISTORY ---
        if (isset($_POST['position_title']) && is_array($_POST['position_title'])) {
            $work_query = "INSERT INTO employment_history (employee_id, start_date, end_date, position_title, department_program, employment_type, salary, office_assignment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtWork = $conn->prepare($work_query);
            for ($i = 0; $i < count($_POST['position_title']); $i++) {
                $pos_title = trim($_POST['position_title'][$i] ?? '');
                if (!empty($pos_title)) {
                    $s_date = trim($_POST['start_date'][$i] ?? '') !== '' ? trim($_POST['start_date'][$i]) : NULL;
                    $e_date = trim($_POST['end_date'][$i] ?? '') !== '' ? trim($_POST['end_date'][$i]) : NULL;
                    
                    $salary_raw = trim($_POST['salary'][$i] ?? '');
                    $salary = $salary_raw !== '' ? floatval($salary_raw) : NULL;
                    
                    $dept = trim($_POST['department_program'][$i] ?? '');
                    $office_assign = trim($_POST['office_assignment'][$i] ?? '');
                    $base_type = trim($_POST['employment_type_base'][$i] ?? '');
                    $final_type = ($base_type === 'Others') ? trim($_POST['employment_type_specify'][$i] ?? '') : $base_type;
                    
                    $stmtWork->bind_param("isssssds", $emp_id, $s_date, $e_date, $pos_title, $dept, $final_type, $salary, $office_assign);
                    $stmtWork->execute();
                }
            }
        }

        // --- 7. L&D / TRAINING ---
        if (isset($_POST['training_title']) && is_array($_POST['training_title'])) {
            $train_query = "INSERT INTO employee_training (employee_id, training_title, start_date, end_date, hours, l_and_d_type, sponsor) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmtTrain = $conn->prepare($train_query);
            for ($i = 0; $i < count($_POST['training_title']); $i++) {
                $title = trim($_POST['training_title'][$i] ?? '');
                if (!empty($title)) {
                    $s_date = trim($_POST['train_start'][$i] ?? '') !== '' ? trim($_POST['train_start'][$i]) : NULL;
                    $e_date = trim($_POST['train_end'][$i] ?? '') !== '' ? trim($_POST['train_end'][$i]) : NULL;
                    $hours = trim($_POST['training_hours'][$i] ?? '') !== '' ? trim($_POST['training_hours'][$i]) : NULL;
                    $type = trim($_POST['l_and_d_type'][$i] ?? '');
                    $sponsor = trim($_POST['sponsor'][$i] ?? '');
                    
                    $stmtTrain->bind_param("issssss", $emp_id, $title, $s_date, $e_date, $hours, $type, $sponsor);
                    $stmtTrain->execute();
                }
            }
        }

        // --- 8. OTHER INFORMATION ---
        if (isset($_POST['detail_type']) && is_array($_POST['detail_type'])) {
            $other_query = "INSERT INTO employee_other_details (employee_id, detail_type, detail_description) VALUES (?, ?, ?)";
            $stmtOther = $conn->prepare($other_query);
            for ($i = 0; $i < count($_POST['detail_type']); $i++) {
                $type = trim($_POST['detail_type'][$i] ?? '');
                $desc = trim($_POST['detail_description'][$i] ?? '');
                if (!empty($type) && !empty($desc)) {
                    $stmtOther->bind_param("iss", $emp_id, $type, $desc);
                    $stmtOther->execute();
                }
            }
        }

        $conn->commit();
        echo json_encode(['success' => true]);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => "Database Error: " . $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit();
}