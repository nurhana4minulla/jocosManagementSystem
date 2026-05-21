<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php"); exit(); }
require_once '../includes/database.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $db = new Database();
    $conn = $db->getConnection();
    $emp_id = intval($_GET['id']);

    // SOFT DELETE
    $stmt = $conn->prepare("UPDATE employees SET is_deleted = 1, deleted_at = NOW() WHERE employee_id = ?");
    $stmt->bind_param("i", $emp_id);
    
    if ($stmt->execute()) {
        header("Location: manage_employees.php?delete_success=1");
    } else {
        header("Location: manage_employees.php?error=Failed to move employee to recycle bin.");
    }
    exit();
} else {
    header("Location: manage_employees.php");
    exit();
}