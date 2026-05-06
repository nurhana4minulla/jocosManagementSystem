<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../index.php"); exit(); }

require_once '../includes/database.php';

$db = new Database();
$conn = $db->getConnection();

$res = $conn->query("SELECT DATABASE()");
$db_name = $res->fetch_row()[0];

$tables = [];
$result = $conn->query("SHOW TABLES");
while($row = $result->fetch_row()) {
    $tables[] = $row[0];
}


$sqlScript = "-- DTI Region IX HR System Backup\n";
$sqlScript .= "-- Generated: " . date('Y-m-d h:i A') . "\n\n";
$sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n"; 

foreach ($tables as $table) {
    $result = $conn->query("SHOW CREATE TABLE $table");
    $row = $result->fetch_row();
    $sqlScript .= "\n-- Structure for table `$table`\n";
    $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
    $sqlScript .= $row[1] . ";\n\n";

    $result = $conn->query("SELECT * FROM $table");
    $columnCount = $result->field_count;

    if ($result->num_rows > 0) {
        $sqlScript .= "-- Data for table `$table`\n";
        while ($row = $result->fetch_row()) {
            $sqlScript .= "INSERT INTO `$table` VALUES(";
            for ($j = 0; $j < $columnCount; $j++) {
                if (isset($row[$j])) {
                    $escaped = $conn->real_escape_string($row[$j]);
                    $sqlScript .= "'" . $escaped . "'";
                } else {
                    $sqlScript .= "NULL"; 
                }
                if ($j < ($columnCount - 1)) {
                    $sqlScript .= ",";
                }
            }
            $sqlScript .= ");\n";
        }
        $sqlScript .= "\n";
    }
}

$sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";

$backup_file_name = 'DTI_HR_Backup_' . date('Y-m-d_H-i-s') . '.sql';

header('Content-Type: application/x-sql');
header('Content-Transfer-Encoding: Binary');
header('Content-Disposition: attachment; filename="' . $backup_file_name . '"');
header('Cache-Control: private, max-age=0, must-revalidate');

echo $sqlScript;
exit();
?>