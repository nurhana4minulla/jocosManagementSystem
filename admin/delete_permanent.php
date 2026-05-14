<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php"); exit(); }
require_once '../includes/database.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $db = new Database();
    $conn = $db->getConnection();
    $emp_id = intval($_GET['id']);

    try {
        $conn->begin_transaction();

        $related_tables = [
            'employee_family', 
            'employee_identifications', 
            'employee_education', 
            'employee_eligibility', 
            'employment_history', 
            'employee_training', 
            'employee_other_details'
        ];

        foreach ($related_tables as $table) {
            $stmt = $conn->prepare("DELETE FROM $table WHERE employee_id = ?");
            $stmt->bind_param("i", $emp_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmtMain = $conn->prepare("DELETE FROM employees WHERE employee_id = ?");
        $stmtMain->bind_param("i", $emp_id);
        $stmtMain->execute();
        $stmtMain->close();

        $conn->commit();

        header("Location: recycle_bin.php?delete_success=1");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: recycle_bin.php?error=Failed to permanently delete record.");
        exit();
    }

} else {
    header("Location: recycle_bin.php");
    exit();
}
?>