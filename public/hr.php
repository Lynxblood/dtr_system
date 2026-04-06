<?php
/**
 * HR Dashboard
 */

session_start();
require_once '../src/config/config.php';
require_once '../src/Core/Database.php';
require_once '../src/Core/Auth.php';
require_once '../src/Components/Header.php';
require_once '../src/Components/Card.php';
require_once '../src/Components/AlertBox.php';
require_once '../src/Handlers/SubmissionHandler.php';
require_once '../src/Handlers/NotificationHandler.php';

use App\Core\Auth;
use App\Core\Database;
use App\Components\Header;
use App\Components\Card;
use App\Components\AlertBox;
use App\Handlers\SubmissionHandler;
use App\Handlers\NotificationHandler;

// Authentication and Authorization
Auth::requireLogin();
Auth::requirePasswordChange();
Auth::requireRole([ROLE_HR]);

$submissionHandler = new SubmissionHandler();
$notificationHandler = new NotificationHandler();

// Get notifications for current HR user
$notifications = $notificationHandler->getNotifications($_SESSION['user_id']);
$unreadCount = $notificationHandler->getUnreadCount($_SESSION['user_id']);

// Handle review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'] ?? null;
    $submissionHandler->review($id, $status, $remarks);
    $alertType = 'success';
    $alertMessage = 'Submission reviewed successfully.';
}

// Get submissions
$submissions = $submissionHandler->getSubmissions();

$compareSubmission = null;
$compareAttendance = [];
$attendancePage = isset($_GET['attendance_page']) ? (int)$_GET['attendance_page'] : 1;
$attendancePerPage = 15;
$attendanceTotal = 0;
$attendancePages = 0;

if (isset($_GET['compare_id'])) {
    $compareId = (int)$_GET['compare_id'];
    $compareSubmission = $submissionHandler->getSubmission($compareId);
    if ($compareSubmission) {
        // Get total count first
        $db = Database::getInstance();
        $attendanceTotal = $db->fetchOne(
            "SELECT COUNT(*) as total FROM attendance_logs WHERE en_no = ? AND DATE_FORMAT(record_datetime, '%Y-%m') = ?",
            [$compareSubmission['en_no'], $compareSubmission['month']]
        )['total'];
        
        $attendancePages = ceil($attendanceTotal / $attendancePerPage);
        $offset = ($attendancePage - 1) * $attendancePerPage;
        
        // Get paginated attendance records
        $compareAttendance = $db->fetchAll(
            "SELECT record_datetime, in_out, mode FROM attendance_logs WHERE en_no = ? AND DATE_FORMAT(record_datetime, '%Y-%m') = ? ORDER BY record_datetime ASC LIMIT $attendancePerPage OFFSET $offset",
            [$compareSubmission['en_no'], $compareSubmission['month']]
        );
    }
}

Header::render('HR Dashboard');
?>

<script>
window.NOTIFICATIONS = <?php echo json_encode($notifications); ?>;
</script>

<!-- Dashboard Content -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Stats Card 1 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Pending Reviews</p>
                <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white"><?php echo count(array_filter($submissions, fn($s) => $s['status'] === 'pending')); ?></p>
            </div>
            <div class="text-blue-600">
                <!-- <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg> -->
                <svg class="w-8 h-8 text-primary-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13V8m0 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>

            </div>
        </div>
    </div>

    <!-- Stats Card 2 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Approved</p>
                <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white"><?php echo count(array_filter($submissions, fn($s) => $s['status'] === 'approved')); ?></p>
            </div>
            <div class="text-green-600">
                <!-- <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg> -->
                <svg class="w-8 h-8 text-green-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 11.5 11 14l4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>

            </div>
        </div>
    </div>

    <!-- Stats Card 3 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Rejected</p>
                <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white"><?php echo count(array_filter($submissions, fn($s) => $s['status'] === 'rejected')); ?></p>
            </div>
            <div class="text-red-600">
                <!-- <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg> -->
                <svg class="w-8 h-8 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>

            </div>
        </div>
    </div>
</div>

<?php if ($compareSubmission): ?>
    <?php Card::open('🔍 Compare Submission to Attendance Records', 'Review the submitted PDF against the actual attendance logs for the same month'); ?>
        <div class="flex justify-end mb-4">
            <a href="hr.php" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:outline-none focus:ring-gray-300 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Close Comparison
            </a>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold mb-4">Submitted PDF</h3>
                <iframe src="download-submission.php?id=<?php echo $compareSubmission['id']; ?>" class="w-full h-[600px] border rounded-lg"></iframe>
            </div>
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm overflow-x-auto overflow-y-auto">
                <h3 class="text-lg font-semibold mb-4">Actual Attendance Records</h3>
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Time</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Mode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compareAttendance as $log): ?>
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-4 py-2"><?php echo htmlspecialchars(date('Y-m-d', strtotime($log['record_datetime']))); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars(date('H:i:s', strtotime($log['record_datetime']))); ?></td>
                                <td class="px-4 py-2"><?php echo $log['in_out'] == 0 ? 'Duty On' : 'Duty Off'; ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($log['mode']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($attendancePages > 1): ?>
                <div class="flex items-center justify-between mt-4">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Showing <?php echo (($attendancePage - 1) * $attendancePerPage) + 1; ?> to <?php echo min($attendancePage * $attendancePerPage, $attendanceTotal); ?> of <?php echo $attendanceTotal; ?> records
                    </div>
                    <div class="flex space-x-1">
                        <?php if ($attendancePage > 1): ?>
                            <a href="hr.php?compare_id=<?php echo $compareId; ?>&attendance_page=<?php echo $attendancePage - 1; ?>" 
                               class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-50 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $attendancePage - 2); $i <= min($attendancePages, $attendancePage + 2); $i++): ?>
                            <a href="hr.php?compare_id=<?php echo $compareId; ?>&attendance_page=<?php echo $i; ?>" 
                               class="px-3 py-2 text-sm font-medium <?php echo $i === $attendancePage ? 'text-blue-600 bg-blue-50 border border-blue-300 dark:bg-gray-700 dark:text-blue-400' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($attendancePage < $attendancePages): ?>
                            <a href="hr.php?compare_id=<?php echo $compareId; ?>&attendance_page=<?php echo $attendancePage + 1; ?>" 
                               class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-50 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php Card::close(); ?>
<?php endif; ?>

<!-- Submissions Review -->
<?php Card::open('📋 Submissions for Review', 'Review employee submitted PDFs'); ?>
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th class="px-6 py-3">Employee</th>
                <th class="px-6 py-3">Month</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Submitted At</th>
                <th class="px-6 py-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($submissions as $sub): ?>
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <td class="px-6 py-4"><?php echo htmlspecialchars($sub['name']); ?> (<?php echo htmlspecialchars($sub['en_no']); ?>)</td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($sub['month']); ?></td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            <?php echo $sub['status'] === 'approved' ? 'text-green-800 bg-green-100' : ($sub['status'] === 'rejected' ? 'text-red-800 bg-red-100' : 'text-yellow-800 bg-yellow-100'); ?>">
                            <?php echo ucfirst($sub['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($sub['submitted_at']); ?></td>
                    <td class="px-6 py-4">
                        <?php if ($sub['status'] === 'pending'): ?>
                            <button onclick="reviewSubmission(<?php echo $sub['id']; ?>, 'approved')" class="text-green-600 hover:text-green-800">Approve</button> |
                            <button onclick="reviewSubmission(<?php echo $sub['id']; ?>, 'rejected')" class="text-red-600 hover:text-red-800">Reject</button>
                            <br>
                        <?php endif; ?>
                        <?php if ($sub['file_path']): ?>
                            <a href="download-submission.php?id=<?php echo $sub['id']; ?>" target="_blank" class="text-blue-600 hover:text-blue-800">View PDF</a>
                            <br>
                            <a href="hr.php?compare_id=<?php echo $sub['id']; ?>" class="text-indigo-600 hover:text-indigo-800">Compare with record</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php Card::close(); ?>

<!-- Review Modal -->
<div id="reviewModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <button type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="reviewModal">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
                <span class="sr-only">Close modal</span>
            </button>
            <div class="p-4 md:p-5 text-center">
                <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400" id="modalTitle">Review Submission</h3>
                <form action="hr.php" method="POST" class="space-y-4">
                    <input type="hidden" name="id" id="reviewId">
                    <input type="hidden" name="status" id="reviewStatus">
                    <input type="hidden" name="review" value="1">
                    <div>
                        <label for="remarks" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Remarks (optional)</label>
                        <textarea name="remarks" id="remarks" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white"></textarea>
                    </div>
                    <div class="flex items-center justify-center space-x-4">
                        <button type="button" data-modal-hide="reviewModal" class="px-5 py-2.5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">Cancel</button>
                        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function reviewSubmission(id, status) {
    document.getElementById('reviewId').value = id;
    document.getElementById('reviewStatus').value = status;
    document.getElementById('modalTitle').textContent = status === 'approved' ? 'Approve Submission' : 'Reject Submission';
    
    // Show Flowbite modal
    const modal = new Modal(document.getElementById('reviewModal'));
    modal.show();
}
</script>

<?php if (isset($alertType)): ?>
    <?php AlertBox::show($alertType, $alertMessage); ?>
<?php endif; ?>

<?php Header::close(); ?>