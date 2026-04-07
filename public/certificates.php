<?php
/**
 * Certificate Management
 *
 * Allows all users to manage digital certificates and signature styles
 */

session_start();
require_once '../src/config/config.php';
require_once '../src/Core/Database.php';
require_once '../src/Core/Auth.php';
require_once '../src/Components/Header.php';
require_once '../src/Components/Card.php';
require_once '../src/Components/AlertBox.php';
require_once '../src/Handlers/SigningHandler.php';

use App\Core\Auth;
use App\Core\Database;
use App\Components\Header;
use App\Components\Card;
use App\Components\AlertBox;
use App\Handlers\SigningHandler;

// Authentication and Authorization
Auth::requireLogin();
Auth::requirePasswordChange();

$user = Auth::getUser();
$db = Database::getInstance();
$signingHandler = new SigningHandler();

$errors = [];
$successMessage = '';

// Handle certificate upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_certificate') {
    $title = trim($_POST['certificate_title'] ?? '');

    if ($title === '') {
        $errors[] = 'Certificate title is required.';
    }

    $certFile = $_FILES['certificate_file'] ?? null;
    if (empty($certFile) || $certFile['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please upload a valid .p12 certificate file.';
    } elseif (strtolower(pathinfo($certFile['name'], PATHINFO_EXTENSION)) !== 'p12') {
        $errors[] = 'Only .p12 certificate files are supported.';
    }

    if (empty($errors)) {
        $result = $signingHandler->addCertificate($title, $certFile);
        if ($result['status'] === 'success') {
            $successMessage = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Handle signature style creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_style') {
    $title = trim($_POST['style_title'] ?? '');
    $certificateId = intval($_POST['style_certificate_id'] ?? 0);
    $fontFamily = trim($_POST['font_family'] ?? 'dejavusans');
    $fontSize = intval($_POST['font_size'] ?? 9);
    $fontStyle = trim($_POST['font_style'] ?? '');

    if ($title === '') {
        $errors[] = 'Signature style name is required.';
    }

    if ($certificateId <= 0) {
        $errors[] = 'Please choose a certificate to attach to this style.';
    }

    if ($fontSize < 6 || $fontSize > 24) {
        $fontSize = 9;
    }

    if (empty($errors)) {
        $styleData = [
            'title' => $title,
            'certificate_id' => $certificateId,
            'font_family' => $fontFamily,
            'font_size' => $fontSize,
            'font_style' => $fontStyle,
            'show_name' => isset($_POST['include_name']),
            'show_location' => isset($_POST['include_location']),
            'show_date' => isset($_POST['include_date']),
            'show_unique' => isset($_POST['include_unique'])
        ];

        $result = $signingHandler->addSignatureStyle($styleData);
        if ($result['status'] === 'success') {
            $successMessage = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Get data
$certificates = $signingHandler->getCertificates();
$styles = $signingHandler->getSignatureStyles();

Header::render('Certificate Management');
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Certificate Management</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Manage your digital certificates and signature styles for electronic document signing.</p>
    </div>

    <!-- Alerts -->
    <?php if (!empty($errors)): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Errors:</h3>
                    <ul class="mt-2 text-sm text-red-700">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($successMessage); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Add Certificate -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Add Certificate</h2>
                <form action="certificates.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="add_certificate">

                    <div>
                        <label for="certificate_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Certificate Title
                        </label>
                        <input
                            type="text"
                            name="certificate_title"
                            id="certificate_title"
                            placeholder="Office signing cert"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label for="certificate_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Certificate File (.p12)
                        </label>
                        <input
                            type="file"
                            name="certificate_file"
                            id="certificate_file"
                            accept=".p12"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-l-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Upload your .p12 certificate file</p>
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-200">
                        Add Certificate
                    </button>
                </form>
            </div>
        </div>

        <!-- Add Signature Style -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Add Signature Style</h2>
                <form action="certificates.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_style">

                    <div>
                        <label for="style_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Style Name
                        </label>
                        <input
                            type="text"
                            name="style_title"
                            id="style_title"
                            placeholder="My signature style"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label for="style_certificate_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Certificate
                        </label>
                        <select
                            name="style_certificate_id"
                            id="style_certificate_id"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Select Certificate</option>
                            <?php foreach ($certificates as $cert): ?>
                                <option value="<?php echo $cert['id']; ?>"><?php echo htmlspecialchars($cert['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="font_family" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Font Family
                            </label>
                            <select
                                name="font_family"
                                id="font_family"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="dejavusans">DejaVu Sans</option>
                                <option value="helvetica">Helvetica</option>
                                <option value="times">Times</option>
                                <option value="courier">Courier</option>
                            </select>
                        </div>

                        <div>
                            <label for="font_size" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Font Size
                            </label>
                            <input
                                type="number"
                                name="font_size"
                                id="font_size"
                                value="9"
                                min="6"
                                max="24"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label for="font_style" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Font Style
                        </label>
                        <select
                            name="font_style"
                            id="font_style"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Normal</option>
                            <option value="B">Bold</option>
                            <option value="I">Italic</option>
                            <option value="BI">Bold Italic</option>
                        </select>
                    </div>

                    <fieldset class="border border-gray-300 rounded-md p-4 dark:border-gray-600">
                        <legend class="text-sm font-medium text-gray-700 dark:text-gray-300">Include in Signature</legend>
                        <div class="space-y-2 mt-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="include_name" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Name</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="include_location" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Location</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="include_date" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Date</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="include_unique" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Unique ID</span>
                            </label>
                        </div>
                    </fieldset>

                    <button
                        type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-200">
                        Add Signature Style
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Certificates List -->
    <div class="mt-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Your Certificates</h2>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <?php if (empty($certificates)): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                    No certificates uploaded yet.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($certificates as $cert): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($cert['title']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo date('M j, Y', strtotime($cert['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Signature Styles List -->
    <div class="mt-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Your Signature Styles</h2>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <?php if (empty($styles)): ?>
                <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                    No signature styles created yet.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Style Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Certificate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Font</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Includes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($styles as $style): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($style['title']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($style['certificate_title']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?php echo htmlspecialchars($style['font_family']); ?>, <?php echo $style['font_size']; ?>pt
                                        <?php if ($style['font_style']): ?>
                                            (<?php echo $style['font_style']; ?>)
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <?php
                                        $includes = [];
                                        if ($style['show_name']) $includes[] = 'Name';
                                        if ($style['show_location']) $includes[] = 'Location';
                                        if ($style['show_date']) $includes[] = 'Date';
                                        if ($style['show_unique']) $includes[] = 'ID';
                                        echo implode(', ', $includes);
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo date('M j, Y', strtotime($style['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php Header::close(); ?>