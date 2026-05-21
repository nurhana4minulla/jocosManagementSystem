<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php"); exit(); }

require_once '../includes/database.php';
include '../includes/header.php'; 

$db = new Database();
$conn = $db->getConnection();

$emp_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// fetch main info
$stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$emp = $stmt->get_result()->fetch_assoc();

if (!$emp) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Employee not found.</div></div>";
    include '../includes/footer.php';
    exit();
}

// helper Functions
function fetchAll($conn, $query, $id) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// fetch all related PDS arrays
$family = fetchAll($conn, "SELECT * FROM employee_family WHERE employee_id = ?", $emp_id);
$ids = fetchAll($conn, "SELECT * FROM employee_identifications WHERE employee_id = ?", $emp_id);
$education = fetchAll($conn, "SELECT * FROM employee_education WHERE employee_id = ?", $emp_id);
$eligibility = fetchAll($conn, "SELECT * FROM employee_eligibility WHERE employee_id = ?", $emp_id);
$work = fetchAll($conn, "SELECT * FROM employment_history WHERE employee_id = ? ORDER BY start_date DESC", $emp_id);
$training = fetchAll($conn, "SELECT * FROM employee_training WHERE employee_id = ? ORDER BY start_date DESC", $emp_id);
$other_info = fetchAll($conn, "SELECT * FROM employee_other_details WHERE employee_id = ?", $emp_id);

$parents_spouse = array_filter($family, function($f) { return $f['relationship_type'] != 'Child'; });
$children = array_filter($family, function($f) { return $f['relationship_type'] == 'Child'; });

// Calculate Age
$dob = new DateTime($emp['dob']);
$now = new DateTime();
$age = $now->diff($dob)->y;
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0" style="color: #0F172A;">Employee Profile</h4>
        <div>
            <a href="manage_employees.php" class="btn btn-sm btn-light border shadow-sm me-2 fw-bold text-muted">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
            
            <div class="btn-group shadow-sm">
                <button type="button" class="btn btn-sm text-white fw-bold px-4 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #0F172A;">
                    <i class="bi  me-1 text-warning"></i> Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-2" style="min-width: 230px;">
                    
                    <li><h6 class="dropdown-header text-uppercase small fw-bold text-muted">Management</h6></li>
                    <li><a class="dropdown-item py-2 fw-bold" href="edit_employee.php?id=<?php echo $emp_id; ?>">
                        <i class="bi bi-pencil-square me-2 text-primary"></i> Edit Profile
                    </a></li>
                    
                    <li><hr class="dropdown-divider"></li>
                    
                    <li><h6 class="dropdown-header text-uppercase small fw-bold text-muted">Generate Documents</h6></li>
                    <li><a class="dropdown-item py-2 fw-bold" href="generate_pds.php?id=<?php echo $emp_id; ?>">
                        <i class="bi bi-file-earmark-pdf-fill me-2 text-danger"></i> Official PDS (Form 212)
                    </a></li>
                    <li><a class="dropdown-item py-2 fw-bold" href="generate_certificate.php?id=<?php echo $emp_id; ?>" target="_blank">
                        <i class="bi bi-patch-check-fill me-2 text-success"></i> Employment Certificate
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-3 col-lg-4">
            <div class="card shadow-sm border-0 text-center p-4">
                <div class="mb-3">
                    <?php if (!empty($emp['photo_path']) && file_exists('../' . $emp['photo_path'])): ?>
                        <img src="../<?php echo htmlspecialchars($emp['photo_path']); ?>" alt="Profile Photo" class="rounded-circle object-fit-cover border shadow-sm" style="width: 150px; height: 150px;">
                    <?php else: ?>
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto fw-bold fs-1 shadow-sm" style="width: 150px; height: 150px;">
                            <?php echo strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <h5 class="fw-bold mb-1" style="color: #0F172A;">
                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                </h5>
                <p class="text-muted small mb-3">Office ID: <strong><?php echo htmlspecialchars($emp['office_id']); ?></strong></p>
                
                <?php 
                $current_contract = !empty($work) ? $work[0] : null;
                $today = date('Y-m-d');
                $is_active = false;
                
                if ($current_contract && !empty($current_contract['start_date'])) {
                    $is_present = empty($current_contract['end_date']) || $current_contract['end_date'] === '0000-00-00';
                    if ($today >= $current_contract['start_date'] && ($is_present || $today <= $current_contract['end_date'])) {
                        $is_active = true;
                    }
                }
                ?>
                
                <?php if ($is_active): ?>
                    <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-pill w-100 mb-3">
                        <i class="bi bi-check-circle-fill me-1"></i> Active Employee
                    </span>
                <?php else: ?>
                    <span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-3 py-2 rounded-pill w-100 mb-3" title="Contract expired or future-dated">
                        <i class="bi bi-x-circle-fill me-1"></i> Inactive
                    </span>
                <?php endif; ?>

                <div class="text-start mt-4 border-top pt-3">
                    <p class="small mb-1"><i class="bi bi-envelope text-primary me-2"></i> <?php echo !empty($emp['email']) ? htmlspecialchars($emp['email']) : 'N/A'; ?></p>
                    <p class="small mb-1"><i class="bi bi-telephone text-primary me-2"></i> <?php echo htmlspecialchars($emp['contact_number']); ?></p>
                    <p class="small mb-0"><i class="bi bi-geo-alt text-primary me-2"></i> <?php echo htmlspecialchars($emp['residential_address']); ?> <?php echo !empty($emp['residential_zip']) ? htmlspecialchars($emp['residential_zip']) : ''; ?></p>
                </div>
            </div>
        </div>

        <div class="col-xl-9 col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white pt-3 pb-0 border-bottom">
                    <ul class="nav nav-tabs border-0" id="profileTabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#personal">Personal & Family</button></li>
                        <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#education">Education & Elig.</button></li>
                        <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#work">Work, L&D & Others</button></li>
                    </ul>
                </div>
                
                <div class="card-body p-4 tab-content">
                    
                    <div class="tab-pane fade show active" id="personal">
                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Basic Demographics</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3"><span class="small text-muted d-block">Date of Birth</span><span class="fw-bold"><?php echo date('F j, Y', strtotime($emp['dob'])); ?> (<?php echo $age; ?> yrs)</span></div>
                            <div class="col-md-3"><span class="small text-muted d-block">Place of Birth</span><span class="fw-bold"><?php echo !empty($emp['place_of_birth']) ? htmlspecialchars($emp['place_of_birth']) : 'N/A'; ?></span></div>
                            <div class="col-md-3"><span class="small text-muted d-block">Sex</span><span class="fw-bold"><?php echo htmlspecialchars($emp['sex']); ?></span></div>
                            <div class="col-md-3"><span class="small text-muted d-block">Civil Status</span><span class="fw-bold"><?php echo htmlspecialchars($emp['civil_status']); ?></span></div>
                            <div class="col-md-3"><span class="small text-muted d-block">Blood Type</span><span class="fw-bold text-danger"><?php echo !empty($emp['blood_type']) ? htmlspecialchars($emp['blood_type']) : 'N/A'; ?></span></div>
                            <div class="col-md-3"><span class="small text-muted d-block">Citizenship</span><span class="fw-bold"><?php echo htmlspecialchars($emp['citizenship']); ?> <?php if($emp['citizenship'] == 'Dual Citizenship') echo '('.htmlspecialchars($emp['citizenship_country']).')'; ?></span></div>
                        </div>

                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2 mt-4">Special Demographics</h6>
                        <div class="row g-3 mb-4 bg-light p-3 rounded border">
                            <div class="col-md-4">
                                <span class="small text-muted d-block">Indigenous Group Member</span>
                                <?php if($emp['is_indigenous']): ?>
                                    <span class="fw-bold text-success">Yes (<?php echo htmlspecialchars($emp['indigenous_group_name']); ?>)</span>
                                <?php else: ?>
                                    <span class="fw-bold text-secondary">No</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <span class="small text-muted d-block">Person with Disability (PWD)</span>
                                <?php if($emp['is_pwd']): ?>
                                    <span class="fw-bold text-success">Yes</span>
                                <?php else: ?>
                                    <span class="fw-bold text-secondary">No</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <span class="small text-muted d-block">Solo Parent</span>
                                <?php if($emp['is_solo_parent']): ?>
                                    <span class="fw-bold text-success">Yes</span>
                                <?php else: ?>
                                    <span class="fw-bold text-secondary">No</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2 mt-4">Contact & Address Details</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <span class="small text-muted d-block">Residential Address</span>
                                <span class="fw-bold">
                                    <?php echo htmlspecialchars($emp['residential_address'] ?? 'N/A'); ?> 
                                    <?php echo !empty($emp['residential_zip']) ? '<span class="text-muted">('.htmlspecialchars($emp['residential_zip']).')</span>' : ''; ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <span class="small text-muted d-block">Permanent Address</span>
                                <span class="fw-bold">
                                    <?php echo !empty($emp['permanent_address']) ? htmlspecialchars($emp['permanent_address']) : 'Same as Residential'; ?> 
                                    <?php echo !empty($emp['permanent_zip']) ? '<span class="text-muted">('.htmlspecialchars($emp['permanent_zip']).')</span>' : ''; ?>
                                </span>
                            </div>
                            
                            <div class="col-md-6">
                                <span class="small text-muted d-block">Emergency Contact Name</span>
                                <span class="fw-bold text-danger"><i class="bi bi-person-heart me-1"></i> <?php echo htmlspecialchars($emp['emergency_contact_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="col-md-6">
                                <span class="small text-muted d-block">Emergency Contact Number</span>
                                <span class="fw-bold text-danger"><i class="bi bi-telephone-fill me-1"></i> <?php echo htmlspecialchars($emp['emergency_contact_number'] ?? 'N/A'); ?></span>
                            </div>
                        </div>

                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2 mt-4">Government Identifications</h6>
                        <div class="row g-3 mb-4">
                            <?php if(count($ids) > 0): ?>
                                <?php foreach($ids as $id_record): ?>
                                    <div class="col-md-4">
                                        <div class="p-2 border rounded bg-white shadow-sm">
                                            <span class="small text-muted d-block"><?php echo htmlspecialchars($id_record['id_type']); ?></span>
                                            <span class="fw-bold font-monospace text-dark"><?php echo htmlspecialchars($id_record['id_number']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 text-muted small">No Government IDs listed.</div>
                            <?php endif; ?>
                        </div>

                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2 mt-4">Parents & Spouse</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-sm small">
                                <thead class="bg-light"><tr><th style="width: 25%;">Relationship</th><th>Full Name</th></tr></thead>
                                <tbody>
                                    <?php if(count($parents_spouse) > 0): ?>
                                        <?php foreach($parents_spouse as $p): ?>
                                            <tr>
                                                <td class="fw-bold text-muted"><?php echo htmlspecialchars($p['relationship_type']); ?></td>
                                                <td><?php echo htmlspecialchars(trim($p['first_name'] . ' ' . $p['middle_name'] . ' ' . $p['last_name'] . ' ' . $p['name_extension'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="2" class="text-center text-muted">No parents or spouse listed.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2 mt-4">Children</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm small">
                                <thead class="bg-light"><tr><th>Full Name</th><th style="width: 30%;">Date of Birth</th></tr></thead>
                                <tbody>
                                    <?php if(count($children) > 0): ?>
                                        <?php foreach($children as $c): ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo htmlspecialchars(trim($c['first_name'] . ' ' . $c['last_name'])); ?></td>
                                                <td><?php echo !empty($c['date_of_birth']) ? date('M d, Y', strtotime($c['date_of_birth'])) : 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="2" class="text-center text-muted">No children listed.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="education">
                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Educational Background</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-sm small">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Level</th>
                                        <th>School Name</th>
                                        <th>Degree/Course</th>
                                        <th>Inclusive Dates</th>
                                        <th>Year Grad.</th>
                                        <th>Honors / Scholarships</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($education) > 0): ?>
                                        <?php foreach($education as $e): ?>
                                            <tr>
                                                <td class="fw-bold text-muted"><?php echo htmlspecialchars($e['level']); ?></td>
                                                <td><?php echo htmlspecialchars($e['school_name']); ?></td>
                                                <td><?php echo !empty($e['degree_course']) ? htmlspecialchars($e['degree_course']) : 'N/A'; ?></td>
                                                <td>
                                                    <?php 
                                                    if (!empty($e['start_year']) || !empty($e['end_year'])) {
                                                        echo htmlspecialchars($e['start_year'] ?? 'N/A') . ' - ' . htmlspecialchars($e['end_year'] ?? 'N/A');
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($e['is_graduated']) {
                                                        echo htmlspecialchars($e['year_graduated']);
                                                    } else {
                                                        echo '<span class="text-warning fw-bold">Undergrad</span>';
                                                        if (!empty($e['highest_level_units'])) {
                                                            echo '<br><small class="text-muted">' . htmlspecialchars($e['highest_level_units']) . '</small>';
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if (!empty($e['academic_honors'])) {
                                                        echo '<span class="badge bg-success bg-opacity-10 text-success fw-bold border border-success border-opacity-25">' . htmlspecialchars($e['academic_honors']) . '</span>';
                                                    } else {
                                                        echo '<span class="text-muted">N/A</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center text-muted">No education records found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Civil Service Eligibility</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm small">
                                <thead class="bg-light"><tr><th>Eligibility</th><th>Rating</th><th>Exam Date</th><th>License No.</th><th>Valid Until</th></tr></thead>
                                <tbody>
                                    <?php if(count($eligibility) > 0): ?>
                                        <?php foreach($eligibility as $el): ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo htmlspecialchars($el['eligibility_name']); ?></td>
                                                <td><?php echo !empty($el['rating']) ? htmlspecialchars($el['rating']) : 'N/A'; ?></td>
                                                <td><?php echo !empty($el['date_of_exam_conferment']) ? date('M d, Y', strtotime($el['date_of_exam_conferment'])) : 'N/A'; ?></td>
                                                <td><?php echo !empty($el['license_number']) ? htmlspecialchars($el['license_number']) : 'N/A'; ?></td>
                                                <td class="text-danger fw-bold"><?php echo !empty($el['valid_until']) ? date('M d, Y', strtotime($el['valid_until'])) : 'Lifetime / N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center text-muted">No eligibility records found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="work">
                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Work Experience (DTI Contract)</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-sm small">
                                <thead class="bg-light"><tr><th>Inclusive Dates</th><th>Position Title</th><th>Department / Agency (Office Assignment)</th><th>Type</th><th>Length of Service</th><th>Salary</th></tr></thead>
                                <tbody>
                                   <?php if(count($work) > 0): ?>
                                        <?php 
                                            $today = date('Y-m-d');
                                            foreach($work as $w): 
                                                $is_present = empty($w['end_date']) || $w['end_date'] === '0000-00-00';
                                                
                                                // Highlight as current if started and (end date is empty OR end date is future/today)
                                                $is_current = (!empty($w['start_date']) && $today >= $w['start_date'] && ($is_present || $today <= $w['end_date']));
                                                
                                                // Format the end date or beautifully show "Present"
                                                $display_end = !$is_present ? date('M d, Y', strtotime($w['end_date'])) : '<span class="text-success fw-bold">Present</span>';

                                                // --- LENGTH OF SERVICE COMPUTATION ---
                                                $length_of_service = 'N/A';
                                                if (!empty($w['start_date']) && $w['start_date'] !== '0000-00-00') {
                                                    $start_date_obj = new DateTime($w['start_date']);
                                                    $end_date_obj = $is_present ? new DateTime() : new DateTime($w['end_date']);
                                                    
                                                    if ($start_date_obj <= $end_date_obj) {
                                                        $diff = $start_date_obj->diff($end_date_obj);
                                                        $y = $diff->y; $m = $diff->m; $d = $diff->d;
                                                        $parts = [];
                                                        if ($y > 0) $parts[] = $y . " Year" . ($y > 1 ? "s" : "");
                                                        if ($m > 0) $parts[] = $m . " Month" . ($m > 1 ? "s" : "");
                                                        if ($y == 0 && $m == 0 && $d > 0) $parts[] = $d . " Day" . ($d > 1 ? "s" : "");
                                                        $length_of_service = empty($parts) ? "Less than a day" : implode(", ", $parts);
                                                    }
                                                }
                                            ?>
                                                <tr class="<?php echo $is_current ? 'table-primary bg-opacity-10' : ''; ?>">
                                                    <td class="text-nowrap">
                                                        <?php echo date('M d, Y', strtotime($w['start_date'])) . ' - ' . $display_end; ?>
                                                    </td>
                                                    <td class="fw-bold">
                                                        <?php echo htmlspecialchars($w['position_title']); ?>
                                                        <?php if ($is_current): ?>
                                                            <br><span class="badge bg-primary mt-1" style="font-size: 0.65rem;">Current Contract</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($w['department_program']); ?><br><small class="text-muted">(<?php echo htmlspecialchars($w['office_assignment']); ?>)</small></td>
                                                    <td><?php echo htmlspecialchars($w['employment_type']); ?></td>
                                                    
                                                    <td><span class="text-dark fw-bold bg-light px-2 py-1 rounded border"><?php echo $length_of_service; ?></span></td>
                                                    
                                                    <td><?php echo !empty($w['salary']) ? '₱'.number_format($w['salary'], 2) : 'N/A'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center text-muted py-3">No work experience listed.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Learning and Development (L&D)</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-sm small">
                                <thead class="bg-light"><tr><th>Training Title</th><th>Dates</th><th>Hours</th><th>Sponsor</th></tr></thead>
                                <tbody>
                                    <?php if(count($training) > 0): ?>
                                        <?php foreach($training as $t): ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo htmlspecialchars($t['training_title']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($t['start_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($t['hours']); ?> hrs</td>
                                                <td><?php echo htmlspecialchars($t['sponsor']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center text-muted">No L&D records found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Other Information</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm small">
                                <thead class="bg-light"><tr><th style="width: 30%;">Category</th><th>Details</th></tr></thead>
                                <tbody>
                                    <?php if(count($other_info) > 0): ?>
                                        <?php foreach($other_info as $info): ?>
                                            <tr>
                                                <td class="fw-bold text-muted"><?php echo htmlspecialchars($info['detail_type']); ?></td>
                                                <td><?php echo htmlspecialchars($info['detail_description']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="2" class="text-center text-muted">No other information listed.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>