<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php"); exit(); }

require_once '../includes/database.php';
include '../includes/header.php'; 

$db = new Database();
$conn = $db->getConnection();

$query = "
    SELECT 
        e.employee_id, e.office_id, e.first_name, e.last_name, e.photo_path,
        h.department_program
    FROM employees e
    LEFT JOIN employment_history h ON e.employee_id = h.employee_id
    WHERE e.is_deleted = 1 
    GROUP BY e.employee_id 
    ORDER BY e.last_name ASC
";
$result = $conn->query($query);
?>

<div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6 d-flex align-items-center">
            <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 55px; height: 55px;">
                <i class="bi bi-trash3-fill text-danger fs-3"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-danger">Recycle Bin</h4>
                <p class="text-muted small mb-0">Restore deleted personnel or remove them permanently.</p>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <a href="manage_employees.php" class="btn btn-light border shadow-sm fw-bold">
                <i class="bi bi-arrow-left"></i> Back to Master List
            </a>
        </div>
    </div>

    <?php if(isset($_GET['restore_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Employee successfully restored to the Master List.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="bg-light text-muted small text-uppercase border-bottom">
                        <tr>
                            <th class="ps-4 py-3">Profile</th>
                            <th class="py-3">Office ID</th>
                            <th class="py-3">Full Name</th>
                            <th class="py-3">Department</th>
                            <th class="text-end pe-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <?php if (!empty($row['photo_path']) && file_exists('../' . $row['photo_path'])): ?>
                                            <img src="../<?php echo htmlspecialchars($row['photo_path']); ?>" alt="Photo" class="rounded-circle object-fit-cover border shadow-sm" style="width: 40px; height: 40px;">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 40px; height: 40px;">
                                                <?php echo strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 fw-bold text-secondary"><?php echo htmlspecialchars($row['office_id']); ?></td>
                                    <td class="py-3 fw-bold text-dark"><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?></td>
                                    <td class="py-3 text-muted"><?php echo !empty($row['department_program']) ? htmlspecialchars($row['department_program']) : 'N/A'; ?></td>
                                    <td class="text-end pe-4 py-3">
                                        <div class="btn-group shadow-sm">
                                            <a href="restore_employee.php?id=<?php echo $row['employee_id']; ?>" class="btn btn-sm btn-light border text-success fw-bold" title="Restore to Master List"><i class="bi bi-arrow-counterclockwise"></i> Restore</a>
                                            <button type="button" class="btn btn-sm btn-danger fw-bold" onclick="alert('For security, permanent deletion is disabled in this demo.')" title="Permanently Delete"><i class="bi bi-x-circle"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <div class="mb-2 fs-1 text-light"><i class="bi bi-trash3"></i></div>
                                            <p class="mb-0 fw-bold fs-5" style="color: #0F172A;">Recycle Bin is empty</p>
                                            <p class="small mb-0">Deleted employee profiles will be temporarily stored here.</p>
                                            <a href="manage_employees.php" class="btn btn-sm btn-outline-secondary mt-3 fw-bold">Return to Master List</a>
                                        </td>
                                    </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>