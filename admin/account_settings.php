<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php"); exit(); }

require_once '../includes/database.php';
$db = new Database();
$conn = $db->getConnection();

$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 1;

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // UPDATE PROFILE INFO
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        
        $stmt = $conn->prepare("UPDATE admin_users SET full_name = ?, username = ? WHERE admin_id = ?");
        $stmt->bind_param("ssi", $full_name, $username, $admin_id);
        
        if ($stmt->execute()) {
            $success_msg = "Profile information updated successfully!";
        } else {
            $error_msg = "Error updating profile. Username might already be taken.";
        }
    }
    
    // CHANGE PASSWORD
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $stmt = $conn->prepare("SELECT password_hash FROM admin_users WHERE admin_id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $admin = $res->fetch_assoc();
        
        $db_hash = $admin['password_hash'];
        $is_valid_current = false;
        
        if (strlen($db_hash) === 32 && md5($current_password) === $db_hash) {
            $is_valid_current = true;
        } else if (password_verify($current_password, $db_hash)) {
            $is_valid_current = true;
        }
        
        if ($new_password !== $confirm_password) {
            $error_msg = "New passwords do not match!";
        } else if (!$is_valid_current) {
            $error_msg = "Incorrect current password!";
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE admin_users SET password_hash = ? WHERE admin_id = ?");
            $update_stmt->bind_param("si", $new_hash, $admin_id);
            
            if ($update_stmt->execute()) {
                $success_msg = "Password changed successfully!";
            } else {
                $error_msg = "Failed to update password. Please try again.";
            }
        }
    }
}

$stmt = $conn->prepare("SELECT username, full_name, created_at FROM admin_users WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_data = $stmt->get_result()->fetch_assoc();

include '../includes/header.php'; 
?>

<div class="container-fluid py-4 page-transition">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="fw-bold mb-0" style="color: #0F172A;">Account Settings</h4>
            <p class="text-muted small mb-0">Manage your admin profile and security credentials</p>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert" style="background-color: #d1e7dd; color: #0f5132;">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-person-badge text-primary me-2"></i> Profile Information</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-secondary"><i class="bi bi-person text-muted"></i></span>
                                <input type="text" name="full_name" class="form-control border-secondary" value="<?php echo htmlspecialchars($admin_data['full_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-secondary"><i class="bi bi-at text-muted"></i></span>
                                <input type="text" name="username" class="form-control border-secondary" value="<?php echo htmlspecialchars($admin_data['username'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="text-muted small mb-4 pb-3 border-bottom">
                            <i class="bi bi-clock-history me-1"></i> Account created: <?php echo date('F j, Y', strtotime($admin_data['created_at'])); ?>
                        </div>

                        <button type="submit" name="update_profile" class="btn text-white fw-bold w-100 py-2 shadow-sm" style="background-color: #0F172A;">
                            Save Profile Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-shield-lock text-warning me-2"></i> Security & Password</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-secondary"><i class="bi bi-key text-muted"></i></span>
                                <input type="password" name="current_password" class="form-control border-secondary" placeholder="Enter your current password to verify identity" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">New Password</label>
                                <input type="password" name="new_password" class="form-control border-secondary" placeholder="New password" required minlength="6">
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <label class="form-label small fw-bold text-muted">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control border-secondary" placeholder="Type new password again" required minlength="6">
                            </div>
                        </div>

                        <div class="d-flex align-items-center mb-4 p-3 bg-light rounded border border-warning border-opacity-25">
                            <i class="bi bi-info-circle-fill text-warning fs-4 me-3"></i>
                            <p class="small text-muted mb-0">For your security, it is highly recommended to use a password containing at least one number and one special character.</p>
                        </div>

                        <button type="submit" name="change_password" class="btn btn-primary fw-bold px-4 py-2 shadow-sm w-100">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold" style="color: #0F172A;"><i class="bi bi-hdd-network-fill text-success me-2"></i> System Administration</h6>
                </div>
                <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                    <div class="mb-3 mb-md-0">
                        <h6 class="fw-bold mb-1">Database Backup</h6>
                        <p class="small text-muted mb-0">Download a complete offline copy of the database (.sql file) containing all employee profiles, system histories, and admin accounts.</p>
                    </div>
                    <div>
                        <a href="backup_database.php" class="btn btn-success fw-bold px-4 py-2 shadow-sm text-nowrap">
                            <i class="bi bi-download me-2"></i> Download Backup
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>