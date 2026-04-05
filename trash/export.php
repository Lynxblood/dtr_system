<?php
// // export.php
// session_start();
// // Ensure user is logged in, has changed their password, and is Admin or HR
// if (!isset($_SESSION['user_id']) || isset($_SESSION['force_password_change']) || !in_array($_SESSION['role'], ['admin', 'hr'])) {
//     header("Location: login.php");
//     exit;
// }
// require_once 'db.php';

// // Check if the required parameters are provided
// if (isset($_GET['en_no']) && isset($_GET['month'])) {
//     $en_no = (int)$_GET['en_no'];
//     $month = $_GET['month']; // Expected format: YYYY-MM

//     // 1. Fetch Employee Details for the filename and header
//     $empStmt = $pdo->prepare("SELECT name FROM employees WHERE en_no = ?");
//     $empStmt->execute([$en_no]);
//     $employee = $empStmt->fetch();

//     if (!$employee) {
//         die("Employee not found.");
//     }

//     $empName = $employee['name'];
//     $safeName = preg_replace('/[^a-zA-Z0-9]/', '_', $empName);
    
//     // 2. Fetch the attendance logs for the specific month
//     $logStmt = $pdo->prepare("
//         SELECT record_datetime, in_out, mode 
//         FROM attendance_logs 
//         WHERE en_no = ? AND DATE_FORMAT(record_datetime, '%Y-%m') = ?
//         ORDER BY record_datetime ASC
//     ");
//     $logStmt->execute([$en_no, $month]);
//     $logs = $logStmt->fetchAll();

//     // 3. Set Headers to force CSV download
//     header('Content-Type: text/csv; charset=utf-8');
//     header('Content-Disposition: attachment; filename="Attendance_' . $safeName . '_' . $month . '.csv"');

//     // 4. Open the output stream
//     $output = fopen('php://output', 'w');

//     // Add a title row and empty row for spacing
//     fputcsv($output, ['Attendance Report', $empName, 'Month:', $month]);
//     fputcsv($output, []); // Blank line

//     // Add the column headers
//     fputcsv($output, ['Date', 'Time', 'Type (IN/OUT)', 'Mode']);

//     if (count($logs) > 0) {
//         foreach ($logs as $log) {
//             $date = date('Y-m-d', strtotime($log['record_datetime']));
//             $time = date('h:i:s A', strtotime($log['record_datetime']));
//             $type = ($log['in_out'] == 0) ? 'Duty On (IN)' : 'Duty Off (OUT)';
            
//             fputcsv($output, [$date, $time, $type, $log['mode']]);
//         }
//     } else {
//         fputcsv($output, ['No records found for this month.']);
//     }

//     // Close the stream
//     fclose($output);
//     exit;
// } else {
//     die("Invalid request parameters.");
// }

// export.php
session_start();
require_once 'db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized.");
}

if (isset($_GET['en_no']) && isset($_GET['month'])) {
    
    $month = $_GET['month'];
    $requested_en_no = (int)$_GET['en_no'];

    // SECURITY: If user is an employee, override the requested ID with their SESSION ID
    // This prevents "ID Swapping" via URL manipulation.
    if ($_SESSION['role'] === 'employee') {
        $en_no = $_SESSION['en_no'];
    } else {
        // Admin and HR can export whichever ID was requested
        $en_no = $requested_en_no;
    }

    // Fetch Employee Name
    $empStmt = $pdo->prepare("SELECT name FROM employees WHERE en_no = ?");
    $empStmt->execute([$en_no]);
    $employee = $empStmt->fetch();

    if (!$employee) { die("Record not found."); }

    // (Rest of the CSV generation logic from previous steps...)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Attendance_' . $en_no . '_' . $month . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Attendance Report', $employee['name'], 'ID:', $en_no, 'Month:', $month]);
    fputcsv($output, []);
    fputcsv($output, ['Date', 'Time', 'Type', 'Terminal Mode']);

    $logStmt = $pdo->prepare("
        SELECT record_datetime, in_out, mode 
        FROM attendance_logs 
        WHERE en_no = ? AND DATE_FORMAT(record_datetime, '%Y-%m') = ?
        ORDER BY record_datetime ASC
    ");
    $logStmt->execute([$en_no, $month]);
    
    while ($row = $logStmt->fetch()) {
        fputcsv($output, [
            date('Y-m-d', strtotime($row['record_datetime'])),
            date('h:i:s A', strtotime($row['record_datetime'])),
            ($row['in_out'] == 0 ? 'IN' : 'OUT'),
            $row['mode']
        ]);
    }
    fclose($output);
    exit;
}
?>