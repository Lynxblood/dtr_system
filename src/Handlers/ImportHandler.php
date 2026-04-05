<?php
/**
 * ImportHandler
 * 
 * Handles attendance record imports from biometric terminal files
 */

namespace App\Handlers;

require_once __DIR__ . '/NotificationHandler.php';

use App\Core\Database;
use App\Handlers\NotificationHandler;

class ImportHandler
{
    private $db;
    private $defaultPassword;
    private $notificationHandler;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->defaultPassword = password_hash(DEFAULT_PASSWORD, PASSWORD_BCRYPT);
        $this->notificationHandler = new NotificationHandler();
    }

    /**
     * Import attendance records from uploaded file
     */
    public function import($file)
    {
        $file = $file['file'] ?? $file;

        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['status' => 'error', 'message' => 'No file uploaded or upload error.'];
        }

        if ($file['size'] > MAX_UPLOAD_SIZE) {
            return ['status' => 'error', 'message' => 'File size exceeds maximum allowed size.'];
        }

        try {
            $lines = file($file['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if (empty($lines)) {
                return ['status' => 'error', 'message' => 'File is empty.'];
            }

            $insertedRecords = 0;
            $newEmployees = 0;

            $this->db->beginTransaction();

            foreach ($lines as $line) {
                $cols = explode("\t", $line);

                if (count($cols) >= 7 && is_numeric(trim($cols[0]))) {
                    $tm_no = (int)trim($cols[1]);
                    $en_no = (int)trim($cols[2]);
                    $name = trim($cols[3]);
                    $in_out = (int)trim($cols[4]);
                    $mode = (int)trim($cols[5]);
                    $datetime = date('Y-m-d H:i:s', strtotime(trim($cols[6])));

                    // Insert or ignore employee
                    $empStmt = $this->db->query(
                        "INSERT IGNORE INTO employees (en_no, name) VALUES (?, ?)",
                        [$en_no, $name]
                    );

                    if ($empStmt->rowCount() > 0) {
                        // Create user account for new employee
                        $this->db->query(
                            "INSERT IGNORE INTO users (username, password, role, en_no, requires_password_change) VALUES (?, ?, ?, ?, 1)",
                            [(string)$en_no, $this->defaultPassword, ROLE_EMPLOYEE, $en_no]
                        );
                        $newEmployees++;
                    }

                    // Insert log
                    $logStmt = $this->db->query(
                        "INSERT IGNORE INTO attendance_logs (tm_no, en_no, in_out, mode, record_datetime) VALUES (?, ?, ?, ?, ?)",
                        [$tm_no, $en_no, $in_out, $mode, $datetime]
                    );

                    if ($logStmt->rowCount() > 0) {
                        $insertedRecords++;
                    }
                }
            }

            $this->db->commit();

            // Send notification to HR
            if ($insertedRecords > 0) {
                $this->notificationHandler->sendNotificationToRole(
                    "Admin imported $insertedRecords new attendance records" . ($newEmployees > 0 ? " and created accounts for $newEmployees new employees" : "") . ".",
                    ROLE_HR
                );
            }

            return [
                'status' => 'success',
                'message' => "Import complete. Added $insertedRecords logs and created accounts for $newEmployees new employees."
            ];
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Validate file format
     */
    private function validateFile($filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        return strtolower($extension) === 'txt';
    }
}
