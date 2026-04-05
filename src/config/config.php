<?php
/**
 * Application Configuration
 * 
 * Central configuration file for database and app settings
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'dms');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Constants
define('APP_NAME', 'Time & Attendance System');

$scheme = 'http';
if ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === '1')) || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) {
    $scheme = 'https';
} elseif (isset($_SERVER['REQUEST_SCHEME'])) {
    $scheme = $_SERVER['REQUEST_SCHEME'];
}

$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'] ?? 'localhost');
$scriptDir = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';

// Ensure APP_URL points to project root, not /public when script is in public folder.
$appDir = $scriptDir;
if (basename($appDir) === 'public') {
    $appDir = dirname($appDir);
}

define('APP_URL', rtrim($scheme . '://' . $host . $appDir, '/'));
define('DEFAULT_PASSWORD', 'ilove@BASC1');
define('MIN_PASSWORD_LENGTH', 8);

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('SESSION_NAME', 'dms_session');

// File Upload
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_TEMP_DIR', __DIR__ . '/../../uploads/');

// Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_HR', 'hr');
define('ROLE_EMPLOYEE', 'employee');

// Timezone
date_default_timezone_set('UTC');
