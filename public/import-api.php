<?php
/**
 * Import API Handler
 * 
 * Handles AJAX requests for importing attendance records
 */

session_start();
header('Content-Type: application/json');

require_once '../src/config/config.php';
require_once '../src/Core/Database.php';
require_once '../src/Core/Auth.php';
require_once '../src/Handlers/NotificationHandler.php';
require_once '../src/Handlers/ImportHandler.php';

use App\Core\Auth;
use App\Handlers\ImportHandler;
use App\Handlers\NotificationHandler;

// Verify authentication and authorization
Auth::requireLogin();
Auth::requirePasswordChange();
Auth::requireRole([ROLE_ADMIN, ROLE_HR]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$handler = new ImportHandler();
$result = $handler->import($_FILES);

echo json_encode($result);
