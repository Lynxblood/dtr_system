<?php
// import.php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// Only Admin and HR can import
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'hr'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error.']);
        exit;
    }

    $lines = file($_FILES['file']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    // Hash the default password once to save CPU cycles during the loop
    $defaultPassword = password_hash('ilove@BASC1', PASSWORD_BCRYPT);
    $insertedRecords = 0;
    $newEmployees = 0;

    $pdo->beginTransaction();

    try {
        $empStmt = $pdo->prepare("INSERT IGNORE INTO employees (en_no, name) VALUES (?, ?)");
        $userStmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, role, en_no, requires_password_change) VALUES (?, ?, 'employee', ?, 1)");
        $logStmt = $pdo->prepare("INSERT IGNORE INTO attendance_logs (tm_no, en_no, in_out, mode, record_datetime) VALUES (?, ?, ?, ?, ?)");

        foreach ($lines as $line) {
            $cols = explode("\t", $line);
            
            if (count($cols) >= 7 && is_numeric(trim($cols[0]))) {
                $tm_no = (int)trim($cols[1]);
                $en_no = (int)trim($cols[2]);
                $name = trim($cols[3]);
                $in_out = (int)trim($cols[4]);
                $mode = (int)trim($cols[5]);
                $datetime = date('Y-m-d H:i:s', strtotime(trim($cols[6])));

                // 1. Insert Employee
                $empStmt->execute([$en_no, $name]);
                
                // 2. If a new employee was added, create their user account
                if ($empStmt->rowCount() > 0) {
                    // Username is their en_no
                    $userStmt->execute([(string)$en_no, $defaultPassword, $en_no]);
                    $newEmployees++;
                }

                // 3. Insert Log
                $logStmt->execute([$tm_no, $en_no, $in_out, $mode, $datetime]);
                if ($logStmt->rowCount() > 0) {
                    $insertedRecords++;
                }
            }
        }
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => "Import complete. Added $insertedRecords logs and created accounts for $newEmployees new employees."]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>