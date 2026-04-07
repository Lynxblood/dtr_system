<?php
/**
 * Employee Dashboard
 */

session_start();
require_once '../src/config/config.php';
require_once '../src/Core/Database.php';
require_once '../src/Core/Auth.php';
require_once '../src/Components/Header.php';
require_once '../src/Components/Card.php';
require_once '../src/Components/AlertBox.php';
require_once '../src/Handlers/ExportHandler.php';
require_once '../src/Handlers/SubmissionHandler.php';
require_once '../src/Handlers/NotificationHandler.php';
require_once '../src/Handlers/SigningHandler.php';

use App\Core\Auth;
use App\Core\Database;
use App\Components\Header;
use App\Components\Card;
use App\Components\AlertBox;
use App\Handlers\ExportHandler;
use App\Handlers\SubmissionHandler;
use App\Handlers\NotificationHandler;
use App\Handlers\SigningHandler;

// Authentication and Authorization
Auth::requireLogin();
Auth::requirePasswordChange();
Auth::requireRole([ROLE_EMPLOYEE]);

$user = Auth::getUser();
$en_no = $user['en_no'];

$db = Database::getInstance();

// Get employee name
$employeeData = $db->fetchOne("SELECT name FROM employees WHERE en_no = ?", [$en_no]);
$user['name'] = $employeeData ? $employeeData['name'] : 'Unknown Employee';

$exportHandler = new ExportHandler();
$submissionHandler = new SubmissionHandler();
$notificationHandler = new NotificationHandler();
$signingHandler = new SigningHandler();

// Handle PDF export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'export_pdf') {
    $month = $_POST['month'] ?? date('Y-m');
    $exportHandler->exportPDF($en_no, $month);
}

// Handle PDF signing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sign_pdf') {
    $signingData = [
        'certificate_id' => $_POST['certificate_id'],
        'style_id' => $_POST['style_id'] ?? 0,
        'name' => $_POST['sign_name'],
        'location' => $_POST['sign_location'],
        'date' => $_POST['sign_date'],
        'unique' => $_POST['sign_unique'],
        'font_family' => $_POST['font_family'] ?? 'dejavusans',
        'font_size' => $_POST['font_size'] ?? 9,
        'font_style' => $_POST['font_style'] ?? '',
        'include_name' => isset($_POST['include_name']),
        'include_location' => isset($_POST['include_location']),
        'include_date' => isset($_POST['include_date']),
        'include_unique' => isset($_POST['include_unique'])
    ];

    $result = $signingHandler->signPDF($_FILES['pdf_file'], $signingData['certificate_id'], $signingData['style_id'], $signingData, $_POST['cert_password']);
    if ($result['status'] === 'success') {
        // Now submit the signed file
        $signedFile = [
            'name' => basename($result['signed_path']),
            'tmp_name' => $result['signed_path'],
            'error' => 0
        ];
        $submitResult = $submissionHandler->submitByEmployee($en_no, $_POST['month'], $signedFile);
        $alertType = $submitResult['status'] === 'success' ? 'success' : 'error';
        $alertMessage = $submitResult['message'];
    } else {
        $alertType = 'error';
        $alertMessage = $result['message'];
    }
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['signed_file'])) {
    $month = $_POST['month'];
    $result = $submissionHandler->submitByEmployee($en_no, $month, $_FILES['signed_file']);
    $alertType = $result['status'] === 'success' ? 'success' : 'error';
    $alertMessage = $result['message'];
}

// Get submissions
$submissions = $submissionHandler->getEmployeeSubmissions($en_no);

// Get notifications
$notifications = $notificationHandler->getNotifications($en_no);
$unreadCount = $notificationHandler->getUnreadCount($en_no);

Header::render('Employee Dashboard');
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
                <p class="text-gray-600 dark:text-gray-400 text-sm">My Submissions</p>
                <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white"><?php echo count($submissions); ?></p>
            </div>
            <div class="text-blue-600">
                <!-- <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 000 16zm0-14a7 7 0 100 14 7 7 0 000-14z"></path>
                </svg> -->
                <svg class="w-8 h-8 text-primary-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 17v-5h1.5a1.5 1.5 0 1 1 0 3H5m12 2v-5h2m-2 3h2M5 10V7.914a1 1 0 0 1 .293-.707l3.914-3.914A1 1 0 0 1 9.914 3H18a1 1 0 0 1 1 1v6M5 19v1a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-1M10 3v4a1 1 0 0 1-1 1H5m6 4v5h1.375A1.627 1.627 0 0 0 14 15.375v-1.75A1.627 1.627 0 0 0 12.375 12H11Z"/>
                </svg>

            </div>
        </div>
    </div>

    <!-- Stats Card 2 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Notifications</p>
                <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white"><?php echo $unreadCount; ?> Unread</p>
            </div>
            <div class="text-green-600">
                <!-- <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM8 16a2 2 0 104 0H8z"></path>
                </svg> -->
                <svg class="w-8 h-8 text-green-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m10.827 5.465-.435-2.324m.435 2.324a5.338 5.338 0 0 1 6.033 4.333l.331 1.769c.44 2.345 2.383 2.588 2.6 3.761.11.586.22 1.171-.31 1.271l-12.7 2.377c-.529.099-.639-.488-.749-1.074C5.813 16.73 7.538 15.8 7.1 13.455c-.219-1.169.218 1.162-.33-1.769a5.338 5.338 0 0 1 4.058-6.221Zm-7.046 4.41c.143-1.877.822-3.461 2.086-4.856m2.646 13.633a3.472 3.472 0 0 0 6.728-.777l.09-.5-6.818 1.277Z"/>
                </svg>

            </div>
        </div>
    </div>
</div>

<!-- Download and Submit Section -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Download PDF -->
    <?php Card::open('📥 Download Attendance PDF', 'Download your attendance record for the month'); ?>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Download a PDF of your attendance records for signing.
        </p>
        
        <form action="employee.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="export_pdf">
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
            <button 
                type="submit" 
                class="w-full px-5 py-2.5 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition">
                Download PDF
            </button>
        </form>
    <?php Card::close(); ?>

    <!-- Sign and Submit PDF -->
    <?php Card::open('📝 Sign & Submit PDF', 'Sign your attendance PDF and submit to head'); ?>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Upload your attendance PDF, sign it electronically, and submit to your head for approval.
        </p>

        <form action="employee.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="sign_pdf">
            <div>
                <label for="month_sign" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Month</label>
                <input
                    type="month"
                    name="month"
                    id="month_sign"
                    value="<?php echo date('Y-m'); ?>"
                    required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label for="pdf_file" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    PDF File to Sign
                </label>
                <input
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-gray-700 dark:file:text-blue-400"
                    id="pdf_file"
                    name="pdf_file"
                    type="file"
                    accept=".pdf"
                    required>
            </div>
            <div>
                <label for="certificate_id" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Certificate</label>
                <select name="certificate_id" id="certificate_id" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Select Certificate</option>
                    <?php
                    $certificates = $signingHandler->getCertificates();
                    foreach ($certificates as $cert): ?>
                        <option value="<?php echo $cert['id']; ?>"><?php echo htmlspecialchars($cert['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="cert_password" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Certificate Password</label>
                <input
                    type="password"
                    name="cert_password"
                    id="cert_password"
                    required
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="sign_name" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input
                        type="text"
                        name="sign_name"
                        id="sign_name"
                        value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label for="sign_location" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                    <input
                        type="text"
                        name="sign_location"
                        id="sign_location"
                        value="Office"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="sign_date" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                    <input
                        type="date"
                        name="sign_date"
                        id="sign_date"
                        value="<?php echo date('Y-m-d'); ?>"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label for="sign_unique" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Unique ID</label>
                    <input
                        type="text"
                        name="sign_unique"
                        id="sign_unique"
                        value="<?php echo uniqid(); ?>"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Include in Signature:</label>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="include_name" id="include_name" checked class="mr-2">
                        Name
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="include_location" id="include_location" checked class="mr-2">
                        Location
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="include_date" id="include_date" checked class="mr-2">
                        Date
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="include_unique" id="include_unique" checked class="mr-2">
                        Unique ID
                    </label>
                </div>
            </div>
            <button
                type="submit"
                class="w-full px-5 py-2.5 text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800 transition">
                Sign & Submit
            </button>
        </form>

        <?php if (isset($alertType)): ?>
            <?php AlertBox::show($alertType, $alertMessage); ?>
        <?php endif; ?>
    <?php Card::close(); ?>
</div>

<!-- Submissions Table -->
<?php Card::open('📋 My Submissions', 'Status of your submitted PDFs'); ?>
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th class="px-6 py-3">Month</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Submitted At</th>
                <th class="px-6 py-3">Remarks</th>
                <th class="px-6 py-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($submissions as $sub): ?>
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <td class="px-6 py-4"><?php echo htmlspecialchars($sub['month']); ?></td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            <?php 
                            $statusColors = [
                                'draft' => 'text-gray-800 bg-gray-100',
                                'employee_signed' => 'text-blue-800 bg-blue-100',
                                'submitted_to_head' => 'text-yellow-800 bg-yellow-100',
                                'head_signed' => 'text-purple-800 bg-purple-100',
                                'submitted_to_president' => 'text-orange-800 bg-orange-100',
                                'president_signed' => 'text-indigo-800 bg-indigo-100',
                                'submitted_to_hr' => 'text-cyan-800 bg-cyan-100',
                                'approved' => 'text-green-800 bg-green-100',
                                'rejected' => 'text-red-800 bg-red-100'
                            ];
                            echo $statusColors[$sub['status']] ?? 'text-gray-800 bg-gray-100';
                            ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $sub['status'])); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($sub['submitted_at']); ?></td>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($sub['remarks'] ?? ''); ?></td>
                    <td class="px-6 py-4">
                        <?php if ($sub['file_path']): ?>
                            <a href="download-submission.php?id=<?php echo $sub['id']; ?>" target="_blank" class="text-blue-600 hover:text-blue-800">View PDF</a>
                        <?php else: ?>
                            <span class="text-gray-500">No file</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php Card::close(); ?>

<?php Header::close(); ?>