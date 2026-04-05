<?php
/**
 * Download or view a submitted PDF securely
 */

session_start();
require_once '../src/config/config.php';
require_once '../src/Core/Database.php';
require_once '../src/Core/Auth.php';
require_once '../src/Handlers/SubmissionHandler.php';

use App\Core\Auth;
use App\Handlers\SubmissionHandler;

Auth::requireLogin();

$submissionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$handler = new SubmissionHandler();
$submission = $handler->getSubmission($submissionId);

if (!$submission) {
    http_response_code(404);
    exit('Submission not found.');
}

$userRole = Auth::getRole();
$userEnNo = Auth::getEnNo();
if ($userRole !== ROLE_HR && $submission['en_no'] !== $userEnNo) {
    http_response_code(403);
    exit('Access denied.');
}

$filePath = $submission['file_path'];
if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found.');
}

$mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
$filename = basename($filePath);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
