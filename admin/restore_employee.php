<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php"); exit(); }
require_once '../includes/database.php';

if (!isset($_GET['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
    die("Invalid CSRF token.");
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $db = new Database();
    $conn = $db->getConnection();
    $emp_id = intval($_GET['id']);

    $stmt = $conn->prepare("UPDATE employees SET is_deleted = 0 WHERE employee_id = ?");
    $stmt->bind_param("i", $emp_id);
    
    if ($stmt->execute()) {
        header("Location: recycle_bin.php?restore_success=1");
    } else {
        header("Location: recycle_bin.php?error=Failed to restore employee.");
    }
    exit();
} else {
    header("Location: recycle_bin.php");
    exit();
}