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
        h.department_program, h.end_date
    FROM employees e
    LEFT JOIN (
        SELECT h1.* FROM employment_history h1
        INNER JOIN (SELECT employee_id, MAX(start_date) as max_start FROM employment_history GROUP BY employee_id) h2 
        ON h1.employee_id = h2.employee_id AND h1.start_date = h2.max_start
    ) h ON e.employee_id = h.employee_id
    WHERE e.is_deleted = 0 AND e.is_archived = 1 
    GROUP BY e.employee_id 
    ORDER BY e.last_name ASC
";
$result = $conn->query($query);
?>

<div class="container-fluid py-4">
    <div class="row mb-4 align-items-center page-transition">
        <div class="col-md-6 d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); backdrop-filter: blur(5px);">
                <i class="bi bi-archive-fill text-warning fs-4"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-1 text-warning" style="letter-spacing: -0.5px; color: #d97706 !important;">Archived Records</h4>
                <p class="text-muted small mb-0 fw-medium">Safely stored personnel profiles. Hidden from the Master List.</p>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <a href="manage_employees.php" class="btn btn-sm btn-gradient-dark shadow-sm fw-bold px-3 py-2" style="border-radius: 8px;">
                <i class="bi bi-arrow-left me-1"></i> Back to Master List
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 page-transition" style="animation-delay: 0.1s; border-radius: 12px; overflow: hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="bg-light text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px;">
                        <tr>
                            <th class="ps-4 py-3 border-bottom-0">Profile</th>
                            <th class="py-3 border-bottom-0">Office ID</th>
                            <th class="py-3 border-bottom-0">Full Name</th>
                            <th class="py-3 border-bottom-0">Last Department</th>
                            <th class="text-end pe-4 py-3 border-bottom-0">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <?php if (!empty($row['photo_path']) && file_exists('../' . $row['photo_path'])): ?>
                                            <img src="../<?php echo htmlspecialchars($row['photo_path']); ?>" alt="Photo" class="rounded-circle object-fit-cover shadow-sm" style="width: 42px; height: 42px; border: 2px solid white;">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px; border: 1px solid #e2e8f0;">
                                                <?php echo strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 fw-bold text-secondary"><?php echo htmlspecialchars($row['office_id']); ?></td>
                                    <td class="py-3 fw-bold" style="color: #0F172A;"><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?></td>
                                    <td class="py-3 text-muted">
                                    <div class="text-truncate" style="max-width: 180px; font-size: 0.8rem;" title="<?php echo !empty($row['department_program']) ? htmlspecialchars($row['department_program']) : 'N/A'; ?>">
                                        <?php echo !empty($row['department_program']) ? htmlspecialchars($row['department_program']) : '<span class="text-muted fw-normal small">N/A</span>'; ?>
                                    </div>
                                    </td>
                                    <td class="text-end pe-4 py-3">
                                        <div class="btn-group shadow-sm" style="border-radius: 6px;">
                                            <a href="view_employee.php?id=<?php echo $row['employee_id']; ?>" class="btn btn-sm btn-light border text-primary fw-bold px-3" title="View Profile">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                            <a href="unarchive_employee.php?id=<?php echo $row['employee_id']; ?>" class="btn btn-sm btn-light border text-success fw-bold px-3" title="Restore to Master List">
                                                <i class="bi bi-arrow-90deg-left me-1"></i> Unarchive
                                            </a>
                                            <button type="button" class="btn btn-sm btn-light border text-danger fw-bold px-3" onclick="confirmDelete(<?php echo $row['employee_id']; ?>)" title="Move to Recycle Bin">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <div class="mb-3 fs-1 text-light"><i class="bi bi-archive" style="opacity: 0.5;"></i></div>
                                    <p class="mb-1 fw-bold fs-5" style="color: #0F172A;">Archive is empty</p>
                                    <p class="small mb-0">Inactive personnel can be safely stored here for future reference.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-body p-4 text-center">
                <div class="mb-3 d-flex justify-content-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2);">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-4"></i>
                    </div>
                </div>
                <h6 class="fw-bold mb-2" style="color: #0F172A; font-size: 1.1rem;">Move to Recycle Bin?</h6>
                <p class="text-muted small mb-4">This profile will be moved from the Archives to the Recycle Bin.</p>
                
                <div class="d-flex gap-2 w-100">
                    <button type="button" class="btn btn-light fw-bold flex-fill" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-glass-danger fw-bold flex-fill" style="border-radius: 8px;">Yes, Move</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const deleteModal = document.getElementById('deleteModal');
        if (deleteModal) { document.body.appendChild(deleteModal); }

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('restore_success')) {
            showToast('Profile restored to the Master List successfully.', 'success');
            window.history.replaceState(null, null, window.location.pathname);
        }
    });

    function confirmDelete(employeeId) {
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.href = "delete_employee.php?id=" + employeeId;
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
</script>

<?php include '../includes/footer.php'; ?>