<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { exit(); }

require_once '../includes/database.php';

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