<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php"); exit(); }

require_once '../includes/database.php';
$db = new Database();
$conn = $db->getConnection();

if (isset($_GET['id'])) {
    $emp_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("UPDATE employees SET is_archived = 1 WHERE employee_id = ?");
    $stmt->bind_param("i", $emp_id);
    
    if ($stmt->execute()) {
        header("Location: manage_employees.php?archive_success=1");
    } else {
        header("Location: manage_employees.php?error=1");
    }
    $stmt->close();
}
$conn->close();
?>