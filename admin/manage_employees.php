<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php"); exit(); }

require_once '../includes/database.php';

$db = new Database();
$conn = $db->getConnection();

// get filters
$search     = isset($_GET['search']) ? trim($_GET['search']) : '';
$status     = isset($_GET['status']) ? $_GET['status'] : 'all';
$office     = isset($_GET['office']) ? $_GET['office'] : 'all';
$department = isset($_GET['department']) ? trim($_GET['department']) : '';
$age_range  = isset($_GET['age']) ? $_GET['age'] : 'all';
$sex        = isset($_GET['sex']) ? $_GET['sex'] : 'all';
$degree     = isset($_GET['degree']) ? $_GET['degree'] : 'all';
$emp_type   = isset($_GET['emp_type']) ? $_GET['emp_type'] : 'all';
$sort       = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc'; 
$page       = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

$limit      = 10; 
$offset     = ($page - 1) * $limit;

$filter_sql = "";

if ($search !== '') {
    $safe_search = $conn->real_escape_string($search);
    $filter_sql .= " AND (e.first_name LIKE '%$safe_search%' OR e.last_name LIKE '%$safe_search%' OR e.office_id LIKE '%$safe_search%')";
}


if ($status === 'active')   { 
    $filter_sql .= " AND h.start_date <= CURDATE() AND (h.end_date IS NULL OR h.end_date >= CURDATE())"; 
}
if ($status === 'inactive') { 
    $filter_sql .= " AND (h.start_date > CURDATE() OR (h.end_date IS NOT NULL AND h.end_date < CURDATE()))"; 
}


if ($office !== 'all') {
    $safe_office = $conn->real_escape_string($office);
    $filter_sql .= " AND h.office_assignment = '$safe_office'";
}
if ($department !== '') {
    $safe_dept = $conn->real_escape_string($department);
    $filter_sql .= " AND h.department_program LIKE '%$safe_dept%'";
}
if ($sex !== 'all') {
    $safe_sex = $conn->real_escape_string($sex);
    $filter_sql .= " AND e.sex = '$safe_sex'";
}
if ($age_range === '18-25') { $filter_sql .= " AND TIMESTAMPDIFF(YEAR, e.dob, CURDATE()) BETWEEN 18 AND 25"; }
if ($age_range === '26-35') { $filter_sql .= " AND TIMESTAMPDIFF(YEAR, e.dob, CURDATE()) BETWEEN 26 AND 35"; }
if ($age_range === '36-45') { $filter_sql .= " AND TIMESTAMPDIFF(YEAR, e.dob, CURDATE()) BETWEEN 36 AND 45"; }
if ($age_range === '46-55') { $filter_sql .= " AND TIMESTAMPDIFF(YEAR, e.dob, CURDATE()) BETWEEN 46 AND 55"; }
if ($age_range === '56+')   { $filter_sql .= " AND TIMESTAMPDIFF(YEAR, e.dob, CURDATE()) >= 56"; }

if ($degree === 'yes') {
    $filter_sql .= " AND EXISTS (SELECT 1 FROM employee_education ed WHERE ed.employee_id = e.employee_id AND ed.is_graduated = 1 AND ed.level IN ('College', 'Graduate Studies'))";
} elseif ($degree === 'no') {
    $filter_sql .= " AND NOT EXISTS (SELECT 1 FROM employee_education ed WHERE ed.employee_id = e.employee_id AND ed.is_graduated = 1 AND ed.level IN ('College', 'Graduate Studies'))";
}
if ($emp_type === 'JO') { $filter_sql .= " AND h.employment_type = 'JO'"; }
if ($emp_type === 'COS') { $filter_sql .= " AND h.employment_type = 'COS'"; }

//sorting
$order_sql = "ORDER BY e.last_name ASC"; 
if ($sort === 'name_desc') { $order_sql = "ORDER BY e.last_name DESC"; }
if ($sort === 'age_asc')   { $order_sql = "ORDER BY e.dob DESC"; } 
if ($sort === 'age_desc')  { $order_sql = "ORDER BY e.dob ASC"; }  
if ($sort === 'newest')    { $order_sql = "ORDER BY e.employee_id DESC"; } 

// CSV EXPORT
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $export_query = "
        SELECT 
            e.*, 
            h.position_title, h.department_program, h.employment_type, 
            h.office_assignment, h.salary, h.start_date, h.end_date
        FROM employees e 
        LEFT JOIN employment_history h ON e.employee_id = h.employee_id 
        WHERE e.is_deleted = 0 " . $filter_sql . " 
        GROUP BY e.employee_id 
        ORDER BY e.last_name ASC
    ";
    
    $export_result = $conn->query($export_query);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=DTI_Complete_PDS_Export_' . date('Y-m-d') . '.csv');
    

    echo "\xEF\xBB\xBF"; 
    $output = fopen('php://output', 'w');
    
    // column headers
    $headers = [
        'OFFICE ID', 'STATUS', 'EMPLOYMENT TYPE', 'POSITION', 'DEPARTMENT/AGENCY', 'OFFICE ASSIGNMENT', 'START DATE', 'END DATE', 'DAILY SALARY',
        'LAST NAME', 'FIRST NAME', 'MIDDLE NAME', 'EXTENSION', 'SEX', 'DATE OF BIRTH', 'AGE', 'CIVIL STATUS', 'BLOOD TYPE',
        'CONTACT NUMBER', 'EMAIL', 'RESIDENTIAL ADDRESS', 'PERMANENT ADDRESS',
        'INDIGENOUS GROUP', 'PWD', 'SOLO PARENT',
        'TIN', 'GSIS', 'PHILHEALTH', 'PAG-IBIG',
        'SPOUSE NAME', 'FATHER NAME', 'MOTHER NAME', 'CHILDREN',
        'EDUCATIONAL BACKGROUND', 'CIVIL SERVICE ELIGIBILITY', 'L&D TRAININGS'
    ];
    fputcsv($output, $headers);
    
    $stmt_ids = $conn->prepare("SELECT id_type, id_number FROM employee_identifications WHERE employee_id = ?");
    $stmt_fam = $conn->prepare("SELECT relationship_type, first_name, last_name, name_extension FROM employee_family WHERE employee_id = ?");
    $stmt_edu = $conn->prepare("SELECT level, school_name, degree_course, year_graduated, is_graduated FROM employee_education WHERE employee_id = ? ORDER BY year_graduated DESC");
    $stmt_elig = $conn->prepare("SELECT eligibility_name, rating FROM employee_eligibility WHERE employee_id = ?");
    $stmt_trn = $conn->prepare("SELECT training_title, start_date, hours FROM employee_training WHERE employee_id = ? ORDER BY start_date DESC");

    if ($export_result && $export_result->num_rows > 0) {
        $now = new DateTime();
        
        // loop through each employee
        while ($row = $export_result->fetch_assoc()) {
            $eid = $row['employee_id'];

            // calc age
            $age = $row['dob'] ? $now->diff(new DateTime($row['dob']))->y : 'N/A';

            
            $today = date('Y-m-d');
            $is_active = (!empty($row['start_date']) && $today >= $row['start_date'] && (empty($row['end_date']) || $today <= $row['end_date']));
            $status = $is_active ? 'Active' : 'Inactive (Expired)';

            // fetch IDs
            $gov_ids = ['TIN' => 'N/A', 'GSIS' => 'N/A', 'PhilHealth' => 'N/A', 'Pag-IBIG' => 'N/A'];
            $stmt_ids->bind_param("i", $eid); $stmt_ids->execute(); $res_ids = $stmt_ids->get_result();
            while($id = $res_ids->fetch_assoc()) { $gov_ids[$id['id_type']] = $id['id_number']; }

            // f family
            $fam = ['Spouse' => 'N/A', 'Father' => 'N/A', 'Mother' => 'N/A', 'Children' => []];
            $stmt_fam->bind_param("i", $eid); $stmt_fam->execute(); $res_fam = $stmt_fam->get_result();
            while($f = $res_fam->fetch_assoc()) {
                $fullName = trim($f['first_name'] . ' ' . $f['last_name'] . ' ' . $f['name_extension']);
                if($f['relationship_type'] == 'Child') { $fam['Children'][] = $fullName; }
                else { $fam[$f['relationship_type']] = $fullName; }
            }
            $children_str = empty($fam['Children']) ? 'None' : implode(" | ", $fam['Children']);

            // f education
            $edu_str = [];
            $stmt_edu->bind_param("i", $eid); $stmt_edu->execute(); $res_edu = $stmt_edu->get_result();
            while($ed = $res_edu->fetch_assoc()) {
                $status_str = $ed['is_graduated'] ? "Grad. {$ed['year_graduated']}" : "Undergrad";
                $course = !empty($ed['degree_course']) ? " ({$ed['degree_course']})" : "";
                $edu_str[] = "[{$ed['level']}] {$ed['school_name']}$course - $status_str";
            }
            $edu_final = empty($edu_str) ? 'N/A' : implode(" | ", $edu_str);

            // f eligibilities
            $elig_str = [];
            $stmt_elig->bind_param("i", $eid); $stmt_elig->execute(); $res_elig = $stmt_elig->get_result();
            while($el = $res_elig->fetch_assoc()) {
                $rating = !empty($el['rating']) ? " ({$el['rating']}%)" : "";
                $elig_str[] = $el['eligibility_name'] . $rating;
            }
            $elig_final = empty($elig_str) ? 'None' : implode("\n", $elig_str);

            // f trainings
            $trn_str = [];
            $stmt_trn->bind_param("i", $eid); $stmt_trn->execute(); $res_trn = $stmt_trn->get_result();
            while($tr = $res_trn->fetch_assoc()) {
                $trn_str[] = "{$tr['training_title']} ({$tr['hours']} hrs)";
            }
            $trn_final = empty($trn_str) ? 'None' : implode("\n", $trn_str);

            // assemble the row
            $csv_row = [
                $row['office_id'], $status, $row['employment_type'], $row['position_title'], $row['department_program'], $row['office_assignment'], $row['start_date'], $row['end_date'], $row['salary'],
                $row['last_name'], $row['first_name'], $row['middle_name'], $row['name_extension'], $row['sex'], $row['dob'], $age, $row['civil_status'], $row['blood_type'],
                $row['contact_number'], $row['email'], $row['residential_address'], $row['permanent_address'],
                ($row['is_indigenous'] ? $row['indigenous_group_name'] : 'No'),
                ($row['is_pwd'] ? 'Yes' : 'No'),
                ($row['is_solo_parent'] ? 'Yes' : 'No'),
                $gov_ids['TIN'], $gov_ids['GSIS'], $gov_ids['PhilHealth'], $gov_ids['Pag-IBIG'],
                $fam['Spouse'], $fam['Father'], $fam['Mother'], $children_str,
                $edu_final, $elig_final, $trn_final
            ];

            fputcsv($output, $csv_row);
        }
    }
    
    fclose($output);
    exit(); 
}

include '../includes/header.php'; 

// get total records
$count_query = "SELECT COUNT(DISTINCT e.employee_id) as total FROM employees e LEFT JOIN employment_history h ON e.employee_id = h.employee_id WHERE e.is_deleted = 0 " . $filter_sql;
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// --- UPDATED: get employees (Fetching start_date & end_date for UI check) ---
$query = "
    SELECT 
        e.employee_id, e.office_id, e.first_name, e.last_name, e.dob, 
        e.sex, e.blood_type, e.photo_path,
        h.department_program, h.office_assignment, h.employment_type,
        h.start_date, h.end_date
    FROM employees e
    LEFT JOIN employment_history h ON e.employee_id = h.employee_id
    WHERE e.is_deleted = 0 " . $filter_sql . "
    GROUP BY e.employee_id 
    " . $order_sql . " 
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($query);

// get drafts
$drafts_query = "SELECT draft_id, draft_name, DATE_FORMAT(created_at, '%b %d, %Y - %h:%i %p') as date_saved FROM pds_drafts ORDER BY created_at DESC";
$drafts_result = $conn->query($drafts_query);

?>

<style>
    .table-pds td, .table-pds th { font-size: 0.875rem; padding-top: 0.6rem !important; padding-bottom: 0.6rem !important; }
    .table-pds .badge { font-size: 0.75rem; }
    .table-pds .btn-sm { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
    .table-pds .avatar-circle { width: 38px !important; height: 38px !important; font-size: 0.9rem !important; }
</style>

<div class="container-fluid py-4">
    
    

    <div class="row mb-4 align-items-center">
        <div class="col-md-6 d-flex align-items-center">
            <div>
                <h4 class="fw-bold mb-0" style="color: #0F172A;">Master List</h4>
                <p class="text-muted small mb-0">Showing <?php echo $total_records; ?> total personnel</p>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <?php 
                $export_params = $_GET;
                $export_params['export'] = 'csv';
                $export_url = '?' . http_build_query($export_params);
            ?>
            <a href="<?php echo $export_url; ?>" class="btn btn-sm btn-success shadow-sm fw-bold me-1" >
                <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
            </a>

            <a href="recycle_bin.php" class="btn btn-sm btn-outline-danger shadow-sm fw-bold me-2">
                <i class="bi bi-trash3"></i> Recycle Bin
            </a>
            <button class="btn btn-warning shadow-sm btn-sm fw-bold me-2" data-bs-toggle="modal" data-bs-target="#draftsModal">
                <i class="bi bi-file-earmark-text"></i> View Drafts
            </button>
            <a href="add_employee.php" class="btn btn-sm text-white fw-bold px-4 shadow-sm" style="background-color: #0F172A;">
                + Add New Employee
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 bg-light page-transition">
        <div class="card-body p-3">
            <form method="GET" action="manage_employees.php">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="filter-label"><i class="bi bi-search"></i> Search Name/ID</label>
                        <input type="text" name="search" class="form-control filter-input" placeholder="Type name or ID..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="filter-label">Status</label>
                        <select name="status" class="form-select filter-input">
                            <option value="all" <?php if($status == 'all') echo 'selected'; ?>>All Status</option>
                            <option value="active" <?php if($status == 'active') echo 'selected'; ?>>Active Only</option>
                            <option value="inactive" <?php if($status == 'inactive') echo 'selected'; ?>>Inactive Only</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="filter-label">Emp. Type</label>
                        <select name="emp_type" class="form-select filter-input">
                            <option value="all" <?php if($emp_type == 'all') echo 'selected'; ?>>All Types</option>
                            <option value="JO" <?php if($emp_type == 'JO') echo 'selected'; ?>>JO</option>
                            <option value="COS" <?php if($emp_type == 'COS') echo 'selected'; ?>>COS</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label">Department</label>
                        <input type="text" name="department" class="form-control filter-input" placeholder="e.g. Negosyo Center" value="<?php echo htmlspecialchars($department); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="filter-label">Office</label>
                        <select name="office" class="form-select filter-input">
                            <option value="all" <?php if($office == 'all') echo 'selected'; ?>>All Stations</option>
                            <option value="Zamboanga City" <?php if($office == 'Zamboanga City') echo 'selected'; ?>>Zamboanga City</option>
                            <option value="Zamboanga del Norte" <?php if($office == 'Zamboanga del Norte') echo 'selected'; ?>>Z. del Norte</option>
                            <option value="Zamboanga del Sur" <?php if($office == 'Zamboanga del Sur') echo 'selected'; ?>>Z. del Sur</option>
                            <option value="Zamboanga Sibugay" <?php if($office == 'Zamboanga Sibugay') echo 'selected'; ?>>Z. Sibugay</option>
                        </select>
                    </div>
                </div>
                
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="filter-label">Age Bracket</label>
                        <select name="age" class="form-select filter-input">
                            <option value="all" <?php if($age_range == 'all') echo 'selected'; ?>>All Ages</option>
                            <option value="18-25" <?php if($age_range == '18-25') echo 'selected'; ?>>18 - 25</option>
                            <option value="26-35" <?php if($age_range == '26-35') echo 'selected'; ?>>26 - 35</option>
                            <option value="36-45" <?php if($age_range == '36-45') echo 'selected'; ?>>36 - 45</option>
                            <option value="46-55" <?php if($age_range == '46-55') echo 'selected'; ?>>46 - 55</option>
                            <option value="56+" <?php if($age_range == '56+') echo 'selected'; ?>>56 and above</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="filter-label">Sex</label>
                        <select name="sex" class="form-select filter-input">
                            <option value="all" <?php if($sex == 'all') echo 'selected'; ?>>All</option>
                            <option value="Male" <?php if($sex == 'Male') echo 'selected'; ?>>Male</option>
                            <option value="Female" <?php if($sex == 'Female') echo 'selected'; ?>>Female</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="filter-label">Degree Holder</label>
                        <select name="degree" class="form-select filter-input">
                            <option value="all" <?php if($degree == 'all') echo 'selected'; ?>>All</option>
                            <option value="yes" <?php if($degree == 'yes') echo 'selected'; ?>>Yes (College/Grad)</option>
                            <option value="no" <?php if($degree == 'no') echo 'selected'; ?>>No</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label" style="color: #3b82f6;"><i class="bi bi-sort-down"></i> Sort By</label>
                        <select name="sort" class="form-select filter-input border-primary" style="background-color: #f0fdfa;" onchange="this.form.submit()">
                            <option value="name_asc" <?php if($sort == 'name_asc') echo 'selected'; ?>>Name (A to Z)</option>
                            <option value="name_desc" <?php if($sort == 'name_desc') echo 'selected'; ?>>Name (Z to A)</option>
                            <option value="age_asc" <?php if($sort == 'age_asc') echo 'selected'; ?>>Age (Youngest to Oldest)</option>
                            <option value="age_desc" <?php if($sort == 'age_desc') echo 'selected'; ?>>Age (Oldest to Youngest)</option>
                            <option value="newest" <?php if($sort == 'newest') echo 'selected'; ?>>Recently Added</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-end">
                        <a href="manage_employees.php" class="btn btn-light shadow-sm text-secondary me-1 fw-bold px-3 border-0" style="padding: 0.5rem;">Clear</a>
                        <button type="submit" class="btn text-white shadow-sm fw-bold px-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 0.5rem; border: none;">Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 page-transition">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap table-pds">
                    <thead class="bg-white text-muted small text-uppercase border-bottom">
                        <tr>
                            <th class="ps-4 py-3">Profile</th>
                            <th class="py-3">Office ID</th>
                            <th class="py-3">Full Name</th>
                            <th class="py-3">Age</th>
                            <th class="py-3">Sex</th>
                            <th class="py-3">Station</th>
                            <th class="py-3">Type</th>
                            <th class="py-3">Department</th>
                            <th class="py-3">Status</th>
                            <th class="text-end pe-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $dob = new DateTime($row['dob']);
                                $now = new DateTime();
                                $age = $now->diff($dob)->y;
                            ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <?php if (!empty($row['photo_path']) && file_exists('../' . $row['photo_path'])): ?>
                                            <img src="../<?php echo htmlspecialchars($row['photo_path']); ?>" alt="Photo" class="rounded-circle object-fit-cover border shadow-sm avatar-circle">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm avatar-circle">
                                                <?php echo strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 fw-bold text-secondary"><?php echo htmlspecialchars($row['office_id']); ?></td>
                                    <td class="py-3 fw-bold" style="color: #0F172A;"><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?></td>
                                    <td class="py-3 fw-bold text-dark"><?php echo $age; ?> <span class="text-muted fw-normal small">(<?php echo date('M Y', strtotime($row['dob'])); ?>)</span></td>
                                    <td class="py-3 text-muted"><?php echo htmlspecialchars($row['sex']); ?></td>
                                    <td class="py-3 text-muted fw-bold"><?php echo !empty($row['office_assignment']) ? htmlspecialchars($row['office_assignment']) : '<span class="text-muted fw-normal">N/A</span>'; ?></td>
                                    <td class="py-3 fw-bold text-primary"><?php echo !empty($row['employment_type']) ? htmlspecialchars($row['employment_type']) : '<span class="text-muted fw-normal">N/A</span>'; ?></td>
                                    <td class="py-3 text-muted"><?php echo !empty($row['department_program']) ? htmlspecialchars($row['department_program']) : '<span class="text-muted fw-normal">N/A</span>'; ?></td>
                                    <td class="py-3">
                                        <?php 
                                        $today = date('Y-m-d');
                                        $is_active = (!empty($row['start_date']) && $today >= $row['start_date'] && (empty($row['end_date']) || $today <= $row['end_date']));
                                        
                                        if ($is_active): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-1 rounded-pill" title="Contract expired or future-dated">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4 py-3">
                                        <div class="btn-group shadow-sm">
                                            <a href="view_employee.php?id=<?php echo $row['employee_id']; ?>" class="btn btn-sm btn-light border text-primary fw-bold" title="View Profile">View</a>
                                            <a href="edit_employee.php?id=<?php echo $row['employee_id']; ?>" class="btn btn-sm btn-light border text-success fw-bold" title="Edit Profile">Edit</a>
                                            <button type="button" class="btn btn-sm btn-light border text-danger fw-bold" onclick="confirmDelete(<?php echo $row['employee_id']; ?>)" title="Delete Profile">Del</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <div class="mb-3 mt-2 fs-1 text-light"><i class="bi bi-funnel"></i></div>
                                    <p class="mb-0 fw-bold fs-5">No matches found.</p>
                                    <p class="small">Try adjusting your filters or clearing them entirely.</p>
                                    <a href="manage_employees.php" class="btn btn-sm btn-outline-secondary mt-2">Clear Filters</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            
            <?php 
                function buildPageUrl($pageNum) {
                    $params = $_GET; 
                    $params['page'] = $pageNum; 
                    return 'manage_employees.php?' . http_build_query($params);
                }
            ?>

            <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
                <a class="page-link shadow-sm" href="<?php echo $page <= 1 ? '#' : buildPageUrl($page - 1); ?>">Previous</a>
            </li>

            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php if($page == $i) echo 'active'; ?>">
                    <a class="page-link shadow-sm <?php if($page == $i) echo 'bg-primary border-primary'; ?>" href="<?php echo buildPageUrl($i); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
                <a class="page-link shadow-sm" href="<?php echo $page >= $total_pages ? '#' : buildPageUrl($page + 1); ?>">Next</a>
            </li>
            
        </ul>
    </nav>
    <?php endif; ?>

</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pb-4 px-4">
                    <div class="mb-3 mt-2">
                        <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="bi bi-trash3-fill text-danger" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2" style="color: #0F172A;">Move to Recycle Bin?</h5>
                    <p class="text-muted small mb-4">This profile will be removed from the master list. You can restore it later.</p>
                    
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light border shadow-sm fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                        <a href="#" id="confirmDeleteBtn" class="btn btn-danger shadow-sm fw-bold px-4">Yes, Remove</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="draftsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="fw-bold mb-0" style="color: #0F172A;"><i class="bi bi-file-earmark-text text-warning me-2"></i> Saved Drafts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-white text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">Draft Name</th>
                                    <th class="py-3">Date Saved</th>
                                    <th class="text-end pe-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($drafts_result && $drafts_result->num_rows > 0): ?>
                                    <?php while($d = $drafts_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 py-3 fw-bold text-dark"><?php echo htmlspecialchars($d['draft_name']); ?></td>
                                            <td class="py-3 text-muted small"><i class="bi bi-clock me-1"></i> <?php echo $d['date_saved']; ?></td>
                                            <td class="text-end pe-4 py-3">
                                                <a href="add_employee.php?draft_id=<?php echo $d['draft_id']; ?>" class="btn btn-sm btn-success fw-bold me-1">Resume</a>
                                                <a href="delete_draft.php?id=<?php echo $d['draft_id']; ?>" class="btn btn-sm btn-outline-danger fw-bold" onclick="return confirm('Delete this draft permanently?');">Discard</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            <div class="mb-2 fs-1 text-light"><i class="bi bi-file-earmark-x"></i></div>
                                            <p class="mb-0 fw-bold fs-6" style="color: #0F172A;">No saved drafts</p>
                                            <p class="small mb-0">Unfinished employee profiles will appear here.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>

document.addEventListener("DOMContentLoaded", function() {
    const draftsModal = document.getElementById('draftsModal');
    if (draftsModal) {
        document.body.appendChild(draftsModal); 
    }
    
    // Fix Delete Modal
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        document.body.appendChild(deleteModal); 
    }
});

function confirmDelete(employeeId) {
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    confirmBtn.href = "delete_employee.php?id=" + employeeId;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

</script>

<?php include '../includes/footer.php'; ?>