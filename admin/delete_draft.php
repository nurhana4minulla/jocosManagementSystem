<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { exit(); }

require_once '../includes/database.php';

if (!isset($_GET['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
    die("Invalid CSRF token.");
}

if (isset($_GET['id'])) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("DELETE FROM pds_drafts WHERE draft_id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
}

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header("Location: manage_employees.php");
}
exit();
?>