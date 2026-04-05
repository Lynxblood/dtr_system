<?php
/**
 * Employee Attendance Portal
 */

session_start();
require_once '../src/config/config.php';
require_once '../src/Core/Database.php';
require_once '../src/Core/Auth.php';
require_once '../src/Components/Header.php';
require_once '../src/Components/Card.php';
require_once '../src/Components/Table.php';

use App\Core\Auth;
use App\Core\Database;
use App\Components\Header;
use App\Components\Card;
use App\Components\Table;

// Authentication and Authorization
Auth::requireLogin();
Auth::requirePasswordChange();
Auth::requireRole(ROLE_EMPLOYEE);

$db = Database::getInstance();
$en_no = Auth::getEnNo();
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Fetch employee name
$nameStmt = $db->query("SELECT name FROM employees WHERE en_no = ?", [$en_no]);
$employee = $nameStmt->fetch();
$displayName = $employee ? $employee['name'] : "Employee #$en_no";

// Fetch attendance logs for selected month
$logStmt = $db->query(
    "SELECT * FROM attendance_logs 
     WHERE en_no = ? AND DATE_FORMAT(record_datetime, '%Y-%m') = ?
     ORDER BY record_datetime DESC",
    [$en_no, $selectedMonth]
);
$logs = $logStmt->fetchAll();

// Statistics
$monthlyCount = count($logs);
$dutyOnCount = count(array_filter($logs, fn($log) => $log['in_out'] == 0));
$dutyOffCount = count(array_filter($logs, fn($log) => $log['in_out'] != 0));

// Prepare table data
$tableHeaders = ['Date', 'Time', 'Status', 'Terminal'];
$tableRows = [];

foreach ($logs as $log) {
    $dateTime = strtotime($log['record_datetime']);
    $date = date('M d, Y', $dateTime);
    $time = date('h:i A', $dateTime);
    
    $status = $log['in_out'] == 0
        ? '<span class="px-3 py-1 text-xs font-semibold text-white bg-green-500 rounded-full">Duty On</span>'
        : '<span class="px-3 py-1 text-xs font-semibold text-white bg-blue-500 rounded-full">Duty Off</span>';

    $tableRows[] = [
        $date,
        $time,
        ['html' => $status],
        htmlspecialchars($log['mode'])
    ];
}

Header::render('My Attendance - ' . htmlspecialchars($displayName));
?>

<!-- Welcome Section -->
<div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 text-white mb-8">
    <h1 class="text-3xl font-bold mb-2">Welcome, <?php echo htmlspecialchars($displayName); ?></h1>
    <p class="text-blue-100">View and download your attendance records</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <!-- Total Records -->
    <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Total Records</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-white mt-2">
                    <?php echo $monthlyCount; ?>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    for <?php echo date('F Y', strtotime($selectedMonth)); ?>
                </p>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H3a1 1 0 00-1 1v12a1 1 0 001 1h14a1 1 0 001-1V6a1 1 0 00-1-1h-3a1 1 0 000-2 2 2 0 00-2 2H4z" clip-rule="evenodd"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Duty On Count -->
    <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Duty On</p>
                <p class="text-4xl font-bold text-green-600 dark:text-green-400 mt-2">
                    <?php echo $dutyOnCount; ?>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">This month</p>
            </div>
            <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Duty Off Count -->
    <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Duty Off</p>
                <p class="text-4xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                    <?php echo $dutyOffCount; ?>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">This month</p>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 2.697m8.368 12.192a6 6 0 01-8.368-8.368m2.122 2.122a3 3 0 11-4.243-4.243m10.605 10.605a1 1 0 11-1.414-1.414M9.172 9.172a3 3 0 11-4.243-4.243m10.605 10.605a1 1 0 11-1.414-1.414" clip-rule="evenodd"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Month Selector -->
    <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-gray-800">
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-4">Select Month</p>
        <form method="GET" action="attendance.php" class="space-y-3">
            <input 
                type="month" 
                name="month" 
                value="<?php echo $selectedMonth; ?>"
                class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <button 
                type="submit"
                class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                Filter
            </button>
        </form>
    </div>
</div>

<!-- Download Section -->
<div class="mb-8">
    <a href="export.php?month=<?php echo htmlspecialchars($selectedMonth); ?>&en_no=<?php echo htmlspecialchars($en_no); ?>" 
       class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
        </svg>
        Download CSV Report
    </a>
</div>

<!-- Attendance Table -->
<?php Card::open('📋 Attendance Records', 'Your attendance logs for ' . date('F Y', strtotime($selectedMonth))); ?>
    <?php if (empty($tableRows)): ?>
        <div class="text-center py-8">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-gray-500 dark:text-gray-400">No attendance records found for this month</p>
        </div>
    <?php else: ?>
        <?php Table::render('myAttendanceTable', $tableHeaders, $tableRows, 0, 'desc'); ?>
    <?php endif; ?>
<?php Card::close(); ?>

<?php Header::close(); ?>
