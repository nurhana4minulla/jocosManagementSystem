<?php
session_start();
require_once 'includes/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $db = new Database();
    $conn = $db->getConnection();

    // inputs
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT admin_id, full_name, password_hash FROM admin_users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        $db_hash = $row['password_hash'];
        $is_valid = false;

        if (strlen($db_hash) === 32 && md5($password) === $db_hash) {
            $is_valid = true; // It's an old MD5 password
        } else if (password_verify($password, $db_hash)) {
            $is_valid = true; // It's a new, highly secure password
        }
        
        if ($is_valid) {
            
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['admin_name'] = $row['full_name'];
            
            header("Location: admin/dashboard.php");
            exit();
            
        } else {
            header("Location: index.php?error=1");
            exit();
        }
    } else {
        header("Location: index.php?error=1");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: index.php");
    exit();
}
?>