<?php
/**
 * President Dashboard
 */

session_start();
require_once '../src/config/config.php';
require_once '../src/Core/Database.php';
require_once '../src/Core/Auth.php';
require_once '../src/Components/Header.php';
require_once '../src/Components/Card.php';
require_once '../src/Components/AlertBox.php';
require_once '../src/Handlers/SubmissionHandler.php';
require_once '../src/Handlers/SigningHandler.php';
require_once '../src/Handlers/NotificationHandler.php';

use App\Core\Auth;
use App\Core\Database;
use App\Components\Header;
use App\Components\Card;
use App\Components\AlertBox;
use App\Handlers\SubmissionHandler;
use App\Handlers\SigningHandler;
use App\Handlers\NotificationHandler;

// Authentication and Authorization
Auth::requireLogin();
Auth::requirePasswordChange();
Auth::requireRole([ROLE_PRESIDENT]);

$user = Auth::getUser();

$db = Database::getInstance();
$submissionHandler = new SubmissionHandler();
$signingHandler = new SigningHandler();
$notificationHandler = new NotificationHandler();

// Handle signing and submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sign_submit') {
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
        // Submit the signed file
        $signedFile = [
            'name' => basename($result['signed_path']),
            'tmp_name' => $result['signed_path'],
            'error' => 0
        ];
        $submitResult = $submissionHandler->submitByPresident($_POST['submission_id'], $signedFile);
        $alertType = $submitResult['status'] === 'success' ? 'success' : 'error';
        $alertMessage = $submitResult['message'];
    } else {
        $alertType = 'error';
        $alertMessage = $result['message'];
    }
}

// Get submissions for president
$submissions = $submissionHandler->getSubmissionsForPresident();

// Get notifications
$notifications = $notificationHandler->getNotifications($user['id'], null);
$unreadCount = $notificationHandler->getUnreadCount($user['id'], null);

Header::render('President Dashboard');
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
                <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white"><?php echo count($submissions); ?></p>
            </div>
            <div class="text-blue-600">
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
                <svg class="w-8 h-8 text-green-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m10.827 5.465-.435-2.324m.435 2.324a5.338 5.338 0 0 1 6.033 4.333l.331 1.769c.44 2.345 2.383 2.588 2.6 3.761.11.586.22 1.171-.31 1.271l-12.7 2.377c-.529.099-.639-.488-.749-1.074C5.813 16.73 7.538 15.8 7.1 13.455c-.219-1.169.218 1.162-.33-1.769a5.338 5.338 0 0 1 4.058-6.221Zm-7.046 4.41c.143-1.877.822-3.461 2.086-4.856m2.646 13.633a3.472 3.472 0 0 0 6.728-.777l.09-.5-6.818 1.277Z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Pending Reviews -->
<?php Card::open('📋 Pending Reviews', 'Submissions from heads awaiting presidential signature'); ?>
    <?php if (empty($submissions)): ?>
        <p class="text-gray-500 dark:text-gray-400">No pending reviews.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($submissions as $sub): ?>
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-semibold"><?php echo htmlspecialchars($sub['name']); ?> - <?php echo htmlspecialchars($sub['month']); ?></h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Submitted: <?php echo htmlspecialchars($sub['submitted_at']); ?></p>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full text-yellow-800 bg-yellow-100">
                            <?php echo ucfirst(str_replace('_', ' ', $sub['status'])); ?>
                        </span>
                    </div>

                    <?php if ($sub['file_path']): ?>
                        <div class="mb-4">
                            <a href="download-submission.php?id=<?php echo $sub['id']; ?>" target="_blank" class="text-blue-600 hover:text-blue-800">View PDF</a>
                        </div>
                    <?php endif; ?>

                    <!-- Sign and Submit Form -->
                    <form action="president.php" method="POST" enctype="multipart/form-data" class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                        <input type="hidden" name="action" value="sign_submit">
                        <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="certificate_id_<?php echo $sub['id']; ?>" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Certificate</label>
                                <select name="certificate_id" id="certificate_id_<?php echo $sub['id']; ?>" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Select Certificate</option>
                                    <?php
                                    $certificates = $signingHandler->getCertificates();
                                    foreach ($certificates as $cert): ?>
                                        <option value="<?php echo $cert['id']; ?>"><?php echo htmlspecialchars($cert['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="cert_password_<?php echo $sub['id']; ?>" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Certificate Password</label>
                                <input
                                    type="password"
                                    name="cert_password"
                                    id="cert_password_<?php echo $sub['id']; ?>"
                                    required
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="sign_name_<?php echo $sub['id']; ?>" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                <input
                                    type="text"
                                    name="sign_name"
                                    id="sign_name_<?php echo $sub['id']; ?>"
                                    value="President Approval"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label for="sign_location_<?php echo $sub['id']; ?>" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                                <input
                                    type="text"
                                    name="sign_location"
                                    id="sign_location_<?php echo $sub['id']; ?>"
                                    value="Presidential Office"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="sign_date_<?php echo $sub['id']; ?>" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                                <input
                                    type="date"
                                    name="sign_date"
                                    id="sign_date_<?php echo $sub['id']; ?>"
                                    value="<?php echo date('Y-m-d'); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label for="sign_unique_<?php echo $sub['id']; ?>" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Unique ID</label>
                                <input
                                    type="text"
                                    name="sign_unique"
                                    id="sign_unique_<?php echo $sub['id']; ?>"
                                    value="<?php echo uniqid('PRES_'); ?>"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Include in Signature:</label>
                            <div class="flex flex-wrap gap-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_name" id="include_name_<?php echo $sub['id']; ?>" checked class="mr-2">
                                    Name
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_location" id="include_location_<?php echo $sub['id']; ?>" checked class="mr-2">
                                    Location
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_date" id="include_date_<?php echo $sub['id']; ?>" checked class="mr-2">
                                    Date
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_unique" id="include_unique_<?php echo $sub['id']; ?>" checked class="mr-2">
                                    Unique ID
                                </label>
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="px-4 py-2 text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800 transition">
                            Sign & Submit to HR
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($alertType)): ?>
        <?php AlertBox::show($alertType, $alertMessage); ?>
    <?php endif; ?>
<?php Card::close(); ?>

<?php Header::close(); ?>