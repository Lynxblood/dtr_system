<?php
/**
 * Admin Dashboard
 */

session_start();
require_once '../src/config/config.php';
require_once '../src/Core/Database.php';
require_once '../src/Core/Auth.php';
require_once '../src/Components/Header.php';
require_once '../src/Components/Card.php';
require_once '../src/Components/AlertBox.php';
require_once '../src/Components/SelectInput.php';
require_once '../src/Components/Table.php';

use App\Core\Auth;
use App\Core\Database;
use App\Components\Header;
use App\Components\Card;
use App\Components\AlertBox;
use App\Components\SelectInput;
use App\Components\Table;

// Authentication and Authorization
Auth::requireLogin();
Auth::requirePasswordChange();
Auth::requireRole([ROLE_ADMIN, ROLE_HR]);

$db = Database::getInstance();

// Fetch latest attendance records (1000 records); table remains manageable
$logsStmt = $db->query("
    SELECT a.*, e.name 
    FROM attendance_logs a 
    JOIN employees e ON a.en_no = e.en_no 
    ORDER BY a.record_datetime DESC 
    LIMIT 1000
");
$logs = $logsStmt->fetchAll();

// Get true total attendance record count (for dashboard stat card)
$totalRecordsStmt = $db->query("SELECT COUNT(*) AS total FROM attendance_logs");
$totalRecords = $totalRecordsStmt->fetchColumn();

// Fetch all employees for dropdown
$empStmt = $db->query("SELECT en_no, name FROM employees ORDER BY name ASC");
$employees = $empStmt->fetchAll();

// Format employees for select input
$employeeOptions = [];
foreach ($employees as $emp) {
    $employeeOptions[$emp['en_no']] = $emp['name'] . ' (ID: ' . $emp['en_no'] . ')';
}

// Prepare table data
$tableHeaders = ['EnNo', 'Name', 'Date & Time', 'Type (IN/OUT)', 'Mode'];
$tableRows = [];

foreach ($logs as $log) {
    $datetime = date('M d, Y h:i A', strtotime($log['record_datetime']));
    
    $badge = $log['in_out'] == 0
        ? '<span class="px-3 py-1 text-xs font-semibold text-white bg-green-500 rounded-full">Duty On</span>'
        : '<span class="px-3 py-1 text-xs font-semibold text-white bg-blue-500 rounded-full">Duty Off</span>';

    $tableRows[] = [
        htmlspecialchars($log['en_no']),
        htmlspecialchars($log['name']),
        $datetime,
        ['html' => $badge],
        htmlspecialchars($log['mode'])
    ];
}

Header::render('Admin Dashboard');
?>

<!-- Dashboard Content -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Stats Card 1 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Total Records</p>
                <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white"><?php echo (int)$totalRecords; ?></p>
            </div>
            <div class="text-blue-600">
                <!-- <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 110-12 6 6 0 010 12z"></path>
                </svg> -->
                <svg class="w-8 h-8 text-primary-800 " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 3v4a1 1 0 0 1-1 1H5m4 8h6m-6-4h6m4-8v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7.914a1 1 0 0 1 .293-.707l3.914-3.914A1 1 0 0 1 9.914 3H18a1 1 0 0 1 1 1Z"/>
                </svg>

            </div>
        </div>
    </div>

    <!-- Stats Card 2 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Total Employees</p>
                <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white"><?php echo count($employees); ?></p>
            </div>
            <div class="text-green-600">
                <!-- <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM9 12a6 6 0 11-12 0 6 6 0 0112 0z"></path>
                </svg> -->
                <svg class="w-8 h-8 text-green-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4.5 17H4a1 1 0 0 1-1-1 3 3 0 0 1 3-3h1m0-3.05A2.5 2.5 0 1 1 9 5.5M19.5 17h.5a1 1 0 0 0 1-1 3 3 0 0 0-3-3h-1m0-3.05a2.5 2.5 0 1 0-2-4.45m.5 13.5h-7a1 1 0 0 1-1-1 3 3 0 0 1 3-3h3a3 3 0 0 1 3 3 1 1 0 0 1-1 1Zm-1-9.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z"/>
                </svg>

            </div>
        </div>
    </div>

    <!-- Stats Card 3 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">System Status</p>
                <p class="text-2xl font-bold mt-2 text-gray-900 dark:text-white">
                    <span class="inline-flex items-center">
                        <span class="w-3 h-3 bg-green-400 rounded-full mr-2"></span>
                        Online
                    </span>
                </p>
            </div>
            <div class="text-purple-600">
                <!-- <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zm1.622-5.89a11.001 11.001 0 0112.331 10.89 20 20 0 01-40 0c1.461-4.435 8.402-7.747 15.809-7.878l2.86.013z" clip-rule="evenodd"></path>
                </svg> -->
                <svg class="w-8 h-8 text-purple-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 6c0 1.657-3.134 3-7 3S5 7.657 5 6m14 0c0-1.657-3.134-3-7-3S5 4.343 5 6m14 0v6M5 6v6m0 0c0 1.657 3.134 3 7 3s7-1.343 7-3M5 12v6c0 1.657 3.134 3 7 3s7-1.343 7-3v-6"/>
                </svg>

            </div>
        </div>
    </div>
</div>

<!-- Import and Export Section -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Import Card -->
    <?php Card::open('📥 Import Records', 'Upload attendance data from biometric terminal'); ?>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Upload a tab-separated .TXT file generated by the biometric terminal system.
        </p>
        
        <form id="uploadForm" class="space-y-4">
            <div>
                <label for="file" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Select File
                </label>
                <input 
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-700 dark:file:text-blue-400" 
                    id="file" 
                    name="file" 
                    type="file" 
                    accept=".txt"
                    required>
            </div>
            <button 
                type="submit" 
                class="w-full px-5 py-2.5 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition">
                Import
            </button>
        </form>
        
        <?php AlertBox::container('alertContainer'); ?>
    <?php Card::close(); ?>

    <!-- Export Card -->
    <?php Card::open('📥 Export Monthly Report', 'Download attendance records for a specific employee'); ?>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Generate a CSV report for any employee and selected month.
        </p>
        
        <form action="export.php" method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="en_no" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Employee</label>
                    <select 
                        name="en_no" 
                        id="en_no"
                        required 
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="" disabled selected>-- Select Employee --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo htmlspecialchars($emp['en_no']); ?>">
                                <?php echo htmlspecialchars($emp['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="month" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Month</label>
                    <input 
                        type="month" 
                        name="month" 
                        id="month"
                        value="<?php echo date('Y-m'); ?>"
                        required 
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
            <button 
                type="submit" 
                class="w-full px-5 py-2.5 text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800 transition">
                Download CSV
            </button>
        </form>
    <?php Card::close(); ?>
</div>

<!-- Attendance Table -->
<?php Card::open('📋 System Logs', 'Latest attendance records'); ?>
    <?php Table::render('attendanceTable', $tableHeaders, $tableRows, 2, 'desc'); ?>
<?php Card::close(); ?>

<?php Header::close(); ?>
