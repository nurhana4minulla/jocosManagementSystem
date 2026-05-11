<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php"); exit(); }

require_once '../includes/database.php';
include '../includes/header.php'; 

$db = new Database();
$conn = $db->getConnection();


$totalRes = $conn->query("SELECT COUNT(*) as count FROM employees WHERE is_deleted = 0");
$total_personnel = $totalRes->fetch_assoc()['count'];

// JO/COS Breakdown 
$typeRes = $conn->query("
    SELECT h.employment_type, COUNT(DISTINCT e.employee_id) as count 
    FROM employees e 
    INNER JOIN (
        SELECT h1.* FROM employment_history h1
        INNER JOIN (SELECT employee_id, MAX(start_date) as max_start FROM employment_history GROUP BY employee_id) h2 
        ON h1.employee_id = h2.employee_id AND h1.start_date = h2.max_start
    ) h ON e.employee_id = h.employee_id 
    WHERE e.is_deleted = 0 
      AND h.employment_type IS NOT NULL
      AND h.start_date <= CURDATE() 
      AND (h.end_date IS NULL OR h.end_date = '' OR h.end_date = '0000-00-00' OR h.end_date >= CURDATE())
    GROUP BY h.employment_type
");
$jo_count = 0; $cos_count = 0;
while($row = $typeRes->fetch_assoc()) {
    if($row['employment_type'] == 'JO') $jo_count = $row['count'];
    if($row['employment_type'] == 'COS') $cos_count = $row['count'];
}

// Gender
$genderRes = $conn->query("SELECT sex, COUNT(*) as count FROM employees WHERE is_deleted = 0 GROUP BY sex");
$male_count = 0; $female_count = 0;
while($row = $genderRes->fetch_assoc()) {
    if($row['sex'] == 'Male') $male_count = $row['count'];
    if($row['sex'] == 'Female') $female_count = $row['count'];
}

// Age 
$ageRes = $conn->query("SELECT dob FROM employees WHERE is_deleted = 0 AND dob IS NOT NULL");
$ages = ['18-25' => 0, '26-35' => 0, '36-45' => 0, '46-55' => 0, '56+' => 0];
$now = new DateTime();
while($row = $ageRes->fetch_assoc()) {
    $dob = new DateTime($row['dob']);
    $age = $now->diff($dob)->y;
    if($age >= 18 && $age <= 25) $ages['18-25']++;
    elseif($age >= 26 && $age <= 35) $ages['26-35']++;
    elseif($age >= 36 && $age <= 45) $ages['36-45']++;
    elseif($age >= 46 && $age <= 55) $ages['46-55']++;
    elseif($age >= 56) $ages['56+']++;
}

// highest level of education 
$eduRes = $conn->query("
    SELECT e.employee_id,
           MAX(CASE
               WHEN ed.level = 'Graduate Studies' AND ed.is_graduated = 1 THEN 5
               WHEN ed.level = 'College' AND ed.is_graduated = 1 THEN 4
               WHEN ed.level = 'College' AND ed.is_graduated = 0 THEN 3
               WHEN ed.level = 'Vocational/Trade Course' THEN 2
               WHEN ed.level IN ('Senior High School', 'Secondary', 'Elementary') THEN 1
               ELSE 0 END) as highest_level
    FROM employees e
    LEFT JOIN employee_education ed ON e.employee_id = ed.employee_id
    WHERE e.is_deleted = 0
    GROUP BY e.employee_id
");

$edu_counts = [
    "Master's / Graduate Degree" => 0, 
    "Bachelor's / College Graduate" => 0, 
    "College Undergrad" => 0, 
    "Vocational Degree" => 0, 
    "Basic Ed (K-12)" => 0
];

while($row = $eduRes->fetch_assoc()) {
    switch($row['highest_level']) {
        case 5: $edu_counts["Master's / Graduate Degree"]++; break;
        case 4: $edu_counts["Bachelor's / College Graduate"]++; break;
        case 3: $edu_counts["College Undergrad"]++; break;
        case 2: $edu_counts["Vocational Degree"]++; break;
        case 1: $edu_counts["Basic Ed (K-12)"]++; break;
    }
}

// JO/COS per Office 
$officeTypeRes = $conn->query("
    SELECT h.office_assignment, h.employment_type, COUNT(DISTINCT e.employee_id) as count 
    FROM employees e 
    INNER JOIN (
        SELECT h1.* FROM employment_history h1
        INNER JOIN (SELECT employee_id, MAX(start_date) as max_start FROM employment_history GROUP BY employee_id) h2 
        ON h1.employee_id = h2.employee_id AND h1.start_date = h2.max_start
    ) h ON e.employee_id = h.employee_id 
    WHERE e.is_deleted = 0 
      AND h.office_assignment IS NOT NULL 
      AND h.employment_type IS NOT NULL
      AND h.start_date <= CURDATE() 
      AND (h.end_date IS NULL OR h.end_date >= CURDATE())
    GROUP BY h.office_assignment, h.employment_type
    ORDER BY h.office_assignment ASC
");

$offices_list = [];
$office_jo_data = [];
$office_cos_data = [];

$temp_office_data = [];
while($row = $officeTypeRes->fetch_assoc()) {
    $off = $row['office_assignment'];
    $type = $row['employment_type'];
    if(!isset($temp_office_data[$off])) {
        $temp_office_data[$off] = ['JO' => 0, 'COS' => 0];
    }
    $temp_office_data[$off][$type] = $row['count'];
}

foreach($temp_office_data as $office_name => $counts) {
    $offices_list[] = $office_name;
    $office_jo_data[] = $counts['JO'];
    $office_cos_data[] = $counts['COS'];
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid py-4">
    
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold mb-1" style="color: #0F172A;">Analytics Dashboard</h4>
            <!-- <p class="text-muted small mb-0">Welcome to the DTI-IX Personnel Management Dashboard.</p> -->
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-sm-6">
            <div class="card shadow-sm border-0 h-100 page-transition card-gradient-primary" style="animation-delay: 0.1s;">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded stat-icon-wrapper d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-people-fill text-white fs-4"></i>
                    </div>
                    <div>
                        <p class="text-white-50 small fw-bold mb-0 text-uppercase" style="letter-spacing: 0.5px;">Total Personnel</p>
                        <h3 class="fw-bold mb-0 text-white"><?php echo $total_personnel; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-sm-6">
            <div class="card shadow-sm border-0 h-100 page-transition card-gradient-success" style="animation-delay: 0.2s;">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded stat-icon-wrapper d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-person-badge-fill text-white fs-4"></i>
                    </div>
                    <div>
                        <p class="text-white-50 small fw-bold mb-0 text-uppercase" style="letter-spacing: 0.5px;">Active JO</p>
                        <h3 class="fw-bold mb-0 text-white"><?php echo $jo_count; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-sm-12">
            <div class="card shadow-sm border-0 h-100 page-transition card-gradient-warning" style="animation-delay: 0.3s;">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded stat-icon-wrapper d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-file-earmark-person-fill text-white fs-4"></i>
                    </div>
                    <div>
                        <p class="text-white-50 small fw-bold mb-0 text-uppercase" style="letter-spacing: 0.5px;">Active COS</p>
                        <h3 class="fw-bold mb-0 text-white"><?php echo $cos_count; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8 page-transition" style="animation-delay: 0.5s;">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0 text-secondary">JO / COS per Office of Assignment</h6>
                </div>
                <div class="card-body pb-4">
                    <canvas id="officeChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 page-transition" style="animation-delay: 0.6s;">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0 text-secondary">Gender Breakdown</h6>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center pb-4">
                    <div style="width: 80%;">
                        <canvas id="genderChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6 page-transition" style="animation-delay: 0.7s;">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0 text-secondary">Highest Educational Attainment</h6>
                </div>
                <div class="card-body pb-4 d-flex justify-content-center align-items-center">
                    <div style="width: 75%;">
                        <canvas id="educationChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 page-transition" style="animation-delay: 0.8s;">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0 text-secondary">Age Demographics</h6>
                </div>
                <div class="card-body pb-4">
                    <canvas id="ageChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    Chart.defaults.font.family = "'Segoe UI', system-ui, -apple-system, sans-serif";
    Chart.defaults.color = '#64748b';

    // JO/COS PER OFFICE 
    const ctxOffice = document.getElementById('officeChart').getContext('2d');
    new Chart(ctxOffice, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($offices_list); ?>,
            datasets: [
                {
                    label: 'Job Order (JO)',
                    data: <?php echo json_encode($office_jo_data); ?>,
                    backgroundColor: '#10b981', 
                    borderRadius: {topLeft: 0, topRight: 0, bottomLeft: 4, bottomRight: 4}
                },
                {
                    label: 'Contract of Service (COS)',
                    data: <?php echo json_encode($office_cos_data); ?>,
                    backgroundColor: '#f59e0b',
                    borderRadius: {topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0}
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { 
                legend: { position: 'top', labels: { usePointStyle: true, padding: 20 } } 
            },
            scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, beginAtZero: true, grid: { borderDash: [4, 4] } }
            }
        }
    });

    // EDUCATIONAL ATTAINMENT
    const ctxEdu = document.getElementById('educationChart').getContext('2d');
    new Chart(ctxEdu, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($edu_counts)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($edu_counts)); ?>,
                backgroundColor: ['#8b5cf6', '#3b82f6', '#10b981', '#f59e0b', '#f43f5e'], 
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: { position: 'right', labels: { usePointStyle: true, padding: 15 } }
            }
        }
    });

    // GENDER 
    const ctxGender = document.getElementById('genderChart').getContext('2d');
    new Chart(ctxGender, {
        type: 'doughnut',
        data: {
            labels: ['Male', 'Female'],
            datasets: [{
                data: [<?php echo $male_count; ?>, <?php echo $female_count; ?>],
                backgroundColor: ['#3b82f6', '#ec4899'], 
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } }
            }
        }
    });

    // AGE DEMOGRAPHICS 
    const ctxAge = document.getElementById('ageChart').getContext('2d');
    new Chart(ctxAge, {
        type: 'bar',
        data: {
            labels: ['18-25', '26-35', '36-45', '46-55', '56+'],
            datasets: [{
                label: 'Number of Personnel',
                data: [
                    <?php echo $ages['18-25']; ?>, 
                    <?php echo $ages['26-35']; ?>, 
                    <?php echo $ages['36-45']; ?>, 
                    <?php echo $ages['46-55']; ?>, 
                    <?php echo $ages['56+']; ?>
                ],
                backgroundColor: '#38bdf8', 
                borderRadius: 4,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [4, 4] } },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>