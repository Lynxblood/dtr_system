<?php
// my_attendance.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || isset($_SESSION['force_password_change']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit;
}

$en_no = $_SESSION['en_no'];

// 1. Get the selected month from URL, default to current month
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// 2. Fetch Employee Display Name
$nameStmt = $pdo->prepare("SELECT name FROM employees WHERE en_no = ?");
$nameStmt->execute([$en_no]);
$employee = $nameStmt->fetch();
$displayName = $employee ? $employee['name'] : "Employee #$en_no";

// 3. Fetch Attendance Logs for the SELECTED month
$logStmt = $pdo->prepare("
    SELECT * FROM attendance_logs 
    WHERE en_no = ? AND DATE_FORMAT(record_datetime, '%Y-%m') = ?
    ORDER BY record_datetime DESC
");
$logStmt->execute([$en_no, $selectedMonth]);
$logs = $logStmt->fetchAll();

// 4. Calculate Stats for the SELECTED month
$monthlyCount = count($logs);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
</head>
<body class="bg-gray-50 dark:bg-gray-900 antialiased">

    <nav class="bg-white border-b border-gray-200 px-4 py-2.5 dark:bg-gray-800 dark:border-gray-700 fixed left-0 right-0 top-0 z-50">
        <div class="flex flex-wrap justify-between items-center max-w-7xl mx-auto">
            <span class="self-center text-xl font-bold dark:text-white">Portal: <?= htmlspecialchars($displayName) ?></span>
            <a href="logout.php" class="text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-4 py-2">Logout</a>
        </div>
    </nav>

    <main class="p-4 h-auto pt-20 max-w-7xl mx-auto">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            
            <div class="md:col-span-2 p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                <h5 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">View History & Download</h5>
                
                <form method="GET" action="my_attendance.php" class="flex flex-col sm:flex-row items-center gap-3">
                    <div class="w-full">
                        <label class="block mb-2 text-xs font-medium text-gray-500 uppercase">Select Month</label>
                        <input type="month" name="month" value="<?= $selectedMonth ?>" required 
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div class="flex gap-2 w-full sm:w-auto pt-6">
                        <button type="submit" class="w-full sm:w-auto text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5">
                            Filter
                        </button>
                        
                        <a href="export.php?month=<?= $selectedMonth ?>&en_no=<?= $en_no ?>" 
                           class="w-full sm:w-auto text-center text-white bg-green-700 hover:bg-green-800 font-medium rounded-lg text-sm px-5 py-2.5">
                            Export CSV
                        </a>
                    </div>
                </form>
            </div>

            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                <h5 class="text-sm font-medium text-gray-500 dark:text-gray-400">Records for <?= date('F Y', strtotime($selectedMonth)) ?></h5>
                <p class="text-4xl font-extrabold text-gray-900 dark:text-white mt-2"><?= $monthlyCount ?></p>
                <p class="text-xs text-gray-500 mt-1">Total logs found</p>
            </div>
        </div>

        <div class="p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
            <table id="myAttendanceTable" class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Time</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Terminal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                            <?= date('M d, Y', strtotime($log['record_datetime'])) ?>
                        </td>
                        <td class="px-6 py-4" data-sort="<?= strtotime($log['record_datetime']) ?>">
                            <?= date('h:i A', strtotime($log['record_datetime'])) ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($log['in_out'] == 0): ?>
                                <span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Time In</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">Time Out</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-xs">Mode <?= htmlspecialchars($log['mode']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#myAttendanceTable').DataTable({
                "pageLength": 10,
                "order": [[1, "desc"]],
                "language": { "search": "Quick Search:" }
            });
        });
    </script>
</body>
</html>