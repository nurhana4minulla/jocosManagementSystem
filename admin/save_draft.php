<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { 
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access.']); 
    exit(); 
}

require_once '../includes/database.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $conn = $db->getConnection();

    // name of draft
    $draft_name = "Draft - " . date('Y-m-d h:i A');
    if (!empty($_POST['last_name'])) {
        $draft_name = "Draft for " . htmlspecialchars($_POST['last_name']);
    }

    $form_data_json = json_encode($_POST);

    $query = "INSERT INTO pds_drafts (draft_name, form_data) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("ss", $draft_name, $form_data_json);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database execution failed.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Query preparation failed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
?>