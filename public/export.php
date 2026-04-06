<?php
/**
 * Export Attendance Records
 */

session_start();
require_once '../src/config/config.php';
require_once '../src/Core/Database.php';
require_once '../src/Core/Auth.php';
require_once '../src/Handlers/ExportHandler.php';

use App\Core\Auth;
use App\Handlers\ExportHandler;

// Verify authentication and authorization
Auth::requireLogin();
Auth::requirePasswordChange();

// Check if user is authorized to export
$user_role = Auth::getRole();
$user_en_no = Auth::getEnNo();
$requested_en_no = isset($_REQUEST['en_no']) ? (int)$_REQUEST['en_no'] : null;
$month = $_REQUEST['month'] ?? date('Y-m');

// Employees can only export their own data
if ($user_role === ROLE_EMPLOYEE && $user_en_no !== $requested_en_no) {
    header("HTTP/1.1 403 Forbidden");
    die('Access denied');
}

// Admin and HR can export any employee's data
if (!in_array($user_role, [ROLE_ADMIN, ROLE_HR, ROLE_EMPLOYEE])) {
    header("HTTP/1.1 403 Forbidden");
    die('Access denied');
}

$handler = new ExportHandler();
$handler->exportCSV($requested_en_no, $month);
